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
    <div class="kasvi-container header-inner">
        
        <div class="site-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <span class="logo-text">Luopioisten Kasvisto</span>
            </a>
        </div>
        
<nav class="site-nav">
    <?php
    wp_nav_menu(array(
        'theme_location' => 'primary',
        'container'      => false,
        'menu_class'     => 'main-menu',
        'depth'          => 1, // Vain 1. taso ylös
        'fallback_cb'    => 'wp_page_menu', // Jos valikkoa ei ole luotu, näytetään sivut
        'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
    ));
    ?>
</nav>

    </div>
</header>