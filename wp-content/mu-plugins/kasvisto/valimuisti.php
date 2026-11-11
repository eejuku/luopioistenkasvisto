<?php
/**
 * Listojen välimuisti.
 *
 * Lajilistat käyvät läpi kaikki kasvikortit, mikä vie sekunteja ja hidastuu korttien
 * määrän kasvaessa. Valmis lista (HTML) tallennetaan siksi tietokantaan, ja seuraavat
 * kävijät saavat sen suoraan.
 *
 * Tallessa oleva lista vanhenee heti, kun mikä tahansa kasvikortti muuttuu:
 *   - kortti tallennetaan, poistetaan tai siirretään roskakoriin
 *   - kortin kenttä muuttuu (myös karttaeditori, WP All Import, WP Sheet Editor)
 * Silloin "listaversio" kasvaa, ja kaikki listat lasketaan seuraavalla latauksella
 * uudelleen. Samalla tyhjennetään WP Super Cachen sivuvälimuisti, jotta sivut eivät
 * näytä vanhaa listaa.
 *
 * Käsin tyhjennys: ?kasvisto_tyhjenna_listat=1 ylläpitäjänä kirjautuneena.
 *
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Listojen säilytysaika, vaikka mikään ei muuttuisi. */
const KASVISTO_LISTA_SAILYTYS = DAY_IN_SECONDS;

/**
 * Nykyinen listaversio. Kasvaa aina, kun kasvikortit muuttuvat.
 */
function kasvisto_lista_versio() {
    return (int) get_option( 'kasvisto_lista_versio', 1 );
}

/**
 * Merkitsee kaikki tallessa olevat listat vanhentuneiksi (kerran per pyyntö).
 */
function kasvisto_vanhenna_listat() {
    static $tehty = false;
    if ( $tehty ) {
        return;
    }
    $tehty = true;

    update_option( 'kasvisto_lista_versio', kasvisto_lista_versio() + 1, false );

    // WP Super Cache: koko sivuvälimuisti tyhjäksi, koska listat ovat monella sivulla.
    if ( function_exists( 'wp_cache_clear_cache' ) ) {
        wp_cache_clear_cache();
    }
}

/**
 * Hakee valmiin listan välimuistista tai rakentaa sen.
 *
 * @param string   $tag      shortcoden nimi
 * @param array    $atts     shortcoden parametrit
 * @param callable $rakenna  palauttaa listan HTML:n
 * @return string
 */
function kasvisto_lista_valimuistista( $tag, $atts, callable $rakenna ) {
    $atts = is_array( $atts ) ? $atts : [];
    ksort( $atts );
    $avain = 'kasvisto_l_' . md5( $tag . '|' . wp_json_encode( $atts ) . '|' . kasvisto_lista_versio() );

    $tallessa = get_transient( $avain );
    if ( is_string( $tallessa ) && $tallessa !== '' ) {
        $html = function_exists( 'gzuncompress' ) ? @gzuncompress( base64_decode( $tallessa ) ) : $tallessa;
        if ( is_string( $html ) && $html !== '' ) {
            return $html;
        }
    }

    $html = $rakenna();

    if ( is_string( $html ) && $html !== '' ) {
        // Pakattuna tietokantaan: suurimmat listat ovat satoja kilotavuja.
        $tallennettava = function_exists( 'gzcompress' ) ? base64_encode( gzcompress( $html, 6 ) ) : $html;
        set_transient( $avain, $tallennettava, KASVISTO_LISTA_SAILYTYS );
    }
    return $html;
}

// --- Milloin listat vanhenevat --------------------------------------------------

// Kortin tallennus (myös ACF:n kentät tallentuvat tämän jälkeen, ks. acf/save_post).
add_action( 'save_post_kasvi', 'kasvisto_vanhenna_listat' );
add_action( 'acf/save_post', function ( $post_id ) {
    if ( is_numeric( $post_id ) && get_post_type( (int) $post_id ) === 'kasvi' ) {
        kasvisto_vanhenna_listat();
    }
}, 20 );

// Poisto, roskakori ja palautus.
foreach ( [ 'deleted_post', 'trashed_post', 'untrashed_post' ] as $kasvisto_koukku ) {
    add_action( $kasvisto_koukku, function ( $post_id ) {
        if ( get_post_type( $post_id ) === 'kasvi' ) {
            kasvisto_vanhenna_listat();
        }
    } );
}
unset( $kasvisto_koukku );

// Kenttien muutokset ilman tallennusta (karttaeditori, tuontityökalut).
foreach ( [ 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ] as $kasvisto_koukku ) {
    add_action( $kasvisto_koukku, function ( $meta_id, $post_id, $meta_key ) {
        if ( is_string( $meta_key ) && strpos( $meta_key, '_edit_' ) === 0 ) {
            return; // muokkauslukko ym. eivät muuta listoja
        }
        if ( get_post_type( $post_id ) === 'kasvi' ) {
            kasvisto_vanhenna_listat();
        }
    }, 10, 3 );
}
unset( $kasvisto_koukku );

// Käsin tyhjennys ylläpitäjälle: lisää osoitteen perään ?kasvisto_tyhjenna_listat=1
add_action( 'init', function () {
    if ( isset( $_GET['kasvisto_tyhjenna_listat'] ) && current_user_can( 'manage_options' ) ) {
        kasvisto_vanhenna_listat();
    }
}, 20 );
