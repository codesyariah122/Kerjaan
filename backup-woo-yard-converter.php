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






/**
 * Titip Dulu
 */

<?php

/**
 * Plugin Name: WooCommerce Yard Converter
 * Description: Konversi meter ke yard dan kalkulasi harga berdasarkan harga per yard untuk produk kain (termasuk variable).
 * Version: 2.0
 * Author: Puji Ermanto <pujiermanto@gmail.com> | AKA Puji Mangku Wanito Mudo | AKA Orang paling ganteng di komplek ini
 * Author URI: https://pujiermanto-portfolio.vercel.app
 * Requires at least: 5.5
 * Requires PHP: 7.2
 */


defined('ABSPATH') || exit;

// ====== TAMBAH FIELD DI ADMIN PRODUK ======
// add_action('woocommerce_product_options_general_product_data', 'woo_add_unit_converter_admin_field');
// function woo_add_unit_converter_admin_field()
// {
//     echo '<div class="options_group">';

//     woocommerce_wp_checkbox([
//         'id' => '_enable_unit_converter',
//         'label' => __('Aktifkan Satuan Yard?', 'woocommerce'),
//         'description' => __('Tampilkan input satuan yard / kg di halaman produk ini.'),
//     ]);

//     woocommerce_wp_text_input([
//         'id' => '_price_per_yard',
//         'label' => __('Harga per Yard', 'woocommerce'),
//         'type' => 'number',
//         'desc_tip' => 'true',
//         'description' => __('Masukkan harga per yard untuk produk ini.', 'woocommerce'),
//         'custom_attributes' => [
//             'step' => '0.01',
//             'min' => '0'
//         ]
//     ]);

//     echo '</div>';
// }
// ====== SIMPAN METADATA ADMIN PRODUK ======
// add_action('woocommerce_process_product_meta', 'woo_save_unit_converter_admin_field');
// function woo_save_unit_converter_admin_field($post_id)
// {
//     $enabled = isset($_POST['_enable_unit_converter']) ? 'yes' : 'no';
//     update_post_meta($post_id, '_enable_unit_converter', $enabled);

//     if (isset($_POST['_price_per_yard'])) {
//         update_post_meta($post_id, '_price_per_yard', wc_clean($_POST['_price_per_yard']));
//     }
// }

// Tambahkan field di admin product
add_action('woocommerce_product_options_general_product_data', 'woo_add_unit_converter_admin_field');
function woo_add_unit_converter_admin_field()
{
    echo '<div class="options_group">';

    // Checkbox untuk mengaktifkan fitur
    woocommerce_wp_checkbox([
        'id' => '_enable_unit_converter',
        'label' => __('Aktifkan Pilihan Satuan?', 'woocommerce'),
        'description' => __('Tampilkan input pilihan yard / kg di halaman produk ini.'),
    ]);

    // Ambil meta atau set default 'yard'
    global $post;
    $unit_type = get_post_meta($post->ID, '_unit_type', true);
    if (empty($unit_type)) {
        $unit_type = 'yard'; // default
    }

    // Dropdown pilihan satuan
    woocommerce_wp_select([
        'id' => '_unit_type',
        'label' => __('Pilih Satuan', 'woocommerce'),
        'options' => [
            'yard'      => __('Yard', 'woocommerce'),
            'kilogram'  => __('Kilogram', 'woocommerce'),
        ],
        'description' => __('Pilih satuan yang digunakan untuk harga.', 'woocommerce'),
        'desc_tip'    => true,
        'value'       => $unit_type
    ]);

    // Harga per Yard
    woocommerce_wp_text_input([
        'id' => '_price_per_yard',
        'label' => __('Harga per Yard', 'woocommerce'),
        'type' => 'number',
        'desc_tip' => 'true',
        'description' => __('Masukkan harga per yard untuk produk ini.', 'woocommerce'),
        'custom_attributes' => [
            'step' => '0.01',
            'min' => '0'
        ]
    ]);

    // Harga per Kilogram
    woocommerce_wp_text_input([
        'id' => '_price_per_kg',
        'label' => __('Harga per Kilogram', 'woocommerce'),
        'type' => 'number',
        'desc_tip' => 'true',
        'description' => __('Masukkan harga per kilogram untuk produk ini.', 'woocommerce'),
        'custom_attributes' => [
            'step' => '0.01',
            'min' => '0'
        ]
    ]);

    echo '</div>';
}

// Simpan data
add_action('woocommerce_process_product_meta', 'woo_save_unit_converter_admin_field');
function woo_save_unit_converter_admin_field($post_id)
{
    $enable = isset($_POST['_enable_unit_converter']) ? 'yes' : 'no';
    update_post_meta($post_id, '_enable_unit_converter', $enable);

    if (isset($_POST['_unit_type'])) {
        update_post_meta($post_id, '_unit_type', sanitize_text_field($_POST['_unit_type']));
    }

    if (isset($_POST['_price_per_yard'])) {
        update_post_meta($post_id, '_price_per_yard', wc_format_decimal($_POST['_price_per_yard']));
    }

    if (isset($_POST['_price_per_kg'])) {
        update_post_meta($post_id, '_price_per_kg', wc_format_decimal($_POST['_price_per_kg']));
    }
}




