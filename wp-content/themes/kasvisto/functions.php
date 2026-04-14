<?php
/**
 * Luopioisten Kasvisto - functions.php
 */

// 1. TEEMAN PERUSASETUKSET
function kasvi_theme_setup() {
    add_theme_support('title-tag'); // Automaattinen sivun otsikko välilehteen
    add_theme_support('post-thumbnails'); // Mahdollistaa artikkelikuvat
    
    // Rekisteröi navigaatiovalikko
    register_nav_menus(array(
        'primary' => 'Päävalikko',
    ));
}
add_action('after_setup_theme', 'kasvi_theme_setup');

// 2. LADATAAN TYYLITIEDOSTO
function kasvi_enqueue_styles() {
    wp_enqueue_style('kasvi-style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'kasvi_enqueue_styles');

// 3. REKISTERÖIDÄÄN "KASVI" SISÄLTÖTYYPPI (CPT)
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

// 4. TUONTITYÖKALUT JA APUFUNKTIOT

// Pöydän puhdistus (localhost:8080/?tyhjenna-kasvit)
function tyhjenna_kaikki_kasvit() {
    if ( !isset($_GET['tyhjenna-kasvit']) || !current_user_can('administrator') ) {
        return;
    }
    global $wpdb;
    set_time_limit(0);
    $post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", 'kasvi' ) );
    if ( empty($post_ids) ) {
        die("Ei poistettavia kasveja löytynyt.");
    }
    $count = 0;
    foreach ( $post_ids as $post_id ) {
        wp_delete_post( $post_id, true );
        $count++;
    }
    echo "<h1>Pöytä puhdistettu!</h1> Poistettiin $count kasvia.";
    exit;
}
add_action('init', 'tyhjenna_kaikki_kasvit');

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

/**
 * Lisää aktiivinen luokka valikkoon ACF-ryhmän perusteella
 */
function korosta_kasviryhma_valikossa($classes, $item, $args) {
    // Tarkistetaan, että ollaan yksittäisellä sivulla ja käytössä on oikea valikko (primary)
    if ( is_singular() && $args->theme_location == 'primary' ) {
        
        $ryhma = get_field('ryhma'); // Haetaan ACF-kentän arvo

        if ( $ryhma ) {
            // Jos valikkokohdan teksti täsmää ACF-ryhmän nimeen
            // Huom: strtolower ja trim varmistavat, että vertailu ei kaadu pieniin kirjoituseroihin
            if ( strtolower(trim($item->title)) == strtolower(trim($ryhma)) ) {
                $classes[] = 'current-menu-item';
            }
        }
    }
    return $classes;
}
add_filter('nav_menu_css_class', 'korosta_kasviryhma_valikossa', 10, 3);



/**
 * Shortcode kasvilistausten upottamiseen rivipohjaisena (kuten arkisto)
 * Käyttö: [kasvilista ryhma="Vieraslajit"]
 */
function custom_kasvilista_shortcode( $atts ) {
    $pairs = shortcode_atts( array(
        'ryhma' => '',
    ), $atts );

    $etsittava = $pairs['ryhma'];
    if ( empty( $etsittava ) ) {
        return 'Määritä ryhmän nimi, esim: [kasvilista ryhma="Vieraslajit"]';
    }

    $args = array(
        'post_type'      => 'kasvi',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => 'lisaryhmat', // ACF-kentän nimi
                'value'   => $etsittava,
                'compare' => 'LIKE',
            ),
        ),
    );

    $query = new WP_Query( $args );
    $output = '';

    if ( $query->have_posts() ) {
        // Lisätään kääre, joka käyttää arkistosivun luokkia
        $output .= '<div class="kasvi-lista-rows shortcode-lista">';
        
        while ( $query->have_posts() ) {
            $query->the_post();
            $tieteellinen = get_field('tieteellinen_nimi');
            
            $output .= '<a href="' . get_permalink() . '" class="kasvi-rivi">';
            $output .= '<div class="sarake nimi-suomi">' . get_the_title() . '</div>';
            $output .= '<div class="sarake nimi-latina"><i>' . esc_html($tieteellinen) . '</i></div>';
            $output .= '</a>';
        }
        $output .= '</div>';
        wp_reset_postdata();
    } else {
        $output = '<p>Ryhmästä "' . esc_html($etsittava) . '" ei löytynyt kasveja.</p>';
    }

    return $output;
}
add_shortcode( 'kasvilista', 'custom_kasvilista_shortcode' );

