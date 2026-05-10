<?php get_header(); ?>
<!--
<section class="hero-banner">
    <div class="container">
        <h1>Tervetuloa Luopioisten kasvistoon!</h1>
      
    </div>
</section>
-->
<main class="container main-content">
    
    <div class="entry-content">
        <?php 
        if ( have_posts() ) : 
            while ( have_posts() ) : the_post(); 
                the_content(); 
            endwhile; 
        endif; 
        ?>
    </div>

    <hr class="section-divider">

    <div class="front-dynamic-grid">
        <div class="dynamic-card">
            <h2>Uusimmat lisäykset</h2>
            <div class="latest-plants">
                <?php 
                $latest_args = array(
                    'post_type' => 'kasvi',
                    'posts_per_page' => 5,
                    'orderby' => 'date',
                    'order' => 'DESC'
                );
                $latest_query = new WP_Query($latest_args);
                if ($latest_query->have_posts()) :
                    echo '<ul class="latest-list">';
                    while ($latest_query->have_posts()) : $latest_query->the_post();
                        echo '<li><a href="'.get_permalink().'">'.get_the_title().'</a></li>';
                    endwhile;
                    echo '</ul>';
                endif;
                wp_reset_postdata();
                ?>
            </div>
        </div>

        <div class="dynamic-card">
            <h2>Pikalinkit</h2>
            <ul class="quick-links">
                <li><a href="archive-kasvi.php">Selaa kaikkia kasveja →</a></li>
                <li><a href="/vieraslajit/">Vieraslajit Luopioisissa →</a></li>
                <li><a href="/tietoa-projektista/">Lue lisää hankkeesta →</a></li>
            </ul>
        </div>
    </div>
</main>

<?php get_footer(); ?>