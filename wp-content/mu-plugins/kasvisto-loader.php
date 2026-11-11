<?php
/**
 * Plugin Name: Luopioisten kasvisto – sivuston toiminnot
 * Description: Lajilistat, kuvagalleria ja karttaeditori. Siirretty Code Snippetsistä versionhallintaan 11/2026.
 *
 * Mu-plugin latautuu automaattisesti, eikä sitä voi poistaa käytöstä wp-administa.
 * WordPress lataa vain mu-plugins-kansion päätason tiedostot, joten tämä tiedosto
 * hakee varsinaisen koodin kansiosta mu-plugins/kasvisto/.
 *
 * Rinnakkaiselo Code Snippetsin kanssa: kaikki rekisteröidään vasta init-vaiheessa,
 * eli snippettien jälkeen. Shortcodet korvaavat silloin snippettien versiot.
 * Karttaeditori jätetään rekisteröimättä niin kauan kuin snippetti #16 on päällä,
 * jotta valikossa ei ole kahta "Kasvikartat"-kohtaa.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$kasvisto_dir = __DIR__ . '/kasvisto/';

require_once $kasvisto_dir . 'uhanalaisuus.php';

require_once $kasvisto_dir . 'lista-kasvisto-uusi.php';
require_once $kasvisto_dir . 'lista-uhanalaisuus.php';
require_once $kasvisto_dir . 'jakalat-lisaryhmat.php';
require_once $kasvisto_dir . 'uudet-ja-muokatut.php';
require_once $kasvisto_dir . 'kuvagalleria.php';
require_once $kasvisto_dir . 'karttaeditori.php';

/**
 * Onko kyse editorin taustapyynnöstä (lohkoeditorin tallennus/esikatselu REST-rajapinnan
 * kautta tai wp-admin)? Silloin listoja ei rakenneta: ne hakevat kaikki kasvit ja
 * tulostavat skriptejä, mikä on turhaa editorille ja voi kaataa tallennuksen.
 */
function kasvisto_editorin_pyynto() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return true;
    }
    return defined( 'REST_REQUEST' ) && REST_REQUEST && is_user_logged_in();
}

/**
 * Rekisteröi shortcoden suojattuna:
 *  - editorin pyynnöissä palautetaan kevyt paikkamerkki
 *  - listan jälkeen palautetaan sivun oma $post (listat käyvät läpi kaikki kasvit,
 *    ja wp_reset_postdata() ei palauta sitä REST-pyynnöissä)
 */
function kasvisto_rekisteroi_shortcode( $tag, $callback ) {
    add_shortcode( $tag, function ( $atts, $content = null, $sc_tag = '' ) use ( $tag, $callback ) {
        if ( kasvisto_editorin_pyynto() ) {
            $kuvaus = '';
            if ( is_array( $atts ) && ! empty( $atts['ryhma'] ) ) {
                $kuvaus = ' (' . $atts['ryhma'] . ')';
            }
            return '<p class="kasvisto-paikkamerkki" style="padding:12px;border:1px dashed #8c8f94;color:#50575e;">'
                 . esc_html( '[' . $tag . ']' . $kuvaus . ' – lista näkyy julkaistulla sivulla.' ) . '</p>';
        }

        global $post;
        $alkuperainen = $post;
        $puskuritaso  = ob_get_level();
        try {
            $html = call_user_func( $callback, $atts, $content, $sc_tag );
        } catch ( \Throwable $e ) {
            // Lista kaatui: koko sivu ei kaadu. Syy lokiin ja ylläpitäjälle näkyviin.
            while ( ob_get_level() > $puskuritaso ) {
                ob_end_clean();
            }
            $syy = get_class( $e ) . ': ' . $e->getMessage() . ' (' . basename( $e->getFile() ) . ' rivi ' . $e->getLine() . ')';
            error_log( '[kasvisto] [' . $tag . '] ' . $syy );
            $html = current_user_can( 'manage_options' )
                ? '<p style="padding:12px;border:2px solid #d63638;background:#fcf0f1;color:#1d2327;"><strong>[' . esc_html( $tag ) . '] kaatui:</strong> ' . esc_html( $syy ) . '</p>'
                : '';
        }
        $post = $alkuperainen;
        if ( $alkuperainen instanceof WP_Post ) {
            setup_postdata( $alkuperainen );
        }
        return $html;
    } );
}

add_action( 'init', function () {
    kasvisto_rekisteroi_shortcode( 'kasvisto_uusi', 'kasvisto_sc_kasvisto_uusi' );
    kasvisto_rekisteroi_shortcode( 'kasvisto_testiversio', 'kasvisto_sc_testiversio' );
    kasvisto_rekisteroi_shortcode( 'jakalat_lisaryhmat', 'kasvisto_sc_jakalat_lisaryhmat' );
    kasvisto_rekisteroi_shortcode( 'kasvisto_viimeisimmat', 'kasvisto_sc_viimeisimmat' );
    kasvisto_rekisteroi_shortcode( 'kuvagalleria', 'kasvisto_sc_kuvagalleria' );
    kasvisto_rekisteroi_shortcode( 'kasvisto_uhanalaisuussivu', 'kasvisto_sc_uhanalaisuussivu' );

    // Snippetin #16 funktio on olemassa → snippetti on vielä päällä, käytetään sitä.
    if ( ! function_exists( 'render_kasvikartat_editor_v5_2' ) ) {
        add_action( 'admin_menu', 'kasvisto_karttaeditori_menu' );
        add_action( 'wp_ajax_save_kasvi_data', 'kasvisto_karttaeditori_tallenna' );
    }
} );
