<?php
/**
 * Kuvien laatu ja alkuperäiset kuvat.
 *
 * Linjaus (11/2026, Tuomo Kuitunen / Eero): sivusto on kuvavetoinen ja kuvia käytetään
 * lajien tunnistamiseen, joten
 *  - alkuperäiset kuvat säilytetään aina (WordPress säilyttää ne myös silloin, kun se
 *    tekee isoista kuvista -scaled-version) ja ne ovat avattavissa suurennoksesta
 *  - lightbox näyttää nopean 2048 px:n version (ks. kasvisto_suurennoksen_url()), josta
 *    pääsee linkillä täysikokoiseen alkuperäiseen
 *  - WordPressin tekemien pienennösten JPEG-laatu nostetaan oletuksesta 82 tasoon 90,
 *    jotta pienet yksityiskohdat (karvat, lehtisolut, jäkälien pinta) säilyvät
 *
 * Alkuperäisistä on poistettu sijaintitieto (ks. kuvien-sijainnit.php), muut
 * metatiedot (kuvaaja, kamera, kuvausaika) säilyvät.
 *
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Pienennösten JPEG-laatu (WordPressin oletus 82). Koskee uusia latauksia. */
const KASVISTO_JPEG_LAATU = 90;

add_filter( 'wp_editor_set_quality', function ( $laatu, $mime = '' ) {
    if ( $mime === '' || $mime === 'image/jpeg' ) {
        return KASVISTO_JPEG_LAATU;
    }
    return $laatu;
}, 10, 2 );

/**
 * Linkki täysikokoiseen alkuperäiseen kuvaan suurennoksen kuvatekstiin.
 * Tyhjä, jos lightbox näyttää jo alkuperäisen (pieni kuva) tai kuva on merkitty
 * "ei merkintää" (kartat, logot).
 */
function kasvisto_alkuperaisen_kuvan_linkki_html( $kuva_id ) {
    $kuva_id = (int) $kuva_id;
    if ( ! $kuva_id || ! wp_attachment_is_image( $kuva_id ) ) {
        return '';
    }
    if ( function_exists( 'kasvisto_kuvan_lisenssi' ) && kasvisto_kuvan_lisenssi( $kuva_id )['tyyppi'] === 'ei' ) {
        return '';
    }
    $meta = wp_get_attachment_metadata( $kuva_id );
    $pienennos = false;
    foreach ( [ '2048x2048', '1536x1536', 'large' ] as $koko ) {
        if ( ! empty( $meta['sizes'][ $koko ] ) ) {
            $pienennos = true;
            break;
        }
    }
    if ( ! $pienennos ) {
        return '';
    }
    $url = wp_get_original_image_url( $kuva_id );
    if ( ! $url ) {
        return '';
    }
    $koko = '';
    if ( empty( $meta['original_image'] ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
        $koko = ' (' . (int) $meta['width'] . ' × ' . (int) $meta['height'] . ' px)';
    }
    return '<span class="kuva-alkuperainen"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">'
         . 'Avaa täysikokoinen kuva' . esc_html( $koko ) . ' ↗</a></span>';
}
