<?php
/**
 * Plugin Name: Tokoweb WhatsApp Chat
 * Description: Floating WhatsApp chat box untuk admin Tokoweb. Dinamis dari CPT.
 * Version: 1.0
 * Author: Puji Ermanto<dev_puji@codedev.co.id> | Aka Mamam Yuk Mas
 */

if (!defined('ABSPATH')) exit;

// Register Custom Post Type
add_action('init', function () {
    register_post_type('whatsapp', [
        'labels' => [
            'name' => 'WhatsApp Admins',
            'singular_name' => 'WhatsApp Admin',
        ],
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-whatsapp',
        'supports' => ['title', 'thumbnail'],
    ]);
});

// Add Meta Box for phone number
add_action('add_meta_boxes', function () {
    add_meta_box('wa_phone_box', 'Nomor WhatsApp', 'wa_phone_box_callback', 'whatsapp');
});

function wa_phone_box_callback($post) {
    $value = get_post_meta($post->ID, '_wa_phone', true);
    echo '<input type="text" name="wa_phone" value="' . esc_attr($value) . '" placeholder="6281234567890" style="width:100%">';
}

// Save Meta Box data
add_action('save_post', function ($post_id) {
    if (array_key_exists('wa_phone', $_POST)) {
        update_post_meta($post_id, '_wa_phone', sanitize_text_field($_POST['wa_phone']));
    }
});

// Load CSS & JS
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('wa-chat-style', plugin_dir_url(__FILE__) . 'wa-style.css');
    wp_enqueue_script('feather-icons', 'https://unpkg.com/feather-icons', [], null, true);
    wp_enqueue_script('wa-chat-script', plugin_dir_url(__FILE__) . 'wa-script.js', [], null, true);
});

// Output HTML
add_action('wp_footer', function () {
    $args = ['post_type' => 'whatsapp', 'posts_per_page' => -1];
    $admins = new WP_Query($args);

    if (!$admins->have_posts()) return;

    echo '<div id="wa-floating-button"><i data-feather="message-circle" class="icon-24"></i></div>';
    echo '<div id="wa-chatbox">';
    echo '<div class="chatbox-header">Hubungi Admin Tokoweb<span id="close-chatbox">&times;</span></div>';
    echo '<div class="chatbox-body" id="admin-list">';

    while ($admins->have_posts()) {
        $admins->the_post();
        $name = get_the_title();
        $phone = get_post_meta(get_the_ID(), '_wa_phone', true);
        $img = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: 'https://via.placeholder.com/40';
        echo "<div class='admin' data-phone='{$phone}' data-name='{$name}'>
                <img src='{$img}' alt='{$name}'><span>{$name}</span>
              </div>";
    }

    echo '</div>'; // admin list
    echo '<div class="chatbox-message" id="chat-form" style="display:none;">
            <div id="selected-admin-info" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;"></div>
            <div class="bubble">Halo kak, ada yang bisa kami bantu seputar product SaaS Multi Tenant | Flash Tokoweb 😊</div>
            <textarea id="user-message">Halo, saya butuh bantuan mengenai produk SaaS Multi Tenant dari Flash Tokoweb 😊</textarea>
            <button id="send-whatsapp">Kirim Chat</button>
        </div>
    </div>";

    wp_reset_postdata();
});
