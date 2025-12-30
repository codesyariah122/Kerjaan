<?php

/**
 * Plugin Name: Floating WhatsApp Chat
 * Description: Floating WhatsApp button with chat box style like WhatsApp Web.
 * Version: 1.1
 * Author: Puji Ermanto <pujiermanto@gmail.com> | AKA Simon Lebone
 */

if (!defined('ABSPATH')) exit;

/* =========================================================
 *  FRONTEND ASSETS
 * ========================================================= */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css');
    wp_enqueue_style('fwc-style', plugin_dir_url(__FILE__) . 'floating-whatsapp-style.css');
    wp_enqueue_script('fwc-script', plugin_dir_url(__FILE__) . 'floating-whatsapp-script.js', [], false, true);
});

/* =========================================================
 *  HELPER: Normalisasi Nomor WhatsApp
 * ========================================================= */
function fwc_normalize_whatsapp_number($raw, $country_code = '62')
{
    if (empty($raw)) return '';

    // Hapus semua non-digit
    $num = preg_replace('/[^0-9]/', '', $raw);
    if ($num === '') return '';

    // Jika mulai dengan 0 → buang nol di depan dan tambahkan kode negara
    if (preg_match('/^0+/', $num)) {
        $num = preg_replace('/^0+/', '', $num);
        return $country_code . $num;
    }

    // Jika mulai dengan 8 → tambahkan kode negara
    if (preg_match('/^8[0-9]{5,}$/', $num)) {
        return $country_code . $num;
    }

    // Jika sudah dimulai dengan kode negara
    if (preg_match('/^' . $country_code . '[0-9]+$/', $num)) {
        return $num;
    }

    return $num;
}

/* =========================================================
 *  ADMIN MENU
 * ========================================================= */
add_action('admin_menu', function () {
    add_options_page('Floating WhatsApp Settings', 'WhatsApp Chat', 'manage_options', 'floating-whatsapp-chat', 'fwc_settings_page');
});

/* =========================================================
 *  REGISTER SETTINGS
 * ========================================================= */
add_action('admin_init', function () {
    register_setting('fwc_settings_group', 'fwc_whatsapp_country');
    register_setting('fwc_settings_group', 'fwc_whatsapp_number', [
        'sanitize_callback' => function ($val) {
            $country = get_option('fwc_whatsapp_country', '62');
            return fwc_normalize_whatsapp_number($val, $country);
        }
    ]);
    register_setting('fwc_settings_group', 'fwc_whatsapp_message', 'sanitize_text_field');
    register_setting('fwc_settings_group', 'fwc_position');
});

/* =========================================================
 *  SETTINGS PAGE
 * ========================================================= */
function fwc_settings_page()
{
    $number = esc_attr(get_option('fwc_whatsapp_number'));
    $country = esc_attr(get_option('fwc_whatsapp_country', '62'));
?>
    <div class="wrap">
        <h1>Floating WhatsApp Chat Settings</h1>
        <form method="post" action="options.php" id="fwc-settings-form">
            <?php settings_fields('fwc_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Country Code</th>
                    <td>
                        <select name="fwc_whatsapp_country" id="fwc_country">
                            <option value="62" <?php selected($country, '62'); ?>>Indonesia (+62)</option>
                            <option value="60" <?php selected($country, '60'); ?>>Malaysia (+60)</option>
                            <option value="65" <?php selected($country, '65'); ?>>Singapore (+65)</option>
                            <option value="66" <?php selected($country, '66'); ?>>Thailand (+66)</option>
                            <option value="1" <?php selected($country, '1'); ?>>USA (+1)</option>
                            <option value="91" <?php selected($country, '91'); ?>>India (+91)</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">WhatsApp Number</th>
                    <td>
                        <input type="text" name="fwc_whatsapp_number" id="fwc_number" value="<?php echo $number; ?>" class="regular-text" placeholder="08123456789" />
                        <p class="description">Masukkan nomor tanpa tanda '+' atau spasi. Contoh: <code>08123456789</code></p>
                        <p id="fwc-error" style="color: red; display:none;">⚠️ Format nomor tidak valid. Gunakan angka saja (contoh: 08123456789).</p>
                    </td>
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

        <script>
            document.getElementById('fwc-settings-form').addEventListener('submit', function(e) {
                const num = document.getElementById('fwc_number').value.trim();
                const err = document.getElementById('fwc-error');
                if (!/^[0-9+ ]+$/.test(num)) {
                    e.preventDefault();
                    err.style.display = 'block';
                    return false;
                }
                err.style.display = 'none';
            });
        </script>
    </div>
<?php
}

/* =========================================================
 *  FRONTEND OUTPUT BUTTON
 * ========================================================= */
add_action('wp_footer', function () {
    $country = get_option('fwc_whatsapp_country', '62');
    $raw_number = get_option('fwc_whatsapp_number');
    $number = fwc_normalize_whatsapp_number($raw_number, $country);
    $message = get_option('fwc_whatsapp_message', '');

    if (!$number) return;

    $position = get_option('fwc_position');
    $position = in_array($position, ['left', 'right']) ? $position : 'left';
    $inline_style = $position === 'left' ? 'left: 20px;' : 'right: 20px;';
    $href_default = 'https://wa.me/' . esc_attr($number) . '?text=' . rawurlencode($message);
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
                    <textarea id="fwc-message" rows="3"><?php echo esc_textarea($message); ?></textarea>
                    <a href="<?php echo $href_default; ?>"
                        target="_blank"
                        class="fwc-send"
                        onclick="this.href='https://wa.me/<?php echo esc_js($number); ?>?text=' + encodeURIComponent(document.getElementById('fwc-message').value)">
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
