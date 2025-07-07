<?php
if (class_exists('FRC_Flashsale_Stock')) {
    return;
}

class FRC_Flashsale_Stock
{
    public function __construct()
    {
        add_action('woocommerce_add_to_cart', [$this, 'reserve_stock'], 10, 6);
        add_action('woocommerce_cart_item_removed', [$this, 'restore_stock']);
    }

    public function reserve_stock($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        $product = wc_get_product($product_id);

        // Cek apakah countdown diaktifkan untuk produk ini
        $enable_countdown = get_post_meta($product_id, '_enable_launch_countdown', true);
        if ($enable_countdown !== 'yes') {
            return; // jika tidak diaktifkan, tidak kurangi stok
        }

        if ($product && $product->managing_stock()) {
            // Kurangi stok
            wc_update_product_stock($product, -$quantity);
            // Simpan info reservasi dan waktu untuk keperluan cron restore stok
            WC()->session->set('frc_reserved_' . $cart_item_key, ['id' => $product_id, 'qty' => $quantity]);
            WC()->session->set('frc_time_' . $cart_item_key, time());
        }
    }

    public function restore_stock($cart_item_key, $cart)
    {
        $reserved = WC()->session->get('frc_reserved_' . $cart_item_key);
        if ($reserved) {
            $product = wc_get_product($reserved['id']);
            if ($product && $product->managing_stock()) {
                // Kembalikan stok
                wc_update_product_stock($product, $reserved['qty']);
                // Hapus data session reservasi
                WC()->session->__unset('frc_reserved_' . $cart_item_key);
                WC()->session->__unset('frc_time_' . $cart_item_key);
            }
        }
    }
}
