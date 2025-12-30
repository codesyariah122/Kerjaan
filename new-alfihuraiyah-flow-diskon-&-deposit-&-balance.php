<?php

/**
 * WooCommerce Mitra Discount + DP Setting
 * Versi 1.5 (dengan panel pengaturan persentase DP di admin WooCommerce)
 */

if (!defined('ABSPATH')) exit;

/**
 * Styling checkout breakdown
 */
add_action('wp_head', function () {
    echo '<style>
        /* === Checkout === */
        .woocommerce-checkout-review-order-table .mitra-breakdown-wrap tr th,
        .woocommerce-checkout-review-order-table .mitra-breakdown-wrap tr td {
            padding: 6px 0 !important;
            vertical-align: middle;
            font-size: 14px;
            line-height: 1.5;
        }
        .woocommerce-checkout-review-order-table .mitra-breakdown-wrap tr th {
            text-align: left !important;
            font-weight: 500;
            color: #222;
            width: 65%;
        }
        .woocommerce-checkout-review-order-table .mitra-breakdown-wrap tr td {
            text-align: right !important;
            font-weight: 600;
            color: #111;
            width: 35%;
            white-space: nowrap;
        }

        /* === Cart === */
        .shop_table.cart .mitra-breakdown-wrap tr th,
        .shop_table.cart .mitra-breakdown-wrap tr td {
            padding: 6px 0 !important;
            vertical-align: middle;
            font-size: 14px;
            line-height: 1.5;
        }
        .shop_table.cart .mitra-breakdown-wrap tr th {
            text-align: left !important;
            font-weight: 500;
            color: #222;
            width: 65%;
        }
        .shop_table.cart .mitra-breakdown-wrap tr td {
            text-align: right !important;
            font-weight: 600;
            color: #111;
            width: 35%;
            white-space: nowrap;
        }

        .mitra-breakdown-wrap tr.total-tagihan th,
        .mitra-breakdown-wrap tr.total-tagihan td {
            border-top: 2px solid #000 !important;
            padding-top: 10px;
            font-weight: 700;
            color: #000;
        }

        .mitra-breakdown-wrap tr.separator-row td hr {
            border: none;
            border-top: 1px solid #ccc;
            margin: 8px 0;
            width: 100%;
            display: block;
        }

        .mitra-breakdown-wrap tr:nth-last-child(2) th,
        .mitra-breakdown-wrap tr:nth-last-child(2) td {
            color: #444;
            font-style: italic;
            font-weight: 500;
        }

        .mitra-breakdown-wrap tr:last-child th,
        .mitra-breakdown-wrap tr:last-child td {
            color: #ED2D56;
            font-weight: 700;
        }
    </style>';
});

/**
 * Paksa harga full untuk Mitra setelah cart dibaca dari session (mengatasi YITH override)
 */
add_action('woocommerce_cart_loaded_from_session', function ($cart) {
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (!empty($cart_item['yith_wcdp_is_deposit']) && $cart_item['yith_wcdp_is_deposit'] === 'yes') {
            if (!empty($cart_item['yith_wcdp_full_price'])) {
                $full_price = floatval($cart_item['yith_wcdp_full_price']);
                $cart_item['data']->set_price($full_price);

                // Tambahan: ubah subtotal line item agar ikut harga full
                $cart_item['line_subtotal'] = $full_price * $cart_item['quantity'];
                $cart_item['line_total'] = $full_price * $cart_item['quantity'];
            }
        }
    }
}, 999);


/**
 * Multi Diskon Mitra
 */
add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    $applied_coupons = $cart->get_applied_coupons();
    if (empty($applied_coupons)) return;

    $discount_details = [];
    $total_discount = 0;

    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if (!$coupon) continue;

        $voucher_percent = floatval(get_post_meta($coupon->get_id(), '_mitra_discount', true));
        if ($voucher_percent <= 0) continue;

        $conditions = [
            ['category' => 'hayfa-series', 'min_qty' => 10],
            ['category' => 'aleena-series', 'min_qty' => 5],
        ];

        $category_qty = [];
        $category_totals = [];

        foreach ($cart->get_cart() as $item) {
            $product = $item['data'];
            $qty = $item['quantity'];
            $line_total = $item['line_total'];

            $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
            $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
            if (!is_wp_error($terms)) {
                foreach ($terms as $cat) {
                    $category_qty[$cat] = ($category_qty[$cat] ?? 0) + $qty;
                    $category_totals[$cat] = ($category_totals[$cat] ?? 0) + $line_total;
                }
            }
        }

        $apply = false;
        foreach ($conditions as $cond) {
            $cat = $cond['category'];
            $min_qty = $cond['min_qty'];
            if (isset($category_qty[$cat]) && $category_qty[$cat] >= $min_qty) {
                $apply = true;
                break;
            }
        }

        if ($apply) {
            $disc_total = 0;
            foreach ($conditions as $cond) {
                $cat = $cond['category'];
                if (isset($category_totals[$cat])) {
                    $disc_total += $category_totals[$cat] * ($voucher_percent / 100);
                }
            }
            $total_discount += $disc_total;
            $discount_details[] = [
                'code' => $code,
                'percent' => $voucher_percent,
                'amount' => $disc_total
            ];
        }
    }

    WC()->session->set('ovm_discount_details', $discount_details);
    WC()->session->set('ovm_total_discount', $total_discount);
}, 30);

