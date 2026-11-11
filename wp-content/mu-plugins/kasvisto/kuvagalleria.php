<?php
/**
 * Shortcode [kuvagalleria] – kuvat kasvikuvataksonomian kategoriasta.
 *
 * Siirretty Code Snippets -snippetistä #25 (vienti 10.11.2026).
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rekisteröidään [kuvagalleria] lyhytkoodi nuolinäppäin-navigoinnilla.
 * Käyttöesimerkki: [kuvagalleria kategoria="talvi" maara="8"]
 */
function kasvisto_sc_kuvagalleria( $atts ) {
    
    $parametrit = shortcode_atts( array(
        'kategoria' => '',   
        'maara'     => 12,   
    ), $atts, 'kuvagalleria' );

    if ( empty( $parametrit['kategoria'] ) ) {
        return '<p style="color:red;"><strong>Virhe:</strong> Lyhytkoodista puuttuu kategoria. Käytä muodossa: <code>[kuvagalleria kategoria="talvi"]</code></p>';
    }

    ob_start();

    $args = array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'post_mime_type' => 'image',
        'posts_per_page' => intval( $parametrit['maara'] ),
        'no_found_rows'  => true, 
        'tax_query'      => array(
            array(
                'taxonomy' => 'kasvikuvataksonomia', 
                'field'    => 'slug',
                'terms'    => sanitize_title( $parametrit['kategoria'] ),
            ),
        ),
    );

    $kuva_query = new WP_Query( $args );

    if ( $kuva_query->have_posts() ) :
        
        // CSS-tyylit
        echo '<style>
            .kuvamatriisi {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 16px;
                margin: 20px 0;
            }
            .kuvamatriisi-item {
                aspect-ratio: 1 / 1;
                overflow: hidden;
                border-radius: 6px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .kuvamatriisi-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .kuvamatriisi-item a {
                display: block;
                width: 100%;
                height: 100%;
            }
            .kuvamatriisi-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            .lightbox-dialog {
                border: none;
                border-radius: 8px;
                padding: 20px;
                max-width: 90vw;
                max-height: 90vh;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            }
            .lightbox-dialog::backdrop {
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: blur(3px);
            }
            .lightbox-close-btn {
                background: #f1f1f1;
                border: none;
                padding: 6px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-weight: bold;
                float: right;
                margin-bottom: 10px;
            }
            .lightbox-close-btn:hover {
                background: #ddd;
            }
        </style>';

        echo '<div class="kuvamatriisi">';
        
        // Luodaan taulukko kaikille ID:ille, jotta JavaScript osaa liikkua niiden välillä
        $dialog_ids = array();
        
        while ( $kuva_query->have_posts() ) : $kuva_query->the_post();
            $id        = get_the_ID();
            $dialog_ids[] = 'lightbox-' . $id; // Tallennetaan ID listaan
            $full_url  = wp_get_attachment_image_url( $id, 'full' );
            $alt       = get_post_meta( $id, '_wp_attachment_image_alt', true );

            echo '<div class="kuvamatriisi-item">';
                echo '<a href="#" onclick="event.preventDefault(); document.getElementById(\'lightbox-' . $id . '\').showModal();">';
                    echo wp_get_attachment_image( $id, 'small' );
                echo '</a>';
            echo '</div>';

            // Lisätty onclick-määrite, joka sulkee ikkunan jos klikkaa mustaa taustaa
            echo '<dialog id="lightbox-' . $id . '" class="lightbox-dialog" onclick="if(event.target === this) this.close();">';
                echo '<button onclick="document.getElementById(\'lightbox-' . $id . '\').close();" class="lightbox-close-btn">Sulje ✕</button>';
                echo '<div style="clear:both;"></div>';
                echo '<img src="' . esc_url( $full_url ) . '" alt="' . esc_attr( $alt ) . '" style="max-width:100%; height:auto; display:block; margin:0 auto; max-height:75vh; object-fit:contain;">';
                $kuvaus = function_exists( 'kasvisto_lightbox_kuvaus' ) ? kasvisto_lightbox_kuvaus( wp_get_attachment_caption( $id ), $id ) : '';
                if ( $kuvaus !== '' ) {
                    echo '<p class="kuva-lisenssi" style="text-align:center; margin:10px 0 0; font-size:0.85rem; color:#50575e;">' . $kuvaus . '</p>';
                }
            echo '</dialog>';

        endwhile;
        
        echo '</div>';
        
        // --- JAVASCRIPT NUOLINÄPPÄIMILLE ---
        // Viedään PHP-taulukko turvallisesti JavaScriptille json_encode-funktiolla
        echo '<script>
        (function() {
            const galleriaIkkunat = ' . json_encode($dialog_ids) . ';

            document.addEventListener("keydown", function(event) {
                // Etsitään onko jokin lightbox tällä hetkellä auki
                let aktiivinenId = null;
                for (let id of galleriaIkkunat) {
                    let dialog = document.getElementById(id);
                    if (dialog && dialog.open) {
                        aktiivinenId = id;
                        break;
                    }
                }

                // Jos mikään ikkuna ei ole auki, ei tehdä mitään
                if (!aktiivinenId) return;

                let nykyinenIndeksi = galleriaIkkunat.indexOf(aktiivinenId);

                // Nuoli oikealle -> Seuraava kuva
                if (event.key === "ArrowRight") {
                    event.preventDefault();
                    document.getElementById(aktiivinenId).close();
                    let seuraavaIndeksi = (nykyinenIndeksi + 1) % galleriaIkkunat.length;
                    document.getElementById(galleriaIkkunat[seuraavaIndeksi]).showModal();
                }
                // Nuoli vasemmalle -> Edellinen kuva
                else if (event.key === "ArrowLeft") {
                    event.preventDefault();
                    document.getElementById(aktiivinenId).close();
                    let edellinenIndeksi = (nykyinenIndeksi - 1 + galleriaIkkunat.length) % galleriaIkkunat.length;
                    document.getElementById(galleriaIkkunat[edellinenIndeksi]).showModal();
                }
            });
        })();
        </script>';
        
        wp_reset_postdata();
        
    else :
        echo '<p>Kuvia ei löytynyt kategoriasta: <strong>' . esc_html( $parametrit['kategoria'] ) . '</strong></p>';
    endif;

    return ob_get_clean();
}
