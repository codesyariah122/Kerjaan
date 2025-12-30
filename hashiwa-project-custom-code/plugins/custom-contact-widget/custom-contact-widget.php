<?php

/**
 * Plugin Name: Custom Kontak Widget
 * Description: Custom Elementor widget untuk menampilkan data dari Kontak Informasi (CPT).
 * Version: 1.0.0
 * Author: Puji Ermanto <pujiermanto@gmail.com> | AKA: Jhony Rotten
 */

if (!defined('ABSPATH')) exit; // Stop langsung akses

// Pastikan Elementor aktif
function custom_contact_widget_dependencies()
{
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Custom Kontak Widget</strong> membutuhkan Elementor aktif untuk berjalan.</p></div>';
        });
        return;
    }
}
add_action('plugins_loaded', 'custom_contact_widget_dependencies');

// Register widget file
function custom_register_kontak_widget($widgets_manager)
{
    require_once(__DIR__ . '/widgets/kontak-widget.php');
    require_once(__DIR__ . '/widgets/kontak-contact-page-widget.php');

    if (class_exists('Custom_Kontak_Widget')) {
        $widgets_manager->register(new \Custom_Kontak_Widget());
    }

    if (class_exists('Custom_Kontak_Contact_Page_Widget')) {
        $widgets_manager->register(new \Custom_Kontak_Contact_Page_Widget());
    }
}
add_action('elementor/widgets/register', 'custom_register_kontak_widget');
