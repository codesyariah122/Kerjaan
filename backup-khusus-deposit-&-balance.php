// ============================
// 5️⃣b FIXED: Integrasi Diskon Mitra dengan Produk Deposit (YITH)
// ============================
// ============================
// 💡 Gabungan Diskon Mitra + YITH Deposit
// ============================
add_action('woocommerce_cart_calculate_fees', function ($cart) {
if (is_admin() && !defined('DOING_AJAX')) return;
if (!is_user_logged_in()) return;

$user = wp_get_current_user();
if (!in_array('mitra', (array) $user->roles)) return;

// Cari kupon mitra aktif
$mitra_coupon = null;
foreach ($cart->get_applied_coupons() as $code) {
$coupon = new WC_Coupon($code);
if ($coupon->get_discount_type() === 'mitra_discount') {
$mitra_coupon = $coupon;
break;
}
}
if (!$mitra_coupon) return;

// Ambil persentase diskon
$mitra_discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
if ($mitra_discount_percent <= 0) return;

    $total_discount=0;
    $total_dp=0;
    $total_pelunasan=0;

    foreach ($cart->get_cart() as $item) {
    $product_id = $item['product_id'];
    $variation_id = $item['variation_id'] ?: $product_id;
    $product = $item['data'];
    $qty = $item['quantity'];
    $price = (float) $product->get_price();
    $subtotal = $price * $qty;

    // Cek apakah produk ini pakai deposit YITH
    $enable_deposit = get_post_meta($variation_id, '_enable_deposit', true) ?: get_post_meta($product_id, '_enable_deposit', true);
    $override = get_post_meta($variation_id, '_override_deposit_options', true) ?: get_post_meta($product_id, '_override_deposit_options', true);

    $is_deposit = ($enable_deposit === 'yes' && $override === 'yes');

    // Hitung diskon
    $discount = ($mitra_discount_percent / 100) * $subtotal;
    $discounted_total = $subtotal - $discount;

    if ($is_deposit) {
    // Produk deposit aktif
    $deposit_rate = (float) get_post_meta($variation_id, '_yith_wcdp_deposit_rate', true);
    if ($deposit_rate <= 0) $deposit_rate=30;

        $dp=($deposit_rate / 100) * $discounted_total;
        $pelunasan=$discounted_total - $dp;

        $total_dp +=$dp;
        $total_pelunasan +=$pelunasan;
        }

        $total_discount +=$discount;
        }

        // Simpan ke session untuk tampilan DP/Pelunasan
        WC()->session->set('mitra_total_discount', $total_discount);
        WC()->session->set('mitra_dp_amount', $total_dp);
        WC()->session->set('mitra_pelunasan_amount', $total_pelunasan);

        // Tambahkan fee negatif (potongan)
        $cart->add_fee(__('Diskon Mitra (' . $mitra_discount_percent . '%)', 'woocommerce'), -$total_discount, false);
        }, 25);

        // ============================
        // 💡 Terapkan Diskon Mitra langsung ke harga produk (agar YITH ikut terpengaruh)
        // ============================
        add_filter('woocommerce_product_get_price', function ($price, $product) {
        if (!is_user_logged_in()) return $price;

        $user = wp_get_current_user();
        if (!in_array('mitra', (array) $user->roles)) return $price;

        // Cek apakah kupon mitra aktif
        $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
        $mitra_coupon = null;

        foreach ($applied_coupons as $code) {
        $coupon = new WC_Coupon($code);
        if ($coupon->get_discount_type() === 'mitra_discount') {
        $mitra_coupon = $coupon;
        break;
        }
        }

        if (!$mitra_coupon) return $price;

        $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
        if ($discount_percent <= 0) return $price;

            // Hitung harga baru
            $discounted_price=(float) $price - ((float) $price * ($discount_percent / 100));
            return $discounted_price;
            }, 10, 2);

            //============================// 💡 Terapkan juga diskon Mitra ke harga DEPOSIT YITH
            //============================add_filter('yith_wcdp_get_deposit_amount', function ($deposit_amount, $product_id, $cart_item) {
            if (!is_user_logged_in()) return $deposit_amount;

            $user=wp_get_current_user();
            if (!in_array('mitra', (array) $user->roles)) return $deposit_amount;

            // Cari kupon mitra aktif
            $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
            $mitra_coupon = null;

            foreach ($applied_coupons as $code) {
            $coupon = new WC_Coupon($code);
            if ($coupon->get_discount_type() === 'mitra_discount') {
            $mitra_coupon = $coupon;
            break;
            }
            }

            if (!$mitra_coupon) return $deposit_amount;

            $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
            if ($discount_percent <= 0) return $deposit_amount;

                // 🔽 Terapkan diskon langsung ke nilai deposit
                $deposit_amount=$deposit_amount - ($deposit_amount * ($discount_percent / 100));

                return $deposit_amount;
                }, 10, 3);

                //============================// 💡 Terapkan diskon Mitra ke tampilan harga DEPOSIT di halaman produk
                //============================add_filter('yith_wcdp_get_product_price', function ($price, $product) {
                if (!is_user_logged_in()) return $price;

                $user=wp_get_current_user();
                if (!in_array('mitra', (array) $user->roles)) return $price;

                // Cek apakah kupon mitra aktif
                $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
                $mitra_coupon = null;

                foreach ($applied_coupons as $code) {
                $coupon = new WC_Coupon($code);
                if ($coupon->get_discount_type() === 'mitra_discount') {
                $mitra_coupon = $coupon;
                break;
                }
                }

                if (!$mitra_coupon) return $price;

                $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
                if ($discount_percent <= 0) return $price;

                    // Terapkan potongan ke harga dasar produk
                    $discounted_price=$price - ($price * ($discount_percent / 100));

                    return $discounted_price;
                    }, 10, 2);

                    //============================// 💡 Kompatibilitas YITH DEPOSIT (Premium) — tampilkan harga diskon di single product
                    //============================add_filter('yith_wcdp_product_get_deposit_amount_from_price', function ($deposit_amount, $price, $product) {
                    if (!is_user_logged_in()) return $deposit_amount;

                    $user=wp_get_current_user();
                    if (!in_array('mitra', (array) $user->roles)) return $deposit_amount;

                    // Cari kupon mitra aktif
                    $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
                    $mitra_coupon = null;

                    foreach ($applied_coupons as $code) {
                    $coupon = new WC_Coupon($code);
                    if ($coupon->get_discount_type() === 'mitra_discount') {
                    $mitra_coupon = $coupon;
                    break;
                    }
                    }

                    if (!$mitra_coupon) return $deposit_amount;

                    $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
                    if ($discount_percent <= 0) return $deposit_amount;

                        // 🔽 Diskon diterapkan ke harga deposit YITH di single product
                        $discounted_deposit=$deposit_amount - ($deposit_amount * ($discount_percent / 100));

                        return $discounted_deposit;
                        }, 10, 3);

                        //============================// 💡 Tampilkan harga deposit yang sudah kena diskon di halaman produk (kompatibel semua versi YITH)
                        //============================add_filter('yith_wcdp_single_product_price_html', function ($html, $product) {
                        if (!is_user_logged_in()) return $html;

                        $user=wp_get_current_user();
                        if (!in_array('mitra', (array) $user->roles)) return $html;

                        // Cari kupon mitra aktif
                        $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
                        $mitra_coupon = null;

                        foreach ($applied_coupons as $code) {
                        $coupon = new WC_Coupon($code);
                        if ($coupon->get_discount_type() === 'mitra_discount') {
                        $mitra_coupon = $coupon;
                        break;
                        }
                        }

                        if (!$mitra_coupon) return $html;

                        $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
                        if ($discount_percent <= 0) return $html;

                            // Ambil harga produk asli
                            $price=(float) $product->get_price();
                            $discounted_price = $price - ($price * ($discount_percent / 100));

                            // Ambil nilai deposit rate dari meta produk
                            $deposit_rate = (float) get_post_meta($product->get_id(), '_yith_wcdp_deposit_rate', true);
                            if ($deposit_rate <= 0) $deposit_rate=30;

                                $deposit_amount=($deposit_rate / 100) * $discounted_price;
                                $balance_amount=$discounted_price - $deposit_amount;

                                // 🔽 Bangun ulang tampilan HTML agar mencerminkan harga baru
                                $html='<div class="yith-wcdp-product-prices">' ;
                                $html .='<p><strong>Pay full amount</strong><br>' . wc_price($discounted_price) . '</p>' ;
                                $html .='<p><strong>Pay deposit</strong><br>' . wc_price($deposit_amount) . '<br>' ;
                                $html .='<small>Balance payment will be required on ' . date_i18n('F j, Y', strtotime('+10 days')) . '</small></p>' ;
                                $html .='</div>' ;

                                return $html;
                                }, 10, 2);

                                //============================// 💡 Terapkan diskon Mitra langsung ke harga regular & sale (agar muncul di single product YITH)
                                //============================add_filter('woocommerce_product_get_regular_price', 'mitra_apply_price_discount' , 99, 2);
                                add_filter('woocommerce_product_get_sale_price', 'mitra_apply_price_discount' , 99, 2);
                                add_filter('woocommerce_product_get_price', 'mitra_apply_price_discount' , 99, 2);

                                // function mitra_apply_price_discount($price, $product)
                                // {
                                // if (!is_user_logged_in() || !$price) return $price;

                                // $user=wp_get_current_user();
                                // if (!in_array('mitra', (array)$user->roles)) return $price;

                                // // Cari kupon mitra aktif
                                // $applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : [];
                                // $mitra_coupon = null;

                                // foreach ($applied_coupons as $code) {
                                // $coupon = new WC_Coupon($code);
                                // if ($coupon->get_discount_type() === 'mitra_discount') {
                                // $mitra_coupon = $coupon;
                                // break;
                                // }
                                // }

                                // if (!$mitra_coupon) return $price;

                                // $discount_percent = (float) get_post_meta($mitra_coupon->get_id(), '_mitra_discount', true);
                                // if ($discount_percent <= 0) return $price;

                                    // // 💰 Terapkan potongan langsung ke semua jenis harga produk
                                    // $discounted=$price - ($price * ($discount_percent / 100));

                                    // return $discounted;
                                    // }
                                    //============================// 💡 Terapkan diskon langsung untuk user role 'mitra' di single product (tanpa butuh kupon aktif)
                                    //============================add_filter('woocommerce_get_price_html', function ($price_html, $product) {
                                    if (!is_user_logged_in()) return $price_html;

                                    $user=wp_get_current_user();
                                    if (!in_array('mitra', (array)$user->roles)) return $price_html;

                                    // Ambil diskon default (misalnya dari opsi global atau meta kupon pertama)
                                    $discount_percent = 50; // Ganti sesuai nilai diskon mitra default kamu

                                    $regular_price = (float) $product->get_regular_price();
                                    if ($regular_price <= 0) return $price_html;

                                        $discounted_price=$regular_price - ($regular_price * ($discount_percent / 100));

                                        // Ganti tampilan HTML harga
                                        $price_html='<span class="price"><del>' . wc_price($regular_price) . '</del> <ins>' . wc_price($discounted_price) . '</ins></span>' ;

                                        return $price_html;
                                        }, 20, 2);