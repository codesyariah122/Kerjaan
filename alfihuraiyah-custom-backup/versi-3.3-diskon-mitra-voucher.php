/**
 * Plugin Name: Override Voucher Mitra (v3.3 Full Fix)
 * Description: Versi final, menghapus total tampilan YITH Deposit bawaan (Inggris) dan menampilkan versi Mitra full Bahasa Indonesia + field Diskon Mitra di kupon.
 * Version: 3.3
 * Author: Puji Ermanto | UCOK AKA
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

add_action('admin_footer', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'shop_coupon') :
?>
<script>
jQuery(function($){
    function toggleMitraField(){
        var val = $('#discount_type').val();
        if(val === 'mitra_discount') $('._mitra_discount_field').show();
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
function ovm_get_applied_voucher_percent() {
	if ( ! WC()->cart ) return 0;
	foreach ( WC()->cart->get_applied_coupons() as $code ) {
		$coupon = new WC_Coupon( $code );
		if ( $coupon->get_discount_type() === 'mitra_discount' ) {
			$meta = get_post_meta( $coupon->get_id(), '_mitra_discount', true );
			return (float) $meta;
		}
	}
	return 0;
}

function ovm_get_mitra_price_percent( $pid = null ) {
	$default = 50;
	if ( ! $pid ) return $default;
	$meta = get_post_meta( $pid, '_mitra_price_percent', true );
	return $meta ? (float) $meta : $default;
}

/* =====================================================
   🧩 BLOK PEMBAYARAN MITRA (Bahasa Indonesia)
===================================================== */
function ovm_render_payment_options_html() {
	if ( ! is_user_logged_in() ) return '';
	$user = wp_get_current_user();
	if ( ! in_array( 'mitra', (array) $user->roles ) ) return '';

	global $product;
	if ( ! $product ) return '';

	$voucher_percent = ovm_get_applied_voucher_percent();
	$mitra_percent   = ovm_get_mitra_price_percent( $product->get_id() );
	$original        = (float) $product->get_regular_price();
	if ( $original <= 0 ) return '';

	$after_voucher = ( $voucher_percent > 0 )
		? $original - ( $original * ( $voucher_percent / 100 ) )
		: $original;

	$harga_mitra = $after_voucher * ( $mitra_percent / 100 );
	$dp          = $harga_mitra * 0.5;
	$pelunasan   = $dp;

	ob_start(); ?>
	<div class="mitra-payment-options"
		style="margin-top:20px;border:1px solid #ddd;padding:15px;border-radius:10px;background:#fafafa;">
		<h4 style="margin-bottom:10px;color:#3A0BF4;">Pilih Skema Pembayaran</h4>

		<label style="display:block;margin-bottom:8px;">
			<input type="radio" name="payment_type" value="full" checked>
			<strong>Bayar Lunas:</strong>
			<span><?php echo wc_price( $harga_mitra ); ?></span><br>
			<?php if ( $voucher_percent > 0 ) : ?>
				<small style="color:#3A0BF4;">
					Harga Mitra setelah diskon <?php echo esc_html( $voucher_percent ); ?>%
					& potongan <?php echo esc_html( $mitra_percent ); ?>%
				</small>
			<?php else : ?>
				<small style="color:#888;">Simulasi harga (belum pakai voucher)</small>
			<?php endif; ?>
		</label>

		<label style="display:block;margin-bottom:8px;">
			<input type="radio" name="payment_type" value="deposit">
			<strong>Bayar DP (50%):</strong>
			<span><?php echo wc_price( $dp ); ?></span><br>
			<small>
				Sisa pelunasan (<?php echo wc_price( $pelunasan ); ?>)
				sebelum <?php echo date_i18n( 'j F Y', strtotime( '+10 days' ) ); ?>
			</small>
		</label>
	</div>
	<?php
	return ob_get_clean();
}

/* =====================================================
   🚫 FORCE REMOVE YITH DEPOSIT (TERLAMBAT)
===================================================== */
add_action('template_redirect', function() {
	if (!is_product()) return;

	// Jalankan setelah semua plugin siap
	add_action('woocommerce_single_product_summary', function() {
		global $wp_filter;
		$hooks = [
			'woocommerce_single_product_summary',
			'woocommerce_after_add_to_cart_form',
			'woocommerce_before_add_to_cart_button'
		];
		foreach ($hooks as $hook) {
			if (isset($wp_filter[$hook])) {
				foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
					foreach ($callbacks as $id => $cb) {
						if (
							(is_array($cb['function']) && strpos(strtolower(serialize($cb['function'])), 'yith_wcdp') !== false)
							|| (is_string($cb['function']) && strpos(strtolower($cb['function']), 'yith_wcdp') !== false)
						) {
							unset($wp_filter[$hook]->callbacks[$priority][$id]);
						}
					}
				}
			}
		}

		// Cetak blok pembayaran Mitra
		echo ovm_render_payment_options_html();
	}, 9999);
});

/* =====================================================
   🪄 FILE KOSONG FALLBACK
===================================================== */
if (!file_exists(__DIR__ . '/empty-template.php')) {
	file_put_contents(__DIR__ . '/empty-template.php', '<?php // Template kosong untuk override YITH ?>');
}
