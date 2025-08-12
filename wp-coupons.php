<?php

/**
 * Plugin Name: Auto Coupon Code
 * Description: Automatic apply coupon code on cart & checkout system
 * Author: Puji Ermanto <pujiermanto@gmail.com> | AKA Dadang Sukamenak
 * Author URI: https://pujiermanto-portfolio.vercel.app
 */
add_action('wp', 'auto_apply_all_active_coupons_safe');

function auto_apply_all_active_coupons_safe()
{
    if (! class_exists('WooCommerce')) return;
    if (! function_exists('is_cart') || (! is_cart() && ! is_checkout())) return;
    if (! function_exists('WC') || ! WC()->cart) return;

    $coupons = get_posts(array(
        'post_type'   => 'shop_coupon',
        'post_status' => 'publish',
        'numberposts' => -1,
    ));

    $applied_coupon_code = '';

    foreach ($coupons as $coupon_post) {
        $code = $coupon_post->post_title;
        $coupon = new WC_Coupon($code);

        // Skip kalau sudah kadaluarsa
        $expiry_date = $coupon->get_date_expires();
        if ($expiry_date && time() > $expiry_date->getTimestamp()) {
            continue;
        }

        // Skip kalau usage limit habis
        $usage_limit = $coupon->get_usage_limit();
        $usage_count = $coupon->get_usage_count();
        if ($usage_limit && $usage_count >= $usage_limit) {
            continue;
        }

        // Hanya apply kalau valid dan belum ada di cart
        if ($coupon->is_valid() && ! WC()->cart->has_discount($code)) {
            WC()->cart->apply_coupon($code);
            $applied_coupon_code = $code;
        }
    }

    // Jika tidak ada kupon baru diaplikasikan, ambil kupon yang sudah ada di cart
    if (!$applied_coupon_code && !empty(WC()->cart->get_applied_coupons())) {
        $applied_coupon_code = WC()->cart->get_applied_coupons()[0];
    }

    wc_clear_notices();

    if ($applied_coupon_code) {
        add_action('wp_footer', function () use ($applied_coupon_code) {
?>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    function setCouponValue() {
                        var couponInput = document.querySelector('input#coupon_code') ||
                            document.querySelector('input[name="coupon_code"]') ||
                            document.querySelector('input[placeholder*="Coupon"]');

                        if (couponInput && couponInput.value.trim() === '') {
                            couponInput.value = '<?php echo esc_js($applied_coupon_code); ?>';
                        }
                    }

                    setCouponValue();
                    jQuery(document.body).on('updated_wc_div updated_cart_totals updated_checkout', setCouponValue);
                    setInterval(setCouponValue, 1000);
                });
            </script>
<?php
        });
    }
}
