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
 * Sivut, joilla on ryhmän uhanalaisuuslista ([kasvisto_testiversio]).
 * Kasvikortin merkki linkittää tänne ja suodattaa listan (?uhanalaisuus=VU).
 * Kun muille ryhmille tehdään vastaava sivu, lisää se tähän.
 */
function kasvisto_uhanalaisuus_listasivut() {
    return [
        'Sammalet' => '/sammalet/suojelu/',
    ];
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
    $sivut = kasvisto_uhanalaisuus_listasivut();
    $listasivu = ( is_string( $ryhma ) && isset( $sivut[ $ryhma ] ) ) ? $sivut[ $ryhma ] : '';

    $html = '<div class="uhanalaisuus-merkit" aria-label="Uhanalaisuus">';
    $html .= '<span class="uhanalaisuus-merkit-otsikko">Uhanalaisuus</span>';
    $html .= '<div class="uhanalaisuus-merkit-rivi">';
    foreach ( $luokat as $luokka ) {
        $koodi  = $luokka['koodi'];
        $sisalto = '<span class="u-merkki-lyhenne">' . esc_html( $koodi !== '' ? $koodi : '–' ) . '</span>'
                 . '<span class="u-merkki-kuvaus">' . esc_html( $luokka['kuvaus'] ) . '</span>';
        $luokat_css = 'u-merkki' . ( $koodi !== '' ? ' u-merkki-' . strtolower( $koodi ) : '' );

        if ( $listasivu !== '' && $koodi !== '' ) {
            $url = add_query_arg( 'uhanalaisuus', $koodi, home_url( $listasivu ) );
            $html .= '<a class="' . esc_attr( $luokat_css ) . '" href="' . esc_url( $url ) . '" title="'
                   . esc_attr( 'Näytä kaikki ' . mb_strtolower( $luokka['kuvaus'] ) . ' (' . $ryhma . ')' ) . '">' . $sisalto . '</a>';
        } else {
            $html .= '<span class="' . esc_attr( $luokat_css ) . '">' . $sisalto . '</span>';
        }
    }
    $html .= '</div></div>';
    return $html;
}
