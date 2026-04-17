<?php get_header(); ?>

<main class="kasvi-clean-layout">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        
        <header class="kasvi-clean-header">

        <!-- murupolku -->
<!-- <?php 
$ryhma = get_field('ryhma');
if ($ryhma) : 
    // Luodaan linkki ryhmän nimellä. 
    // Oletetaan että sinulla on sivu nimeltään "Sammalet", jonka slug on "sammalet"
    $ryhma_slug = sanitize_title($ryhma); 
    $ryhma_url = home_url('/' . $ryhma_slug . '/');
?>
    <nav class="breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>">Etusivu</a> &raquo; 
        <a href="<?php echo esc_url($ryhma_url); ?>"><?php echo esc_html($ryhma); ?></a>
    </nav>
<?php endif; ?> --> 

<!-- paluulinkki -->

<div class="back-link-container" style="margin-bottom: 20px;">
    <a href="javascript:history.back()" class="back-link">
        << Takaisin listaukseen
    </a>
</div>



        <h1><?php the_title(); ?></h1>
            <?php $tieteellinen = get_field('tieteellinen_nimi'); ?>
            <?php if($tieteellinen): ?>
                <div class="latina-sub"><i><?php echo esc_html($tieteellinen); ?></i></div>
            <?php endif; ?>

 <div class="badge-row" style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
        <?php // Pääryhmä (Sammalet) ?>
        <?php $ryhma = get_field('ryhma'); if($ryhma): ?>
            <span class="badge-ryhma"><?php echo esc_html($ryhma); ?></span>
        <?php endif; ?>

        <?php 
        // Sammalryhmä (Lehtisammalet jne.)
        $s_obj = get_field_object('sammalryhma');
        $s_val = get_field('sammalryhma');
        
        if($s_val): 
            $val = is_array($s_val) ? $s_val[0] : $s_val;
            $label = $s_obj['choices'][$val] ?? $val;
            ?>
            <span class="badge-ryhma badge-sub"><?php echo esc_html($label); ?></span>
        <?php endif; ?>
    </div>





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
            <!--    
                <div class="media-group">
                    <h3>Kuvat</h3>
                    <div class="clean-gallery">
                        <?php 
                        for ($i = 1; $i <= 9; $i++) {
                            $url = get_field("kuva_{$i}_url");
                            if ($url) {
                                echo '<a href="' . esc_url($url) . '" class="glightbox" data-glightbox="title: ' . esc_attr(get_the_title()) . '">';
                                echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(get_the_title()) . '" >';
                                echo '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>
        -->

        <!--
<div class="media-group">
    <h3>Kuvat</h3>
    <div class="clean-gallery">
        <?php 
        // 1. VANHAT URL-KENTÄT (Säilytetään tuki)
        for ($i = 1; $i <= 10; $i++) {
            $url = get_field("kuva_{$i}_url");
            if ($url) {
                echo '<a href="' . esc_url($url) . '" class="glightbox" data-glightbox="title: ' . esc_attr(get_the_title()) . '">';
                echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(get_the_title()) . '">';
                echo '</a>';
            }
        }

        // 2. POIMITAAN KUVAT WYSIWYG-EDITORISTA
        $editori_sisalto = get_field('galleria_editori');

        if ($editori_sisalto) {
            /**
             * Parannettu regex:
             * - i: case-insensitive
             * - U: ungreedy (ei hotki liikaa kerralla)
             * - s: käsittelee koko sisällön yhtenä merkkijonona (vaikka olisi rivinvaihtoja)
             */
            $pattern = '/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i';
            
            if (preg_match_all($pattern, $editori_sisalto, $matches)) {
                foreach ($matches[1] as $img_url) {
                    
                    // Putsataan URL mahdollisista HTML-entiteeteistä (kuten &amp;)
                    $img_url = htmlspecialchars_decode($img_url);

                    // Haetaan alkuperäinen kuva (poistetaan koon lisäys, esim -300x200)
                    // Tämä kattaa myös S3:n mahdolliset variaatiot paremmin
                    $full_url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|webp))/i', '', $img_url);
                    
                    ?>
                    <a href="<?php echo esc_url($full_url); ?>" 
                       class="glightbox" 
                       data-glightbox="title: <?php echo esc_attr(get_the_title()); ?>">
                        <img src="<?php echo esc_url($img_url); ?>" 
                             alt="<?php echo esc_attr(get_the_title()); ?>" 
                             loading="lazy">
                    </a>
                    <?php
                }
            }
        }
        ?>
    </div>
</div>
        -->
<div class="media-group">
    <h3>Kuvat</h3>
    <div class="clean-gallery">
        <?php 
        // 1. VANHAT URL-KENTÄT
        for ($i = 1; $i <= 10; $i++) {
            $url = get_field("kuva_{$i}_url");
            if ($url) {
                echo '<a href="' . esc_url($url) . '" class="glightbox" data-glightbox="title: ' . esc_attr(get_the_title()) . '">';
                echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(get_the_title()) . '">';
                echo '</a>';
            }
        }

        // 2. POIMITAAN KUVAT WYSIWYG-EDITORISTA
        $editori_sisalto = get_field('galleria_editori');

        if ($editori_sisalto) {
            $pattern = '/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i';
            
            if (preg_match_all($pattern, $editori_sisalto, $matches)) {
                foreach ($matches[1] as $img_url) {
                    $img_url = htmlspecialchars_decode($img_url);
                    $full_url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif|webp))/i', '', $img_url);
                    
                    // Oletusarvot
                    $display_title = get_the_title();
                    $caption = '';

                    // Yritetään hakea kuvan ID URL-osoitteen perusteella
                    $attachment_id = attachment_url_to_postid($full_url);

                    if ($attachment_id) {
                        // Haetaan kuvan oma otsikko mediakirjastosta
                        $media_title = get_the_title($attachment_id);
                        // Haetaan kuvateksti (caption / excerpt)
                        $media_caption = wp_get_attachment_caption($attachment_id);

                        // Jos kuvalla on muu kuin tiedostonimeltä näyttävä otsikko, käytetään sitä
                        if ($media_title && !preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $media_title)) {
                            $display_title = $media_title;
                        }
                        
                        if ($media_caption) {
                            $caption = $media_caption;
                        }
                    }

                    // Rakennetaan GLightboxin kuvausmääre
                    // title = ylärivi, description = alarivi (kuvateksti)
                    $lightbox_meta = 'title: ' . esc_attr($display_title) . ';';
                    if ($caption) {
                        $lightbox_meta .= ' description: ' . esc_attr($caption) . ';';
                    }
                    ?>
                    <a href="<?php echo esc_url($full_url); ?>" 
                       class="glightbox" 
                       data-glightbox="<?php echo $lightbox_meta; ?>">
                        <img src="<?php echo esc_url($img_url); ?>" 
                             alt="<?php echo esc_attr($display_title); ?>" 
                             loading="lazy">
                    </a>
                    <?php
                }
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lightbox = GLightbox({
        selector: '.glightbox',
        loop: true, // Tämä mahdollistaa jatkuvan selaamisen
        openEffect: 'zoom',
        closeEffect: 'fade' 
    });
    });

</script>

<?php get_footer(); ?>