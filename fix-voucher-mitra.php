<?php

/**
 * Plugin Name: Auto Mitra Discount v1.3
 * Description: Diskon Mitra otomatis berdasarkan kategori & qty, tampil rapi di cart/checkout, debug log aktif.
 * Version: 1.3
 * Author: Puji Dev
 */

if (!defined('ABSPATH')) exit;

// 1️⃣ Tambah tipe kupon baru "mitra_discount"
add_filter('woocommerce_coupon_discount_types', function ($types) {
    $types['mitra_discount'] = __('Diskon Mitra (%)', 'mitra');
    return $types;
});

// 2️⃣ Tambah field _mitra_discount di halaman kupon
add_action('woocommerce_coupon_options', function () {
    woocommerce_wp_text_input([
        'id' => '_mitra_discount',
        'label' => __('Persentase Diskon Mitra (%)', 'woocommerce'),
        'desc_tip' => true,
        'description' => 'Contoh: 10 untuk 10%',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0']
    ]);
});

add_action('woocommerce_coupon_options_save', function ($post_id) {
    if (isset($_POST['_mitra_discount'])) {
        update_post_meta($post_id, '_mitra_discount', sanitize_text_field($_POST['_mitra_discount']));
    }
});

// 3️⃣ Hitung diskon Mitra otomatis
add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    $applied_coupons = $cart->get_applied_coupons();
    if (empty($applied_coupons)) return;

    $discount_total = 0;
    $voucher_percent = 0;

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

            $cats = [];
            if (taxonomy_exists('product_cat')) {
                $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
                if (!is_wp_error($terms)) $cats = $terms;
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Product: " . $product->get_name() . " | Cats: " . implode(',', $cats));
            }

            foreach ($cats as $cat) {
                $category_qty[$cat] = ($category_qty[$cat] ?? 0) + $qty;
                $category_totals[$cat] = ($category_totals[$cat] ?? 0) + $line_total;
            }
        }

        $all_apply = false;
        foreach ($conditions as $cond) {
            $cat = $cond['category'];
            $min_qty = $cond['min_qty'];
            if (isset($category_qty[$cat]) && $category_qty[$cat] >= $min_qty) {
                $all_apply = true;
                break;
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== Auto Mitra Discount Debug ===");
            error_log("Applied Coupon: $code");
            error_log("Voucher Percent: $voucher_percent%");
            error_log("Category Qty: " . print_r($category_qty, true));
            error_log("Category Totals: " . print_r($category_totals, true));
            error_log("All Apply? " . ($all_apply ? 'YES' : 'NO'));
        }

        if ($all_apply) {
            foreach ($conditions as $cond) {
                $cat = $cond['category'];
                if (isset($category_totals[$cat])) {
                    $discount_total += $category_totals[$cat] * ($voucher_percent / 100);
                }
            }
        }

        break; // cukup satu kupon pertama
    }

    if ($discount_total > 0) {
        $cart->add_fee('Diskon Mitra (' . $voucher_percent . '%)', -$discount_total, false);
    }

    WC()->session->set('ovm_diskon_kupon', $discount_total);
    WC()->session->set('ovm_voucher_percent', $voucher_percent);
    WC()->session->set('ovm_raw_subtotal', $cart->subtotal);
    WC()->session->set('ovm_total_tagihan', max(0, $cart->subtotal - $discount_total));
}, 20);

// 4️⃣ Print breakdown cart & checkout rapi
add_action('woocommerce_cart_totals_before_order_total', 'ovm_print_breakdown', 20);
add_action('woocommerce_review_order_before_order_total', 'ovm_print_breakdown', 20);
function ovm_print_breakdown()
{
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    $raw_subtotal = WC()->session->get('ovm_raw_subtotal') ?: 0;
    $diskon_kupon = WC()->session->get('ovm_diskon_kupon') ?: 0;
    $voucher_percent = WC()->session->get('ovm_voucher_percent') ?: 0;

    if ($raw_subtotal <= 0) return;

    // Subtotal
    echo '<tr class="custom-subtotal">';
    echo '<th style="text-align:left;">Subtotal</th>';
    echo '<td style="text-align:right;">' . wc_price($raw_subtotal) . '</td>';
    echo '</tr>';

    // Diskon Mitra
    if ($diskon_kupon > 0) {
        echo '<tr class="custom-kupon-mitra">';
        echo '<th style="text-align:left;">Diskon Mitra (' . $voucher_percent . '%)</th>';
        echo '<td style="text-align:right;">–' . wc_price($diskon_kupon) . '</td>';
        echo '</tr>';
    }
}


// 5️⃣ Rapiin tampilan fee
add_filter('woocommerce_cart_totals_get_coupons', function ($coupons) {
    foreach ($coupons as $code => $coupon) {
        $mitra_discount = get_post_meta($coupon->get_id(), '_mitra_discount', true);
        if ($mitra_discount > 0) {
            // hapus kupon mitra supaya WooCommerce tidak render row default
            unset($coupons[$code]);
        }
    }
    return $coupons;
}, 20);

// Hapus kupon mitra otomatis dari applied coupons WooCommerce
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return;

    $applied_coupons = $cart->get_applied_coupons();
    foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if (!$coupon) continue;

        $mitra_discount = get_post_meta($coupon->get_id(), '_mitra_discount', true);
        if ($mitra_discount > 0) {
            $cart->remove_coupon($code); // hapus dari cart
            WC()->session->__unset('applied_coupons'); // update session
        }
    }
}, 5);


// Backup: sembunyikan baris kupon 0 via CSS (theme override)
add_action('wp_head', function () {
    echo '<style>
        .cart-discount td:empty, 
        .cart-discount th:empty,
        .cart-discount td:contains("Rp0") {
            display: none !important;
        }
    </style>';
});


// 6️⃣ Override Total WooCommerce
add_filter('woocommerce_cart_totals_order_total_html', function ($value) {
    $user = wp_get_current_user();
    if (in_array('mitra', (array)$user->roles)) {
        $total_tagihan = WC()->session->get('ovm_total_tagihan') ?: 0;
        return wc_price($total_tagihan);
    }
    return $value;
}, 20);

add_filter('woocommerce_cart_totals_order_total_label', function ($label) {
    $user = wp_get_current_user();
    if (in_array('mitra', (array)$user->roles)) return 'Total Tagihan Invoice';
    return $label;
});

// 7️⃣ Auto-fill kupon Elementor
add_action('wp_print_footer_scripts', function () {
    if (!is_cart() && !is_checkout()) return;

    $args = [
        'post_type'      => 'shop_coupon',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $coupon_query = new WP_Query($args);

    if ($coupon_query->have_posts()) {
        $coupon_post = $coupon_query->posts[0];
        $coupon_code = esc_js($coupon_post->post_name);
?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var activeCoupon = '<?php echo $coupon_code; ?>';
                const openCoupon = () => {
                    const trigger = document.querySelector('.js-cart-coupon');
                    const formWrap = document.querySelector('.c-cart__coupon-from-wrap');
                    if (trigger && formWrap && !formWrap.classList.contains('c-cart__coupon-from-wrap--opened')) trigger.click();
                };
                openCoupon();
                setTimeout(openCoupon, 500);

                const observer = new MutationObserver(() => {
                    const couponInput = document.querySelector('input[name="coupon_code"]');
                    if (couponInput && !couponInput.value) {
                        couponInput.value = activeCoupon;
                        const applyButton = document.querySelector('button[name="apply_coupon"]');
                        if (applyButton) applyButton.click();
                        observer.disconnect();
                    }
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        </script>
<?php
    }
});
