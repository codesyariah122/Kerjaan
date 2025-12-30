<?php
if (!defined('ABSPATH')) exit;

/**
 * REGISTER CUSTOM POST TYPE: program
 */
add_action('init', function () {

    $labels = [
        'name'               => 'Program',
        'singular_name'      => 'Program',
        'menu_name'          => 'Program',
        'add_new'            => 'Tambah Program',
        'add_new_item'       => 'Tambah Program Baru',
        'edit_item'          => 'Edit Program',
        'view_item'          => 'Lihat Program',
        'all_items'          => 'Semua Program',
        'search_items'       => 'Cari Program',
        'not_found'          => 'Program tidak ditemukan',
        'not_found_in_trash' => 'Tidak ada Program di sampah',
    ];

    register_post_type('program', [
        'labels'        => $labels,
        'public'        => true,
        'publicly_queryable' => true,
        'show_ui'       => true,
        'menu_icon'     => 'dashicons-welcome-learn-more',
        'supports'      => ['title', 'thumbnail', 'editor', 'excerpt'],
        'has_archive'   => false,
        'rewrite'       => ['slug' => 'program'],
        'show_in_rest'  => true,   // 🔥 MATIKAN agar kompatibel dg slug 'tutor'
        'show_in_menu' => false     // 🔥 WAJIB agar tidak buat menu baru
    ]);
});

/**
 * REGISTER TAXONOMY: jenis_program
 * (Technoshoku, Tokutei Ginou, Reguler)
 */
add_action('init', function () {

    $labels = [
        'name'          => 'Jenis Program',
        'singular_name' => 'Jenis Program',
        'menu_name'     => 'Jenis Program'
    ];

    register_taxonomy('jenis_program', 'program', [
        'hierarchical' => true,
        'labels'       => $labels,
        'show_ui'      => true,
        'show_in_rest' => true,
        'rewrite'      => ['slug' => 'jenis-program']
    ]);

    // AUTO CREATE DEFAULT TERMS
    $defaults = ['Technoshoku', 'Tokutei Ginou', 'Reguler'];
    foreach ($defaults as $term) {
        if (!term_exists($term, 'jenis_program')) {
            wp_insert_term($term, 'jenis_program');
        }
    }
});

/**
 * ADD SUBMENU under TUTOR LMS
 */
add_action('admin_menu', function () {
    global $submenu;

    // pastikan ada menu tutor
    if (isset($submenu['tutor'])) {

        // Tambahkan menu Program tepat setelah "Courses"
        array_splice($submenu['tutor'], 1, 0, [[
            'Program Utama',
            'manage_options',
            'edit.php?post_type=program'
        ]]);
    }
}, 999);


// add_action('admin_menu', function () {
//     add_submenu_page(
//         'tutor', // 🔥 parent asli Tutor LMS
//         'Program Utama',       // Page title
//         'Program Utama',       // Menu label
//         'manage_options',
//         'edit.php?post_type=program',
//         null
//     );
// });