/**
 * Tampilkan breakdown diskon + DP di cart/checkout (v16)
 */
add_action('woocommerce_cart_totals_before_order_total', 'ovm_breakdown_invoice_style', 25);
add_action('woocommerce_review_order_before_order_total', 'ovm_breakdown_invoice_style', 25);
function ovm_breakdown_invoice_style()
{
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    $subtotal = WC()->cart->get_subtotal();
    $discounts = WC()->session->get('ovm_discount_details') ?: [];
    $total_discount = WC()->session->get('ovm_total_discount') ?: 0;

    $coupon_discount = 0;
    foreach (WC()->cart->get_coupons() as $code => $coupon) {
        $coupon_discount += WC()->cart->get_coupon_discount_amount($code, WC()->cart->display_cart_ex_tax);
    }

    $total_after_discount = $subtotal - ($total_discount + $coupon_discount);

    $mitra_dp_percent = floatval(get_option('ovm_mitra_dp_percent', 50));
    $mitra_dp_percent = max(1, min(100, $mitra_dp_percent));

    $dp_amount = $total_after_discount * ($mitra_dp_percent / 100);
    $pelunasan = $total_after_discount - $dp_amount;

    echo '<tbody class="mitra-breakdown-wrap">';
    echo '<tr><th>Subtotal Produk</th><td>' . wc_price($subtotal) . '</td></tr>';

    if ($coupon_discount > 0) {
        echo '<tr><th>Diskon Kupon</th><td>–' . wc_price($coupon_discount) . '</td></tr>';
    }

    // echo '<tr class="separator-row"><td colspan="2"><hr></td></tr>';

    echo '<tr class="total-tagihan"><th>Total Tagihan Invoice</th><td>' . wc_price($total_after_discount) . '</td></tr>';
    echo '<tr><th>Pelunasan</th><td>' . wc_price($pelunasan) . '</td></tr>';
    echo '<tr class="dp-row"><th>Total Bayar (DP ' . $mitra_dp_percent . '%)</th><td>' . wc_price($dp_amount) . '</td></tr>';
    echo '</tbody>';

    // CSS sederhana kiri-kanan
    add_action('wp_footer', function () {
        echo '<style>
        .mitra-breakdown-wrap th, .mitra-breakdown-wrap td {
            padding: 6px 0;
            font-size: 14px;
            text-align: left;
            vertical-align: middle;
        }
        .mitra-breakdown-wrap td {
            text-align: right;
            font-weight: 600;
        }
        .mitra-breakdown-wrap tr.total-tagihan th,
        .mitra-breakdown-wrap tr.total-tagihan td,
        .mitra-breakdown-wrap tr.dp-row th,
        .mitra-breakdown-wrap tr.dp-row td {
            font-weight: 700;
            color: #ED2D56;
        }
        .mitra-breakdown-wrap tr.separator-row td hr {
            border: none;
            border-top: 1px solid #ccc;
            margin: 6px 0;
        }
        </style>';
    }, 99);
}

add_filter('woocommerce_cart_totals_order_total_html', function ($value) {
    $user = wp_get_current_user();
    if (in_array('mitra', (array)$user->roles)) {
        return ''; // sembunyikan total bawaan
    }
    return $value;
});


/**
 * Tambah setting "Persentase DP Mitra" di WooCommerce → Settings → Mitra
 */
add_filter('woocommerce_get_sections_general', function ($sections) {
    $sections['mitra'] = __('Mitra', 'woocommerce');
    return $sections;
});

