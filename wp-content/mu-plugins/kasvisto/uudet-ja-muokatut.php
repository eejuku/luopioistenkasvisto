<?php
/**
 * Shortcode [kasvisto_viimeisimmat] – uusimmat / viimeksi muokatut kortit.
 *
 * Siirretty Code Snippets -snippetistä #9 (vienti 10.11.2026).
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function kasvisto_sc_viimeisimmat($atts) {
    $a = shortcode_atts([
        'tyyppi' => 'uudet', // 'uudet' tai 'muokatut'
        'maara'  => 10,
    ], $atts);

    // Määritetään järjestys tyypin mukaan
    $orderby = ($a['tyyppi'] === 'muokatut') ? 'modified' : 'date';
    $otsikko = ($a['tyyppi'] === 'muokatut') ? 'Viimeksi päivitetyt kortit' : 'Uusimmat kasvikortit';

    $args = [
        'post_type'      => 'kasvi',
        'posts_per_page' => (int)$a['maara'],
        'orderby'        => $orderby,
        'order'          => 'DESC'
    ];

    $query = new WP_Query($args);
    
    ob_start();
    ?>

    <div class="viimeisimmat-kasvit-container" style="margin-bottom: 30px;">
        <h3 style="border-bottom: 2px solid #4a7c44; padding-bottom: 10px;"><?php echo esc_html($otsikko); ?></h3>
        
        <div class="kasvi-lista-rows">
            <div class="lista-otsikot" style="display: flex; font-weight: bold; border-bottom: 1px solid #ccc; padding: 5px 0; font-size: 0.9rem;">
                <div style="flex: 2;">Nimi</div>
                <div style="flex: 2;">Tieteellinen nimi</div>
                <div style="flex: 1; text-align: right;">Pvm</div>
            </div>

            <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); 
                $tieteellinen = get_field('tieteellinen_nimi');
                // Valitaan päivämäärä tyypin mukaan
                $pvm = ($a['tyyppi'] === 'muokatut') ? get_the_modified_date('j.n.Y') : get_the_date('j.n.Y');
            ?>
                <a href="<?php the_permalink(); ?>" class="kasvi-rivi" style="display: flex; padding: 10px 0; border-bottom: 1px solid #eee; text-decoration: none; color: inherit; align-items: center;">
                    <div style="flex: 2; font-weight: 500;"><?php the_title(); ?></div>
                    <div style="flex: 2; font-style: italic; color: #666; font-size: 0.95rem;"><?php echo esc_html($tieteellinen); ?></div>
                    <div style="flex: 1; text-align: right; font-size: 0.85rem; color: #888;"><?php echo $pvm; ?></div>
                </a>
            <?php endwhile; wp_reset_postdata(); endif; ?>
        </div>
    </div>

    <?php
    return ob_get_clean();
}

/**
 * Etusivun lyhyt lista: uusimmat tai viimeksi muokatut kasvikortit.
 * Käyttö teemassa: echo kasvisto_etusivu_lajilista('uudet', 5);
 *
 * @param string $tyyppi 'uudet' (lisäyspäivä) tai 'muokatut' (muokkauspäivä)
 * @param int    $maara  rivien määrä
 */
function kasvisto_etusivu_lajilista( $tyyppi = 'uudet', $maara = 5 ) {
    $muokatut = ( $tyyppi === 'muokatut' );
    $query = new WP_Query( [
        'post_type'              => 'kasvi',
        'posts_per_page'         => (int) $maara,
        'orderby'                => $muokatut ? 'modified' : 'date',
        'order'                  => 'DESC',
        'no_found_rows'          => true,
        'ignore_sticky_posts'    => true,
        'update_post_term_cache' => false,
    ] );

    if ( ! $query->have_posts() ) {
        return '<p class="etusivu-lajilista-tyhja">Ei kasvikortteja.</p>';
    }

    // Kevyt taulukko ilman sarakeotsikoita: suomenkielinen nimi | tieteellinen nimi | päivämäärä.
    $html = '<table class="etusivu-lajitaulukko"><tbody>';
    while ( $query->have_posts() ) {
        $query->the_post();
        $tieteellinen = function_exists( 'get_field' ) ? (string) get_field( 'tieteellinen_nimi' ) : '';
        $iso_pvm  = $muokatut ? get_the_modified_date( 'Y-m-d' ) : get_the_date( 'Y-m-d' );
        $pvm      = $muokatut ? get_the_modified_date( 'j.n.Y' ) : get_the_date( 'j.n.Y' );

        $html .= '<tr>'
               . '<td class="etusivu-lajitaulukko-suomi"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></td>'
               . '<td class="etusivu-lajitaulukko-latina">' . esc_html( $tieteellinen ) . '</td>'
               . '<td class="etusivu-lajitaulukko-pvm"><time datetime="' . esc_attr( $iso_pvm ) . '">' . esc_html( $pvm ) . '</time></td>'
               . '</tr>';
    }
    wp_reset_postdata();
    $html .= '</tbody></table>';
    return $html;
}
