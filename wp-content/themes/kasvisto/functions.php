<?php
/**
 * Luopioisten Kasvisto - functions.php
 */

// 1. TEEMAN PERUSASETUKSET
function kasvi_theme_setup() {
    add_theme_support('title-tag'); 
    add_theme_support('post-thumbnails'); 
    
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
        'rewrite'            => array('slug' => 'laji'),
    );

    register_post_type('kasvi', $args);
}
add_action('init', 'rekisteroi_kasvit_cpt');

/**
 * Aktiivinen luokka valikkoon - SUOJATTU ACF-tarkistuksella
 */
function korosta_kasviryhma_valikossa($classes, $item, $args) {
    if ( function_exists('get_field') && is_singular() && $args->theme_location == 'primary' ) {
        $ryhma = get_field('ryhma'); 
        if ( $ryhma ) {
            if ( strtolower(trim($item->title)) == strtolower(trim($ryhma)) ) {
                $classes[] = 'current-menu-item';
            }
        }
    }
    return $classes;
}
add_filter('nav_menu_css_class', 'korosta_kasviryhma_valikossa', 10, 3);

/**
 * Kasvilistat - SUOJATTU ACF-tarkistuksella
 */
// function custom_kasvilista_shortcode( $atts ) {
//     if ( !function_exists('get_field') ) return '';

//     $pairs = shortcode_atts( array('ryhma' => ''), $atts );
//     $etsittava = $pairs['ryhma'];
//     if ( empty( $etsittava ) ) return 'Määritä ryhmä.';

//     $args = array(
//         'post_type'      => 'kasvi',
//         'posts_per_page' => -1,
//         'orderby'        => 'title',
//         'order'          => 'ASC',
//         'meta_query'     => array(
//             array('key' => 'lisaryhmat', 'value' => $etsittava, 'compare' => 'LIKE'),
//         ),
//     );

//     $query = new WP_Query( $args );
//     $output = '';
//     if ( $query->have_posts() ) {
//         $output .= '<div class="kasvi-lista-rows shortcode-lista">';
//         while ( $query->have_posts() ) {
//             $query->the_post();
//             $tieteellinen = get_field('tieteellinen_nimi');
//             $output .= '<a href="' . get_permalink() . '" class="kasvi-rivi">';
//             $output .= '<div class="sarake nimi-suomi">' . get_the_title() . '</div>';
//             $output .= '<div class="sarake nimi-latina"><i>' . esc_html($tieteellinen) . '</i></div>';
//             $output .= '</a>';
//         }
//         $output .= '</div>';
//         wp_reset_postdata();
//     }
//     return $output;
// }
// add_shortcode( 'kasvilista', 'custom_kasvilista_shortcode' );

/**
 * Hakumuokkaukset
 */
function muokkaa_hakua($query) {
    if ($query->is_search && !is_admin()) {
        $query->set('post_type', array('page', 'kasvi'));
    }
    return $query;
}
add_filter('pre_get_posts', 'muokkaa_hakua');

function kasvisto_muokkaa_haun_maaraa( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
        $query->set( 'posts_per_page', 30 );
    }
}
add_action( 'pre_get_posts', 'kasvisto_muokkaa_haun_maaraa' );

/**
 * Laskurit
 */
