<?php get_header(); ?>


<main class="container page-layout">
    <aside class="sidebar-left">
        <?php
        // Haetaan nykyisen sivun ylin emosivu
        $ancestors = get_post_ancestors($post->ID);
        $top_level = (!empty($ancestors)) ? end($ancestors) : $post->ID;

        // Tulostetaan listaus kaikista tämän "perheen" sivuista
        echo '<nav class="side-nav">';
        echo '<h3><a href="' . get_permalink($top_level) . '">' . get_the_title($top_level) . '</a></h3>';
        echo '<ul>';
        wp_list_pages(array(
            'child_of' => $top_level,
            'title_li' => '',
            'depth'    => 0 // 0 tarkoittaa että se hakee kaikki tasot
        ));
        echo '</ul>';
        echo '</nav>';
        ?>
    </aside>

    <article class="content-area">
        <h1><?php the_title(); ?></h1>
        <?php the_content(); ?>
    
<div class="content-section">
    <small>
        Sivu luotu: <?php echo get_the_date(); ?>.
        <?php if (get_the_modified_time() != get_the_time()) : ?>
            Päivitetty viimeksi asdlkaj lskaj : <?php the_modified_date(); ?>.
        <?php endif; ?>
    </small>
</div>

    </article>
</main>

<?php get_footer(); ?>