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
 * Kunkin kasviryhmän uhanalaisuussivu. Kasvikortin merkki linkittää tänne ja
 * suodattaa sivun listan (?uhanalaisuus=VU).
 *
 * Linkki näytetään vasta, kun sivu on julkaistu ja sen sisällössä on
 * [kasvisto_uhanalaisuussivu]- tai [kasvisto_testiversio]-shortcode. Sivun voi siis
 * rakentaa tuotannossa rauhassa, ja linkit aktivoituvat itsestään.
 */
function kasvisto_uhanalaisuus_listasivut() {
    return [
        'Putkilokasvit' => 'putkilokasvit/suojeluohjelmat/uhanalaiset-kasvit',
        'Sammalet'      => 'sammalet/suojelu',
        'Jäkälät'       => 'jakalat/suojelu',
        'Piensienet'    => 'piensienet/piensienten-suojelu',
    ];
}

/**
 * Ryhmän uhanalaisuussivun osoite tai '' (ei sivua / sivulla ei vielä listaa).
 */
function kasvisto_uhanalaisuus_listasivu_url( $ryhma ) {
    static $valimuisti = [];
    if ( ! is_string( $ryhma ) || $ryhma === '' ) {
        return '';
    }
    if ( array_key_exists( $ryhma, $valimuisti ) ) {
        return $valimuisti[ $ryhma ];
    }

    $url   = '';
    $sivut = kasvisto_uhanalaisuus_listasivut();
    if ( isset( $sivut[ $ryhma ] ) ) {
        $sivu = get_page_by_path( $sivut[ $ryhma ] );
        if ( $sivu && $sivu->post_status === 'publish'
            && ( has_shortcode( $sivu->post_content, 'kasvisto_uhanalaisuussivu' )
                || has_shortcode( $sivu->post_content, 'kasvisto_testiversio' ) ) ) {
            $url = get_permalink( $sivu );
        }
    }
    return $valimuisti[ $ryhma ] = $url;
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
