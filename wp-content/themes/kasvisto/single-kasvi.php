<?php get_header(); ?>

<main class="kasvi-clean-layout">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        
        <header class="kasvi-clean-header">

        <!-- murupolku -->


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
                        <div class="field-value">
                            <?php the_content(); ?>
                        </div>
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
                    'Mikrosienet' => 'mikrosienet',
                    'Muuta' => 'muuta',
                    'Kemia' => 'kemia',
                    'Vertaa' => 'vertaa',
                    'Löytöpaikat Luopioisissa' => 'loytopaikat',
                    'Kirjallisuus' => 'kirjallisuus', 
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

<?php // 1. VANHA STAATTINEN KARTTA (jos olemassa) ?>
                <?php $karttakuva = get_field('karttakuva'); ?>
                <?php if($karttakuva): ?>
                    <div class="media-group">
                        <h3>Kartta (kuva)</h3>
                        <div class="static-map-img">
                            <img src="<?php echo esc_url($karttakuva['url'] ?? $karttakuva); ?>" alt="Levinneisyyskartta">
                        </div>
                    </div>
                <?php endif; ?>

<?php 
$pisteet_json = get_field('karttapisteet_json');
$uusi_karttapohja_url = get_template_directory_uri() . '/images/karttapohja.png'; 

if ($pisteet_json) : 
    $pisteet = json_decode($pisteet_json, true);
    if (!empty($pisteet)) : ?>
        
        <style>
            /* Kapselointi: Varmistetaan etteivät teeman tyylit vaikuta */
            .kasvikartta-wrapper {
                all: initial; /* Nollaa perityt tyylit */
                display: block;
                position: relative;
                width: 100%;
                max-width: 600px; /* Tai kartan todellinen leveys */
                margin: 20px 0;
                font-family: sans-serif;
            }
            .kasvikartta-container {
                position: relative;
                width: 100%;
                line-height: 0;
            }
            .kasvikartta-container img {
                width: 100%;
                height: auto;
                display: block;
                border: none;
            }
        </style>

<div class="media-group">
    <h3>Löytöpaikat kartalla</h3>
    <div class="kasvikartta-wrapper">
        <div class="kasvikartta-container" style="position: relative;">
            <img src="<?php echo $uusi_karttapohja_url; ?>" alt="Kartta" style="width: 100%; height: auto; display: block;">
            
            <?php 
            // 1. HAETAAN DATA
            $raw_data = get_field('karttapisteet_json');
            $data = is_string($raw_data) ? json_decode($raw_data, true) : $raw_data;

            // 2. TUNNISTETAAN RAKENNE (Vanhassa pelkkä taulukko, uudessa objekti)
            $pisteet = [];
            $pistekoko = 15; // Oletuskoko jos ei määritetty

            if (isset($data['points'])) {
                $pisteet = $data['points'];
                $pistekoko = isset($data['size']) ? intval($data['size']) : 15;
            } elseif (is_array($data)) {
                $pisteet = $data; // Vanha muoto
            }

            // 3. VAKIOARVOT (Samat kuin editorissa ja vanhassa koodissasi)
            $L = 42; $T = 7; $R = 535; $B = 500;
            
            // HUOM: Jos pohjakarttasi koko on eri, tarkista nämä:
            $img_w = 577; 
            $img_h = 516; 

            if (!empty($pisteet)) :
                foreach ($pisteet as $p) : 
                    // Lasketaan sijainti POHJA-alueen mukaan
                    $x_px = $L + (floatval($p['x']) * ($R - $L));
                    $y_px = $T + (floatval($p['y']) * ($B - $T));
                    
                    $left_pct = ($x_px / $img_w) * 100;
                    $top_pct  = ($y_px / $img_h) * 100;

                    // Haetaan väri: katsotaan 'c' (uusi) tai 'v' (vanha)
                    $vari = isset($p['c']) ? $p['c'] : (isset($p['v']) ? $p['v'] : 'black');

                    // KÄÄNTÄJÄ: Korjataan suomenkielinen väri englanniksi CSS:ää varten
                    if ($vari === 'musta') {
                        $vari = 'black';
                    }
                    
                    // Muunnetaan koko prosentiksi kuvan leveydestä, jotta se skaalautuu
                    // (Pisteen koko suhteessa 577px leveään kuvaan)
                    $koko_pct = ($pistekoko / $img_w) * 100;
                    ?>
<div class="kartta-piste" style="
    position: absolute;
    left: <?php echo $left_pct; ?>%;
    top: <?php echo $top_pct; ?>%;
    width: <?php echo $koko_pct; ?>%;
    height: auto;
    aspect-ratio: 1 / 1; /* Varmistaa että on täydellinen ympyrä */
    background-color: <?php echo $vari; ?> !important; /* Pakotetaan väri */
    border: 1.5px solid #ffffff;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 4px rgba(0,0,0,0.4);
    z-index: 10;
"></div>
                <?php endforeach; 
            endif; ?>
        </div>
    </div>
</div>
    <?php endif; 
endif; ?>

<!--
-->

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