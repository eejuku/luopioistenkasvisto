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

add_action( 'init', function () {
    // Snippetti #6 ajettiin vain julkisella puolella (scope: front-end).
    if ( ! is_admin() ) {
        add_shortcode( 'kasvisto_uusi', 'kasvisto_sc_kasvisto_uusi' );
    }
    add_shortcode( 'kasvisto_testiversio', 'kasvisto_sc_testiversio' );
    add_shortcode( 'jakalat_lisaryhmat', 'kasvisto_sc_jakalat_lisaryhmat' );
    add_shortcode( 'kasvisto_viimeisimmat', 'kasvisto_sc_viimeisimmat' );
    add_shortcode( 'kuvagalleria', 'kasvisto_sc_kuvagalleria' );

    // Snippetin #16 funktio on olemassa → snippetti on vielä päällä, käytetään sitä.
    if ( ! function_exists( 'render_kasvikartat_editor_v5_2' ) ) {
        add_action( 'admin_menu', 'kasvisto_karttaeditori_menu' );
        add_action( 'wp_ajax_save_kasvi_data', 'kasvisto_karttaeditori_tallenna' );
    }
} );
