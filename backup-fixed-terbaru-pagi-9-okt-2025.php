<?php
<?php

/**
 * Plugin Name: Override Voucher Mitra
 * Description: Menambahkan tipe kupon "Diskon Mitra" dengan fitur diskon khusus untuk role Mitra, serta menampilkan informasi DP dan Pelunasan di halaman cart.
 * Version: 1.0
 * Author: Puji Ermanto | UCOK AKA
 * Author URI: https://ucokaka.com
 * Text Domain: override-voucher-mitra
 */
/**
 * Plugin Name: Override Voucher Mitra
 * Description: Menambahkan tipe kupon "Diskon Mitra" dengan fitur diskon khusus untuk role Mitra, serta menampilkan informasi DP dan Pelunasan di halaman cart.
 * Version: 1.0
 * Author: Puji Ermanto | UCOK AKA
 * Author URI: https://ucokaka.com
 * Text Domain: override-voucher-mitra
 */
// ============================
// 1️⃣ Tambah field Diskon Mitra di Product Data
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
// 2️⃣ Simpan field Diskon Mitra
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
// 4️⃣ Simpan nilai _mitra_discount di kupon
// ============================
add_action('woocommerce_coupon_options_save', function ($post_id) {
    if (isset($_POST['_mitra_discount'])) {
        update_post_meta($post_id, '_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
});

// Update perbaikan terbaru 
add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    $mitra_discount_percent = 50; // contoh
    $cart_total = 0;

    foreach ($cart->get_cart() as $item) {
        $product = $item['data'];
        $qty = $item['quantity'];
        $price = (float)$product->get_regular_price();
        $cart_total += $price * $qty;
    }

    // Terapkan diskon ke total harga produk
    $total_discount = ($mitra_discount_percent / 100) * $cart_total;
    $discounted_total = $cart_total - $total_discount;

    // Hitung DP & Pelunasan setelah diskon
    $dp = $discounted_total / 2;
    $pelunasan = $discounted_total / 2;

    // Simpan ke session
    WC()->session->set('mitra_total_discount', $total_discount);
    WC()->session->set('mitra_dp_amount', $dp);
    WC()->session->set('mitra_pelunasan_amount', $pelunasan);

    // Tambahkan diskon ke cart
    $cart->add_fee(sprintf('Diskon Mitra (%d%%)', $mitra_discount_percent), -$total_discount);
}, 20);

// ============================
// 💡 Terapkan Diskon Mitra langsung ke harga produk (agar YITH ikut terpengaruh)
// ============================
add_filter('woocommerce_product_get_price', function ($price, $product) {
    if (!is_user_logged_in()) return $price;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array) $user->roles)) return $price;

    // Cek apakah kupon mitra aktif
    $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
    $mitra_coupon = null;

    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $mitra_coupon = $coupon;
            break;
        }
    }

    if (!$mitra_coupon) return $price;

    $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
    if ($discount_percent <= 0) return $price;

    // Hitung harga baru
    $discounted_price = (float) $price - ((float) $price * ($discount_percent / 100));
    return $discounted_price;
}, 10, 2);

// ============================
// 💡 Terapkan juga diskon Mitra ke harga DEPOSIT YITH
// ============================
add_filter('yith_wcdp_get_deposit_amount', function ($deposit_amount, $product_id, $cart_item) {
    if (!is_user_logged_in()) return $deposit_amount;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array) $user->roles)) return $deposit_amount;

    // Cari kupon mitra aktif
    $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
    $mitra_coupon = null;

    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $mitra_coupon = $coupon;
            break;
        }
    }

    if (!$mitra_coupon) return $deposit_amount;

    $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
    if ($discount_percent <= 0) return $deposit_amount;

    // 🔽 Terapkan diskon langsung ke nilai deposit
    $deposit_amount = $deposit_amount - ($deposit_amount * ($discount_percent / 100));

    return $deposit_amount;
}, 10, 3);

// ============================
// 💡 Terapkan diskon Mitra ke tampilan harga DEPOSIT di halaman produk
// ============================
add_filter('yith_wcdp_get_product_price', function ($price, $product) {
    if (!is_user_logged_in()) return $price;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array) $user->roles)) return $price;

    // Cek apakah kupon mitra aktif
    $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
    $mitra_coupon = null;

    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $mitra_coupon = $coupon;
            break;
        }
    }

    if (!$mitra_coupon) return $price;

    $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
    if ($discount_percent <= 0) return $price;

    // Terapkan potongan ke harga dasar produk
    $discounted_price = $price - ($price * ($discount_percent / 100));

    return $discounted_price;
}, 10, 2);

