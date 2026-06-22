<?php
/**
 * Yksittäisen hakutuloksen pomminvarma rakenne 
 * (Kuva poimitaan ACF Kasvikuvagalleriasta, thumbnail-koko)
 */

$kuva_url = '';

// 1. HAETAAN KUVAGALLERIA ACF-KENTÄSTÄ (kasvikuvagalleria)
$galleria = get_field('kasvikuvagalleria', get_the_ID());

if ( !empty($galleria) && is_array($galleria) ) {
    // Poimitaan gallerian ensimmäinen kuva
    $ensimmainen_kuva = $galleria[0];
    
    // Tarkistetaan löytyykö kuvasta 'thumbnail'-koko, muuten käytetään pääosoitetta
    if ( isset($ensimmainen_kuva['sizes']['thumbnail']) ) {
        $kuva_url = $ensimmainen_kuva['sizes']['thumbnail'];
    } else {
        $kuva_url = $ensimmainen_kuva['url'];
    }
}

// 2. VARAJÄRJESTELMÄ: Jos galleriasta ei löytynyt kuvaa, kokeillaan WP:n omaa artikkelikuvaa
if ( !$kuva_url && has_post_thumbnail() ) {
    $kuva_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
}


// 3. TEKSTIN PUHDISTUS JA FIKSU POIMINTA SIVUN ALUSTA
// Otetaan sivun pääsisältö
$raaka_sisalto = get_the_content();

// Perataan shortcodet ja HTML-sotkut pois
$puhdas_teksti = strip_shortcodes($raaka_sisalto);
$puhdas_teksti = wp_strip_all_tags($puhdas_teksti);

// Siistitään ylimääräiset rivinvaihdot ja välilyönnit yhdeksi pötköksi
$puhdas_teksti = preg_replace('/\s+/', ' ', $puhdas_teksti);
$puhdas_teksti = trim($puhdas_teksti);

// Katkaistaan siisti 180 merkin tekstitunniste ilman koodia tai HTML:ää
$tekstikatkelma = '';
if ( !empty($puhdas_teksti) ) {
    $tekstikatkelma = wp_html_excerpt($puhdas_teksti, 180, '...');
}
?>

<div class="search-result-item">

    <?php if ( $kuva_url ) : ?>
        <div class="result-thumb-wrapper">
            <a href="<?php the_permalink(); ?>">
                <img src="<?php echo esc_url($kuva_url); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
            </a>
        </div>
    <?php endif; ?>

    <div class="result-content-wrapper">
        <span class="result-meta">
            <?php 
                $nykyinen_post_type = get_post_type();
                $naytettava_ryhma = '';

                // JOS kyseessä on kasvikortti, yritetään hakea ryhmä ACF-kentästä
                if ( $nykyinen_post_type === 'kasvi' ) {
                    $acf_ryhma = get_field('ryhma', get_the_ID());
                    
                    if ( $acf_ryhma ) {
                        // Jos valintalista palauttaa taulukon (esim. label/value), poimitaan label. Muuten raaka teksti.
                        $naytettava_ryhma = is_array($acf_ryhma) ? $acf_ryhma['label'] : $acf_ryhma;
                    }
                }

                // Jos ryhmää ei löytynyt (tai kyseessä on sivu), käytetään oletusnimeä
                if ( empty($naytettava_ryhma) ) {
                    $post_type_obj = get_post_type_object( $nykyinen_post_type );
                    $naytettava_ryhma = $post_type_obj->labels->singular_name;
                }

                echo esc_html( ucfirst($naytettava_ryhma) ); 
            ?>
        </span>
        
        <h2 class="result-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            <?php 
            $tieteellinen = get_field('tieteellinen_nimi');
            if ( $tieteellinen ) : ?>
                <span class="result-scientific">(<?php echo esc_html($tieteellinen); ?>)</span>
            <?php endif; ?>
        </h2>

        <?php if ( $tekstikatkelma ) : ?>
            <div class="result-excerpt">
                <?php echo esc_html($tekstikatkelma); ?>
            </div>
        <?php endif; ?>
    </div>

</div>
