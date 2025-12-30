<?php

/**
 * Plugin Name: Override Voucher Mitra (v3.9.1 Ultimate Visible Fix)
 * Version: 3.9.1
 * Author: Puji Ermanto | UCOK AKA (with admin coupon fix)
 * Description: Versi paling stabil: lengkap (admin + frontend), sisip blok pembayaran Mitra, paksa tampil di semua tema.
 */

/* =====================================================
   🔧 ADMIN: Tambah tipe kupon “Diskon Mitra”
===================================================== */
add_action('woocommerce_coupon_options', function ($coupon_id) {
    $discount_value = get_post_meta($coupon_id, '_mitra_discount', true);
    echo '<div class="options_group _mitra_discount_field" style="display:none;">';
    woocommerce_wp_text_input([
        'id' => '_mitra_discount',
        'label' => __('Diskon Mitra (%)', 'woocommerce'),
        'type' => 'number',
        'desc_tip' => true,
        'description' => __('Masukkan persentase diskon mitra global (contoh: 25 = 25%)', 'woocommerce'),
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
        'value' => $discount_value ?: ''
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

/* 🧩 Tambahkan JS toggle field Mitra di admin kupon */
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'shop_coupon') :
?>
        <script>
            jQuery(function($) {
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

/* =====================================================
   🔧 HELPER FUNCTIONS
===================================================== */
function ovm_get_applied_voucher_percent()
{
    if (!WC()->cart) return 0;
    foreach (WC()->cart->get_applied_coupons() as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
            $meta = get_post_meta($coupon->get_id(), '_mitra_discount', true);
            return (float) $meta;
        }
    }
    return 0;
}

function ovm_get_mitra_price_percent($pid = null)
{
    $default = 50;
    if (!$pid) return $default;
    $meta = get_post_meta($pid, '_mitra_price_percent', true);
    return $meta ? (float) $meta : $default;
}

/* =====================================================
   🧩 BLOK PEMBAYARAN MITRA
===================================================== */
function ovm_render_payment_options_html()
{
    if (!is_user_logged_in()) return '';
    $user = wp_get_current_user();
    if (!in_array('mitra', (array)$user->roles)) return '';

    global $product;
    if (empty($product)) return '';

    $voucher_percent = ovm_get_applied_voucher_percent();
    $mitra_percent   = ovm_get_mitra_price_percent($product->get_id());
    $original        = (float)$product->get_regular_price();
    if ($original <= 0) return '';

    $after_voucher = ($voucher_percent > 0)
        ? $original - ($original * ($voucher_percent / 100))
        : $original;

    $harga_mitra = $after_voucher * ($mitra_percent / 100);
    $dp          = $harga_mitra * 0.5;
    $pelunasan   = $dp;

    ob_start(); ?>
    <div class="mitra-payment-options"
        style="margin-top:20px;border:1px solid #ddd;padding:15px;border-radius:10px;background:#fafafa;display:block !important;visibility:visible !important;opacity:1 !important;z-index:99999 !important;">
        <h4 style="margin-bottom:10px;color:#3A0BF4;">💰 Pilih Skema Pembayaran Mitra</h4>

        <label style="display:block;margin-bottom:8px;">
            <input type="radio" name="payment_type" value="full" checked>
            <strong>Bayar Lunas:</strong> <span><?php echo wc_price($harga_mitra); ?></span><br>
            <small style="color:#3A0BF4;">Potongan Mitra <?php echo esc_html($mitra_percent); ?>%</small>
        </label>

        <label style="display:block;margin-bottom:8px;">
            <input type="radio" name="payment_type" value="deposit">
            <strong>Bayar DP (50%):</strong> <span><?php echo wc_price($dp); ?></span><br>
            <small>Sisa pelunasan (<?php echo wc_price($pelunasan); ?>)
                sebelum <?php echo date_i18n('j F Y', strtotime('+10 days')); ?></small>
        </label>
    </div>
<?php
    return ob_get_clean();
}

/* =====================================================
   🚫 NONAKTIFKAN YITH DEPOSIT TEMPLATE
===================================================== */
add_filter('wc_get_template', function ($located, $template_name) {
    if (strpos($template_name, 'deposit') !== false || strpos($template_name, 'yith') !== false) {
        return __DIR__ . '/empty-template.php';
    }
    return $located;
}, 9999, 2);

if (!file_exists(__DIR__ . '/empty-template.php')) {
    file_put_contents(__DIR__ . '/empty-template.php', '<?php // Template kosong untuk override YITH ?>');
}

/* =====================================================
   🪄 DOM INJECTOR — Ultimate Fix (v3.9.1)
===================================================== */
add_action('wp_footer', function () {
    if (!is_product()) return;
    $html = ovm_render_payment_options_html();
    if (!$html) return;
    $escaped = json_encode($html);
?>
    <script type="text/javascript">
        (function() {
            var escapedHtml = <?php echo $escaped; ?>;

            function insertMitraBlock() {
                document.querySelectorAll('.yith-wcdp-deposit-wrapper, .yith-wcdp-options, .yith-wcdp-message').forEach(el => el.remove());
                const targets = [
                    '.woocommerce-variation-add-to-cart',
                    'form.cart',
                    '.single_add_to_cart_button',
                    '.product .summary',
                    '.single_variation_wrap'
                ];
                let inserted = false;

                for (const sel of targets) {
                    const el = document.querySelector(sel);
                    if (el && !document.querySelector('.mitra-payment-options')) {
                        const wrap = document.createElement('div');
                        wrap.innerHTML = escapedHtml;
                        try {
                            el.insertAdjacentElement('afterend', wrap);
                        } catch {
                            el.parentNode.insertBefore(wrap, el.nextSibling);
                        }
                        console.log('✅ Blok Pembayaran Mitra disisipkan ke', sel);
                        inserted = true;
                        break;
                    }
                }

                if (!inserted) {
                    const fallback = document.querySelector('.product') || document.body;
                    if (!document.querySelector('.mitra-payment-options')) {
                        const wrap2 = document.createElement('div');
                        wrap2.innerHTML = escapedHtml;
                        fallback.appendChild(wrap2);
                        console.log('✅ Blok Pembayaran Mitra fallback appended');
                    }
                }

                const elBlock = document.querySelector('.mitra-payment-options');
                if (elBlock) {
                    elBlock.style.display = 'block';
                    elBlock.style.visibility = 'visible';
                    elBlock.style.opacity = '1';
                    elBlock.style.zIndex = '99999';
                }
            }

            window.addEventListener('load', function() {
                insertMitraBlock();
                setTimeout(insertMitraBlock, 600);
                const obs = new MutationObserver(() => {
                    if (!document.querySelector('.mitra-payment-options')) insertMitraBlock();
                });
                obs.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        })();
    </script>
<?php
});
