<article class="search-result-item">
    <span class="result-meta">
        <?php echo (get_post_type() === 'kasvi') ? 'Kasvikortti' : 'Sivusto'; ?>
    </span>
    
    <h2 class="result-title">
        <a href="<?php the_permalink(); ?>">
            <?php the_title(); ?>
            <?php if ( get_post_type() === 'kasvi' ) : ?>
                <span class="result-scientific">
                    <?php echo get_post_meta(get_the_ID(), 'tieteellinen_nimi', true); ?>
                </span>
            <?php endif; ?>
        </a>
    </h2>

    <?php if ( get_post_type() !== 'kasvi' ) : ?>
        <div class="result-excerpt" style="font-size: 13px; color: #666;">
            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
        </div>
    <?php endif; ?>
</article>