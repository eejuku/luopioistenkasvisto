<?php get_header(); ?>

<main class="kasvi-clean-layout">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        
        <header class="kasvi-clean-header">
            <div class="back-link-container" style="margin-bottom: 20px;">
                <a href="javascript:history.back()" class="back-link">
                    &lt;&lt; Takaisin listaukseen
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
                <h2>Lajikuvaus</h2>
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
                $fields_lajikuvaus = [
                    // 'Synonyymi' => 'synonyymi',
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

                
                // Listaus kentistä: Otsikko => ACF-slug
                $fields_luopioinen = [
                    'Löytöpaikat Luopioisissa' => 'loytopaikat',       
                ];

                foreach ($fields_lajikuvaus as $label => $slug): 
                    $value = get_field($slug);
                    
                    // Näytetään osio vain, jos kentässä on sisältöä
                    if ($value): ?>
                        <div class="content-section">
                            <h3><?php echo esc_html($label); ?></h3>
                            <div class="field-value">
                                <?php 
                                // ERIKOISKÄSITTELY: Isäntäkasvit-taulukko
                                if ($slug === 'isantakasvit') {
                                    echo do_shortcode('[isäntäkasvit]');
                                } 
                                // NORMAALI KÄSITTELY muille kentille
                                else {
                                    if (is_array($value)) {
                                        $display_value = isset($value['label']) ? $value['label'] : implode(', ', $value);
                                        echo wpautop(esc_html($display_value));
                                    } else {
                                        echo wpautop($value);
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; 
                endforeach; ?>

                <div class="content-section">
                    <small>
                        Kortti luotu: <?php the_date(); ?>.
                        <?php if (get_the_modified_time() != get_the_time()) : ?>
                            Päivitetty: <?php the_modified_date(); ?>.
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <div class="kasvi-media-sidebar">
                
                <div class="media-group">
                    <h3>Kuvat</h3>

                    <?php
// Varmistetaan, että ACF on aktiivinen
if ( function_exists('get_field') ) :
    $kuvat = get_field('kasvikuvagalleria'); // Haetaan nykyisen kasvin kuvat
    
    if ( !empty($kuvat) && is_array($kuvat) ) : ?>
        
        <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
        <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script> -->

        <div class="kasvi-galleria-grid">
            <?php foreach ($kuvat as $kuva) : 
                $pieni_kuva = $kuva['sizes']['medium']; // 300x300px kuva
                // $pieni_kuva   = !empty($kuva['sizes']['medium_large']) ? $kuva['sizes']['medium_large'] : $kuva['sizes']['medium'];
                $iso_kuva     = $kuva['url'];
                $otsikko      = !empty($kuva['title']) ? $kuva['title'] : '';
                $kuvausteksti = !empty($kuva['caption']) ? $kuva['caption'] : '';
            ?>
                <a href="<?php echo esc_url($iso_kuva); ?>" 
                   class="glightbox kasvi-galleria-item" 
                   data-gallery="kasvigalleria" 
                   data-title="<?php echo esc_attr($otsikko); ?>" 
                   data-description="<?php echo esc_attr($kuvausteksti); ?>">
                    
                    <img src="<?php echo esc_url($pieni_kuva); ?>" 
                         alt="<?php echo esc_attr($kuva['alt']); ?>" 
                         loading="lazy">
                </a>
            <?php endforeach; ?>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof GLightbox !== 'undefined') {
                GLightbox({
                    selector: '.glightbox',
                    touchNavigation: true,
                    loop: true
                });
            }
        });
        </script>

    <?php 
    endif;
