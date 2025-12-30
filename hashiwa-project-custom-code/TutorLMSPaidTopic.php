<?php

/**
 * Tutor LMS Paid Topic - Full Snippet Adjusted
 * Author: Puji Ermanto | AKA Jhony Rotten
 * Version: 1.1
 * Description: Menyimpan harga per topik dari table wpsu_tutor_topic_price dan tampilkan di order.
 */

// -----------------------------
// 1️⃣ Simpan Harga Per Topik (tetap)
add_action('tutor_after_topic_save', 'save_custom_topic_price_meta', 10, 2);
function save_custom_topic_price_meta($topic_id, $topic_data)
{
    if (isset($_POST['custom_topic_price'])) {
        $price = floatval($_POST['custom_topic_price']);
        update_post_meta($topic_id, '_custom_topic_price', $price);

        global $wpdb;
        $table = $wpdb->prefix . 'tutor_topic_price';
        $wpdb->replace(
            $table,
            [
                'course_id'   => $topic_data['course_id'],
                'topic_title' => get_the_title($topic_id),
                'price'       => $price,
                'created_at'  => current_time('mysql'),
            ],
            ['course_id', 'topic_title', 'price', 'created_at']
        );
    }
}

// -----------------------------
// 2️⃣ Hitung total per topic saat order
add_action('tutor_after_order_create', 'save_topic_prices_to_order', 10, 2);
function save_topic_prices_to_order($order_id, $order_data)
{
    global $wpdb;

    $user_id   = $order_data['user_id'];
    $course_id = $order_data['course_id'];

    // Ambil semua topic dari course
    $topics = get_post_meta($course_id, '_tutor_topics', true);
    if (!$topics || !is_array($topics)) $topics = [];

    $total_price = 0;
    $topic_prices = [];

    $table = $wpdb->prefix . 'tutor_topic_price';

    foreach ($topics as $topic_id) {
        // ambil harga dari tabel custom
        $price = $wpdb->get_var($wpdb->prepare(
            "SELECT price FROM $table WHERE course_id=%d AND topic_title=%s ORDER BY id ASC LIMIT 1",
            $course_id,
            get_the_title($topic_id)
        ));
        $price = floatval($price);
        $topic_prices[$topic_id] = $price;
        $total_price += $price;
    }

    // Simpan harga tiap topic di meta order
    update_post_meta($order_id, '_topic_prices', $topic_prices);
    // Simpan total order
    update_post_meta($order_id, '_tutor_order_total', $total_price);

    // Update tabel wpsu_tutor_orders agar kolom total_price dan subtotal_price sesuai
    $orders_table = $wpdb->prefix . 'tutor_orders';
    $wpdb->update(
        $orders_table,
        [
            'subtotal_price' => $total_price,
            'total_price'    => $total_price,
        ],
        ['id' => $order_id]
    );

    // Pastikan user student yang login adalah author
    wp_update_post([
        'ID' => $order_id,
        'post_author' => $user_id
    ]);
}

// -----------------------------
// 3️⃣ Tampilkan harga per topic di dashboard student
add_filter('tutor_order_item_price', 'display_custom_topic_prices', 10, 3);
function display_custom_topic_prices($price_html, $item_id, $order_id)
{
    $topic_prices = get_post_meta($order_id, '_topic_prices', true);
    if ($topic_prices && is_array($topic_prices)) {
        $price_html = '';
        foreach ($topic_prices as $topic_id => $price) {
            $topic_title = get_the_title($topic_id);
            $price_html .= $topic_title . ': ' . tutor_price($price) . '<br>';
        }
    }
    return $price_html;
}

// -----------------------------
// 4️⃣ Pastikan frontend purchase history menampilkan order user
add_filter('tutor_purchase_history_query_args', 'custom_purchase_history_query');
function custom_purchase_history_query($args)
{
    $args['author'] = get_current_user_id(); // hanya order milik user login
    return $args;
}
