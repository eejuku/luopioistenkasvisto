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
        'rewrite'            => array('slug' => 'laji'),
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
