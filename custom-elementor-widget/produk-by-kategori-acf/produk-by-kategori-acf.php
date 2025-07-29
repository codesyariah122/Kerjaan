<?php

/**
 * Plugin Name: Produk by Kategori ACF
 * Description: Widget Elementor untuk menampilkan produk berdasarkan kategori, jumlah, urutan, dan role user.
 * Version: 1.2.0
 * Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Maman Salajami | AKA Deden Inyuuus
 * Author URI: https://pujiermanto-portfolio.vercel.app
 * Text Domain: produk-by-kategori-acf
 */

if (!defined('ABSPATH')) exit;

// Jajanin atuh kaka
add_action('plugins_loaded', function () {
    if (did_action('elementor/loaded')) {
        add_action('elementor/widgets/register', function ($widgets_manager) {
            require_once plugin_dir_path(__FILE__) . 'widgets/produk-by-kategori-acf-widget.php';
            $widgets_manager->register(new \Produk_By_Kategori_ACF_Widget());
        });
    }
});
