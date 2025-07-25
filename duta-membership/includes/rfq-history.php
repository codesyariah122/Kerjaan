<?php

function duta_rfq_history_shortcode()
{
    if (!is_user_logged_in()) return '';

    ob_start();

    global $wpdb;
    $table = $wpdb->prefix . 'duta_rfq';
    $user_id = get_current_user_id();

    $items = $wpdb->get_results("SELECT * FROM $table WHERE user_id = $user_id ORDER BY created_at DESC");

    if ($items) {
        echo '<table border="1" cellpadding="6" style="border-collapse: collapse">';
        echo '<tr><th>Tanggal</th><th>Produk</th><th>Jumlah</th><th>Catatan</th></tr>';
        foreach ($items as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->created_at) . '</td>';
            echo '<td>' . esc_html($row->product) . '</td>';
            echo '<td>' . esc_html($row->quantity) . '</td>';
            echo '<td>' . esc_html($row->notes) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>Belum ada permintaan.</p>';
    }

    return ob_get_clean();
}
