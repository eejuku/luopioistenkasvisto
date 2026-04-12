<?php get_header(); ?>

<main class="kasvi-clean-layout">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        
        <header class="kasvi-clean-header">
            <h1><?php the_title(); ?></h1>
            <?php $tieteellinen = get_field('tieteellinen_nimi'); ?>
            <?php if($tieteellinen): ?>
                <div class="latina-sub"><i><?php echo esc_html($tieteellinen); ?></i></div>
            <?php endif; ?>

                <?php $ryhma = get_field('ryhma'); if($ryhma): ?>
                   <br/>  <span class="badge-ryhma"><?php echo esc_html($ryhma); ?></span>
                <?php endif; ?>
        </header>

        <div class="kasvi-flex-grid">
            
            <div class="kasvi-text-content">
                
                <?php if(get_the_content()): ?>
                    <div class="content-section">
                        <h3>Yleiskuvaus</h3>
                        <?php the_content(); ?>
                    </div>
                <?php endif; ?>

                <?php 
                // Listaus kentistä: Otsikko => ACF-slug
                $fields = [
                    'Uhanalaisuus' => 'uhanalaisuus',
                    'Koko' => 'koko',
                    'Kasvupaikka' => 'kasvupaikka',
                    'Levinneisyys' => 'levinneisyys',
                    'Maastotuntomerkit' => 'maastotuntomerkit',
                    'Kemia' => 'kemia',
                    'Vertaa' => 'vertaa',
                    'Löytöpaikat Luopioisissa' => 'loytopaikat',
                    'Kirjallisuus' => 'kirjallisuus',
                    'Mikrosienet' => 'mikrosienet',
                    'Muuta' => 'muuta',
                    'Löydettyjä isäntäkasveja' => 'isantakasvit',
                    'Isäntäkasvin muita piensieniä' => 'isantakasvin_muut'         
                ];

foreach ($fields as $label => $slug): 
    $value = get_field($slug);
    
    if ($value): ?>
        <div class="content-section">
            <h3><?php echo esc_html($label); ?></h3>
            <div class="field-value">
                <?php 
                if (is_array($value)) {
                    // Jos ACF palauttaa useita valintoja (esim. [label, value])
                    // tai useita rivejä, yhdistetään ne pilkulla
                    $display_value = isset($value['label']) ? $value['label'] : implode(', ', $value);
                    echo wpautop(esc_html($display_value));
                } else {
                    // Normaali tekstikenttä
                    echo wpautop($value);
                }
                ?>
            </div>
        </div>
    <?php endif; 
endforeach; ?>
<!--
<div class="content-section metadata-section" style="margin-top: 40px; opacity: 0.7; font-size: 0.9em;">
    <p>
        <strong>Kortti luotu:</strong> <?php echo get_the_date(); ?><br>
        <strong>Viimeksi päivitetty:</strong> <?php echo get_the_modified_date(); ?> klo <?php echo get_the_modified_time(); ?>
    </p>
</div>
-->
<div class="content-section">
    <small>
        Kortti luotu: <?php the_date(); ?>.
        <?php if (get_the_modified_time() != get_the_time()) : ?>
            Päivitetty viimeksi: <?php the_modified_date(); ?>.
        <?php endif; ?>
    </small>
</div>
            </div>

            <div class="kasvi-media-sidebar">
                
                <div class="media-group">
                    <h3>Kuvat</h3>
                    <div class="clean-gallery">
                        <?php 
                        for ($i = 1; $i <= 10; $i++) {
                            $url = get_field("kuva_{$i}_url");
                            if ($url) {
                                echo '<a href="' . esc_url($url) . '" class="glightbox" data-glightbox="title: ' . esc_attr(get_the_title()) . '">';
                                echo '<img src="' . esc_url($url) . '" alt="">';
                                echo '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <?php $karttakuva = get_field('karttakuva'); ?>
                <?php if($karttakuva): ?>
                      
                    <div class="media-group">
                        <h3>Kartta</h3>
                        <div class="static-map-img">
                            <img src="<?php echo esc_url($karttakuva['url'] ?? $karttakuva); ?>" alt="Levinneisyyskartta">
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    <?php endwhile; endif; ?>
</main>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lightbox = GLightbox({ selector: '.glightbox' });
    });

</script>

<?php get_footer(); ?>