// ====== TAMPILKAN INPUT DI HALAMAN PRODUK ======
add_action('wp_enqueue_scripts', 'woo_converter_inline_style');
function woo_converter_inline_style()
{
    if (is_product()) {
        $product_id = get_queried_object_id();
        if ($product_id && get_post_meta($product_id, '_enable_unit_converter', true) === 'yes') {
            wp_register_style('woo-yard-converter-inline', false);
            wp_enqueue_style('woo-yard-converter-inline');
            wp_add_inline_style('woo-yard-converter-inline', '
                form.cart {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 20px;
                    padding: 0;
                    align-items: flex-start;
                }

                form.cart .woo-converter-wrapper {
                    flex: 1 1 300px;
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .woo-converter-fields {
                    display: flex;
                    gap: 15px;
                    align-items: flex-end;
                    flex-wrap: wrap;
                }

                .woo-converter-fields > * {
                    flex: 1 1 120px;
                }

                .woo-unit-converter {
                    flex: 1 1 auto;
                }

                .woo-btn-wrapper {
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                    margin-top: 10px;
                }

                .woo-btn-wrapper .button {
                    flex: 1 1 200px;
                    max-width: 250px;
                }

                .woo-converter-fields .quantity {
                    flex: 1 1 100px;
                    align-self: flex-start;
                    margin-top: 32px; /* atur agar sejajar dengan input satuan */
                }

                @media(min-width: 600px) {
                    .woo-btn-wrapper .button {
                        width: auto;
                    }
                }
            ');
        }
    }
}

// Tombol beli via whatsapp
add_action('wp_enqueue_scripts', 'woo_add_custom_cart_button_style', 20);
function woo_add_custom_cart_button_style()
{
    if (!is_product()) return;

    $product_id = get_queried_object_id();
    if (!$product_id || get_post_meta($product_id, '_enable_unit_converter', true) !== 'yes') return;

    wp_register_style('woo-custom-buttons-style', false);
    wp_enqueue_style('woo-custom-buttons-style');

    $custom_css = '
        .woo-btn-wrapper {
            display: flex;
            gap: 10px;
            flex-wrap: nowrap; /* Ubah dari wrap → nowrap */
            margin-top: 21px;
            align-items: center;
        }

        .woo-btn-wrapper .button {
            flex: 1 1 auto;
            max-width: none;
        }

        @media (max-width: 600px) {
            .woo-btn-wrapper {
                flex-wrap: wrap; /* agar tetap responsif di mobile */
            }
        }

        .woo-btn-wrapper .beli-langsung-wa {
            background: #25D366 !important;
            color: white !important;
            border: none !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .woo-btn-wrapper .beli-langsung-wa:hover {
            background: #1ebe5d !important;
            box-shadow: 0 4px 10px rgba(37, 211, 102, 0.4);
        }


        .single_add_to_cart_button {
            order: 1;
            border-radius: 4px!important;
        }
    ';

    wp_add_inline_style('woo-custom-buttons-style', $custom_css);
}

function custom_enqueue_fontawesome()
{
    wp_enqueue_style(
        'font-awesome-cdn',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
        array(),
        '6.7.2'
    );
}
add_action('wp_enqueue_scripts', 'custom_enqueue_fontawesome');

function custom_enqueue_fontawesome_scripts()
{
    wp_enqueue_script(
        'font-awesome-js',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js',
        array(),
        '6.7.2',
        true
    );
}
add_action('wp_enqueue_scripts', 'custom_enqueue_fontawesome_scripts');

add_action('woocommerce_after_add_to_cart_button', 'woo_add_beli_langsung_button', 5);
function woo_add_beli_langsung_button()
{
    global $product;
    $product_id = $product->get_id();

    if (get_post_meta($product_id, '_enable_unit_converter', true) !== 'yes') return;

    $product_title = $product->get_title();
    $product_url   = get_permalink($product_id);
    $site_name     = get_bloginfo('name');
    $price_per_yard_raw = get_post_meta($product_id, '_price_per_yard', true);
    $price_per_yard = floatval($price_per_yard_raw);
    $harga_format = 'Rp' . number_format($price_per_yard, 0, ',', '.');
    $total_bayar_format = 'Rp' . number_format($price_per_yard, 0, ',', '.');

    // Ambil nomor WA dari custom post
    $args = array(
        'post_type'      => 'whatsapp_admin',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
    );

    $wa_query = new WP_Query($args);
    $nomor_wa = '6281234567890';
    if ($wa_query->have_posts()) {
        while ($wa_query->have_posts()) {
            $wa_query->the_post();
            $nomor = get_field('nomor_whatsapp_admin');
            if (!empty($nomor)) {
                $nomor_bersih = preg_replace('/[^0-9]/', '', $nomor);
                if (strpos($nomor_bersih, '0') === 0) {
                    $nomor_bersih = '62' . substr($nomor_bersih, 1);
                }
                $nomor_wa = $nomor_bersih;
            }
        }
        wp_reset_postdata();
    }

    // HTML tombol WA
    echo '<div class="woo-btn-wrapper">';
    echo '<a href="#" data-wa="' . esc_attr($nomor_wa) . '" class="button beli-langsung-wa" style="background-color: #25D366; color: white; padding: 20px 25px; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fab fa-whatsapp" style="font-size: 18px; color: white;"></i>
            Beli Langsung
        </a>';
    echo '</div>';

    // Tambahkan JS ke footer
    add_action('wp_footer', function () use ($site_name) {
?>
        <script>
            function getSelectedUnit() {
                const selected = document.querySelector('input[name="input_unit"]:checked');
                return selected ? selected.value : 'meter';
            }

            document.addEventListener('DOMContentLoaded', function() {
                const addToCartBtn = document.querySelector('.single_add_to_cart_button');
                const waBtn = document.querySelector('.beli-langsung-wa');
                if (!waBtn) return;

                if (addToCartBtn && waBtn) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'woo-btn-wrapper';
                    addToCartBtn.parentNode.insertBefore(wrapper, addToCartBtn);
                    wrapper.appendChild(addToCartBtn);
                    wrapper.appendChild(waBtn);
                }

                document.addEventListener('click', function(e) {
                    if (e.target.closest('.beli-langsung-wa')) {
                        e.preventDefault();

                        const unitInput = document.querySelector('input[name="unit_satuan"]:checked');
                        const unit = unitInput ? parseFloat(unitInput.value) || 1 : 1;
                        const unitSelected = getSelectedUnit();
                        const qty = localStorage.getItem('yard_value') ? localStorage.getItem('yard_value') : 1;

                        const title = document.querySelector('h1.product_title')?.textContent.trim() || 'Produk';
                        const url = window.location.href;

                        const variationID = parseInt(jQuery('input[name="variation_id"]').val());
                        const variationData = jQuery('form.variations_form').data('product_variations');
                        const variation = variationData?.find(v => v.variation_id === variationID);

                        let hargaRaw = 0;
                        let gambar = '-';
                        let warna = '-';

                        // 1️⃣ Ambil dari swatch custom kalau ada
                        const selectedSwatch = document.querySelector('.st-swatch-preview li.selected .st-custom-attribute');
                        if (selectedSwatch) {
                            warna = selectedSwatch.getAttribute('data-name').trim();
                        }

                        // 2️⃣ Kalau masih "-", coba ambil dari variation.attributes (fallback)
                        if (warna === '-' && variation) {
                            for (const [key, value] of Object.entries(variation.attributes)) {
                                if (key.includes('attribute_pa_warna') || key.includes('attribute_pa_d697')) {
                                    warna = value.replace(/-/g, ' ').toUpperCase();
                                    break;
                                }
                            }
                        }

                        if (variation) {
                            hargaRaw = variation.display_price || 0;

                            // Ambil warna dari attributes
                            const attrWarna = Object.values(variation.attributes).find(v => v.includes('warna'));
                            if (attrWarna) {
                                warna = attrWarna.replace(/-/g, ' ').toUpperCase();
                            }

                            // Ambil gambar dari object variation.image.src
                            if (variation.image && variation.image.src) {
                                gambar = variation.image.src;
                            }
                        }

                        const total = hargaRaw * unit;
                        const hargaFormat = new Intl.NumberFormat('id-ID').format(hargaRaw);
                        const totalFormat = new Intl.NumberFormat('id-ID').format(total);

                        const pesan = `Halo Admin Damara Kain, saya tertarik untuk membeli produk berikut:\n\n` +
                            `📌 *Nama Produk:* ${title}\n` +
                            `📏 *Jumlah:* ${qty} ${unitSelected}\n` +
                            `🎨 *Warna:* ${warna}\n` +
                            `🖼️ *Gambar:* ${gambar}\n` +
                            `💸 *Harga per ${unit} ${unitSelected}:* Rp${hargaFormat}\n` +
                            `💳 *Total Bayar:* Rp${totalFormat}\n\n` +
                            `🔗 *Link Produk:* ${url}\n\n` +
                            `Mohon konfirmasinya ya, terima kasih 🙏`;

                        const nomor = waBtn.getAttribute('data-wa');
                        const waLink = `https://wa.me/${nomor}?text=${encodeURIComponent(pesan)}`;
                        window.open(waLink, '_blank');
                        setTimeout(() => {
                            localStorage.removeItem('yard_value');
                            document.querySelector('input[name="input_satuan"]').value = 1
                        }, 500)
                    }
                });
            });
        </script>
    <?php
    });
}

// Input yard
add_action('wp_enqueue_scripts', 'woo_register_yard_converter_styles');
function woo_register_yard_converter_styles()
{

    // Hanya load di halaman produk
    if (! is_product()) {
        return;
    }

    $product_id = get_queried_object_id();
    if (! $product_id || get_post_meta($product_id, '_enable_unit_converter', true) !== 'yes') {
        return;
    }

    /* -----------------------------------------------------
     * 1) Register style kosong (tidak butuh file .css fisik)
     * --------------------------------------------------- */
    wp_register_style('woo-yard-converter', false /* no URL */);

    /* -----------------------------------------------------
     * 2) Enqueue supaya <link> muncul di <head>
     * --------------------------------------------------- */
    wp_enqueue_style('woo-yard-converter');

    /* -----------------------------------------------------
     * 3) Susun CSS dan sisipkan inline pada handle tsb
     * --------------------------------------------------- */
    $css = "
        /* —— Layout yang sudah ada —— */
        form.cart {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 0;
            align-items: flex-start;
        }
        form.cart .woo-converter-wrapper {
            flex: 1 1 300px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .woo-converter-fields {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .woo-converter-fields > * {
            flex: 1 1 120px;
        }
        .woo-unit-converter { flex: 1 1 auto; }
        .woo-btn-wrapper {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .woo-btn-wrapper .button {
            flex: 1 1 200px;
            max-width: 250px;
        }
        .woo-converter-fields .quantity {
            flex: 1 1 100px;
            align-self: flex-start;
            margin-top: 32px;
        }

        /* —— CUSTOM RADIO (seperti gambar) —— */
        input[type='radio'][name='input_unit']{
            -webkit-appearance:none;
            appearance:none;
            width:20px;
            height:20px;
            border:2px solid #8a8a8a;      /* ring luar abu */
            border-radius:50%;
            background:#fff;
            position:relative;
            cursor:pointer;
            transition:all .2s ease;
        }
        input[type='radio'][name='input_unit']:checked::before{
            content:'';
            position:absolute;
            top:3px; left:3px;
            width:12px; height:12px;
            border-radius:50%;
            background:#1e1f37; 
        }

        .woo-flex-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .woo-flex-row input[type='number'] {
            width: 150px;
            padding: 8px;
            border: none;
        }

        .woo-unit-box {
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
        }
    ";

    wp_add_inline_style('woo-yard-converter', $css);
}

function load_sweetalert_in_head()
{
    if (is_product()) {
        echo '
        <!-- SweetAlert2 CDN -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        ';
    }
}

add_action('wp_head', 'load_sweetalert_in_head');

function load_sweetalert_css_in_head()
{
    if (is_product()) {
        echo '
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
        ';
    }
}
add_action('wp_head', 'load_sweetalert_css_in_head');

add_action('woocommerce_before_add_to_cart_quantity', 'woo_converter_input_fields_conditional', 5);
function woo_converter_input_fields_conditional()
{
    global $product;
    $product_id = $product->get_id();

    if (get_post_meta($product_id, '_enable_unit_converter', true) !== 'yes') return;
    $default_unit = get_post_meta($product_id, '_unit_type', true) ?: 'yard';
    $price_per_yard_meta = get_post_meta($product_id, '_price_per_yard', true);
    $price_per_kg_meta   = get_post_meta($product_id, '_price_per_kg', true);
    // Helper: normalisasi meta jadi float (ambil nilai numerik pertama jika array)
    $normalize_price = function ($val) {
        if (is_array($val)) {
            // cari nilai numerik pertama
            foreach ($val as $v) {
                if ($v !== '' && is_numeric($v)) {
                    return floatval($v);
                }
            }
            // fallback ke nilai pertama apapun
            $first = reset($val);
            return $first !== false ? floatval($first) : 0.0;
        }
        return is_numeric($val) ? floatval($val) : 0.0;
    };

    $price_per_yard = $normalize_price($price_per_yard_meta);
    $price_per_kg   = $normalize_price($price_per_kg_meta);
    // Cek apakah produk memiliki variasi
    if ($product->is_type('variable')) {
        $available_variations = $product->get_available_variations();
        if (empty($available_variations)) {
            echo '<p>Produk ini saat ini tidak tersedia dan tidak dapat dibeli.</p>';
            return;
        }
    }
    ?>
    <div class="woo-converter-wrapper">
        <label for="input_satuan" style="font-weight: bold;">Unit Satuan</label>

        <div class="woo-converter-fields">
            <div class="woo-unit-converter">
                <div class="woo-flex-row">
                    <?php if ($default_unit === 'yard' || $default_unit === ''): ?>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="input_unit" value="yard" id="unit_yard" <?php checked($default_unit, 'yard'); ?>>
                            Yard
                        </label>
                    <?php endif; ?>

                    <?php if ($default_unit === 'kilogram' || $default_unit === ''): ?>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="input_unit" value="kg" id="unit_kg" <?php checked($default_unit, 'kilogram'); ?>>
                            Kilogram
                        </label>
                    <?php endif; ?>

                    <div class="woo-unit-box">
                        <input type="number" step="0.01" min="0.1" id="input_satuan" name="input_satuan" value="1" />
                        <div id="unit_label" style="background: #1e1f37; color: #fff; padding: 8px 12px;">
                            <?php echo ucfirst($default_unit === 'kilogram' ? 'Kg' : 'Yard'); ?>
                        </div>
                    </div>
                </div>
                <div class="woo-flex-row">
                    <small id="yard-max-alert" style="display: none; color: red; font-size: 12px;">Maksimal order 60 yard.</small>
                </div>
            </div>
        </div>

        <p id="price-per-yard-display"
            data-price-yard="<?php echo esc_attr($price_per_yard); ?>"
            data-price-kg="<?php echo esc_attr($price_per_kg); ?>"
            style="margin: 5px 0; font-weight: bold;">

        </p>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const meterInput = document.querySelector('#input_satuan');
            const unitRadios = document.querySelectorAll('input[name="input_unit"]');
            const qtyInput = document.querySelector('input.qty');
            const unitLabel = document.querySelector('#unit_label');
            const priceDisplay = document.querySelector('#price-per-yard-display');
            const qtyWrapper = document.querySelector('.quantity');
            const converterFields = document.querySelector('.woo-converter-fields');
            const productForm = document.querySelector('form.variations_form');

            function convertToYard(meter) {
                return meter / 0.9144;
            }

            function getSelectedUnit() {
                const selected = document.querySelector('input[name="input_unit"]:checked');
                return selected ? selected.value : 'meter';
            }

            function updateQty() {
                const length = parseFloat(meterInput.value) || 0;
                const unit = getSelectedUnit();

                if (unit === 'yard') {
                    qtyInput.value = Math.ceil(length);
                    unitLabel.textContent = 'Yard';
                } else {
                    qtyInput.value = Math.ceil(length);
                    unitLabel.textContent = 'Kg';
                }
            }

            function updatePrice(variation) {
                if (!variation || !variation.display_price) return;

                const length = parseFloat(meterInput.value) || 0;
                const unit = getSelectedUnit();
                const yardValue = unit === 'meter' ? convertToYard(length) : length;
                const totalPrice = variation.display_price * yardValue;

                const unitLabelText = unit === 'yard' ? 'Yard' : 'Kg'; // <-- pindahkan ke sini

                const priceElem = document.querySelector('.woocommerce-variation-price .price');
                if (priceElem) {
                    priceElem.innerHTML = `Rp ${Math.round(totalPrice).toLocaleString('id-ID')} (${length} ${unitLabelText})`;

                    priceDisplay.textContent = 'Harga per ' + unitLabelText + ': Rp ' +
                        Math.round(totalPrice).toLocaleString('id-ID') + ` / ${unitLabelText} (Bruto)`;
                }

                // Update harga per yard jika priceDisplay ada
                if (priceDisplay) {
                    priceDisplay.textContent = `Harga per ${unitLabelText}: Rp ` +
                        Math.round(totalPrice).toLocaleString('id-ID') + ` / ${unitLabelText} (Bruto)`;
                } else {
                    const nomor_wa = '<?php echo esc_js($nomor_wa); ?>';
                    const product_title = '<?php echo esc_js($product->get_title()); ?>';
                    const message = `Halo, saya ingin menanyakan harga untuk produk: *${product_title}*.`;

                    priceDisplay.innerHTML = '<a href="https://wa.me/' + nomor_wa +
                        '?text=' + encodeURIComponent(message) +
                        '" class="button" style="background-color: #0073aa; color: white; padding: 20px 25px; border-radius: 4px; text-decoration: none;">Call Us</a>';
                }
            }

            window.updatePrice = function(variation) {
                if (!variation || !variation.display_price) return;

                const meterInput = document.querySelector('#input_satuan');
                const unit = document.querySelector('input[name="input_unit"]:checked')?.value || 'yard';
                const length = parseFloat(meterInput.value) || 1;
                const yardValue = unit === 'meter' ? (length / 0.9144) : length;
                const totalPrice = variation.display_price * yardValue;

                setTimeout(() => {
                    const priceElem = document.querySelector('.woocommerce-variation-price .price');
                    if (priceElem) {
                        priceElem.innerHTML = `Rp ${Math.round(totalPrice).toLocaleString('id-ID')} (${length} Yard)`;
                    }

                    const priceDisplay = document.querySelector('#price-per-yard-display');
                    if (priceDisplay) {
                        priceDisplay.textContent = 'Harga per Yard: Rp ' + Math.round(variation.display_price).toLocaleString('id-ID') + '- / Yard (Bruto)';
                    }
                }, 100);
            };

            // Event listeners
            // meterInput.addEventListener('input', updateQty);
            meterInput.addEventListener('input', function() {
                const maxYard = 60;

                let value = parseFloat(meterInput.value);
                if (isNaN(value)) return;

                const unit = getSelectedUnit();
                let qtyVal = value;

                if (unit === 'yard' && qtyVal > maxYard) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Maksimal Order 60 Yard',
                        text: 'Jumlah yard yang Anda masukkan melebihi batas maksimal (60 yard).',
                        confirmButtonColor: '#25D366',
                    });

                    meterInput.value = maxYard;
                    qtyVal = maxYard;
                }

                const alertBox = document.getElementById('yard-max-alert');
                if (alertBox) {
                    alertBox.style.display = qtyVal >= maxYard ? 'block' : 'none';
                }

                if (!isNaN(qtyVal)) {
                    localStorage.setItem('yard_value', qtyVal);
                }

                const priceDisplay = document.getElementById('price-per-yard-display');
                const pricePerYard = parseFloat(priceDisplay.dataset.priceYard) || 0;
                const pricePerKg = parseFloat(priceDisplay.dataset.priceKg) || 0;

                let pricePerUnit = unit === 'yard' ? pricePerYard : pricePerKg;

                console.log(unit)

                let totalPrice = pricePerUnit * qtyVal;

                priceDisplay.textContent = `Harga total: Rp ${totalPrice.toLocaleString('id-ID')} (${qtyVal.toFixed(2)} ${unit === 'yard' ? 'Yard' : 'Kg'})`;

                updateQty();
            });

            meterInput.addEventListener('blur', function() {
                const val = parseFloat(meterInput.value);
                if (!isNaN(val)) {
                    // hanya tambahkan .00 jika angka bulat
                    meterInput.value = Number.isInteger(val) ? val.toFixed(2) : val;
                }
            });


            // unitRadios.forEach(radio => radio.addEventListener('change', updateQty));
            // unitRadios.forEach(radio => radio.addEventListener('change', () => {
            //     updateQty();
            //     const variation = jQuery('form.variations_form').data('product_variations')
            //         ?.find(v => v.variation_id === parseInt(jQuery('input[name="variation_id"]').val()));
            //     if (variation) updatePrice(variation);
            // }));
            unitRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    unitLabel.textContent = (this.value === 'yard') ? 'Yard' : 'Kg';
                });
            });

            // Update harga saat variasi ditemukan
            jQuery('form.variations_form .reset_variations').on('click', function() {
                const priceElem = document.querySelector('.woocommerce-variation-price .price');
                if (priceElem) {
                    priceElem.innerHTML = 'Silakan pilih variasi terlebih dahulu';
                }
                priceDisplay.textContent = '';
            });

            meterInput.addEventListener('input', () => {
                const variation = jQuery('form.variations_form').data('product_variations')
                    ?.find(v => v.variation_id === parseInt(jQuery('input[name="variation_id"]').val()));
                if (variation) updatePrice(variation);
            });

            updateQty();

            const variation = jQuery('form.variations_form').data('product_variations')
                ?.find(v => v.variation_id === parseInt(jQuery('input[name="variation_id"]').val()));
            if (variation) updatePrice(variation);

            jQuery('form.variations_form').on('woocommerce_variation_has_changed', function() {
                const variationId = parseInt(jQuery('input[name="variation_id"]').val());
                const variations = jQuery('form.variations_form').data('product_variations');

                const selected = variations?.find(v => v.variation_id === variationId);
                if (selected) {
                    updatePrice(selected);
                }
            });

        });
    </script>
