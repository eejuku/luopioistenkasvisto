<?php
/**
 * Uhanalaisuusluokat: yhteiset apufunktiot kasvikortin merkeille ja uhanalaisuuslistalle.
 *
 * ACF-kenttä `uhanalaisuus` on valintaruutukenttä, jonka arvot ovat muotoa
 * "Vaarantuneet (VU)". Kasvilla voi olla useampi luokka.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Luokat siinä järjestyksessä kuin ne näytetään. Avain = lyhenne, arvo = ACF-kentän arvo.
 */
function kasvisto_uhanalaisuus_sanakirja() {
    return [
        'NE' => 'Arvioimatta jätetyt (NE)',
        'NA' => 'Arviointiin kelpaamaton (NA)',
        'DD' => 'Puutteellisesti tunnetut (DD)',
        'RE' => 'Hävinneet (RE)',
        'EW' => 'Luonnosta hävinneet (EW)',
        'CR' => 'Äärimmäisen uhanalaiset (CR)',
        'EN' => 'Erittäin uhanalaiset (EN)',
        'VU' => 'Vaarantuneet (VU)',
        'NT' => 'Silmälläpidettävät (NT)',
        'LC' => 'Elinvoimaiset (LC)',
        'RT' => 'Alueellisesti uhanalaiset (RT)',
    ];
}

/**
 * Kasvikortin uhanalaisuusmerkki linkittää ryhmän uhanalaisuuslistaan ja suodattaa sen
 * valmiiksi (?uhanalaisuus=VU).
 *
 * Sivu löydetään automaattisesti: se julkaistu sivu, jonka sisällössä on
 * [kasvisto_uhanalaisuussivu ryhma="..."] (tai [kasvisto_testiversio ryhma="..."]).
 * Sivun voi siis luoda tai siirtää minne tahansa, eikä linkkejä tarvitse säätää.
 *
 * Jos samalle ryhmälle on useampi sivu, valitaan:
 *   1. [kasvisto_uhanalaisuussivu] ennen [kasvisto_testiversio]-sivua
 *   2. alla luetellut ensisijaiset sivut
 *   3. ei testisivuja (osoitteessa "testi"), jos muitakin on
 *   4. vanhin sivu
 */
function kasvisto_uhanalaisuus_ensisijaiset_sivut() {
    return [
        'Putkilokasvit' => 'putkilokasvit/suojelu',
        'Sammalet'      => 'sammalet/suojelu',
        'Jäkälät'       => 'jakalat/suojelu',
        'Piensienet'    => 'piensienet/piensienten-suojelu',
    ];
}

/**
 * Ryhmä (pienin kirjaimin) → uhanalaisuussivun osoite. Tulos välimuistissa 12 h,
 * ja välimuisti tyhjennetään aina, kun jokin sivu tallennetaan tai poistetaan.
 */
function kasvisto_uhanalaisuus_sivukartta() {
    static $kartta = null;
    if ( $kartta !== null ) {
        return $kartta;
    }
    $tallessa = get_transient( 'kasvisto_uhanalaisuus_sivut' );
    if ( is_array( $tallessa ) ) {
        return $kartta = $tallessa;
    }

    global $wpdb;
    $sivut = $wpdb->get_results(
        "SELECT ID, post_content FROM {$wpdb->posts}
         WHERE post_type = 'page' AND post_status = 'publish'
           AND ( post_content LIKE '%[kasvisto_uhanalaisuussivu%' OR post_content LIKE '%[kasvisto_testiversio%' )
         ORDER BY ID ASC"
    );

    $ensisijaiset = array_combine( array_map( 'mb_strtolower', array_keys( kasvisto_uhanalaisuus_ensisijaiset_sivut() ) ), array_values( kasvisto_uhanalaisuus_ensisijaiset_sivut() ) );
    $parhaat      = []; // ryhma => [pisteet, url]
    $regex        = get_shortcode_regex( [ 'kasvisto_uhanalaisuussivu', 'kasvisto_testiversio' ] );

    foreach ( (array) $sivut as $sivu ) {
        if ( ! preg_match_all( '/' . $regex . '/s', $sivu->post_content, $osumat, PREG_SET_ORDER ) ) {
            continue;
        }
        $polku = trim( (string) get_page_uri( $sivu->ID ), '/' );
        $url   = get_permalink( $sivu->ID );
        foreach ( $osumat as $osuma ) {
            $atts  = shortcode_parse_atts( $osuma[3] );
            $ryhma = is_array( $atts ) && ! empty( $atts['ryhma'] ) ? mb_strtolower( trim( $atts['ryhma'] ) ) : '';
            if ( $ryhma === '' ) {
                continue;
            }
            $pisteet = 0;
            if ( $osuma[2] === 'kasvisto_uhanalaisuussivu' ) {
                $pisteet += 100;
            }
            if ( isset( $ensisijaiset[ $ryhma ] ) && $ensisijaiset[ $ryhma ] === $polku ) {
                $pisteet += 10;
            }
            if ( strpos( $polku, 'testi' ) === false ) {
                $pisteet += 1;
            }
            // Tasapelissä vanhin (pienin ID) voittaa, koska sivut käydään ID-järjestyksessä.
            if ( ! isset( $parhaat[ $ryhma ] ) || $pisteet > $parhaat[ $ryhma ][0] ) {
                $parhaat[ $ryhma ] = [ $pisteet, $url ];
            }
        }
    }

    $kartta = array_map( function ( $p ) { return $p[1]; }, $parhaat );
    set_transient( 'kasvisto_uhanalaisuus_sivut', $kartta, 12 * HOUR_IN_SECONDS );
    return $kartta;
}

