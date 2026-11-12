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

/**
 * Kasvin synonyymit yhtenä hakutekstinä (pienin kirjaimin, erottimena " | ").
 * Listojen haku vertaa hakusanaa myös tähän, mutta synonyymeja ei näytetä listassa.
 *
 * Kasvikortin synonyymit ovat ACF-toistokentässä `synonyymiryhma`, alikentät
 * `suomenkielinen_synonyymi` ja `tieteellinen_synonyymi` (ks. single-kasvi.php).
 * (Listat lukivat aiemmin olematonta kenttää `synonyymit`, joten haku ei löytänyt
 * synonyymeja.)
 */
function kasvisto_synonyymit_hakuun( $post_id ) {
    if ( ! function_exists( 'get_field' ) ) {
        return '';
    }
    $rivit = get_field( 'synonyymiryhma', $post_id );
    if ( ! is_array( $rivit ) || empty( $rivit ) ) {
        return '';
    }
    $nimet = [];
    foreach ( $rivit as $rivi ) {
        if ( ! is_array( $rivi ) ) {
            continue;
        }
        foreach ( [ 'suomenkielinen_synonyymi', 'tieteellinen_synonyymi' ] as $kentta ) {
            $nimi = isset( $rivi[ $kentta ] ) ? trim( wp_strip_all_tags( (string) $rivi[ $kentta ] ) ) : '';
            if ( $nimi !== '' ) {
                $nimet[] = mb_strtolower( $nimi, 'UTF-8' );
            }
        }
    }
    return implode( ' | ', array_unique( $nimet ) );
}

/**
 * Suurennoksen (lightbox) kuvaosoite.
 *
 * Lightbox näytti ennen alkuperäisen tiedoston (jopa ~3 Mt). Alkuperäisessä ovat myös
 * kameran metatiedot (EXIF/IPTC/XMP). WordPressin tekemät koot ovat kevyitä ja niistä
 * metatiedot on jo poistettu, joten käytetään suurinta niistä.
 *
 * Järjestys: 2048x2048 → 1536x1536 → large. Jos kuva on niin pieni, ettei mitään
 * näistä ole tehty, palautetaan alkuperäinen.
 *
 * @param array|int $kuva ACF:n kuvataulukko tai liitteen ID
 * @return string
 */
function kasvisto_suurennoksen_url( $kuva ) {
    $koot = [ '2048x2048', '1536x1536', 'large' ];

    if ( is_array( $kuva ) ) {
        foreach ( $koot as $koko ) {
            if ( ! empty( $kuva['sizes'][ $koko ] ) ) {
                return $kuva['sizes'][ $koko ];
            }
        }
        $kuva = ! empty( $kuva['ID'] ) ? (int) $kuva['ID'] : ( $kuva['url'] ?? '' );
        if ( ! is_int( $kuva ) ) {
            return (string) $kuva;
        }
    }

    $meta = wp_get_attachment_metadata( (int) $kuva );
    foreach ( $koot as $koko ) {
        if ( ! empty( $meta['sizes'][ $koko ] ) ) {
            $url = wp_get_attachment_image_url( (int) $kuva, $koko );
            if ( $url ) {
                return $url;
            }
        }
    }
    return (string) wp_get_attachment_url( (int) $kuva );
}
