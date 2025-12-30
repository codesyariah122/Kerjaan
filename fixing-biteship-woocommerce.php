<?php
if (!defined('WPINC')) die();

define('BITESHIP_VERSION', '3.2.0');
define('BITESHIP_PLUGIN_PATH', plugin_dir_path(__FILE__));

require BITESHIP_PLUGIN_PATH . 'includes/class-biteship.php';

// Activation & Deactivation
register_activation_hook(__FILE__, function () {
    require BITESHIP_PLUGIN_PATH . 'includes/class-biteship-activator.php';
    Biteship_Activator::activate();
});

register_deactivation_hook(__FILE__, function () {
    require BITESHIP_PLUGIN_PATH . 'includes/class-biteship-deactivator.php';
    Biteship_Deactivator::deactivate();
});

// Uninstall
register_uninstall_hook(__FILE__, 'biteship_uninstall');
function biteship_uninstall()
{
    require BITESHIP_PLUGIN_PATH . 'includes/class-biteship-uninstall.php';
    Biteship_Uninstall::uninstall();
}

// Load shipping method class
add_action('plugins_loaded', function () {
    if (class_exists('WooCommerce')) {
        require_once BITESHIP_PLUGIN_PATH . 'includes/class-biteship-shipping-method.php';

        // Register shipping method
        add_filter('woocommerce_shipping_methods', function ($methods) {
            $methods['biteship'] = 'Biteship_Shipping_Method';
            return $methods;
        });
    }
}, 20);

// Force-enable minimal options
add_action('admin_init', function () {
    $keys = ['enabled', 'licence'];
    foreach ($keys as $key) {
        if (get_option('biteship_' . $key) === false) {
            update_option('biteship_' . $key, $key === 'enabled' ? 'yes' : 'FORCE-LICENSE');
        }
    }
});

// Initialize plugin helpers
if (class_exists('Biteship')) {
    $plugin = new Biteship();
    $plugin->run();
}
