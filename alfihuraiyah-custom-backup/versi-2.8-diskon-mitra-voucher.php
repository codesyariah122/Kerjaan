/**
 * Plugin Name: Override Voucher Mitra (v2.8 – DOM Injector Ready)
 * Description: Versi lengkap untuk sistem Mitra. Menghitung harga mitra setelah voucher, menampilkan pilihan “Bayar Lunas / Bayar DP” dalam Bahasa Indonesia, dan tetap muncul walau template tema override WooCommerce.
 * Version: 2.8
 * Author: Puji Ermanto | UCOK AKA (Hybrid Full ID)
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

/* =====================================================
   🔧 Helper Functions
===================================================== */

// Ambil persentase diskon voucher mitra aktif di cart
function ovm_get_applied_voucher_percent() {
    if (!WC()->cart) return 0;
    $applied = WC()->cart->get_applied_coupons();
    foreach ($applied as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $meta = get_post_meta($coupon->get_id(), '_mitra_discount', true);
            $val = (float)$meta;
            if ($val > 0) return $val;
        }
    }
    return 0;
}

// Ambil persentase harga mitra dari produk (default 50%)
function ovm_get_mitra_price_percent($product_id = null) {
    $default = 50;
    if (!$product_id) return $default;
    $meta = get_post_meta($product_id, '_mitra_price_percent', true);
    $val = (float)$meta;
    return $val > 0 ? $val : $default;
}

