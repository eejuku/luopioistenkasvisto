<?php
/**
 * Kuvien sijaintitietojen (GPS) poisto.
 *
 * Miksi: kuvaan tallentuneet koordinaatit voivat paljastaa esimerkiksi uhanalaisen
 * lajin kasvupaikan. Inventaariossa 11/2026 sijainti löytyi 146 alkuperäisestä kuvasta
 * (lähinnä puhelimet ja TG-7).
 *
 * Mitä poistetaan: vain sijainti — EXIFin GPS-osio ja XMP:n exif:GPS*-kentät.
 * Kuvaaja, tekijänoikeus, kamera, kuvausaika ja kuvan asento (orientation) säilyvät.
 * Kuvadataa ei pakata uudelleen: tiedosto pysyy tavulleen saman kokoisena, vain
 * sijaintitavut nollataan. Laatu ei siis heikkene.
 *
 * Käyttö:
 *  - Uudet lataukset käsitellään automaattisesti ennen kuin WordPress tekee pienennökset
 *    ja WP Offload Media siirtää tiedoston S3:een (suodatin wp_handle_upload).
 *  - Palvelimella jo olevat kuvat: WP-CLI
 *        wp kasvisto poista-sijainnit            (kuivaharjoitus: vain listaa)
 *        wp kasvisto poista-sijainnit --kirjoita (muuttaa tiedostot)
 *  - S3:ssa olevat kuvat: tyokalut/s3-poista-sijainnit.py
 *
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Poistaa sijaintitiedot JPEG-datasta.
 *
 * @param string $data   JPEG-tiedoston sisältö
 * @param array  $loydot täytetään: ['exif' => bool, 'xmp' => bool]
 * @return string|null   muutettu data, tai null jos sijaintia ei ollut (tai ei JPEG)
 */
function kasvisto_poista_sijainti_jpegista( $data, &$loydot = [] ) {
    $loydot = [ 'exif' => false, 'xmp' => false ];
    $pituus = strlen( $data );
    if ( $pituus < 4 || substr( $data, 0, 2 ) !== "\xFF\xD8" ) {
        return null;
    }

    $o = 2;
    while ( $o + 4 <= $pituus ) {
        if ( $data[ $o ] !== "\xFF" ) {
            break;
        }
        $merkki = ord( $data[ $o + 1 ] );
        if ( $merkki === 0xFF ) { // täytetavu
            $o++;
            continue;
        }
        if ( $merkki === 0xD8 || $merkki === 0x01 || ( $merkki >= 0xD0 && $merkki <= 0xD7 ) ) {
            $o += 2;
            continue;
        }
        if ( $merkki === 0xDA || $merkki === 0xD9 ) { // kuvadata alkaa / loppu
            break;
        }
        $seg_pituus = unpack( 'n', substr( $data, $o + 2, 2 ) )[1];
        $alku       = $o + 4;              // segmentin sisältö
        $loppu      = $o + 2 + $seg_pituus; // ensimmäinen tavu segmentin jälkeen
        if ( $seg_pituus < 2 || $loppu > $pituus ) {
            break;
        }

        if ( $merkki === 0xE1 ) {
            if ( substr( $data, $alku, 6 ) === "Exif\0\0" ) {
                if ( kasvisto_nollaa_exif_gps( $data, $alku + 6, $loppu ) ) {
                    $loydot['exif'] = true;
                }
            } elseif ( strncmp( substr( $data, $alku, 29 ), 'http://ns.adobe.com/xap/1.0/', 28 ) === 0 ) {
                $xmp  = substr( $data, $alku, $loppu - $alku );
                $uusi = kasvisto_tyhjenna_xmp_gps( $xmp );
                if ( $uusi !== $xmp ) {
                    $data          = substr_replace( $data, $uusi, $alku, strlen( $uusi ) );
                    $loydot['xmp'] = true;
                }
            }
        }
        $o = $loppu;
    }

    return ( $loydot['exif'] || $loydot['xmp'] ) ? $data : null;
}

/**
 * Nollaa EXIFin GPS-osion paikallaan. $t = TIFF-otsakkeen alku, $raja = APP1-segmentin loppu.
 * Osoitin GPS-osioon jää, mutta osio on tyhjä (0 kenttää) — se on sallittu rakenne.
 */
