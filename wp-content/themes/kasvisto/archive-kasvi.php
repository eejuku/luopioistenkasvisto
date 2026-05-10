<?php get_header(); ?>

<main class="kasvi-lista-container">
    <h1 class="sivu-otsikko">Kasvisto</h1>

    <header class="lista-header-wrapper">
        <div class="header-top-row">
            <nav class="kasvi-nav">
                <?php 
                $ryhmat = ['Putkilokasvit', 'Sammalet', 'Jäkälät', 'Piensienet'];
                $all_kasvit = get_posts(['post_type'=>'kasvi', 'posts_per_page'=>-1]);
                $counts = array_count_values(array_map(function($p) {
                    return get_field('ryhma', $p->ID);
                }, $all_kasvit));

                foreach($ryhmat as $r): 
                    $luku = $counts[$r] ?? 0;
                ?>
                    <button class="nav-btn <?php echo ($r == 'Putkilokasvit') ? 'active' : ''; ?>" data-filter="<?php echo $r; ?>" data-count="<?php echo $luku; ?>">
                        <span class="btn-label"><?php echo $r; ?></span>
                        <span class="count">(<?php echo $luku; ?>)</span>
                    </button>
                <?php endforeach; ?>
            </nav>

            <div class="haku-osio">
                <input type="text" id="kasvi-haku" placeholder="Etsi nimellä..." autocomplete="off">
            </div>
        </div>

        <h2 id="ryhma-otsikko">Putkilokasvit (<?php echo $counts['Putkilokasvit'] ?? 0; ?>)</h2>

        <div class="aakkos-nav" id="aakkoset">
            <?php 
            $kirjaimet = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','Å','Ä','Ö'];
            foreach($kirjaimet as $k) echo '<button class="kirjain-linkki" data-letter="'.$k.'">'.$k.'</button>';
            ?>
        </div>
    </header>

    <div class="lista-otsikot">
        <div class="sarake">Suomenkielinen nimi</div>
        <div class="sarake">Tieteellinen nimi</div>
    </div>

    <div id="kasvi-lista" class="kasvi-lista-rows">
        <?php 
        $query = new WP_Query(['post_type'=>'kasvi','posts_per_page'=>-1,'orderby'=>'title', 'order'=>'ASC']);
        if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); 
            $tieteellinen = get_field('tieteellinen_nimi');
            $ryhma = get_field('ryhma');
            $eka = mb_strtoupper(mb_substr(get_the_title(), 0, 1, 'UTF-8'));
        ?>
            <a href="<?php the_permalink(); ?>" 
               class="kasvi-rivi" 
               data-ryhma="<?php echo esc_attr($ryhma); ?>" 
               data-nimi="<?php echo esc_attr(get_the_title()); ?>" 
               data-latina="<?php echo esc_attr($tieteellinen); ?>"
               data-letter="<?php echo $eka; ?>"
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
    const ryhmaOtsikko = document.getElementById('ryhma-otsikko');
    const napit = document.querySelectorAll('.nav-btn');
    const kirjainNapit = document.querySelectorAll('.kirjain-linkki');
    const rivit = document.querySelectorAll('.kasvi-rivi');

    function suodata() {
        const termi = haku.value.toLowerCase();
        const nykyinenRyhma = document.querySelector('.nav-btn.active').getAttribute('data-filter');
        
        rivit.forEach(rivi => {
            const matchHaku = rivi.getAttribute('data-nimi').toLowerCase().includes(termi) || 
                             rivi.getAttribute('data-latina').toLowerCase().includes(termi);
            const matchRyhma = rivi.getAttribute('data-ryhma') === nykyinenRyhma;
            rivi.style.display = (matchHaku && matchRyhma) ? 'flex' : 'none';
        });
    }

    haku.addEventListener('input', suodata);

    napit.forEach(nappi => {
        nappi.addEventListener('click', function() {
            napit.forEach(n => n.classList.remove('active'));
            this.classList.add('active');
            const ryhma = this.getAttribute('data-filter');
            const maara = this.getAttribute('data-count');
            ryhmaOtsikko.textContent = `${ryhma} (${maara})`;
            haku.value = '';
            suodata();
        });
    });

    kirjainNapit.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const k = this.getAttribute('data-letter');
            const kohde = Array.from(rivit).find(r => 
                r.getAttribute('data-letter') === k && r.style.display !== 'none'
            );
            if (kohde) kohde.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
});
</script>


<?php get_footer(); ?>