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

    <?php // Listat tulevat mu-pluginista (mu-plugins/kasvisto/uudet-ja-muokatut.php). ?>
    <?php if ( function_exists('kasvisto_etusivu_lajilista') ) : ?>
    <div class="front-dynamic-grid">
        <div class="dynamic-card">
            <h2>Uusimmat lisäykset</h2>
            <?php echo kasvisto_etusivu_lajilista('uudet', 5); ?>
        </div>

        <div class="dynamic-card">
            <h2>Viimeksi muokatut</h2>
            <?php echo kasvisto_etusivu_lajilista('muokatut', 5); ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php get_footer(); ?>