endif; 
?>

                    <div class="clean-gallery">
                        <?php 
                        // 1. VANHAT URL-KENTÄT
                        for ($i = 1; $i <= 4; $i++) {
                            $url = get_field("kuva_{$i}_url");
                            if ($url) {
                                echo '<a href="' . esc_url($url) . '" class="glightbox" data-glightbox="title: ' . esc_attr(get_the_title()) . '">';
                                echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(get_the_title()) . '">';
                                echo '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <?php // 1. VANHA STAATTINEN KARTTA (jos olemassa) ?>
                <?php $karttakuva = get_field('karttakuva'); ?>
                <?php if($karttakuva): ?>
                    <div class="media-group">
                        <h3>Luopioisten löytöpaikat</h3>
                        <div class="static-map-img">
                            <img src="<?php echo esc_url($karttakuva['url'] ?? $karttakuva); ?>" alt="Levinneisyyskartta">
                        </div>
                    </div>
                <?php endif; ?>

                <?php 
                /**
                 * DATAN VALMISTELU KARTTOJA VARTEN
                 */
                $pisteet_json = get_field('karttapisteet_json');
                $maakunta_data = get_field('eliomaakunnat'); 

                $on_luopioinen = false;
                if ($pisteet_json) {
                    $pisteet_decoded = json_decode($pisteet_json, true);
                    if (!empty($pisteet_decoded)) $on_luopioinen = true;
                }

                $on_maakunta = false;
                $json_maakunta_final = '{}';
                if (!empty($maakunta_data) && $maakunta_data !== '{}') {
                    $maakunta_decoded = json_decode($maakunta_data, true);
                    if ($maakunta_decoded && count($maakunta_decoded) > 0) {
                        $on_maakunta = true;
                        $json_maakunta_final = wp_json_encode($maakunta_decoded);
                    }
                }

                // Jos kumpaakaan dataa ei ole, ei piirretä koko kartta-osiota
                if ($on_luopioinen || $on_maakunta) : 
                    
                    // Päätetään kumpi välilehti on automaattisesti auki ladattaessa.
                    // Ensisijaisesti Luopioinen, mutta jos sitä ei ole, avataan Suomi-kartta suoraan.
                    $default_tab = $on_luopioinen ? 'tab-luopioinen' : 'tab-suomi';
                ?>
                    <div class="media-group kartta-osio">
                        <h3>Kartat</h3>
                        
                        <div class="kartta-tabs">
                            <?php if ($on_luopioinen) : ?>
                                <button class="kartta-tab-button <?php echo ($default_tab === 'tab-luopioinen') ? 'active' : ''; ?>" 
                                        onclick="openKarttaTab(event, 'tab-luopioinen')">
                                    Luopioisten löytöpaikat
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($on_maakunta) : ?>
                                <button class="kartta-tab-button <?php echo ($default_tab === 'tab-suomi') ? 'active' : ''; ?>" 
                                        onclick="openKarttaTab(event, 'tab-suomi')">
                                    Levinneisyys Suomessa
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if ($on_luopioinen) : 
                            $uusi_karttapohja_url = get_template_directory_uri() . '/images/karttapohja.png';
                            $pisteet = isset($pisteet_decoded['points']) ? $pisteet_decoded['points'] : $pisteet_decoded;
                            $pistekoko = isset($pisteet_decoded['size']) ? intval($pisteet_decoded['size']) : 15;
                        ?>
                            <div id="tab-luopioinen" class="kartta-tab-content <?php echo ($default_tab === 'tab-luopioinen') ? 'active' : ''; ?>" style="<?php echo ($default_tab === 'tab-luopioinen') ? 'display: block;' : 'display: none;'; ?>">
                                <div class="kasvikartta-wrapper">
                                    <div class="kasvikartta-container">
                                        <img src="<?php echo $uusi_karttapohja_url; ?>" alt="Kartta">
                                        <?php 
                                        $L = 42; $T = 7; $R = 535; $B = 500; $img_w = 577; $img_h = 516; 
                                        foreach ($pisteet as $p) : 
                                            $x_px = $L + (floatval($p['x']) * ($R - $L));
                                            $y_px = $T + (floatval($p['y']) * ($B - $T));
                                            $left_pct = ($x_px / $img_w) * 100;
                                            $top_pct  = ($y_px / $img_h) * 100;
                                            $vari = isset($p['c']) ? $p['c'] : (isset($p['v']) ? $p['v'] : 'black');
                                            if ($vari === 'musta') $vari = 'black';
                                            $koko_pct = ($pistekoko / $img_w) * 100;
                                        ?>
                                            <div class="kartta-piste" style="position: absolute; left: <?php echo $left_pct; ?>%; top: <?php echo $top_pct; ?>%; width: <?php echo $koko_pct; ?>%; aspect-ratio: 1/1; background-color: <?php echo $vari; ?> !important; border: 1.5px solid #ffffff; border-radius: 50%; transform: translate(-50%, -50%); z-index: 10; box-shadow: 0 0 4px rgba(0,0,0,0.4);"></div>
                                        <?php endforeach; ?>

                                    </div>
                                     <div class="legend" style="display: flex; justify-content: center; margin-top: 15px; font-size: 0.65em; color: #2f2f2f; gap: 15px;">
                                            <span><span style="display:inline-block; width:10px; height:10px; background:#000; border-radius: 50%; border:1px solid #333;"></span> Nykyhavainto</span>
                                            <span><span style="display:inline-block; width:10px; height:10px; background:#0000FF; border-radius: 50%; border:1px solid #333;"></span> Havainto 2020 jälkeen</span>
                                            <span><span style="display:inline-block; width:10px; height:10px; background:#FF0000; border-radius: 50%; border:1px solid #333;"></span> Hävinnyt</span>
                                        </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($on_maakunta) : ?>
                            <div id="tab-suomi" class="kartta-tab-content <?php echo ($default_tab === 'tab-suomi') ? 'active' : ''; ?>" style="<?php echo ($default_tab === 'tab-suomi') ? 'display: block;' : 'display: none;'; ?>">
                                <div class="maakuntakartta-inner">
                                    <?php include(get_template_directory() . '/parts/suomi-kartta.php'); ?>
                                    <div class="legend" style="display: flex; justify-content: center; margin-top: 15px; font-size: 0.7em; gap: 15px;">
                                        <span><span style="display:inline-block; width:10px; height:10px; background:#2d5a27; border:1px solid #333;"></span> Nykyhavainto</span>
                                        <span><span style="display:inline-block; width:10px; height:10px; background:#a8c69f; border:1px solid #333;"></span> Vanha havainto</span>
                                        <span><span style="display:inline-block; width:10px; height:10px; background:#c64b4b; border:1px solid #333;"></span> Hävinnyt</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                <?php endif; // Kartta-osion end ?>
            </div>
        </div>

        <script>
        function openKarttaTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("kartta-tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("kartta-tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            
            // Suojataan klikkaus siltä varalta, että painike toimii pelkkänä otsikkona (evt on null)
            if (evt && evt.currentTarget) {
                evt.currentTarget.classList.add("active");
            }
        }

        <?php if ($on_maakunta) : ?>
        document.addEventListener("DOMContentLoaded", function() {
            const data = <?php echo $json_maakunta_final; ?>;
            const svg = document.getElementById('suomi-svg');
            if (!svg) return;
            for (const [id, status] of Object.entries(data)) {
                const el = document.getElementById(id);
                if (el) {
                    let color = "#ffffff";
                    if (status === 'current') color = "#2d5a27";
                    else if (status === 'old') color = "#a8c69f";
                    else if (status === 'extinct') color = "#c64b4b";
                    if (el.tagName.toLowerCase() === 'g') {
                        el.querySelectorAll('path, polygon, polyline').forEach(p => p.style.fill = color);
                    } else {
                        el.style.fill = color;
                    }
                }
            }
        });
        <?php endif; ?>
        </script>

    <?php endwhile; endif; ?>
</main>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.glightbox')) {
        const lightbox = GLightbox({
            selector: '.glightbox',
            loop: true,
            openEffect: 'zoom',
            closeEffect: 'fade' 
        });
    }
});
</script>

<?php get_footer(); ?>