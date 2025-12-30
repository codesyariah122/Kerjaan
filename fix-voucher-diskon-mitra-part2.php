<?php

/**
 * Plugin Name: Override Voucher Mitra (Final)
 * Description: Menambahkan tipe kupon "Diskon Mitra" dan menghitung diskon sesuai skema: voucher memotong total harga terlebih dahulu -> kemudian dihitung Harga Mitra -> lalu DP & Pelunasan. Mendukung pemilihan skema Full vs Deposit pada single product.
 * Version: 2.3
 * Author: Puji Ermanto | UCOK AKA (modified)
 * Text Domain: override-voucher-mitra
 */

/* =====================================================
   Helper functions
===================================================== */

/**
 * Ambil persentase voucher (kupon) yang dipakai untuk potongan (contoh: 25 = 25%).
 * Default 25 jika tidak ada kupon mitra aktif.
 */
function ovm_get_applied_voucher_percent()
{
    $default = 25;
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
 * Ambil persentase harga mitra (contoh: 50 = 50%).
 * Mencari dari kupon mitra aktif terlebih dahulu (field _mitra_discount pada kupon).
 * Jika tidak ada, fallback ke product meta (jika $product_id diberikan).
 * Jika tidak ada, kembalikan default 50.
 */
function ovm_get_mitra_percent($product_id = null)
{
    $default = 50;

    // 1) Coba ambil dari kupon mitra aktif
    if (WC()->cart) {
        $applied = WC()->cart->get_applied_coupons();
        foreach ($applied as $code) {
            $coupon = new WC_Coupon($code);
            if ($coupon->get_discount_type() === 'mitra_discount') {
                $meta = get_post_meta($coupon->get_id(), '_mitra_discount', true);
                $val  = (float) $meta;
                if ($val > 0) return $val;
            }
        }
    }

    // 2) Jika diberikan product_id, coba ambil dari product meta
    if ($product_id) {
        $meta = get_post_meta($product_id, '_mitra_discount', true);
        $val  = (float) $meta;
        if ($val > 0) return $val;
    }

    // 3) fallback
    return $default;
}

/* =====================================================
   1️⃣ Tambah Field Diskon Mitra di Produk & Kupon
===================================================== */
add_action('woocommerce_product_options_general_product_data', function () {
    woocommerce_wp_text_input([
        'id' => '_mitra_discount',
        'label' => __('Diskon Mitra (%)', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('Masukkan persentase diskon khusus untuk role Mitra. Contoh: 10 = 10%', 'woocommerce'),
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0']
    ]);
});

add_action('woocommerce_admin_process_product_object', function ($product) {
    if (isset($_POST['_mitra_discount'])) {
        $product->update_meta_data('_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
});

add_action('woocommerce_coupon_options', function ($coupon_id) {
    $value = get_post_meta($coupon_id, '_mitra_discount', true);
    echo '<div class="options_group _mitra_discount_field" style="display:none;">';
    woocommerce_wp_text_input([
        'id' => '_mitra_discount',
        'label' => __('Diskon Mitra (%)', 'woocommerce'),
        'type' => 'number',
        'desc_tip' => true,
        'description' => __('Masukkan persentase diskon mitra global (contoh: 10 = 10%)', 'woocommerce'),
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
        'value' => $value ?: ''
    ]);
    echo '</div>';
});

add_action('woocommerce_coupon_options_save', function ($post_id) {
    if (isset($_POST['_mitra_discount'])) {
        update_post_meta($post_id, '_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
});

add_filter('woocommerce_coupon_discount_types', function ($types) {
    $types['mitra_discount'] = __('Diskon Mitra', 'woocommerce');
    return $types;
});

add_action('admin_footer', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'shop_coupon') :
?>
        <script>
            jQuery(function($) {
                function toggleMitraField() {
                    var val = $('#discount_type').val();
                    $('._mitra_discount_field').toggle(val === 'mitra_discount');
                }
                toggleMitraField();
                $(document).on('change', '#discount_type', toggleMitraField);
            });
        </script>
    <?php
    endif;
});

/* =====================================================
   2️⃣ Simpan pilihan payment_type saat add-to-cart
      (agar saat di cart kita tahu user pilih full atau deposit)
===================================================== */

/**
 * Tangkap payment_type dari POST saat user klik "Tambah ke keranjang"
 * dan masukkan ke cart item data.
 */
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id, $variation_id) {
    // ambil posted payment_type jika ada
    $payment_type = 'full';
    if (isset($_REQUEST['payment_type'])) {
        $payment_type = sanitize_text_field(wp_unslash($_REQUEST['payment_type']));
    }

    // simpan ke cart item data
    $cart_item_data['mitra_payment_type'] = $payment_type;

    // buat unique key agar item yang sama tapi payment_type beda dianggap berbeda
    $cart_item_data['unique_key'] = md5(microtime() . rand());

    return $cart_item_data;
}, 20, 3);

/* =====================================================
   3️⃣ Hitung Diskon Voucher Mitra Sesuai Rumus Dokumen
      (memperhitungkan apakah ada item deposit atau semua full)
===================================================== */
add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    $original_total = 0;
    $has_deposit_item = false;
    foreach ($cart->get_cart() as $cart_item_key => $item) {
        $product = $item['data'];
        $price = (float)$product->get_regular_price();
        $qty   = (int)$item['quantity'];
        $original_total += $price * $qty;

        // cek cart item payment_type
        $ptype = isset($item['mitra_payment_type']) ? $item['mitra_payment_type'] : 'full';
        if ($ptype === 'deposit') {
            $has_deposit_item = true;
        }
    }

    // Ambil persentase voucher kupon mitra (potongan)
    $voucher_percent = ovm_get_applied_voucher_percent();

    // Ambil persentase harga mitra (mis. 50) dari kupon atau fallback ke default
    $mitra_percent = ovm_get_mitra_percent();

    // Hitung pengurangan voucher dari total
    $discount_amount = $original_total * ($voucher_percent / 100);
    $discounted_total = $original_total - $discount_amount;

    // Harga Mitra = mitra_percent% dari total setelah voucher
    $harga_mitra = $discounted_total * ($mitra_percent / 100);

    if ($has_deposit_item) {
        // jika ada item deposit -> kita tampilkan DP & pelunasan (skema deposit)
        $dp_mitra = $harga_mitra * 0.5;
        $pelunasan = $dp_mitra;

        // Simpan ke session (dipakai di cart display & checkout)
        WC()->session->set('mitra_total_discount', $discount_amount);
        WC()->session->set('mitra_dp_amount', $dp_mitra);
        WC()->session->set('mitra_pelunasan_amount', $pelunasan);
        WC()->session->set('mitra_harga_percent', $mitra_percent);
        WC()->session->set('mitra_harga_mitra', $harga_mitra);
        WC()->session->set('mitra_has_deposit', true);
    } else {
        // semua full payment -> tidak perlu DP/pelunasan; user bayar penuh
        WC()->session->set('mitra_total_discount', $discount_amount);
        WC()->session->set('mitra_dp_amount', 0);
        WC()->session->set('mitra_pelunasan_amount', 0);
        WC()->session->set('mitra_harga_percent', $mitra_percent);
        WC()->session->set('mitra_harga_mitra', $harga_mitra);
        WC()->session->set('mitra_has_deposit', false);
    }

    // Tambahkan fee label untuk menunjukkan diskon secara transparan di cart totals
    if ($discount_amount > 0) {
        // hapus fee sebelumnya dengan label sama jika ada (untuk AJAX recalc)
        // (woocommerce tidak menyediakan remove_fee, jadi biarkan unik di hook priority tinggi)
        $cart->add_fee(sprintf('Diskon Mitra (%s%%)', $voucher_percent), -$discount_amount);
    }
}, 999);

