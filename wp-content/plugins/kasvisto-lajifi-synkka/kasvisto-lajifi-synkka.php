<?php
/**
 * Plugin Name: Laji.fi Kasvisto Synkka
 * Description: Eliösynkronointi virallisen Warehouse-rajapinnan kautta (Tarkka lajihaku toimivalla tokenilla).
 * Version: 4.0
 * Author: Kotikoodari
 */

if (!defined('ABSPEMENT')) {
    define('ABSPATH', dirname(__FILE__));
}

if (!defined('ABSPATH')) {
    exit; 
}

// MÄÄRITÄ LAJI.FI API-TOKEN TÄHÄN (Se toimiva bd97... alkava tokenisi)
define('LAJIFI_API_TOKEN', 'bd97d00f49b6e7ac97eb1f48c48c46876310a952ab83b86bfff212f7f173b9d4');

/**
 * LISÄTÄÄN TYÖKALUSIVU WORDPRESSIN VALIKKOON
 */
function lajifi_lisaa_hallintasivu() {
    add_management_page(
        'Laji.fi Synkronointi',
        'Laji.fi Synkka',
        'manage_options',
        'lajifi-synkronointi',
        'lajifi_renderöi_hallintasivu'
    );
}
add_action('admin_menu', 'lajifi_lisaa_hallintasivu');

/**
 * REKISTERÖIDÄÄN CSS HALLINTASIVULLE
 */