/* =====================================================
   🏷️ Tambah Field di Produk & Kupon
===================================================== */
add_action('woocommerce_product_options_general_product_data', function() {
    woocommerce_wp_text_input([
        'id' => '_mitra_price_percent',
        'label' => __('Persentase Harga Mitra (%)', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('Contoh: 50 berarti harga mitra = 50% dari harga setelah voucher.', 'woocommerce'),
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
    ]);
});
add_action('woocommerce_admin_process_product_object', function($product) {
    if (isset($_POST['_mitra_price_percent'])) {
        $product->update_meta_data('_mitra_price_percent', sanitize_text_field($_POST['_mitra_price_percent']));
    }
});
add_action('woocommerce_coupon_options', function($coupon_id) {
    $value = get_post_meta($coupon_id, '_mitra_discount', true);
    echo '<div class="options_group">';
    woocommerce_wp_text_input([
        'id' => '_mitra_discount',
        'label' => __('Diskon Voucher Mitra (%)', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('Masukkan persentase diskon voucher mitra. Contoh: 25 = 25%', 'woocommerce'),
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
        'value' => $value ?: '',
    ]);
    echo '</div>';
});
add_action('woocommerce_coupon_options_save', function($post_id) {
    if (isset($_POST['_mitra_discount'])) {
        update_post_meta($post_id, '_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
});
add_filter('woocommerce_coupon_discount_types', function($types) {
    $types['mitra_discount'] = __('Diskon Mitra', 'woocommerce');
    return $types;
});

/* =====================================================
   💰 Simpan Jenis Pembayaran (Full / DP)
===================================================== */
add_filter('woocommerce_add_cart_item_data', function($cart_item_data, $product_id) {
    $ptype = isset($_REQUEST['payment_type']) ? sanitize_text_field($_REQUEST['payment_type']) : 'full';
    $cart_item_data['mitra_payment_type'] = $ptype;
    $cart_item_data['unique_key'] = md5(microtime() . rand());
    return $cart_item_data;
}, 20, 2);

/* =====================================================
   🧮 Hitung Diskon Mitra di Cart
===================================================== */
add_action('woocommerce_cart_calculate_fees', function($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    $original_total = 0;
    $has_deposit = false;

    foreach ($cart->get_cart() as $item) {
        $product = $item['data'];
        $price   = (float)$product->get_regular_price();
        $qty     = (int)$item['quantity'];
        $original_total += $price * $qty;
        if (!empty($item['mitra_payment_type']) && $item['mitra_payment_type'] === 'deposit') $has_deposit = true;
    }

    $voucher_percent = ovm_get_applied_voucher_percent();
    $first_item = reset($cart->get_cart());
    $mitra_percent = ovm_get_mitra_price_percent($first_item['data']->get_id());

    $after_voucher = $original_total - ($original_total * ($voucher_percent / 100));
    $harga_mitra = $after_voucher * ($mitra_percent / 100);
    $dp_mitra = $harga_mitra * 0.5;

    WC()->session->set('mitra_harga_mitra', $harga_mitra);
    WC()->session->set('mitra_dp_amount', $has_deposit ? $dp_mitra : 0);
    WC()->session->set('mitra_pelunasan_amount', $has_deposit ? $dp_mitra : 0);
    WC()->session->set('mitra_voucher_percent', $voucher_percent);
    WC()->session->set('mitra_price_percent', $mitra_percent);
    WC()->session->set('mitra_has_deposit', $has_deposit);

    if ($voucher_percent > 0) {
        $cart->add_fee(sprintf('Diskon Voucher Mitra (%s%%)', $voucher_percent), -$original_total * ($voucher_percent / 100));
    }
}, 999);

/* =====================================================
   🧾 Tampilkan Rincian di Cart
===================================================== */
add_action('woocommerce_cart_totals_before_order_total', function() {
    $harga_mitra = WC()->session->get('mitra_harga_mitra');
    $dp = WC()->session->get('mitra_dp_amount');
    $pelunasan = WC()->session->get('mitra_pelunasan_amount');
    $voucher_percent = WC()->session->get('mitra_voucher_percent');
    $mitra_percent = WC()->session->get('mitra_price_percent');
    $has_deposit = WC()->session->get('mitra_has_deposit');

    if (!$harga_mitra) return;

    echo '<tr><th style="color:#3A0BF4;">Harga Mitra (setelah diskon ' . esc_html($voucher_percent) . '% & potongan ' . esc_html($mitra_percent) . '%)</th><td>' . wc_price($harga_mitra) . '</td></tr>';
    if ($has_deposit) {
        echo '<tr><th style="color:#3A0BF4;">DP (50%)</th><td>' . wc_price($dp) . '</td></tr>';
        echo '<tr><th style="color:#3A0BF4;">Pelunasan (50%)</th><td>' . wc_price($pelunasan) . '</td></tr>';
    } else {
        echo '<tr><th style="color:#3A0BF4;">Total Pembayaran (Lunas)</th><td><strong>' . wc_price($harga_mitra) . '</strong></td></tr>';
    }
});

/* =====================================================
   💬 Smart Display di Halaman Produk
===================================================== */
add_filter('woocommerce_get_price_html', function($price_html, $product) {
    if (!is_user_logged_in()) return $price_html;
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return $price_html;

    $voucher_percent = ovm_get_applied_voucher_percent();
    $mitra_percent = ovm_get_mitra_price_percent($product->get_id());
    $original = (float)$product->get_regular_price();
    if ($original <= 0) return $price_html;

    if ($voucher_percent > 0) {
        $after_voucher = $original - ($original * ($voucher_percent / 100));
        $harga_mitra = $after_voucher * ($mitra_percent / 100);
        return sprintf(
            '<del>%s</del> <ins>%s</ins><br><small style="color:#3A0BF4;">Harga Mitra (setelah diskon %s%% & potongan %s%%)</small>',
            wc_price($original),
            wc_price($harga_mitra),
            esc_html($voucher_percent),
            esc_html($mitra_percent)
        );
    } else {
        $harga_mitra = $original * ($mitra_percent / 100);
        return sprintf(
            '<del>%s</del> <ins>%s</ins><br><small style="color:#999;">Simulasi harga mitra (%s%%). Aktifkan voucher mitra untuk melihat harga final.</small>',
            wc_price($original),
            wc_price($harga_mitra),
            esc_html($mitra_percent)
        );
    }
}, 30, 2);

/* =====================================================
   🧩 DOM Injector — Pilihan Pembayaran di Halaman Produk
===================================================== */
add_filter('the_content', function($content) {
    if (!is_product()) return $content;
    if (!is_user_logged_in()) return $content;
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return $content;

    global $product;
    if (!$product) return $content;

    $voucher_percent = ovm_get_applied_voucher_percent();
    $mitra_percent = ovm_get_mitra_price_percent($product->get_id());
    $original = (float)$product->get_regular_price();
    if ($original <= 0) return $content;

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
            <strong>Bayar Lunas:</strong> <span><?php echo wc_price($harga_mitra); ?></span><br>
            <?php if ($voucher_percent > 0): ?>
                <small style="color:#3A0BF4;">Harga Mitra setelah diskon
                    <?php echo esc_html($voucher_percent); ?>% & potongan
                    <?php echo esc_html($mitra_percent); ?>%</small>
            <?php else: ?>
                <small style="color:#888;">Simulasi harga (belum pakai voucher)</small>
            <?php endif; ?>
        </label>

        <label style="display:block;margin-bottom:8px;">
            <input type="radio" name="payment_type" value="deposit">
            <strong>Bayar DP (50%):</strong> <span><?php echo wc_price($dp); ?></span><br>
            <small>Sisa pelunasan (<?php echo wc_price($pelunasan); ?>) sebelum
                <?php echo date_i18n('j F Y', strtotime('+10 days')); ?></small>
        </label>
    </div>
<?php
    $injected_html = ob_get_clean();

    // Sisipkan sebelum </form> pertama (biasanya form add-to-cart)
    $content = preg_replace('/<\/form>/', $injected_html . '</form>', $content, 1);
    return $content;
}, 9999);
