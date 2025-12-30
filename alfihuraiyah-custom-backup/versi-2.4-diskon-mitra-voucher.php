<?php

/**
 * Plugin Name: Override Voucher Mitra (Final Fix)
 * Description: Menambahkan tipe kupon "Diskon Mitra" dan menghitung diskon sesuai skema: voucher memotong total harga terlebih dahulu -> kemudian dihitung Harga Mitra -> lalu DP & Pelunasan. Menambahkan field harga mitra (%) di produk.
 * Version: 2.4
 * Author: Puji Ermanto | UCOK AKA (final fix)
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


/* =====================================================
   Helper functions
===================================================== */

/**
 * Ambil persentase voucher dari kupon (contoh: 25 = 25%).
 */
function ovm_get_applied_voucher_percent()
{
    $default = 25; // fallback jika belum ada kupon mitra aktif
    if (! WC()->cart) return $default;

    $applied = WC()->cart->get_applied_coupons();
    foreach ($applied as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $meta = get_post_meta($coupon->get_id(), '_mitra_discount', true);
            $val  = (float) $meta;
            if ($val > 0) return $val;
        }
    }
    return $default;
}


/**
 * Ambil persentase harga mitra dari product meta.
 * Default 50% jika kosong.
 */
function ovm_get_mitra_price_percent($product_id = null)
{
    $default = 50;
    if (! $product_id) return $default;

    $meta = get_post_meta($product_id, '_mitra_price_percent', true);
    $val  = (float) $meta;
    if ($val > 0) return $val;

    return $default;
}

/* =====================================================
   1️⃣ Tambah Field Harga Mitra (%) di Produk + Field Diskon Mitra di Kupon
===================================================== */
add_action('woocommerce_product_options_general_product_data', function () {
    // Field baru untuk harga mitra
    woocommerce_wp_text_input([
        'id' => '_mitra_price_percent',
        'label' => __('Persentase Harga Mitra (%)', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('Misal 50 berarti harga mitra = 50% dari total setelah voucher.', 'woocommerce'),
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
    ]);
});

add_action('woocommerce_admin_process_product_object', function ($product) {
    if (isset($_POST['_mitra_price_percent'])) {
        $product->update_meta_data('_mitra_price_percent', sanitize_text_field($_POST['_mitra_price_percent']));
    }
});

/* =====================================================
   2️⃣ Simpan tipe pembayaran (full/deposit) saat add to cart
===================================================== */
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    $ptype = isset($_REQUEST['payment_type']) ? sanitize_text_field($_REQUEST['payment_type']) : 'full';
    $cart_item_data['mitra_payment_type'] = $ptype;
    $cart_item_data['unique_key'] = md5(microtime() . rand());
    return $cart_item_data;
}, 20, 2);

/* =====================================================
   3️⃣ Hitung Rumus Diskon Mitra
===================================================== */
add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && ! defined('DOING_AJAX')) return;

    $user = wp_get_current_user();
    if (! in_array('mitra', (array) $user->roles)) return;

    $original_total = 0;
    $has_deposit = false;

    foreach ($cart->get_cart() as $item) {
        $product = $item['data'];
        $price   = (float) $product->get_regular_price();
        $qty     = (int) $item['quantity'];
        $original_total += $price * $qty;

        if (isset($item['mitra_payment_type']) && $item['mitra_payment_type'] === 'deposit') {
            $has_deposit = true;
        }
    }

    $voucher_percent = ovm_get_applied_voucher_percent();

    // ambil mitra_price_percent dari product pertama (asumsi semua sama)
    $first_item = reset($cart->get_cart());
    $first_product = $first_item['data'];
    $mitra_percent = ovm_get_mitra_price_percent($first_product->get_id());

    // hitung
    $after_voucher   = $original_total - ($original_total * ($voucher_percent / 100));
    $harga_mitra     = $after_voucher * ($mitra_percent / 100);
    $dp_mitra        = $harga_mitra * 0.5;

    // simpan ke session
    WC()->session->set('mitra_harga_mitra', $harga_mitra);
    WC()->session->set('mitra_dp_amount', $has_deposit ? $dp_mitra : 0);
    WC()->session->set('mitra_pelunasan_amount', $has_deposit ? $dp_mitra : 0);
    WC()->session->set('mitra_price_percent', $mitra_percent);
    WC()->session->set('mitra_voucher_percent', $voucher_percent);
    WC()->session->set('mitra_has_deposit', $has_deposit);

    if ($voucher_percent > 0) {
        $discount_value = $original_total * ($voucher_percent / 100);
        $cart->add_fee(sprintf('Diskon Voucher Mitra (%s%%)', $voucher_percent), -$discount_value);
    }
}, 999);