/* =====================================================
   4️⃣ Tampilkan Detail Harga Mitra di Cart (sesuai skema)
===================================================== */
add_action('woocommerce_cart_totals_before_order_total', function () {
    $discount = WC()->session->get('mitra_total_discount');
    $dp = WC()->session->get('mitra_dp_amount');
    $pelunasan = WC()->session->get('mitra_pelunasan_amount');
    $mitra_percent = WC()->session->get('mitra_harga_percent') ?: 50;
    $harga_mitra = WC()->session->get('mitra_harga_mitra') ?: 0;
    $has_deposit = WC()->session->get('mitra_has_deposit');

    if (!$discount && $discount !== 0) return; // jika belum ada perhitungan, absen

    // Harga Mitra ditampilkan (harga_mitra adalah total harga mitra setelah voucher)
    echo '<tr><th style="color:#3A0BF4;">Harga Mitra (' . esc_html($mitra_percent) . '%)</th><td>' . wc_price($harga_mitra) . '</td></tr>';

    if ($has_deposit) {
        // tampilkan DP & Pelunasan
        echo '<tr><th style="color:#3A0BF4;">DP (50%)</th><td>' . wc_price($dp) . '</td></tr>';
        echo '<tr><th style="color:#3A0BF4;">Pelunasan (50%)</th><td>' . wc_price($pelunasan) . '</td></tr>';
    } else {
        // full payment -> tunjukkan info bahwa total bayar adalah harga_mitra
        echo '<tr><th style="color:#3A0BF4;">Total Bayar (Full Payment)</th><td>' . wc_price($harga_mitra) . '</td></tr>';
    }
});

