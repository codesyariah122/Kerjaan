<?php

/**
 * Plugin Name: Tokoweb Poster Grid
 * Description: Plugin untuk menampilkan grid poster (gambar + judul) menggunakan Custom Post Type dan widget Elementor.
 * Version: 1.0
 * Author: Puji Ermanto <pujiermanto@gmail.com>
 */

if (!defined('ABSPATH')) exit;

// Register Custom Post Type
function tokoweb_register_poster_post_type()
{
    register_post_type('tokoweb_poster', [
        'labels' => [
            'name' => 'Poster',
            'singular_name' => 'Poster',
            'add_new' => 'Tambah Baru',
            'add_new_item' => 'Tambah Poster Baru',
            'edit_item' => 'Edit Poster',
            'new_item' => 'Poster Baru',
            'view_item' => 'Lihat Poster',
            'search_items' => 'Cari Poster',
            'not_found' => 'Tidak ditemukan',
        ],
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-format-image',
        'supports' => ['title', 'thumbnail'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'tokoweb_register_poster_post_type');

// Enqueue Styles
function tokoweb_poster_grid_enqueue_assets()
{
    wp_enqueue_style('tokoweb-poster-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'tokoweb_poster_grid_enqueue_assets');

// Register Elementor Widget
function tokoweb_register_elementor_poster_widget()
{
    if (!did_action('elementor/loaded')) return;

    require_once plugin_dir_path(__FILE__) . 'widget-poster-grid.php';
    \Elementor\Plugin::instance()->widgets_manager->register(new \Tokoweb_Elementor_Poster_Grid());
}
add_action('elementor/widgets/register', 'tokoweb_register_elementor_poster_widget');
