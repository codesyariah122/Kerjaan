<?php

/**
 * Plugin Name: Tokoweb Typing Text
 * Description: Menambahkan efek teks ketik otomatis. Gunakan shortcode [tokoweb_typing title="Judul Typing Text"].
 * Version: 1.0
 * Author: Puji Ermanto <pujiermanto@gmail.com> | AKA Sumpena | AKA Deden Iynyuuss
 */

// Enqueue script dan CSS
function tokoweb_typing_enqueue_scripts()
{
    wp_enqueue_script('typed-js', 'https://cdn.jsdelivr.net/npm/typed.js@2.0.12', [], null, true);
    wp_enqueue_style('tokoweb-typing-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'tokoweb_typing_enqueue_scripts');

// Shortcode
function tokoweb_typing_shortcode($atts)
{
    $atts = shortcode_atts([
        'title' => '',
    ], $atts);

    if (empty($atts['title'])) {
        return '<p><em>Harap isi atribut title pada shortcode.</em></p>';
    }

    // Ambil post berdasarkan title
    $post = get_page_by_title($atts['title'], OBJECT, 'tokoweb_typing_text');
    if (!$post) {
        return '<p><em>Typing Text dengan judul "' . esc_html($atts['title']) . '" tidak ditemukan.</em></p>';
    }

    $text_raw = get_post_meta($post->ID, '_tokoweb_typing_texts', true);
    $color = get_post_meta($post->ID, '_tokoweb_typing_color', true) ?: '#003865';

    if (!$text_raw) {
        return '<p><em>Tidak ada teks untuk ditampilkan.</em></p>';
    }

    $texts = array_map('trim', explode(',', $text_raw));
    $js_array = json_encode($texts);
    $uniq_id = 'typing_' . $post->ID;

    ob_start();
?>
    <div class="tokoweb-typing-wrapper" style="color: <?= esc_attr($color) ?>;">
        <span id="<?= esc_attr($uniq_id) ?>" class="tokoweb-typing"></span>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            new Typed("#<?= esc_attr($uniq_id) ?>", {
                strings: <?= $js_array ?>,
                typeSpeed: 60,
                backSpeed: 30,
                backDelay: 2000,
                loop: true,
                showCursor: true,
                cursorChar: "|"
            });
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('tokoweb_typing', 'tokoweb_typing_shortcode');

// Register Custom Post Type
function tokoweb_register_typing_post_type()
{
    register_post_type('tokoweb_typing_text', [
        'labels' => [
            'name' => 'Typing Text',
            'singular_name' => 'Typing Text',
            'add_new' => 'Tambah Baru',
            'add_new_item' => 'Tambah Typing Text',
            'edit_item' => 'Edit Typing Text',
            'new_item' => 'Typing Text Baru',
            'view_item' => 'Lihat Typing Text',
            'search_items' => 'Cari Typing Text',
            'not_found' => 'Tidak ditemukan',
        ],
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-editor-spellcheck',
        'supports' => ['title'],
        'show_in_rest' => false,
    ]);
}
add_action('init', 'tokoweb_register_typing_post_type');

// Metabox
function tokoweb_typing_add_metabox()
{
    add_meta_box(
        'tokoweb_typing_metabox',
        'Daftar Teks (pisahkan dengan koma)',
        'tokoweb_typing_metabox_callback',
        'tokoweb_typing_text',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'tokoweb_typing_add_metabox');

function tokoweb_typing_metabox_callback($post)
{
    $value = get_post_meta($post->ID, '_tokoweb_typing_texts', true);
    $color = get_post_meta($post->ID, '_tokoweb_typing_color', true) ?: '#003865';

    echo '<p><strong>Daftar teks:</strong></p>';
    echo '<textarea name="tokoweb_typing_texts" style="width:100%;min-height:80px;">' . esc_textarea($value) . '</textarea>';

    echo '<p style="margin-top:15px;"><label for="tokoweb_typing_color"><strong>Warna teks:</strong></label><br>';
    echo '<input type="color" name="tokoweb_typing_color" value="' . esc_attr($color) . '"></p>';

    echo '<p style="margin-top:10px; font-size:13px; color:#666;">
        Created by <strong>Puji Ermanto</strong> AKA <em>Sumpena</em>
    </p>';
}

function tokoweb_typing_save_post($post_id)
{
    if (array_key_exists('tokoweb_typing_texts', $_POST)) {
        update_post_meta($post_id, '_tokoweb_typing_texts', sanitize_text_field($_POST['tokoweb_typing_texts']));
    }

    if (isset($_POST['tokoweb_typing_color'])) {
        update_post_meta($post_id, '_tokoweb_typing_color', sanitize_hex_color($_POST['tokoweb_typing_color']));
    }
}
add_action('save_post', 'tokoweb_typing_save_post');

// Elementor Widget
function tokoweb_register_elementor_widget()
{
    if (!did_action('elementor/loaded')) return;

    require_once plugin_dir_path(__FILE__) . 'widget-typing-text.php';
    \Elementor\Plugin::instance()->widgets_manager->register(new \Tokoweb_Elementor_Typing_Text());
}
add_action('elementor/widgets/register', 'tokoweb_register_elementor_widget');
