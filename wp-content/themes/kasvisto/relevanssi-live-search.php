<?php if ( have_posts() ) : ?>
    <?php while ( have_posts() ) : the_post(); ?>
        <div class="live-search-result-item">
            <a href="<?php the_permalink(); ?>">
                <span class="live-result-title"><?php the_title(); ?></span>
                <?php if ( get_post_type() === 'kasvi' ) : ?>
                    <span style="font-style: italic; color: #666; font-size: 13px; margin-left: 5px;">
                        <?php echo get_post_meta(get_the_ID(), 'tieteellinen_nimi', true); ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    <?php endwhile; ?>
<?php else : ?>
    <div style="padding: 15px;">Ei osumia.</div>
<?php endif; ?>