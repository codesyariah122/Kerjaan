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

// Tambahkan field "Diskon Mitra (%)" di tab General coupon
add_action('woocommerce_coupon_options', function ($coupon_id) {
    $discount_value = get_post_meta($coupon_id, '_mitra_discount', true);

    echo '<div class="options_group _mitra_discount_field" style="display:none;">';
    woocommerce_wp_text_input([
        'id' => '_mitra_discount',
        'label' => __('Diskon Mitra (%)', 'woocommerce'),
        'type' => 'number',
        'desc_tip' => true,
        'description' => __('Masukkan persentase diskon mitra global (contoh: 10 = 10%)', 'woocommerce'),
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
        'value' => $discount_value ?: ''
    ]);
    echo '</div>';
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
// Tambahkan script agar field muncul otomatis
// ============================
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'shop_coupon') :
?>
        <script>
            jQuery(document).ready(function($) {
                function toggleMitraField() {
                    var val = $('#discount_type').val();
                    if (val === 'mitra_discount') {
                        $('._mitra_discount_field').show(); // ✅ ubah jadi class
                    } else {
                        $('._mitra_discount_field').hide();
                    }
                }
                toggleMitraField();
                $(document).on('change', '#discount_type', toggleMitraField);
            });
        </script>
<?php
    endif;
});

// ============================
// 4️⃣ Simpan nilai _mitra_discount di kupon
// ============================
add_action('woocommerce_coupon_options_save', function ($post_id) {
    if (isset($_POST['_mitra_discount'])) {
        update_post_meta($post_id, '_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
});

// 🚫 Nonaktifkan perhitungan kupon default untuk tipe 'mitra_discount'
add_filter('woocommerce_coupon_is_valid_for_cart', function ($valid, $coupon) {
    if ($coupon->get_discount_type() === 'mitra_discount') {
        return false; // biar WooCommerce tidak hitung ulang
    }
    return $valid;
}, 10, 2);

add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!is_user_logged_in() || !in_array('mitra', (array)wp_get_current_user()->roles)) return;

    $raw_subtotal = 0; // Subtotal dari harga normal produk
    foreach ($cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $raw_subtotal += (float)$product->get_regular_price() * $cart_item['quantity'];
    }

    // 1. Terapkan Diskon Mitra 50% (jika ini adalah diskon standar Harga Mitra)
    $discount_mitra_50_percent = $raw_subtotal * 0.5;
    $subtotal_after_50_percent_discount = $raw_subtotal - $discount_mitra_50_percent;

    // 2. Ambil diskon kupon Mitra (25%)
    $voucher_percent = 0;
    foreach ($cart->get_applied_coupons() as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $voucher_percent = (float)get_post_meta($coupon->get_id(), '_mitra_discount', true);
            break;
        }
    }
    // Jika tidak ada kupon diskon mitra, voucher_percent tetap 0
    $discount_mitra_coupon_25_percent = ($voucher_percent / 100) * $subtotal_after_50_percent_discount;

    // Total akhir setelah semua diskon
    $total_after_all_discounts = $subtotal_after_50_percent_discount - $discount_mitra_coupon_25_percent;

    // Simpan ke session
    WC()->session->set('mitra_raw_subtotal', $raw_subtotal); // Rp 339.000
    WC()->session->set('mitra_discount_50_percent_amount', $discount_mitra_50_percent); // Rp 169.500
    WC()->session->set('mitra_discount_coupon_25_percent_amount', $discount_mitra_coupon_25_percent); // Rp 42.375 (25% dari Rp169.500)
    WC()->session->set('mitra_total_after_all_discounts', $total_after_all_discounts); // Rp 127.125
    WC()->session->set('mitra_calculated_dp', $total_after_all_discounts * 0.5); // Rp 63.562,5
    WC()->session->set('mitra_calculated_pelunasan', $total_after_all_discounts * 0.5); // Rp 63.562,5

    // Tambahkan diskon sebagai fee agar mempengaruhi total cart
    // $cart->add_fee( 'Diskon Harga Mitra (50%)', -$discount_mitra_50_percent );
    // $cart->add_fee( sprintf('Diskon Kupon Mitra (%d%%)', $voucher_percent), -$discount_mitra_coupon_25_percent );

}, 10); // Prioritas agar dihitung sebelum total akhir

