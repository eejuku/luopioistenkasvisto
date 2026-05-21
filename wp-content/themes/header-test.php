
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="kasvi-container header-main-row">
        
        <div class="site-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <img src="https://luopioistenkasvisto.fi/Kuvat/Kartat/logo2.jpg" alt="Luopioisten Kasvisto" class="main-logo">
            </a>
        </div>

        <div class="header-actions-stack">
            
            <nav class="secondary-nav">
                <a href="<?php echo home_url('/tietoa-sivustosta/'); ?>">Tietoa sivustosta</a>
                <a href="<?php echo home_url('/tietoa-tekijasta/'); ?>">Tietoa tekijästä</a>
                <a href="<?php echo home_url('/blogi/'); ?>">Blogi</a>
            </nav>
            
            <div class="header-search">
                <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                    <label>
                        <span class="screen-reader-text">Haku:</span>
                        <input type="search" class="search-field" placeholder="Etsi lajeja..." value="<?php echo get_search_query(); ?>" name="s" data-relevanssi-search="true" />
                    </label>
                    <button type="submit" class="search-submit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                    </form>
            </div>
        </div>
    </div>

    <div class="main-nav-bar">
        <div class="kasvi-container">
            <nav class="site-nav">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'main-menu',
                    'depth'          => 1,
                ));
                ?>
            </nav>
        </div>
    </div>
</header>


<!--
<header class="site-header">
    <div class="kasvi-container header-inner">

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

    </div>
</header> -->