<?php
}

add_action('woocommerce_after_single_product', function () {
    global $product;

    // Pastikan $product valid dan unit converter aktif
    if (!is_product() || !$product || get_post_meta($product->get_id(), '_enable_unit_converter', true) !== 'yes') {
        return;
    }
?>
    <style>
        /* Overlay full screen */
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99999;
        }

        /* Animasi spinner */
        .spinner {
            border: 6px solid #f3f3f3;
            border-top: 6px solid #25D366;
            /* hijau WhatsApp */
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- HTML overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hapus elemen quantity
            const quantityDiv = document.querySelector('.single-product .quantity');
            const cartForm = document.querySelector('form.cart');
            if (quantityDiv && cartForm) {
                console.log("Load data");
                quantityDiv.remove();
            }

            // Hilangkan overlay setelah load
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
        });
    </script>
<?php
});

// Added variation
add_filter('woocommerce_add_cart_item_data', function ($data, $product_id, $variation_id) {
    if (!empty($_POST['input_satuan'])) {
        $yard = floatval($_POST['input_satuan']);
        if ($yard > 0) {
            $data['input_satuan'] = $yard;
        }
    }

    // Cari attribute warna apa pun secara dinamis
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'attribute_pa_warna') !== false && !empty($value)) {
            $data['variation_warna'] = sanitize_text_field($value);
            break;
        }
    }

    return $data;
}, 10, 3);


// add_action('woocommerce_variation_options', 'add_image_variation_field', 10, 3);
// function add_image_variation_field($loop, $variation_data, $variation)
// {
//     // Ambil ID gambar yang sudah ada
//     $image_id = get_post_meta($variation->ID, 'upload_image_id', true);

//     // Tampilkan input untuk ID gambar
//     woocommerce_wp_hidden_input([
//         'id' => 'upload_image_id[' . $loop . ']',
//         'value' => $image_id
//     ]);

//     // Tampilkan gambar jika ada
//     if ($image_id) {
//         echo wp_get_attachment_image($image_id, 'thumbnail');
//     }
// }

// add_action('woocommerce_save_product_variation', 'save_image_variation_field', 10, 2);
// function save_image_variation_field($variation_id, $i)
// {
//     if (isset($_POST['upload_image_id'][$i])) {
//         $image_id = intval($_POST['upload_image_id'][$i]);
//         update_post_meta($variation_id, 'upload_image_id', $image_id);
//         error_log("Saved image ID: " . $image_id . " for variation ID: " . $variation_id);
//     }
// }

// add_action('woocommerce_save_product_variation', 'save_image_variation_field', 10, 2);
// function save_image_variation_field($variation_id, $i)
// {
//     if (isset($_POST['upload_image_id'])) {
//         error_log("Upload image IDs: " . print_r($_POST['upload_image_id'], true));
//         if (isset($_POST['upload_image_id'][$i])) {
//             $image_id = intval($_POST['upload_image_id'][$i]);
//             // Jika ID gambar adalah 0, coba ambil ID dari URL
//             if ($image_id === 0) {
//                 $image_url = $_POST['upload_image_id'][$i]; // Ambil URL gambar
//                 $image_id = attachment_url_to_postid($image_url); // Dapatkan ID dari URL
//             }
//             update_post_meta($variation_id, 'upload_image_id', $image_id);
//             error_log("Saved image ID: " . $image_id . " for variation ID: " . $variation_id);
//         }
//     }
// }


add_action('admin_footer', 'enqueue_image_uploader_script');
function enqueue_image_uploader_script()
{
?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.upload_image_button').click(function(e) {
                e.preventDefault();
                var button = $(this);
                var loop = button.attr('rel'); // Ambil rel untuk loop
                var uploader = wp.media({
                    title: 'Pilih Gambar',
                    button: {
                        text: 'Pilih'
                    },
                    multiple: false
                }).on('select', function() {
                    var attachment = uploader.state().get('selection').first().toJSON();
                    $('input[name="upload_image_id[' + loop + ']"]').val(attachment.id); // Update input dengan ID
                    button.find('img').attr('src', attachment.sizes.thumbnail.url); // Update gambar
                }).open();
            });
        });
    </script>
    <?php
}

add_action('woocommerce_save_product_variation', 'save_image_variation_field', 10, 2);
function save_image_variation_field($variation_id, $i)
{
    if (isset($_POST['upload_image_id'])) {
        if (isset($_POST['upload_image_id'][$i])) {
            $image_id = intval($_POST['upload_image_id'][$i]);
            update_post_meta($variation_id, 'upload_image_id', $image_id);
            error_log("Saved image ID: " . $image_id . " for variation ID: " . $variation_id);
        }
    }
}



add_filter('woocommerce_cart_item_display', function ($item_name, $cart_item, $cart_item_key) {
    if (isset($cart_item['variation_warna'])) {
        $item_name .= '<br><small>Warna: ' . esc_html($cart_item['variation_warna']) . '</small>';
    }
    return $item_name;
}, 10, 3);

add_filter('woocommerce_cart_item_name', function ($item_name, $cart_item, $cart_item_key) {
    if (isset($cart_item['variation_id'])) {
        // Ambil ID variasi dari item
        $variation_id = $cart_item['variation_id'];
        // Ambil ID gambar dari metadata
        $image_id = get_post_meta($variation_id, 'upload_image_id', true);
        // Jika ada gambar, tambahkan ke nama item
        if ($image_id) {
            $image_url = wp_get_attachment_url($image_id);
            $item_name = '<img src="' . esc_url($image_url) . '" alt="Warna" style="width: 50px; height: auto; margin-right: 5px;">' . $item_name;
        }
    }
    return $item_name;
}, 10, 3);


add_filter('woocommerce_get_price_html', 'custom_variable_price_per_yard_display', 100, 2);
function custom_variable_price_per_yard_display($price_html, $product)
{
    if (!is_product() || is_admin()) {
        return $price_html;
    }

    if (!$product->is_type('variable')) {
        return $price_html;
    }

    $product_id = $product->get_id();

    // Cek apakah konversi aktif
    if (get_post_meta($product_id, '_enable_unit_converter', true) !== 'yes') {
        return $price_html;
    }

    // Ambil semua variasi
    $available_variations = $product->get_available_variations();
    $prices = [];

    foreach ($available_variations as $variation) {
        $vid = $variation['variation_id'];
        $custom_price = get_post_meta($vid, '_price_per_yard', true);

        // Debugging
        error_log("Disini debug harga: " . $custom_price);

        if (is_numeric($custom_price) && $custom_price > 0) {
            $prices[] = floatval($custom_price);
        }
    }

    if (empty($prices)) {
        return '<p class="price">Harga tidak tersedia</p>'; // Tampilkan pesan jika tidak ada harga
    }

    $min = min($prices);
    $max = max($prices);

    if ($min == $max) {
        return '<p class="price">Harga per yard: ' . wc_price($min) . '</p>';
    } else {
        return '<p class="price">Harga per yard: ' . wc_price($min) . ' – ' . wc_price($max) . '</p>';
    }
}


// ====== TAMBAH FIELD HARGA PER YARD DI VARIASI PRODUK ======
add_action('woocommerce_variation_options_pricing', 'woo_add_price_per_yard_variation_field', 10, 3);
function woo_add_price_per_yard_variation_field($loop, $variation_data, $variation)
{
    woocommerce_wp_text_input([
        'id' => '_price_per_yard[' . $loop . ']',
        'class' => 'short',
        'label' => __('Harga per Yard', 'woocommerce'),
        'desc_tip' => 'true',
        'type' => 'number',
        'custom_attributes' => [
            'step' => '0.01',
            'min' => '0'
        ],
        'value' => get_post_meta($variation->ID, '_price_per_yard', true)
    ]);
}

add_action('woocommerce_save_product_variation', 'woo_save_price_per_yard_variation_field', 10, 2);
function woo_save_price_per_yard_variation_field($variation_id, $i)
{
    if (isset($_POST['_price_per_yard'][$i])) {
        update_post_meta($variation_id, '_price_per_yard', wc_clean($_POST['_price_per_yard'][$i]));
    }
}

add_action('woocommerce_variation_options', 'add_default_variation_radio_button', 10, 3);
function add_default_variation_radio_button($loop, $variation_data, $variation)
{
    $checked = get_post_meta($variation->ID, '_is_default_variation', true) === 'yes' ? 'checked' : '';
    echo '<p class="form-row form-row-full">
        <label>' . __('Jadikan sebagai default', 'woocommerce') . '</label><br>
        <input type="radio" name="_is_default_variation_radio" value="' . esc_attr($variation->ID) . '" ' . $checked . '> ' . __('Ya', 'woocommerce') . '
    </p>';
}

add_action('woocommerce_save_product_variation', 'save_default_variation_radio', 10, 2);
function save_default_variation_radio($variation_id, $i)
{
    $is_default = isset($_POST['_is_default_variation_radio']) && $_POST['_is_default_variation_radio'] == $variation_id ? 'yes' : 'no';

    if ($is_default === 'yes') {
        $parent_id = wp_get_post_parent_id($variation_id);
        $children = get_children(['post_parent' => $parent_id, 'post_type' => 'product_variation']);
        foreach ($children as $child) {
            update_post_meta($child->ID, '_is_default_variation', $child->ID == $variation_id ? 'yes' : 'no');
        }
    } else {
        update_post_meta($variation_id, '_is_default_variation', 'no');
    }
}

add_action('admin_footer', 'limit_default_variation_checkbox_selection');
function limit_default_variation_checkbox_selection()
{
    global $pagenow;
    if ($pagenow === 'post.php' && get_post_type() === 'product') {
    ?>
        <script>
            jQuery(document).ready(function($) {
                function enforceSingleDefault() {
                    $('input[name^="_is_default_variation"]').on('change', function() {
                        if ($(this).is(':checked')) {
                            $('input[name^="_is_default_variation"]').not(this).prop('checked', false);
                        }
                    });
                }
                enforceSingleDefault();
            });
        </script>
    <?php
    }
}

// Tambahkan log untuk memeriksa variasi yang diambil
add_filter('woocommerce_available_variation', 'woo_add_price_per_yard_to_variation_json');
function woo_add_price_per_yard_to_variation_json($variation_data)
{
    $variation_id = $variation_data['variation_id'];
    $price_per_yard = get_post_meta($variation_id, '_price_per_yard', true);
    error_log("Variation ID: $variation_id, Price per Yard: $price_per_yard"); // Debug log
    $variation_data['price_per_yard'] = $price_per_yard;
    return $variation_data;
}


add_filter('woocommerce_get_price_html', 'custom_price_per_yard_output', 10, 2);
function custom_price_per_yard_output($price, $product)
{
    if (is_product() && get_post_meta($product->get_id(), '_enable_unit_converter', true) === 'yes') {
        $custom_price = get_post_meta($product->get_id(), '_price_per_yard', true);
        if (!empty($custom_price)) {
            return wc_price($custom_price) . ' <small>/ yard</small>';
        }
    }
    return $price;
}

add_action('wp_footer', 'woo_autoselect_default_variation');
function woo_autoselect_default_variation()
{
    if (!is_product()) return;

    global $product;

    if (!$product->is_type('variable')) return;

    $variations = $product->get_available_variations();
    $attributes = $product->get_variation_attributes();

    // Cari ID variasi yang default
    foreach ($variations as $variation) {
        $variation_id = $variation['variation_id'];
        $is_default = get_post_meta($variation_id, '_is_default_variation', true);

        if ($is_default === 'yes') {
            $default_attrs = $variation['attributes']; // ex: ['attribute_pa_color' => 'blue']
            break;
        }
    }

    if (empty($default_attrs)) return;

    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($default_attrs as $name => $value): ?>
                const select = document.querySelector('[name="<?php echo esc_js($name); ?>"]');
                if (select) {
                    select.value = "<?php echo esc_js($value); ?>";
                    select.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            <?php endforeach; ?>

            // Setelah WooCommerce update DOM, panggil updatePrice
            setTimeout(() => {
                const vid = document.querySelector('input[name="variation_id"]')?.value;
                const vars = jQuery('form.variations_form').data('product_variations');
                const sel = vars?.find(v => v.variation_id == vid);
                if (sel && typeof updatePrice === 'function') updatePrice(sel);
            }, 600);

            // Pastikan harga tetap sinkron saat variasi berubah manual
            jQuery('form.variations_form').on('woocommerce_variation_has_changed', function() {
                const vid2 = jQuery('input[name="variation_id"]').val();
                const vars2 = jQuery(this).data('product_variations');
                const sel2 = vars2?.find(v => v.variation_id == vid2);
                if (sel2 && typeof updatePrice === 'function') updatePrice(sel2);
            });
        });
    </script>
    <?php
}


