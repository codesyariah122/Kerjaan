<?php
add_action('init', function () {
    register_post_type('alur_pendaftaran', [
        'labels' => [
            'name' => 'Alur Pendaftaran',
            'singular_name' => 'Langkah',
            'add_new_item' => 'Tambah Langkah Baru',
            'edit_item' => 'Edit Langkah',
        ],
        'public' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-list-view',
        'supports' => ['title'],
    ]);
});