// Sivun tallennus tai poisto → sivukartta rakennetaan uudelleen.
add_action( 'save_post_page', function () { delete_transient( 'kasvisto_uhanalaisuus_sivut' ); } );
add_action( 'deleted_post', function () { delete_transient( 'kasvisto_uhanalaisuus_sivut' ); } );
add_action( 'trashed_post', function () { delete_transient( 'kasvisto_uhanalaisuus_sivut' ); } );

/**
 * Ryhmän uhanalaisuussivun osoite tai '' (ryhmälle ei ole sivua, jolla lista on).
 */
function kasvisto_uhanalaisuus_listasivu_url( $ryhma ) {
    if ( ! is_string( $ryhma ) || trim( $ryhma ) === '' ) {
        return '';
    }
    $kartta = kasvisto_uhanalaisuus_sivukartta();
    $avain  = mb_strtolower( trim( $ryhma ) );
    return isset( $kartta[ $avain ] ) ? $kartta[ $avain ] : '';
}

/**
 * Ryhmän nimi genetiivissä otsikoita varten ("Sammalten uhanalaisuus").
 */
function kasvisto_uhanalaisuus_ryhma_genetiivi( $ryhma ) {
    $g = [
        'Putkilokasvit' => 'Putkilokasvien',
        'Sammalet'      => 'Sammalten',
        'Jäkälät'       => 'Jäkälien',
        'Piensienet'    => 'Piensienten',
    ];
    return $g[ $ryhma ] ?? $ryhma;
}

/**
 * Kasvin uhanalaisuusluokat: [ ['koodi' => 'VU', 'kuvaus' => 'Vaarantuneet'], ... ]
 * Tyhjä taulukko, jos kenttä on tyhjä.
 */
function kasvisto_uhanalaisuus_luokat( $post_id ) {
    if ( ! function_exists( 'get_field' ) ) {
        return [];
    }
    $kentta = get_field( 'uhanalaisuus', $post_id );
    if ( empty( $kentta ) ) {
        return [];
    }

    $luokat = [];
    foreach ( (array) $kentta as $valinta ) {
        // Kenttä voi palauttaa arvon tai taulukon (value/label) asetuksista riippuen.
        $arvo = is_array( $valinta ) ? ( $valinta['value'] ?? '' ) : $valinta;
        $arvo = trim( (string) $arvo );
        if ( $arvo === '' ) {
            continue;
        }
        if ( preg_match( '/^(.*?)\s*\(([A-Z]{2})\)$/u', $arvo, $osat ) ) {
            $luokat[ $osat[2] ] = [ 'koodi' => $osat[2], 'kuvaus' => $osat[1] ];
        } else {
            $luokat[ $arvo ] = [ 'koodi' => '', 'kuvaus' => $arvo ];
        }
    }
    return array_values( $luokat );
}

/**
 * Kasvikortin uhanalaisuusmerkit (HTML). Tyhjä merkkijono, jos luokkia ei ole.
 */
