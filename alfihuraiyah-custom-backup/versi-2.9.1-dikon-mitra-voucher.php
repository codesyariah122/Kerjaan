<?php

/**
 * Plugin Name: Override Voucher Mitra (v2.9.1 Final)
 * Description: Versi lengkap dengan admin field kupon, tipe "Diskon Mitra", perhitungan harga mitra, opsi "Bayar Lunas / Bayar DP" (Bahasa Indonesia penuh), dan kompatibel penuh dengan YITH Deposit.
 * Version: 2.9.1
 * Author: Puji Ermanto | UCOK AKA
 */

/* =====================================================
   🎯 BAGIAN ADMIN — Tambah Tipe Kupon & Field Diskon Mitra
===================================================== */

// Tambah field Diskon Mitra di halaman kupon
add_action('woocommerce_coupon_options', function ($coupon_id) {
    $discount_value = get_post_meta($coupon_id, '_mitra_discount', true);
    echo '<div class="options_group _mitra_discount_field" style="display:none;">';
    woocommerce_wp_text_input([
        'id' => '_mitra_discount',
        'label' => __('Diskon Mitra (%)', 'woocommerce'),
        'type' => 'number',
        'desc_tip' => true,
        'description' => __('Masukkan persentase diskon khusus untuk role Mitra (contoh: 50 = 50%)', 'woocommerce'),
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
        'value' => $discount_value ?: ''
    ]);
    echo '</div>';
});

// Simpan field Diskon Mitra
add_action('woocommerce_coupon_options_save', function ($post_id) {
    if (isset($_POST['_mitra_discount'])) {
        update_post_meta($post_id, '_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
});

// Tambah tipe diskon baru
add_filter('woocommerce_coupon_discount_types', function ($discount_types) {
    $discount_types['mitra_discount'] = __('Diskon Mitra', 'woocommerce');
    return $discount_types;
});

// Tampilkan field hanya saat tipe = Diskon Mitra
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'shop_coupon') :
?>
        <script>
            jQuery(document).ready(function($) {
                function toggleMitraField() {
                    var val = $('#discount_type').val();
                    if (val === 'mitra_discount') $('._mitra_discount_field').show();
                    else $('._mitra_discount_field').hide();
                }
                toggleMitraField();
                $(document).on('change', '#discount_type', toggleMitraField);
            });
        </script>
    <?php
    endif;
});

// Nonaktifkan perhitungan otomatis WooCommerce untuk tipe 'mitra_discount'
add_filter('woocommerce_coupon_is_valid_for_cart', function ($valid, $coupon) {
    if ($coupon->get_discount_type() === 'mitra_discount') {
        return false;
    }
    return $valid;
}, 10, 2);


/* =====================================================
   🔧 Helper Functions
===================================================== */
function ovm_get_applied_voucher_percent()
{
    if (!WC()->cart) return 0;
    foreach (WC()->cart->get_applied_coupons() as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $meta = get_post_meta($coupon->get_id(), '_mitra_discount', true);
            return (float)$meta;
        }
    }
    return 0;
}

function ovm_get_mitra_price_percent($pid = null)
{
    $default = 50;
    if (!$pid) return $default;
    $meta = get_post_meta($pid, '_mitra_price_percent', true);
    return $meta ? (float)$meta : $default;
}


/* =====================================================
   🧩 Blok Tampilan Pembayaran Mitra di Single Product
===================================================== */
function ovm_render_payment_options_html()
{
    if (!is_user_logged_in()) return '';
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return '';

    global $product;
    if (!$product) return '';

    $voucher_percent = ovm_get_applied_voucher_percent();
    $mitra_percent = ovm_get_mitra_price_percent($product->get_id());
    $original = (float)$product->get_regular_price();
    if ($original <= 0) return '';

    $after_voucher = ($voucher_percent > 0)
        ? $original - ($original * ($voucher_percent / 100))
        : $original;

    $harga_mitra = $after_voucher * ($mitra_percent / 100);
    $dp = $harga_mitra * 0.5;
    $pelunasan = $dp;

    ob_start(); ?>
    <div class="mitra-payment-options"
        style="margin-top:20px;border:1px solid #ddd;padding:15px;border-radius:10px;background:#fafafa;">
        <h4 style="margin-bottom:10px;color:#3A0BF4;">Pilih Skema Pembayaran</h4>

        <label style="display:block;margin-bottom:8px;">
            <input type="radio" name="payment_type" value="full" checked>
            <strong>Bayar Lunas:</strong>
            <span><?php echo wc_price($harga_mitra); ?></span><br>
            <?php if ($voucher_percent > 0): ?>
                <small style="color:#3A0BF4;">
                    Harga Mitra setelah diskon <?php echo esc_html($voucher_percent); ?>%
                    & potongan <?php echo esc_html($mitra_percent); ?>%
                </small>
            <?php else: ?>
                <small style="color:#888;">Simulasi harga (belum pakai voucher)</small>
            <?php endif; ?>
        </label>

        <label style="display:block;margin-bottom:8px;">
            <input type="radio" name="payment_type" value="deposit">
            <strong>Bayar DP (50%):</strong>
            <span><?php echo wc_price($dp); ?></span><br>
            <small>
                Sisa pelunasan (<?php echo wc_price($pelunasan); ?>)
                sebelum <?php echo date_i18n('j F Y', strtotime('+10 days')); ?>
            </small>
        </label>
    </div>
<?php
    return ob_get_clean();
}


/* =====================================================
   🧱 Injector fallback DOM & Hook tambahan
===================================================== */
function ovm_inject_payment_options_dom($content)
{
    if (!is_product()) return $content;
    $html = ovm_render_payment_options_html();
    if (!$html) return $content;
    return preg_replace('/<\/form>/', $html . '</form>', $content, 1);
}
add_filter('the_content', 'ovm_inject_payment_options_dom', 9999);

function ovm_render_payment_options_block()
{
    echo ovm_render_payment_options_html();
}
add_action('woocommerce_after_add_to_cart_form', 'ovm_render_payment_options_block', 9999);


/* =====================================================
   🪄 Override YITH Deposit (hapus semua tampilan bawaan)
===================================================== */
add_action('init', function () {
    remove_all_actions('yith_wcdp_single_add_to_cart_fields');
    remove_all_actions('yith_wcdp_after_single_add_to_cart_button');
    remove_all_actions('yith_wcdp_before_single_add_to_cart_button');
    remove_all_actions('woocommerce_before_add_to_cart_button');
    remove_all_actions('woocommerce_after_add_to_cart_form');
});

add_action('wp', function () {
    if (!is_product()) return;

    global $wp_filter;
    $hooks_to_check = [
        'woocommerce_single_product_summary',
        'woocommerce_before_add_to_cart_button',
        'woocommerce_after_add_to_cart_form'
    ];

    foreach ($hooks_to_check as $hook) {
        if (isset($wp_filter[$hook])) {
            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback_id => $callback) {
                    if (
                        (is_array($callback['function']) && strpos($callback['function'][0], 'YITH') !== false)
                        || (is_string($callback['function']) && strpos($callback['function'], 'yith_wcdp') !== false)
                    ) {
                        unset($wp_filter[$hook]->callbacks[$priority][$callback_id]);
                    }
                }
            }
        }
    }

    // Paksa tampilkan blok Mitra versi Indonesia
    add_action('woocommerce_after_add_to_cart_form', function () {
        echo ovm_render_payment_options_html();
    }, 9999);
});
