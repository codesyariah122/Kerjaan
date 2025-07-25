<?php
function duta_rfq_admin_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'duta_rfq';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

    echo '<div class="wrap">';
    echo '<h1>📋 Daftar Permintaan RFQ Member</h1>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>
        <th>ID</th>
        <th>Nama Member</th>
        <th>Email</th>
        <th>Produk</th>
        <th>Qty</th>
        <th>Catatan</th>
        <th>Tanggal</th>
    </tr></thead>';
    echo '<tbody>';

    foreach ($results as $row) {
        $user_info = get_userdata($row->user_id);
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($user_info ? $user_info->display_name : '—') . '</td>';
        echo '<td>' . esc_html($user_info ? $user_info->user_email : '—') . '</td>';
        echo '<td>' . esc_html($row->product) . '</td>';
        echo '<td>' . esc_html($row->quantity) . '</td>';
        echo '<td>' . esc_html($row->notes) . '</td>';
        echo '<td>' . esc_html($row->created_at) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}
