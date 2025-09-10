<?php

/**
 * =========================
 * Countdown Pembayaran Mitra (Deadline Per Produk)
 * =========================
 */

// 1️⃣ Tambah field di tab Umum produk
// Tambah field deadline (posisi sementara di tab umum)
add_action('woocommerce_product_options_general_product_data', function () {
    global $post;

    $saved = get_post_meta($post->ID, '_mitra_payment_deadline', true);

    $value = '';
    if ($saved) {
        $dt = new DateTime('@' . $saved);
        $dt->setTimezone(wp_timezone());
        $value = $dt->format('Y-m-d\TH:i');
    }

    echo '<div class="options_group" id="deadline-wrapper">';
    woocommerce_wp_text_input([
        'id'          => '_mitra_payment_deadline',
        'label'       => 'Deadline Pembayaran Mitra',
        'value'       => $value,
        'desc_tip'    => true,
        'description' => 'Set tanggal & jam batas pembayaran (format: YYYY-MM-DD HH:MM)',
        'type'        => 'datetime-local',
    ]);
    echo '</div>';
});

// JS untuk mindahin field ke bawah select role
add_action('admin_footer', function () {
    global $pagenow;
    if ($pagenow !== 'post.php' && $pagenow !== 'post-new.php') return;
?>
    <script>
        jQuery(function($) {
            // pindahkan #deadline-wrapper ke bawah field _visible_for_role
            var $roleField = $('#_visible_for_role').closest('p, .form-field');
            if ($roleField.length) {
                $('#deadline-wrapper').insertAfter($roleField);
            }
        });
    </script>
<?php
});


// 2️⃣ Simpan field deadline
add_action('woocommerce_admin_process_product_object', function ($product) {
    if (isset($_POST['_mitra_payment_deadline'])) {
        $val = sanitize_text_field($_POST['_mitra_payment_deadline']);
        if ($val) {
            try {
                $dt = new DateTime($val, wp_timezone());
                $product->update_meta_data('_mitra_payment_deadline', $dt->getTimestamp());
            } catch (Exception $e) {
                $product->update_meta_data('_mitra_payment_deadline', strtotime($val));
            }
        } else {
            $product->delete_meta_data('_mitra_payment_deadline');
        }
    }
});

// 3️⃣ Styling countdown
add_action('wp_head', function () {
?>
    <style>
        #mitra-countdown-box {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            max-width: 100%;
        }

        #mitra-countdown-box>div:first-child {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        #mitra-countdown {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        #mitra-countdown .count-box {
            background: #8B4513;
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            min-width: 70px;
            text-align: center;
            flex: 1 1 auto;
        }

        #mitra-countdown .count-box div:first-child {
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
        }

        #mitra-countdown .count-box div:last-child {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        @media (max-width: 480px) {
            #mitra-countdown {
                gap: 6px;
            }

            #mitra-countdown .count-box {
                padding: 8px 10px;
                min-width: 55px;
            }

            #mitra-countdown .count-box div:first-child {
                font-size: 16px;
            }
        }
    </style>
<?php
});

// 4️⃣ Countdown + Hide tombol checkout
add_action('wp_footer', function () {
    $cart = WC()->cart->get_cart();
    if (empty($cart)) return;

    // ambil produk pertama di cart
    $first = reset($cart);
    $product_id = $first['product_id'];
    if (!$product_id) return;

    $end_time = get_post_meta($product_id, '_mitra_payment_deadline', true);
    if (!$end_time) return;
?>
    <script>
        jQuery(function($) {
            var end = <?php echo $end_time * 1000; ?>;

            var $btn = $(
                '.c-cart__checkout-btn, ' +
                '.wc-proceed-to-checkout .checkout-button, ' +
                '.woocommerce-mini-cart__buttons .checkout'
            );

            if ($('body').hasClass('woocommerce-cart')) {
                $btn.hide().after(`
                <div id="mitra-countdown-box">
                    <div>⚡ Waktu Pembayaran Mitra</div>
                    <div id="mitra-countdown"></div>
                </div>
            `);
            } else {
                $btn.hide();
            }

            function hideMiniCartCheckout() {
                $('.woocommerce-mini-cart__buttons .checkout').hide();
            }
            hideMiniCartCheckout();
            $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
                hideMiniCartCheckout();
            });

            function updateCountdown() {
                var now = Date.now();
                var dist = end - now;

                if (dist <= 0) {
                    $('#mitra-countdown-box').remove();
                    $btn.show().text('Lanjutkan Pembayaran').css({
                        'pointer-events': 'auto',
                        'opacity': '1',
                        'cursor': 'pointer'
                    });
                    return;
                }

                var d = Math.floor(dist / (1000 * 60 * 60 * 24));
                var h = Math.floor((dist % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var m = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60));
                var s = Math.floor((dist % (1000 * 60)) / 1000);

                $('#mitra-countdown').html(`
                <div class="count-box"><div>${d}</div><div>Days</div></div>
                <div class="count-box"><div>${h}</div><div>Hours</div></div>
                <div class="count-box"><div>${m}</div><div>Minutes</div></div>
                <div class="count-box"><div>${s}</div><div>Seconds</div></div>
            `);

                setTimeout(updateCountdown, 1000);
            }

            if ($('body').hasClass('woocommerce-cart')) {
                updateCountdown();
            }
        });
    </script>
