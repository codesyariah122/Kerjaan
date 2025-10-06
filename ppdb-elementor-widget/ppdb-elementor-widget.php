<?php

/**
 * Plugin Name: PPDB Shortcode
 * Description: Shortcode untuk menampilkan gambar dari Option Page JetEngine (PPDB).
 * Version: 1.0
 * Author: Puji Ermanto <pujiermanto@gmail.com>
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fungsi Render (utama dipakai di shortcode)
 */
function ppdb_render_content($title = '')
{
    $selected_title = sanitize_title($title); // normalisasi key

    // Ambil option "customize"
    $options = get_option('customize', []);
    if (!is_array($options)) {
        $options = [];
    }

    // Ambil data PPDB
    $ppdb = $options['konten-ppdb-online'] ?? [];

    // Fallback JetEngine kalau ada
    if (empty($ppdb) && function_exists('jet_engine')) {
        try {
            $je = jet_engine();
            if (is_object($je) && method_exists($je, 'options')) {
                $maybe = $je->options->get_option('konten-ppdb-online');
                if (is_array($maybe)) {
                    $ppdb = $maybe;
                }
            }
        } catch (Exception $e) {
            $ppdb = [];
        }
    }

    if (empty($ppdb)) {
        return '<p>No PPDB content found.</p>';
    }

    ob_start();
    echo '<div class="ppdb-gallery">';

    foreach ($ppdb as $item) {
        if (!is_array($item)) continue;

        foreach ($item as $key => $image_json) {
            if (empty($image_json)) continue;

            // cek apakah array atau string JSON
            $image_data = is_array($image_json) ? $image_json : json_decode($image_json, true);
            if (!is_array($image_data) || empty($image_data['url'])) continue;

            // normalisasi key
            $key_normalized = sanitize_title($key);

            // kalau ada filter title, cocokin
            if ($selected_title && $key_normalized !== $selected_title) continue;

            $img_url = esc_url($image_data['url']);
?>
            <div class="ppdb-item">
                <img src="<?php echo $img_url; ?>" alt="<?php echo esc_attr($key); ?>" />
            </div>
<?php
        }
    }

    echo '</div>';
    return ob_get_clean();
}

/**
 * Shortcode
 * contoh: [ppdb title="PPDB-PG"]
 */
function ppdb_shortcode($atts)
{
    $atts = shortcode_atts(['title' => ''], $atts, 'ppdb');
    return ppdb_render_content($atts['title']);
}
add_shortcode('ppdb', 'ppdb_shortcode');
