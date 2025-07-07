<?php

/**
 * Plugin Name: Realtime Admin Monitor
 * Description: Hanya mengizinkan satu user admin yang aktif dalam Dashboard secara bersamaan dan melacak keaktifannya.
 * Version: 1.0
 * Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Jhony Kemod | AKA Cowok paling ganteng di komplek ini
 * Author URI: https://pujiermanto-portfolio.vercel.app
 * License: Unlicense
 * License URI: https://unlicense.org/
 *  Text Domain: realtime-admin-monitor
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/activity-logger.php';

// Inject JS untuk tracking keaktifan
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'index.php') === false && strpos($hook, 'dashboard') === false) return;

    wp_enqueue_script('admin-monitor-js', plugin_dir_url(__FILE__) . 'assets/monitor.js', ['jquery'], false, true);
    wp_localize_script('admin-monitor-js', 'adminMonitorAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('admin_monitor_nonce')
    ]);
});

// AJAX: log activity
add_action('wp_ajax_log_admin_activity', 'log_admin_activity');

// Tambahkan widget ke dashboard
add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget('admin_activity_monitor_widget', 'Admin Activity Monitor', 'render_admin_activity_widget');
});

// Render widget kosong (diisi oleh JS)
function render_admin_activity_widget()
{
    echo '<div id="admin-activity-monitor-table">Memuat data keaktifan admin...</div>';
}