// ============================
// 💡 Kompatibilitas YITH DEPOSIT (Premium) — tampilkan harga diskon di single product
// ============================
add_filter('yith_wcdp_product_get_deposit_amount_from_price', function ($deposit_amount, $price, $product) {
    if (!is_user_logged_in()) return $deposit_amount;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array) $user->roles)) return $deposit_amount;

    // Cari kupon mitra aktif
    $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
    $mitra_coupon = null;

    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $mitra_coupon = $coupon;
            break;
        }
    }

    if (!$mitra_coupon) return $deposit_amount;

    $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
    if ($discount_percent <= 0) return $deposit_amount;

    // 🔽 Diskon diterapkan ke harga deposit YITH di single product
    $discounted_deposit = $deposit_amount - ($deposit_amount * ($discount_percent / 100));

    return $discounted_deposit;
}, 10, 3);

// ============================
// 💡 Tampilkan harga deposit yang sudah kena diskon di halaman produk (kompatibel semua versi YITH)
// ============================
add_filter('yith_wcdp_single_product_price_html', function ($html, $product) {
    if (!is_user_logged_in()) return $html;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array) $user->roles)) return $html;

    // Cari kupon mitra aktif
    $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
    $mitra_coupon = null;

    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $mitra_coupon = $coupon;
            break;
        }
    }

    if (!$mitra_coupon) return $html;

    $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
    if ($discount_percent <= 0) return $html;

    // Ambil harga produk asli
    $price = (float) $product->get_price();
    $discounted_price = $price - ($price * ($discount_percent / 100));

    // Ambil nilai deposit rate dari meta produk
    $deposit_rate = (float) get_post_meta($product->get_id(), '_yith_wcdp_deposit_rate', true);
    if ($deposit_rate <= 0) $deposit_rate = 30;

    $deposit_amount = ($deposit_rate / 100) * $discounted_price;
    $balance_amount = $discounted_price - $deposit_amount;

    // 🔽 Bangun ulang tampilan HTML agar mencerminkan harga baru
    $html  = '<div class="yith-wcdp-product-prices">';
    $html .= '<p><strong>Pay full amount</strong><br>' . wc_price($discounted_price) . '</p>';
    $html .= '<p><strong>Pay deposit</strong><br>' . wc_price($deposit_amount) . '<br>';
    $html .= '<small>Balance payment will be required on ' . date_i18n('F j, Y', strtotime('+10 days')) . '</small></p>';
    $html .= '</div>';

    return $html;
}, 10, 2);

// ============================
// 💡 Terapkan diskon Mitra langsung ke harga regular & sale (agar muncul di single product YITH)
// ============================
add_filter('woocommerce_product_get_regular_price', 'mitra_apply_price_discount', 99, 2);
add_filter('woocommerce_product_get_sale_price', 'mitra_apply_price_discount', 99, 2);
add_filter('woocommerce_product_get_price', 'mitra_apply_price_discount', 99, 2);

// function mitra_apply_price_discount($price, $product)
// {
//     if (!is_user_logged_in() || !$price) return $price;

//     $user = wp_get_current_user();
//     if (!in_array('mitra', (array)$user->roles)) return $price;

//     // Cari kupon mitra aktif
//     $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
//     $mitra_coupon = null;

//     foreach ($applied_coupons as $code) {
//         $coupon = new WC_Coupon($code);
//         if ($coupon->get_discount_type() === 'mitra_discount') {
//             $mitra_coupon = $coupon;
//             break;
//         }
//     }

//     if (!$mitra_coupon) return $price;

//     $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
//     if ($discount_percent <= 0) return $price;

//     // 💰 Terapkan potongan langsung ke semua jenis harga produk
//     $discounted = $price - ($price * ($discount_percent / 100));

//     return $discounted;
// }
// ============================
// 💡 Terapkan diskon langsung untuk user role 'mitra' di single product (tanpa butuh kupon aktif)
// ============================
add_filter('woocommerce_get_price_html', function ($price_html, $product) {
    if (!is_user_logged_in()) return $price_html;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return $price_html;

    // Ambil diskon default (misalnya dari opsi global atau meta kupon pertama)
    $discount_percent = 50; // Ganti sesuai nilai diskon mitra default kamu

    $regular_price = (float) $product->get_regular_price();
    if ($regular_price <= 0) return $price_html;

    $discounted_price = $regular_price - ($regular_price * ($discount_percent / 100));

    // Ganti tampilan HTML harga
    $price_html = '<span class="price"><del>' . wc_price($regular_price) . '</del> <ins>' . wc_price($discounted_price) . '</ins></span>';

    return $price_html;
}, 20, 2);



