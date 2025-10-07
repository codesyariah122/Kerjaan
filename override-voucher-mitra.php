<?php
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
// ============================
// Terapkan Harga Diskon untuk User Mitra Hanya Jika Ada Coupon Mitra
// ============================
function apply_mitra_price_only_with_coupon($price, $product)
{
    if (!is_user_logged_in()) return $price;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array) $user->roles)) return $price;

    // cek coupon aktif
    if (!WC()->cart) return $price;
    $applied_coupons = WC()->cart->get_applied_coupons();
    if (empty($applied_coupons)) return $price;

    $mitra_discount = 0;
    $coupon_discount = 0;

    // ambil diskon dari coupon pertama yang bertipe 'mitra_discount'
    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            // ambil diskon dari produk
            $parent_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
            $mitra_discount = (float) get_post_meta($parent_id, '_mitra_discount', true);

            // jika diskon produk kosong / 0, ambil dari coupon
            if ($mitra_discount <= 0) {
                $coupon_discount = (float) get_post_meta($coupon->get_id(), '_mitra_discount', true);
                $mitra_discount = $coupon_discount;
            }
            break; // pakai coupon pertama yang ada
        }
    }

    if ($mitra_discount <= 0) return $price;

    $price = (float) $price;
    $price = $price - (($mitra_discount / 100) * $price);

    return $price;
}
add_filter('woocommerce_product_get_price', 'apply_mitra_price_only_with_coupon', 10, 2);
add_filter('woocommerce_product_variation_get_price', 'apply_mitra_price_only_with_coupon', 10, 2);
add_filter('woocommerce_product_get_regular_price', 'apply_mitra_price_only_with_coupon', 10, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'apply_mitra_price_only_with_coupon', 10, 2);

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
    // Logging awal
    wc_get_logger()->info('=== Diskon Mitra Hook Dijalankan ===', ['source' => 'mitra-discount']);
    wc_get_logger()->info('Coupon Type: ' . $coupon->get_discount_type(), ['source' => 'mitra-discount']);
    wc_get_logger()->info('Discounting Amount: ' . $discounting_amount, ['source' => 'mitra-discount']);

    if ($coupon->get_discount_type() === 'mitra_discount') {
        if (!is_user_logged_in()) {
            wc_get_logger()->info('❌ User belum login', ['source' => 'mitra-discount']);
            return 0;
        }

        $user = wp_get_current_user();
        if (!in_array('mitra', (array) $user->roles)) {
            wc_get_logger()->info('❌ Bukan user role mitra', ['source' => 'mitra-discount']);
            return 0;
        }

        $product = $cart_item['data'];
        $parent_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();

        $coupon_id = is_callable([$coupon, 'get_id']) ? $coupon->get_id() : $coupon->get_data()['id'];
        $mitra_discount = (float) get_post_meta($coupon_id, '_mitra_discount', true);

        // Jika kosong, coba ambil dari produk
        if ($mitra_discount <= 0) {
            $mitra_discount = (float) get_post_meta($parent_id, '_mitra_discount', true);
            wc_get_logger()->info('💡 Ambil diskon dari produk: ' . $mitra_discount, ['source' => 'mitra-discount']);
        } else {
            wc_get_logger()->info('💡 Ambil diskon dari coupon: ' . $mitra_discount, ['source' => 'mitra-discount']);
        }

        if ($mitra_discount <= 0) {
            wc_get_logger()->info('⚠️ Diskon masih 0 — tidak diterapkan', ['source' => 'mitra-discount']);
            return 0;
        }

        $discount = ($mitra_discount / 100) * $discounting_amount;

        wc_get_logger()->info('✅ Diskon diterapkan: ' . $discount, ['source' => 'mitra-discount']);
        return $discount;
    }

    return $discount;
}, 10, 5);



// ============================
// Tampilkan info Diskon Mitra di bawah kupon tanpa price
// ============================
add_filter('woocommerce_cart_totals_coupon_html', function ($coupon_html, $coupon) {
    if ($coupon->get_discount_type() === 'mitra_discount') {
        $html  = $coupon_html;
        $html .= '<tr class="c-cart__totals-discount cart-discount coupon-' . esc_attr($coupon->get_code()) . '">';
        $html .= '<th class="c-cart__sub-sub-header" style="font-weight: normal; font-size: 14px; color: #3A0BF4; text-transform: capitalize; padding: 12px;">'
            . __('Diskon Khusus Mitra', 'woocommerce') . '</th>';
        $html .= '<td class="c-cart__totals-price" data-title="' . esc_attr__('Diskon Khusus Mitra', 'woocommerce') . '" style="font-weight: normal; font-size: 14px; color: #3A0BF4;">&nbsp;</td>';
        $html .= '</tr>';
        return $html;
    }
    return $coupon_html;
}, 10, 2);

