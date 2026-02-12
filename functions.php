<?php
// Testi: jos tämä on päällä, sivun pitäisi pysähtyä ja näyttää teksti
// die('Koodi ajetaan kasvisto-teemasta!');

function rekisteroi_kasvit_cpt() {
    $labels = array(
        'name'               => 'Kasvit',
        'singular_name'      => 'Kasvi',
        'add_new'            => 'Lisää uusi kasvi',
        'add_new_item'       => 'Lisää uusi kasvi',
        'edit_item'          => 'Muokkaa kasvia',
        'all_items'          => 'Kaikki kasvit',
        'search_items'       => 'Etsi kasveja',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-palmtree',
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
        'show_in_rest'       => true, // Tämä on tärkeä ACF:lle ja editorille
        'hierarchical'       => false,
        'rewrite'            => array('slug' => 'kasvit'),
    );

    register_post_type('kasvi', $args);
}
add_action('init', 'rekisteroi_kasvit_cpt');
