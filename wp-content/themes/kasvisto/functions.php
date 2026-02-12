<?php

/**
 * 1. Rekisteröidään "Kasvi" sisältötyyppi
 */
function rekisteroi_kasvit_cpt() {
    $labels = array(
        'name'               => 'Kasvit',
        'singular_name'      => 'Kasvi',
        'add_new'            => 'Lisää uusi kasvi',
        'add_new_item'       => 'Lisää uusi kasvi',
        'edit_item'          => 'Muokkaa kasvia',
        'all_items'          => 'Kaikki kasvit',
        'search_items'       => 'Etsi kasveja',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-palmtree',
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
        'show_in_rest'       => true,
        'hierarchical'       => false,
        'rewrite'            => array('slug' => 'kasvit'),
    );

    register_post_type('kasvi', $args);
}
add_action('init', 'rekisteroi_kasvit_cpt');


function tyhjenna_kaikki_kasvit() {
    if ( !isset($_GET['tyhjenna-kasvit']) || !current_user_can('administrator') ) {
        return;
    }

    global $wpdb;
    
    // Tehostusasetukset
    set_time_limit(0);
    
    // Haetaan kaikkien 'kasvi'-tyyppisten postauksien ID:t suoraan tietokannasta
    $post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", 'kasvi' ) );

    if ( empty($post_ids) ) {
        die("Ei poistettavia kasveja löytynyt.");
    }

    $count = 0;
    foreach ( $post_ids as $post_id ) {
        // Poistetaan postaus ja kaikki siihen liittyvät metadata (ACF-kentät)
        wp_delete_post( $post_id, true );
        $count++;
    }

    echo "<h1>Pöytä puhdistettu!</h1>";
    echo "Poistettiin yhteensä $count kasvia.";
    exit;
}


function paivita_kuvalinkit_csv_stasta() {
    if ( !isset($_GET['paivita-kuvat']) || !current_user_can('administrator') ) {
        return;
    }

    global $wpdb;
    set_time_limit(0);

    $csv_file = ABSPATH . 'wp-content/kaikki_puhdistettu.csv';
    
    if ( !file_exists($csv_file) ) {
        die("Tiedostoa ei löydy!");
    }

    $file = fopen($csv_file, 'r');
    $header = fgetcsv($file, 0, ';'); // Varmista että tässä on ; tai , riippuen CSV:stäsi

    $count = 0;
    while ( ($data = fgetcsv($file, 0, ';')) !== FALSE ) {
        $row = array_combine($header, $data);
        
        // Haetaan kasvin nimi (trim poistaa mahdolliset välilyönnit)
        $nimi = !empty($row['Suomenkielinen nimi']) ? trim($row['Suomenkielinen nimi']) : trim($row['Kasvi']);
        
        if (empty($nimi)) continue;

        // Etsitään olemassa olevan kasvin ID nimen perusteella
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'kasvi' AND post_status = 'publish' LIMIT 1", 
            $nimi
        ));

        if ($post_id) {
            // Päivitetään kuva-URL:t kenttiin kuva_1_url ... kuva_10_url
            for ($i = 1; $i <= 10; $i++) {
                $sarake_nimi = "Kuva $i"; // CSV-sarakkeen nimi, esim. "Kuva 1"
                $acf_kentta = "kuva_{$i}_url"; // ACF-kentän nimi
                
                if (!empty($row[$sarake_nimi])) {
                    update_field($acf_kentta, trim($row[$sarake_nimi]), $post_id);
                }
            }
            $count++;
        }
    }

    fclose($file);
    echo "<h1>Kuvalinkit päivitetty!</h1>";
    echo "Käsiteltiin $count kasvia.";
    exit;
}
add_action('init', 'paivita_kuvalinkit_csv_stasta');


/**
 * 2. Tuontityökalu CSV-tiedostolle
 * Ajetaan osoitteessa: localhost:8080/?tuo-kasvit
 */