add_action('woocommerce_cart_totals_after_order_total', function () {
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    $dp = WC()->session->get('mitra_dp_amount');
    $has_deposit = WC()->session->get('mitra_has_deposit');

    if ($has_deposit && $dp > 0) {
        echo '<tr style="border-top:2px solid #000;"><th>Total Bayar (Checkout DP)</th><td><strong>' . wc_price($dp) . '</strong></td></tr>';
    }
});

/* =====================================================
   5️⃣ Sinkronkan DP & Harga di Cart + Checkout
      (menggunakan filter YITH deposit plugin)
      — hanya kembalikan nilai DP jika item untuk product tersebut
        di cart berstatus payment_type=deposit
===================================================== */
add_filter('yith_wcdp_get_deposit_amount', function ($deposit_amount, $product_id) {
    // jika tidak login atau bukan mitra, default behavior
    if (!is_user_logged_in()) return $deposit_amount;
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return $deposit_amount;

    // cek apakah ada item di cart dengan product_id yang memiliki mitra_payment_type=deposit
    if (! WC()->cart) return $deposit_amount;

    foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
        $prod = $item['data'];
        if ((int)$prod->get_id() === (int)$product_id) {
            $ptype = isset($item['mitra_payment_type']) ? $item['mitra_payment_type'] : 'full';
            if ($ptype === 'deposit') {
                // hitung deposit untuk product tersebut menggunakan rumus voucher -> harga mitra -> 50%
                $voucher_percent = ovm_get_applied_voucher_percent();
                $mitra_percent = ovm_get_mitra_percent($product_id);

                $original = (float)$prod->get_regular_price();
                if ($original <= 0) return $deposit_amount;

                // potong voucher dari harga asli (per item)
                $after_voucher = $original - ($original * ($voucher_percent / 100));
                // harga mitra per item
                $harga_mitra = $after_voucher * ($mitra_percent / 100);
                // dp 50% per item
                $dp_mitra = $harga_mitra * 0.5;

                return $dp_mitra;
            }
        }
    }

    // otherwise keep default
    return $deposit_amount;
}, 999, 2);

/* =====================================================
   6️⃣ Tampilkan Harga Mitra di Halaman Produk
      (sama seperti sebelumnya)
===================================================== */
add_filter('woocommerce_get_price_html', function ($price_html, $product) {
    if (!is_user_logged_in()) return $price_html;
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return $price_html;

    $voucher_percent = ovm_get_applied_voucher_percent();
    $mitra_percent = ovm_get_mitra_percent($product->get_id());

    $original = (float)$product->get_regular_price();
    if ($original <= 0) return $price_html;

    // Rumus:
    $after_voucher = $original - ($original * ($voucher_percent / 100));
    $harga_mitra = $after_voucher * ($mitra_percent / 100);

    $price_html = sprintf(
        '<del>%s</del> <ins>%s</ins><br><small style="color:#3A0BF4;">Harga Mitra setelah diskon & potongan %s%%</small>',
        wc_price($original),
        wc_price($harga_mitra),
        esc_html($mitra_percent)
    );

    return $price_html;
}, 30, 2);

