<?php
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
            $applied_coupon_code = $code; // Simpan kode kupon yang dipakai
        }
    }

    // Bersihkan pesan error yang muncul akibat auto apply
    wc_clear_notices();

    // Isi otomatis input coupon code di cart/checkout
    if ($applied_coupon_code) {
        add_action('wp_footer', function () use ($applied_coupon_code) {
?>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    var couponInput = document.querySelector('input#coupon_code');
                    if (couponInput) {
                        couponInput.value = '<?php echo esc_js($applied_coupon_code); ?>';
                    }
                });
            </script>
<?php
        });
    }
}