function tuo_kasvit_csv_stasta() {
    if ( !isset($_GET['tuo-kasvit']) || !current_user_can('administrator') ) {
        return;
    }

    set_time_limit(0);
    ini_set('memory_limit', '512M');
    wp_defer_term_counting(true);
    wp_defer_comment_counting(true);

    $csv_file = ABSPATH . 'wp-content/kaikki_puhdistettu.csv';
    
    if ( !file_exists($csv_file) ) {
        die("Tiedostoa ei löydy polusta: " . $csv_file);
    }

    // Avataan tiedosto ja tunnistetaan erotinmerkki (oletuksena pilkku)
    $file = fopen($csv_file, 'r');
    $header = fgetcsv($file, 0, ';'); // Jos CSV:ssä on puolipisteet, muuta ',' -> ';'

    $count = 0;
    $errors = 0;

    while ( ($data = fgetcsv($file, 0, ';')) !== FALSE ) {
        // TÄRKEÄ KORJAUS: Tarkistetaan täsmäävätkö sarakkeet
        if (count($header) !== count($data)) {
            $errors++;
            continue; // Hypätään tämän rivin yli, jos se on rikki
        }

        $row = array_combine($header, $data);

        $nimi = !empty($row['Suomenkielinen nimi']) ? $row['Suomenkielinen nimi'] : $row['Kasvi'];
        if (empty($nimi)) continue;

        $existing_post = get_page_by_title($nimi, OBJECT, 'kasvi');

        $post_data = array(
            'post_title'   => $nimi,
            'post_content' => isset($row['Kuvaus']) ? $row['Kuvaus'] : '',
            'post_status'  => 'publish',
            'post_type'    => 'kasvi',
        );

        if ( $existing_post ) {
            $post_id = $existing_post->ID;
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if ($post_id && !is_wp_error($post_id)) {
            // Päivitetään ACF-kentät (lisätty isset-tarkistukset varmuuden vuoksi)
            $fields = array(
                'suomenkielinen_nimi' => 'Suomenkielinen nimi',
                'tieteellinen_nimi'   => 'Tieteellinen nimi',
                'ryhma'               => 'Ryhmä',
                'koko'                => 'Koko',
                'kasvupaikka'         => 'Kasvupaikka',
                'kemia'               => 'Kemia',
                'levinneisyys'        => 'Levinneisyys',
                'mikrosienet'         => 'Mikrosienet',
                'muuta'               => 'Muuta',
                'isantakasvit'        => 'Löydettyjä isäntäkasveja',
                'isantakasvin_muut'   => 'Isäntäkasvin muita piensieniä',
                'loytopaikat'         => 'Löytöpaikat Luopioisissa',
                'maastotuntomerkit'   => 'Maastotuntomerkit',
                'vertaa'              => 'Vertaa seuraaviin',
                'kirjallisuus'        => 'Kirjallisuus',
                'kuvaus'              => 'Kuvaus'
            );

            foreach ($fields as $acf_slug => $csv_col) {
                if (isset($row[$csv_col])) {
                    update_field($acf_slug, $row[$csv_col], $post_id);
                }
            }
            
            // Kuvat
            if (isset($row['Kuva 1'])) update_field('kuva_1_url', $row['Kuva 1'], $post_id);
            if (isset($row['Kuva 2'])) update_field('kuva_2_url', $row['Kuva 2'], $post_id);
        }

        $count++;
    }

    fclose($file);
    wp_defer_term_counting(false);
    wp_defer_comment_counting(false);

    echo "<h1>Tuonti suoritettu!</h1>";
    echo "<p>Onnistuneesti tuotu/päivitetty: $count kasvia.</p>";
    if ($errors > 0) {
        echo "<p style='color:red;'>Hylätty $errors riviä virheellisen sarakemäärän vuoksi.</p>";
    }
    exit;
}
add_action('init', 'tuo_kasvit_csv_stasta');