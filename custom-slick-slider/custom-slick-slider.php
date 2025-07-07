<?php

/**
 * Plugin Name: Custom Slick Slider
 * Plugin URI: https://tokoweb.co/plugins/custom-slick-slider
 * Description: Slider plugin menggunakan Slick JS dan support Elementor.
 * Version: 1.1
 * Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Vickerness | AKA Dunkelheit | AKA Tatang Kegelapan
 * Author URI: https://pujiermanto-blog.vercel.app
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-slick-slider
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Enqueue Scripts & Styles
add_action('wp_enqueue_scripts', 'csslick_enqueue_assets');
function csslick_enqueue_assets()
{
    // 1. Enqueue slick.css dulu
    wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');

    // 2. Enqueue custom style dulu SEBELUM slick-theme (biar bisa ditimpa oleh slick-theme kalau perlu)
    wp_enqueue_style('slick-custom', plugins_url('/css/slick-custom.css', __FILE__), [], time());


    // 3. Enqueue slick-theme.css PALING AKHIR agar bisa kamu timpa pakai selector lebih spesifik
    wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');

    // Slick JS CDN + inisialisasi lokal
    wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], null, true);
    wp_enqueue_script('slick-init', plugins_url('/js/slick-init.js', __FILE__), ['slick-js'], null, true);
}

add_action('elementor/frontend/after_enqueue_scripts', 'csslick_enqueue_assets');
add_action('elementor/editor/after_enqueue_scripts', 'csslick_enqueue_assets');
// Elementor Integration
add_action('elementor/widgets/register', function ($widgets_manager) {
    require_once __DIR__ . '/widgets/class-custom-slick-slider-widget.php';
    $widgets_manager->register(new \Custom_Slick_Slider_Widget());
});
