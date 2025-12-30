/**
 * Plugin Name: Override Voucher Mitra (v2.9 Final)
 * Description: Versi lengkap sesuai dokumen Alfihuraiyah Mitra — menghitung harga mitra, menampilkan opsi "Bayar Lunas / Bayar DP" (Bahasa Indonesia penuh), dan selalu muncul walau YITH override aktif.
 * Version: 2.9
 * Author: Puji Ermanto | UCOK AKA
 */

add_action('woocommerce_after_add_to_cart_form', 'ovm_render_payment_options_block', 9999);
add_filter('the_content', 'ovm_inject_payment_options_dom', 9999);

/* =====================================================
   🔧 Helper Functions
===================================================== */
function ovm_get_applied_voucher_percent() {
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

function ovm_get_mitra_price_percent($pid = null) {
    $default = 50;
    if (!$pid) return $default;
    $meta = get_post_meta($pid, '_mitra_price_percent', true);
    return $meta ? (float)$meta : $default;
}

/* =====================================================
   🧩 Blok Tampilan Pembayaran Mitra
===================================================== */
function ovm_render_payment_options_html() {
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
   🧱 Injector cadangan (DOM)
===================================================== */
function ovm_inject_payment_options_dom($content) {
    if (!is_product()) return $content;
    $html = ovm_render_payment_options_html();
    if (!$html) return $content;
    return preg_replace('/<\/form>/', $html . '</form>', $content, 1);
}

/* =====================================================
   🪄 Hook fallback di bawah tombol Add to Cart
===================================================== */
function ovm_render_payment_options_block() {
    echo ovm_render_payment_options_html();
}

/* =====================================================
   🧩 Override penuh YITH Deposit — Bahasa Indonesia (Force Late)
===================================================== */
add_action('template_redirect', function() {
    // Pastikan halaman produk
    if (!is_product()) return;

    // Jalankan override setelah semua plugin siap
    add_action('woocommerce_single_product_summary', function() {
        // Hilangkan semua hook YITH (default Inggris)
        global $wp_filter;
        if (isset($wp_filter['woocommerce_single_product_summary'])) {
            foreach ($wp_filter['woocommerce_single_product_summary']->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback_id => $callback) {
                    if (is_array($callback['function']) &&
                        is_string($callback['function'][1]) &&
                        strpos($callback['function'][1], 'yith_wcdp') !== false) {
                        unset($wp_filter['woocommerce_single_product_summary']->callbacks[$priority][$callback_id]);
                    }
                }
            }
        }

        // Cetak blok Mitra versi Indonesia
        echo ovm_render_payment_options_html();
    }, 9999);
});


/* =====================================================
   🔒 Override Total YITH Deposit (v3.0)
   Mencegah semua tampilan default YITH muncul
   dan memaksa pakai versi Mitra Indonesia.
===================================================== */
add_action('init', function() {
    // Matikan semua hook tampilan YITH Deposit
    remove_all_actions('yith_wcdp_single_add_to_cart_fields');
    remove_all_actions('yith_wcdp_after_single_add_to_cart_button');
    remove_all_actions('yith_wcdp_before_single_add_to_cart_button');
    remove_all_actions('woocommerce_before_add_to_cart_button');
    remove_all_actions('woocommerce_after_add_to_cart_form');
});

add_action('wp', function() {
    if (!is_product()) return;

    // Hapus semua fungsi YITH yang mungkin masih terpasang
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

    // Paksa tampilkan versi Mitra
    add_action('woocommerce_after_add_to_cart_form', function() {
        echo ovm_render_payment_options_html();
    }, 9999);
});

