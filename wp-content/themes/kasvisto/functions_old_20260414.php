
// Kuvalinkkien päivitys (localhost:8080/?paivita-kuvat)
function paivita_kuvalinkit_csv_stasta() {
    if ( !isset($_GET['paivita-kuvat']) || !current_user_can('administrator') ) {
        return;
    }
    global $wpdb;
    set_time_limit(0);
    $csv_file = ABSPATH . 'wp-content/kaikki_puhdistettu.csv';
    if ( !file_exists($csv_file) ) die("Tiedostoa ei löydy!");
    $file = fopen($csv_file, 'r');
    $header = fgetcsv($file, 0, ';');
    $count = 0;
    while ( ($data = fgetcsv($file, 0, ';')) !== FALSE ) {
        $row = array_combine($header, $data);
        $nimi = !empty($row['Suomenkielinen nimi']) ? trim($row['Suomenkielinen nimi']) : trim($row['Kasvi']);
        if (empty($nimi)) continue;
        $post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'kasvi' AND post_status = 'publish' LIMIT 1", $nimi));
        if ($post_id) {
            for ($i = 1; $i <= 10; $i++) {
                $sarake_nimi = "Kuva $i";
                if (!empty($row[$sarake_nimi])) {
                    update_field("kuva_{$i}_url", trim($row[$sarake_nimi]), $post_id);
                }
            }
            $count++;
        }
    }
    fclose($file);
    echo "<h1>Kuvalinkit päivitetty!</h1> Käsiteltiin $count kasvia.";
    exit;
}
add_action('init', 'paivita_kuvalinkit_csv_stasta');

// Kasvien tuonti (localhost:8080/?tuo-kasvit)
function tuo_kasvit_csv_stasta() {
    if ( !isset($_GET['tuo-kasvit']) || !current_user_can('administrator') ) {
        return;
    }
    set_time_limit(0);
    ini_set('memory_limit', '512M');
    $csv_file = ABSPATH . 'wp-content/kaikki_puhdistettu.csv';
    if ( !file_exists($csv_file) ) die("Tiedostoa ei löydy!");
    $file = fopen($csv_file, 'r');
    $header = fgetcsv($file, 0, ';');
    $count = 0;
    while ( ($data = fgetcsv($file, 0, ';')) !== FALSE ) {
        if (count($header) !== count($data)) continue;
        $row = array_combine($header, $data);
        $nimi = !empty($row['Suomenkielinen nimi']) ? $row['Suomenkielinen nimi'] : $row['Kasvi'];
        if (empty($nimi)) continue;
        $post_data = array('post_title' => $nimi, 'post_content' => $row['Kuvaus'] ?? '', 'post_status' => 'publish', 'post_type' => 'kasvi');
        $existing = get_page_by_title($nimi, OBJECT, 'kasvi');
        $post_id = $existing ? wp_update_post(array_merge($post_data, ['ID' => $existing->ID])) : wp_insert_post($post_data);

        if ($post_id && !is_wp_error($post_id)) {
            $fields = [
                'suomenkielinen_nimi' => 'Suomenkielinen nimi', 'tieteellinen_nimi' => 'Tieteellinen nimi',
                'ryhma' => 'Ryhmä', 'koko' => 'Koko', 'kasvupaikka' => 'Kasvupaikka', 'kemia' => 'Kemia',
                'levinneisyys' => 'Levinneisyys', 'mikrosienet' => 'Mikrosienet', 'muuta' => 'Muuta',
                'isantakasvit' => 'Löydettyjä isäntäkasveja', 'isantakasvin_muut' => 'Isäntäkasvin muita piensieniä',
                'loytopaikat' => 'Löytöpaikat Luopioisissa', 'maastotuntomerkit' => 'Maastotuntomerkit',
                'vertaa' => 'Vertaa seuraaviin', 'kirjallisuus' => 'Kirjallisuus', 'kuvaus' => 'Kuvaus'
            ];
            foreach ($fields as $acf => $csv) { if (isset($row[$csv])) update_field($acf, $row[$csv], $post_id); }
            if (isset($row['Kuva 1'])) update_field('kuva_1_url', $row['Kuva 1'], $post_id);
            if (isset($row['Kuva 2'])) update_field('kuva_2_url', $row['Kuva 2'], $post_id);
        }
        $count++;
    }
    fclose($file);
    echo "<h1>Tuonti suoritettu!</h1> $count kasvia käsiteltiin.";
    exit;
}
add_action('init', 'tuo_kasvit_csv_stasta');

function hae_laji_fi_havainnot($tieteellinen_nimi) {
    if (empty($tieteellinen_nimi)) return null;
    $hakutermi = trim(preg_replace('/\s*\([^)]*\)/', '', $tieteellinen_nimi));
    $api_url = "https://api.laji.fi/v0/warehouse/query/unit/list";
    $token = "ba6318f58b94ce93bb2c3f835e5fa3cffa253232adee3531afbeb691abd4d732";
    
    $params = array(
        'target' => $hakutermi,
        'teamMemberId' => '2756012',
        'pageSize' => 500,
        'access_token' => $token
    );
    
    $url = add_query_arg($params, $api_url);
    $response = wp_remote_get($url, array('timeout' => 20));
    if (is_wp_error($response)) return null;
    return json_decode(wp_remote_retrieve_body($response), true);
}