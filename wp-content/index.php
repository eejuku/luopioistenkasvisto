<?php get_header(); ?>

<main class="site-main container">
    <div class="content-layout">
        
        <article class="main-content">
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <header class="page-header">
                    <h1><?php the_title(); ?></h1>
                </header>
                
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            <?php endwhile; endif; ?>
        </article>

        <aside class="sidebar">
            <nav class="sub-navigation">
                <?php
                // Näytetään hierarkkinen valikko (sisarsivut ja lapsisivut)
                $ancestors = get_post_ancestors($post->ID);
                $root = (!empty($ancestors)) ? end($ancestors) : $post->ID;
                
                $child_args = array(
                    'child_of' => $root,
                    'title_li' => '<h2>' . get_the_title($root) . '</h2>',
                );
                ?>
                <ul>
                    <?php wp_list_pages($child_args); ?>
                </ul>
            </nav>
        </aside>

    </div>
</main>

<?php get_footer(); ?>