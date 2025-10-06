<?php

/**
 * Plugin Name: Alur Pendaftaran
 * Description: Plugin untuk membuat CPT Alur Pendaftaran + Widget Elementor custom.
 * Version: 1.0
 * Author: Nama Anda
 * Text Domain: alur-pendaftaran
 */

if (! defined('ABSPATH')) exit;

define('ALUR_PENDAFTARAN_PATH', plugin_dir_path(__FILE__));

// Load file
require_once ALUR_PENDAFTARAN_PATH . 'includes/cpt.php';
require_once ALUR_PENDAFTARAN_PATH . 'includes/meta-box.php';
// require_once ALUR_PENDAFTARAN_PATH . 'includes/elementor-widget.php';
// Daftarkan widget Elementor HANYA setelah Elementor aktif
add_action('plugins_loaded', function () {
    if (did_action('elementor/loaded')) {
        require_once ALUR_PENDAFTARAN_PATH . 'includes/elementor-widget.php';
    }
});

// Enqueue style
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('alur-style', plugin_dir_url(__FILE__) . 'assets/style.css');
});