/* =====================================================
   7️⃣ Override DP di Single Product agar sama seperti Cart
      (tambahkan attribute data-deposit-amount, dan juga
      pastikan radio input memiliki name="payment_type" agar
      dapat dikirim saat add-to-cart)
===================================================== */
add_filter('yith_wcdp_single_add_to_cart_fields_html', function ($html, $product) {
    if (!is_user_logged_in()) return $html;
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return $html;

    $voucher_percent = ovm_get_applied_voucher_percent();
    $mitra_percent = ovm_get_mitra_percent($product->get_id());

    $original = (float)$product->get_regular_price();
    if ($original <= 0) return $html;

    // Rumus sesuai dokumen
    $after_voucher = $original - ($original * ($voucher_percent / 100)); // harga setelah voucher
    $harga_mitra   = $after_voucher * ($mitra_percent / 100); // harga mitra (mitra_percent%)
    $dp_mitra      = $harga_mitra * 0.5;   // DP 50% dari harga mitra
    $pelunasan     = $dp_mitra;            // pelunasan = DP

    // Override HTML tampilan
    ob_start(); ?>
    <div class="yith-wcdp-single-add-to-cart-fields" data-deposit-type="rate" data-deposit-amount="<?php echo esc_attr($dp_mitra); ?>">
        <label class="full">
            <input type="radio" name="payment_type" value="full" checked="checked">
            <span class="label">
                Pay full amount
                <span class="price-label full-price">
                    <del><?php echo wc_price($original); ?></del>
                    <ins><?php echo wc_price($harga_mitra); ?></ins>
                </span><br>
                <small class="yith-wcdp-note">Harga Mitra setelah diskon & potongan <?php echo esc_html($mitra_percent); ?>%</small>
            </span>
        </label>
        <label class="deposit">
            <input type="radio" name="payment_type" value="deposit">
            <span class="label">
                Pay deposit
                <span class="price-label deposit-price"><?php echo wc_price($dp_mitra); ?></span>
                <small class="yith-wcdp-expiration-notice">
                    Balance payment will be required on
                    <span class="expiration-date"><?php echo date_i18n('F j, Y', strtotime('+10 days')); ?></span>
                    (<?php echo wc_price($pelunasan); ?>)
                </small>
            </span>
        </label>
    </div>
<?php
    return ob_get_clean();
}, 999, 2);

/* =====================================================
   8️⃣ (Opsional) Jika theme melakukan AJAX add-to-cart,
      pastikan payment_type ikut dikirim - kita tidak
      bisa memaksa theme, tapi di sini kita menambahkan
      sedikit JS untuk memastikan form add-to-cart mengandung
      payment_type saat submit (non-AJAX fallback).
      NOTE: jika theme/child theme sudah menanganinya, ini aman.
===================================================== */
add_action('wp_footer', function () {
    if (! is_product()) return;
    if (! is_user_logged_in()) return;
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;
?>
    <script>
        (function($) {
            $(function() {
                // Pastikan sebelum add-to-cart, pilihan payment_type disertakan di form
                var $form = $('form.cart');
                if (!$form.length) return;
                $form.on('submit', function() {
                    // hapus dulu jika ada input hidden payment_type
                    $form.find('input[name="payment_type"][type="hidden"]').remove();
                    // ambil radio
                    var val = $form.find('input[name="payment_type"]:checked').val() || 'full';
                    // tambahkan hidden input agar dikirim saat add-to-cart
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'payment_type',
                        value: val
                    }).appendTo($form);
                    return true;
                });

                // Jika theme pakai AJAX add-to-cart via button bukan form submit,
                // kita juga mengecek click tombol add-to-cart
                $form.find('button[type="submit"], .single_add_to_cart_button').on('click', function() {
                    var val = $form.find('input[name="payment_type"]:checked').val() || 'full';
                    $form.find('input[name="payment_type"][type="hidden"]').remove();
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'payment_type',
                        value: val
                    }).appendTo($form);
                });
            });
        })(jQuery);
    </script>
<?php
}, 99);