// ============================
// 💡 Terapkan juga diskon Mitra ke harga DEPOSIT YITH
// ============================
add_filter('yith_wcdp_get_deposit_amount', function ($deposit_amount, $product_id, $cart_item) {
    if (!is_user_logged_in()) return $deposit_amount;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return $deposit_amount;

    // Ambil harga produk asli (bukan harga deposit)
    $product = wc_get_product($product_id);
    $price = (float)$product->get_regular_price();

    // Terapkan diskon mitra 50% dari total harga produk
    $discount_percent = 50;
    $discounted_total = $price - ($price * ($discount_percent / 100));

    // Ambil rate DP (default 50%)
    $deposit_rate = (float)get_post_meta($product_id, '_yith_wcdp_deposit_rate', true);
    if ($deposit_rate <= 0) $deposit_rate = 50;

    // Hitung DP dari total setelah diskon (bukan dari deposit)
    $deposit_amount = ($deposit_rate / 100) * $discounted_total;

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
    if (!is_user_logged_in() || !in_array('mitra', (array)wp_get_current_user()->roles)) return;

    $raw_subtotal = WC()->session->get('mitra_raw_subtotal') ?: 0; // Harga normal
    $discount_mitra_coupon = WC()->session->get('mitra_discount_coupon_25_percent_amount') ?: 0; // Diskon 25% dari kupon

    $shipping_cost = WC()->cart->get_shipping_total(); // Ambil ongkir dari WooCommerce
    // Jika Anda ingin diskon 50% Harga Mitra juga ditampilkan terpisah:
    $discount_mitra_50_percent_amount = WC()->session->get('mitra_discount_50_percent_amount') ?: 0;

    // Asumsi Total Tagihan Invoice adalah (Raw Subtotal - Diskon 50% - Diskon 25% + Ongkir)
    $total_tagihan_invoice = $raw_subtotal - $discount_mitra_50_percent_amount - $discount_mitra_coupon + $shipping_cost;

    $dp_amount = $total_tagihan_invoice * 0.5;
    $pelunasan_amount = $total_tagihan_invoice * 0.5;

    // Tampilkan sesuai format revisi D
    echo '<tr class="cart-subtotal custom-subtotal">
            <th>Subtotal</th>
            <td>' . wc_price($raw_subtotal) . '</td>
          </tr>';
    // Tampilkan diskon 50% Harga Mitra jika Anda ingin itu terlihat sebagai baris terpisah
    echo '<tr class="cart-discount custom-mitra-50">
            <th>Diskon Mitra (50%)</th>
            <td>–' . wc_price($discount_mitra_50_percent_amount) . '</td>
          </tr>';
    echo '<tr class="cart-discount custom-kupon-mitra">
            <th>Discount Mitra (25%)</th>
            <td>–' . wc_price($discount_mitra_coupon) . '</td>
          </tr>';
    echo '<tr class="shipping custom-shipping">
            <th>Ongkos Kirim</th>
            <td>' . wc_price($shipping_cost) . '</td>
          </tr>';
    echo '<tr class="order-total custom-total-invoice">
            <th>Total Tagihan Invoice</th>
            <td>' . wc_price($total_tagihan_invoice) . '</td>
          </tr>';
    echo '<tr class="fee custom-dp">
            <th>DP 50%</th>
            <td>' . wc_price($dp_amount) . '</td>
          </tr>';
    echo '<tr class="fee custom-pelunasan">
            <th>Pelunasan 50%</th>
            <td>' . wc_price($pelunasan_amount) . '</td>
          </tr>';

    // Simpan nilai akhir DP untuk Total Bayar (Checkout DP)
    WC()->session->set('final_checkout_dp_amount', $dp_amount);
}, 15); // Prioritas setelah kupon dihitung

