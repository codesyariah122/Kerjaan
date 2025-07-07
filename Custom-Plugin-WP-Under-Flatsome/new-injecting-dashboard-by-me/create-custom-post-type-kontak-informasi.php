<?php
function mapasitujutrans_register_cpt_kontak()
{
    $labels = array(
        'name' => 'Kontak Informasi',
        'singular_name' => 'Kontak Informasi',
        'add_new' => 'Tambah Kontak',
        'add_new_item' => 'Tambah Kontak Baru',
        'edit_item' => 'Edit Kontak',
        'new_item' => 'Kontak Baru',
        'view_item' => 'Lihat Kontak',
        'search_items' => 'Cari Kontak',
        'not_found' => 'Kontak tidak ditemukan',
        'not_found_in_trash' => 'Tidak ada kontak di trash',
        'menu_name' => 'Kontak Informasi',
    );
    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => array('title'),
        'menu_icon' => 'dashicons-phone',
        'capability_type' => 'post',
    );
    register_post_type('kontak_informasi', $args);
}
add_action('init', 'mapasitujutrans_register_cpt_kontak');
