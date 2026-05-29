<?php
/**
 * Yksittäisen hakutuloksen pomminvarma rakenne 
 * (Kuva poimitaan WYSIWYG-editorista ja teksti puhdistetaan)
 */

$kuva_url = '';

// 1. HAETAAN SISÄLTÖ WYSIWYG-EDITORISTA (galleria_editori)
$editori_sisalto = get_field('galleria_editori', get_the_ID());

if ( $editori_sisalto ) {
    // Regex-kaava, jolla etsitään kaikki img-tagit ja niiden src-osoitteet
    $pattern = '/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i';
    
    if ( preg_match($pattern, $editori_sisalto, $matches) ) {
        // preg_match poimii vain ensimmäisen osuman, joka löytyy osoitteesta $matches[1]
        $img_url = htmlspecialchars_decode($matches[1]);
        
        // Puhdistetaan mahdolliset WordPressin kokomerkinnät (esim. -150x150.jpg) pois, jos halutaan alkuperäinen kuva
        // Tai voit käyttää suoraan $img_url, jolloin käytetään sitä kokoa mikä editorissa oli
        $kuva_url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|webp))/i', '', $img_url);
    }
}

// 2. VARAJÄRJESTELMÄ: Jos editorista ei löytynyt kuvaa, kokeillaan WP:n omaa artikkelikuvaa
if ( !$kuva_url && has_post_thumbnail() ) {
    $kuva_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
}


// 3. TEKSTIN PUHDISTUS JA FIKSU POIMINTA SIVUN ALUSTA
// Otetaan sivun pääsisältö
$raaka_sisalto = get_the_content();

// Jos pääsisältö on tyhjä (esim. kaikki teksti onkin ACF-kentissä), 
// voidaan käyttää myös editorin tekstiä varana ilman HTML-taggilaatikkoja
if ( empty(trim($raaka_sisalto)) && $editori_sisalto ) {
    $raaka_sisalto = $editori_sisalto;
}

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
                $post_type = get_post_type_object( get_post_type() );
                echo esc_html( $post_type->labels->singular_name ); 
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


<!-- <article class="search-result-item">
    <span class="result-meta">
        <?php echo (get_post_type() === 'kasvi') ? 'Kasvikortti' : 'Sivusto'; ?>
    </span>
    
    <h2 class="result-title">
        <a href="<?php the_permalink(); ?>">
            <?php the_title(); ?>
            <?php if ( get_post_type() === 'kasvi' ) : ?>
                <span class="result-scientific">
                    <?php echo get_post_meta(get_the_ID(), 'tieteellinen_nimi', true); ?>
                </span>
            <?php endif; ?>
        </a>
    </h2>

    <?php if ( get_post_type() !== 'kasvi' ) : ?>
        <div class="result-excerpt" style="font-size: 13px; color: #666;">
            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
        </div>
    <?php endif; ?>
</article> -->