function kasvisto_nollaa_exif_gps( &$data, $t, $raja ) {
    $jarj = substr( $data, $t, 2 );
    if ( $jarj !== 'II' && $jarj !== 'MM' ) {
        return false;
    }
    $le  = ( $jarj === 'II' );
    $u16 = function ( $p ) use ( &$data, $t, $le ) {
        return unpack( $le ? 'v' : 'n', substr( $data, $t + $p, 2 ) )[1];
    };
    $u32 = function ( $p ) use ( &$data, $t, $le ) {
        return unpack( $le ? 'V' : 'N', substr( $data, $t + $p, 4 ) )[1];
    };
    $sisalla = function ( $p, $n ) use ( $t, $raja ) {
        return $p >= 0 && $n >= 0 && $t + $p + $n <= $raja;
    };

    $ifd0 = $u32( 4 );
    if ( ! $sisalla( $ifd0, 2 ) ) {
        return false;
    }
    $n   = $u16( $ifd0 );
    $gps = 0;
    for ( $i = 0; $i < $n; $i++ ) {
        $e = $ifd0 + 2 + $i * 12;
        if ( ! $sisalla( $e, 12 ) ) {
            return false;
        }
        if ( $u16( $e ) === 0x8825 ) {
            $gps = $u32( $e + 8 );
            break;
        }
    }
    if ( ! $gps || ! $sisalla( $gps, 2 ) ) {
        return false;
    }
    $kentat = $u16( $gps );
    if ( $kentat === 0 || ! $sisalla( $gps, 2 + $kentat * 12 ) ) {
        return false;
    }

    // Tyyppien koot (TIFF): BYTE, ASCII, SHORT, LONG, RATIONAL, SBYTE, UNDEFINED, SSHORT, SLONG, SRATIONAL, FLOAT, DOUBLE
    $koot = [ 1 => 1, 2 => 1, 3 => 2, 4 => 4, 5 => 8, 6 => 1, 7 => 1, 8 => 2, 9 => 4, 10 => 8, 11 => 4, 12 => 8 ];
    for ( $i = 0; $i < $kentat; $i++ ) {
        $e      = $gps + 2 + $i * 12;
        $tyyppi = $u16( $e + 2 );
        $koko  = ( $koot[ $tyyppi ] ?? 1 ) * $u32( $e + 4 );
        if ( $koko > 4 ) { // arvo on erillään, osoitin kentässä
            $arvo = $u32( $e + 8 );
            if ( $sisalla( $arvo, $koko ) ) {
                $data = substr_replace( $data, str_repeat( "\0", $koko ), $t + $arvo, $koko );
            }
        }
    }
    // Kentät ja seuraavan osion osoitin nollaan → osio on tyhjä.
    $data = substr_replace( $data, str_repeat( "\0", 2 + $kentat * 12 ), $t + $gps, 2 + $kentat * 12 );
    if ( $sisalla( $gps + 2 + $kentat * 12, 4 ) ) {
        $data = substr_replace( $data, "\0\0\0\0", $t + $gps + 2 + $kentat * 12, 4 );
    }
    return true;
}

/**
 * Korvaa XMP:n GPS-kentät välilyönneillä. Pituus säilyy, joten segmentin koko ei muutu,
 * ja XML pysyy kelvollisena (välilyönti on sallittu kenttien välissä).
 */
function kasvisto_tyhjenna_xmp_gps( $xmp ) {
    $tyhjaksi = function ( $m ) {
        return str_repeat( ' ', strlen( $m[0] ) );
    };
    // Elementtimuoto: <exif:GPSLatitude>…</exif:GPSLatitude>
    $xmp = preg_replace_callback( '#<(exif(?:EX)?:GPS\w+)\b[^>]*?(?:/>|>.*?</\1\s*>)#s', $tyhjaksi, $xmp );
    // Attribuuttimuoto: exif:GPSLatitude="…"
    $xmp = preg_replace_callback( '#(?<=\s)exif(?:EX)?:GPS\w+\s*=\s*(?:"[^"]*"|\'[^\']*\')#', $tyhjaksi, $xmp );
    return $xmp;
}

/**
 * Käsittelee tiedoston levyllä. Palauttaa löydöt, tai null jos sijaintia ei ollut.
 */
function kasvisto_poista_sijainti_tiedostosta( $polku, $kirjoita = true ) {
    if ( ! is_readable( $polku ) || ! preg_match( '/\.jpe?g$/i', $polku ) ) {
        return null;
    }
    $data = file_get_contents( $polku );
    if ( $data === false ) {
        return null;
    }
    $uusi = kasvisto_poista_sijainti_jpegista( $data, $loydot );
    if ( $uusi === null ) {
        return null;
    }
    if ( $kirjoita && file_put_contents( $polku, $uusi, LOCK_EX ) !== strlen( $uusi ) ) {
        error_log( '[kasvisto] sijainnin poisto epäonnistui: ' . $polku );
        return null;
    }
    return $loydot;
}

/**
 * Uudet lataukset: sijainti pois ennen pienennöksiä ja S3-siirtoa.
 */
add_filter( 'wp_handle_upload', function ( $tiedosto ) {
    if ( ! empty( $tiedosto['file'] ) && in_array( $tiedosto['type'] ?? '', [ 'image/jpeg', 'image/pjpeg' ], true ) ) {
        kasvisto_poista_sijainti_tiedostosta( $tiedosto['file'] );
    }
    return $tiedosto;
} );

/**
 * WP-CLI: wp kasvisto poista-sijainnit [--kirjoita]
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'kasvisto poista-sijainnit', function ( $args, $assoc ) {
        $kirjoita = ! empty( $assoc['kirjoita'] );
        $juuri    = wp_get_upload_dir()['basedir'];
        $iter     = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $juuri, FilesystemIterator::SKIP_DOTS ) );
        $kaikki   = 0;
        $loytyi   = 0;
        foreach ( $iter as $f ) {
            if ( ! $f->isFile() || ! preg_match( '/\.jpe?g$/i', $f->getFilename() ) ) {
                continue;
            }
            $kaikki++;
            $tulos = kasvisto_poista_sijainti_tiedostosta( $f->getPathname(), $kirjoita );
            if ( $tulos ) {
                $loytyi++;
                WP_CLI::log( substr( $f->getPathname(), strlen( $juuri ) + 1 ) . '  (' . implode( '+', array_keys( array_filter( $tulos ) ) ) . ')' );
            }
        }
        $teksti = "JPEG-kuvia $kaikki, sijainti $loytyi kuvassa.";
        if ( $kirjoita ) {
            WP_CLI::success( $teksti . ' Sijainnit poistettu.' );
        } else {
            WP_CLI::success( $teksti . ' Kuivaharjoitus: mitään ei muutettu. Aja uudelleen --kirjoita.' );
        }
    }, [ 'shortdesc' => 'Poistaa GPS-sijainnin palvelimella olevista JPEG-kuvista.' ] );
}