function kasvisto_uhanalaisuus_merkit_html( $post_id ) {
    $luokat = kasvisto_uhanalaisuus_luokat( $post_id );
    if ( empty( $luokat ) ) {
        return '';
    }

    $ryhma = function_exists( 'get_field' ) ? get_field( 'ryhma', $post_id ) : '';
    $ryhma = is_array( $ryhma ) ? reset( $ryhma ) : $ryhma;
    $listasivu = kasvisto_uhanalaisuus_listasivu_url( $ryhma );

    $html = '<div class="uhanalaisuus-merkit" aria-label="Uhanalaisuus">';
    $html .= '<span class="uhanalaisuus-merkit-otsikko">Uhanalaisuus</span>';
    $html .= '<div class="uhanalaisuus-merkit-rivi">';
    foreach ( $luokat as $luokka ) {
        $koodi  = $luokka['koodi'];
        $sisalto = '<span class="u-merkki-lyhenne">' . esc_html( $koodi !== '' ? $koodi : '–' ) . '</span>'
                 . '<span class="u-merkki-kuvaus">' . esc_html( $luokka['kuvaus'] ) . '</span>';
        $luokat_css = 'u-merkki' . ( $koodi !== '' ? ' u-merkki-' . strtolower( $koodi ) : '' );

        if ( $listasivu !== '' && $koodi !== '' ) {
            $url = add_query_arg( 'uhanalaisuus', $koodi, $listasivu );
            $html .= '<a class="' . esc_attr( $luokat_css ) . '" href="' . esc_url( $url ) . '" title="'
                   . esc_attr( 'Näytä kaikki ' . mb_strtolower( $luokka['kuvaus'] ) . ' (' . $ryhma . ')' ) . '">' . $sisalto . '</a>';
        } else {
            $html .= '<span class="' . esc_attr( $luokat_css ) . '">' . $sisalto . '</span>';
        }
    }
    $html .= '</div></div>';
    return $html;
}

/**
 * Shortcode [kasvisto_uhanalaisuussivu ryhma="Jäkälät"]
 *
 * Ryhmän uhanalaisuussivun listaustyökalu: koko ryhmän lajilista, jossa
 * uhanalaisuusluokka valitaan napeista. Sama työkalu kuin [kasvisto_testiversio],
 * mutta ryhmä on pakollinen ja otsikolla on valmis oletus ("Jäkälien uhanalaisuus").
 *
 * Kaikki [kasvisto_testiversio]-parametrit toimivat sellaisinaan, esim.
 *   [kasvisto_uhanalaisuussivu ryhma="Sammalet" loytopisteet="kylla" hakukentta="ei"
 *    aakkosnavigaatio="ei" otsikko="Sammalten uhanalaisuus" alaotsikko="..."]
 *
 *   ryhma            Putkilokasvit | Sammalet | Jäkälät | Piensienet (pakollinen)
 *   otsikko          listan otsikko (oletus "<Ryhmän> uhanalaisuus")
 *   alaotsikko       pienempi teksti otsikon alla
 *   loytopisteet     "kylla" näyttää löytöpaikkasarakkeen (oletus "ei")
 *   hakukentta       "ei" piilottaa hakukentän
 *   aakkosnavigaatio "ei" piilottaa aakkosnapit
 *   nayta_maara      "ei" piilottaa lajimäärän otsikosta
 *   putkilokasvien_lisaryhmat, sammalten_lisaryhmat, jakalien_lisaryhmat,
 *   piensienten_lisaryhmat   rajaus lisäryhmän mukaan (pilkuin erotettuna)
 *
 * Kasvikortin uhanalaisuusmerkki linkittää sivulle, jolla tämä (tai
 * [kasvisto_testiversio]) on, ja suodattaa listan valmiiksi (?uhanalaisuus=VU).
 */
function kasvisto_sc_uhanalaisuussivu( $atts ) {
    $atts  = is_array( $atts ) ? $atts : [];
    $ryhma = isset( $atts['ryhma'] ) ? trim( $atts['ryhma'] ) : '';

    if ( $ryhma === '' || ! function_exists( 'kasvisto_sc_testiversio' ) ) {
        return '<p><strong>Virhe:</strong> [kasvisto_uhanalaisuussivu] tarvitsee ryhmän, esim. ryhma="Jäkälät".</p>';
    }

    $atts['ryhma'] = $ryhma;
    if ( ! isset( $atts['otsikko'] ) || trim( $atts['otsikko'] ) === '' ) {
        $atts['otsikko'] = kasvisto_uhanalaisuus_ryhma_genetiivi( $ryhma ) . ' uhanalaisuus';
    }

    return kasvisto_sc_testiversio( $atts );
}
