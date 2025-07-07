<?php

/**
 * Plugin Name: Flashsale Role Control
 * Description: Plugin custom untuk flashsale WooCommerce dan kontrol produk berdasarkan role User dan Mitra.
 * Version: Bintang Lima
 * Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Jhony Kemod | AKA Cowok paling ganteng di komplek ini
 * Author URI: https://pujiermanto-portfolio.vercel.app
 */


if (!defined('ABSPATH')) exit;

define('FRC_PATH', plugin_dir_path(__FILE__));
define('FRC_URL', plugin_dir_url(__FILE__));

require_once FRC_PATH . 'includes/class-user-role-manager.php';
require_once FRC_PATH . 'includes/class-flashsale-stock.php';
require_once FRC_PATH . 'includes/class-role-product-filter.php';
require_once FRC_PATH . 'includes/class-abandoned-cart-cron.php';

register_activation_hook(__FILE__, ['FRC_User_Role_Manager', 'activate']);

add_action('plugins_loaded', function () {
    new FRC_Flashsale_Stock();
    new FRC_Role_Product_Filter();
    new FRC_Abandoned_Cart_Cron();
});

register_deactivation_hook(__FILE__, ['FRC_Abandoned_Cart_Cron', 'deactivate']);
