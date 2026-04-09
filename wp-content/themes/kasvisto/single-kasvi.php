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
                    'Koko' => 'koko',
                    'Kasvupaikka' => 'kasvupaikka',
                    'Levinneisyys' => 'levinneisyys',
                    'Maastotuntomerkit' => 'maastotuntomerkit',
                    'Kemia' => 'kemia',
                    'Vertaa' => 'vertaa',
                    'Löytöpaikat Luopioisissa' => 'loytopaikat',
                    'Kirjallisuus' => 'kirjallisuus'
                ];

                foreach ($fields as $label => $slug): 
                    $value = get_field($slug);
                    if ($value): ?>
                        <div class="content-section">
                            <h3><?php echo $label; ?></h3>
                            <div class="field-value"><?php echo wpautop($value); ?></div>
                        </div>
                    <?php endif; 
                endforeach; ?>
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

                <?php if ($tieteellinen): ?>
                    <div class="media-group">
                        <div id="lajifi-kartta" style="height: 350px;"></div>
                        <div id="map-status" class="map-status-clean"></div>
                        <button id="nayta-havainnot-btn" class="text-link-btn">Näytä havainnot listana ▼</button>
                        
                        <div id="havainto-taulukko-wrap" style="display: none;">
                            <table class="simple-obs-table">
                                <tbody id="taulukko-body"></tbody>
                            </table>
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
// (Sama JS-logiikka kuin aiemmin kartalle ja lightboxille)
document.addEventListener('DOMContentLoaded', function() {
    const lightbox = GLightbox({ selector: '.glightbox' });
    const karttaElement = document.getElementById('lajifi-kartta');
    if (!karttaElement) return;
    
    const kartta = L.map('lajifi-kartta').setView([61.36, 24.85], 11);
    L.tileLayer('https://avoin-karttakuva.maanmittauslaitos.fi/avoin/wmts/1.0.0/maastokartta/default/WGS84_Pseudo-Mercator/{z}/{y}/{x}.png?api-key=API-AVAIMESI', {
        attribution: '&copy; MML'
    }).addTo(kartta);

    <?php 
    $pisteet = [];
    if (function_exists('hae_laji_fi_havainnot') && $tieteellinen) {
        $havainnot = hae_laji_fi_havainnot($tieteellinen);
        if (isset($havainnot['results'])) {
            foreach ($havainnot['results'] as $unit) {
                $coords = $unit['gathering']['conversions']['wgs84CenterPoint'] ?? null;
                if ($coords) $pisteet[] = ['lat' => $coords['lat'], 'lon' => $coords['lon'], 'paikka' => $unit['gathering']['locality'] ?? ''];
            }
        }
    }
    ?>
    const pisteet = <?php echo json_encode($pisteet); ?>;
    if (pisteet.length > 0) {
        const markerGroup = L.featureGroup();
        pisteet.forEach(p => {
            L.circleMarker([p.lat, p.lon], { radius: 6, color: '#e74c3c' }).addTo(markerGroup);
            document.getElementById('taulukko-body').innerHTML += `<tr><td>${p.paikka}</td></tr>`;
        });
        markerGroup.addTo(kartta);
        kartta.fitBounds(markerGroup.getBounds());
    }
    
    document.getElementById('nayta-havainnot-btn').addEventListener('click', function() {
        const wrap = document.getElementById('havainto-taulukko-wrap');
        wrap.style.display = wrap.style.display === 'none' ? 'block' : 'none';
    });
});
</script>

<?php get_footer(); ?>