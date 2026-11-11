<?php
/**
 * Kuvien Creative Commons -lisenssimerkintä (CC BY-NC-SA 4.0).
 *
 *  - Jokaiseen mediakirjaston kuvaan voi merkitä kuvaajan ja poikkeavan lisenssin
 *    (Media → kuva → kentät "Kuvaaja" ja "Lisenssi").
 *  - Suurennetun kuvan alle tulee kuvaaja ja lisenssi linkkeineen (pikkukuviin ei merkintää).
 *  - Sivujen ja artikkelien kuvalohkojen alle tulee sama merkintä.
 *  - Sivun <head>-osaan tulee koneluettava lisenssilinkki (rel="license").
 *
 * Lisenssimerkintä linkittää sivuston käyttöehtosivulle (sivustosta/kayttooikeudet),
 * jos sellainen on, muuten Creative Commonsin suomenkieliseen tiivistelmään.
 *
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const KASVISTO_LISENSSI_LYHYT = 'CC BY-NC-SA 4.0';
const KASVISTO_LISENSSI_NIMI  = 'Nimeä-EiKaupallinen-JaaSamoin 4.0 Kansainvälinen';
const KASVISTO_LISENSSI_URL   = 'https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fi';

/** Käyttöehtosivu, jolle merkinnät linkittävät (polku sivuston juuresta). */
const KASVISTO_KAYTTOEHTOSIVU = 'sivustosta/kayttooikeudet';

/**
 * Kuvaaja, jota käytetään, jos kuvalle ei ole merkitty kuvaajaa (käytännössä kaikki
 * sivuston kuvat ovat hänen). Poikkeukset merkitään kuvakohtaisesti mediakirjastossa.
 */
const KASVISTO_OLETUSKUVAAJA = 'Tuomo Kuitunen';

/**
 * Käyttöehtosivun osoite (tai CC:n tiivistelmä, jos sivua ei ole).
 */
function kasvisto_kayttoehtosivu_url() {
    static $url = null;
    if ( $url === null ) {
        $sivu = get_page_by_path( KASVISTO_KAYTTOEHTOSIVU );
        $url  = ( $sivu && $sivu->post_status === 'publish' ) ? get_permalink( $sivu ) : KASVISTO_LISENSSI_URL;
    }
    return $url;
}

/**
 * Kuvan lisenssitiedot.
 *
 * @param int|null $kuva_id mediakirjaston ID; null = kuva ilman mediakirjastoa (oletuslisenssi)
 * @return array{tyyppi:string, kuvaaja:string}  tyyppi: 'cc' | 'pidatetty' | 'ei'
 */
function kasvisto_kuvan_lisenssi( $kuva_id = null ) {
    $tyyppi  = 'cc';
    $kuvaaja = '';
    if ( $kuva_id ) {
        $tallennettu = get_post_meta( $kuva_id, '_kasvisto_lisenssi', true );
        if ( in_array( $tallennettu, [ 'cc', 'pidatetty', 'ei' ], true ) ) {
            $tyyppi = $tallennettu;
        }
        $kuvaaja = trim( (string) get_post_meta( $kuva_id, '_kasvisto_kuvaaja', true ) );
    }
    if ( $kuvaaja === '' ) {
        $kuvaaja = KASVISTO_OLETUSKUVAAJA;
    }
    return [ 'tyyppi' => $tyyppi, 'kuvaaja' => $kuvaaja ];
}

/**
 * Kuvaaja- ja lisenssirivi (HTML) suurennetun kuvan tai kuvalohkon alle.
 * Tyhjä, jos merkintää ei näytetä.
 */
function kasvisto_lisenssirivi_html( $kuva_id = null ) {
    $l = kasvisto_kuvan_lisenssi( $kuva_id );
    if ( $l['tyyppi'] === 'ei' ) {
        return '';
    }
    $tekija = $l['kuvaaja'] !== '' ? 'Kuva: ' . esc_html( $l['kuvaaja'] ) . ' / Luopioisten kasvisto' : 'Kuva: Luopioisten kasvisto';

    if ( $l['tyyppi'] === 'pidatetty' ) {
        // Muiden kuvat: vain kuvaajan nimi, ei sivuston nimeä.
        $tekija = $l['kuvaaja'] !== '' ? 'Kuva: ' . esc_html( $l['kuvaaja'] ) : 'Kuva';
        return '<span class="kuva-lisenssirivi">' . $tekija . ' · © Kaikki oikeudet pidätetään</span>';
    }
    return '<span class="kuva-lisenssirivi">' . $tekija
         . ' · <a href="' . esc_url( kasvisto_kayttoehtosivu_url() ) . '" rel="license" title="' . esc_attr( KASVISTO_LISENSSI_NIMI ) . '">'
         . esc_html( KASVISTO_LISENSSI_LYHYT ) . '</a></span>';
}

/**
 * GLightboxin kuvausteksti: kuvateksti + lisenssirivi (HTML, käytä esc_attr()).
 */