add_action('wp_ajax_update_cart_quantity', 'custom_update_cart_quantity');
add_action('wp_ajax_nopriv_update_cart_quantity', 'custom_update_cart_quantity'); // opsional, jika user belum login

function custom_update_cart_quantity()
{
    check_ajax_referer('update_cart_nonce'); // validasi nonce

    $new_qty = isset($_POST['input_satuan']) ? floatval($_POST['input_satuan']) : 0;
    if ($new_qty <= 0) {
        wp_send_json_error('Kuantitas tidak valid');
    }

    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        WC()->cart->set_quantity($cart_item_key, $new_qty, true); // true: trigger recalculation
        WC()->cart->cart_contents[$cart_item_key]['input_satuan'] = $new_qty;
        break; // hanya 1 item
    }

    WC()->cart->calculate_totals();

    wp_send_json_success('Berhasil update cart');
}

// Menambahkan checkbox untuk menyembunyikan harga produk
add_action('woocommerce_product_options_general_product_data', 'woo_add_hide_price_field');
function woo_add_hide_price_field()
{
    echo '<div class="options_group">';

    woocommerce_wp_checkbox([
        'id' => '_hide_product_price',
        'label' => __('Sembunyikan Harga Produk?', 'woocommerce'),
        'description' => __('Centang untuk menyembunyikan harga produk di halaman produk.', 'woocommerce'),
    ]);

    echo '</div>';
}

// Menyimpan status checkbox
add_action('woocommerce_process_product_meta', 'woo_save_hide_price_field');
function woo_save_hide_price_field($post_id)
{
    $hide_price = isset($_POST['_hide_product_price']) ? 'yes' : 'no';
    update_post_meta($post_id, '_hide_product_price', $hide_price);
}

// Menyembunyikan harga produk
add_filter('woocommerce_get_price_html', 'custom_hide_product_price', 10, 2);
function custom_hide_product_price($price, $product)
{
    if (get_post_meta($product->get_id(), '_hide_product_price', true) === 'yes') {
        return '<span class="hidden-price">Harga disembunyikan</span>';
    }
    return $price;
}

// Mengubah status produk menjadi tidak dapat dibeli jika harga disembunyikan
add_filter('woocommerce_is_purchasable', 'custom_hide_add_to_cart', 10, 2);
function custom_hide_add_to_cart($purchasable, $product)
{
    if (get_post_meta($product->get_id(), '_hide_product_price', true) === 'yes') {
        return false; // Produk tidak dapat dibeli
    }
    return $purchasable;
}

// Menyembunyikan harga produk dan tombol pembelian
// add_action('template_redirect', 'custom_conditionally_remove_add_to_cart_price');
// function custom_conditionally_remove_add_to_cart_price()
// {
//     if (!is_product()) return;

//     global $post;
//     $product_id = $post->ID;

//     if (get_post_meta($product_id, '_hide_product_price', true) === 'yes') {
//         // Hapus elemen harga & tombol add to cart
//         remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
//         remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

//         // Tambahkan tombol WhatsApp
//         add_action('woocommerce_single_product_summary', 'add_call_to_us_button', 30);
//     }
// }


// Menambahkan tombol Call To Us
function add_call_to_us_button()
{
    global $product;

    // Ambil nomor WhatsApp dari post type whatsapp_admin
    $whatsapp_number = get_whatsapp_number();
    if ($whatsapp_number) {
        // Format nomor WhatsApp
        $formatted_number = preg_replace('/^0/', '62', $whatsapp_number);
        $message = urlencode('Saya ingin menanyakan harga untuk produk: ' . $product->get_name());
        $whatsapp_link = 'https://wa.me/' . $formatted_number . '?text=' . $message;

        echo '<a href="' . esc_url($whatsapp_link) . '" class="button call-to-us" target="_blank">Call To Us</a>';
    }
}

// Fungsi untuk mengambil nomor WhatsApp dari post type whatsapp_admin
function get_whatsapp_number()
{
    $args = [
        'post_type' => 'whatsapp_admin',
        'posts_per_page' => 1,
    ];
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            return get_post_meta(get_the_ID(), 'nomor_whatsapp_admin', true);
        }
        wp_reset_postdata();
    }
    return false; // Jika tidak ada nomor yang ditemukan
}

add_action('woocommerce_single_product_summary', 'custom_insert_call_to_us_button_php', 80);
function custom_insert_call_to_us_button_php()
{
    global $product;

    if (
        is_product() &&
        is_a($product, 'WC_Product') &&
        get_post_meta($product->get_id(), '_hide_product_price', true) === 'yes'
    ) {
        $whatsapp_number = get_whatsapp_number();
        if (!$whatsapp_number) return;

        $formatted_number = preg_replace('/^0/', '62', $whatsapp_number);
        $product_name     = $product->get_name();
        $product_sku      = $product->get_sku();
        $product_id       = $product->get_id();
        $product_stock    = $product->get_stock_quantity();
        if ($product_stock === null || $product_stock === '') {
            $product_stock = $product->is_in_stock() ? 'Tersedia' : 'Kosong';
        }
        $product_link = get_permalink($product_id);
        $site_name    = get_bloginfo('name');

        echo '
        <a href="#" id="call-to-us-btn" 
           class="button call-to-us-button" 
           data-wa="' . esc_attr($formatted_number) . '" 
           data-product="' . esc_attr($product_name) . '" 
           data-sku="' . esc_attr($product_sku) . '"
           data-stock="' . esc_attr($product_stock) . '"
           data-id="' . esc_attr($product_id) . '"
           data-link="' . esc_url($product_link) . '"
           data-site="' . esc_attr($site_name) . '"
           target="_blank">
           <i class="fab fa-whatsapp"></i>
           &nbsp;Call To Us
        </a>

        <style>
        .call-to-us-button {
            background-color: #25D366;
            color: white;
            padding: 14px 24px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
            text-align: center!important;
        }
        .call-to-us-button:hover {
            background-color: white;
            color: #25D366;
            border: 2px solid #25D366;
        }
        .call-to-us-button i {
            font-size: 18px;
        }
        </style>

        <script>
        document.addEventListener("DOMContentLoaded", function () {
            const btn = document.getElementById("call-to-us-btn");
            if (!btn) return;

            btn.addEventListener("click", function (e) {
                e.preventDefault();

                const phone        = btn.getAttribute("data-wa");
                const productName  = btn.getAttribute("data-product");
                const productSKU   = btn.getAttribute("data-sku");
                const productStock = btn.getAttribute("data-stock");
                const productID    = btn.getAttribute("data-id");
                const productLink  = btn.getAttribute("data-link");
                const siteName     = btn.getAttribute("data-site");

                // Ambil jumlah yard dari localStorage
                let yardValue = localStorage.getItem("yard_value") || "1";

                // Ambil variasi warna dari swatch <li.selected>
                let colorText = "Tanpa variasi";
                const selectedColor = document.querySelector(".st-swatch-preview li.selected span[data-name]");
                if (selectedColor) {
                    const colorName = selectedColor.getAttribute("data-name");
                    colorText = "Warna: " + colorName;
                }

                const message = 
`Halo Admin ${siteName} 👋
Saya tertarik dengan produk *${productName}*.
SKU: ${productSKU}
ID Produk: ${productID}
Stok Tersedia: ${productStock}
Link Produk: ${productLink}
Pilihan saya:
${colorText}
Jumlah: ${yardValue} yard
Mohon infonya lebih lanjut ya.`;

                const waLink = "https://wa.me/" + phone + "?text=" + encodeURIComponent(message);
                window.open(waLink, "_blank");
            });
        });
        </script>';
    }
}


add_action('wp_footer', function () {
    if (!is_product()) return;

    global $product;
    if (get_post_meta($product->get_id(), '_hide_product_price', true) === 'yes') {
    ?>
        <script>
            jQuery(function($) {
                // Sembunyikan harga dan tombol beli
                $('.price, .woocommerce-variation-price, #price-per-yard-display').hide();
                // Tampilkan input yard
                $('.woo-converter-wrapper, .woo-converter-fields').show(); // Pastikan input yard ditampilkan
                $('.single_add_to_cart_button, .button-buy-now, .beli-langsung-wa').hide();
                $('form.cart .quantity').show(); // Sembunyikan kuantitas jika diperlukan
            });
        </script>
<?php
    }
});



add_action('wp_head', function () {
    if (!is_product()) return;

    $product_id = get_queried_object_id();
    if (!$product_id) return;

    if (get_post_meta($product_id, '_hide_product_price', true) === 'yes') {
        echo '<style>
            /* Sembunyikan harga dan tombol beli */
            .price,
            .woocommerce-variation-price,
            .woocommerce-Price-amount,
            #price-per-yard-display,
            /*.woo-converter-wrapper, */
            /*.woo-converter-fields,*/
            .single_add_to_cart_button,
            .button-buy-now,
            .beli-langsung-wa,
            form.cart .quantity {
                display: none !important;
            }

            /* Tampilkan product meta dan tabs */
            .product_meta,
            .product-share,
            .woocommerce-tabs,
            .wc-tabs-wrapper,
            .woocommerce-tabs .panel,
            .woocommerce-product-details__short-description,
            #reviews,
            #comments,
            .woocommerce-Reviews {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            .product-share {
                margin-bottom: 2rem!important;
            }
            
        </style>';
    }
});

// Redirect setelah order sukses di halaman thank you, jika ada produk WA order
add_action('woocommerce_thankyou', 'redirect_to_whatsapp_after_order');
function redirect_to_whatsapp_after_order($order_id)
{
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    // Cek produk dengan ACF order_via_whatsapp = 'yes'
    $has_whatsapp_order = false;
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $whatsapp_field = get_field('order_via_whatsapp', $product_id);
        if ($whatsapp_field && in_array('yes', (array) $whatsapp_field)) {
            $has_whatsapp_order = true;
            break;
        }
    }

    if (!$has_whatsapp_order) return; // Jika tidak ada produk WA, skip

    $wa_number = get_whatsapp_number();
    if (!$wa_number) return;

    // Data pelanggan
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $customer_phone = $order->get_billing_phone();
    $customer_email = $order->get_billing_email();
    $order_total = $order->get_formatted_order_total();

    // List produk
    $items_text = "";
    foreach ($order->get_items() as $item) {
        $product_name = $item->get_name();
        $qty = $item->get_quantity();
        $items_text .= "- {$product_name} (Qty: {$qty})\n";
    }

    // Buat pesan WA
    $message = "Halo Admin,\n";
    $message .= "Ada order baru dari website:\n\n";
    $message .= "Nama: {$customer_name}\n";
    $message .= "Telepon: {$customer_phone}\n";
    $message .= "Email: {$customer_email}\n";
    $message .= "Order ID: #{$order_id}\n";
    $message .= "Total: {$order_total}\n";
    $message .= "Detail produk:\n{$items_text}\n";
    $message .= "Mohon tindak lanjut ya. Terima kasih!";

    // Redirect ke WA
    $wa_link = "https://wa.me/" . preg_replace('/^0/', '62', $wa_number) . "?text=" . rawurlencode($message);

    // Redirect hanya kalau bukan admin
    if (!is_admin()) {
        wp_safe_redirect($wa_link);
        exit;
    }
}



// Backup fixed terakhir :
<?php

/**
 * Plugin Name: WooCommerce Yard Converter
 * Description: Konversi meter ke yard dan kalkulasi harga berdasarkan harga per yard untuk produk kain (termasuk variable).
 * Version: 2.0
 * Author: Puji Ermanto <pujiermanto@gmail.com> | AKA Puji Mangku Wanito Mudo | AKA Orang paling ganteng di komplek ini
 * Author URI: https://pujiermanto-portfolio.vercel.app
 * Requires at least: 5.5
 * Requires PHP: 7.2
 */


defined('ABSPATH') || exit;