/* =====================================================
   4️⃣ Tampilkan di Cart
===================================================== */
add_action('woocommerce_cart_totals_before_order_total', function () {
    $harga_mitra = WC()->session->get('mitra_harga_mitra');
    $dp = WC()->session->get('mitra_dp_amount');
    $pelunasan = WC()->session->get('mitra_pelunasan_amount');
    $mitra_percent = WC()->session->get('mitra_price_percent');
    $has_deposit = WC()->session->get('mitra_has_deposit');

    if (! $harga_mitra) return;

    echo '<tr><th style="color:#3A0BF4;">Harga Mitra (' . esc_html($mitra_percent) . '%)</th><td>' . wc_price($harga_mitra) . '</td></tr>';
    if ($has_deposit) {
        echo '<tr><th style="color:#3A0BF4;">DP (50%)</th><td>' . wc_price($dp) . '</td></tr>';
        echo '<tr><th style="color:#3A0BF4;">Pelunasan (50%)</th><td>' . wc_price($pelunasan) . '</td></tr>';
        echo '<tr style="border-top:2px solid #000;"><th>Total Bayar (Checkout DP)</th><td><strong>' . wc_price($dp) . '</strong></td></tr>';
    } else {
        echo '<tr><th style="color:#3A0BF4;">Total Bayar (Full Payment)</th><td><strong>' . wc_price($harga_mitra) . '</strong></td></tr>';
    }
});

/* =====================================================
   5️⃣ Override Harga di Single Product
===================================================== */
add_filter('woocommerce_get_price_html', function ($price_html, $product) {
    if (! is_user_logged_in()) return $price_html;
    $user = wp_get_current_user();
    if (! in_array('mitra', (array) $user->roles)) return $price_html;

    $voucher_percent = ovm_get_applied_voucher_percent();
    $mitra_percent   = ovm_get_mitra_price_percent($product->get_id());
    $original = (float) $product->get_regular_price();

    if ($original <= 0) return $price_html;

    $after_voucher = $original - ($original * ($voucher_percent / 100));
    $harga_mitra = $after_voucher * ($mitra_percent / 100);

    return sprintf(
        '<del>%s</del> <ins>%s</ins><br><small style="color:#3A0BF4;">Harga Mitra (setelah diskon %s%% & potongan %s%%)</small>',
        wc_price($original),
        wc_price($harga_mitra),
        esc_html($voucher_percent),
        esc_html($mitra_percent)
    );
}, 30, 2);

/* =====================================================
   6️⃣ Tampilkan opsi Full / Deposit di Single Product
===================================================== */
add_filter('yith_wcdp_single_add_to_cart_fields_html', function ($html, $product) {
    if (! is_user_logged_in()) return $html;
    $user = wp_get_current_user();
    if (! in_array('mitra', (array) $user->roles)) return $html;

    $voucher_percent = ovm_get_applied_voucher_percent();
    $mitra_percent = ovm_get_mitra_price_percent($product->get_id());
    $original = (float) $product->get_regular_price();
    if ($original <= 0) return $html;

    $after_voucher = $original - ($original * ($voucher_percent / 100));
    $harga_mitra = $after_voucher * ($mitra_percent / 100);
    $dp = $harga_mitra * 0.5;
    $pelunasan = $dp;

    ob_start(); ?>
    <div class="yith-wcdp-single-add-to-cart-fields" data-deposit-type="rate" data-deposit-amount="<?php echo esc_attr($dp); ?>">
        <label class="full">
            <input type="radio" name="payment_type" value="full" checked="checked">
            <span class="label">
                Pay full amount
                <span class="price-label full-price">
                    <del><?php echo wc_price($original); ?></del>
                    <ins><?php echo wc_price($harga_mitra); ?></ins>
                </span><br>
                <small class="yith-wcdp-note">Harga Mitra setelah diskon <?php echo esc_html($voucher_percent); ?>% & potongan <?php echo esc_html($mitra_percent); ?>%</small>
            </span>
        </label>
        <label class="deposit">
            <input type="radio" name="payment_type" value="deposit">
            <span class="label">
                Pay deposit
                <span class="price-label deposit-price"><?php echo wc_price($dp); ?></span>
                <small class="yith-wcdp-expiration-notice">Balance payment (<?php echo wc_price($pelunasan); ?>) due <?php echo date_i18n('F j, Y', strtotime('+10 days')); ?></small>
            </span>
        </label>
    </div>
<?php
    return ob_get_clean();
}, 999, 2);
