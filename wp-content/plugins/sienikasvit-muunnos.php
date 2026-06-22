<?php
/**
 * Plugin Name: 🍄 Piensienien Isäntäkasvimuunnin (Aito ACF-Linkki)
 * Description: Työkalusivu piensienien vanhojen ACF-tekstikenttien siirtoon uuteen ACF Linkki + yleisyys -rakenteeseen.
 * Version: 1.5
 * Author: Oma Sivusto
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Luodaan työkaluvalikko
add_action('admin_menu', function() {
    add_management_page(
        'Sienien isäntäkasvien muunnos',
        'Sienikasvien muunnos',
        'manage_options',
        'sienikasvien-muunnostyokalu',
        'renderoi_sienityokalu_sivu'
    );
});

// 2. Ajax-tallennus
add_action('wp_ajax_tallenna_sienikasvit_repeateriin', function() {
    if ( !current_user_can('manage_options') ) wp_send_json_error('Ei oikeuksia.');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $rivit   = isset($_POST['rivit']) ? $_POST['rivit'] : array();

    if ( !$post_id ) wp_send_json_error('Virheellinen ID.');

    $repeater_data = array();

    if ( !empty($rivit) && is_array($rivit) ) {
        foreach ( $rivit as $rivi ) {
            $osoite_url = !empty($rivi['valittu_kasvi']) ? esc_url_raw($rivi['valittu_kasvi']) : '';
            if ( empty($osoite_url) ) continue; 
            
            // Haetaan sivun otsikko URL-osoitteen perusteella ACF-linkkiä varten
            $liitetty_id = url_to_postid($osoite_url);
            $otsikko = $liitetty_id ? get_the_title($liitetty_id) : '';

            // TÄMÄ ON SE RESEPTI: ACF Link-kenttä vaatii tarkan taulukkorakenteen
            $repeater_data[] = array(
                'isantakasvin_osoite'    => array(
                    'title'  => $otsikko, // Näkyy ylläpidossa kauniina tekstinä URL-raakatekstin sijaan
                    'url'    => $osoite_url,
                    'target' => '',
                ),
                'yleisyys_isantakasvilla' => sanitize_text_field($rivi['yleisyys'])
            );
        }
    }

    // Päivitetään ACF Repeater ja tyhjennetään vanha tekstikenttä
    update_field('loydettyja_isantakasveja_2', $repeater_data, $post_id);
    update_field('isantakasvit', '', $post_id);

    wp_send_json_success('Tallennettu ja aito ACF-linkki luotu!');
});

// 3. Työkalusivun käyttöliittymä
function renderoi_sienityokalu_sivu() {
    $yleisyydet = array('Hyvin harvinainen', 'Harvinainen', 'Melko yleinen', 'Yleinen', 'Hyvin yleinen');
    
    // Haetaan kaikki kasvikortit valintalistaa varten
    $kasvit_args = array(
        'post_type'      => array('kasvi', 'page'), 
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC'
    );
    $kaikki_kasvit = get_posts($kasvit_args);

    // Haetaan sivut, joissa vanha kenttä ei ole tyhjä
    $sienet_args = array(
        'post_type'      => array('kasvi', 'post', 'piensienet'), 
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => 'isantakasvit',
                'value'   => '',
                'compare' => '!='
            )
        )
    );
    $sienet = get_posts($sienet_args);
    ?>
    <div class="wrap">
        <h1>🍄 Piensienien isäntäkasvien siirtoalusta</h1>
        <p>Pilkko vanha teksti, valitse oikea kasvikortti ja tallenna ACF Linkki -kenttään.</p>

        <?php if ( empty($sienet) ) : ?>
            <div class="notice notice-success"><p>Kaikki kasvikortit muunnettu! Ei löytynyt kortteja, joissa vanha `isantakasvit`-kenttä sisältäisi tekstiä.</p></div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="width: 15%;">Kasvikortti / Sieni</th>
                        <th style="width: 25%;">Vanha teksti (`isantakasvit`)</th>
                        <th style="width: 10%; text-align: center;">Toiminto</th>
                        <th style="width: 50%;">Uusi Repeater-rakenne</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $sienet as $sieni ) : 
                        $vanha_teksti = get_post_meta($sieni->ID, 'isantakasvit', true);
                        $uusi_repeater = get_field('loydettyja_isantakasveja_2', $sieni->ID);
                    ?>
                        <tr id="sieni-rivi-<?php echo $sieni->ID; ?>">
                            <td>
                                <strong><a href="<?php echo get_edit_post_link($sieni->ID); ?>" target="_blank"><?php echo esc_html($sieni->post_title); ?></a></strong>
                            </td>
                            
                            <td class="vanha-sarake" style="background: #fff8f8; font-family: monospace; font-size: 11px;">
                                <div class="tekstisisalto"><?php echo !empty($vanha_teksti) ? nl2br(esc_html($vanha_teksti)) : '<em style="color:#aaa;">Tyhjä</em>'; ?></div>
                            </td>
                            
                            <td style="text-align: center; vertical-align: middle;">
                                <button class="button esitayta-nappi" data-id="<?php echo $sieni->ID; ?>">Pilkko ➡️</button>
                            </td>
                            
                            <td class="uusi-sarake" style="background: #f8fff8;">
                                <div class="repeater-editor" data-id="<?php echo $sieni->ID; ?>">
                                    <div class="rivit-container">
                                        <?php 
                                        if ( !empty($uusi_repeater) && is_array($uusi_repeater) ) {
                                            foreach ( $uusi_repeater as $index => $row ) {
                                                // Haetaan olemassa olevan ACF Linkki -kentän URL
                                                $osoite_url = '';
                                                if ( is_array($row['isantakasvin_osoite']) ) {
                                                    $osoite_url = isset($row['isantakasvin_osoite']['url']) ? $row['isantakasvin_osoite']['url'] : '';
                                                } else {
                                                    $osoite_url = isset($row['isantakasvin_osoite']) ? $row['isantakasvin_osoite'] : '';
                                                }
                                                $valittuYleisyys = isset($row['yleisyys_isantakasvilla']) ? $row['yleisyys_isantakasvilla'] : '';
                                                ?>
                                                <div class="repeater-rivi" style="display:flex; gap:10px; margin-bottom:5px; align-items: center;">
                                                    <select class="r-osoite" style="flex:1;">
                                                        <option value="">- Valitse kasvikortti -</option>
                                                        <?php foreach ( $kaikki_kasvit as $k ) : 
                                                            $k_url = get_permalink($k->ID);
                                                        ?>
                                                            <option value="<?php echo esc_attr($k_url); ?>" <?php selected($osoite_url, $k_url); ?>><?php echo esc_html($k->post_title); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    
                                                    <select class="r-yleisyys" style="width:150px;">
                                                        <option value="">- Yleisyys -</option>
                                                        <?php foreach ( $yleisyydet as $v ) : ?>
                                                            <option value="<?php echo esc_attr($v); ?>" <?php selected($v, $valittuYleisyys); ?>><?php echo esc_html($v); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button class="button poista-rivi-nappi" style="color:red; font-weight:bold;">X</button>
                                                </div>
                                                <?php
                                            }
                                        } else {
                                            echo '<em class="tyhja-ilmoitus" style="color:#aaa;">Ei rivejä. Klikkaa "Pilkko" tai lisää alta.</em>';
                                        }
                                        ?>
                                    </div>
                                    <div style="margin-top: 10px; display: flex; justify-content: space-between;">
                                        <button class="button button-small lisaa-rivi-nappi">+ Lisää tyhjä rivi</button>
                                        <button class="button button-primary tallenna-rivi-nappi" style="display:none;">Tallenna muutokset 💾</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        
        // Luodaan uusi HTML-rivi
        function luoRiviHtml(index, haettuTeksti, valittuYleisyys) {
            var kasvit = <?php echo json_encode(array_map(function($k) { 
                return array('title' => $k->post_title, 'url' => get_permalink($k->ID)); 
            }, $kaikki_kasvit)); ?>;
            
            var yleisyydet = <?php echo json_encode($yleisyydet); ?>;
            
            var kasviSelect = '<select class="r-osoite" style="flex:1;"><option value="">- Valitse kasvikortti -</option>';
            var loytyiKortti = false;
            
            $.each(kasvit, function(i, k) {
                var selected = '';
                if (haettuTeksti && k.title.toLowerCase().trim() === haettuTeksti.toLowerCase().trim()) {
                    selected = 'selected';
                    loytyiKortti = true;
                }
                kasviSelect += '<option value="'+k.url+'" '+selected+'>'+k.title+'</option>';
            });
            kasviSelect += '</select>';
            
            var vinkkiHtml = '';
            if (!loytyiKortti && haettuTeksti) {
                vinkkiHtml = '<span class="r-vinkki" style="font-size:11px; color:#c94a4a; background:#fbebeb; padding:3px 6px; border-radius:3px; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="Alkuperäinen teksti: '+haettuTeksti+'">🔍 '+haettuTeksti+'</span>';
            }
            
            var yleisyysSelect = '<select class="r-yleisyys" style="width:130px;"><option value="">- Yleisyys -</option>';
            $.each(yleisyydet, function(i, v) {
                var selected = (v === valittuYleisyys) ? 'selected' : '';
                yleisyysSelect += '<option value="'+v+'" '+selected+'>'+v+'</option>';
            });
            yleisyysSelect += '</select>';

            return '<div class="repeater-rivi" style="display:flex; gap:10px; margin-bottom:5px; align-items:center;">' +
                   kasviSelect +
                   vinkkiHtml +
                   yleisyysSelect +
                   '<button class="button poista-rivi-nappi" style="color:red; font-weight:bold;">X</button>' +
                   '</div>';
        }

        // 1. Pilkkominen
        $('.esitayta-nappi').on('click', function() {
            var $nappi = $(this);
            var id = $nappi.data('id');
            var $tr = $('#sieni-rivi-' + id);
            var vanhaTeksti = $tr.find('.vanha-sarake .tekstisisalto').html();
            
            var rivit = vanhaTeksti.split(/<br\s*\/?>/i).map(s => s.trim()).filter(Boolean);
            var $container = $tr.find('.rivit-container');
            
            $container.find('.tyhja-ilmoitus').remove();
            
            $.each(rivit, function(index, arvo) {
                $container.append(luoRiviHtml(index, arvo, ''));
            });

            $tr.find('.tallenna-rivi-nappi').show();
            $nappi.prop('disabled', true);
        });

        // 2. Tyhjän rivin lisääminen
        $('.lisaa-rivi-nappi').on('click', function() {
            var $container = $(this).closest('.repeater-editor').find('.rivit-container');
            $container.find('.tyhja-ilmoitus').remove();
            $container.append(luoRiviHtml(Date.now(), '', ''));
            $(this).siblings('.tallenna-rivi-nappi').show();
        });

        // 3. Rivin poistaminen
        $(document).on('click', '.poista-rivi-nappi', function() {
            var $editor = $(this).closest('.repeater-editor');
            $(this).closest('.repeater-rivi').remove();
            $editor.find('.tallenna-rivi-nappi').show();
        });

        // 4. Tallennus Ajaxilla
        $('.tallenna-rivi-nappi').on('click', function() {
            var $nappi = $(this);
            var $editor = $nappi.closest('.repeater-editor');
            var postID = $editor.data('id');
            var $tr = $('#sieni-rivi-' + postID);
            
            var datat = [];
            $editor.find('.repeater-rivi').each(function() {
                datat.push({
                    valittu_kasvi: $(this).find('.r-osoite').val(),
                    yleisyys: $(this).find('.r-yleisyys').val()
                });
            });

            $nappi.text('Tallennetaan...').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tallenna_sienikasvit_repeateriin',
                    post_id: postID,
                    rivit: datat
                },
                success: function(response) {
                    if (response.success) {
                        $tr.find('.vanha-sarake .tekstisisalto').html('<em style="color:#aaa;">Tyhjä (siirretty)</em>');
                        $tr.find('.esitayta-nappi').replaceWith('<span style="color: green; font-weight: bold;">Valmis ✨</span>');
                        $nappi.text('Muutokset tallennettu!').removeClass('button-primary').css('background', '#46b450').css('color', '#fff');
                        setTimeout(function() {
                            $nappi.hide().text('Tallenna muutokset 💾').addClass('button-primary').attr('style', '').prop('disabled', false);
                        }, 2000);
                    } else {
                        alert('Virhe: ' + response.data);
                        $nappi.text('Tallenna muutokset 💾').prop('disabled', false);
                    }
                }
            });
        });
    });
    </script>
    <?php
}