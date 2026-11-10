<?php
/**
 * Shortcode [kasvisto_testiversio] – lajilista uhanalaisuussuodattimilla.
 *
 * Siirretty Code Snippets -snippetistä #17 (vienti 10.11.2026).
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// SHORTCODE: [kasvisto_testiversio ryhma="Sammalet" loytopisteet="kylla" nayta_maara="ei"]
function kasvisto_sc_testiversio($atts) {
    $a = shortcode_atts([
        'ryhma'                     => 'Sammalet',
        'uhanalaisuus'              => '', 
        'putkilokasvien_lisaryhmat' => '',
        'sammalten_lisaryhmat'      => '',
        'jakalien_lisaryhmat'       => '',
        'piensienten_lisaryhmat'    => '',
        'otsikko'                   => '', 
        'alaotsikko'                => '', 
        'loytopisteet'              => 'ei',
        'hakukentta'                => 'kylla',
        'aakkosnavigaatio'          => 'kylla',
        'nayta_maara'               => 'kylla',
    ], $atts);

    $kohde_ryhma = trim($a['ryhma']);
    $seula_putkilo_lisa = $a['putkilokasvien_lisaryhmat'] ? array_map('trim', explode(',', $a['putkilokasvien_lisaryhmat'])) : [];
    $seula_sammal_lisa  = $a['sammalten_lisaryhmat'] ? array_map('trim', explode(',', $a['sammalten_lisaryhmat'])) : [];
    $seula_jakala_lisa  = $a['jakalien_lisaryhmat'] ? array_map('trim', explode(',', $a['jakalien_lisaryhmat'])) : [];
    $seula_sieni_lisa   = $a['piensienten_lisaryhmat'] ? array_map('trim', explode(',', $a['piensienten_lisaryhmat'])) : [];
    
    $nayta_pisteet   = (trim($a['loytopisteet']) === 'kylla');
    $nayta_haku      = (trim($a['hakukentta']) !== 'ei');
    $nayta_aakkoset  = (trim($a['aakkosnavigaatio']) !== 'ei');
    $tulosta_laskuri = (trim($a['nayta_maara']) !== 'ei');

    $uhanalaisuus_sanakirja = [
        'NE' => 'Arvioimatta jätetyt (NE)',
        'NA' => 'Arviointiin kelpaamaton (NA)',
        'DD' => 'Puutteellisesti tunnetut (DD)',
        'RE' => 'Hävinneet (RE)',
        'EW' => 'Luonnosta hävinneet (EW)',
        'CR' => 'Äärimmäisen uhanalaiset (CR)',
        'EN' => 'Erittäin uhanalaiset (EN)',
        'VU' => 'Vaarantuneet (VU)',
        'NT' => 'Silmälläpidettävät (NT)',
        'LC' => 'Elinvoimaiset (LC)',
        'RT' => 'Alueellisesti uhanalaiset (RT)'
    ];

    $args = [
        'post_type'      => 'kasvi',
        'posts_per_page' => -1,
    ];

    $query = new WP_Query($args);
    $kasvit = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // --- PÄÄRYHMÄTARKISTUS ---
            $ryhma = get_field('ryhma', $post_id);
            $match_ryhma = is_array($ryhma) ? in_array($kohde_ryhma, $ryhma) : (strcasecmp((string)$ryhma, $kohde_ryhma) === 0);
            if (!$match_ryhma) continue;

            // --- LISÄRYHMÄTARKISTUKSET ---
            if (!empty($seula_putkilo_lisa) && !array_intersect($seula_putkilo_lisa, (array)get_field('putkilokasvien_lisaryhmat', $post_id))) continue;
            if (!empty($seula_sammal_lisa) && !array_intersect($seula_sammal_lisa, (array)get_field('sammalten_lisaryhmat', $post_id))) continue;
            if (!empty($seula_jakala_lisa) && !array_intersect($seula_jakala_lisa, (array)get_field('jakalien_lisaryhmat', $post_id))) continue;
            if (!empty($seula_sieni_lisa) && !array_intersect($seula_sieni_lisa, (array)get_field('piensienten_lisaryhmat', $post_id))) continue;

            // --- ACF CHECKBOX ARVOT ---
            $u_kentta = get_field('uhanalaisuus', $post_id);
            $kasvin_luokat = [];

            if (is_array($u_kentta)) {
                foreach ($u_kentta as $valinta) {
                    $arvo = is_array($valinta) ? (isset($valinta['value']) ? $valinta['value'] : '') : $valinta;
                    if (!empty($arvo)) {
                        $kasvin_luokat[] = trim($arvo);
                    }
                }
            } elseif (!empty($u_kentta)) {
                $kasvin_luokat[] = trim((string)$u_kentta);
            }

            if (empty($kasvin_luokat)) {
                $kasvin_luokat[] = 'Arvioimatta jätetyt (NE)';
            }

            $naytettava_teksti = $kasvin_luokat[0];
            if (preg_match('/^(.*?)\s*\(([A-Z]{2})\)$/', $naytettava_teksti, $matches)) {
                $muotoiltu_u_teksti = '<strong style="color:#2c3338; font-weight:600;">' . $matches[2] . '</strong> <span style="color:#c0c0c0; font-size:0.8rem; margin:0 4px;">—</span> ' . $matches[1];
            } else {
                $muotoiltu_u_teksti = $naytettava_teksti;
            }

            // --- SYNONYYMIT ---
            $synonyymit_teksti = '';
            if (have_rows('synonyymit', $post_id)) {
                $syn_list = [];
                while (have_rows('synonyymit', $post_id)) {
                    the_row();
                    $syn_suomi  = get_sub_field('suomenkielinen_synonyymi');
                    $syn_latina = get_sub_field('tieteellinen_synonyymi');
                    if ($syn_suomi) $syn_list[] = mb_strtolower($syn_suomi, 'UTF-8');
                    if ($syn_latina) $syn_list[] = mb_strtolower($syn_latina, 'UTF-8');
                }
                $synonyymit_teksti = implode(' | ', $syn_list);
            }

            // --- LÖYTÖPISTEET ---
            $pisteet_maara_teksti = '-';
            $pisteet_numeraalinen = 0;
            if ($nayta_pisteet) {
                $json_data = get_field('karttapisteet_json', $post_id);
                if (!empty($json_data)) {
                    $pisteet_taulukko = json_decode($json_data, true);
                    if (is_array($pisteet_taulukko)) {
                        // Tunnistaa uuden rakenteen {"size":"15","points":[...]}
                        if (isset($pisteet_taulukko['points']) && is_array($pisteet_taulukko['points'])) {
                            $pisteet_numeraalinen = count($pisteet_taulukko['points']);
                        } else {
                            // Alkuperäinen suora taulukkorakenne [...]
                            $pisteet_numeraalinen = count($pisteet_taulukko);
                        }
                        $pisteet_maara_teksti = (string)$pisteet_numeraalinen;
                    }
                }
            }

            $latina_nimi = (string)get_field('tieteellinen_nimi', $post_id);
            $kasvit[] = [
                'title'         => get_the_title(),
                'link'          => get_permalink(),
                'latina'        => $latina_nimi,
                'nayta_u_html'  => $muotoiltu_u_teksti,
                'kaikki_u_json' => json_encode($kasvin_luokat),
                'synonyymit'    => $synonyymit_teksti,
                'pisteet'       => $pisteet_maara_teksti,
                'pisteet_num'   => $pisteet_numeraalinen,
                'eka'           => mb_strtoupper(mb_substr(get_the_title(), 0, 1, 'UTF-8')),
                'eka_latina'    => $latina_nimi ? mb_strtoupper(mb_substr($latina_nimi, 0, 1, 'UTF-8')) : ''
            ];
        }
        wp_reset_postdata();
    }

    usort($kasvit, function($a, $b) {
        return strcoll(setlocale(LC_COLLATE, 'fi_FI.utf8', 'fi_FI', 'finnish'), '') ? 
               strcoll($a['title'], $b['title']) : strcmp($a['title'], $b['title']);
    });
    
    $slug_pohja = $a['otsikko'] ? $a['otsikko'] : $kohde_ryhma;
    $wrapper_slug = sanitize_title($slug_pohja . '-testi');
    $naytettava_otsikko = !empty($a['otsikko']) ? $a['otsikko'] : $kohde_ryhma;
    $rakenne_luokka = $nayta_pisteet ? 'on-loytopisteet' : 'ei-loytopisteet';

    ob_start(); 
    ?>
    
    <div class="kasvi-lista-container <?php echo $rakenne_luokka; ?>" id="container-<?php echo $wrapper_slug; ?>" style="width: 100%; max-width: 860px; margin: 0 auto 0 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        
        <header class="lista-header-wrapper" style="margin-bottom: 15px; width: 100%; box-sizing: border-box;">
            <div class="header-top-row" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap;">
                <div class="otsikko-ryhma" style="display: flex; flex-direction: column; gap: 4px;">
                    <h2 class="ryhma-otsikko-staattinen" style="margin: 0; line-height: 1.2; font-size: 1.6rem; font-weight: 700;">
                        <?php echo esc_html($naytettava_otsikko); ?>
                        <?php if ($tulosta_laskuri) : ?>
                            (<span class="laskuri-maara"><?php echo count($kasvit); ?></span>)
                        <?php else : ?>
                            <span class="laskuri-maara" style="display:none;"><?php echo count($kasvit); ?></span>
                        <?php endif; ?>
                    </h2>
                    <?php if (!empty($a['alaotsikko'])) : ?>
                        <span class="lista-alaotsikko" style="font-size: 0.85rem; color: #666; font-weight: normal;"><?php echo esc_html($a['alaotsikko']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($nayta_haku) : ?>
                    <div class="haku-osio" style="position: relative; width: 180px; margin-top: 2px;">
                        <input type="text" id="haku-<?php echo $wrapper_slug; ?>" class="kasvi-haku-input" placeholder="Etsi..." autocomplete="off" style="width: 100%; box-sizing: border-box; padding: 6px 30px 6px 10px; margin: 0; border: 1px solid #8c8f94; border-radius: 4px;">
                        <button type="button" class="haku-tyhjennys-btn" style="display: none; position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #999; font-size: 16px; cursor: pointer; padding: 0; line-height: 1;">&times;</button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($nayta_aakkoset) : ?>
                <div class="aakkos-nav" style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 2px; width: 100%;">
                    <?php 
                    $kirjaimet = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','Å','Ä','Ö'];
                    foreach($kirjaimet as $k) {
                        echo '<button type="button" class="kirjain-linkki" data-letter="'.$k.'" style="padding: 3px 6px; font-size: 0.8rem; border: 1px solid #dcdcde; background: #fff; border-radius: 3px; cursor: pointer;">'.$k.'</button>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="uhanalaisuus-suodatin-ruudukko" style="display: flex; flex-wrap: nowrap; gap: 4px; margin-bottom: 25px; width: 100%; box-sizing: border-box;">
            <?php foreach ($uhanalaisuus_sanakirja as $lyhenne => $taysi_acf_arvo) : 
                $kuvaus = trim(preg_replace('/\s*\([A-Z]{2}\)/', '', $taysi_acf_arvo));
                ?>
                <div class="u-suodatin-nappi" 
                     data-u-acf-arvo="<?php echo esc_attr($taysi_acf_arvo); ?>" 
                     style="border: 1px solid #dcdcde; background: #f6f7f7; color: #1d2327; padding: 4px 1px; text-align: center; border-radius: 4px; cursor: pointer; user-select: none; transition: all 0.1s ease; height: 72px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; align-items: center; flex: 1 1 0px; min-width: 0;">
                    <div class="u-lyhenne" style="font-size: 0.95rem; font-weight: 700; line-height: 1;"><?php echo $lyhenne; ?></div>
                    <div class="u-kuvaus" style="font-size: 0.58rem; line-height: 1.1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; font-weight: normal; max-width: 100%; height: 22px;"><?php echo esc_html($kuvaus); ?></div>
                    <div class="u-maara-laskuri" style="font-size: 0.9rem; font-weight: 600; color: #666; line-height: 1;">0</div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="lista-otsikot sarake-toggles">
            <div class="sarake otsikko-suomi active" data-sort="suomi"><span class="otsikko-teksti">Suomenkielinen nimi</span><span class="indikaattori">▼</span></div>
            <div class="sarake otsikko-latina" data-sort="latina"><span class="otsikko-teksti">Tieteellinen nimi</span><span class="indikaattori">&nbsp;</span></div>
            <div class="sarake otsikko-uhanalaisuus" data-sort="uhanalaisuus"><span class="otsikko-teksti">Uhanalaisuus</span><span class="indikaattori">&nbsp;</span></div>
            <?php if ($nayta_pisteet) : ?>
                <div class="sarake otsikko-pisteet" data-sort="pisteet"><span class="indikaattori" style="margin-left:0; margin-right:5px;">&nbsp;</span><span class="otsikko-teksti">Löytöpaikat Luopioisissa</span></div>
            <?php endif; ?>
        </div>

        <div class="kasvi-lista-rows sort-suomi" id="lista-<?php echo $wrapper_slug; ?>" style="width: 100%;">
            <?php if (!empty($kasvit)) : ?>
                <?php foreach ($kasvit as $kasvi) : ?>
                    <a href="<?php echo $kasvi['link']; ?>" class="kasvi-rivi" 
                       data-nimi="<?php echo esc_attr($kasvi['title']); ?>" 
                       data-latina="<?php echo esc_attr($kasvi['latina']); ?>" 
                       data-kaikki-uhanalaisuudet='<?php echo esc_attr($kasvi['kaikki_u_json']); ?>'
                       data-synonyymit="<?php echo esc_attr($kasvi['synonyymit']); ?>"
                       data-pisteet="<?php echo $kasvi['pisteet_num']; ?>"
                       data-letter-suomi="<?php echo $kasvi['eka']; ?>"
                       data-letter-latina="<?php echo $kasvi['eka_latina']; ?>">
                        <div class="sarake nimi-suomi"><?php echo $kasvi['title']; ?></div>
                        <div class="sarake nimi-latina"><i><?php echo esc_html($kasvi['latina']); ?></i></div>
                        <div class="sarake nimi-uhanalaisuus"><?php echo $kasvi['nayta_u_html']; ?></div>
                        <?php if ($nayta_pisteet) : ?>
                            <div class="sarake nimi-pisteet"><?php echo esc_html($kasvi['pisteet']); ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="ei-tuloksia-viesti">Ei löytynyt kasveja valituilla kriteereillä.</p>
            <?php endif; ?>
            <p class="ei-suodatin-tuloksia" style="display:none; padding: 15px 0; color: #666; width: 100%;">Ei tuloksia valituilla uhanalaisuuskriteereillä.</p>
        </div>
    </div>

    <style>
    .aakkos-nav:empty { display: none !important; }

    /* TÄYDELLINEN SYNKRONOITU GRID ILMAN GAP-REPESYJÄ (LEVEYDET TÄSMÄÄVÄT AINA VASTAKKAIN) */
    .kasvi-lista-container.ei-loytopisteet .lista-otsikot,
    .kasvi-lista-container.ei-loytopisteet .kasvi-rivi {
        display: grid;
        grid-template-columns: 35% 35% 30%;
        width: 100%;
        box-sizing: border-box;
    }
    
    .kasvi-lista-container.on-loytopisteet .lista-otsikot,
    .kasvi-lista-container.on-loytopisteet .kasvi-rivi {
        display: grid;
        grid-template-columns: 32% 32% 22% 14%;
        width: 100%;
        box-sizing: border-box;
    }

    .kasvi-lista-container .kasvi-rivi {
        text-decoration: none;
        color: #1d2327;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f1;
    }
    .kasvi-lista-container .kasvi-rivi:hover { background-color: #f6f7f7; }

    /* SISÄLTÖJEN VERTIKAALINEN TASAUS JA REUNAN LOGIIKKA */
    .kasvi-lista-container .sarake { 
        box-sizing: border-box; 
        line-height: 1.2; 
        overflow: hidden; 
        display: flex; 
        align-items: center;
    }
    
    /* Normaalit sarakkeet tasataan vasemmalle */
    .kasvi-lista-container .sarake:not(.otsikko-pisteet):not(.nimi-pisteet) {
        justify-content: flex-start;
        text-align: left;
    }

    /* Löytöpisteet pakotetaan oikeaan reunaan kauniisti */
    .kasvi-lista-container .otsikko-pisteet,
    .kasvi-lista-container .nimi-pisteet {
        justify-content: flex-end;
        text-align: right;
    }

    .kasvi-lista-container .nimi-suomi, 
    .kasvi-lista-container .nimi-latina,
    .kasvi-lista-container .nimi-uhanalaisuus,
    .kasvi-lista-container .nimi-pisteet {
        white-space: nowrap; 
        overflow: hidden; 
        text-overflow: ellipsis;
        display: block;
        width: 100%;
    }

    .sarake-toggles .sarake { cursor: pointer; user-select: none; font-weight: 700; font-size: 0.85rem; padding: 10px 0; color: #333; }
    
    /* VIHREÄ BRÄNDIVÄRI SEULA- JA LAJITTELU-AKTIIVISUUTEEN */
    .kasvi-lista-rows.sort-suomi .nimi-suomi, .sarake-toggles .otsikko-suomi.active { color: #417430 !important; }
    .kasvi-lista-rows.sort-latina .nimi-latina, .sarake-toggles .otsikko-latina.active { color: #417430 !important; }
    .kasvi-lista-rows.sort-uhanalaisuus .nimi-uhanalaisuus, .sarake-toggles .otsikko-uhanalaisuus.active { color: #417430 !important; }
    .kasvi-lista-rows.sort-pisteet .nimi-pisteet, .sarake-toggles .otsikko-pisteet.active { color: #417430 !important; }
    
    .sarake-toggles .sarake .indikaattori { font-size: 0.65rem; margin-left: 5px; display: inline-block; vertical-align: middle; }
    .sarake-toggles .otsikko-pisteet .indikaattori { margin-left: 0; margin-right: 5px; }
    .kirjain-linkki:disabled { opacity: 0.3; cursor: not-allowed; }

    .u-suodatin-nappi:hover { border-color: #8c8f94 !important; }
    .u-suodatin-nappi.aktiivinen { background: #417430 !important; color: #fff !important; border-color: #417430 !important; }
    .u-suodatin-nappi.aktiivinen .u-maara-laskuri { color: #fff !important; opacity: 0.95; }
    </style>

    <script>
    (function() {
        const containerId = '#container-<?php echo $wrapper_slug; ?>';
        const container = document.querySelector(containerId);
        if (!container) return;

        const haku = container.querySelector('.kasvi-haku-input');
        const tyhjennysBtn = container.querySelector('.haku-tyhjennys-btn');
        const lista = container.querySelector('#lista-<?php echo $wrapper_slug; ?>');
        const kirjainNapit = container.querySelectorAll('.kirjain-linkki');
        const otsikkoTogglet = container.querySelectorAll('.sarake-toggles .sarake');
        const uSuodattimet = container.querySelectorAll('.u-suodatin-nappi');
        const laskuri = container.querySelector('.laskuri-maara');
        const rivit = Array.from(lista.querySelectorAll('.kasvi-rivi'));

        let nykyinenJarjestys = 'suomi';
        let aktiivisetUhanalaisuudet = [];

        function paivitaNappienLaskurit() {
            const termi = haku ? haku.value.toLowerCase() : '';
            
            uSuodattimet.forEach(nappi => {
                const acfArvo = nappi.getAttribute('data-u-acf-arvo');
                let count = 0;

                rivit.forEach(rivi => {
                    const nimi = (rivi.getAttribute('data-nimi') || '').toLowerCase();
                    const latina = (rivi.getAttribute('data-latina') || '').toLowerCase();
                    const synonyymit = (rivi.getAttribute('data-synonyymit') || '').toLowerCase();
                    const matchHaku = !termi || nimi.includes(termi) || latina.includes(termi) || synonyymit.includes(termi);
                    
                    if (matchHaku) {
                        try {
                            const lajinLuokat = JSON.parse(rivi.getAttribute('data-kaikki-uhanalaisuudet') || '[]');
                            if (lajinLuokat.includes(acfArvo)) {
                                count++;
                            }
                        } catch(e) {}
                    }
                });

                const maaraDiv = nappi.querySelector('.u-maara-laskuri');
                if (maaraDiv) maaraDiv.textContent = count;
            });
        }

        function suodataLajiLista() {
            const termi = haku ? haku.value.toLowerCase() : '';
            let nakyvissa = 0;

            rivit.forEach(rivi => {
                const nimi = (rivi.getAttribute('data-nimi') || '').toLowerCase();
                const latina = (rivi.getAttribute('data-latina') || '').toLowerCase();
                const synonyymit = (rivi.getAttribute('data-synonyymit') || '').toLowerCase();
                
                let lajinLuokat = [];
                try {
                    lajinLuokat = JSON.parse(rivi.getAttribute('data-kaikki-uhanalaisuudet') || '[]');
                } catch(e) {}

                const matchHaku = !termi || nimi.includes(termi) || latina.includes(termi) || synonyymit.includes(termi);
                const matchUhanalaisuus = aktiivisetUhanalaisuudet.length === 0 || aktiivisetUhanalaisuudet.some(luokka => lajinLuokat.includes(luokka));

                if (matchHaku && matchUhanalaisuus) {
                    rivi.style.display = 'grid';
                    nakyvissa++;
                } else {
                    rivi.style.display = 'none';
                }
            });

            if (laskuri) laskuri.textContent = nakyvissa;
            
            const vikaViesti = container.querySelector('.ei-suodatin-tuloksia');
            if (vikaViesti) vikaViesti.style.display = nakyvissa === 0 ? 'block' : 'none';

            paivitaAakkoset();
            paivitaNappienLaskurit();
        }

        function paivitaAakkoset() {
            if (kirjainNapit.length === 0) return;
            const loytyvatKirjaimet = new Set();
            
            rivit.forEach(rivi => {
                if (rivi.style.display !== 'none') {
                    const attr = nykyinenJarjestys === 'latina' ? 'data-letter-latina' : 'data-letter-suomi';
                    const k = rivi.getAttribute(attr);
                    if (k) loytyvatKirjaimet.add(k);
                }
            });

            kirjainNapit.forEach(btn => {
                const k = btn.getAttribute('data-letter');
                btn.disabled = !loytyvatKirjaimet.has(k);
            });
        }

        function jarjestaLista(kriteeri) {
            nykyinenJarjestys = kriteeri;
            lista.classList.remove('sort-suomi', 'sort-latina', 'sort-uhanalaisuus', 'sort-pisteet');
            lista.classList.add('sort-' + kriteeri);

            otsikkoTogglet.forEach(otsikko => {
                const indikaattori = otsikko.querySelector('.indikaattori');
                if (otsikko.getAttribute('data-sort') === kriteeri) {
                    otsikko.classList.add('active');
                    indikaattori.innerHTML = kriteeri === 'pisteet' ? '▲' : '▼'; // Pisteet järjestetään suurimmasta pienimpään
                } else {
                    otsikko.classList.remove('active');
                    indikaattori.innerHTML = '&nbsp;';
                }
            });

            rivit.sort((a, b) => {
                if (kriteeri === 'pisteet') {
                    return parseInt(b.getAttribute('data-pisteet') || '0', 10) - parseInt(a.getAttribute('data-pisteet') || '0', 10);
                } else if (kriteeri === 'uhanalaisuus') {
                    try {
                        const arrA = JSON.parse(a.getAttribute('data-kaikki-uhanalaisuudet') || '[]');
                        const arrB = JSON.parse(b.getAttribute('data-kaikki-uhanalaisuudet') || '[]');
                        return (arrA[0] || '').localeCompare(arrB[0] || '');
                    } catch(e) { return 0; }
                } else {
                    const valA = kriteeri === 'suomi' ? (a.getAttribute('data-nimi') || '') : (a.getAttribute('data-latina') || '');
                    const valB = kriteeri === 'suomi' ? (b.getAttribute('data-nimi') || '') : (b.getAttribute('data-latina') || '');
                    return valA.localeCompare(valB, 'fi');
                }
            });

            lista.innerHTML = '';
            rivit.forEach(rivi => lista.appendChild(rivi));
            paivitaAakkoset();
        }

        uSuodattimet.forEach(nappi => {
            nappi.addEventListener('click', function() {
                const acfArvo = this.getAttribute('data-u-acf-arvo');
                
                if (this.classList.contains('aktiivinen')) {
                    this.classList.remove('aktiivinen');
                    aktiivisetUhanalaisuudet = aktiivisetUhanalaisuudet.filter(item => item !== acfArvo);
                } else {
                    this.classList.add('aktiivinen');
                    aktiivisetUhanalaisuudet.push(acfArvo);
                }
                suodataLajiLista();
            });
        });

        if (haku) {
            haku.addEventListener('input', function() {
                if (tyhjennysBtn) tyhjennysBtn.style.display = this.value.length > 0 ? 'block' : 'none';
                suodataLajiLista();
            });

            if (tyhjennysBtn) {
                tyhjennysBtn.addEventListener('click', function() {
                    haku.value = '';
                    this.style.display = 'none';
                    suodataLajiLista();
                    haku.focus();
                });
            }
        }

        if (kirjainNapit.length > 0) {
            kirjainNapit.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const k = this.getAttribute('data-letter');
                    const attrName = nykyinenJarjestys === 'latina' ? 'data-letter-latina' : 'data-letter-suomi';
                    const kohde = rivit.find(r => r.getAttribute(attrName) === k && r.style.display !== 'none');
                    if (kohde) {
                        kohde.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        }

        otsikkoTogglet.forEach(otsikko => {
            otsikko.addEventListener('click', function() {
                jarjestaLista(this.getAttribute('data-sort'));
            });
        });

        suodataLajiLista();
    })();
    </script>

    <?php
    return ob_get_clean();
}