// =========================================================================================
/*
** Diskon mitra untuk case DP & Pelunasan
*/

// ============================
// Tambahkan field _mitra_discount ke halaman kupon
// ============================
add_action('woocommerce_coupon_options', function ($coupon_id) {
    $discount_type = get_post_meta($coupon_id, 'discount_type', true);
    $show = ($discount_type === 'mitra_discount');

    echo '<div id="field_mitra_discount" style="' . ($show ? '' : 'display:none;') . '">';
    woocommerce_wp_text_input([
        'id' => '_mitra_discount',
        'label' => __('Diskon Mitra (%)', 'woocommerce'),
        'type' => 'number',
        'desc_tip' => true,
        'description' => __('Masukkan persentase diskon mitra global (contoh: 10 = 10%)', 'woocommerce'),
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
    ]);
    echo '</div>';
});

// ============================
// Simpan nilai _mitra_discount
// ============================
add_action('woocommerce_coupon_options_save', function ($post_id) {
    if (isset($_POST['_mitra_discount'])) {
        update_post_meta($post_id, '_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
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
                        $('#field_mitra_discount').show();
                    } else {
                        $('#field_mitra_discount').hide();
                    }
                }
                toggleMitraField();
                $(document).on('change', '#discount_type', toggleMitraField);
            });
        </script>
<?php
    endif;
});

// Simpan nilai field
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


// ============================
// 6️⃣ Recalculate DP & Pelunasan berdasarkan _mitra_discount
// ============================
add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!is_user_logged_in()) return;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array) $user->roles)) return;

    $logger = wc_get_logger();
    $context = ['source' => 'mitra_discount'];

    $logger->info('=== MULAI HITUNG DISKON MITRA ===', $context);

    // 🔍 Cek apakah ada kupon bertipe mitra aktif
    $applied_coupons = $cart->get_applied_coupons();
    $mitra_coupon = null;
    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $mitra_coupon = $coupon;
            break;
        }
    }

    if (!$mitra_coupon) {
        $logger->warning('❌ Tidak ada kupon mitra aktif', $context);
        return;
    }

    // ❌ Kalau tidak ada kupon mitra, hentikan
    if (!$mitra_coupon) return;

    // ===============================
    // 🔢 Hitung Diskon Mitra Total
    // ===============================
    $total_discount = 0;
    $mitra_discount_value = 0;
    $discounted_total = 0;

    // Ambil ID kupon dengan cara aman
    $coupon_id = is_callable([$mitra_coupon, 'get_id']) ? $mitra_coupon->get_id() : $mitra_coupon->get_data()['id'];
    $mitra_discount_global = (float) get_post_meta($coupon_id, '_mitra_discount', true);
    $logger->info('Kupon ditemukan: ID=' . $coupon_id . ', Diskon Global=' . $mitra_discount_global, $context);


    foreach ($cart->get_cart() as $item) {
        $product = $item['data'];
        $qty = $item['quantity'];
        $price = (float) $product->get_price();

        // ambil diskon produk
        $parent_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
        $coupon_id = is_callable([$mitra_coupon, 'get_id']) ? $mitra_coupon->get_id() : $mitra_coupon->get_data()['id'];
        $mitra_discount = (float) get_post_meta($coupon_id, '_mitra_discount', true);

        // jika kosong, ambil dari kupon
        if ($mitra_discount <= 0) {
            $mitra_discount = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
        }

        // jika tetap kosong, skip
        if ($mitra_discount <= 0) continue;

        // hitung potongan
        $line_discount = ($mitra_discount / 100) * ($price * $qty);
        $total_discount += $line_discount;

        // total harga setelah diskon
        $discounted_total += ($price * $qty) - $line_discount;

        // simpan terakhir dipakai
        $mitra_discount_value = $mitra_discount;
    }

    // Jika tidak ada produk yang eligible
    if ($total_discount <= 0) return;

    // ===============================
    // 💵 Hitung DP & Pelunasan
    // ===============================
    $dp_percent = $mitra_discount_value ?: 50; // fallback 50% kalau gak ada
    $pelunasan_percent = 100 - $dp_percent;

    $dp_amount = ($dp_percent / 100) * $discounted_total;
    $pelunasan_amount = ($pelunasan_percent / 100) * $discounted_total;

    // ===============================
    // 💾 Simpan ke Session WooCommerce
    // ===============================
    WC()->session->set('mitra_total_discount', $total_discount);
    WC()->session->set('mitra_dp_amount', $dp_amount);
    WC()->session->set('mitra_pelunasan_amount', $pelunasan_amount);

    // ===============================
    // 🧾 Tambahkan Info di Cart Total
    // ===============================
    $cart->add_fee(__('Diskon Mitra (' . $mitra_discount_value . '%)', 'woocommerce'), -$total_discount);
    $cart->add_fee(__('DP (' . $dp_percent . '%)', 'woocommerce'), $dp_amount, false);
    $cart->add_fee(__('Pelunasan (' . $pelunasan_percent . '%)', 'woocommerce'), $pelunasan_amount, false);
}, 20);

