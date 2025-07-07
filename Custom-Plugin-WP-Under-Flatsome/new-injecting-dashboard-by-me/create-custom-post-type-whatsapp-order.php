<?php

// Buat Custom Post Type WhatsApp Order
// 1. Daftarkan CPT
function create_whatsapp_order_post_type()
{
    register_post_type(
        'wa_order',
        array(
            'labels' => array(
                'name' => __('WhatsApp Order'),
                'singular_name' => __('WhatsApp Order'),
                'menu_name' => __('WhatsApp Order'),
                'add_new' => __('Tambah Nomor'),
                'add_new_item' => __('Tambah Nomor WhatsApp Baru'),
                'edit_item' => __('Edit Nomor WhatsApp'),
                'new_item' => __('Nomor WhatsApp Baru'),
                'view_item' => __('Lihat Nomor WhatsApp'),
                'search_items' => __('Cari Nomor WhatsApp'),
                'not_found' => __('Tidak ditemukan'),
                'not_found_in_trash' => __('Tidak ditemukan di tempat sampah')
            ),
            'public' => true,
            'has_archive' => false,
            'show_in_rest' => true,
            'menu_position' => 25,
            'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode('
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.7 20.3c-1.2 1.2-2.8 1.7-4.5 1.4l-2.5-.4a1 1 0 0 0-.8.2l-2.6 1.6a1 1 0 0 1-1.5-1.1l.4-2.7a1 1 0 0 0-.2-.8l-1.4-2.4a8.5 8.5 0 1 1 12.9 4.2z"/>
                </svg>
            '),
            'supports' => array('title'),
        )
    );
}
add_action('init', 'create_whatsapp_order_post_type');


// 2. Tambahkan Meta Box nomor WA
function wa_order_add_meta_box()
{
    add_meta_box(
        'wa_order_number',             // ID
        'Nomor WhatsApp',              // Title
        'wa_order_number_meta_box_cb', // Callback function
        'wa_order',                   // Post type
        'normal',                     // Context
        'default'                    // Priority
    );
}
add_action('add_meta_boxes', 'wa_order_add_meta_box');

// 3. Callback tampilkan form input di meta box
function wa_order_number_meta_box_cb($post)
{
    // Nonce field untuk keamanan
    wp_nonce_field('wa_order_save_meta_box_data', 'wa_order_meta_box_nonce');

    $value = get_post_meta($post->ID, '_wa_order_number', true);
    echo '<label for="wa_order_number_field">Nomor WhatsApp (format: 6281234567890):</label> ';
    echo '<input type="text" id="wa_order_number_field" name="wa_order_number_field" value="' . esc_attr($value) . '" size="25" />';
}

// 4. Simpan data meta box
function wa_order_save_meta_box_data($post_id)
{
    // Cek nonce
    if (!isset($_POST['wa_order_meta_box_nonce'])) return;
    if (!wp_verify_nonce($_POST['wa_order_meta_box_nonce'], 'wa_order_save_meta_box_data')) return;

    // Jangan simpan saat autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Cek hak akses user
    if (!current_user_can('edit_post', $post_id)) return;

    if (!isset($_POST['wa_order_number_field'])) return;

    $my_data = sanitize_text_field($_POST['wa_order_number_field']);
    update_post_meta($post_id, '_wa_order_number', $my_data);
}
add_action('save_post', 'wa_order_save_meta_box_data');
