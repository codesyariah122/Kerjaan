<?php

function duta_rfq_form_shortcode()
{
    if (!is_user_logged_in()) return '<p>Harap login terlebih dahulu.</p>';

    ob_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duta_rfq_nonce']) && wp_verify_nonce($_POST['duta_rfq_nonce'], 'duta_rfq_action')) {

        $product = sanitize_text_field($_POST['product']);
        $quantity = sanitize_text_field($_POST['quantity']);
        $notes = sanitize_textarea_field($_POST['notes']);

        $user_id = get_current_user_id();

        global $wpdb;
        $table = $wpdb->prefix . 'duta_rfq';

        $wpdb->insert($table, [
            'user_id' => $user_id,
            'product' => $product,
            'quantity' => $quantity,
            'notes' => $notes,
            'created_at' => current_time('mysql')
        ]);

        echo '<div style="color: green;">Permintaan berhasil dikirim!</div>';
    }

?>
    <form method="post">
        <?php wp_nonce_field('duta_rfq_action', 'duta_rfq_nonce'); ?>
        <p><label>Produk yang dibutuhkan<br><input type="text" name="product" required></label></p>
        <p><label>Jumlah / Unit<br><input type="number" name="quantity" required></label></p>
        <p><label>Catatan Tambahan<br><textarea name="notes"></textarea></label></p>
        <p><button type="submit">Kirim Permintaan</button></p>
    </form>
<?php

    return ob_get_clean();
}
