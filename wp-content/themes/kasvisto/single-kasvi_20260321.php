<?php get_header(); ?>

<main class="kasvi-container single-kasvi-card">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        
        <header class="kasvi-header-compact">
            <div class="header-content">
                <h1><?php the_title(); ?></h1>
                <?php $tieteellinen = get_field('tieteellinen_nimi'); ?>
                <?php if($tieteellinen): ?>
                    <div class="tieteellinen-latina"><i><?php echo esc_html($tieteellinen); ?></i></div>
                <?php endif; ?>
                
                <?php $ryhma = get_field('ryhma'); if($ryhma): ?>
                    <span class="badge-ryhma"><?php echo esc_html($ryhma); ?></span>
                <?php endif; ?>
            </div>
        </header>

        <div class="kasvi-main-grid">
            
            <div class="kasvi-content-column">
                
                <?php if(get_the_content()): ?>
                <div class="info-card">
                    <h3>Yleiskuvaus</h3>
                    <div class="content-text"><?php the_content(); ?></div>
                </div>
                <?php endif; ?>

                <?php 
                $koko = get_field('koko');
                $paikka = get_field('kasvupaikka');
                $levinneisyys = get_field('levinneisyys');
                if ($koko || $paikka || $levinneisyys): ?>
                    <div class="info-card">
                        <h3>Biologia & Kasvupaikka</h3>
                        <div class="info-grid">
                            <?php if($koko) echo '<div class="info-row"><strong>Koko:</strong> <span>' . esc_html($koko) . '</span></div>'; ?>
                            <?php if($paikka) echo '<div class="info-row full-width"><strong>Kasvupaikka:</strong> <div>' . wpautop($paikka) . '</div></div>'; ?>
                            <?php if($levinneisyys) echo '<div class="info-row full-width"><strong>Levinneisyys:</strong> <div>' . wpautop($levinneisyys) . '</div></div>'; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php 
                $maasto = get_field('maastotuntomerkit');
                $kemia = get_field('kemia');
                $vertaa = get_field('vertaa');
                if ($maasto || $kemia || $vertaa): ?>
                    <div class="info-card">
                        <h3>Tuntomerkit</h3>
                        <?php if($maasto) echo '<div class="info-row full-width"><strong>Maastotuntomerkit:</strong> ' . wpautop($maasto) . '</div>'; ?>
                        <?php if($kemia) echo '<div class="info-row full-width"><strong>Kemia:</strong> ' . wpautop($kemia) . '</div>'; ?>
                        <?php if($vertaa) echo '<div class="info-row full-width"><strong>Vertaa:</strong> ' . wpautop($vertaa) . '</div>'; ?>
                    </div>
                <?php endif; ?>

                <?php 
                $loytopaikat = get_field('loytopaikat');
                if ($loytopaikat): ?>
                    <div class="info-card highlight">
                        <h3>Paikallistiedot (Luopioinen)</h3>
                        <div class="content-text"><?php echo wpautop($loytopaikat); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="kasvi-media-column">
                
                <div class="media-card">
                    <h3>Kuvat</h3>
                    <div class="kasvi-gallery-grid-compact">
                        <?php 
                        for ($i = 1; $i <= 10; $i++) {
                            $url = get_field("kuva_{$i}_url");
                            if ($url) {
                                echo '<a href="' . esc_url($url) . '" class="glightbox" data-gall="kasvigalleria" data-vbtype="image">';
                                echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(get_the_title()) . '">';
                                echo '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <?php if ($tieteellinen): ?>
                <div class="media-card">
                    <h3>Levinneisyyskartta</h3>
                    <div id="lajifi-kartta" style="height: 300px; border-radius: 8px;"></div>
                    <div id="debug-lista" class="map-status"></div>
                    
                    <button id="nayta-havainnot-btn" class="compact-btn">
                        Näytä havainnot listana ▼
                    </button>
                    
                    <div id="havainto-taulukko-wrap" style="display: none;">
                        <div class="table-scroll">
                            <table class="compact-table">
                                <thead>
                                    <tr><th>Pvm</th><th>Paikka</th></tr>
                                </thead>
                                <tbody id="taulukko-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

       <?php 
        $kirjallisuus = get_field('kirjallisuus'); 
        if($kirjallisuus): ?>
            <footer class="kasvi-card-footer">
                <strong>Kirjallisuus:</strong> <?php echo strip_tags($kirjallisuus); ?>
            </footer>
        <?php endif; ?>

    <?php endwhile; endif; ?>
</main>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Alustetaan Lightbox
    const lightbox = GLightbox({
        selector: '.glightbox', // Huom: muutetaan linkkien luokka täksi
        touchNavigation: true,
        loop: true
    });
});
</script>

<?php get_footer(); ?> ```

### Tee vielä tämä muutos kuva-looppiin (kohdassa 105):
Jotta GLightbox tunnistaa kuvat, vaihda linkin luokka `gallery-link` -> `glightbox`. Muutin myös loopin lisäämään kuvatekstin automaattisesti:

```php
<?php 
for ($i = 1; $i <= 10; $i++) {
    $url = get_field("kuva_{$i}_url");
    if ($url) {
        // Käytetään kasvin nimeä otsikkona lightboxissa
        echo '<a href="' . esc_url($url) . '" class="glightbox" data-glightbox="title: ' . esc_attr(get_the_title()) . '">';
        echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(get_the_title()) . '">';
        echo '</a>';
    }
}
?>