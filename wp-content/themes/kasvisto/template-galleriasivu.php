<?php
/**
 * Template Name: Moniosainen Galleriasivu
 * Description: Sivupohja useille päällekkäisille väliotsikoille ja kuvagallerioille.
 */

get_header(); ?>

<main id="primary" class="site-main galleriasivu-container" style="max-width: 1000px; margin: 0 auto; padding: 20px; box-sizing: border-box;">

    <?php while ( have_posts() ) : the_post(); ?>
        
        <header class="page-header" style="margin-bottom: 40px;">
            <h1 class="page-title"><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
                <div class="page-intro" style="font-size: 1.2rem; color: #555; margin-top: 10px;"><?php the_excerpt(); ?></div>
            <?php endif; ?>
        </header>

        <div class="page-content">
            <?php the_content(); // Normaali editorisisältö, jos sellaista on ?>
        </div>

        <?php if ( function_exists('have_rows') && have_rows('galleriasivun_sisalto') ) : ?>
            
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
            <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>

            <div class="joustava-sisalto-wrapper">
                <?php 
                $galleria_index = 1; // Juokseva numero eri gallerioille

                while ( have_rows('galleriasivun_sisalto') ) : the_row(); 
                    
                    // LOHKO 1: VÄLIOTSIKKO
                    if ( get_row_layout() == 'valiotsikko_lohko' ) : 
                        $otsikko = get_sub_field('teksti');
                        $kuvaus  = get_sub_field('lyhyt_kuvaus');
                        ?>
                        <section class="galleria-valiotsikko" style="margin-top: 50px; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            <h2 style="margin: 0 0 5px 0; color: #222; font-size: 1.8rem;"><?php echo esc_html($otsikko); ?></h2>
                            <?php if ($kuvaus) : ?>
                                <p style="margin: 0; color: #666; font-style: italic; font-size: 0.95rem;"><?php echo esc_html($kuvaus); ?></p>
                            <?php endif; ?>
                        </section>
                    <?php 
                    endif;

                    // LOHKO 2: KUVAGALLERIA
                    if ( get_row_layout() == 'kuvagalleria_lohko' ) : 
                        $kuvat = get_sub_field('kuvat');
                        if ( $kuvat ) : ?>
                            <section class="galleria-osio" style="margin-bottom: 40px;">
                                <div class="kasvi-galleria-grid">
                                    <?php foreach ( $kuvat as $kuva ) : 
                                        $pieni_kuva   = !empty($kuva['sizes']['medium_large']) ? $kuva['sizes']['medium_large'] : $kuva['sizes']['medium'];
                                        $iso_kuva     = $kuva['url'];
                                        $otsikko      = !empty($kuva['title']) ? $kuva['title'] : '';
                                        $kuvausteksti = !empty($kuva['caption']) ? $kuva['caption'] : '';
                                    ?>
                                        <a href="<?php echo esc_url($iso_kuva); ?>" 
                                           class="glightbox kasvi-galleria-item" 
                                           data-gallery="galleria-<?php echo $galleria_index; ?>" 
                                           data-title="<?php echo esc_attr($otsikko); ?>" 
                                           data-description="<?php echo esc_attr($kuvausteksti); ?>">
                                            
                                            <img src="<?php echo esc_url($pieni_kuva); ?>" alt="<?php echo esc_attr($kuva['alt']); ?>" loading="lazy">
                                            
                                            <div class="galleria-overlay"><span class="suurennus-ikoni">🔍</span></div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                            <?php 
                            $galleria_index++; // Kasvatetaan id-numeroa seuraavaa galleriaa varten
                        endif;
                    endif;
   // LOHKO 3: PDF-LISTAUS (LIGHTBOX ERIYTETTY + IFRAME PAKOTETTU)
                    if ( get_row_layout() == 'pdf_listaus_lohko' ) : 
                        $dokumentit = get_sub_field('dokumentit');
                        
                        if ( $dokumentit ) : ?>
                            <section class="pdf-lista-osio" style="margin-bottom: 40px;">
                                <ul class="pdf-latauslista">
                                    <?php foreach ( $dokumentit as $rivi ) : 
                                        $doc = $rivi['tiedosto']; 
                                        
                                        if ( empty($doc) || !is_array($doc) ) {
                                            continue;
                                        }

                                        $tiedosto_url = $doc['url'];
                                        $otsikko      = !empty($doc['title']) ? $doc['title'] : $doc['filename'];
                                        $kuvausteksti = !empty($doc['caption']) ? $doc['caption'] : '';
                                        
                                        // Tiedostokoon laskenta
                                        $koko_tavuina = isset($doc['filesize']) ? $doc['filesize'] : 0;
                                        $koko_teksti  = '';
                                        if ( $koko_tavuina > 0 ) {
                                            $koko_teksti  = ($koko_tavuina >= 1048576) ? ' (PDF, ' . round($koko_tavuina / 1048576, 1) . ' MB)' : ' (PDF, ' . round($koko_tavuina / 1024) . ' KB)';
                                        }
                                    ?>
                                        <li class="pdf-item">
                                            <div class="pdf-info">
                                                <span class="pdf-ikoni">📄</span>
                                                <div class="pdf-tekstit">
                                                    <h4 class="pdf-otsikko">
                                                        <a href="<?php echo esc_url($tiedosto_url); ?>" 
                                                           class="glightbox pdf-lightbox-link" 
                                                           data-gallery="pdf-galleria-<?php echo $galleria_index; ?>" 
                                                           data-type="iframe" 
                                                           data-title="<?php echo esc_attr($otsikko); ?>">
                                                                <?php echo esc_html($otsikko); ?>
                                                        </a>
                                                        <span class="pdf-koko"><?php echo $koko_teksti; ?></span>
                                                    </h4>
                                                    <?php if ($kuvausteksti) : ?>
                                                        <p class="pdf-kuvaus"><?php echo esc_html($kuvausteksti); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <a href="<?php echo esc_url($tiedosto_url); ?>" 
                                               class="pdf-suoralataus-nappi" 
                                               download="<?php echo esc_attr($doc['filename']); ?>" 
                                               title="Lataa tiedosto omalle koneelle">
                                                <span class="lataus-ikoni">📥</span> Lataa
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </section>
                        <?php 
                            // Kasvatetaan indeksiä myös täällä, jotta seuraavat lohkot pysyvät erillään
                            $galleria_index++; 
                        endif;
                    endif;
 
                endwhile; ?>
            </div>

            <style>
