<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        /* Dynaamiset teemamuuttujat (Ylikirjoitetaan PHP:llä alempana) */
        :root {
            --header-bg-image: url('<?php echo get_template_directory_uri(); ?>/images/default-header.jpg');
            --theme-color: #77a464;
            --theme-color-dark: #2d5a27;
        }

        /* Headerin pääsäiliö toimii "ankkurina" itsenäisille elementeille */
        .site-header {
            position: relative;
            background-image: var(--header-bg-image);
            background-size: cover;
            background-position: center;
            border-bottom: 3px solid var(--theme-color);
            color: #fff;
            min-height: 180px; /* Korkeus, jotta elementeillä on tilaa loistaa */
            box-sizing: border-box;
        }

        /* Tumma peitekerros kuvan päällä */
        .site-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.35) 0%, rgba(0,0,0,0.65) 100%);
            z-index: 1;
        }

        /* Koko headerin kattava suojarasia, joka pitää elementit maksimileveydessä */
        .header-content-wrapper {
            position: relative;
            z-index: 2;
            max-width: 1400px; /* Laajennettu, jotta haku ja logo menevät enemmän reunoille */
            margin: 0 auto;
            padding: 0 30px; /* Marginaali reunoihin laajakuvassa */
            height: 180px; /* Täsmää headerin minimikorkeuden kanssa */
            box-sizing: border-box;
        }

        /* ==========================================================================
           1. LOGO (Täysin itsenäinen, vapaasti asemoitavissa)
           ========================================================================== */
        .site-logo {
            position: absolute;
            left: 30px; /* Asemoitu vapaasti vasempaan reunaan */
            bottom: 45px; /* Säädä tästä logon korkeutta alareunasta */
            display: flex;
            align-items: center;
            z-index: 5;
        }

        .logo-text {
            font-size: 1.9rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.4);
            line-height: 1.1;
        }

        .logo-text:hover { opacity: 0.7; }

        /* ==========================================================================
           2. OIKEA REUNA: Pikkunavigaatio ja Uusi Toimiva Haku
           ========================================================================== */
        .header-right-stack {
            position: absolute;
            right: 30px; /* Vedetty aivan oikeaan reunaan */
            top: 15px; /* Aivan yläreunassa */
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
            z-index: 5;
        }

        .top-secondary-nav {
            display: flex;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
            white-space: nowrap;
            flex-wrap: nowrap;
        }

        .top-secondary-nav li {
            white-space: nowrap;
            display: inline-block;
        }

        .top-secondary-nav a, .top-secondary-nav li a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
            transition: color 0.2s ease;
            border-bottom: 1px solid transparent;
            padding-bottom: 2px;
        }

        .top-secondary-nav a:hover, .top-secondary-nav li a:hover {
            color: #ffffff;
            border-bottom-color: #ffffff;
        }

        /* HAKUKENTÄN TYYLIT */
        .header-search-wrap {
            width: 250px;
            margin-top: 5px;
        }

        .modern-search-form {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            padding: 6px 14px;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }

        /* Suurennuslasi-ikoni */
        .modern-search-form::before {
            content: "🔍";
            font-size: 0.85rem;
            margin-right: 8px;
            opacity: 0.7;
        }

        /* Kun kenttä aktivoidaan */
        .modern-search-form:focus-within {
            background: rgba(255, 255, 255, 1);
            border-color: var(--theme-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .modern-search-form:focus-within::before {
            opacity: 1;
            filter: invert(1) brightness(0.2);
        }

        .modern-search-form .search-field {
            background: transparent !important;
            border: none !important;
            outline: none !important;
            font-size: 0.85rem !important;
            width: 100% !important;
            color: #ffffff !important;
            padding: 0 !important;
            font-family: inherit;
            box-shadow: none !important;
        }

        .modern-search-form:focus-within .search-field {
            color: #222222 !important;
        }

        .modern-search-form .search-field::placeholder {
            color: rgba(255, 255, 255, 0.65);
        }
        .modern-search-form:focus-within .search-field::placeholder {
            color: #999999;
        }

        /* ==========================================================================
           UUSI TYYLIKÄS LIVE-HAUN TULOSVALIKKO (RELEVANSSI AJAX)
           ========================================================================== */
        .relevanssi-live-search-results,
        div.relevanssi-live-search-results {
            width: 350px !important; /* Hieman leveämpi, jotta teksti ei ahtaudu */
            right: 0 !important;
            left: auto !important;
            margin-top: 8px !important;
            background: #ffffff !important;
            border-radius: 12px !important; /* Pehmeät pyöristetyt kulmat */
            box-shadow: 0 10px 30px rgba(0,0,0,0.18) !important; /* Moderni, syvä varjo */
            border: 1px solid rgba(0,0,0,0.06) !important;
            padding: 8px 0 !important; /* Sisältö ei osu reunoihin */
            z-index: 99999 !important;
            overflow: hidden !important;
            text-align: left !important;
            font-family: inherit !important;
        }

        /* Yksittäinen tulosrivi */
        .relevanssi-live-search-results .relevanssi-live-search-result-item,
        div.relevanssi-live-search-results div.relevanssi-live-search-result-item {
            padding: 12px 18px !important;
            border-bottom: 1px solid #f0f4f0 !important;
            transition: all 0.2s ease !important;
            background: transparent !important;
        }

        /* Poistetaan viimeiseltä riviltä turha alaviiva */
        .relevanssi-live-search-results .relevanssi-live-search-result-item:last-child {
            border-bottom: none !important;
        }

        /* Kun hiiri viedään rivin päälle tai sitä liikutetaan nuolinäppäimillä */
        .relevanssi-live-search-results .relevanssi-live-search-result-item:hover,
        .relevanssi-live-search-results .relevanssi-live-search-result-item.relevanssi-live-search-result-item-active {
            background-color: #f4f8f3 !important; /* Erittäin haalea luonnonvihreä tausta */
            cursor: pointer !important;
        }

        /* Tulosrivin linkki/teksti */
        .relevanssi-live-search-results .relevanssi-live-search-result-item a,
        div.relevanssi-live-search-results div.relevanssi-live-search-result-item a {
            color: #2d5a27 !important; /* Teeman tummanvihreä teksti otsikoille */
            text-decoration: none !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            display: block !important;
            line-height: 1.3 !important;
            margin-bottom: 3px !important;
        }

        /* Jos Relevanssi tulostaa pienen tekstikatkelman (excerpt) laatikkoon */
        .relevanssi-live-search-results .relevanssi-live-search-result-item .relevanssi-live-search-result-excerpt {
            color: #666666 !important;
            font-size: 0.8rem !important;
            font-weight: normal !important;
            line-height: 1.4 !important;
            display: block !important;
        }

        /* "Näytä kaikki tulokset" tai "Ei tuloksia" -viestilaatikko (jos käytössä) */
        .relevanssi-live-search-results .relevanssi-live-search-no-results,
        .relevanssi-live-search-results .relevanssi-live-search-show-all {
            padding: 12px 18px !important;
            text-align: center !important;
            font-size: 0.85rem !important;
            color: #666 !important;
            background: #fafafa !important;
        }
        
        .relevanssi-live-search-results .relevanssi-live-search-show-all a {
            color: var(--theme-color) !important;
            font-weight: bold !important;
            text-decoration: underline !important;
        }

        /* ==========================================================================
           4. MOBIILIOPTIMOINTI (Tämä oli jo koodissa, varmistetaan vain laatikon leveys)
           ========================================================================== */
        @media (max-width: 1024px) {
            /* ... (muut mobiilityylit pysyvät samoina) ... */

            .relevanssi-live-search-results,
            div.relevanssi-live-search-results {
                width: 100% !important;
                right: 0 !important;
                left: 0 !important;
                margin-top: 5px !important;
                box-sizing: border-box !important;
                border-radius: 8px !important;
            }
        }

        /* ==========================================================================
           3. KESKELLÄ/ALHAALLA: Päänavigaatio (Riippumaton muista)
           ========================================================================== */
        .site-nav {
            position: absolute;
            left: 50%;
            bottom: 20px; /* Alareunassa */
            transform: translateX(-50%); /* Täydellinen keskitys sivun leveyteen nähden */
            display: flex;
            justify-content: center;
            z-index: 4;
        }

        .main-menu, .site-nav ul {
            list-style: none !important;
            margin: 0;
            padding: 0;
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }

        .main-menu li { list-style: none !important; }

        .main-menu li a, .site-nav ul li a {
            display: inline-block;
            padding: 8px 20px;
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            white-space: nowrap;
            backdrop-filter: blur(4px);
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .main-menu li a:hover {
            background-color: rgba(255, 255, 255, 0.3) !important;
            border-color: #fff !important;
            color: #fff !important;
        }

        .main-menu li.current-menu-item a,
        .main-menu li.current_page_item a,
        .main-menu li.current-page-ancestor a,
        .main-menu li.current-menu-ancestor a,
        .main-menu li.current_page_ancestor a {
            background-color: var(--theme-color) !important;
            color: #fff !important;
            border-color: var(--theme-color) !important;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }

        /* ==========================================================================
           4. MOBIILIOPTIMOINTI (Puretaan absoluuttisuus ja järjestetään uusiksi)
           ========================================================================== */
        @media (max-width: 1024px) {
            .site-header {
                min-height: auto;
            }
            .header-content-wrapper {
                position: static;
                height: auto;
                padding: 20px 15px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
            /* Palautetaan elementit normaaliin jonoon mobiilissa */
            .header-right-stack, .site-logo, .site-nav {
                position: static;
                transform: none;
                align-items: center;
                text-align: center;
                width: 100%;
            }
            
            /* Järjestetään elementit mobiiliin */
            .site-logo {
                justify-content: center;
                order: 1; /* Logo ylimpänä */
            }
            
            .site-nav {
                order: 2; /* Päävalikko keskellä */
            }
            
            .header-right-stack {
                order: 3; /* Pikkunavigaatio ja haku alimpana */
                gap: 15px;
            }
            
            .top-secondary-nav {
                justify-content: center;
                width: 100%;
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .relevanssi-live-search-results,
            div.relevanssi-live-search-results {
                width: 100% !important;
                box-sizing: border-box;
            }
        }
    </style>

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
/**
 * DYNAAMINEN TEEMAN JA TAUSTAKUVAN TUNNISTUS
 */
$nykyinen_ryhma = '';

if (is_single()) {
    $nykyinen_ryhma = get_field('ryhma');
} else {
    $current_url = $_SERVER['REQUEST_URI'];
    if (strpos($current_url, 'putkilokasvit') !== false) {
        $nykyinen_ryhma = 'Putkilokasvit';
    } elseif (strpos($current_url, 'sammalet') !== false || strpos($current_url, 'sammal') !== false) {
        $nykyinen_ryhma = 'Sammalet';
    } elseif (strpos($current_url, 'jakalat') !== false || strpos($current_url, 'jakala') !== false) {
        $nykyinen_ryhma = 'Jäkälät';
    } elseif (strpos($current_url, 'piensienet') !== false || strpos($current_url, 'piensieni') !== false) {
        $nykyinen_ryhma = 'Piensienet';
    }
}

$bg_kuva = 'default-header.jpg';
$teemavari = '#77a464';
$teemavari_tumma = '#2d5a27';

switch (trim($nykyinen_ryhma)) {
    case 'Putkilokasvit':
        $bg_kuva = 'putkilokasvit-bg.jpg';
        $teemavari = '#4a7c44';
        $teemavari_tumma = '#2d5a27';
        break;
    case 'Sammalet':
        $bg_kuva = 'sammalet-bg.jpg';
        $teemavari = '#5d8a39';
        $teemavari_tumma = '#3c5c22';
        break;
    case 'Jäkälät':
        $bg_kuva = 'jakalat-bg.jpg';
        $teemavari = '#6f8980';
        $teemavari_tumma = '#475b54';
        break;
    case 'Piensienet':
        $bg_kuva = 'piensienet-bg.jpg';
        $teemavari = '#a0522d';
        $teemavari_tumma = '#6e361c';
        break;
}

$header_kuva_url = get_template_directory_uri() . '/images/' . $bg_kuva;
?>

<style>
    :root {
        --theme-color: <?php echo $teemavari; ?>;
        --theme-color-dark: <?php echo $teemavari_tumma; ?>;
    }
    
    .site-header {
        background-image: url(<?php echo esc_url($header_kuva_url); ?>) !important;
    }
</style>

<header class="site-header">
    <div class="header-content-wrapper">
        
        <div class="site-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <span class="logo-text">Luopioisten<br>Kasvisto</span>
            </a>
        </div>

        <nav class="site-nav">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'main-menu',
                'depth'          => 1,
                'fallback_cb'    => 'wp_page_menu',
                'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            ));
            ?>
        </nav>

        <div class="header-right-stack">
            <ul class="top-secondary-nav">
                <li><a href="<?php echo esc_url(home_url('/sivustosta/')); ?>">Sivustosta</a></li>
                <li><a href="<?php echo esc_url(home_url('/kirjoittajasta/')); ?>">Kirjoittajasta</a></li>
                <li><a href="https://luopioistenkasvisto.fi/blogi/" target="_blank">Tuomon kuva ja sana -blogi</a></li>
            </ul>
            
            <div class="header-search-wrap">
                <form role="search" method="get" class="modern-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <input 
                        type="search" 
                        class="search-field" 
                        placeholder="Etsi kasveja tai sieniä..." 
                        value="<?php echo get_search_query(); ?>" 
                        name="s" 
                        id="s" 
                        autocomplete="off" 
                        data-rlvlive="true" 
                        data-rlvparent="form"
                    >
                </form>
            </div>
        </div>

    </div>
</header>


<!-- <!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        /* Dynaamiset teemamuuttujat (Ylikirjoitetaan PHP:llä alempana) */
        :root {
            --header-bg-image: url('<?php echo get_template_directory_uri(); ?>/images/default-header.jpg');
            --theme-color: #77a464;
            --theme-color-dark: #2d5a27;
        }

        /* Headerin pääsäiliö toimii "ankkurina" itsenäisille elementeille */
        .site-header {
            position: relative;
            background-image: var(--header-bg-image);
            background-size: cover;
            background-position: center;
            border-bottom: 3px solid var(--theme-color);
            color: #fff;
            min-height: 180px; /* Hieman lisää korkeutta, jotta elementeillä on tilaa loistaa */
            box-sizing: border-box;
        }

        /* Tumma peitekerros kuvan päällä */
        .site-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.35) 0%, rgba(0,0,0,0.65) 100%);
            z-index: 1;
        }

        /* Koko headerin kattava suojarasia, joka pitää elementit maksimileveydessä */
        .header-content-wrapper {
            position: relative;
            z-index: 2;
            max-width: 1400px; /* Laajennettu, jotta haku ja logo menevät enemmän reunoille */
            margin: 0 auto;
            padding: 0 30px; /* Marginaali reunoihin laajakuvassa */
            height: 180px; /* Täsmää headerin minimikorkeuden kanssa */
            box-sizing: border-box;
        }

        /* ==========================================================================
           1. LOGO (Täysin itsenäinen, vapaasti asemoitavissa)
           ========================================================================== */
        .site-logo {
            position: absolute;
            left: 30px; /* Asemoitu vapaasti vasempaan reunaan */
            bottom: 45px; /* Säädä tästä logon korkeutta alareunasta */
            display: flex;
            align-items: center;
            z-index: 5;
        }

        .logo-text {
            font-size: 1.9rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.4);
            line-height: 1.1;
        }

        .logo-text:hover { opacity: 0.7; }

        /* ==========================================================================
           2. OIKEA REUNA: Pikkunavigaatio ja Haku (Vedetty aivan oikeaan laitaan)
           ========================================================================== */
        .header-right-stack {
            position: absolute;
            right: 30px; /* Vedetty aivan oikeaan reunaan */
            top: 15px; /* Aivan yläreunassa */
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
            z-index: 5;
        }

        .top-secondary-nav {
            display: flex;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
            white-space: nowrap;
            flex-wrap: nowrap;
        }

        .top-secondary-nav li {
            white-space: nowrap;
            display: inline-block;
        }

        .top-secondary-nav a, .top-secondary-nav li a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
            transition: color 0.2s ease;
            border-bottom: 1px solid transparent;
            padding-bottom: 2px;
        }

        .top-secondary-nav a:hover, .top-secondary-nav li a:hover {
            color: #ffffff;
            border-bottom-color: #ffffff;
        }

        .header-search-wrap {
            width: 250px;
            margin-top: 5px;
        }

        .modern-search-form {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            padding: 6px 14px;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }

        .modern-search-form::before {
            content: "🔍";
            font-size: 0.85rem;
            margin-right: 8px;
            opacity: 0.7;
        }

        .modern-search-form:focus-within {
            background: rgba(255, 255, 255, 1);
            border-color: var(--theme-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .modern-search-form:focus-within::before {
            opacity: 1;
            filter: invert(1) brightness(0.2);
        }

        .modern-search-form .search-field {
            background: transparent;
            border: none;
            outline: none;
            font-size: 0.85rem;
            width: 100%;
            color: #ffffff;
            padding: 0;
            font-family: inherit;
        }

        .modern-search-form:focus-within .search-field {
            color: #222222;
        }

        .modern-search-form .search-field::placeholder {
            color: rgba(255, 255, 255, 0.65);
        }
        .modern-search-form:focus-within .search-field::placeholder {
            color: #999999;
        }

        .relevanssi-live-search-results {
            width: 280px !important;
            right: 0 !important;
            left: auto !important;
            color: #333;
        }

        /* ==========================================================================
           3. KESKELLÄ/ALHAALLA: Päänavigaatio (Riippumaton muista)
           ========================================================================== */
        .site-nav {
            position: absolute;
            left: 50%;
            bottom: 20px; /* Alareunassa */
            transform: translateX(-50%); /* Täydellinen keskitys sivun leveyteen nähden */
            display: flex;
            justify-content: center;
            z-index: 4;
        }

        .main-menu, .site-nav ul {
            list-style: none !important;
            margin: 0;
            padding: 0;
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }

        .main-menu li { list-style: none !important; }

        .main-menu li a, .site-nav ul li a {
            display: inline-block;
            padding: 8px 20px;
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            white-space: nowrap;
            backdrop-filter: blur(4px);
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .main-menu li a:hover {
            background-color: rgba(255, 255, 255, 0.3) !important;
            border-color: #fff !important;
            color: #fff !important;
        }

        .main-menu li.current-menu-item a,
        .main-menu li.current_page_item a,
        .main-menu li.current-page-ancestor a,
        .main-menu li.current-menu-ancestor a,
        .main-menu li.current_page_ancestor a {
            background-color: var(--theme-color) !important;
            color: #fff !important;
            border-color: var(--theme-color) !important;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }

        /* ==========================================================================
           4. MOBIILIOPTIMOINTI (Puretaan absoluuttisuus, jotta toimii puhelimilla)
           ========================================================================== */
        @media (max-width: 1024px) {
            .site-header {
                min-height: auto;
            }
            .header-content-wrapper {
                position: static;
                height: auto;
                padding: 20px 15px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
            /* Palautetaan elementit normaaliin jonoon mobiilissa */
            .header-right-stack, .site-logo, .site-nav {
                position: static;
                transform: none;
                align-items: center;
                text-align: center;
                width: 100%;
            }
            
            /* MÄÄRITETÄÄN MOBIILIJÄRJESTYS ORDER-OMINAISUUDELLA */
            .site-logo {
                justify-content: center;
                order: 1; /* Logo ylimpänä */
            }
            
            .site-nav {
                order: 3; /* Päävalikko alinna */
            }
            
            .header-right-stack {
                order: 2; /* Pikkunavigaatio ja haku keskellä */
                gap: 15px;
            }
            
            .top-secondary-nav {
                justify-content: center;
                width: 100%;
                overflow-x: auto;
                padding-bottom: 4px;
            }
            .site-logo {
                justify-content: center;
            }
        }
    </style>

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
/**
 * DYNAAMINEN TEEMAN JA TAUSTAKUVAN TUNNISTUS
 */
$nykyinen_ryhma = '';

if (is_single()) {
    $nykyinen_ryhma = get_field('ryhma');
} else {
    $current_url = $_SERVER['REQUEST_URI'];
    if (strpos($current_url, 'putkilokasvit') !== false) {
        $nykyinen_ryhma = 'Putkilokasvit';
    } elseif (strpos($current_url, 'sammalet') !== false || strpos($current_url, 'sammal') !== false) {
        $nykyinen_ryhma = 'Sammalet';
    } elseif (strpos($current_url, 'jakalat') !== false || strpos($current_url, 'jakala') !== false) {
        $nykyinen_ryhma = 'Jäkälät';
    } elseif (strpos($current_url, 'piensienet') !== false || strpos($current_url, 'piensieni') !== false) {
        $nykyinen_ryhma = 'Piensienet';
    }
}

$bg_kuva = 'default-header.jpg';
$teemavari = '#77a464';
$teemavari_tumma = '#2d5a27';

switch (trim($nykyinen_ryhma)) {
    case 'Putkilokasvit':
        $bg_kuva = 'putkilokasvit-bg.jpg';
        $teemavari = '#4a7c44';
        $teemavari_tumma = '#2d5a27';
        break;
    case 'Sammalet':
        $bg_kuva = 'sammalet-bg.jpg';
        $teemavari = '#5d8a39';
        $teemavari_tumma = '#3c5c22';
        break;
    case 'Jäkälät':
        $bg_kuva = 'jakalat-bg.jpg';
        $teemavari = '#6f8980';
        $teemavari_tumma = '#475b54';
        break;
    case 'Piensienet':
        $bg_kuva = 'piensienet-bg.jpg';
        $teemavari = '#a0522d';
        $teemavari_tumma = '#6e361c';
        break;
}

$header_kuva_url = get_template_directory_uri() . '/images/' . $bg_kuva;
?>

<style>
    :root {
        --theme-color: <?php echo $teemavari; ?>;
        --theme-color-dark: <?php echo $teemavari_tumma; ?>;
    }
    
    .site-header {
        background-image: url(<?php echo esc_url($header_kuva_url); ?>) !important;
    }
</style>

<header class="site-header">
    <div class="header-content-wrapper">
        
        <div class="site-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <span class="logo-text">Luopioisten<br>Kasvisto</span>
            </a>
        </div>

        <nav class="site-nav">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'main-menu',
                'depth'          => 1,
                'fallback_cb'    => 'wp_page_menu',
                'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            ));
            ?>
        </nav>

        <div class="header-right-stack">
            <ul class="top-secondary-nav">
                <li><a href="<?php echo esc_url(home_url('/sivustosta/')); ?>">Sivustosta</a></li>
                <li><a href="<?php echo esc_url(home_url('/kirjoittajasta/')); ?>">Kirjoittajasta</a></li>
                <li><a href="https://luopioistenkasvisto.fi/blogi/" target="_blank">Tuomon kuva ja sana -blogi</a></li>
            </ul>
            
<div class="header-search-wrap">
                <form role="search" method="get" class="modern-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <input 
                        type="search" 
                        class="search-field" 
                        placeholder="Etsi kasveja tai sieniä..." 
                        value="<?php echo get_search_query(); ?>" 
                        name="s" 
                        id="s" 
                        autocomplete="off" 
                        data-rlvlive="true" 
                        data-rlvparent="form"
                    >
                </form>
            </div>
        </div>

    </div>
</header> -->






<!-- <!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
        /* Dynaamiset teemamuuttujat (Ylikirjoitetaan PHP:llä alempana) */
        :root {
            --header-bg-image: url('<?php echo get_template_directory_uri(); ?>/images/default-header.jpg');
            --theme-color: #77a464;
            --theme-color-dark: #2d5a27;
        }

        /* Headerin pääsäiliö */
        .site-header {
            position: relative;
            background-image: var(--header-bg-image);
            background-size: cover;
            background-position: center;
            padding: 30px 0 20px 0;
            border-bottom: 3px solid var(--theme-color);
            color: #fff;
            min-height: 140px;
            display: flex;
            align-items: flex-end;
        }

        /* Tumma peitekerros kuvan päällä, jotta tekstit ja valkoinen logo erottuvat taustasta upeasti */
        .site-header::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.65) 100%);
            z-index: 1;
        }

        /* Kaikki sisältö tuodaan z-indexillä peitekerroksen päälle */
        .header-inner-grid {
            position: relative;
            z-index: 2;
            width: 100%;
            display: grid;
            grid-template-columns: 300px 1fr 300px;
            align-items: flex-end;
            gap: 20px;
        }

        /* 1. VASEN REUNA: Logo */
        .site-logo {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            height: 100%;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }

        /* Logo tekstinä siististi transparenttina / valkoisena */
        .logo-text {
            font-size: 1.9rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.4);
            line-height: 1.1;
        }

 .logo-text:hover {
            /* color: #e8e8e8; */
            opacity: 0.7;
        }

        /* 2. KESKELLÄ: Päänavigaatio */
        .site-nav {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .main-menu, .site-nav ul {
            list-style: none !important;
            margin: 0;
            padding: 0;
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
        }

        .main-menu li { list-style: none !important; }

        /* Läpinäkyvät modernit painikkeet, jotka muuttuvat aktiivisen osion väriseksi */
        .main-menu li a, .site-nav ul li a {
            display: inline-block;
            padding: 8px 20px;
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            white-space: nowrap;
            backdrop-filter: blur(4px);
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Hover-efekti */
        .main-menu li a:hover {
            background-color: rgba(255, 255, 255, 0.3) !important;
            border-color: #fff !important;
            color: #fff !important;
        }

        /* Aktiivinen valintapainike käyttää PHP:llä määritettyä teemaväriä */
        .main-menu li.current-menu-item a,
        .main-menu li.current_page_item a,
        .main-menu li.current-page-ancestor a,
        .main-menu li.current-menu-ancestor a,
        .main-menu li.current_page_ancestor a {
            background-color: var(--theme-color) !important;
            color: #fff !important;
            border-color: var(--theme-color) !important;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }

        /* 3. OIKEA YLÄREUNA: Horisontaalinen pikkunavigaatio */
        .top-secondary-nav {
            position: absolute;
            top: -70px;
            right: 0;
            display: flex;
            gap: 20px;
            list-style: none;
            margin-right: 10px;
            padding: 0;
        }

        .top-secondary-nav a, .top-secondary-nav li a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
            transition: color 0.2s ease;
            border-bottom: 1px solid transparent;
            padding-bottom: 2px;
        }

        .top-secondary-nav a:hover, .top-secondary-nav li a:hover {
            color: #ffffff;
            border-bottom-color: #ffffff;
        }

        /* Responsiivisuussäädöt mobiiliin ja pikkunäytöille */
        @media (max-width: 1024px) {
            .header-inner-grid {
                grid-template-columns: 1fr;
                align-items: center;
                text-align: center;
            }
            .top-secondary-nav {
                position: static;
                justify-content: center;
                margin-bottom: 15px;
                width: 100%;
            }
            .site-logo {
                justify-content: center;
                width: 100%;
            }
            .site-nav {
                justify-content: center;
                width: 100%;
            }
        }
    </style>

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
/**
 * DYNAAMINEN TEEMAN JA TAUSTAKUVAN TUNNISTUS
 * Tunnistaa automaattisesti ollaanko Putkilokasveissa, Sammalissa, Jäkälissä vai Piensienissä.
 */
$nykyinen_ryhma = '';

// 1. Jos ollaan yksittäisessä kasvikortissa, luetaan ACF-kenttä 'ryhma'
if (is_single()) {
    $nykyinen_ryhma = get_field('ryhma');
} 
// 2. Jos ollaan arkisto- tai listaussivulla, tunnistetaan ryhmä URL-osoitteesta tai sivun otsikosta
else {
    $current_url = $_SERVER['REQUEST_URI'];
    if (strpos($current_url, 'putkilokasvit') !== false) {
        $nykyinen_ryhma = 'Putkilokasvit';
    } elseif (strpos($current_url, 'sammalet') !== false || strpos($current_url, 'sammal') !== false) {
        $nykyinen_ryhma = 'Sammalet';
    } elseif (strpos($current_url, 'jakalat') !== false || strpos($current_url, 'jakala') !== false) {
        $nykyinen_ryhma = 'Jäkälät';
    } elseif (strpos($current_url, 'piensienet') !== false || strpos($current_url, 'piensieni') !== false) {
        $nykyinen_ryhma = 'Piensienet';
    }
}

// Oletusarvot (Jos ollaan etusivulla tai yleisillä infosivuilla)
$bg_kuva = 'default-header.jpg';
$teemavari = '#77a464'; // Alkuperäinen vihreäsi
$teemavari_tumma = '#2d5a27';

// Vaihdetaan kuvat ja värit tunnistetun pääryhmän mukaan
switch (trim($nykyinen_ryhma)) {
    case 'Putkilokasvit':
        $bg_kuva = 'putkilokasvit-bg.jpg';
        $teemavari = '#4a7c44'; // Syvä metsänvihreä
        $teemavari_tumma = '#2d5a27';
        break;
        
    case 'Sammalet':
        $bg_kuva = 'sammalet-bg.jpg';
        $teemavari = '#5d8a39'; // Tuore sammaleenvihreä
        $teemavari_tumma = '#3c5c22';
        break;
        
    case 'Jäkälät':
        $bg_kuva = 'jakalat-bg.jpg';
        $teemavari = '#6f8980'; // Jäkälän harmahtavan vihreä / rusehtava
        $teemavari_tumma = '#475b54';
        break;
        
    case 'Piensienet':
        $bg_kuva = 'piensienet-bg.jpg';
        $teemavari = '#a0522d'; // Sienen rusehtava / lämmin kupari
        $teemavari_tumma = '#6e361c';
        break;
}

// Luodaan valmis polku teeman images-kansioon tallennettaville kuville
$header_kuva_url = get_template_directory_uri() . '/images/' . $bg_kuva;
?>

<style>
    :root {
        --theme-color: <?php echo $teemavari; ?>;
        --theme-color-dark: <?php echo $teemavari_tumma; ?>;
    }
    
    .site-header {
        background-image: url(<?php echo esc_url($header_kuva_url); ?>) !important;
    }
</style>

<header class="site-header">
    <div class="kasvi-container header-inner-grid">
        
        <div class="site-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <span class="logo-text">Luopioisten<br>Kasvisto</span>
            </a>
        </div>

        <nav class="site-nav">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'main-menu',
                'depth'          => 1,
                'fallback_cb'    => 'wp_page_menu',
                'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            ));
            ?>
        </nav>

        <ul class="top-secondary-nav">
            <li><a href="<?php echo esc_url(home_url('/sivustosta/')); ?>">Sivustosta</a></li>
            <li><a href="<?php echo esc_url(home_url('/kirjoittajasta/')); ?>">Kirjoittajasta</a></li>
            <li><a href="https://luopioistenkasvisto.fi/blogi/" target="_blank">Tuomon kuva ja sana -blogi</a></li>
        </ul>

    </div>
</header> -->