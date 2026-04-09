<?php
/**
 * Template Name: Kasvien Lisäryhmät
 */

get_header(); ?>

<div class="container page-layout">
    
    <aside class="sidebar-left">
        <nav class="side-nav">
            <?php
            // Näytetään sivunavigaatio kuten muillakin sivuilla
            $top_level = ( !empty($post->post_parent) ) ? array_reverse(get_post_ancestors($post->ID))[0] : $post->ID;
            wp_list_pages(array(
                'child_of' => $top_level,
                'title_li' => '<h3>' . get_the_title($top_level) . '</h3>',
                'depth'    => 0
            ));
            ?>
        </nav>
    </aside>

    <main class="content-area">
        <header class="kasvi-header">
            <h1><?php the_title(); ?></h1>
            <?php the_content(); // Mahdollinen esittelyteksti sivulle ?>
        </header>

        <div class="kasvi-lista-grid">
            <?php
            // Määritetään mitä ryhmää etsitään sivun slugin perusteella
            // Esim. jos sivun osoite on /vieraslajit/, etsitään arvoa "Vieraslajit"
            $current_slug = $post->post_name;
            
            // Tehdään mäppäys slugin ja ACF-arvon välillä
            $ryhma_map = array(
                '100-yleisinta'         => '100 yleisintä',
                'vieraslajit'           => 'Vieraslajit',
                'karkulaiset-jaanteet'  => 'Karkulaiset, jäänteet',
                'talventorrottajat'     => 'Talventörröttäjät'
            );

            $etsittava_arvo = $ryhma_map[$current_slug];

            $args = array(
                'post_type'      => 'kasvi', // Vaihda tähän oikea custom post type nimi
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'meta_query'     => array(
                    array(
                        'key'     => 'lisaryhmat', // ACF-kentän nimi
                        'value'   => $etsittava_arvo,
                        'compare' => 'LIKE', // LIKE on turvallinen jos on useita valintoja
                    ),
                ),
            );

            $query = new WP_Query($args);

            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) : $query->the_post(); 
                    $tieteellinen = get_field('tieteellinen_nimi');
                    ?>
                    <div class="kasvi-kortti">
                        <a href="<?php the_permalink(); ?>">
                            <div class="kasvi-nimi"><?php the_title(); ?></div>
                            <div class="kasvi-tieteellinen"><?php echo esc_html($tieteellinen); ?></div>
                        </a>
                    </div>
                <?php endwhile;
                wp_reset_postdata();
            else :
                echo '<p>Tähän ryhmään ei ole vielä merkitty kasveja.</p>';
            endif;
            ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>