function kasvisto_lightbox_kuvaus( $kuvateksti, $kuva_id = null ) {
    $osat = [];
    if ( trim( (string) $kuvateksti ) !== '' ) {
        $osat[] = '<span class="kuva-kuvateksti">' . esc_html( $kuvateksti ) . '</span>';
    }
    $rivi = kasvisto_lisenssirivi_html( $kuva_id );
    if ( $rivi !== '' ) {
        $osat[] = $rivi;
    }
    return implode( '', $osat ); // osat ovat omia rivejään (display: block, ks. style.css)
}

// --- Mediakirjaston kentät: Kuvaaja ja Lisenssi -----------------------------------

add_filter( 'attachment_fields_to_edit', function ( $kentat, $kuva ) {
    if ( ! wp_attachment_is_image( $kuva->ID ) ) {
        return $kentat;
    }
    $kuvaaja = get_post_meta( $kuva->ID, '_kasvisto_kuvaaja', true );
    $tyyppi  = get_post_meta( $kuva->ID, '_kasvisto_lisenssi', true ) ?: 'cc';
    $nimi    = 'attachments[' . $kuva->ID . ']';

    $kentat['kasvisto_kuvaaja'] = [
        'label' => 'Kuvaaja',
        'input' => 'text',
        'value' => $kuvaaja,
        'helps' => 'Täytä vain, jos kuvaaja on joku muu kuin ' . KASVISTO_OLETUSKUVAAJA . '.',
    ];

    $vaihtoehdot = [
        'cc'        => 'CC BY-NC-SA 4.0 (oletus)',
        'pidatetty' => '© Kaikki oikeudet pidätetään (esim. muiden kuvat)',
        'ei'        => 'Ei merkintää (esim. kartat, logot)',
    ];
    $valikko = '<select name="' . esc_attr( $nimi ) . '[kasvisto_lisenssi]" id="attachments-' . (int) $kuva->ID . '-kasvisto_lisenssi">';
    foreach ( $vaihtoehdot as $arvo => $teksti ) {
        $valikko .= '<option value="' . esc_attr( $arvo ) . '"' . selected( $tyyppi, $arvo, false ) . '>' . esc_html( $teksti ) . '</option>';
    }
    $valikko .= '</select>';

    $kentat['kasvisto_lisenssi'] = [
        'label' => 'Lisenssi',
        'input' => 'html',
        'html'  => $valikko,
    ];
    return $kentat;
}, 10, 2 );

add_filter( 'attachment_fields_to_save', function ( $kuva, $tiedot ) {
    $id = (int) $kuva['ID'];
    if ( isset( $tiedot['kasvisto_kuvaaja'] ) ) {
        $kuvaaja = sanitize_text_field( wp_unslash( $tiedot['kasvisto_kuvaaja'] ) );
        $kuvaaja === '' ? delete_post_meta( $id, '_kasvisto_kuvaaja' ) : update_post_meta( $id, '_kasvisto_kuvaaja', $kuvaaja );
    }
    if ( isset( $tiedot['kasvisto_lisenssi'] ) ) {
        $tyyppi = in_array( $tiedot['kasvisto_lisenssi'], [ 'cc', 'pidatetty', 'ei' ], true ) ? $tiedot['kasvisto_lisenssi'] : 'cc';
        $tyyppi === 'cc' ? delete_post_meta( $id, '_kasvisto_lisenssi' ) : update_post_meta( $id, '_kasvisto_lisenssi', $tyyppi );
    }
    return $kuva;
}, 10, 2 );

// --- Sivujen ja artikkelien kuvalohkot ------------------------------------------

add_filter( 'render_block', function ( $html, $lohko ) {
    if ( ( $lohko['blockName'] ?? '' ) !== 'core/image' || is_admin() ) {
        return $html;
    }
    $id   = ! empty( $lohko['attrs']['id'] ) ? (int) $lohko['attrs']['id'] : 0;
    $rivi = $id ? kasvisto_lisenssirivi_html( $id ) : '';
    if ( $rivi === '' || stripos( $html, '</figure>' ) === false ) {
        return $html;
    }
    $rivi = '<span class="kuva-lisenssi kuvalohko-lisenssi">' . $rivi . '</span>';
    // Kuvatekstin perään, tai kuvan alle, jos kuvatekstiä ei ole.
    if ( stripos( $html, '</figcaption>' ) !== false ) {
        return preg_replace( '#</figcaption>#i', $rivi . '</figcaption>', $html, 1 );
    }
    return preg_replace( '#</figure>(?!.*</figure>)#is', '<figcaption class="wp-element-caption">' . $rivi . '</figcaption></figure>', $html, 1 );
}, 10, 2 );

// --- Koneluettava lisenssi ------------------------------------------------------

add_action( 'wp_head', function () {
    echo '<link rel="license" href="' . esc_url( KASVISTO_LISENSSI_URL ) . '">' . "\n";
} );