// Tambahkan field di admin product
add_action('woocommerce_product_options_general_product_data', 'woo_add_unit_converter_admin_field');
function woo_add_unit_converter_admin_field()
{
    echo '<div class="options_group">';

    // Checkbox untuk mengaktifkan fitur
    woocommerce_wp_checkbox([
        'id' => '_enable_unit_converter',
        'label' => __('Aktifkan Pilihan Satuan?', 'woocommerce'),
        'description' => __('Tampilkan input pilihan yard / kg di halaman produk ini.'),
    ]);

    // Ambil meta atau set default 'yard'
    global $post;
    $unit_type = get_post_meta($post->ID, '_unit_type', true);
    if (empty($unit_type)) {
        $unit_type = 'yard'; // default
    }

    // Dropdown pilihan satuan
    woocommerce_wp_select([
        'id' => '_unit_type',
        'label' => __('Pilih Satuan', 'woocommerce'),
        'options' => [
            'yard'      => __('Yard', 'woocommerce'),
            'kilogram'  => __('Kilogram', 'woocommerce'),
        ],
        'description' => __('Pilih satuan yang digunakan untuk harga.', 'woocommerce'),
        'desc_tip'    => true,
        'value'       => $unit_type
    ]);

    // Harga per Yard
    woocommerce_wp_text_input([
        'id' => '_price_per_yard',
        'label' => __('Harga per Yard', 'woocommerce'),
        'type' => 'number',
        'desc_tip' => 'true',
        'description' => __('Masukkan harga per yard untuk produk ini.', 'woocommerce'),
        'custom_attributes' => [
            'step' => '0.01',
            'min' => '0'
        ]
    ]);

    // Harga per Kilogram
    woocommerce_wp_text_input([
        'id' => '_price_per_kg',
        'label' => __('Harga per Kilogram', 'woocommerce'),
        'type' => 'number',
        'desc_tip' => 'true',
        'description' => __('Masukkan harga per kilogram untuk produk ini.', 'woocommerce'),
        'custom_attributes' => [
            'step' => '0.01',
            'min' => '0'
        ]
    ]);

    echo '</div>';
}

// Simpan data
add_action('woocommerce_process_product_meta', 'woo_save_unit_converter_admin_field');
function woo_save_unit_converter_admin_field($post_id)
{
    $enable = isset($_POST['_enable_unit_converter']) ? 'yes' : 'no';
    update_post_meta($post_id, '_enable_unit_converter', $enable);

    if (isset($_POST['_unit_type'])) {
        update_post_meta($post_id, '_unit_type', sanitize_text_field($_POST['_unit_type']));
    }

    if (isset($_POST['_price_per_yard'])) {
        update_post_meta($post_id, '_price_per_yard', wc_format_decimal($_POST['_price_per_yard']));
    }

    if (isset($_POST['_price_per_kg'])) {
        update_post_meta($post_id, '_price_per_kg', wc_format_decimal($_POST['_price_per_kg']));
    }
}