add_filter('woocommerce_get_settings_general', function ($settings, $current_section) {
    if ('mitra' === $current_section) {
        $settings_mitra = [
            [
                'title' => __('Pengaturan Mitra', 'woocommerce'),
                'type'  => 'title',
                'desc'  => 'Atur persentase DP khusus untuk user dengan role Mitra.',
                'id'    => 'ovm_mitra_settings'
            ],
            [
                'title'    => __('Persentase DP Mitra (%)', 'woocommerce'),
                'desc_tip' => true,
                'id'       => 'ovm_mitra_dp_percent',
                'type'     => 'number',
                'css'      => 'min-width:100px;',
                'default'  => '50',
                'autoload' => false,
                'desc'     => 'Masukkan persentase DP (misal: 30 untuk 30%)'
            ],
            ['type' => 'sectionend', 'id' => 'ovm_mitra_settings'],
        ];
        return $settings_mitra;
    }
    return $settings;
}, 10, 2);

/**
 * Filter harga produk agar user MITRA selalu dapat harga full,
 * walau YITH Deposits memanggil get_price() (mengatasi override internal)
 */
add_filter('woocommerce_product_get_price', function ($price, $product) {
    $user = wp_get_current_user();
    if (in_array('mitra', (array) $user->roles)) {

        // Ambil meta harga full dari YITH jika ada
        $full_price = get_post_meta($product->get_id(), '_yith_wcdp_product_price', true);
        if (!$full_price) {
            $full_price = get_post_meta($product->get_id(), 'yith_wcdp_product_price', true);
        }

        // Jika harga full tersedia dan lebih besar dari harga deposit, gunakan itu
        if (!empty($full_price) && floatval($full_price) > floatval($price)) {
            $price = floatval($full_price);
        }
    }
    return $price;
}, 999, 2);

/**
 * Paksa harga FULL untuk user role "mitra"
 * Bekerja dengan YITH WooCommerce Deposits (semua versi)
 */
// add_action('woocommerce_before_calculate_totals', function ($cart) {
//     if (is_admin() && !defined('DOING_AJAX')) return;

//     $user = wp_get_current_user();
//     if (!in_array('mitra', (array)$user->roles)) return;

//     foreach ($cart->get_cart() as $cart_item_key => &$cart_item) {
//         $full_price = null;

//         // 1️⃣ — Ambil dari struktur YITH cart (beberapa versi pakai ini)
//         if (!empty($cart_item['yith_wcdp_full_price'])) {
//             $full_price = floatval($cart_item['yith_wcdp_full_price']);
//         } elseif (!empty($cart_item['yith_wcdp']['full_price'])) {
//             $full_price = floatval($cart_item['yith_wcdp']['full_price']);
//         } elseif (!empty($cart_item['yith_wcdp']['full_amount'])) {
//             $full_price = floatval($cart_item['yith_wcdp']['full_amount']) / max(1, $cart_item['quantity']);
//         }

//         // 2️⃣ — Kalau masih null, coba hitung manual dari rate & deposit amount
//         if (empty($full_price)) {
//             $deposit_amount = $cart_item['yith_wcdp_deposit_amount'] ?? ($cart_item['yith_wcdp']['deposit_amount'] ?? null);
//             $deposit_rate   = $cart_item['yith_wcdp']['rate'] ?? null;

//             if (!empty($deposit_amount) && !empty($deposit_rate)) {
//                 $full_price = floatval($deposit_amount) / floatval($deposit_rate);
//             }
//         }

//         // 3️⃣ — Override harga cart jika ditemukan harga full
//         if (!empty($full_price) && $full_price > 0) {
//             $cart_item['data']->set_price($full_price);
//             $cart_item['line_total']    = $full_price * $cart_item['quantity'];
//             $cart_item['line_subtotal'] = $full_price * $cart_item['quantity'];
//         }

//         // Debug sementara (hapus nanti)
//         if (defined('WP_DEBUG') && WP_DEBUG) {
//             error_log('[MITRA] ' . $cart_item['data']->get_name() . ' → full_price=' . $full_price);
//         }
//     }
// }, 99999);
/**
 * Paksa harga FULL untuk user role "mitra"
 * Sesuai struktur baru YITH WooCommerce Deposits
 */
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    foreach ($cart->get_cart() as $key => &$item) {

        // YITH struktur baru
        if (!empty($item['deposit']) && !empty($item['deposit_type']) && $item['deposit_type'] === 'rate') {

            $deposit_rate   = floatval($item['deposit_rate']);
            $deposit_value  = floatval($item['deposit_value']);

            // Pastikan keduanya valid
            if ($deposit_rate > 0 && $deposit_value > 0) {
                $full_price = $deposit_value / ($deposit_rate / 100);

                // Terapkan harga penuh per item
                $item['data']->set_price($full_price);
                $item['line_total']    = $full_price * $item['quantity'];
                $item['line_subtotal'] = $full_price * $item['quantity'];

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[MITRA] {$item['data']->get_name()} → full_price dihitung: {$full_price}");
                }
            }
        }
    }
}, 99999);
