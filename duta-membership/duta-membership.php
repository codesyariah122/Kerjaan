<?php

/**
 * Plugin Name: Duta Membership
 * Description: Plugin keanggotaan untuk PT. DUTA PERSADA INSTRUMENT
 * Version: 1.0
 * Author: Tukang Koding | Tatang Kolentrang | AKA : Puji Ermanto <pujiermanto@gmail.com>
 */

defined('ABSPATH') or exit;

// 🔁 Load semua modul
require_once plugin_dir_path(__FILE__) . 'includes/register-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/login-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/member-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/edit-profile.php'; // << pindahkan HTML edit profil ke sini
require_once plugin_dir_path(__FILE__) . 'includes/rfq-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/rfq-history.php';
require_once plugin_dir_path(__FILE__) . 'admin/rfq-admin-page.php';

// 🎯 Daftarkan shortcode
add_shortcode('duta_register_form', 'duta_register_form_shortcode');
add_shortcode('duta_login_form', 'duta_login_form_shortcode');
add_shortcode('duta_member_dashboard', 'duta_member_dashboard_shortcode');
add_shortcode('duta_edit_profile', 'duta_edit_profile_shortcode'); // << Shortcode form edit profil
add_shortcode('duta_rfq_form', 'duta_rfq_form_shortcode');
add_shortcode('duta_rfq_history', 'duta_rfq_history_shortcode');

// 🛡️ Aktifkan session
function duta_membership_init_session()
{
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'duta_membership_init_session');

// 📦 Buat tabel RFQ saat plugin diaktifkan
function duta_membership_install()
{
    global $wpdb;
    $table = $wpdb->prefix . 'duta_rfq';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        product TEXT NOT NULL,
        quantity VARCHAR(100),
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'duta_membership_install');

// 📁 Admin menu RFQ
add_action('admin_menu', 'duta_membership_admin_menu');
function duta_membership_admin_menu()
{
    add_menu_page(
        'RFQ Member',
        'RFQ Member',
        'manage_options',
        'duta-rfq',
        'duta_rfq_admin_page',
        'dashicons-feedback',
        26
    );
}
