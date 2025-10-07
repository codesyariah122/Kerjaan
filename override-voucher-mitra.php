<?php

/**
 * Plugin Name: Override Voucher Mitra
 * Description: Menambahkan tipe kupon "Diskon Mitra" dengan fitur diskon khusus untuk role Mitra, serta menampilkan informasi DP dan Pelunasan di halaman cart.
 * Version: 1.0
 * Author: Puji Ermanto | UCOK AKA
 * Author URI: https://ucokaka.com
 * Text Domain: override-voucher-mitra
 */
// ============================
// Tambah field Diskon Mitra di Product Data
// ============================
add_action('woocommerce_product_options_general_product_data', function () {
    woocommerce_wp_text_input([
        'id' => '_mitra_discount',
        'label' => __('Diskon Mitra (%)', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('Masukkan persentase diskon khusus untuk role Mitra. Contoh: 10 = 10%', 'woocommerce'),
        'type' => 'number',
        'custom_attributes' => [
            'step' => '0.01',
            'min' => '0'
        ],
    ]);
});

// ============================
// Simpan field Diskon Mitra
// ============================
add_action('woocommerce_admin_process_product_object', function ($product) {
    if (isset($_POST['_mitra_discount'])) {
        $product->update_meta_data('_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
});

// Tambahkan field "Diskon Mitra (%)" hanya jika tipe kupon = mitra_discount
add_action('woocommerce_coupon_options', function ($coupon_id) {
    global $post;

    $discount_type = get_post_meta($coupon_id, 'discount_type', true);

    // Tampilkan hanya jika discount_type = mitra_discount
    if ($discount_type === 'mitra_discount') {
        woocommerce_wp_text_input([
            'id' => '_mitra_discount',
            'label' => __('Diskon Mitra (%)', 'woocommerce'),
            'type' => 'number',
            'desc_tip' => true,
            'description' => __('Masukkan persentase diskon mitra global (contoh: 10 = 10%)', 'woocommerce'),
            'custom_attributes' => ['step' => '0.01', 'min' => '0']
        ]);
    }
});

// Simpan nilai field
add_action('woocommerce_coupon_options_save', function ($post_id) {
    if (isset($_POST['_mitra_discount'])) {
        update_post_meta($post_id, '_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
});


// ============================
// 3️⃣ Tambah tipe diskon baru di dropdown kupon
// ============================
add_filter('woocommerce_coupon_discount_types', function ($discount_types) {
    $discount_types['mitra_discount'] = __('Diskon Mitra', 'woocommerce');
    return $discount_types;
});

// ============================
// Simpan nilai _mitra_discount di kupon
// ============================
add_action('woocommerce_coupon_options_save', function ($post_id) {
    if (isset($_POST['_mitra_discount'])) {
        update_post_meta($post_id, '_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
});

// ============================
// Hitung Diskon Mitra + DP & Pelunasan
// ============================
add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!is_user_logged_in()) return;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array) $user->roles)) return;

    // Cari kupon Mitra aktif
    $mitra_coupon = null;
    foreach ($cart->get_applied_coupons() as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $mitra_coupon = $coupon;
            break;
        }
    }

    if (!$mitra_coupon) return;

    // Ambil persentase diskon dari kupon
    $mitra_discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
    if ($mitra_discount_percent <= 0) return;

    // Hitung total harga produk
    $cart_total = 0;
    foreach ($cart->get_cart() as $item) {
        $product = $item['data'];
        $qty = $item['quantity'];
        $cart_total += ((float) $product->get_price()) * $qty;
    }

    // Hitung total diskon
    $total_discount = ($mitra_discount_percent / 100) * $cart_total;
    $discounted_total = $cart_total - $total_discount;

    // Hitung DP & Pelunasan 50/50 dari harga bersih
    $dp_amount = $discounted_total / 2;
    $pelunasan_amount = $discounted_total / 2;

    // Simpan ke session supaya bisa dipakai di tampilan
    WC()->session->set('mitra_total_discount', $total_discount);
    WC()->session->set('mitra_dp_amount', $dp_amount);
    WC()->session->set('mitra_pelunasan_amount', $pelunasan_amount);

    // Hanya tambahkan fee untuk Diskon Mitra (negatif)
    $cart->add_fee(__('Diskon Mitra (' . $mitra_discount_percent . '%)', 'woocommerce'), -$total_discount, false);
}, 20);

// ============================
// Override tampilan kupon Mitra di cart
// ============================
add_filter('woocommerce_cart_totals_coupon_html', function ($html, $coupon) {
    if ($coupon->get_discount_type() === 'mitra_discount') {
        $percent = get_post_meta($coupon->get_id(), '_mitra_discount', true);
        $discount = WC()->session->get('mitra_total_discount') ?: 0;

        $custom_row = sprintf(
            '<tr class="c-cart__totals-discount cart-discount coupon-mitra">
                <th class="c-cart__sub-sub-header" style="color:#3A0BF4;">Diskon Mitra (%s%%)</th>
                <td class="c-cart__totals-price" style="color:#3A0BF4;">–<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">Rp</span>%s</span></td>
            </tr>',
            esc_html($percent ?: '0'),
            number_format($discount, 0, ',', '.')
        );
        return $custom_row;
    }
    return $html;
}, 10, 2);


// ============================
// Tampilkan DP & Pelunasan di cart tepat setelah Diskon Mitra
// ============================
add_action('woocommerce_cart_totals_before_order_total', function () {
    $dp = WC()->session->get('mitra_dp_amount') ?: 0;
    $pelunasan = WC()->session->get('mitra_pelunasan_amount') ?: 0;

    if ($dp > 0 && $pelunasan > 0) {
        // Baris DP
        echo '<tr class="fee cart-mitra-dp" style="border-top:10px solid transparent;">
                <th class="c-cart__sub-header">DP (50%)</th>
                <td class="c-cart__totals-price">
                    <span class="woocommerce-Price-amount amount">
                        <span class="woocommerce-Price-currencySymbol">Rp</span>' . number_format($dp, 0, ',', '.') . '
                    </span>
                </td>
              </tr>';

        // Baris Pelunasan
        echo '<tr class="fee cart-mitra-pelunasan" style="border-top:10px solid transparent;">
                <th class="c-cart__sub-header">Pelunasan (50%)</th>
                <td class="c-cart__totals-price">
                    <span class="woocommerce-Price-amount amount">
                        <span class="woocommerce-Price-currencySymbol">Rp</span>' . number_format($pelunasan, 0, ',', '.') . '
                    </span>
                </td>
              </tr>';
    }
});
