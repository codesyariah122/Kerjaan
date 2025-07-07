<?php
/**
 * Plugin Name: WooCommerce Yard Converter
 * Description: Konversi meter ke yard untuk produk kain di WooCommerce.
 * Version: 1.0
 * Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Joni Kusumah
 */

 defined('ABSPATH') || exit;

 // ===== TAMBAH DROPDOWN DAN INPUT METER =====
 add_action('woocommerce_before_add_to_cart_quantity', 'woo_converter_input_fields');
 function woo_converter_input_fields() {
     ?>
     <div class="woo-unit-converter">
         <label for="input_meter">Masukkan panjang:</label><br>
         <input type="number" step="0.01" min="0.1" id="input_meter" name="input_meter" value="1" />
         <select name="input_unit" id="input_unit">
             <option value="meter">Meter</option>
             <option value="yard">Yard</option>
         </select>
     </div>
 
     <script>
     document.addEventListener('DOMContentLoaded', function () {
         const meterInput = document.querySelector('#input_meter');
         const unitSelect = document.querySelector('#input_unit');
         const qtyInput = document.querySelector('input.qty');
 
         function convertToYard(meter) {
             return Math.ceil(meter / 0.9144);
         }
 
         function convertToMeter(yard) {
             return Math.ceil(yard * 0.9144);
         }
 
         function updateQty() {
             const value = parseFloat(meterInput.value);
             const unit = unitSelect.value;
 
             if (!isNaN(value)) {
                 if (unit === 'meter') {
                     qtyInput.value = convertToYard(value);
                 } else {
                     qtyInput.value = Math.ceil(value);
                 }
             }
         }
 
         meterInput.addEventListener('input', updateQty);
         unitSelect.addEventListener('change', updateQty);
         updateQty();
     });
     </script>
     <?php
 }
 
 // ===== SIMPAN KE DATA CART =====
 add_filter('woocommerce_add_cart_item_data', 'woo_save_converter_to_cart', 10, 3);
 function woo_save_converter_to_cart($cart_item_data, $product_id, $variation_id) {
     if (isset($_POST['input_meter']) && isset($_POST['input_unit'])) {
         $unit = wc_clean($_POST['input_unit']);
         $length = floatval($_POST['input_meter']);
 
         // validasi nilai
         if ($length <= 0 || !in_array($unit, ['meter', 'yard'])) {
             wc_add_notice(__('Panjang atau satuan tidak valid.'), 'error');
             return false;
         }
 
         $cart_item_data['converter_input'] = [
             'unit' => $unit,
             'length' => $length,
         ];
     }
     return $cart_item_data;
 }
 
 // ===== TAMPILKAN DI CART DAN CHECKOUT =====
 add_filter('woocommerce_get_item_data', 'woo_display_converter_in_cart', 10, 2);
 function woo_display_converter_in_cart($item_data, $cart_item) {
     if (isset($cart_item['converter_input'])) {
         $item_data[] = [
             'key' => 'Panjang Asli',
             'value' => $cart_item['converter_input']['length'] . ' ' . $cart_item['converter_input']['unit'],
         ];
     }
     return $item_data;
 }
 
 // ===== SIMPAN KE ORDER =====
 add_action('woocommerce_checkout_create_order_line_item', 'woo_add_converter_to_order_items', 10, 4);
 function woo_add_converter_to_order_items($item, $cart_item_key, $values, $order) {
     if (isset($values['converter_input'])) {
         $item->add_meta_data('Panjang Asli', $values['converter_input']['length'] . ' ' . $values['converter_input']['unit']);
     }
 }

 // ===== TAMPILKAN CHECKBOX DI SEMUA PRODUK (SIMPLE & VARIABLE) =====
add_action('woocommerce_product_options_general_product_data', 'woo_add_unit_converter_admin_field');
function woo_add_unit_converter_admin_field() {
    echo '<div class="options_group">';
    woocommerce_wp_checkbox([
        'id' => '_enable_unit_converter',
        'label' => __('Aktifkan Konversi Satuan?', 'woocommerce'),
        'description' => __('Tampilkan input konversi meter ↔ yard di halaman produk ini.'),
    ]);
    echo '</div>';
}

// ===== SIMPAN NILAI CHECKBOX =====
add_action('woocommerce_process_product_meta', 'woo_save_unit_converter_admin_field');
function woo_save_unit_converter_admin_field($post_id) {
    $enabled = isset($_POST['_enable_unit_converter']) ? 'yes' : 'no';
    update_post_meta($post_id, '_enable_unit_converter', $enabled);
}

// ===== TAMPILKAN INPUT HANYA JIKA DIAKTIFKAN =====
add_action('woocommerce_before_add_to_cart_quantity', 'woo_converter_input_fields_conditional', 5);
function woo_converter_input_fields_conditional() {
    global $product;

    $product_id = $product->get_id();
    $enabled = get_post_meta($product_id, '_enable_unit_converter', true);

    if ($enabled === 'yes') {
        woo_converter_input_fields();
    }
}