// ============================
// 💡 Tampilkan harga diskon di MINI CART (fix untuk theme yang override template)
// ============================
add_filter('woocommerce_widget_cart_item_quantity', function ($quantity_html, $cart_item, $cart_item_key) {
    if (!is_user_logged_in()) return $quantity_html;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return $quantity_html;

    $product = $cart_item['data'];
    $qty = $cart_item['quantity'];
    $regular_price = (float) $product->get_regular_price();
    if ($regular_price <= 0) return $quantity_html;

    // Ambil diskon mitra (dari kupon atau default 50%)
    $discount_percent = 50;
    $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $discount_percent = (float) get_post_meta($coupon->get_id(), '_mitra_discount', true);
            break;
        }
    }

    // Hitung harga diskon
    $discounted_price = $regular_price - ($regular_price * ($discount_percent / 100));

    // Jika pakai deposit
    if (!empty($cart_item['deposit']) && $cart_item['deposit'] === 'yes') {
        $deposit_rate = (float) get_post_meta($product->get_id(), '_yith_wcdp_deposit_rate', true);
        if ($deposit_rate <= 0) $deposit_rate = 50;
        $discounted_price = ($deposit_rate / 100) * $discounted_price;
    }

    $discounted_total = $discounted_price * $qty;

    // 🔁 Ganti tampilan "1 × RpXXX"
    $quantity_html = sprintf(
        '<span class="quantity">%d × <del>%s</del> <ins>%s</ins></span>',
        $qty,
        wc_price($regular_price),
        wc_price($discounted_price)
    );

    return $quantity_html;
}, 20, 3);

// ============================
// 💡 Ubah subtotal mini cart agar ikut diskon mitra
// ============================
add_filter('woocommerce_widget_shopping_cart_total', function ($subtotal_html) {
    if (!is_user_logged_in()) return $subtotal_html;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return $subtotal_html;

    $cart_total = WC()->cart->get_subtotal();
    $discount_percent = 50;

    $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $discount_percent = (float) get_post_meta($coupon->get_id(), '_mitra_discount', true);
            break;
        }
    }

    $discounted_total = $cart_total - ($cart_total * ($discount_percent / 100));

    $subtotal_html = sprintf(
        '<span class="c-product-list-widget__total-title">Subtotal</span> <del>%s</del> <ins>%s</ins>',
        wc_price($cart_total),
        wc_price($discounted_total)
    );

    return $subtotal_html;
}, 20);

/**
 * ✅ Koreksi total bayar agar mengikuti DP setelah diskon
 * (tanpa double potongan dari YITH Deposit)
 */
add_action('woocommerce_cart_totals_after_order_total', function () {
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    $dp = WC()->session->get('mitra_dp_amount') ?: 0;
    if ($dp <= 0) return;

    echo '<tr class="fee mitra-total-bayar" style="border-top: 2px solid #000;">
        <th class="c-cart__sub-header" style="
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 800;
            color: #000;
            font-size: 1.1em;
            padding-top: 12px;
        ">
            Total Bayar (Checkout DP)
        </th>
        <td class="c-cart__totals-price" style="
            font-size: 1.8em;
            font-weight: 900;
            color: #3A0BF4;
            text-shadow: 0 0 3px rgba(58, 11, 244, 0.2);
            padding-top: 12px;
        ">
            ' . wc_price($dp) . '
        </td>
    </tr>';
});

// 🔧 Sembunyikan baris "order-total" WooCommerce bawaan di cart
add_filter('woocommerce_cart_totals_order_total_html', function ($value) {
    $user = wp_get_current_user();
    if (in_array('mitra', (array)$user->roles)) {
        return ''; // kosongkan baris total default
    }
    return $value;
});

/**
 * 🎯 Tambah CSS hanya untuk user role mitra & jika ada diskon mitra
 */
add_action('wp_head', function () {
    if (!is_user_logged_in()) return;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    // cek apakah session diskon mitra aktif
    $total_discount = WC()->session->get('mitra_total_discount') ?: 0;
    if ($total_discount <= 0) return;

    echo '<style>
    /* 🔒 Sembunyikan total bawaan WooCommerce + YITH Deposit hanya untuk mitra */
    .cart_totals .order-total,
    .cart_totals small.yith-wcdp-balance-info,
    .cart_totals .order-total td small,
    /* Sembunyikan baris-baris lama yang tidak diinginkan */
    .cart_totals .cart-mitra-diskon, /* Jika ini adalah baris diskon 50% lama */
    .cart_totals .cart-mitra-harga,
    .cart_totals .cart-mitra-dp, /* Jika ini adalah baris DP lama */
    .cart_totals .cart-mitra-pelunasan /* Jika ini adalah baris Pelunasan lama */
    {
        display: none !important;
    }

    /* 💡 Tampilkan hanya baris "Total Bayar (Checkout DP)" baru */
    .cart_totals .mitra-total-bayar {
        display: table-row !important;
    }

    /* --- Style untuk baris custom Anda --- */
    .cart_totals .custom-subtotal th,
    .cart_totals .custom-subtotal td,
    .cart_totals .custom-mitra-50 th,
    .cart_totals .custom-mitra-50 td,
    .cart_totals .custom-kupon-mitra th,
    .cart_totals .custom-kupon-mitra td,
    .cart_totals .custom-shipping th,
    .cart_totals .custom-shipping td,
    .cart_totals .custom-total-invoice th,
    .cart_totals .custom-total-invoice td,
    .cart_totals .custom-dp th,
    .cart_totals .custom-dp td,
    .cart_totals .custom-pelunasan th,
    .cart_totals .custom-pelunasan td {
        /* Sesuaikan style sesuai kebutuhan Anda, misal warna, font-weight */
    }
    .cart_totals .custom-total-invoice {
        border-top: 1px solid #eee; /* Garis pemisah */
    }
</style>';
});

