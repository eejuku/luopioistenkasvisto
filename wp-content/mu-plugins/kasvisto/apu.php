<?php
/**
 * Yhteiset apufunktiot listoille.
 *
 * Rekisteröinti: ../kasvisto-loader.php (ladataan ennen listoja)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Käy läpi kaikki julkaistut kasvikortit erissä ja palauttaa yhden kortin ID:n kerrallaan.
 *
 * Miksi: listat lukevat jokaisesta kortista useita ACF-kenttiä. WordPress ja ACF
 * säilyttävät jokaisen luetun kortin kentät muistissa koko sivulatauksen ajan, joten
 * kaikkien korttien läpikäynti kerralla vei muistin loppuun (128 Mt Dockerissa,
 * virhe "Allowed memory size exhausted" tiedostossa wp-includes/meta.php).
 *
 * Nyt kortit ladataan esim. 100 kappaleen erissä yhdellä tietokantahaulla, ja erän
 * jälkeen sen kortit poistetaan muistista. Muistinkulutus pysyy samana korttien
 * määrästä riippumatta.
 *
 * Silmukan sisällä globaali $post on kyseinen kortti, joten get_the_title(),
 * get_permalink() ym. toimivat ilman parametreja. Lopuksi kutsutaan wp_reset_postdata().
 *
 * Käyttö:
 *   foreach ( kasvisto_kasvit_erissa() as $post_id ) { ... continue; ... }
 *
 * @param int $erakoko korttien määrä per erä
 * @return Generator<int>
 */
function kasvisto_kasvit_erissa( $erakoko = 100 ) {
    global $post;

    $idt = get_posts( [
        'post_type'              => 'kasvi',
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'orderby'                => 'ID',
        'order'                  => 'ASC',
        'no_found_rows'          => true,
        'suppress_filters'       => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ] );

    foreach ( array_chunk( $idt, max( 1, (int) $erakoko ) ) as $era ) {
        // Koko erän kortit ja kentät yhdellä haulla (ei kyselyä per kortti).
        _prime_post_caches( $era, false, false );
        update_meta_cache( 'post', $era );

        foreach ( $era as $id ) {
            $kortti = get_post( $id );
            if ( ! $kortti ) {
                continue;
            }
            $post = $kortti; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
            setup_postdata( $kortti );
            yield $id;
        }

        // Erän kortit pois muistista.
        foreach ( $era as $id ) {
            wp_cache_delete( $id, 'post_meta' );
            wp_cache_delete( $id, 'posts' );
        }
        kasvisto_tyhjenna_acf_valimuisti();
    }

    wp_reset_postdata();
}

/**
 * ACF säilyttää luetut kenttäarvot omassa välimuistissaan. Tyhjennetään se erien välissä.
 */
function kasvisto_tyhjenna_acf_valimuisti() {
    if ( ! function_exists( 'acf_get_store' ) ) {
        return;
    }
    foreach ( [ 'values', 'post_values' ] as $nimi ) {
        $store = acf_get_store( $nimi );
        if ( $store && method_exists( $store, 'reset' ) ) {
            $store->reset();
        }
    }
}