/**
 * Shortcode 100 yleisimmän kasvin listaamiseksi synkronoituna archive-kasvi.php:n kanssa.
 * Käyttö: [yleisimmat_kasvit]
 */
function lista_yleisimmat_kasvit_shortcode() {
    $args = array(
        'post_type'      => 'kasvi',
        'posts_per_page' => 100,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => 'lisaryhmat',
                'value'   => '100 yleisintä',
                'compare' => 'LIKE'
            )
        )
    );

    $query = new WP_Query($args);
    $output = '';

    if ($query->have_posts()) {
        // 1. Header-wrapper (sama kuin archivessa)
        $output .= '<div class="lista-header-wrapper">';
        $output .= '    <div class="header-top-row">';
        $output .= '        <h2>100 yleisintä kasvia</h2>';
        $output .= '        <input type="text" id="yleisimmat-haku" placeholder="Etsi yleisimmistä kasveista..." class="haku-input" style="padding: 8px 15px; border-radius: 20px; border: 1px solid #ddd; min-width: 250px;">';
        $output .= '    </div>';
        
        // Sarakeotsikot (täsmälleen sama rakenne kuin archivessa)
        $output .= '    <div class="lista-otsikot" style="display: flex; padding: 10px 20px; border-bottom: 2px solid #27ae60; font-weight: bold; margin-top: 15px;">';
        $output .= '        <div class="sarake" style="flex: 1;">Suomenkielinen nimi</div>';
        $output .= '        <div class="sarake" style="flex: 1; text-align: right;">Tieteellinen nimi</div>';
        $output .= '    </div>';
        $output .= '</div>'; // .lista-header-wrapper loppu

        // 2. Lista-rivit (sama kuin archivessa)
        $output .= '<div class="kasvi-lista-rows" id="yleisimmat-lista-rows">';
        
        $i = 0;
        while ($query->have_posts()) {
            $query->the_post();
            $tieteellinen = get_field('tieteellinen_nimi');
            
            // Zebra-striping ja tyylit synkronoituina
            $bg_color = ($i % 2 == 0) ? '#f9f9f9' : '#ffffff';
            
            $output .= '<a href="' . get_permalink() . '" class="kasvi-rivi" style="display: flex; padding: 12px 20px; border-bottom: 1px solid #eee; background-color: ' . $bg_color . ';">';
            $output .= '    <div class="sarake nimi-suomi" style="flex: 1; font-weight: 500; color: #27ae60;">' . get_the_title() . '</div>';
            
            $latina = $tieteellinen ? esc_html($tieteellinen) : '';
            $output .= '    <div class="sarake nimi-latina" style="flex: 1; color: #666; font-style: italic; text-align: right;">' . $latina . '</div>';
            $output .= '</a>';
            
            $i++;
        }
        
        $output .= '</div>'; // .kasvi-lista-rows loppu

        // 3. JavaScript hakuun (kohdistettu uuteen ID:hen)
        $output .= "
        <script>
        (function() {
            var searchInput = document.getElementById('yleisimmat-haku');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    var filter = this.value.toLowerCase();
                    var rows = document.querySelectorAll('#yleisimmat-lista-rows .kasvi-rivi');
                    
                    rows.forEach(function(row) {
                        var text = row.textContent.toLowerCase();
                        row.style.display = text.indexOf(filter) > -1 ? 'flex' : 'none';
                    });
                });
            }
        })();
        </script>";
        
        wp_reset_postdata();
    } else {
        $output = '<p>Yleisimpiä kasveja ei löytynyt. Varmista ACF-valinnat.</p>';
    }

    return $output;
}
add_shortcode('yleisimmat_kasvit', 'lista_yleisimmat_kasvit_shortcode');