// ====== TAMPILKAN INPUT DI HALAMAN PRODUK ======
add_action('wp_enqueue_scripts', 'woo_converter_inline_style');
function woo_converter_inline_style()
{
    if (is_product()) {
        $product_id = get_queried_object_id();
        if ($product_id && get_post_meta($product_id, '_enable_unit_converter', true) === 'yes') {
            wp_register_style('woo-yard-converter-inline', false);
            wp_enqueue_style('woo-yard-converter-inline');
            wp_add_inline_style('woo-yard-converter-inline', '
                form.cart {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 20px;
                    padding: 0;
                    align-items: flex-start;
                }

                form.cart .woo-converter-wrapper {
                    flex: 1 1 300px;
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .woo-converter-fields {
                    display: flex;
                    gap: 15px;
                    align-items: flex-end;
                    flex-wrap: wrap;
                }

                .woo-converter-fields > * {
                    flex: 1 1 120px;
                }

                .woo-unit-converter {
                    flex: 1 1 auto;
                }

                .woo-btn-wrapper {
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                    margin-top: 10px;
                }

                .woo-btn-wrapper .button {
                    flex: 1 1 200px;
                    max-width: 250px;
                }

                .woo-converter-fields .quantity {
                    flex: 1 1 100px;
                    align-self: flex-start;
                    margin-top: 32px; /* atur agar sejajar dengan input satuan */
                }

                @media(min-width: 600px) {
                    .woo-btn-wrapper .button {
                        width: auto;
                    }
                }
            ');
        }
    }
}

// Tombol beli via whatsapp
add_action('wp_enqueue_scripts', 'woo_add_custom_cart_button_style', 20);
function woo_add_custom_cart_button_style()
{
    if (!is_product()) return;

    $product_id = get_queried_object_id();
    if (!$product_id || get_post_meta($product_id, '_enable_unit_converter', true) !== 'yes') return;

    wp_register_style('woo-custom-buttons-style', false);
    wp_enqueue_style('woo-custom-buttons-style');

    $custom_css = '
        .woo-btn-wrapper {
            display: flex;
            gap: 10px;
            flex-wrap: nowrap; /* Ubah dari wrap → nowrap */
            margin-top: 21px;
            align-items: center;
        }

        .woo-btn-wrapper .button {
            flex: 1 1 auto;
            max-width: none;
        }

        @media (max-width: 600px) {
            .woo-btn-wrapper {
                flex-wrap: wrap; /* agar tetap responsif di mobile */
            }
        }

        .woo-btn-wrapper .beli-langsung-wa {
            background: #25D366 !important;
            color: white !important;
            border: none !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .woo-btn-wrapper .beli-langsung-wa:hover {
            background: #1ebe5d !important;
            box-shadow: 0 4px 10px rgba(37, 211, 102, 0.4);
        }


        .single_add_to_cart_button {
            order: 1;
            border-radius: 4px!important;
        }
    ';

    wp_add_inline_style('woo-custom-buttons-style', $custom_css);
}

function custom_enqueue_fontawesome()
{
    wp_enqueue_style(
        'font-awesome-cdn',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
        array(),
        '6.7.2'
    );
}
add_action('wp_enqueue_scripts', 'custom_enqueue_fontawesome');

function custom_enqueue_fontawesome_scripts()
{
    wp_enqueue_script(
        'font-awesome-js',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js',
        array(),
        '6.7.2',
        true
    );
}
add_action('wp_enqueue_scripts', 'custom_enqueue_fontawesome_scripts');

add_action('woocommerce_after_add_to_cart_button', 'woo_add_beli_langsung_button', 5);
function woo_add_beli_langsung_button()
{
    global $product;
    $product_id = $product->get_id();

    if (get_post_meta($product_id, '_enable_unit_converter', true) !== 'yes') return;

    $product_title = $product->get_title();
    $product_url   = get_permalink($product_id);
    $site_name     = get_bloginfo('name');
    $price_per_yard_raw = get_post_meta($product_id, '_price_per_yard', true);
    $price_per_yard = floatval($price_per_yard_raw);
    $harga_format = 'Rp' . number_format($price_per_yard, 0, ',', '.');
    $total_bayar_format = 'Rp' . number_format($price_per_yard, 0, ',', '.');

    // Ambil nomor WA dari custom post
    $args = array(
        'post_type'      => 'whatsapp_admin',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
    );

    $wa_query = new WP_Query($args);
    $nomor_wa = '6281234567890';
    if ($wa_query->have_posts()) {
        while ($wa_query->have_posts()) {
            $wa_query->the_post();
            $nomor = get_field('nomor_whatsapp_admin');
            if (!empty($nomor)) {
                $nomor_bersih = preg_replace('/[^0-9]/', '', $nomor);
                if (strpos($nomor_bersih, '0') === 0) {
                    $nomor_bersih = '62' . substr($nomor_bersih, 1);
                }
                $nomor_wa = $nomor_bersih;
            }
        }
        wp_reset_postdata();
    }

    // HTML tombol WA
    echo '<div class="woo-btn-wrapper">';
    echo '<a href="#" data-wa="' . esc_attr($nomor_wa) . '" class="button beli-langsung-wa" style="background-color: #25D366; color: white; padding: 20px 25px; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fab fa-whatsapp" style="font-size: 18px; color: white;"></i>
            Beli Langsung
        </a>';
    echo '</div>';

    // Tambahkan JS ke footer
    add_action('wp_footer', function () use ($site_name) {
?>
        <script>
            // Fungsi ambil satuan yang dipilih
            function getSelectedUnit() {
                const selected = document.querySelector('input[name="input_unit"]:checked');
                return selected ? selected.value : 'yard';
            }

            // Fungsi ambil jumlah input (misalnya 3 yard)
            function getSelectedQty() {
                const inputSatuan = document.querySelector('input[name="input_satuan"]');
                return inputSatuan ? parseFloat(inputSatuan.value) || 1 : 1;
            }

            document.addEventListener('DOMContentLoaded', function() {
                const addToCartBtn = document.querySelector('.single_add_to_cart_button');
                const waBtn = document.querySelector('.beli-langsung-wa');
                if (!waBtn) return;

                if (addToCartBtn && waBtn) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'woo-btn-wrapper';
                    addToCartBtn.parentNode.insertBefore(wrapper, addToCartBtn);
                    wrapper.appendChild(addToCartBtn);
                    wrapper.appendChild(waBtn);
                }

                document.addEventListener('click', function(e) {
                    if (e.target.closest('.single_add_to_cart_button')) {
                        const unitInput = document.querySelector('input[name="unit_satuan"]:checked');
                        const unit = unitInput ? parseFloat(unitInput.value) || 1 : 1;
                        const qty = localStorage.getItem('yard_value') ? localStorage.getItem('yard_value') : 1;
                        localStorage.setItem('yard_value', qty);
                        localStorage.setItem('unit_value', unit);

                        console.log("Add To Cart di click")
                        console.log(unitInput);
                        console.log(unit);
                        console.log(qty);

                    }
                });

                document.addEventListener('click', function(e) {
                    if (e.target.closest('.beli-langsung-wa')) {
                        e.preventDefault();

                        const unitSelected = getSelectedUnit();
                        const qty = getSelectedQty();

                        const title = document.querySelector('h1.product_title')?.textContent.trim() || 'Produk';
                        const url = window.location.href;

                        const variationID = parseInt(jQuery('input[name="variation_id"]').val());
                        const variationData = jQuery('form.variations_form').data('product_variations');
                        const variation = variationData?.find(v => v.variation_id === variationID);

                        let hargaRaw = 0;
                        let gambar = '-';
                        let warna = '-';

                        // Ambil warna dari swatch custom (jika ada)
                        const selectedSwatch = document.querySelector('.st-swatch-preview li.selected .st-custom-attribute');
                        if (selectedSwatch) {
                            warna = selectedSwatch.getAttribute('data-name').trim();
                        }

                        // Fallback: dari attribute variation
                        if (warna === '-' && variation) {
                            for (const [key, value] of Object.entries(variation.attributes)) {
                                if (key.includes('attribute_pa_warna') || key.includes('attribute_pa_d697')) {
                                    warna = value.replace(/-/g, ' ').toUpperCase();
                                    break;
                                }
                            }
                        }

                        if (variation) {
                            hargaRaw = variation.display_price || 0;
                            if (variation.image?.src) {
                                gambar = variation.image.src;
                            }
                        } else {
                            // fallback ke price default product jika non-variation
                            const priceEl = document.querySelector('.woocommerce-Price-amount bdi');
                            hargaRaw = priceEl ? parseFloat(priceEl.textContent.replace(/[^\d]/g, '')) || 0 : 0;
                        }

                        // Hitung total sesuai qty aktual
                        const total = hargaRaw * qty;

                        const hargaFormat = new Intl.NumberFormat('id-ID').format(hargaRaw);
                        const totalFormat = new Intl.NumberFormat('id-ID').format(total);

                        const pesan = `Halo Admin Damara Kain, saya tertarik untuk membeli produk berikut:\n\n` +
                            `📌 *Nama Produk:* ${title}\n` +
                            `📏 *Jumlah:* ${qty} ${unitSelected}\n` +
                            `🎨 *Warna:* ${warna}\n` +
                            `🖼️ *Gambar:* ${gambar}\n` +
                            `💸 *Harga per ${unitSelected}:* Rp${hargaFormat}\n` +
                            `💳 *Total Bayar:* Rp${totalFormat}\n\n` +
                            `🔗 *Link Produk:* ${url}\n\n` +
                            `Mohon konfirmasinya ya, terima kasih 🙏`;

                        const nomor = waBtn.getAttribute('data-wa');
                        const waLink = `https://wa.me/${nomor}?text=${encodeURIComponent(pesan)}`;
                        window.open(waLink, '_blank');
                    }
                });
            });
        </script>
    <?php
    });
}

// Input Satuan
add_action('wp_enqueue_scripts', 'woo_register_yard_converter_styles');
function woo_register_yard_converter_styles()
{

    // Hanya load di halaman produk
    if (! is_product()) {
        return;
    }

    $product_id = get_queried_object_id();
    if (! $product_id || get_post_meta($product_id, '_enable_unit_converter', true) !== 'yes') {
        return;
    }

    /* -----------------------------------------------------
     * 1) Register style kosong (tidak butuh file .css fisik)
     * --------------------------------------------------- */
    wp_register_style('woo-yard-converter', false /* no URL */);

    /* -----------------------------------------------------
     * 2) Enqueue supaya <link> muncul di <head>
     * --------------------------------------------------- */
    wp_enqueue_style('woo-yard-converter');

    /* -----------------------------------------------------
     * 3) Susun CSS dan sisipkan inline pada handle tsb
     * --------------------------------------------------- */
    $css = "
        /* —— Layout yang sudah ada —— */
        form.cart {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 0;
            align-items: flex-start;
        }
        form.cart .woo-converter-wrapper {
            flex: 1 1 300px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .woo-converter-fields {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .woo-converter-fields > * {
            flex: 1 1 120px;
        }
        .woo-unit-converter { flex: 1 1 auto; }
        .woo-btn-wrapper {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .woo-btn-wrapper .button {
            flex: 1 1 200px;
            max-width: 250px;
        }
        .woo-converter-fields .quantity {
            flex: 1 1 100px;
            align-self: flex-start;
            margin-top: 32px;
        }

        /* —— CUSTOM RADIO (seperti gambar) —— */
        input[type='radio'][name='input_unit']{
            -webkit-appearance:none;
            appearance:none;
            width:20px;
            height:20px;
            border:2px solid #8a8a8a;      /* ring luar abu */
            border-radius:50%;
            background:#fff;
            position:relative;
            cursor:pointer;
            transition:all .2s ease;
        }
        input[type='radio'][name='input_unit']:checked::before{
            content:'';
            position:absolute;
            top:3px; left:3px;
            width:12px; height:12px;
            border-radius:50%;
            background:#1e1f37; 
        }

        .woo-flex-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .woo-flex-row input[type='number'] {
            width: 150px;
            padding: 8px;
            border: none;
        }

        .woo-unit-box {
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
        }
    ";

    wp_add_inline_style('woo-yard-converter', $css);
}

function load_sweetalert_in_head()
{
    if (is_product()) {
        echo '
        <!-- SweetAlert2 CDN -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        ';
    }
}

add_action('wp_head', 'load_sweetalert_in_head');

function load_sweetalert_css_in_head()
{
    if (is_product()) {
        echo '
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
        ';
    }
}
add_action('wp_head', 'load_sweetalert_css_in_head');

add_action('woocommerce_before_add_to_cart_quantity', 'woo_converter_input_fields_conditional', 5);
function woo_converter_input_fields_conditional()
{
    global $product;
    $product_id = $product->get_id();

    if (get_post_meta($product_id, '_enable_unit_converter', true) !== 'yes') return;
    $default_unit = get_post_meta($product_id, '_unit_type', true) ?: 'yard';
    $price_per_yard_meta = get_post_meta($product_id, '_price_per_yard', true);
    $price_per_kg_meta   = get_post_meta($product_id, '_price_per_kg', true);
    // Helper: normalisasi meta jadi float (ambil nilai numerik pertama jika array)
    $normalize_price = function ($val) {
        if (is_array($val)) {
            // cari nilai numerik pertama
            foreach ($val as $v) {
                if ($v !== '' && is_numeric($v)) {
                    return floatval($v);
                }
            }
            // fallback ke nilai pertama apapun
            $first = reset($val);
            return $first !== false ? floatval($first) : 0.0;
        }
        return is_numeric($val) ? floatval($val) : 0.0;
    };

    $price_per_yard = $normalize_price($price_per_yard_meta);
    $price_per_kg   = $normalize_price($price_per_kg_meta);
    // Cek apakah produk memiliki variasi
    if ($product->is_type('variable')) {
        $available_variations = $product->get_available_variations();
        if (empty($available_variations)) {
            echo '<p>Produk ini saat ini tidak tersedia dan tidak dapat dibeli.</p>';
            return;
        }
    }
    ?>
    <div class="woo-converter-wrapper">
        <label for="input_satuan" style="font-weight: bold;">Unit Satuan</label>

        <div class="woo-converter-fields">
            <div class="woo-unit-converter">
                <div class="woo-flex-row">
                    <?php
                    $enable_converter = get_post_meta($product_id, '_enable_unit_converter', true);

                    // Jika enable_unit_converter = yes → tampilkan keduanya
                    if ($enable_converter === 'yes'): ?>
                        <label style="display: flex; align-items: center; gap: 5px; margin-right: 10px;">
                            <input type="radio" name="input_unit" value="yard" id="unit_yard" <?php checked($default_unit, 'yard'); ?>>
                            Yard
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="input_unit" value="kg" id="unit_kg" <?php checked($default_unit, 'kilogram'); ?>>
                            Kilogram
                        </label>

                        <?php else:
                        // Kalau tidak, tampilkan hanya unit default
                        if ($default_unit === 'yard' || $default_unit === ''): ?>
                            <label style="display: flex; align-items: center; gap: 5px;">
                                <input type="radio" name="input_unit" value="yard" id="unit_yard" checked>
                                Yard
                            </label>
                        <?php elseif ($default_unit === 'kilogram'): ?>
                            <label style="display: flex; align-items: center; gap: 5px;">
                                <input type="radio" name="input_unit" value="kg" id="unit_kg" checked>
                                Kilogram
                            </label>
                    <?php endif;
                    endif;
                    ?>

                    <div class="woo-unit-box" style="display: flex; align-items: center;">
                        <input type="number" step="0.01" min="0.1" id="input_satuan" name="input_satuan" value="1" style="width: 100px; text-align: right;" />
                        <div id="unit_label" style="background: #1e1f37; color: #fff; padding: 8px 12px;">
                            <?php echo ucfirst($default_unit === 'kilogram' ? 'Kg' : 'Yard'); ?>
                        </div>
                    </div>
                </div>

                <div class="woo-flex-row">
                    <small id="yard-max-alert" style="display: none; color: red; font-size: 12px;">Maksimal order 60 yard.</small>
                </div>
            </div>
        </div>

        <p id="price-per-yard-display"
            data-price-yard="<?php echo esc_attr($price_per_yard); ?>"
            data-price-kg="<?php echo esc_attr($price_per_kg); ?>"
            style="margin: 5px 0; font-weight: bold;">

        </p>

    </div>

    <!-- CSS Fallback: Sembunyikan bagian total di .product-price-quantity jika JS gagal -->
    <style>
        .product-price-quantity .woocommerce-Price-amount:last-of-type {
            display: none !important;
            /* Hilangkan price total di akhir */
        }

        .product-price-quantity::after {
            content: none !important;
            /* Hilangkan × jika ada */
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const meterInput = document.querySelector('#input_satuan');
            const unitRadios = document.querySelectorAll('input[name="input_unit"]');
            const qtyInput = document.querySelector('input.qty');
            const unitLabel = document.querySelector('#unit_label');
            const priceDisplay = document.querySelector('#price-per-yard-display');
            const qtyWrapper = document.querySelector('.quantity');
            const converterFields = document.querySelector('.woo-converter-fields');
            const productForm = document.querySelector('form.variations_form');

            const MIN_YARD = 60;
            // const MAX_YARD = 60;
            const MIN_KG = 20;

            function debounce(fn, delay = 800) {
                let timeout;
                return (...args) => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => fn(...args), delay);
                };
            }

            function getSelectedUnit() {
                const selected = document.querySelector('input[name="input_unit"]:checked');
                return selected ? selected.value : 'meter';
            }

            function handleValidation() {
                const alertBox = document.getElementById('yard-max-alert');
                let value = parseFloat(meterInput.value);
                if (isNaN(value)) return;

                const unit = getSelectedUnit();
                const minValue = unit === 'yard' ? MIN_YARD : MIN_KG; // 🧠 Tambahkan ini
                alertBox.style.display = 'none';

                if (value < minValue) {
                    Swal.fire({
                        icon: 'warning',
                        title: `Minimal Order ${minValue} ${unit === 'yard' ? 'Yard' : 'Kg'}`,
                        text: `Jumlah ${unit} yang Anda masukkan kurang dari ${minValue} ${unit}.`,
                        confirmButtonColor: '#25D366',
                    });
                    alertBox.textContent = `Minimal order ${minValue} ${unit}.`;
                    alertBox.style.display = 'block';
                    meterInput.value = minValue;
                }

                updateQty();
                updateProductPriceQuantity();
                const variation = jQuery('form.variations_form').data('product_variations')
                    ?.find(v => v.variation_id === parseInt(jQuery('input[name="variation_id"]').val()));
                if (variation) updatePrice(variation);
            }

            function convertToYard(meter) {
                return meter / 0.9144;
            }

            function updateQty() {
                const length = parseFloat(meterInput.value) || 0;
                const unit = getSelectedUnit();

                if (unit === 'yard') {
                    qtyInput.value = Math.ceil(length);
                    unitLabel.textContent = 'Yard';
                } else {
                    qtyInput.value = Math.ceil(length);
                    unitLabel.textContent = 'Kg';
                }
            }

            // FUNGSI BARU: Update tampilan .product-price-quantity (mirip cart)
            function updateProductPriceQuantity() {
                let productPriceQuantity = document.querySelector('.product-price-quantity');
                if (!productPriceQuantity) return;

                const length = parseFloat(meterInput.value) || 1;
                const unit = getSelectedUnit();
                const unitLabelText = unit === 'yard' ? 'Yard' : 'Kg';

                const variation = jQuery('form.variations_form').data('product_variations')
                    ?.find(v => v.variation_id === parseInt(jQuery('input[name="variation_id"]').val()));

                let hargaPerUnit = 0;
                if (variation && variation.display_price) {
                    hargaPerUnit = variation.display_price;
                } else {
                    hargaPerUnit = parseFloat(priceDisplay.dataset[`price${unit.charAt(0).toUpperCase() + unit.slice(1)}`]) || 0;
                }

                const hargaPerUnitFormat = new Intl.NumberFormat('id-ID').format(hargaPerUnit);

                // Hapus semua elemen .woocommerce-Price-amount yang lama
                productPriceQuantity.querySelectorAll('.woocommerce-Price-amount.amount').forEach(el => el.remove());

                // Buat elemen unit price baru
                const unitPriceSpan = document.createElement('span');
                unitPriceSpan.classList.add('woocommerce-Price-amount', 'amount');
                unitPriceSpan.innerHTML = `<bdi><span class="woocommerce-Price-currencySymbol">Rp</span>${hargaPerUnitFormat}</bdi>`;

                // Update HTML
                const quantityDiv = productPriceQuantity.querySelector('.quantity');
                if (quantityDiv) {
                    quantityDiv.innerHTML = `${length} ${unitLabelText} × `;
                    quantityDiv.appendChild(unitPriceSpan);
                }
            }

            function updatePrice(variation) {
                if (!variation || !variation.display_price) return;

                const length = parseFloat(meterInput.value) || 0;
                const unit = getSelectedUnit();
                const yardValue = unit === 'meter' ? convertToYard(length) : length;
                const totalPrice = variation.display_price * yardValue;

                const unitLabelText = unit === 'yard' ? 'Yard' : 'Kg'; // <-- pindahkan ke sini

                const priceElem = document.querySelector('.woocommerce-variation-price .price');
                if (priceElem) {
                    priceElem.innerHTML = `Rp ${Math.round(totalPrice).toLocaleString('id-ID')} (${length} ${unitLabelText})`;

                    priceDisplay.textContent = 'Harga per ' + unitLabelText + ': Rp ' +
                        Math.round(totalPrice).toLocaleString('id-ID') + ` / ${unitLabelText} (Bruto)`;
                }

                // Update harga per yard jika priceDisplay ada
                if (priceDisplay) {
                    priceDisplay.textContent = `Harga per ${unitLabelText}: Rp ` +
                        Math.round(totalPrice).toLocaleString('id-ID') + ` / ${unitLabelText} (Bruto)`;
                } else {
                    const nomor_wa = '<?php echo esc_js($nomor_wa); ?>';
                    const product_title = '<?php echo esc_js($product->get_title()); ?>';
                    const message = `Halo, saya ingin menanyakan harga untuk produk: *${product_title}*.`;

                    priceDisplay.innerHTML = '<a href="https://wa.me/' + nomor_wa +
                        '?text=' + encodeURIComponent(message) +
                        '" class="button" style="background-color: #0073aa; color: white; padding: 20px 25px; border-radius: 4px; text-decoration: none;">Call Us</a>';
                }
            }

            window.updatePrice = function(variation) {
                if (!variation || !variation.display_price) return;

                const meterInput = document.querySelector('#input_satuan');
                const unit = document.querySelector('input[name="input_unit"]:checked')?.value || 'yard';
                const length = parseFloat(meterInput.value) || 1;
                const yardValue = unit === 'meter' ? (length / 0.9144) : length;
                const totalPrice = variation.display_price * yardValue;

                setTimeout(() => {
                    const priceElem = document.querySelector('.woocommerce-variation-price .price');
                    if (priceElem) {
                        priceElem.innerHTML = `Rp ${Math.round(totalPrice).toLocaleString('id-ID')} (${length} Yard)`;
                    }

                    const priceDisplay = document.querySelector('#price-per-yard-display');
                    if (priceDisplay) {
                        priceDisplay.textContent = 'Harga per Yard: Rp ' + Math.round(variation.display_price).toLocaleString('id-ID') + '- / Yard (Bruto)';
                    }
                }, 100);
            };

            // Event listeners
            // meterInput.addEventListener('input', updateQty);
            meterInput.addEventListener('blur', debounce(handleValidation, 80));

            unitRadios.forEach(radio => {
                radio.addEventListener('change', () => {
                    unitLabel.textContent = radio.value === 'yard' ? 'Yard' : 'Kg';
                    handleValidation();
                    updateProductPriceQuantity(); // Tambah ini
                });
            });

            meterInput.addEventListener('blur', function() {
                const val = parseFloat(meterInput.value);
                if (!isNaN(val)) {
                    // hanya tambahkan .00 jika angka bulat
                    meterInput.value = Number.isInteger(val) ? val.toFixed(2) : val;
                }
            });

            // handleValidation();

            // unitRadios.forEach(radio => radio.addEventListener('change', updateQty));
            // unitRadios.forEach(radio => radio.addEventListener('change', () => {
            //     updateQty();
            //     const variation = jQuery('form.variations_form').data('product_variations')
            //         ?.find(v => v.variation_id === parseInt(jQuery('input[name="variation_id"]').val()));
            //     if (variation) updatePrice(variation);
            // }));
            unitRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    unitLabel.textContent = (this.value === 'yard') ? 'Yard' : 'Kg';
                });
            });

            // Update harga saat variasi ditemukan
            jQuery('form.variations_form .reset_variations').on('click', function() {
                const priceElem = document.querySelector('.woocommerce-variation-price .price');
                if (priceElem) {
                    priceElem.innerHTML = 'Silakan pilih variasi terlebih dahulu';
                }
                priceDisplay.textContent = '';
            });

            meterInput.addEventListener('input', () => {
                const variation = jQuery('form.variations_form').data('product_variations')
                    ?.find(v => v.variation_id === parseInt(jQuery('input[name="variation_id"]').val()));
                if (variation) updatePrice(variation);

                updateProductPriceQuantity(); // Tambah ini
            });

            function updateMinValue() {
                const unit = getSelectedUnit();
                const minValue = unit === 'yard' ? MIN_YARD : MIN_KG;
                const label = unit === 'yard' ? 'Yard' : 'Kg';

                meterInput.value = minValue;
                unitLabel.textContent = label;
            }

            // Saat load pertama
            updateMinValue();

            meterInput.value = getSelectedUnit() === 'yard' ? MIN_YARD : MIN_KG;

            // Saat radio unit diganti
            document.querySelectorAll('input[name="input_unit"]').forEach((radio) => {
                radio.addEventListener('change', updateMinValue);
            });

            updateQty();

            const variation = jQuery('form.variations_form').data('product_variations')
                ?.find(v => v.variation_id === parseInt(jQuery('input[name="variation_id"]').val()));
            if (variation) updatePrice(variation);

            jQuery('form.variations_form').on('woocommerce_variation_has_changed', function() {
                const variationId = parseInt(jQuery('input[name="variation_id"]').val());
                const variations = jQuery('form.variations_form').data('product_variations');

                const selected = variations?.find(v => v.variation_id === variationId);
                if (selected) {
                    updatePrice(selected);
                }
            });
        });
    </script>
