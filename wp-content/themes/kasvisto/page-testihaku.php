<?php
/**
 * Template Name: Relevanssi Testisivu
 * * TÄMÄ ON TÄYSIN PUHDAS TESTISIVU ILMAN TEEMAN SOTKUJA.
 * TÄMÄN AVULLA VARMISTETAAN, ETTÄ RELEVANSSI AJAX KÄYNNISTYY.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <title>Relevanssi Live Search Testi</title>
    
    <?php wp_head(); ?>

    <style>
        body { font-family: sans-serif; background: #f0f4f0; padding: 50px; color: #333; }
        .testi-laatikko { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { font-size: 20px; margin-bottom: 5px; color: #2d5a27; }
        p { font-size: 13px; color: #666; margin-bottom: 25px; }
        
        /* Minimityylit hakukentälle, jotta se näyttää siistiltä */
        .relevanssi-testi-input {
            width: 100%;
            padding: 12px 20px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 25px;
            box-sizing: border-box;
            outline: none;
        }
        .relevanssi-testi-input:focus { border-color: #2d5a27; }

        /* Relevanssin oletusikkunan pakotettu tyylitys, jotta nähdään jos se aukeaa */
        .relevanssi-live-search-results {
            background: white !important;
            border: 1px solid #ccc !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15) !important;
            padding: 10px !important;
            color: black !important;
        }
    </style>
</head>
<body>

<div class="testi-laatikko">
    <h1>Relevanssi AJAX -testikenttä</h1>
    <p>Kirjoita kenttään jonkin kasvin nimi (esim. 3 merkkiä) ja katso, aukeaako tähän alle harmaa/valkoinen tuloslaatikko automaattisesti ilman Enterin painamista.</p>

<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input 
            type="search" 
            class="relevanssi-testi-input" 
            placeholder="Kirjoita tähän..." 
            value="" 
            name="s" 
            id="s"
            autocomplete="off" 
            data-rlvlive="true"
            data-rlvparent="form"
        >
    </form>
</div>

<?php wp_footer(); ?>

</body>
</html>