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

// 4. TUONTITYÖKALUT JA APUFUNKTIOT
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
function custom_kasvilista_shortcode( $atts ) {
    if ( !function_exists('get_field') ) return '';

    $pairs = shortcode_atts( array('ryhma' => ''), $atts );
    $etsittava = $pairs['ryhma'];
    if ( empty( $etsittava ) ) return 'Määritä ryhmä.';

    $args = array(
        'post_type'      => 'kasvi',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => array(
            array('key' => 'lisaryhmat', 'value' => $etsittava, 'compare' => 'LIKE'),
        ),
    );

    $query = new WP_Query( $args );
    $output = '';
    if ( $query->have_posts() ) {
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
    }
    return $output;
}
add_shortcode( 'kasvilista', 'custom_kasvilista_shortcode' );

/**
 * Yleisimmät kasvit - SUOJATTU ACF-tarkistuksella
 */
function lista_yleisimmat_kasvit_shortcode() {
    if ( !function_exists('get_field') ) return 'ACF puuttuu.';

    $args = array(
        'post_type'      => 'kasvi',
        'posts_per_page' => 100,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => array(
            array('key' => 'lisaryhmat', 'value' => '100 yleisintä', 'compare' => 'LIKE')
        )
    );

    $query = new WP_Query($args);
    $output = '';
    if ($query->have_posts()) {
        $output .= '<div class="lista-header-wrapper"><div class="header-top-row"><h2>100 yleisintä kasvia</h2><input type="text" id="yleisimmat-haku" placeholder="Etsi..." class="haku-input"></div></div>';
        $output .= '<div class="kasvi-lista-rows" id="yleisimmat-lista-rows">';
        $i = 0;
        while ($query->have_posts()) {
            $query->the_post();
            $tieteellinen = get_field('tieteellinen_nimi');
            $bg_color = ($i % 2 == 0) ? '#f9f9f9' : '#ffffff';
            $output .= '<a href="' . get_permalink() . '" class="kasvi-rivi" style="background-color: ' . $bg_color . ';">';
            $output .= '<div class="sarake nimi-suomi">' . get_the_title() . '</div>';
            $output .= '<div class="sarake nimi-latina"><i>' . esc_html($tieteellinen) . '</i></div></a>';
            $i++;
        }
        $output .= '</div>';
        wp_reset_postdata();
    }
    return $output;
}
add_shortcode('yleisimmat_kasvit', 'lista_yleisimmat_kasvit_shortcode');

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
 * Laskurit - SUOJATTU ACF-tarkistuksella
 */