// 1) Terapkan harga Mitra langsung ke cart items sebelum totals dihitung
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && ! defined('DOING_AJAX')) return;
    if (empty($cart) || ! is_user_logged_in()) return;

    $user = wp_get_current_user();
    if (! in_array('mitra', (array) $user->roles)) return;

    // Cari kupon mitra aktif (jika ada)
    $voucher_percent = 0;
    $applied = $cart->get_applied_coupons();
    foreach ($applied as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $voucher_percent = (float) get_post_meta($coupon->get_id(), '_mitra_discount', true);
            break;
        }
    }
    // fallback jika tidak ada kupon
    if ($voucher_percent <= 0) $voucher_percent = 50; // sesuaikan default kamu

    $original_total = 0;
    $yith_deposit_total = 0;

    foreach ($cart->get_cart() as $cart_item_key => &$cart_item) {
        // hanya apply sekali per item
        if (! empty($cart_item['mitra_price_applied'])) continue;

        $product = $cart_item['data'];
        $regular = (float) $product->get_regular_price();
        if ($regular <= 0) {
            // jika produk tidak punya regular price gunakan get_price()
            $regular = (float) $product->get_price();
        }

        $mitra_price = $regular * 0.5;

        if ($voucher_percent > 0) {
            $mitra_price = $mitra_price - ($mitra_price * ($voucher_percent / 100));
        }

        // set harga unit pada cart item sehingga semua kalkulasi berikutnya pakai harga ini
        $cart_item['data']->set_price($mitra_price);

        // flag agar tidak apply lagi
        $cart_item['mitra_price_applied'] = true;

        // untuk perhitungan session
        $original_total += $regular * $cart_item['quantity'];

        // jika punya info deposit dari YITH, coba akumulasikan deposit_amount (fallback nanti)
        if (! empty($cart_item['_deposit_info']['deposit_amount'])) {
            $yith_deposit_total += (float) $cart_item['_deposit_info']['deposit_amount'] * (int) $cart_item['quantity'];
        }
    }

    $cart_subtotal = $cart->get_subtotal();
    $dp_mitra = $cart_subtotal * 0.5;

    $discount_amount = ($voucher_percent / 100) * $dp_mitra;

    $final_checkout_dp = $dp_mitra - $discount_amount;
    $pelunasan = $dp_mitra;
    WC()->session->set('mitra_total_discount', $discount_amount);
    WC()->session->set('mitra_dp_amount', $final_checkout_dp);
    WC()->session->set('mitra_pelunasan_amount', $pelunasan);
}, 20, 1);

// 2) Pastikan order items disesuaikan saat order dibuat (safety)
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (! is_user_logged_in()) return;
    $user = wp_get_current_user();
    if (! in_array('mitra', (array) $user->roles)) return;

    // mapping cart product_id => cart item (ambil data WC_Cart)
    $cart_map = [];
    if (WC()->cart) {
        foreach (WC()->cart->get_cart() as $ci) {
            $cart_map[$ci['product_id']] = $ci;
        }
    }

    foreach ($order->get_items() as $item_id => $item) {
        if (! $item instanceof WC_Order_Item_Product) continue;
        $pid = $item->get_product_id();
        if (isset($cart_map[$pid])) {
            $ci = $cart_map[$pid];
            $unit_price = (float) $ci['data']->get_price();
            $qty = (int) $ci['quantity'];
            $line_total = $unit_price * $qty;

            // set subtotal & total untuk item (tanpa tax)
            $item->set_subtotal($line_total);
            $item->set_subtotal_tax(0);
            $item->set_total($line_total);
            $item->set_total_tax(0);

            // save item
            $item->save();
        }
    }

    $order->calculate_totals(false);
}, 10, 2);
