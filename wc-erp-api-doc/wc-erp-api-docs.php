<?php

/**
 * Plugin Name: WC ERP API Docs
 * Description: Halaman Swagger UI otomatis untuk dokumentasi API ERP WooCommerce (endpoint /wp-json/erp/v1/...).
 * Version: 1.0.0
 * Author: Puji Ermanto from tokoweb <Pujiermanto@gmail.com> | AKA Yusuf Mansiur
 */

if (!defined('ABSPATH')) exit;

define('WC_ERP_API_DOCS_PATH', plugin_dir_path(__FILE__));
define('WC_ERP_API_DOCS_URL', plugin_dir_url(__FILE__));

require_once WC_ERP_API_DOCS_PATH . 'includes/class-wc-erp-api-docs.php';

add_action('plugins_loaded', function () {
    new WC_ERP_API_Docs();
});

register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
