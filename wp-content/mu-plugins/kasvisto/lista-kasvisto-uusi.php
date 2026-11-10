<?php
/**
 * Shortcode [kasvisto_uusi] – suodatettava lajilista.
 *
 * Siirretty Code Snippets -snippetistä #6 (vienti 10.11.2026).
 * Code Snippetsissä ajettiin vain julkisella puolella (scope: front-end).
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function kasvisto_sc_kasvisto_uusi($atts) {
    // 1. Määritellään kaikki mahdolliset parametrit
    $a = shortcode_atts([
        'ryhma'                     => 'Putkilokasvit',
        'uhanalaisuus'              => '',
        'putkilokasvien_lisaryhmat' => '',
        'sammalten_lisaryhmat'      => '',
        'jakalien_lisaryhmat'       => '',
        'piensienten_lisaryhmat'    => '',
        'otsikko'                   => '', 
        'alaotsikko'                => '', 
        'loytopisteet'              => 'ei', // "kylla" aktivoi löytöpistesarakkeen
        'hakukentta'                => 'kylla', // "ei" piilottaa haun
        'aakkosnavigaatio'          => 'kylla', // "ei" piilottaa aakkoset
        'vain_luopioiset'          => 'kylla', // "kylla" suodattaa pois "Muualta löydettyjä sammalia", "ei" näyttää kaikki
    ], $atts);

    // Siistitään parametrit taulukoiksi
    $kohde_ryhma = trim($a['ryhma']);
    $seula_uhanalaisuus = $a['uhanalaisuus'] ? array_map('trim', explode(',', $a['uhanalaisuus'])) : [];
    $seula_putkilo_lisa = $a['putkilokasvien_lisaryhmat'] ? array_map('trim', explode(',', $a['putkilokasvien_lisaryhmat'])) : [];
    $seula_sammal_lisa  = $a['sammalten_lisaryhmat'] ? array_map('trim', explode(',', $a['sammalten_lisaryhmat'])) : [];
    $seula_jakala_lisa  = $a['jakalien_lisaryhmat'] ? array_map('trim', explode(',', $a['jakalien_lisaryhmat'])) : [];
    $seula_sieni_lisa   = $a['piensienten_lisaryhmat'] ? array_map('trim', explode(',', $a['piensienten_lisaryhmat'])) : [];
    
    // Liputetaan näkyvyydet ja suodatukset
    $nayta_pisteet     = (trim($a['loytopisteet']) === 'kylla');
    $nayta_haku        = (trim($a['hakukentta']) !== 'ei');
    $nayta_aakkoset    = (trim($a['aakkosnavigaatio']) !== 'ei');
    $rajus_luopioiset  = (trim($a['vain_luopioiset']) === 'kylla');

    // ULTRAKEVYT TIETOKANTAHAKU (Estää muistin loppumisen)
    $args = [
        'post_type'              => 'kasvi',
        'posts_per_page'         => -1,
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ];

    $query = new WP_Query($args);
    $kasvit = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // --- TARKISTUS 1: PÄÄRYHMÄ ---
            $ryhma = get_field('ryhma', $post_id);
            $match_ryhma = is_array($ryhma) ? in_array($kohde_ryhma, $ryhma) : (strcasecmp((string)$ryhma, $kohde_ryhma) === 0);
            if (!$match_ryhma) continue;

            // --- TARKISTUS 2: UHANALAISEUS ---
            if (!empty($seula_uhanalaisuus)) {
                $kannan_uhanalaisuudet = (array)get_field('uhanalaisuus', $post_id);
                if (!array_intersect($seula_uhanalaisuus, $kannan_uhanalaisuudet)) continue;
            }

            // --- TARKISTUS 3: LISÄRYHMÄT ---
            if (!empty($seula_putkilo_lisa)) {
                $lisa = (array)get_field('putkilokasvien_lisaryhmat', $post_id);
                if (!array_intersect($seula_putkilo_lisa, $lisa)) continue;
            }
            if (!empty($seula_sammal_lisa)) {
                $lisa = (array)get_field('sammalten_lisaryhmat', $post_id);
                if (!array_intersect($seula_sammal_lisa, $lisa)) continue;
            }
            if (!empty($seula_jakala_lisa)) {
                $lisa = (array)get_field('jakalien_lisaryhmat', $post_id);
                if (!array_intersect($seula_jakala_lisa, $lisa)) continue;
            }
            if (!empty($seula_sieni_lisa)) {
                $lisa = (array)get_field('piensienten_lisaryhmat', $post_id);
                if (!array_intersect($seula_sieni_lisa, $lisa)) continue;
            }

            // --- TARKISTUS 4: LUOPIOISTEN ALUEEN RAJAUS ---
            if ($rajus_luopioiset) {
                $sammal_lisa_valinnat = (array)get_field('sammalten_lisaryhmat', $post_id);
                if (in_array('Muualta löydettyjä sammalia', $sammal_lisa_valinnat)) {
                    continue;
                }
            }

            // --- KEVEÄ SYNONYYMIHAKU (Hakuilmiötä varten ilman loop-kaatumista) ---
            $synonyymit_teksti = '';
            $synonyymit_repeater = get_field('synonyymit', $post_id);
            if (is_array($synonyymit_repeater) && !empty($synonyymit_repeater)) {
                $syn_list = [];
                foreach ($synonyymit_repeater as $row) {
                    if (!empty($row['suomenkielinen_synonyymi'])) $syn_list[] = mb_strtolower($row['suomenkielinen_synonyymi'], 'UTF-8');
                    if (!empty($row['tieteellinen_synonyymi']))    $syn_list[] = mb_strtolower($row['tieteellinen_synonyymi'], 'UTF-8');
                }
                $synonyymit_teksti = implode(' | ', $syn_list);
            }

            // --- LÖYTÖPISTEIDEN LASKENTA VAIN JOS PARAMEETRI PÄÄLLÄ ---
            $pisteet_maara_teksti = '-';
            $pisteet_numeraalinen = 0;
            if ($nayta_pisteet) {
                $json_data = get_field('karttapisteet_json', $post_id);
                if (!empty($json_data)) {
                    $pisteet_taulukko = json_decode($json_data, true);
                    if (is_array($pisteet_taulukko) && !empty($pisteet_taulukko)) {
                        $pisteet_numeraalinen = count($pisteet_taulukko);
                        $pisteet_maara_teksti = (string)$pisteet_numeraalinen;
                    }
                }
            }

            $latina_nimi = (string)get_field('tieteellinen_nimi', $post_id);
            $suomi_nimi  = get_the_title();

            $kasvit[] = [
                'title'       => $suomi_nimi,
                'link'        => get_permalink(),
                'latina'      => $latina_nimi,
                'synonyymit'  => $synonyymit_teksti,
                'pisteet'     => $pisteet_maara_teksti,
                'pisteet_num' => $pisteet_numeraalinen,
                'eka'         => mb_strtoupper(mb_substr($suomi_nimi, 0, 1, 'UTF-8')),
                'eka_latina'  => $latina_nimi ? mb_strtoupper(mb_substr($latina_nimi, 0, 1, 'UTF-8')) : ''
            ];
        }
        wp_reset_postdata();
    }

    // TURVALLINEN SUOMALAINEN JÄRJESTYS (Ei riko PHP 8+ ympäristöjä)
    if (!empty($kasvit)) {
        $kasvit = wp_list_sort($kasvit, 'title', 'ASC');
    }
    
    // Generoidaan slug uniikiksi
    $slug_pohja = $a['otsikko'] ? $a['otsikko'] : $kohde_ryhma;
    $wrapper_slug = sanitize_title($slug_pohja . '-' . implode('-', $seula_uhanalaisuus));
    $naytettava_otsikko = !empty($a['otsikko']) ? $a['otsikko'] : $kohde_ryhma;
    $kasvien_maara = count($kasvit);
    $rakenne_luokka = $nayta_pisteet ? 'on-loytopisteet' : 'ei-loytopesteet';

    ob_start(); 
    ?>
    
    <div class="kasvi-lista-container <?php echo $rakenne_luokka; ?>" id="container-<?php echo $wrapper_slug; ?>" style="width: 100%; max-width: 900px; margin: 0 auto 0 0; box-sizing: border-box;">
        <header class="lista-header-wrapper" style="margin-bottom: 20px;">
            <div class="header-top-row" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap;">
                
                <div class="otsikko-ryhma" style="display: flex; flex-direction: column; gap: 4px;">
                    <h2 class="ryhma-otsikko-staattinen" style="margin: 0; line-height: 1.2;">
                        <?php echo esc_html($naytettava_otsikko); ?> (<?php echo $kasvien_maara; ?>)
                    </h2>
                    <?php if (!empty($a['alaotsikko'])) : ?>
                        <span class="lista-alaotsikko" style="font-size: 0.85rem; color: #666; font-weight: normal;"><?php echo esc_html($a['alaotsikko']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($nayta_haku) : ?>
                    <div class="haku-osio" style="position: relative; width: 180px; margin-top: 2px;">
                        <input type="text" id="haku-<?php echo $wrapper_slug; ?>" 
                               class="kasvi-haku-input" 
                               placeholder="Etsi..." 
                               autocomplete="off"
                               style="width: 100%; box-sizing: border-box; padding-right: 30px; margin: 0;">
                        <button type="button" class="haku-tyhjennys-btn" style="display: none; position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #999; font-size: 16px; cursor: pointer; padding: 0; line-height: 1;">&times;</button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($nayta_aakkoset) : ?>
                <div class="aakkos-nav" style="margin-top: 15px;">
                    <?php 
                    $kirjaimet = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','Å','Ä','Ö'];
                    foreach($kirjaimet as $k) {
                        echo '<button type="button" class="kirjain-linkki" data-letter="'.$k.'">'.$k.'</button>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="lista-otsikot sarake-toggles">
            <div class="sarake otsikko-suomi active" data-sort="suomi">
                <span class="otsikko-teksti">Suomenkielinen nimi</span><span class="indikaattori">▼</span>
            </div>
            <div class="sarake otsikko-latina" data-sort="latina">
                <span class="otsikko-teksti">Tieteellinen nimi</span><span class="indikaattori">&nbsp;</span>
            </div>
            <?php if ($nayta_pisteet) : ?>
                <div class="sarake otsikko-pisteet" data-sort="pisteet">
                    <span class="otsikko-teksti">Löytöpisteet</span><span class="indikaattori">&nbsp;</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="kasvi-lista-rows sort-suomi" id="lista-<?php echo $wrapper_slug; ?>">
            <?php if (!empty($kasvit)) : ?>
                <?php foreach ($kasvit as $kasvi) : ?>
                    <a href="<?php echo $kasvi['link']; ?>" class="kasvi-rivi" 
                       data-nimi="<?php echo esc_attr($kasvi['title']); ?>" 
                       data-latina="<?php echo esc_attr($kasvi['latina']); ?>" 
                       data-synonyymit="<?php echo esc_attr($kasvi['synonyymit']); ?>"
                       data-pisteet="<?php echo $kasvi['pisteet_num']; ?>"
                       data-letter-suomi="<?php echo $kasvi['eka']; ?>"
                       data-letter-latina="<?php echo $kasvi['eka_latina']; ?>">
                        <div class="sarake nimi-suomi"><?php echo $kasvi['title']; ?></div>
                        <div class="sarake nimi-latina"><i><?php echo esc_html($kasvi['latina']); ?></i></div>
                        <?php if ($nayta_pisteet) : ?>
                            <div class="sarake nimi-pisteet"><?php echo esc_html($kasvi['pisteet']); ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php else : ?>
                <p>Ei löytynyt kasveja valituilla kriteereillä.</p>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .aakkos-nav:empty {
        display: none !important;
        margin: 0 !important;
        padding: 0 !important;
        height: 0 !important;
    }

    .kasvi-lista-container.ei-loytopesteet .lista-otsikot,
    .kasvi-lista-container.ei-loytopesteet .kasvi-rivi {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: center;
    }
    
    .kasvi-lista-container.on-loytopisteet .lista-otsikot,
    .kasvi-lista-container.on-loytopisteet .kasvi-rivi {
        display: grid;
        grid-template-columns: 3fr 3fr 1fr;
        gap: 20px;
        align-items: center;
    }

    .kasvi-lista-container .sarake {
        box-sizing: border-box;
        line-height: 1.2;
    }

    .kasvi-lista-container .nimi-suomi,
    .kasvi-lista-container .nimi-latina {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .kasvi-lista-container .sarake .otsikko-teksti {
        word-break: normal;
        overflow-wrap: normal;
        white-space: normal;
        display: inline;
    }

    .kasvi-lista-container .nimi-pisteet {
        text-align: right;
        font-weight: 500;
        color: #444;
        padding-right: 16px;
    }

    @media (max-width: 600px) {
        .kasvi-lista-container.on-loytopisteet .lista-otsikot,
        .kasvi-lista-container.on-loytopisteet .kasvi-rivi {
            gap: 10px;
            grid-template-columns: 3fr 3fr 1fr;
        }
        .kasvi-lista-container .otsikko-pisteet {
            font-size: 0.75rem;
        }
    }

    .sarake-toggles .sarake { cursor: pointer; user-select: none; }
    
    .kasvi-lista-rows.sort-suomi .nimi-suomi { color: #77a464 !important; }
    .kasvi-lista-rows.sort-suomi .nimi-latina { color: #666 !important; }
    .kasvi-lista-rows.sort-suomi .nimi-pisteet { color: #444 !important; }
    .sarake-toggles .otsikko-suomi.active { color: #77a464 !important; }

    .kasvi-lista-rows.sort-latina .nimi-suomi { color: #666 !important; }
    .kasvi-lista-rows.sort-latina .nimi-latina { color: #77a464 !important; }
    .kasvi-lista-rows.sort-latina .nimi-pisteet { color: #444 !important; }
    .sarake-toggles .otsikko-latina.active { color: #77a464 !important; }

    .kasvi-lista-rows.sort-pisteet .nimi-suomi { color: #666 !important; }
    .kasvi-lista-rows.sort-pisteet .nimi-latina { color: #666 !important; }
    .kasvi-lista-rows.sort-pisteet .nimi-pisteet { color: #77a464 !important; }
    .sarake-toggles .otsikko-pisteet.active { color: #77a464 !important; }
    
    .sarake-toggles .sarake:not(.active) { color: #333 !important; }

    .sarake-toggles .sarake .indikaattori {
        font-size: 0.7rem;
        margin-left: 4px;
        display: inline-block;
        vertical-align: middle;
        width: 12px;
        text-align: center;
    }
    </style>

    <script>
    (function() {
        const containerId = '#container-<?php echo $wrapper_slug; ?>';
        const container = document.querySelector(containerId);
        if (!container) return;

        const haku = container.querySelector('#haku-<?php echo $wrapper_slug; ?>');
        const tyhjennysBtn = container.querySelector('.haku-tyhjennys-btn');
        const lista = container.querySelector('#lista-<?php echo $wrapper_slug; ?>');
        const kirjainNapit = container.querySelectorAll('.kirjain-linkki');
        const otsikkoTogglet = container.querySelectorAll('.sarake-toggles .sarake');
        const rivit = Array.from(lista.querySelectorAll('.kasvi-rivi'));

        let nykyinenJarjestys = 'suomi';

        function paivitaAakkoset() {
            if (kirjainNapit.length === 0) return;
            const loytyvatKirjaimet = new Set();
            
            rivit.forEach(rivi => {
                if (rivi.style.display !== 'none') {
                    const attr = nykyinenJarjestys === 'suomi' ? 'data-letter-suomi' : 'data-letter-latina';
                    const k = rivi.getAttribute(attr);
                    if (k) loytyvatKirjaimet.add(k);
                }
            });

            kirjainNapit.forEach(btn => {
                const k = btn.getAttribute('data-letter');
                btn.disabled = !loytyvatKirjaimet.has(k);
            });
        }

        function jarjestaLista(kieli) {
            nykyinenJarjestys = kieli;

            lista.classList.remove('sort-suomi', 'sort-latina', 'sort-pisteet');
            lista.classList.add('sort-' + kieli);

            otsikkoTogglet.forEach(otsikko => {
                const indikaattori = otsikko.querySelector('.indikaattori');
                if (otsikko.getAttribute('data-sort') === kieli) {
                    otsikko.classList.add('active');
                    indikaattori.innerHTML = '▼';
                } else {
                    otsikko.classList.remove('active');
                    indikaattori.innerHTML = '&nbsp;';
                }
            });

            rivit.sort((a, b) => {
                if (kieli === 'pisteet') {
                    const valA = parseInt(a.getAttribute('data-pisteet') || '0', 10);
                    const valB = parseInt(b.getAttribute('data-pisteet') || '0', 10);
                    return valB - valA;
                } else {
                    const valA = kieli === 'suomi' ? (a.getAttribute('data-nimi') || '') : (a.getAttribute('data-latina') || '');
                    const valB = kieli === 'suomi' ? (b.getAttribute('data-nimi') || '') : (b.getAttribute('data-latina') || '');
                    return valA.localeCompare(valB, 'fi');
                }
            });

            lista.innerHTML = '';
            rivit.forEach(rivi => lista.appendChild(rivi));
            paivitaAakkoset();
        }

        if (haku) {
            haku.addEventListener('input', function() {
                const termi = this.value.toLowerCase();
                tyhjennysBtn.style.display = termi.length > 0 ? 'block' : 'none';

                rivit.forEach(rivi => {
                    const nimi = (rivi.getAttribute('data-nimi') || '').toLowerCase();
                    const latina = (rivi.getAttribute('data-latina') || '').toLowerCase();
                    const synonyymit = (rivi.getAttribute('data-synonyymit') || '').toLowerCase();
                    
                    const match = nimi.includes(termi) || latina.includes(termi) || synonyymit.includes(termi);
                    rivi.style.display = match ? 'grid' : 'none';
                });
                paivitaAakkoset();
            });

            tyhjennysBtn.addEventListener('click', function() {
                haku.value = '';
                this.style.display = 'none';
                rivit.forEach(rivi => rivi.style.display = 'grid');
                haku.focus();
                paivitaAakkoset();
            });
        }

        if (kirjainNapit.length > 0) {
            kirjainNapit.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const k = this.getAttribute('data-letter');
                    const attrName = nykyinenJarjestys === 'suomi' ? 'data-letter-suomi' : 'data-letter-latina';
                    const kohde = rivit.find(r => r.getAttribute(attrName) === k && r.style.display !== 'none');
                    if (kohde) {
                        kohde.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        }

        otsikkoTogglet.forEach(otsikko => {
            otsikko.addEventListener('click', function() {
                const kieli = this.getAttribute('data-sort');
                jarjestaLista(kieli);
            });
        });

        paivitaAakkoset();
    })();
    </script>

    <?php
    return ob_get_clean();
}