<?php
});

// 🔹 Redirect checkout ke cart kalau masih ada deadline mitra yang berjalan
add_action('template_redirect', function () {
    if (!is_checkout()) return; // hanya cek di checkout

    $cart = WC()->cart->get_cart();
    if (empty($cart)) return;

    $now = time();

    foreach ($cart as $item) {
        $product_id = $item['product_id'];
        $deadline = get_post_meta($product_id, '_mitra_payment_deadline', true);
        if ($deadline && $deadline > $now) {
            // redirect ke cart
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }
    }
});

// Custom diskon Mitra
// ============================
// 1️⃣ Tambah field Diskon Mitra di Product Data
// ============================
add_action('woocommerce_product_options_general_product_data', function () {
    woocommerce_wp_text_input([
        'id'          => '_mitra_discount',
        'label'       => __('Diskon Mitra (%)', 'woocommerce'),
        'desc_tip'    => true,
        'description' => __('Masukkan persentase diskon khusus untuk role Mitra. Contoh: 10 = 10%', 'woocommerce'),
        'type'        => 'number',
        'custom_attributes' => [
            'step' => '0.01',
            'min'  => '0'
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

// ============================
// 3️⃣ Terapkan Harga Diskon untuk User Mitra
// ============================
function apply_mitra_price($price, $product)
{
    if (!is_user_logged_in()) return $price;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array) $user->roles)) return $price;

    // ambil diskon dari parent product kalau ini variation
    $parent_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
    $discount  = (float) get_post_meta($parent_id, '_mitra_discount', true);

    if ($discount <= 0) return $price;

    $price = (float) $price;
    $price = $price - (($discount / 100) * $price);

    return $price;
}
add_filter('woocommerce_product_get_price', 'apply_mitra_price', 10, 2);
add_filter('woocommerce_product_variation_get_price', 'apply_mitra_price', 10, 2);
add_filter('woocommerce_product_get_regular_price', 'apply_mitra_price', 10, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'apply_mitra_price', 10, 2);

// ============================
// 4️⃣ Tambah tipe diskon baru di dropdown coupon
// ============================
add_filter('woocommerce_coupon_discount_types', function ($discount_types) {
    $discount_types['mitra_discount'] = __('Diskon Mitra', 'woocommerce');
    return $discount_types;
});

// ============================
// 5️⃣ Override perhitungan coupon untuk tipe mitra_discount
// ============================
add_action('woocommerce_coupon_get_discount_amount', function ($discount, $discounting_amount, $cart_item, $single, $coupon) {
    if ($coupon->get_discount_type() === 'mitra_discount') {
        // Hanya untuk user role Mitra
        if (!is_user_logged_in()) return 0;
        $user = wp_get_current_user();
        if (!in_array('mitra', (array) $user->roles)) return 0;

        // Ambil diskon dari produk
        $product = $cart_item['data'];
        $parent_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
        $mitra_discount = (float) get_post_meta($parent_id, '_mitra_discount', true);
        if ($mitra_discount <= 0) return 0;

        // Hitung diskon berdasarkan harga item
        $discount = ($mitra_discount / 100) * $discounting_amount;
        return $discount;
    }
    return $discount;
}, 10, 5);
