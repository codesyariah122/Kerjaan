<?php

/**
 * Plugin Name: Floating WhatsApp Chat
 * Description: Floating WhatsApp button with chat box style like WhatsApp Web.
 * Version: 1.0
 * Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Simon Lebone
 */

if (!defined('ABSPATH')) exit;
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');
});
// Add settings menu
add_action('admin_menu', function () {
    add_options_page('Floating WhatsApp Settings', 'WhatsApp Chat', 'manage_options', 'floating-whatsapp-chat', 'fwc_settings_page');
});

// Register settings
add_action('admin_init', function () {
    register_setting('fwc_settings_group', 'fwc_whatsapp_number');
    register_setting('fwc_settings_group', 'fwc_whatsapp_message');
    register_setting('fwc_settings_group', 'fwc_position');
});

// Settings page content
function fwc_settings_page()
{
?>
    <div class="wrap">
        <h1>Floating WhatsApp Chat Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('fwc_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">WhatsApp Number (e.g. 628123456789)</th>
                    <td><input type="text" name="fwc_whatsapp_number" value="<?php echo esc_attr(get_option('fwc_whatsapp_number')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Pre-filled Message</th>
                    <td><textarea name="fwc_whatsapp_message" rows="4" class="large-text"><?php echo esc_textarea(get_option('fwc_whatsapp_message')); ?></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Position</th>
                    <td>
                        <select name="fwc_position">
                            <option value="right" <?php selected(get_option('fwc_position'), 'right'); ?>>Right</option>
                            <option value="left" <?php selected(get_option('fwc_position'), 'left'); ?>>Left</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}

// Enqueue assets
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('fwc-style', plugin_dir_url(__FILE__) . 'floating-whatsapp-style.css');
    wp_enqueue_script('fwc-script', plugin_dir_url(__FILE__) . 'floating-whatsapp-script.js', [], false, true);
});

// Output button HTML
add_action('wp_footer', function () {
    $number = get_option('fwc_whatsapp_number');
    $message = urlencode(get_option('fwc_whatsapp_message'));
    if (!$number) return;

    $position = get_option('fwc_position');
    $position = in_array($position, ['left', 'right']) ? $position : 'left';

    // Tentukan nilai style inline berdasarkan posisi
    $inline_style = $position === 'left' ? 'left: 20px;' : 'right: 20px;';
?>
    <div class="fwc-container fwc-<?php echo esc_attr($position); ?>" style="position: fixed; bottom: 20px; <?php echo esc_attr($inline_style); ?> z-index: 9999;">
        <div class="fwc-bubble">
            <div class="fwc-header">
                <span>Hi, There!</span>
                <button class="fwc-close">&times;</button>
            </div>
            <div class="fwc-body">
                <p>How can I help you?</p>
                <div class="fwc-message-row">
                    <textarea id="fwc-message" rows="3"><?php echo urldecode($message); ?></textarea>
                    <a href="https://wa.me/<?php echo $number; ?>?text=<?php echo $message; ?>"
                        target="_blank"
                        class="fwc-send"
                        onclick="this.href='https://wa.me/<?php echo $number; ?>?text=' + encodeURIComponent(document.getElementById('fwc-message').value)">
                        <i class="fas fa-paper-plane"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="fwc-icon">
            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="Chat" />
        </div>
    </div>
<?php
});
