<?php

/**
 * Plugin Name: Hashiwa Program Widget
 * Description: Custom CPT + Elementor Widget for Program Utama Hashiwa Japanese Academy.
 * Version: 1.0.0
 * Author: Puji Ermanto
 */

if (! defined('ABSPATH')) exit;

// Constants
define('HASHIWA_PW_PATH', plugin_dir_path(__FILE__));
define('HASHIWA_PW_URL', plugin_dir_url(__FILE__));

add_action('init', function () {
    flush_rewrite_rules();
});

// Load CPT
require_once HASHIWA_PW_PATH . 'post-types/program.php';

// Load ACF JSON
add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = HASHIWA_PW_PATH . 'acf-json';
    return $paths;
});

// Elementor category
add_action('elementor/elements/categories_registered', function ($elements_manager) {
    $elements_manager->add_category(
        'hashiwa-category',
        [
            'title' => 'Hashiwa Widgets',
            'icon'  => 'fa fa-star'
        ]
    );
});

// Register Elementor widget
add_action('elementor/widgets/register', function ($widgets_manager) {
    require_once HASHIWA_PW_PATH . 'elementor/widget-program-card.php';
    $widgets_manager->register(new \Hashiwa_Program_Card_Widget());
});

// Enqueue frontend styles
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'hashiwa-program-style',
        HASHIWA_PW_URL . 'assets/program-style.css',
        [],
        '1.0.0'
    );
});
