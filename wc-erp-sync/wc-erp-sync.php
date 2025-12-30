<?php

/**
 * Plugin Name: WC ERP Sync
 * Description: Lightweight plugin to sync WooCommerce products (SKU_ERP) and orders with an external ERP via REST API.
 * Version: 1.0.0
 * Author: Puji Ermanto from tokoweb <Pujiermanto@gmail.com> | AKA Yusuf Mansiur
 * Text Domain: wc-erp-sync
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WC_ERP_SYNC_PATH', plugin_dir_path(__FILE__));
define('WC_ERP_SYNC_VERSION', '1.0.0');

// Pastikan baris ini TIDAK ada tag PHP baru atau HTML apapun di atas/bawahnya
require_once WC_ERP_SYNC_PATH . 'includes/class-wc-erp-sync.php';

function wc_erp_sync_init()
{
    $GLOBALS['wc_erp_sync'] = new WC_ERP_Sync();
}
add_action('plugins_loaded', 'wc_erp_sync_init');

register_activation_hook(__FILE__, ['WC_ERP_Sync', 'activate']);
register_deactivation_hook(__FILE__, ['WC_ERP_Sync', 'deactivate']);
register_uninstall_hook(__FILE__, 'wc_erp_sync_uninstall');

function wc_erp_sync_uninstall()
{
    delete_option('wc_erp_sync_settings');
}
