<?php
/**
 * Shortcode [jakalat_lisaryhmat] – jäkälälista lisäryhmän mukaan.
 *
 * Siirretty Code Snippets -snippetistä #15 (vienti 10.11.2026).
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function kasvisto_sc_jakalat_lisaryhmat($atts) {
    $a = shortcode_atts([
        'ryhma' => '', // Tähän syötetään esim. "100 tavallisinta lajia"
    ], $atts);

    $kohderyhma = trim($a['ryhma']);
    
    $args = [
        'post_type'      => 'kasvi',
        'posts_per_page' => -1,
    ];

    $query = new WP_Query($args);
    $kasvit = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            // Haetaan valintaruutujen (checkbox) arvot lisäryhmistä
            $lisaryhmat = get_field('jakalien_lisaryhmat');
            
            $match = false;
            if (is_array($lisaryhmat)) {
                $match = in_array($kohderyhma, $lisaryhmat);
            } elseif ($lisaryhmat) {
                $match = (strcasecmp((string)$lisaryhmat, $kohderyhma) === 0);
            }

            if ($match) {
                $kasvit[] = [
                    'title' => get_the_title(),
                    'link'  => get_permalink(),
                    'latina' => get_field('tieteellinen_nimi'),
                    'eka' => mb_strtoupper(mb_substr(get_the_title(), 0, 1, 'UTF-8'))
                ];
            }
        }
        wp_reset_postdata();
    }

    // Suomalainen aakkosjärjestys
    usort($kasvit, function($a, $b) {
        return strcoll(setlocale(LC_COLLATE, 'fi_FI.utf8', 'fi_FI', 'finnish'), '') ? 
               strcoll($a['title'], $b['title']) : 
               strcmp($a['title'], $b['title']);
    });

    // Lasketaan tulosten määrä
    $maara = count($kasvit);
    
    ob_start(); 
    ?>
    
    <div class="kasvi-lista-container">
        <header class="lista-header-wrapper">
            <div class="header-top-row" style="display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 15px;">
                <h2 class="ryhma-otsikko-staattinen" style="margin: 0;">
                    <?php echo esc_html($kohderyhma); ?> (<?php echo $maara; ?>)
                </h2>
                
                <div class="haku-osio">
                    <input type="text" id="haku-<?php echo sanitize_title($kohderyhma); ?>" 
                           class="kasvi-haku-input" 
                           placeholder="Etsi..." 
                           autocomplete="off"
                           style="padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; max-width: 200px;">
                </div>
            </div>

            <div class="aakkos-nav">
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

        <div class="kasvi-lista-rows" id="lista-<?php echo sanitize_title($kohderyhma); ?>">
            <?php if (!empty($kasvit)) : ?>
                <?php foreach ($kasvit as $kasvi) : ?>
                    <a href="<?php echo $kasvi['link']; ?>" class="kasvi-rivi" 
                       data-nimi="<?php echo esc_attr($kasvi['title']); ?>" 
                       data-latina="<?php echo esc_attr($kasvi['latina']); ?>" 
                       data-letter="<?php echo $kasvi['eka']; ?>" style="display: flex;">
                        <div class="sarake nimi-suomi"><?php echo $kasvi['title']; ?></div>
                        <div class="sarake nimi-latina"><i><?php echo esc_html($kasvi['latina']); ?></i></div>
                    </a>
                <?php endforeach; ?>
            <?php else : ?>
                <p>Ei löytynyt jäkäliä ryhmästä "<?php echo esc_html($kohderyhma); ?>".</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    (function() {
        const wrapperId = '#lista-<?php echo sanitize_title($kohderyhma); ?>';
        const haku = document.getElementById('haku-<?php echo sanitize_title($kohderyhma); ?>');
        const lista = document.querySelector(wrapperId);
        if (!haku || !lista) return;
        const rivit = lista.querySelectorAll('.kasvi-rivi');
        const kirjainNapit = haku.closest('.kasvi-lista-container').querySelectorAll('.kirjain-linkki');

        haku.addEventListener('input', function() {
            const termi = this.value.toLowerCase();
            rivit.forEach(rivi => {
                const nimi = (rivi.getAttribute('data-nimi') || '').toLowerCase();
                const latina = (rivi.getAttribute('data-latina') || '').toLowerCase();
                rivi.style.display = (nimi.includes(termi) || latina.includes(termi)) ? 'flex' : 'none';
            });
        });

        kirjainNapit.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const k = this.getAttribute('data-letter');
                const kohde = Array.from(rivit).find(r => r.getAttribute('data-letter') === k && r.style.display !== 'none');
                if (kohde) {
                    kohde.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    })();
    </script>

    <?php
    return ob_get_clean();
}
