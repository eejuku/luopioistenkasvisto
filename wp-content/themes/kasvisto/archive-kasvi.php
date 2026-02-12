<?php get_header(); ?>

<main class="kasvi-lista-container">
    <header class="lista-header">
        <h1>Kasvisto</h1>
        
        <nav class="kasvi-nav">
            <button class="nav-btn active" data-filter="Putkilokasvit">Putkilokasvit</button>
            <button class="nav-btn" data-filter="Sammalet">Sammalet</button>
            <button class="nav-btn" data-filter="Jäkälät">Jäkälät</button>
            <button class="nav-btn" data-filter="Piensienet">Piensienet</button>
        </nav>

        <div class="haku-osio">
            <input type="text" id="kasvi-haku" placeholder="Etsi nimellä..." autocomplete="off">
        </div>
    </header>

    <div class="lista-otsikot">
        <div class="sarake">Suomenkielinen nimi</div>
        <div class="sarake">Tieteellinen nimi</div>
    </div>

    <div id="kasvi-lista" class="kasvi-lista-rows">
        <?php 
        // Haetaan kaikki kasvit (2000 kpl on tekstinä kevyt)
        $args = array(
            'post_type' => 'kasvi',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        $query = new WP_Query($args);

        if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); 
            $tieteellinen = get_field('tieteellinen_nimi');
            $ryhma = get_field('ryhma');
        ?>
            <a href="<?php the_permalink(); ?>" 
               class="kasvi-rivi" 
               data-ryhma="<?php echo esc_attr($ryhma); ?>" 
               data-nimi="<?php echo esc_attr(get_the_title()); ?>" 
               data-latina="<?php echo esc_attr($tieteellinen); ?>"
               style="display: <?php echo ($ryhma == 'Putkilokasvit') ? 'flex' : 'none'; ?>;">
                
                <div class="sarake nimi-suomi"><?php the_title(); ?></div>
                <div class="sarake nimi-latina"><i><?php echo esc_html($tieteellinen); ?></i></div>
            </a>
        <?php endwhile; wp_reset_postdata(); endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const haku = document.getElementById('kasvi-haku');
    const napit = document.querySelectorAll('.nav-btn');
    const rivit = document.querySelectorAll('.kasvi-rivi');
    let nykyinenRyhma = 'Putkilokasvit';

    // Suodatusfunktio
    function suodata() {
        const termi = haku.value.toLowerCase();
        
        rivit.forEach(rivi => {
            const nimi = rivi.getAttribute('data-nimi').toLowerCase();
            const latina = rivi.getAttribute('data-latina').toLowerCase();
            const ryhma = rivi.getAttribute('data-ryhma');
            
            const matchHaku = nimi.includes(termi) || latina.includes(termi);
            const matchRyhma = ryhma === nykyinenRyhma;

            if (matchHaku && matchRyhma) {
                rivi.style.display = 'flex';
            } else {
                rivi.style.display = 'none';
            }
        });
    }

    // Hakukentän kuuntelija
    haku.addEventListener('input', suodata);

    // Navigaation kuuntelija
    napit.forEach(nappi => {
        nappi.addEventListener('click', function() {
            napit.forEach(n => n.classList.remove('active'));
            this.classList.add('active');
            nykyinenRyhma = this.getAttribute('data-filter');
            suodata();
        });
    });
});
</script>

<?php get_footer(); ?>