// ============================
// 🚫 Sembunyikan baris kupon WooCommerce default untuk kupon tipe mitra_discount
// ============================
// ============================
// 🎨 Tampilkan Diskon Mitra sejajar dengan baris kupon
// ============================
// ============================
// 💎 Override total kupon WooCommerce khusus untuk tipe mitra_discount
// ============================
add_filter('woocommerce_cart_totals_coupon_html', function ($html, $coupon) {
    // Cek jika kupon bertipe mitra_discount
    if ($coupon->get_discount_type() === 'mitra_discount') {

        // Ambil nilai diskon dari meta kupon dan total hitungan yang sudah disimpan di session
        $percent  = get_post_meta($coupon->get_id(), '_mitra_discount', true);
        $discount = WC()->session->get('mitra_total_discount');

        // Kalau belum ada di session, fallback ke nol biar gak error
        if (empty($discount)) {
            $discount = 0;
        }

        // Ganti total kupon bawaan WooCommerce dengan tampilan khusus
        $custom_row = sprintf(
            '<tr class="c-cart__totals-discount cart-discount coupon-mitra">
                <th class="c-cart__sub-sub-header" style="color:#3A0BF4;">Diskon Mitra (%s%%)</th>
                <td class="c-cart__totals-price" data-title="%s" style="color:#3A0BF4;">–<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">Rp</span>%s</span></td>
            </tr>',
            esc_html($percent ?: '0'),
            esc_attr__('Diskon Mitra', 'woocommerce'),
            number_format($discount, 0, ',', '.')
        );

        // Return HANYA custom row ini (jadi baris Rp 0 bawaan tidak ditampilkan)
        return $custom_row;
    }

    // Untuk kupon lain, tetap gunakan default HTML WooCommerce
    return $html;
}, 10, 2);


// ============================
// Terbaru 
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

// ============================
// 5️⃣ Hitung Diskon Mitra + DP & Pelunasan
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

    // Tambahkan fee sebagai informasi (tidak mengubah total)
    $cart->add_fee(__('Diskon Mitra (' . $mitra_discount_percent . '%)', 'woocommerce'), -$total_discount, false);
    $cart->add_fee(__('DP (50%)', 'woocommerce'), $dp_amount, false);
    $cart->add_fee(__('Pelunasan (50%)', 'woocommerce'), $pelunasan_amount, false);
}, 20);

// ============================
// 6️⃣ Override tampilan kupon Mitra di cart
// ============================
add_filter('woocommerce_cart_totals_coupon_html', function ($html, $coupon) {
    if ($coupon->get_discount_type() === 'mitra_discount') {
        $percent = get_post_meta($coupon->get_id(), '_mitra_discount', true);
        $discount = WC()->session->get('mitra_total_discount') ?: 0;

        $custom_row = sprintf(
            '<tr class="cart-discount coupon-mitra">
                <th style="color:#3A0BF4;">Diskon Mitra (%s%%)</th>
                <td style="color:#3A0BF4;">–<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">Rp</span>%s</span></td>
            </tr>',
            esc_html($percent ?: '0'),
            number_format($discount, 0, ',', '.')
        );
        return $custom_row;
    }
    return $html;
}, 10, 2);