function hae_kasvi_maara_optimoitu($ryhma_slug, $lisa_slug = '') {
    $debug_mode = true; // Muuta false, kun olet valmis ja luvut täsmäävät.
    
    if ( !function_exists('get_field') ) return 0;

    $ryhma_slug = trim($ryhma_slug);
    $lisa_slug  = trim($lisa_slug);

    $transient_key = 'kasvi_count_' . md5($ryhma_slug . $lisa_slug);
    $count = get_transient($transient_key);

    // Jos ollaan debug-tilassa tai välimuistia ei ole, lasketaan uudestaan
    if ($debug_mode || $count === false) {
        
        $args = array(
            'post_type'      => 'kasvi',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array('relation' => 'AND')
        );

        // Pääryhmä (KORJATTU: LIKE, jotta osuu taulukkoon ja pikkukirjaimiin)
        if (!empty($ryhma_slug)) {
            $args['meta_query'][] = array(
                'key'     => 'ryhma',
                'value'   => $ryhma_slug,
                'compare' => 'LIKE'
            );
        }

        // Lisäryhmä (Checkbox) - Tämä pidetään täsmälleen sellaisena kuin se oli GitHubissa
        if (!empty($lisa_slug)) {
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array('key' => 'putkilokasvien_lisaryhmat', 'value' => '"' . $lisa_slug . '"', 'compare' => 'LIKE'),
                array('key' => 'sammalten_lisaryhmat',      'value' => '"' . $lisa_slug . '"', 'compare' => 'LIKE'),
                array('key' => 'jakalien_lisaryhmat',       'value' => '"' . $lisa_slug . '"', 'compare' => 'LIKE'),
                array('key' => 'piensienten_lisaryhmat',    'value' => '"' . $lisa_slug . '"', 'compare' => 'LIKE'),
            );
        }

        $query = new WP_Query($args);
        $count = (int)$query->post_count;

        // Tallennetaan välimuistiin vain, jos ei olla debug-tilassa
        if (!$debug_mode) {
            set_transient($transient_key, $count, 3600);
        }
    }

    return $count;
}

// Lyhytkoodi listauksia varten
add_shortcode('laji_laskuri', function($atts) {
    $a = shortcode_atts(array('ryhma' => '', 'lisa' => ''), $atts);
    return hae_kasvi_maara_optimoitu($a['ryhma'], $a['lisa']);
});

// Automaattinen välimuistin tyhjennys kun kasvi tallennetaan
add_action('save_post', function($post_id) {
    if (get_post_type($post_id) === 'kasvi') { // KORJATTU: post_type vastaamaan hakuasi 'kasvi'
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_kasvi_count_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_kasvi_count_%'");
    }
});

/**
 * Isäntäkasvitaulukko - SUOJATTU ACF-tarkistuksella
 */
function tulosta_isantakasvi_taulukko() {
    if ( !function_exists('get_field') ) return '';
    $teksti = get_field('isantakasvit');
    if (empty($teksti)) return '';

    $html = '<div class="isantakasvit-wrap"><table class="isanta-taulukko"><thead><tr><th>Nimi</th><th>Tieteellinen</th><th>Luopioinen</th><th>Yleisyys</th></tr></thead><tbody>';
    $rivit = explode("\n", str_replace(['<p>', '</p>'], "\n", $teksti));

    foreach ($rivit as $rivi) {
        $rivi = trim(strip_tags($rivi));
        if (empty($rivi)) continue;
        $osat = explode('|', $rivi);
        $nimi = isset($osat[0]) ? trim($osat[0]) : '';
        if (empty($nimi)) continue;

        $kasvi_query = new WP_Query(['post_type' => 'kasvi', 'title' => $nimi, 'posts_per_page' => 1]);
        $tieteellinen = '—';
        $linkki = $nimi;

        if ($kasvi_query->have_posts()) {
            while ($kasvi_query->have_posts()) {
                $kasvi_query->the_post();
                $linkki = '<a href="' . get_permalink() . '">' . $nimi . '</a>';
                $tiet_kentta = get_field('tieteellinen_nimi');
                if ($tiet_kentta) $tieteellinen = '<i>' . $tiet_kentta . '</i>';
            }
            wp_reset_postdata();
        }
        $html .= "<tr><td>$linkki</td><td>$tieteellinen</td><td>" . (isset($osat[1]) ? $osat[1] : '—') . "</td><td>" . (isset($osat[2]) ? $osat[2] : '—') . "</td></tr>";
    }
    $html .= '</tbody></table></div>';
    return $html;
}
add_shortcode('isäntäkasvit', 'tulosta_isantakasvi_taulukko');

/**
 * MASSA-AJOTYÖKALU V3: Karttapisteiden tuonti JSON-tiedostosta ACF-kenttiin
 * Korjattu lukemaan suoraan piste-taulukko ilman 'pisteet'-avainta.
 */