function lajifi_lataa_resurssit($hook) {
    if ($hook !== 'tools_page_lajifi-synkronointi') {
        return;
    }
    wp_enqueue_script('jquery');
    wp_register_style('lajifi-admin-css', false);
    wp_enqueue_style('lajifi-admin-css');
    wp_add_inline_style('lajifi-admin-css', '
        .lajifi-wrap { max-width: 1000px; margin-top: 20px; font-family: sans-serif; }
        .lajifi-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        .lajifi-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .lajifi-table th, .lajifi-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .lajifi-table th { background: #f8f9fa; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-success { background: #e7f4e4; color: #2e7d32; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .lajifi-loading { display: none; font-weight: bold; color: #007cba; margin-top: 10px; }
        .lajifi-ilmoitus { padding: 10px; margin-bottom: 15px; border-radius: 4px; display: none; }
        .lajifi-ilmoitus-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .lajifi-ilmoitus-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    ');
}
add_action('admin_enqueue_scripts', 'lajifi_lataa_resurssit');

/**
 * HALLINTASIVUN NÄKYMÄ
 */
function lajifi_renderöi_hallintasivu() {
    $ajax_url = admin_url('admin-ajax.php');
    ?>
    <div class="wrap lajifi-wrap">
        <h1>Laji.fi Kasvisto Synkronointi (Warehouse API)</h1>
        <p>Etsitään kohteet havaintotietokannan kautta tarkkojen ID-tunnusten löytämiseksi.</p>
        
        <div class="lajifi-card">
            <button id="lajifi-etsi-btn" class="button button-primary button-large">Vaihe 1: Hae ja vertaa Laji.fi -tietokantaan</button>
            <button id="lajifi-tallenna-btn" class="button button-secondary button-large" disabled>Vaihe 2: Tallenna valitut osumat WordPressiin</button>
            <div id="lajifi-lataus" class="lajifi-loading">Haetaan tietoja Laji.fi APIsta yksitellen...</div>
        </div>

        <div id="lajifi-ilmoitus" class="lajifi-ilmoitus"></div>

        <div class="lajifi-card">
            <h2>Löytyneet vastaavuudet</h2>
            <table class="lajifi-table">
                <thead>
                    <tr>
                        <th width="40">Valitse</th>
                        <th>Eliö WordPressissä (Tieteellinen)</th>
                        <th>Tulos</th>
                        <th>Laji.fi virallinen nimi</th>
                        <th>Laji.fi Suomenkielinen nimi</th>
                        <th>Laji.fi ID</th>
                    </tr>
                </thead>
                <tbody id="lajifi-tulokset-body">
                    <tr>
                        <td colspan="6" style="color: #666;">Klikkaa "Vaihe 1" -painiketta aloittaaksesi haun.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var wpAjaxUrl = "<?php echo esc_url($ajax_url); ?>";
            
            $("#lajifi-etsi-btn").on("click", function(e) {
                e.preventDefault();
                var $btn = $(this);
                $btn.prop("disabled", true);
                $("#lajifi-lataus").show();
                $("#lajifi-ilmoitus").hide();
                $("#lajifi-tulokset-body").empty();

                $.ajax({
                    url: wpAjaxUrl,
                    type: "POST",
                    data: { action: "lajifi_etsi_vastaavuudet" },
                    success: function(response) {
                        $btn.prop("disabled", false);
                        $("#lajifi-lataus").hide();

                        if (!response.success) {
                            naytaIlmoitus(response.data.message || "Tuntematon virhe.", "error");
                            return;
                        }

                        var html = "";
                        var osumatLaskuri = 0;

                        $.each(response.data.osumat, function(pid, kasvi) {
                            var statusBadge = "";
                            var valintaruutu = "";

                            if (kasvi.status === "match") {
                                osumatLaskuri++;
                                statusBadge = "<span class=\"badge badge-success\">Tarkka osuma</span>";
                                valintaruutu = "<input type=\"checkbox\" class=\"kasvi-valinta\" value=\"" + pid + "\" checked>";
                            } else {
                                statusBadge = "<span class=\"badge badge-danger\">Ei osumaa</span>";
                                if(kasvi.debug) {
                                    statusBadge += "<br><small style='color:#a00; font-size:10px; font-weight:bold;'>" + kasvi.debug + "</small>";
                                }
                                valintaruutu = "<input type=\"checkbox\" disabled>";
                            }

                            html += "<tr>";
                            html += "<td>" + valintaruutu + "</td>";
                            html += "<td><strong>" + kasvi.wp_title + "</strong><br><small><i>" + kasvi.wp_tieteellinen + "</i></small></td>";
                            html += "<td>" + statusBadge + "</td>";
                            html += "<td>" + kasvi.laji_tieteellinen + "</td>";
                            html += "<td>" + kasvi.laji_suomi + "</td>";
                            html += "<td><small>" + kasvi.laji_id + "</small></td>";
                            html += "</tr>";
                        });

                        $("#lajifi-tallenna-btn").prop("disabled", osumatLaskuri === 0);
                        $("#lajifi-tulokset-body").html(html || "<tr><td colspan='6'>Ei synkronoitavia kohteita.</td></tr>");
                    },
                    error: function(xhr) {
                        $btn.prop("disabled", false);
                        $("#lajifi-lataus").hide();
                        naytaIlmoitus("Palvelinvirhe (Status: " + xhr.status + ").", "error");
                    }
                });
            });

            $("#lajifi-tallenna-btn").on("click", function(e) {
                e.preventDefault();
                var valitutIDt = [];
                $(".kasvi-valinta:checked").each(function() { valitutIDt.push($(this).val()); });

                if (valitutIDt.length === 0) return;

                var $btn = $(this);
                $btn.prop("disabled", true);
                $("#lajifi-lataus").text("Tallennetaan tietoja sivustolle...").show();

                $.ajax({
                    url: wpAjaxUrl,
                    type: "POST",
                    data: { action: "lajifi_tallenna_vastaavuudet", post_ids: valitutIDt },
                    success: function(response) {
                        $btn.prop("disabled", false);
                        $("#lajifi-lataus").hide().text("Haetaan tietoja Laji.fi APIsta yksitellen...");
                        if (response.success) {
                            naytaIlmoitus("Synkronointi onnistui! Päivitettiin " + response.data.paivitetty + " kohteen tiedot.", "success");
                            $("#lajifi-tulokset-body").empty();
                            $btn.prop("disabled", true);
                        } else {
                            naytaIlmoitus(response.data.message || "Tallennus epäonnistui.", "error");
                        }
                    },
                    error: function() {
                        $btn.prop("disabled", false);
                        $("#lajifi-lataus").hide();
                        naytaIlmoitus("Yhteysvirhe tallennuksen aikana.", "error");
                    }
                });
            });

            function naytaIlmoitus(viesti, tyyppi) {
                $("#lajifi-ilmoitus").removeClass("lajifi-ilmoitus-error lajifi-ilmoitus-success").addClass("lajifi-ilmoitus-" + tyyppi).text(viesti).fadeIn();
            }
        });
    </script>
    <?php
}

/**
 * AJAX: HAKEE ELIÖT JA KYSYY LAJI.FI WAREHOUSE-APIsta
 */