<?php
}

// Added variation data to cart item session
add_filter('woocommerce_add_cart_item_data', function ($data, $product_id, $variation_id) {
    if (!empty($_POST['input_satuan'])) {
        $satuan = floatval($_POST['input_satuan']);
        if ($satuan > 0) {
            $data['input_satuan'] = $satuan;
        }
    }

    if (!empty($_POST['input_unit'])) {
        $data['input_unit'] = sanitize_text_field($_POST['input_unit']);
    }

    // Cari attribute warna dinamis
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'attribute_pa_warna') !== false && !empty($value)) {
            $data['variation_warna'] = sanitize_text_field($value);
            break;
        }
    }

    return $data;
}, 10, 3);

add_filter('woocommerce_get_cart_item_from_session', function ($cart_item, $values) {
    if (isset($values['input_satuan'])) {
        $cart_item['input_satuan'] = $values['input_satuan'];
    }
    if (isset($values['input_unit'])) {
        $cart_item['input_unit'] = $values['input_unit'];
    }
    return $cart_item;
}, 10, 2);

add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (!empty($cart_item['input_satuan'])) {
        $unit = !empty($cart_item['input_unit']) ? ucfirst($cart_item['input_unit']) : 'Yard';
        $item_data[] = array(
            'name'  => "Jumlah ($unit)",
            'value' => wc_clean($cart_item['input_satuan']) . " " . $unit
        );
    }
    return $item_data;
}, 10, 2);

add_action('woocommerce_add_order_item_meta', function ($item_id, $values, $cart_item_key) {
    if (isset($values['input_satuan'])) {
        wc_add_order_item_meta($item_id, 'input_satuan', $values['input_satuan']);
    }
    if (isset($values['input_unit'])) {
        wc_add_order_item_meta($item_id, 'input_unit', $values['input_unit']);
    }
}, 10, 3);

// Kalkulasi ulang harga di cart berdasarkan input_satuan
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (did_action('woocommerce_before_calculate_totals') >= 2) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['input_satuan']) && $cart_item['input_satuan'] > 0) {
            $harga_awal = floatval($cart_item['data']->get_price());
            $jumlah = floatval($cart_item['input_satuan']);

            // Harga total per produk = harga dasar × input_satuan
            $harga_baru = $harga_awal * $jumlah;

            $cart_item['data']->set_price($harga_baru);
        }
    }
});

// ===============================
// 4️⃣ Update tampilan mini cart langsung
// ===============================
add_filter('woocommerce_widget_cart_item_quantity', function ($quantity_html, $cart_item, $cart_item_key) {
    if (!empty($cart_item['input_satuan']) && !empty($cart_item['input_unit'])) {
        $satuan = $cart_item['input_satuan'];
        $unit = ucfirst($cart_item['input_unit']);
        $harga_awal = floatval($cart_item['data']->get_regular_price());
        $total = $harga_awal * $satuan;
        return sprintf('<span class="quantity">%s %s × %s</span>', $satuan, $unit, wc_price($harga_awal)) .
            ' <br> <strong>Total: ' . wc_price($total) . '</strong>';
    }
    return $quantity_html;
}, 10, 3);

// ===============================
// 5️⃣ Refresh mini cart via AJAX
// ===============================
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    ob_start();
    woocommerce_mini_cart();
    $fragments['div.widget_shopping_cart_content'] = ob_get_clean();
    return $fragments;
});
// End Added variation data to cart item session

add_action('woocommerce_after_single_product', function () {
    global $product;

    // Pastikan $product valid dan unit converter aktif
    if (!is_product() || !$product || get_post_meta($product->get_id(), '_enable_unit_converter', true) !== 'yes') {
        return;
    }
?>
    <style>
        /* Overlay full screen */
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            transition: opacity 0.4s ease;
        }

        /* Animasi spinner */
        .spinner {
            border: 6px solid #f3f3f3;
            border-top: 6px solid #6a6969ff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Teks loading */
        .loading-text {
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            color: #333;
            font-weight: 500;
            text-align: center;
        }
    </style>

    <!-- HTML overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Memuat data produk, harap tunggu...</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hapus elemen quantity
            const quantityDiv = document.querySelector('.single-product .quantity');
            const cartForm = document.querySelector('form.cart');
            if (quantityDiv && cartForm) {
                console.log("Load data");
                quantityDiv.remove();
            }

            // Hilangkan overlay dengan efek fade out
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => overlay.remove(), 400);
            }
        });
    </script>
<?php
});

add_action('admin_footer', 'enqueue_image_uploader_script');
function enqueue_image_uploader_script()
{
?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.upload_image_button').click(function(e) {
                e.preventDefault();
                var button = $(this);
                var loop = button.attr('rel'); // Ambil rel untuk loop
                var uploader = wp.media({
                    title: 'Pilih Gambar',
                    button: {
                        text: 'Pilih'
                    },
                    multiple: false
                }).on('select', function() {
                    var attachment = uploader.state().get('selection').first().toJSON();
                    $('input[name="upload_image_id[' + loop + ']"]').val(attachment.id); // Update input dengan ID
                    button.find('img').attr('src', attachment.sizes.thumbnail.url); // Update gambar
                }).open();
            });
        });
    </script>
    <?php
}

add_action('woocommerce_save_product_variation', 'save_image_variation_field', 10, 2);
function save_image_variation_field($variation_id, $i)
{
    if (isset($_POST['upload_image_id'])) {
        if (isset($_POST['upload_image_id'][$i])) {
            $image_id = intval($_POST['upload_image_id'][$i]);
            update_post_meta($variation_id, 'upload_image_id', $image_id);
            error_log("Saved image ID: " . $image_id . " for variation ID: " . $variation_id);
        }
    }
}

add_filter('woocommerce_cart_item_display', function ($item_name, $cart_item, $cart_item_key) {
    if (isset($cart_item['variation_warna'])) {
        $item_name .= '<br><small>Warna: ' . esc_html($cart_item['variation_warna']) . '</small>';
    }
    return $item_name;
}, 10, 3);

add_filter('woocommerce_cart_item_name', function ($item_name, $cart_item, $cart_item_key) {
    if (isset($cart_item['variation_id'])) {
        // Ambil ID variasi dari item
        $variation_id = $cart_item['variation_id'];
        // Ambil ID gambar dari metadata
        $image_id = get_post_meta($variation_id, 'upload_image_id', true);
        // Jika ada gambar, tambahkan ke nama item
        if ($image_id) {
            $image_url = wp_get_attachment_url($image_id);
            $item_name = '<img src="' . esc_url($image_url) . '" alt="Warna" style="width: 50px; height: auto; margin-right: 5px;">' . $item_name;
        }
    }
    return $item_name;
}, 10, 3);


add_filter('woocommerce_get_price_html', 'custom_variable_price_per_yard_display', 100, 2);
function custom_variable_price_per_yard_display($price_html, $product)
{
    if (!is_product() || is_admin()) {
        return $price_html;
    }

    if (!$product->is_type('variable')) {
        return $price_html;
    }

    $product_id = $product->get_id();

    // Cek apakah konversi aktif
    if (get_post_meta($product_id, '_enable_unit_converter', true) !== 'yes') {
        return $price_html;
    }

    // Ambil semua variasi
    $available_variations = $product->get_available_variations();
    $prices = [];

    foreach ($available_variations as $variation) {
        $vid = $variation['variation_id'];
        $custom_price = get_post_meta($vid, '_price_per_yard', true);

        // Debugging
        error_log("Disini debug harga: " . $custom_price);

        if (is_numeric($custom_price) && $custom_price > 0) {
            $prices[] = floatval($custom_price);
        }
    }

    if (empty($prices)) {
        return '<p class="price">Harga tidak tersedia</p>'; // Tampilkan pesan jika tidak ada harga
    }

    $min = min($prices);
    $max = max($prices);

    if ($min == $max) {
        return '<p class="price">Harga per yard: ' . wc_price($min) . '</p>';
    } else {
        return '<p class="price">Harga per yard: ' . wc_price($min) . ' – ' . wc_price($max) . '</p>';
    }
}


// ====== TAMBAH FIELD HARGA PER YARD DI VARIASI PRODUK ======
add_action('woocommerce_variation_options_pricing', 'woo_add_price_per_yard_variation_field', 10, 3);
function woo_add_price_per_yard_variation_field($loop, $variation_data, $variation)
{
    woocommerce_wp_text_input([
        'id' => '_price_per_yard[' . $loop . ']',
        'class' => 'short',
        'label' => __('Harga per Yard', 'woocommerce'),
        'desc_tip' => 'true',
        'type' => 'number',
        'custom_attributes' => [
            'step' => '0.01',
            'min' => '0'
        ],
        'value' => get_post_meta($variation->ID, '_price_per_yard', true)
    ]);
}

add_action('woocommerce_save_product_variation', 'woo_save_price_per_yard_variation_field', 10, 2);
function woo_save_price_per_yard_variation_field($variation_id, $i)
{
    if (isset($_POST['_price_per_yard'][$i])) {
        update_post_meta($variation_id, '_price_per_yard', wc_clean($_POST['_price_per_yard'][$i]));
    }
}

add_action('woocommerce_variation_options', 'add_default_variation_radio_button', 10, 3);
function add_default_variation_radio_button($loop, $variation_data, $variation)
{
    $checked = get_post_meta($variation->ID, '_is_default_variation', true) === 'yes' ? 'checked' : '';
    echo '<p class="form-row form-row-full">
        <label>' . __('Jadikan sebagai default', 'woocommerce') . '</label><br>
        <input type="radio" name="_is_default_variation_radio" value="' . esc_attr($variation->ID) . '" ' . $checked . '> ' . __('Ya', 'woocommerce') . '
    </p>';
}

add_action('woocommerce_save_product_variation', 'save_default_variation_radio', 10, 2);
function save_default_variation_radio($variation_id, $i)
{
    $is_default = isset($_POST['_is_default_variation_radio']) && $_POST['_is_default_variation_radio'] == $variation_id ? 'yes' : 'no';

    if ($is_default === 'yes') {
        $parent_id = wp_get_post_parent_id($variation_id);
        $children = get_children(['post_parent' => $parent_id, 'post_type' => 'product_variation']);
        foreach ($children as $child) {
            update_post_meta($child->ID, '_is_default_variation', $child->ID == $variation_id ? 'yes' : 'no');
        }
    } else {
        update_post_meta($variation_id, '_is_default_variation', 'no');
    }
}

add_action('admin_footer', 'limit_default_variation_checkbox_selection');
function limit_default_variation_checkbox_selection()
{
    global $pagenow;
    if ($pagenow === 'post.php' && get_post_type() === 'product') {
    ?>
        <script>
            jQuery(document).ready(function($) {
                function enforceSingleDefault() {
                    $('input[name^="_is_default_variation"]').on('change', function() {
                        if ($(this).is(':checked')) {
                            $('input[name^="_is_default_variation"]').not(this).prop('checked', false);
                        }
                    });
                }
                enforceSingleDefault();
            });
        </script>
    <?php
    }
}

