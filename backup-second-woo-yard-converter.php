<?php

/**
 * Plugin Name: WooCommerce Yard Only Converter
 * Description: Konversi panjang (yard) ke quantity otomatis di WooCommerce (product page & mini cart).
 * Version: 1.2
 * Author: Puji Ermanto | AKA: Joni Mangku Wanito Mudo
 */

defined('ABSPATH') || exit;

// ===== ADMIN: Checkbox untuk aktifkan per produk =====
add_action('woocommerce_product_options_general_product_data', function () {
    echo '<div class="options_group">';
    woocommerce_wp_checkbox([
        'id'          => '_enable_yard_converter',
        'label'       => __('Aktifkan Input Yard?', 'woocommerce'),
        'description' => __('Aktifkan konversi panjang yard ke quantity otomatis.'),
    ]);
    echo '</div>';
});

add_action('woocommerce_process_product_meta', function ($post_id) {
    $enabled = isset($_POST['_enable_yard_converter']) ? 'yes' : 'no';
    update_post_meta($post_id, '_enable_yard_converter', $enabled);
});

// ===== Hapus input quantity dan yard di halaman produk =====
add_filter('woocommerce_is_sold_individually', function ($return, $product) {
    if (get_post_meta($product->get_id(), '_enable_yard_converter', true) === 'yes') {
        return true; // Hapus input quantity
    }
    return $return;
}, 10, 2);

// ===== Override quantity berdasarkan input yard (jika disediakan) =====
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['input_yard'])) {
        $yard = floatval($_POST['input_yard']);
        if ($yard > 0) {
            $cart_item_data['yard_input'] = $yard;
            $cart_item_data['quantity'] = $yard;
        }
    }
    return $cart_item_data;
}, 10, 3);

// ===== Tampilkan panjang yard di keranjang dan checkout =====
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (isset($cart_item['yard_input'])) {
        $item_data[] = [
            'key'   => 'Panjang',
            'value' => $cart_item['yard_input'] . ' yard',
        ];
    }
    return $item_data;
}, 10, 2);

// ===== Simpan ke order meta =====
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    if (isset($values['yard_input'])) {
        $item->add_meta_data('Panjang', $values['yard_input'] . ' yard');
    }
}, 10, 4);

// ===== Input yard di Mini Cart (editable) =====
add_filter('woocommerce_cart_item_name', function ($product_name, $cart_item, $cart_item_key) {
    $product = $cart_item['data'];
    if (get_post_meta($product->get_id(), '_enable_yard_converter', true) !== 'yes') {
        return $product_name;
    }

    $yard = isset($cart_item['yard_input']) ? $cart_item['yard_input'] : '';
    ob_start();
?>
    <div class="mini-yard-converter" style="margin-top:10px;font-size:0.9em;">
        <label>Panjang: <input type="number" step="0.01" min="0.01"
                name="cart[<?php echo esc_attr($cart_item_key); ?>][input_yard]" value="<?php echo esc_attr($yard); ?>"
                style="width:80px;margin-left:5px;"> yard</label>
    </div>
    <script>
        (function() {
            const block = document.querySelector('.mini-yard-converter');
            if (!block) return;
            const input = block.querySelector('input[type=number]');
            const quantity = block.closest('.woocommerce-mini-cart-item')
                .querySelector('.quantity');

            function upd() {
                const v = parseFloat(input.value) || 0;
                quantity.textContent = v + ' × ';
            }
            input.addEventListener('input', upd);
            upd();
        })();
    </script>
<?php
    return $product_name . ob_get_clean();
}, 10, 3);

// ===== Update qty di cart dari mini cart input yard =====
add_action('woocommerce_cart_updated', function () {
    if (isset($_POST['cart']) && is_array($_POST['cart'])) {
        foreach ($_POST['cart'] as $key => $vals) {
            if (isset(WC()->cart->cart_contents[$key]['yard_input']) && isset($vals['input_yard'])) {
                WC()->cart->cart_contents[$key]['yard_input'] = floatval($vals['input_yard']);
                WC()->cart->cart_contents[$key]['quantity'] = floatval($vals['input_yard']);
            }
        }
    }
});

// Hapus input quantity di halaman single product jika opsi yard aktif
add_filter('woocommerce_quantity_input_args', function ($args, $product) {
    if (is_product() && get_post_meta($product->get_id(), '_enable_yard_converter', true) === 'yes') {
        $args['min_value'] = 1;
        $args['max_value'] = 1;
        $args['input_value'] = 1;
        $args['style'] = 'display:none;'; // Sembunyikan input
    }
    return $args;
}, 10, 2);

// Hapus label dan wrapper quantity agar bersih
add_action('woocommerce_before_add_to_cart_quantity', function () {
    global $product;
    if (get_post_meta($product->get_id(), '_enable_yard_converter', true) === 'yes') {
        echo '<style>.quantity { display: none !important; }</style>';
    }
});
