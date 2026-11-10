<?php
/**
 * Shortcode [jakalat_lisaryhmat] – jäkälälista lisäryhmän mukaan.
 *
 * Alun perin Code Snippets -snippetti #15. Snippetin oma toteutus kaatoi
 * molemmat sitä käyttävät sivut (HTTP 500, havaittu 16.10. ja 10.11.2026):
 *   /jakalat/50-tavallisinta-jakalaa/
 *   /jakalat/muualta-loydettyja-jakalia/
 *
 * Nyt shortcode käyttää samaa listaa kuin [kasvisto_uusi], joka toimii
 * Jäkälälajit-sivulla. Sivujen sisältöä ei tarvitse muuttaa: vanha muoto
 * [jakalat_lisaryhmat ryhma="..."] toimii edelleen.
 *
 * Vastaa: [kasvisto_uusi ryhma="Jäkälät" jakalien_lisaryhmat="..." otsikko="..." vain_luopioiset="ei"]
 *
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function kasvisto_sc_jakalat_lisaryhmat( $atts ) {
    $a = shortcode_atts( [
        'ryhma' => '', // esim. "50 tavallisinta jäkälää"
    ], $atts );

    $lisaryhma = trim( $a['ryhma'] );

    return kasvisto_sc_kasvisto_uusi( [
        'ryhma'               => 'Jäkälät',
        'jakalien_lisaryhmat' => $lisaryhma,
        'otsikko'             => $lisaryhma,
        // Sammalia koskeva rajaus ei liity jäkäliin.
        'vain_luopioiset'     => 'ei',
    ] );
}
