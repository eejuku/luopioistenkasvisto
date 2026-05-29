<?php get_header(); ?>

<style>
    .search-container { max-width: 900px; margin: 40px auto; padding: 0 20px; font-family: sans-serif; }
    .search-header { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #eee; }
    .search-header h1 { font-size: 24px; color: #333; margin-bottom: 20px; }
    
    .search-page-form { margin-bottom: 40px; display: flex; gap: 10px; }
    .search-page-form input[type="search"] { flex-grow: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
    .search-page-form button { background: #2d5a27; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }

    .search-results-list { list-style: none; padding: 0; margin: 0; }
    
    /* UUSI JOUSTAVA RAKENNE KUVAA VARTEN */
    .search-result-item { 
        display: flex;
        gap: 20px;
        align-items: flex-start;
        padding: 20px 0; 
        border-bottom: 1px solid #eee; 
        transition: opacity 0.3s ease; 
    }
    
    /* Hakutuloksen kuvalaatikko */
    .result-thumb-wrapper {
        flex-shrink: 0;
        width: 100px;
        height: 100px;
        border-radius: 6px;
        overflow: hidden;
        background: #f5f5f5;
        border: 1px solid #e5e5e5;
    }
    .result-thumb-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* Hakutuloksen tekstisisältö */
    .result-content-wrapper {
        flex-grow: 1;
    }
    
    .result-meta { font-size: 10px; text-transform: uppercase; color: #888; margin-bottom: 4px; display: block; }
    .result-title { font-size: 18px; margin: 0; line-height: 1.3; }
    .result-title a { color: #2d5a27; text-decoration: none; }
    .result-scientific { font-style: italic; color: #777; font-size: 14px; margin-left: 8px; font-weight: normal; }
    .result-excerpt { font-size: 14px; color: #555; margin-top: 6px; line-height: 1.5; }

    /* Lataa lisää -painike */
    .load-more-wrapper { text-align: center; margin-top: 40px; }
    #load-more-btn { 
        background: #f4f4f4; color: #2d5a27; border: 1px solid #ddd; padding: 12px 30px; 
        border-radius: 25px; cursor: pointer; font-weight: bold; transition: all 0.2s;
    }
    #load-more-btn:hover { background: #2d5a27; color: white; border-color: #2d5a27; }
    #load-more-btn:disabled { opacity: 0.5; cursor: default; }

    /* Mobiilimuotoilu, jotta kuva ja teksti toimivat pienellä näytöllä */
    @media (max-width: 600px) {
        .search-result-item { gap: 15px; }
        .result-thumb-wrapper { width: 70px; height: 70px; }
        .result-title { font-size: 16px; }
        .result-scientific { display: block; margin-left: 0; margin-top: 2px; }
    }
</style>

<div class="search-container">
    <header class="search-header">
        <h1><?php printf( 'Haku: %s', '<span>' . get_search_query() . '</span>' ); ?></h1>
        <form role="search" method="get" class="search-page-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
            <input type="search" value="<?php echo get_search_query(); ?>" name="s" placeholder="Etsi lajia tai sisältöä...">
            <button type="submit">Hae</button>
        </form>
        <p style="font-size: 13px; color: #999;">Löytyi <span id="total-results"><?php echo $wp_query->found_posts; ?></span> osumaa.</p>
    </header>

    <div id="search-results-container" class="search-results-list">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
            <?php include( locate_template( 'template-parts/search-item.php' ) ); ?>
        <?php endwhile; endif; ?>
    </div>

    <?php if ( $wp_query->max_num_pages > 1 ) : ?>
        <div class="load-more-wrapper">
            <button id="load-more-btn" data-page="1" data-max="<?php echo $wp_query->max_num_pages; ?>" data-query="<?php echo get_search_query(); ?>">
                Lataa lisää tuloksia
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('load-more-btn');
    if (!btn) return;

    btn.addEventListener('click', function() {
        let currentPage = parseInt(btn.getAttribute('data-page'));
        let maxPages = parseInt(btn.getAttribute('data-max'));
        let searchQuery = btn.getAttribute('data-query');
        
        btn.disabled = true;
        btn.textContent = 'Ladataan...';

        fetch(`${window.location.pathname}?s=${searchQuery}&paged=${currentPage + 1}`)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newItems = doc.querySelectorAll('.search-result-item');
                const container = document.getElementById('search-results-container');

                newItems.forEach(item => container.appendChild(item));

                btn.setAttribute('data-page', currentPage + 1);
                btn.disabled = false;
                btn.textContent = 'Lataa lisää tuloksia';

                if (currentPage + 1 >= maxPages) {
                    btn.parentElement.remove();
                }
            })
            .catch(err => {
                console.error('Haku epäonnistui', err);
                btn.textContent = 'Virhe ladattaessa.';
            });
    });
});
</script>

<?php get_footer(); ?>