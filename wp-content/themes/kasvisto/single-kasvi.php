<?php get_header(); ?>

<main class="kasvi-container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        
        <header class="kasvi-header">
            <div class="nimi-ryhma">
                <h1>
                    <?php the_title(); ?> 
                    <?php 
                    $tieteellinen = get_field('tieteellinen_nimi');
                    if($tieteellinen): ?>
                        <small class="tieteellinen-nimi"><i>(<?php echo esc_html($tieteellinen); ?>)</i></small>
                    <?php endif; ?>
                </h1>
                <?php 
                $ryhma = get_field('ryhma');
                if($ryhma): ?>
                    <span class="kasvi-ryhma"><?php echo esc_html($ryhma); ?></span>
                <?php endif; ?>
            </div>
        </header>

        <div class="kasvi-grid">
            <div class="kasvi-details">
                
                <?php if(get_the_content()): ?>
                <section class="kasvi-info-block kuvaus">
                    <h3>Kuvaus</h3>
                    <div class="content-text"><?php the_content(); ?></div>
                </section>
                <?php endif; ?>

                <?php 
                $koko = get_field('koko');
                $paikka = get_field('kasvupaikka');
                $levinneisyys = get_field('levinneisyys');
                if ($koko || $paikka || $levinneisyys): ?>
                    <section class="kasvi-info-block">
                        <h3>Perustiedot</h3>
                        <?php if($koko) echo '<p><strong>Koko:</strong> ' . esc_html($koko) . '</p>'; ?>
                        <?php if($paikka) echo '<p><strong>Kasvupaikka:</strong> ' . esc_html($paikka) . '</p>'; ?>
                        <?php if($levinneisyys) echo '<p><strong>Levinneisyys:</strong> ' . esc_html($levinneisyys) . '</p>'; ?>
                    </section>
                <?php endif; ?>

                <?php 
                $maasto = get_field('maastotuntomerkit');
                $kemia = get_field('kemia');
                $vertaa = get_field('vertaa');
                if ($maasto || $kemia || $vertaa): ?>
                    <section class="kasvi-info-block">
                        <h3>Tuntomerkit & Kemia</h3>
                        <?php if($maasto) echo '<p><strong>Maastotuntomerkit:</strong> ' . esc_html($maasto) . '</p>'; ?>
                        <?php if($kemia) echo '<p><strong>Kemia:</strong> ' . esc_html($kemia) . '</p>'; ?>
                        <?php if($vertaa) echo '<p><strong>Vertaa:</strong> ' . esc_html($vertaa) . '</p>'; ?>
                    </section>
                <?php endif; ?>

                <?php 
                $sienet = get_field('mikrosienet');
                $isanta = get_field('isantakasvit');
                $muut_sienet = get_field('isantakasvin_muut');
                if ($sienet || $isanta || $muut_sienet): ?>
                    <section class="kasvi-info-block">
                        <h3>Ekologia</h3>
                        <?php if($sienet) echo '<p><strong>Mikrosienet:</strong> ' . esc_html($sienet) . '</p>'; ?>
                        <?php if($isanta) echo '<p><strong>Löydetyt isäntäkasvit:</strong> ' . esc_html($isanta) . '</p>'; ?>
                        <?php if($muut_sienet) echo '<p><strong>Isäntäkasvin muut piensienet:</strong> ' . esc_html($muut_sienet) . '</p>'; ?>
                    </section>
                <?php endif; ?>
                
                <?php 
                $loytopaikat = get_field('loytopaikat');
                if ($loytopaikat): ?>
                    <section class="kasvi-info-block">
                        <h3>Paikallistiedot</h3>
                        <p><strong>Löytöpaikat Luopioisissa:</strong> <?php echo esc_html($loytopaikat); ?></p>
                    </section>
                <?php endif; ?>

                <?php 
                $muuta = get_field('muuta');
                if($muuta): ?>
                    <div class="kasvi-note">
                        <strong>Muuta:</strong> <?php echo esc_html($muuta); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="kasvi-gallery-container">
                <h3>Kuvat</h3>
                <div class="kasvi-gallery-grid">
                    <?php 
                    for ($i = 1; $i <= 10; $i++) {
                        $url = get_field("kuva_{$i}_url");
                        if ($url) {
                            echo '<a href="' . esc_url($url) . '" class="gallery-link">';
                            echo '<img src="' . esc_url($url) . '" alt="' . esc_attr(get_the_title()) . '">';
                            echo '</a>';
                        }
                    }
                    ?>
                </div>
                <p class="gallery-hint">Klikkaa kuvaa suurentaaksesi.</p>
            </div>
        </div>

        <?php 
        $kirjallisuus = get_field('kirjallisuus');
        if($kirjallisuus): ?>
        <footer class="kasvi-footer">
            <p><strong>Kirjallisuus:</strong> <?php echo esc_html($kirjallisuus); ?></p>
        </footer>
        <?php endif; ?>

    <?php endwhile; endif; ?>
</main>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/luminous-lightbox/2.3.2/luminous-basic.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/luminous-lightbox/2.3.2/Luminous.min.js"></script>

<script>
window.addEventListener('load', function() {
    if (typeof LuminousGallery !== 'undefined') {
        var elements = document.querySelectorAll('.gallery-link');
        if(elements.length > 0) {
            new LuminousGallery(elements, {arrowNavigation: true});
        }
    }
});
</script>

<?php get_footer(); ?>