function hae_kasvi_maara_optimoitu($ryhma_slug, $lisa_slug = '') {
    if ( !function_exists('get_field') ) return 0;
    
    $transient_key = 'kasvi_count_' . md5($ryhma_slug . $lisa_slug);
    $count = get_transient($transient_key);

    if ($count === false) {
        $args = array(
            'post_type'      => 'kasvi',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array('relation' => 'AND')
        );
        if (!empty($ryhma_slug)) {
            $args['meta_query'][] = array('key' => 'ryhma', 'value' => $ryhma_slug, 'compare' => '=');
        }
        $query = new WP_Query($args);
        $count = (int)$query->post_count;
        set_transient($transient_key, $count, 3600);
    }
    return $count;
}
add_shortcode('laji_laskuri', function($atts) {
    $a = shortcode_atts(array('ryhma' => '', 'lisa' => ''), $atts);
    return hae_kasvi_maara_optimoitu($a['ryhma'], $a['lisa']);
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
 * Maakuntapäivitys - SUOJATTU ACF-tarkistuksella
 */
function aja_maakuntapaivitys_raportilla() {
    if (isset($_GET['aja_paivitys']) && current_user_can('manage_options') && function_exists('update_field')) {
        
        $json_polku = get_template_directory() . '/data/sammaldata_pro.json';
        
        if (!file_exists($json_polku)) {
            die("Virhe: Tiedostoa ei löydy polusta: " . $json_polku);
        }

        $json_sisalto = file_get_contents($json_polku);
        $laji_data = json_decode($json_sisalto, true);

        echo "<h2>Maakuntatietojen päivitysraportti (ACF Tieteellinen nimi & Kasvin nimi -haku)</h2><hr>";

        $paivitetty = 0;
        $ei_loytynyt = [];

        foreach ($laji_data as $tieteellinen_raw => $sisalto) {
            // Nollataan post_id jokaisen kierroksen alussa, ettei vanha ID jää muistiin
            $post_id = false;

            // 1. SIISTITÄÄN TIETEELLINEN NIMI (Poistetaan sulut ja auktorit)
            $tieteellinen_siisti = trim(explode('(', $tieteellinen_raw)[0]);
            
            $maakunta_objekti = $sisalto['maakunnat'];
            // Haetaan suomenkielinen nimi oikealla avaimella
            $kasvin_nimi = isset($sisalto['nimi_fi']) ? trim($sisalto['nimi_fi']) : ''; 

            // VAIHE 1: HAETAAN POSTAUS ACF-KENTÄN "tieteellinen_nimi" PERUSTEELLA
            $args = [
                'post_type'      => 'kasvi',
                'posts_per_page' => 1,
                'post_status'    => 'any',
                'meta_query'     => [
                    [
                        'key'     => 'tieteellinen_nimi',
                        'value'   => $tieteellinen_siisti,
                        'compare' => '=',
                    ]
                ],
            ];
            
            $query = new WP_Query($args);

            if ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                echo "<span style='color: green;'>✅ Päivitetty (ACF):</span> <strong>$tieteellinen_siisti</strong> (ID: $post_id)<br>";
            } else {
                // VAIHE 2: Joskus tieteellinen nimi saattaa olla suoraan otsikkona
                $args_title = [
                    'post_type'      => 'kasvi',
                    'title'          => $tieteellinen_siisti,
                    'posts_per_page' => 1,
                    'post_status'    => 'any',
                ];
                $query_title = new WP_Query($args_title);
                
                if ($query_title->have_posts()) {
                    $query_title->the_post();
                    $post_id = get_the_ID();
                    echo "<span style='color: blue;'>ℹ️ Löytyi tieteellisen otsikon perusteella:</span> <strong>$tieteellinen_siisti</strong> (ID: $post_id)<br>";
                } 
                // VAIHE 3: VARASUUNNITELMA – Haetaan suomenkielisen kasvin nimen perusteella (KORJATTU MUUTTUJA)
                elseif (!empty($kasvin_nimi)) {
                    $args_kasvi_title = [
                        'post_type'      => 'kasvi',
                        'title'          => $kasvin_nimi, // Nyt muuttujan nimi on täsmälleen oikein!
                        'posts_per_page' => 1,
                        'post_status'    => 'any',
                    ];
                    $query_kasvi_title = new WP_Query($args_kasvi_title);

                    if ($query_kasvi_title->have_posts()) {
                        $query_kasvi_title->the_post();
                        $post_id = get_the_ID();
                        echo "<span style='color: purple;'>🌿 Löytyi kasvin nimen perusteella:</span> <strong>$kasvin_nimi</strong> (Tieteellinen: $tieteellinen_siisti) (ID: $post_id)<br>";
                    }
                }
            }

            // Jos postaus löytyi ja ID on voimassa, ajetaan päivitys
            if ($post_id) {
                update_field('eliomaakunnat', wp_json_encode($maakunta_objekti), $post_id);
                $paivitetty++;
            } else {
                echo "<span style='color: red;'>❌ EI LÖYTYNYT:</span> Tieteellinen: <code>$tieteellinen_siisti</code>" . ($kasvin_nimi ? " / Nimi: <code>$kasvin_nimi</code>" : "") . "<br>";
                $ei_loytynyt[] = $tieteellinen_siisti;
            }

            // Varmistetaan WordPressin globaalien muuttujien nollaus kierroksen päätteeksi
            wp_reset_postdata();
        }

        echo "<hr><h3>Yhteenveto:</h3>";
        echo "Päivitetty yhteensä: $paivitetty kpl. Epäonnistuneet: " . count($ei_loytynyt) . " kpl.";
        exit;
    }
}
add_action('init', 'aja_maakuntapaivitys_raportilla');



/**
 * AJAX-pohjainen massasiivoustyökalu sammalten kuva-URL-kentille (Paranneltu versio)
 */

// 1. Valikkolinkki
add_action('admin_menu', 'sammalkuvien_siivous_valikko');
function sammalkuvien_siivous_valikko() {
    add_menu_page(
        'Sammalkuvien siivous',
        'Sammalkuvien siivous',
        'manage_options',
        'sammal-kuvasiivous',
        'tulosta_sammal_kuvasiivous_sivu',
        'dashicons-images-alt2',
        25
    );
}

// 2. AJAX-vastaanottaja tallennukselle
add_action('wp_ajax_tallenna_sammal_rivi', 'ajax_tallenna_sammal_rivi');
function ajax_tallenna_sammal_rivi() {
    check_ajax_referer('sammal_siivous_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Ei oikeuksia');
    }

    $post_id = intval($_POST['post_id']);
    $kentat = isset($_POST['kentat']) ? $_POST['kentat'] : array();

    for ($i = 1; $i <= 8; $i++) {
        $meta_avain = "kuva_{$i}_url";
        if (isset($kentat[$meta_avain])) {
            $uusi_arvo = trim($kentat[$meta_avain]);
            
            if (empty($uusi_arvo)) {
                delete_post_meta($post_id, $meta_avain);
                delete_post_meta($post_id, "_" . $meta_avain);
            } else {
                update_field($meta_avain, $uusi_arvo, $post_id);
            }
        }
    }

    wp_send_json_success('Päivitetty!');
}