/* ==========================================================================
   1. KUVAGALLERIA (GRID) TYYLIT
   ========================================================================== */
.kasvi-galleria-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
    margin: 15px 0;
    width: 100%;
}
.kasvi-galleria-item {
    position: relative; 
    display: block; 
    aspect-ratio: 1 / 1;
    overflow: hidden; 
    border-radius: 6px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
    background-color: #f9f9f9;
}
.kasvi-galleria-item img {
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
    transition: transform 0.3s ease;
}
.kasvi-galleria-item:hover img { 
    transform: scale(1.05); 
}
.galleria-overlay {
    position: absolute; 
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.25); 
    display: flex; 
    align-items: center; 
    justify-content: center;
    opacity: 0; 
    transition: opacity 0.3s ease;
}
.kasvi-galleria-item:hover .galleria-overlay { 
    opacity: 1; 
}
.suurennus-ikoni { 
    color: #fff; 
    font-size: 20px; 
    text-shadow: 0 1px 3px rgba(0,0,0,0.3); 
}

/* ==========================================================================
   2. PDF-LISTAUKSEN TYYLIT
   ========================================================================== */
.pdf-latauslista {
    list-style: none;
    padding: 0;
    margin: 15px 0;
}
.pdf-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fdfdfd;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    padding: 15px 20px;
    margin-bottom: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    transition: all 0.2s ease;
}
.pdf-item:hover {
    border-color: #ccc;
    background: #fff;
}
.pdf-info {
    display: flex;
    align-items: center;
    gap: 15px;
}
.pdf-ikoni {
    font-size: 28px;
    line-height: 1;
}
.pdf-tekstit {
    display: flex;
    flex-direction: column;
}
.pdf-otsikko {
    margin: 0;
    font-size: 1.05rem;
    color: #333;
    font-weight: 600;
}

/* UUSI: Lightboxiin aukeava linkki otsikossa */
.pdf-otsikko a.pdf-lightbox-link {
    color: #2b6cb0;
    text-decoration: none;
    transition: color 0.2s ease;
}
.pdf-otsikko a.pdf-lightbox-link:hover {
    color: #1a4332; /* Kasvisivun tummanvihreä korostusväri */
    text-decoration: underline;
}

.pdf-koko {
    font-size: 0.8rem;
    color: #888;
    font-weight: normal;
    margin-left: 5px;
}
.pdf-kuvaus {
    margin: 4px 0 0 0;
    font-size: 0.88rem;
    color: #666;
}

/* UUSI: Suoralatauspainike oikeassa reunassa */
.pdf-suoralataus-nappi {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #edf2f7;
    color: #4a5568 !important;
    text-decoration: none !important;
    padding: 8px 14px;
    border-radius: 4px;
    font-size: 0.88rem;
    font-weight: 600;
    border: 1px solid #cbd5e0;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.pdf-suoralataus-nappi:hover {
    background: #e2e8f0;
    color: #2d3748 !important;
    border-color: #a0aec0;
}
.lataus-ikoni {
    font-size: 16px;
}

/* ==========================================================================
   3. GLIGHTBOX IFRAME OPTIMOINTI (PDF-KOKO)
   ========================================================================== */
.gslide-inline, .gslide-iframe {
    width: 90vw !important;
    height: 85vh !important;
    max-width: 1200px !important;
}

/* ==========================================================================
   4. MOBIILIRESPONSIIVISUUS (MEDIA QUERIES)
   ========================================================================== */
/* Tabletit ja puhelimet (Alle 600px laitteet) */
@media (max-width: 600px) {
    .pdf-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    .pdf-suoralataus-nappi {
        width: 100%;
        justify-content: center;
    }
}

/* Pienet puhelimet (Alle 480px laitteet) */
@media (max-width: 480px) {
    .kasvi-galleria-grid { 
        grid-template-columns: repeat(3, 1fr); 
        gap: 6px; 
    }
}
</style>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    if (typeof GLightbox !== 'undefined') {
                        GLightbox({
                            selector: '.glightbox',
                            touchNavigation: true,
                            loop: true,
                            // Pakotetaan iframe-ikkunalle hyvät oletuskoot PDF-käyttöä varten:
                            iframeWidth: '90%',
                            iframeHeight: '85vh'
                        });
                    }
                });
            </script>

        <?php endif; ?>

    <?php endwhile; ?>

</main>

<?php get_footer(); ?>