add_action('init', function() {
    if (!isset($_GET['aja_karttatuonti']) || $_GET['aja_karttatuonti'] !== '1') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die('Sinulla ei ole oikeutta ajaa tätä skriptiä. Kirjaudu ensin sisään ylläpitäjänä.');
    }

    $json_polku = get_stylesheet_directory() . '/karttapisteet.json';

    if (!file_exists($json_polku)) {
        wp_die('Virhe: Tiedostoa "karttapisteet.json" ei löytynyt teeman kansiosta polusta: ' . $json_polku);
    }

    $json_data = file_get_contents($json_polku);
    $data = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_die('Virhe JSON-tiedoston rakenteessa: ' . json_last_error_msg());
    }

    echo '<!DOCTYPE html><html lang="fi"><head><meta charset="UTF-8"><title>Karttatuonnin raportti (Korjattu data)</title>';
    echo '<style>
        body { font-family: -apple-system, sans-serif; line-height: 1.5; padding: 30px; background: #f0f2f5; color: #1d2327; }
        .raportti-box { max-width: 900px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        h1 { border-bottom: 2px solid #f0f0f1; padding-bottom: 10px; margin-top: 0; }
        .rivi { padding: 8px 12px; margin-bottom: 4px; border-radius: 4px; font-size: 0.9rem; display: flex; justify-content: space-between; }
        .onnistui { background: #edfae7; color: #1e4620; border-left: 4px solid #46b450; }
        .virhe { background: #fbeaea; color: #631010; border-left: 4px solid #dc3232; }
        .stat-box { display: flex; gap: 20px; margin-bottom: 20px; font-weight: bold; }
        .stat { padding: 10px 15px; border-radius: 4px; background: #f6f7f7; }
    </style></head><body>';
    
    echo '<div class="raportti-box">';
    echo '<h1>Karttapisteiden massatuonti (Korjattu datarakenne)</h1>';

    $onnistuneet_laskuri = 0;
    $virhe_laskuri = 0;
    $tulostus_rivit = '';

    foreach ($data as $kuva_nimi => $pisteet_taulukko) {
        // Puhdistetaan lajin nimi tiedostonimestä
        $laji_nimi = pathinfo($kuva_nimi, PATHINFO_FILENAME);
        $laji_nimi = str_replace('û', '×', $laji_nimi);
        $laji_nimi = trim($laji_nimi);

        // Varmistetaan, että kyseessä on taulukko (jos JSONissa on jotain muuta dataa)
        if (!is_array($pisteet_taulukko)) {
            continue;
        }
        
        $args = [
            'post_type'      => 'kasvi',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'     => 'tieteellinen_nimi',
                    'value'   => $laji_nimi,
                    'compare' => '='
                ]
            ]
        ];
        
        $kasvi_query = new WP_Query($args);

        if ($kasvi_query->have_posts()) {
            $kasvi_query->the_post();
            $post_id = get_the_ID();
            
            // Muutetaan korjattu piste-taulukko puhtaaksi JSON-tekstiksi
            $pisteet_json_string = json_encode($pisteet_taulukko, JSON_UNESCAPED_UNICODE);
            
            // Päivitetään ACF-kenttä
            update_field('karttapisteet_json', $pisteet_json_string, $post_id);
            
            $artikkelin_otsikko = get_the_title();
            $tulostus_rivit .= '<div class="rivi onnistui"><span>✓ <strong>' . esc_html($laji_nimi) . '</strong> &rarr; "' . esc_html($artikkelin_otsikko) . '"</span> <span>Tallennettu ' . count($pisteet_taulukko) . ' pistettä</span></div>';
            $onnistuneet_laskuri++;
        } else {
            $tulostus_rivit .= '<div class="rivi virhe"><span>✕ <strong>' . esc_html($laji_nimi) . '</strong></span> <span>Ei löytynyt kasvia ACF "tieteellinen_nimi" perusteella.</span></div>';
            $virhe_laskuri++;
        }
        
        wp_reset_postdata();
    }

    echo '<div class="stat-box">';
    echo '<div class="stat" style="color:#46b450;">Onnistui: ' . $onnistuneet_laskuri . ' kpl</div>';
    echo '<div class="stat" style="color:#dc3232;">Ei löydetty: ' . $virhe_laskuri . ' kpl</div>';
    echo '</div>';

    echo '<div class="rivit-wrapper">' . $tulostus_rivit . '</div>';
    echo '</div></body></html>';
    
    exit;
});