// 3. Käyttöliittymä
function tulosta_sammal_kuvasiivous_sivu() {
    if (!current_user_can('manage_options')) return;

    $args = array(
        'post_type'      => 'kasvi',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => 'ryhma',
                'value'   => 'Sammalet',
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args);
    ?>
    
    <div class="wrap">
        <h1>🌿 Sammalkuvien massasiivous (AJAX)</h1>
        <p>Tyhjennä kenttiä rasteista ja paina rivikohtaista <strong>Tallenna rivi</strong> -painiketta. Onnistuneen tallennuksen jälkeen sivu häivyttää kasvin pois listalta automaattisesti. Kasvin nimeä klikkaamalla pääset tarkistamaan kasvikortin uudella välilehdellä.</p>
        
        <style>
            .siivous-taulukko { width: 100%; max-width: 1600px; margin-top: 20px; border-collapse: collapse; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .siivous-taulukko th, .siivous-taulukko td { padding: 12px; border: 1px solid #ccd0d4; text-align: left; vertical-align: middle; }
            .siivous-taulukko th { background: #f8f9fa; position: sticky; top: 32px; z-index: 10; }
            .siivous-taulukko tr { transition: all 0.5s ease; }
            .url-input-container { display: flex; flex-direction: column; gap: 6px; }
            .url-input-row { display: flex; align-items: center; gap: 10px; width: 100%; }
            .url-input-row label { font-size: 10px; color: #646970; width: 60px; flex-shrink: 0; font-weight: 600; }
            .url-input-wrap { display: flex; align-items: center; gap: 4px; flex-grow: 1; }
            .url-input-wrap input { flex-grow: 1; font-size: 12px; padding: 4px 8px; height: 28px; width: 100%; }
            .tyhjenna-btn { background: #e65c5c; color: #fff; border: none; border-radius: 3px; cursor: pointer; padding: 0 8px; height: 28px; font-weight: bold; font-size: 14px; line-height: 28px; }
            .tyhjenna-btn:hover { background: #cc4444; }
            .rivi-tallenna-btn { background: #2271b1; color: #fff; border: none; padding: 8px 16px; border-radius: 3px; cursor: pointer; font-weight: bold; width: 100%; max-width: 140px; }
            .rivi-tallenna-btn:hover { background: #135e96; }
            .kasvi-linkki { font-weight: bold; font-size: 14px; color: #2271b1; text-decoration: none; }
            .kasvi-linkki:hover { text-decoration: underline; color: #135e96; }
            .kasvi-tieteellinen { font-style: italic; color: #646970; font-size: 12px; display: block; margin-top: 2px; }
        </style>

        <table class="siivous-taulukko">
            <thead>
                <tr>
                    <th style="width: 17%;">Kasvin nimi (Linkki)</th>
                    <th style="width: 73%;">Kuva URL-kentät (1-8)</th>
                    <th style="width: 10%; text-align: center;">Toiminto</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $naytetty_lkm = 0;
                if ($query->have_posts()) : 
                    while ($query->have_posts()) : $query->the_post(); 
                        $post_id = get_the_ID();
                        $tieteellinen = get_field('tieteellinen_nimi', $post_id);
                        $kasvi_url = get_permalink($post_id); // Haetaan kasvikortin osoite
                        
                        $onko_kuvia = false;
                        $kuva_kentat = array();
                        for ($i = 1; $i <= 8; $i++) {
                            $url = get_post_meta($post_id, "kuva_{$i}_url", true);
                            $kuva_kentat[$i] = $url;
                            if (!empty($url)) $onko_kuvia = true;
                        }

                        if (!$onko_kuvia) continue;
                        $naytetty_lkm++;
                        ?>
                        <tr id="rivi-<?php echo $post_id; ?>">
                            <td>
                                <a href="<?php echo esc_url($kasvi_url); ?>" target="_blank" class="kasvi-linkki" title="Avaa kasvikortti uudessa välilehdessä">
                                    <?php the_title(); ?> 🔗
                                </a>
                                <?php if ($tieteellinen) : ?>
                                    <span class="kasvi-tieteellinen"><?php echo esc_html($tieteellinen); ?></span>
                                <?php endif; ?>
                                <small style="color: #999; display: block; margin-top: 4px;">ID: <?php echo $post_id; ?></small>
                            </td>
                            <td>
                                <div class="url-input-container">
                                    <?php for ($i = 1; $i <= 8; $i++) : ?>
                                        <div class="url-input-row">
                                            <label for="input_<?php echo $post_id . '_' . $i; ?>">Kuva <?php echo $i; ?></label>
                                            <div class="url-input-wrap">
                                                <input type="text" 
                                                       class="kasvi-input-<?php echo $post_id; ?>"
                                                       data-avain="kuva_<?php echo $i; ?>_url"
                                                       id="input_<?php echo $post_id . '_' . $i; ?>"
                                                       value="<?php echo esc_attr($kuva_kentat[$i]); ?>">
                                                <button type="button" 
                                                        class="tyhjenna-btn" 
                                                        onclick="document.getElementById('input_<?php echo $post_id . '_' . $i; ?>').value='';" 
                                                        title="Tyhjennä">×</button>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" 
                                        class="rivi-tallenna-btn" 
                                        onclick="tallennaSammalRivi(<?php echo $post_id; ?>)">
                                    Tallenna rivi
                                </button>
                            </td>
                        </tr>
                        <?php 
                    endwhile; 
                    wp_reset_postdata(); 
                endif; 

                if ($naytetty_lkm === 0) : ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 30px; font-size: 16px; color: green;">
                            🎉 Kaikki sammalet on jo siivottu vanhoista kuva-URL-osoitteista!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    function tallennaSammalRivi(postId) {
        const painike = jQuery(`#rivi-${postId} .rivi-tallenna-btn`);
        painike.text('Tallennetaan...').prop('disabled', true);

        const kentat = {};
        let onkoKuviaJaljella = false;

        jQuery(`.kasvi-input-${postId}`).each(function() {
            const avain = jQuery(this).data('avain');
            const arvo = jQuery(this).val().trim();
            kentat[avain] = arvo;
            if (arvo !== '') {
                onkoKuviaJaljella = true;
            }
        });

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tallenna_sammal_rivi',
                post_id: postId,
                kentat: kentat,
                nonce: '<?php echo wp_create_nonce("sammal_siivous_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    painike.text('Tallennettu!').css('background', '#46b450');
                    
                    if (!onkoKuviaJaljella) {
                        setTimeout(function() {
                            jQuery(`#rivi-${postId}`).css({'background': '#bfffbf', 'opacity': '0'});
                            setTimeout(function() {
                                jQuery(`#rivi-${postId}`).remove();
                            }, 500);
                        }, 600);
                    } else {
                        setTimeout(function() {
                            painike.text('Tallenna rivi').prop('disabled', false).css('background', '#2271b1');
                        }, 2000);
                    }
                } else {
                    alert('Virhe tallennuksessa: ' + response.data);
                    painike.text('Yritä uudelleen').prop('disabled', false);
                }
            },
            error: function() {
                alert('Yhteysvirhe palvelimeen.');
                painike.text('Yritä uudelleen').prop('disabled', false);
            }
        });
    }
    </script>
    <?php
}