// ============================
// 6️⃣ Override tampilan kupon Mitra di cart
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
// 💡 Perbaiki tampilan harga di single product (pilihan full & deposit) agar ikut Diskon Mitra
// ============================
add_filter('yith_wcdp_single_add_to_cart_fields_html', function ($html, $product) {
    if (!is_user_logged_in()) return $html;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return $html;

    // Cari kupon mitra aktif
    $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
    $mitra_coupon = null;
    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $mitra_coupon = $coupon;
            break;
        }
    }
    if (!$mitra_coupon) return $html;

    $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
    if ($discount_percent <= 0) return $html;

    // Ambil harga produk asli
    $price = (float) $product->get_regular_price();
    if ($price <= 0) return $html;

    // 💡 Terapkan diskon ke total harga dulu (baru dihitung DP & pelunasan)
    $discounted_total = $price - ($price * ($discount_percent / 100));

    // Ambil nilai deposit rate (default 50%)
    $deposit_rate = (float) get_post_meta($product->get_id(), '_yith_wcdp_deposit_rate', true);
    if ($deposit_rate <= 0) $deposit_rate = 50;

    // 💡 DP & Pelunasan dihitung dari total setelah diskon
    $deposit_amount = ($deposit_rate / 100) * $discounted_total;
    $balance_amount = $discounted_total - $deposit_amount;

    // 🔁 Bangun ulang tampilan harga
    $html = '
    <div class="yith-wcdp-single-add-to-cart-fields" data-deposit-type="rate" data-deposit-amount="' . esc_attr($deposit_amount) . '" data-deposit-rate="' . esc_attr($deposit_rate) . '">
        <label class="full">
            <input type="radio" name="payment_type" value="full" checked="checked">
            <span class="label">
                Pay full amount
                <span class="price-label full-price">
                    <span class="price"><del>' . wc_price($price) . '</del> <ins>' . wc_price($discounted_total) . '</ins></span>
                </span>
            </span>
        </label>
        <label class="deposit">
            <input type="radio" name="payment_type" value="deposit">
            <span class="label">
                Pay deposit
                <span class="price-label deposit-price">' . wc_price($deposit_amount) . '</span>
                <small class="yith-wcdp-expiration-notice">
                    Balance payment will be required on <span class="expiration-date">' . date_i18n('F j, Y', strtotime('+10 days')) . '</span> (' . wc_price($balance_amount) . ')
                </small>
            </span>
        </label>
    </div>';

    return $html;
}, 20, 2);



// ============================
// 7️⃣ Tampilkan DP & Pelunasan di cart (hanya jika ada produk dengan deposit aktif)
// ============================
add_action('woocommerce_cart_totals_before_order_total', function () {
    $dp = WC()->session->get('mitra_dp_amount') ?: 0;
    $pelunasan = WC()->session->get('mitra_pelunasan_amount') ?: 0;

    // ✅ Tampilkan hanya jika produk dengan deposit aktif ditemukan
    if ($dp > 0 && $pelunasan > 0) {
        echo '<tr class="fee cart-mitra-dp" style="border-top:10px solid transparent;">
                <th class="c-cart__sub-header" style="color:#3A0BF4;">DP (50%)</th>
                <td class="c-cart__totals-price" style="color:#3A0BF4;">
                    <span class="woocommerce-Price-amount amount">
                        <span class="woocommerce-Price-currencySymbol">Rp</span>' . number_format($dp, 0, ',', '.') . '
                    </span>
                </td>
              </tr>';

        echo '<tr class="fee cart-mitra-pelunasan" style="border-top:0;">
                <th class="c-cart__sub-header" style="color:#3A0BF4;">Pelunasan (50%)</th>
                <td class="c-cart__totals-price" style="color:#3A0BF4;">
                    <span class="woocommerce-Price-amount amount">
                        <span class="woocommerce-Price-currencySymbol">Rp</span>' . number_format($pelunasan, 0, ',', '.') . '
                    </span>
                </td>
              </tr>';
    }
});