// Tambahkan log untuk memeriksa variasi yang diambil
add_filter('woocommerce_available_variation', 'woo_add_price_per_yard_to_variation_json');
function woo_add_price_per_yard_to_variation_json($variation_data)
{
    $variation_id = $variation_data['variation_id'];
    $price_per_yard = get_post_meta($variation_id, '_price_per_yard', true);
    error_log("Variation ID: $variation_id, Price per Yard: $price_per_yard"); // Debug log
    $variation_data['price_per_yard'] = $price_per_yard;
    return $variation_data;
}


add_filter('woocommerce_get_price_html', 'custom_price_per_yard_output', 10, 2);
function custom_price_per_yard_output($price, $product)
{
    if (is_product() && get_post_meta($product->get_id(), '_enable_unit_converter', true) === 'yes') {
        $custom_price = get_post_meta($product->get_id(), '_price_per_yard', true);
        if (!empty($custom_price)) {
            return wc_price($custom_price) . ' <small>/ yard</small>';
        }
    }
    return $price;
}

add_action('wp_footer', 'woo_autoselect_default_variation');
function woo_autoselect_default_variation()
{
    if (!is_product()) return;

    global $product;

    if (!$product->is_type('variable')) return;

    $variations = $product->get_available_variations();
    $attributes = $product->get_variation_attributes();

    // Cari ID variasi yang default
    foreach ($variations as $variation) {
        $variation_id = $variation['variation_id'];
        $is_default = get_post_meta($variation_id, '_is_default_variation', true);

        if ($is_default === 'yes') {
            $default_attrs = $variation['attributes']; // ex: ['attribute_pa_color' => 'blue']
            break;
        }
    }

    if (empty($default_attrs)) return;

    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($default_attrs as $name => $value): ?>
                const select = document.querySelector('[name="<?php echo esc_js($name); ?>"]');
                if (select) {
                    select.value = "<?php echo esc_js($value); ?>";
                    select.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            <?php endforeach; ?>

            // Setelah WooCommerce update DOM, panggil updatePrice
            setTimeout(() => {
                const vid = document.querySelector('input[name="variation_id"]')?.value;
                const vars = jQuery('form.variations_form').data('product_variations');
                const sel = vars?.find(v => v.variation_id == vid);
                if (sel && typeof updatePrice === 'function') updatePrice(sel);
            }, 600);

            // Pastikan harga tetap sinkron saat variasi berubah manual
            jQuery('form.variations_form').on('woocommerce_variation_has_changed', function() {
                const vid2 = jQuery('input[name="variation_id"]').val();
                const vars2 = jQuery(this).data('product_variations');
                const sel2 = vars2?.find(v => v.variation_id == vid2);
                if (sel2 && typeof updatePrice === 'function') updatePrice(sel2);
            });
        });
    </script>
    <?php
}


add_action('wp_ajax_update_cart_quantity', 'custom_update_cart_quantity');
add_action('wp_ajax_nopriv_update_cart_quantity', 'custom_update_cart_quantity'); // opsional, jika user belum login

function custom_update_cart_quantity()
{
    check_ajax_referer('update_cart_nonce'); // validasi nonce

    $new_qty = isset($_POST['input_satuan']) ? floatval($_POST['input_satuan']) : 0;
    if ($new_qty <= 0) {
        wp_send_json_error('Kuantitas tidak valid');
    }

    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        WC()->cart->set_quantity($cart_item_key, $new_qty, true); // true: trigger recalculation
        WC()->cart->cart_contents[$cart_item_key]['input_satuan'] = $new_qty;
        break; // hanya 1 item
    }

    WC()->cart->calculate_totals();

    wp_send_json_success('Berhasil update cart');
}

// Menambahkan checkbox untuk menyembunyikan harga produk
add_action('woocommerce_product_options_general_product_data', 'woo_add_hide_price_field');
function woo_add_hide_price_field()
{
    echo '<div class="options_group">';

    woocommerce_wp_checkbox([
        'id' => '_hide_product_price',
        'label' => __('Sembunyikan Harga Produk?', 'woocommerce'),
        'description' => __('Centang untuk menyembunyikan harga produk di halaman produk.', 'woocommerce'),
    ]);

    echo '</div>';
}

// Menyimpan status checkbox
add_action('woocommerce_process_product_meta', 'woo_save_hide_price_field');
function woo_save_hide_price_field($post_id)
{
    $hide_price = isset($_POST['_hide_product_price']) ? 'yes' : 'no';
    update_post_meta($post_id, '_hide_product_price', $hide_price);
}

// Menyembunyikan harga produk
add_filter('woocommerce_get_price_html', 'custom_hide_product_price', 10, 2);
function custom_hide_product_price($price, $product)
{
    if (get_post_meta($product->get_id(), '_hide_product_price', true) === 'yes') {
        return '<span class="hidden-price">Harga disembunyikan</span>';
    }
    return $price;
}

// Mengubah status produk menjadi tidak dapat dibeli jika harga disembunyikan
add_filter('woocommerce_is_purchasable', 'custom_hide_add_to_cart', 10, 2);
function custom_hide_add_to_cart($purchasable, $product)
{
    if (get_post_meta($product->get_id(), '_hide_product_price', true) === 'yes') {
        return false; // Produk tidak dapat dibeli
    }
    return $purchasable;
}


// Menambahkan tombol Call To Us
function add_call_to_us_button()
{
    global $product;

    // Ambil nomor WhatsApp dari post type whatsapp_admin
    $whatsapp_number = get_whatsapp_number();
    if ($whatsapp_number) {
        // Format nomor WhatsApp
        $formatted_number = preg_replace('/^0/', '62', $whatsapp_number);
        $message = urlencode('Saya ingin menanyakan harga untuk produk: ' . $product->get_name());
        $whatsapp_link = 'https://wa.me/' . $formatted_number . '?text=' . $message;

        echo '<a href="' . esc_url($whatsapp_link) . '" class="button call-to-us" target="_blank">Call To Us</a>';
    }
}

// Fungsi untuk mengambil nomor WhatsApp dari post type whatsapp_admin
function get_whatsapp_number()
{
    $args = [
        'post_type' => 'whatsapp_admin',
        'posts_per_page' => 1,
    ];
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            return get_post_meta(get_the_ID(), 'nomor_whatsapp_admin', true);
        }
        wp_reset_postdata();
    }
    return false; // Jika tidak ada nomor yang ditemukan
}

add_action('woocommerce_single_product_summary', 'custom_insert_call_to_us_button_php', 80);
function custom_insert_call_to_us_button_php()
{
    global $product;

    if (
        is_product() &&
        is_a($product, 'WC_Product') &&
        get_post_meta($product->get_id(), '_hide_product_price', true) === 'yes'
    ) {
        $whatsapp_number = get_whatsapp_number();
        if (!$whatsapp_number) return;

        $formatted_number = preg_replace('/^0/', '62', $whatsapp_number);
        $product_name     = $product->get_name();
        $product_sku      = $product->get_sku();
        $product_id       = $product->get_id();
        $product_stock    = $product->get_stock_quantity();
        if ($product_stock === null || $product_stock === '') {
            $product_stock = $product->is_in_stock() ? 'Tersedia' : 'Kosong';
        }
        $product_link = get_permalink($product_id);
        $site_name    = get_bloginfo('name');

        echo '
        <a href="#" id="call-to-us-btn" 
           class="button call-to-us-button" 
           data-wa="' . esc_attr($formatted_number) . '" 
           data-product="' . esc_attr($product_name) . '" 
           data-sku="' . esc_attr($product_sku) . '"
           data-stock="' . esc_attr($product_stock) . '"
           data-id="' . esc_attr($product_id) . '"
           data-link="' . esc_url($product_link) . '"
           data-site="' . esc_attr($site_name) . '"
           target="_blank">
           <i class="fab fa-whatsapp"></i>
           &nbsp;Call To Us
        </a>

        <style>
        .call-to-us-button {
            background-color: #25D366;
            color: white;
            padding: 14px 24px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
            text-align: center!important;
        }
        .call-to-us-button:hover {
            background-color: white;
            color: #25D366;
            border: 2px solid #25D366;
        }
        .call-to-us-button i {
            font-size: 18px;
        }
        </style>

        <script>
        document.addEventListener("DOMContentLoaded", function () {
            const btn = document.getElementById("call-to-us-btn");
            if (!btn) return;

            btn.addEventListener("click", function (e) {
                e.preventDefault();

                const phone        = btn.getAttribute("data-wa");
                const productName  = btn.getAttribute("data-product");
                const productSKU   = btn.getAttribute("data-sku");
                const productStock = btn.getAttribute("data-stock");
                const productID    = btn.getAttribute("data-id");
                const productLink  = btn.getAttribute("data-link");
                const siteName     = btn.getAttribute("data-site");

                // Ambil jumlah yard dari localStorage
                let yardValue = localStorage.getItem("yard_value") || "1";

                // Ambil variasi warna dari swatch <li.selected>
                let colorText = "Tanpa variasi";
                const selectedColor = document.querySelector(".st-swatch-preview li.selected span[data-name]");
                if (selectedColor) {
                    const colorName = selectedColor.getAttribute("data-name");
                    colorText = "Warna: " + colorName;
                }

                const message = 
`Halo Admin ${siteName} 👋
Saya tertarik dengan produk *${productName}*.
SKU: ${productSKU}
ID Produk: ${productID}
Stok Tersedia: ${productStock}
Link Produk: ${productLink}
Pilihan saya:
${colorText}
Jumlah: ${yardValue} yard
Mohon infonya lebih lanjut ya.`;

                const waLink = "https://wa.me/" + phone + "?text=" + encodeURIComponent(message);
                window.open(waLink, "_blank");
            });
        });
        </script>';
    }
}


add_action('wp_footer', function () {
    if (!is_product()) return;

    global $product;
    if (get_post_meta($product->get_id(), '_hide_product_price', true) === 'yes') {
    ?>
        <script>
            jQuery(function($) {
                // Sembunyikan harga dan tombol beli
                $('.price, .woocommerce-variation-price, #price-per-yard-display').hide();
                // Tampilkan input yard
                $('.woo-converter-wrapper, .woo-converter-fields').show(); // Pastikan input yard ditampilkan
                $('.single_add_to_cart_button, .button-buy-now, .beli-langsung-wa').hide();
                $('form.cart .quantity').show(); // Sembunyikan kuantitas jika diperlukan
            });
        </script>
<?php
    }
});

add_action('wp_head', function () {
    if (!is_product()) return;

    $product_id = get_queried_object_id();
    if (!$product_id) return;

    if (get_post_meta($product_id, '_hide_product_price', true) === 'yes') {
        echo '<style>
            /* Sembunyikan harga dan tombol beli */
            .price,
            .woocommerce-variation-price,
            .woocommerce-Price-amount,
            #price-per-yard-display,
            /*.woo-converter-wrapper, */
            /*.woo-converter-fields,*/
            .single_add_to_cart_button,
            .button-buy-now,
            .beli-langsung-wa,
            form.cart .quantity {
                display: none !important;
            }

            /* Tampilkan product meta dan tabs */
            .product_meta,
            .product-share,
            .woocommerce-tabs,
            .wc-tabs-wrapper,
            .woocommerce-tabs .panel,
            .woocommerce-product-details__short-description,
            #reviews,
            #comments,
            .woocommerce-Reviews {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            .product-share {
                margin-bottom: 2rem!important;
            }
            
        </style>';
    }
});

// Redirect setelah order sukses di halaman thank you, jika ada produk WA order
add_action('woocommerce_thankyou', 'redirect_to_whatsapp_after_order');
function redirect_to_whatsapp_after_order($order_id)
{
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    // Cek produk dengan ACF order_via_whatsapp = 'yes'
    $has_whatsapp_order = false;
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $whatsapp_field = get_field('order_via_whatsapp', $product_id);
        if ($whatsapp_field && in_array('yes', (array) $whatsapp_field)) {
            $has_whatsapp_order = true;
            break;
        }
    }

    if (!$has_whatsapp_order) return; // Jika tidak ada produk WA, skip

    $wa_number = get_whatsapp_number();
    if (!$wa_number) return;

    // Data pelanggan
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $customer_phone = $order->get_billing_phone();
    $customer_email = $order->get_billing_email();
    $order_total = $order->get_formatted_order_total();

    // List produk
    $items_text = "";
    foreach ($order->get_items() as $item) {
        $product_name = $item->get_name();
        $qty = $item->get_quantity();
        $items_text .= "- {$product_name} (Qty: {$qty})\n";
    }

    // Buat pesan WA
    $message = "Halo Admin,\n";
    $message .= "Ada order baru dari website:\n\n";
    $message .= "Nama: {$customer_name}\n";
    $message .= "Telepon: {$customer_phone}\n";
    $message .= "Email: {$customer_email}\n";
    $message .= "Order ID: #{$order_id}\n";
    $message .= "Total: {$order_total}\n";
    $message .= "Detail produk:\n{$items_text}\n";
    $message .= "Mohon tindak lanjut ya. Terima kasih!";

    // Redirect ke WA
    $wa_link = "https://wa.me/" . preg_replace('/^0/', '62', $wa_number) . "?text=" . rawurlencode($message);

    // Redirect hanya kalau bukan admin
    if (!is_admin()) {
        wp_safe_redirect($wa_link);
        exit;
    }
}
