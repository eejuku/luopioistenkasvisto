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
 * Rakentaa ryhmän uhanalaisuussivun /sammalet/suojelu/ -sivun mallin mukaan:
 *   1. lista kustakin luokasta (oletus RE, CR, EN, VU, NT, RT); tyhjät luokat
 *      kootaan yhdelle "ei löytöjä" -riville
 *   2. koko ryhmän lista uhanalaisuussuodattimilla ([kasvisto_testiversio])
 *
 * Parametrit:
 *   ryhma        Putkilokasvit | Sammalet | Jäkälät | Piensienet (pakollinen)
 *   luokat       pilkuin erotetut lyhenteet, esim. "CR,EN,VU,NT"
 *   loytopisteet "kylla" näyttää löytöpaikkasarakkeen (oletus), "ei" piilottaa
 *   otsikko      koko listan otsikko (oletus "<Ryhmän> uhanalaisuus")
 *   kokolista    "ei" jättää koko ryhmän suodatettavan listan pois
 */
function kasvisto_sc_uhanalaisuussivu( $atts ) {
    $a = shortcode_atts( [
        'ryhma'        => '',
        'luokat'       => 'RE,CR,EN,VU,NT,RT',
        'loytopisteet' => 'kylla',
        'otsikko'      => '',
        'kokolista'    => 'kylla',
    ], $atts );

    $ryhma = trim( $a['ryhma'] );
    if ( $ryhma === '' || ! function_exists( 'kasvisto_sc_kasvisto_uusi' ) ) {
        return '<p><strong>Virhe:</strong> [kasvisto_uhanalaisuussivu] tarvitsee ryhmän, esim. ryhma="Jäkälät".</p>';
    }

    $sanakirja = kasvisto_uhanalaisuus_sanakirja();
    $luokat    = array_values( array_filter( array_map( 'trim', explode( ',', strtoupper( $a['luokat'] ) ) ),
        function ( $k ) use ( $sanakirja ) { return isset( $sanakirja[ $k ] ); } ) );

    // Lasketaan lajimäärät luokittain samoin rajauksin kuin listat
    // ([kasvisto_uusi] jättää pois "Muualta löydettyjä sammalia").
    $maarat = array_fill_keys( $luokat, 0 );
    $kysely = new WP_Query( [
        'post_type'              => 'kasvi',
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
    ] );
    foreach ( $kysely->posts as $id ) {
        $kasvin_ryhma = get_field( 'ryhma', $id );
        $osuu = is_array( $kasvin_ryhma ) ? in_array( $ryhma, $kasvin_ryhma, true ) : ( strcasecmp( (string) $kasvin_ryhma, $ryhma ) === 0 );
        if ( ! $osuu ) {
            continue;
        }
        if ( in_array( 'Muualta löydettyjä sammalia', (array) get_field( 'sammalten_lisaryhmat', $id ), true ) ) {
            continue;
        }
        foreach ( kasvisto_uhanalaisuus_luokat( $id ) as $luokka ) {
            if ( isset( $maarat[ $luokka['koodi'] ] ) ) {
                $maarat[ $luokka['koodi'] ]++;
            }
        }
    }

    $html    = '<div class="uhanalaisuussivu">';
    $tyhjat  = [];
    foreach ( $luokat as $koodi ) {
        if ( $maarat[ $koodi ] === 0 ) {
            $tyhjat[] = $sanakirja[ $koodi ];
            continue;
        }
        $html .= kasvisto_sc_kasvisto_uusi( [
            'ryhma'            => $ryhma,
            'uhanalaisuus'     => $sanakirja[ $koodi ],
            'otsikko'          => $sanakirja[ $koodi ],
            'loytopisteet'     => $a['loytopisteet'],
            'hakukentta'       => 'ei',
            'aakkosnavigaatio' => 'ei',
        ] );
    }
    if ( $tyhjat ) {
        $html .= '<p class="uhanalaisuussivu-ei-loytoja"><strong>Ei löytöjä Luopioisista:</strong> '
               . esc_html( implode( ', ', $tyhjat ) ) . '.</p>';
    }

    if ( $a['kokolista'] !== 'ei' && function_exists( 'kasvisto_sc_testiversio' ) ) {
        $html .= kasvisto_sc_testiversio( [
            'ryhma'        => $ryhma,
            'otsikko'      => $a['otsikko'] !== '' ? $a['otsikko'] : kasvisto_uhanalaisuus_ryhma_genetiivi( $ryhma ) . ' uhanalaisuus',
            'loytopisteet' => $a['loytopisteet'],
        ] );
    }

    return $html . '</div>';
}