function lajifi_ajax_etsi_vastaavuudet() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Ei oikeuksia.']);
    }

    $token = trim(LAJIFI_API_TOKEN);
    if (empty($token) || $token === 'SINUN_API_TOKENISI_TÄHÄN') {
        wp_send_json_error(['message' => 'API-token puuttuu koodista.']);
    }

    $args = [
        'post_type'      => 'kasvi', 
        'posts_per_page' => 15, 
        'meta_query'     => [
            'relation' => 'OR',
            ['key' => 'laji_fi_id', 'compare' => 'NOT EXISTS'],
            ['key' => 'laji_fi_id', 'value' => '', 'compare' => '=']
        ]
    ];

    $query = new WP_Query($args);
    if (!$query->have_posts()) {
        wp_send_json_success(['osumat' => []]);
    }

    $kasvikortit = [];

    while ($query->have_posts()) {
        $query->the_post();
        $pid = get_the_ID();
        
        $tieteellinen_meta = get_post_meta($pid, 'tieteellinen_nimi', true);
        $tieteellinen = is_string($tieteellinen_meta) ? trim($tieteellinen_meta) : '';
        
        if (empty($tieteellinen)) continue;

        $kasvikortit[$pid] = [
            'wp_title'         => get_the_title(),
            'wp_tieteellinen'  => $tieteellinen,
            'status'           => 'no_match',
            'laji_id'          => '',
            'laji_tieteellinen'=> '',
            'laji_suomi'       => '',
            'debug'            => ''
        ];

        // KÄYTETÄÄN WAREHOUSE-LISTAUSTA JOSSA TOKENISI TOIMII
        // Etsitään havaintoja tieteellisellä nimellä (target) ja pyydetään vastaukseen lajin viralliset nimet ja ID:t
        $url = "https://api.laji.fi/v0/warehouse/query/unit/list?target=" . urlencode($tieteellinen) . "&pageSize=1&selected=unit.linkings.taxon.id,unit.linkings.taxon.scientificName,unit.linkings.taxon.vernacularName.fi&access_token=" . $token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPGET, true); 
        
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($code !== 200) {
            $kasvikortit[$pid]['debug'] = 'API virhe koodi: ' . $code;
            continue;
        }

        $data = json_decode($body, true);

        if (!empty($data) && isset($data['results']) && is_array($data['results']) && !empty($data['results'])) {
            // Poimitaan ensimmäisen havainnon linkitetty taksoni
            $havainto = $data['results'][0];
            
            if (isset($havainto['unit']['linkings']['taxon'])) {
                $t = $havainto['unit']['linkings']['taxon'];
                
                $kasvikortit[$pid]['status']            = 'match';
                $kasvikortit[$pid]['laji_id']           = isset($t['id']) ? $t['id'] : '';
                $kasvikortit[$pid]['laji_tieteellinen'] = isset($t['scientificName']) ? $t['scientificName'] : '';
                $kasvikortit[$pid]['laji_suomi']        = isset($t['vernacularName']['fi']) ? $t['vernacularName']['fi'] : '-';
            } else {
                $kasvikortit[$pid]['debug'] = 'Havainto löytyi, mutta lajitunniste puuttui';
            }
        } else {
            $kasvikortit[$pid]['debug'] = 'Ei havaintoja Laji.fi:ssä tällä nimellä';
        }
    }
    wp_reset_postdata();

    set_transient('lajifi_paritus_valimuisti', $kasvikortit, HOUR_IN_SECONDS);
    wp_send_json_success(['osumat' => $kasvikortit]);
}
add_action('wp_ajax_lajifi_etsi_vastaavuudet', 'lajifi_ajax_etsi_vastaavuudet');

/**
 * AJAX: TALLENTAA HYVÄKSYTYT OSUMAT
 */
function lajifi_ajax_tallenna_vastaavuudet() {
    if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Ei oikeuksia.']);
    if (!isset($_POST['post_ids']) || !is_array($_POST['post_ids'])) wp_send_json_error(['message' => 'Ei kohteita.']);

    $valitut_id_t = array_map('intval', $_POST['post_ids']);
    $valimuisti = get_transient('lajifi_paritus_valimuisti');

    if (!$valimuisti) wp_send_json_error(['message' => 'Haku vanhentui.']);

    $paivitetty_laskuri = 0;
    foreach ($valitut_id_t as $pid) {
        if (isset($valimuisti[$pid]) && $valimuisti[$pid]['status'] === 'match') {
            $data = $valimuisti[$pid];
            update_post_meta($pid, 'laji_fi_id', sanitize_text_field($data['laji_id']));
            if (!empty($data['laji_suomi']) && $data['laji_suomi'] !== '-') {
                $nykyinen_suomi = get_post_meta($pid, 'suomenkielinen_nimi', true);
                if (empty($nykyinen_suomi)) {
                    update_post_meta($pid, 'suomenkielinen_nimi', sanitize_text_field($data['laji_suomi']));
                }
            }
            $paivitetty_laskuri++;
        }
    }

    delete_transient('lajifi_paritus_valimuisti');
    wp_send_json_success(['paivitetty' => $paivitetty_laskuri]);
}
add_action('wp_ajax_lajifi_tallenna_vastaavuudet', 'lajifi_ajax_tallenna_vastaavuudet');