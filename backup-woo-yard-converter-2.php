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
// Simpan data Unit Converter di produk
add_action('woocommerce_process_product_meta', 'woo_save_unit_converter_admin_field');
function woo_save_unit_converter_admin_field($post_id)
{
    // ✅ Checkbox: pakai standar WooCommerce (yes / no)
    $enable_unit_converter = isset($_POST['_enable_unit_converter']) ? 'yes' : 'no';
    update_post_meta($post_id, '_enable_unit_converter', $enable_unit_converter);

    // ✅ Simpan pilihan satuan utama
    if (isset($_POST['_unit_type'])) {
        update_post_meta($post_id, '_unit_type', sanitize_text_field($_POST['_unit_type']));
    }

    // ✅ Simpan harga per Yard
    if (isset($_POST['_price_per_yard'])) {
        update_post_meta($post_id, '_price_per_yard', wc_format_decimal($_POST['_price_per_yard']));
    }

    // ✅ Simpan harga per Kilogram
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
        $enable_converter = get_post_meta($product_id, '_enable_unit_converter', true);
        if ($product_id && $enable_converter !== 'yes') {
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
    $enable_converter = get_post_meta($product_id, '_enable_unit_converter', true);
    if (!$product_id || $enable_converter === '') return;

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
    $enable_converter = get_post_meta($product_id, '_enable_unit_converter', true);
    if ($enable_converter === '') {
        return;
    }

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
    $enable_converter = get_post_meta($product_id, '_enable_unit_converter', true);
    if (! $product_id || $enable_converter === '') {
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

        .woo-flex-row label {
            display: inline-flex !important;
            align-items: center !important;
            gap: 5px !important;
            margin-right: 10px !important;
            margin-bottom: 0 !important;
            vertical-align: middle;
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
    $enable_converter = get_post_meta($product_id, '_enable_unit_converter', true);

    if (! $product_id || $enable_converter === '') {
        return;
    }

    $default_unit = get_post_meta($product_id, '_unit_type', true) ?: 'yard';

    if (empty($default_unit)) {
        $default_unit = 'yard';
    }

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
                        if ($default_unit === 'yard'): ?>
                            <label style="display:inline-flex; align-items:center; gap:5px; margin-right:10px; margin-bottom:0;">
                                <input type="radio" name="input_unit" value="yard" id="unit_yard" <?php checked($default_unit, 'yard'); ?>>
                                Yard
                            </label>
                        <?php elseif ($default_unit === 'kilogram'): ?>
                            <label style="display:inline-flex; align-items:center; gap:5px; margin-right:10px; margin-bottom:0;">
                                <input type="radio" name="input_unit" value="kg" id="unit_kg" <?php checked($default_unit, 'kilogram'); ?>>
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
                const addToCartBtn = document.querySelector('.single_add_to_cart_button');
                let value = parseFloat(meterInput.value);
                if (isNaN(value)) {
                    addToCartBtn.disabled = true; // kalau bukan angka, disable
                    return;
                }

                const unit = getSelectedUnit();
                const minValue = unit === 'yard' ? MIN_YARD : MIN_KG;
                alertBox.style.display = 'none';
                addToCartBtn.disabled = false; // reset dulu (aktifkan)
                addToCartBtn.style.opacity = '1'; // reset visual normal
                let compareValue = value;
                if (unit === 'yard') {
                    // Kalau input asalnya meter, konversi ke yard
                    compareValue = value / 0.9144;
                }

                if (compareValue + 0.001 < minValue) {
                    Swal.fire({
                        icon: 'warning',
                        title: `Minimal Order ${minValue} ${unit === 'yard' ? 'Yard' : 'Kg'}`,
                        text: `Jumlah ${unit} yang Anda masukkan kurang dari ${minValue} ${unit}.`,
                        confirmButtonColor: '#25D366',
                    });
                    alertBox.textContent = `Minimal order ${minValue} ${unit}.`;
                    alertBox.style.display = 'block';
                    meterInput.value = minValue;

                    // 🔴 Disable tombol Add To Cart
                    addToCartBtn.disabled = true;
                    addToCartBtn.style.opacity = '0.5'; // tampilkan efek “dimmed”
                } else {
                    // ✅ Valid — aktifkan tombol
                    addToCartBtn.disabled = false;
                    addToCartBtn.style.opacity = '1';
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

                    priceDisplay.textContent = 'Total Harga ' + variation.display_price + 'x' + yardValue + `(${unitLabelText})` + ' = Rp ' +
                        Math.round(totalPrice).toLocaleString('id-ID');
                }

                // Update harga per yard jika priceDisplay ada
                if (priceDisplay) {
                    priceDisplay.textContent = `Total Harga ${variation.display_price} x ${yardValue} (${unitLabelText}) =  Rp ` +
                        Math.round(totalPrice).toLocaleString('id-ID');
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
                        priceDisplay.textContent = 'Subtotal : Rp ' + Math.round(variation.display_price).toLocaleString('id-ID') + '- / Yard (Bruto)';
                    }
                }, 100);
            };

            // Event listeners
            // meterInput.addEventListener('input', updateQty);
            meterInput.addEventListener('input', debounce(handleValidation, 300));
            // meterInput.addEventListener('blur', handleValidation);

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
// ===============================
// 2️⃣ Simpan data unit + jumlah ke cart
// ===============================
add_filter('woocommerce_add_cart_item_data', function ($data, $product_id, $variation_id) {
    if (!empty($_POST['input_satuan'])) {
        $data['input_satuan'] = floatval($_POST['input_satuan']);
    }
    if (!empty($_POST['input_unit'])) {
        $data['input_unit'] = sanitize_text_field($_POST['input_unit']);
    }
    return $data;
}, 10, 3);

add_filter('woocommerce_get_cart_item_from_session', function ($cart_item, $values) {
    if (isset($values['input_satuan'])) $cart_item['input_satuan'] = $values['input_satuan'];
    if (isset($values['input_unit'])) $cart_item['input_unit'] = $values['input_unit'];
    return $cart_item;
}, 10, 2);
// ===============================
// ✅ CSS inline untuk hilangkan efek <br> dan sejajarkan jumlah + harga
// ===============================
add_action('wp_head', function () {
    echo '<style>
        /* Hilangkan <br> tambahan di variation detail checkout */
        .woocommerce-checkout-review-order .variation p br,
        .woocommerce-order-details .variation p br,
        .woocommerce table.shop_table .variation p br {
            display: none !important;
        }

        /* Sejajarkan teks dan harga */
        .woocommerce-checkout-review-order .variation p,
        .woocommerce-order-details .variation p,
        .custom-flex-item {
            display: inline-flex !important;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 400;
            color: #444;
            margin: 0;
        }
    </style>';
});

// ===============================
// ✅ Tampilkan jumlah + base price sejajar di detail checkout
// ===============================
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (!empty($cart_item['input_satuan'])) {
        $unit_value = floatval($cart_item['input_satuan']);
        $unit_label = !empty($cart_item['input_unit']) ? ucfirst($cart_item['input_unit']) : 'Yard';
        $base_price = floatval($cart_item['data']->get_regular_price());

        // Output clean tanpa inline style
        $custom_value = sprintf(
            '<span class="custom-flex-item">
                <span>%s %s</span>
                <span style="opacity: 0.6;">×</span>
                <span>%s</span>
            </span>',
            wc_clean($unit_value),
            esc_html($unit_label),
            wc_price($base_price)
        );

        $item_data[] = array(
            'name'    => "Jumlah ($unit_label)",
            'display' => $custom_value,
        );
    }
    return $item_data;
}, 10, 2);

// ===============================
// ✅ Bersihkan <br> otomatis (backup jika WooCommerce tetap inject <br>)
// ===============================
add_filter('woocommerce_display_item_meta', function ($html, $item, $args) {
    return str_replace(array('<br>', '<br/>', '<br />'), '', $html);
}, 10, 3);

// ===============================
// 4️⃣ Update tampilan mini cart langsung
// ===============================
add_filter('woocommerce_widget_cart_item_quantity', function ($quantity_html, $cart_item, $cart_item_key) {
    if (!empty($cart_item['input_satuan']) && !empty($cart_item['input_unit'])) {
        $satuan     = $cart_item['input_satuan'];
        $unit       = ucfirst($cart_item['input_unit']);
        $harga_awal = floatval($cart_item['data']->get_regular_price());
        $total      = $harga_awal * $satuan;
        // return sprintf('<strong>Total: ' . wc_price($total) . '</strong>');
        return '';
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

// Override default cart quantity input dengan input_satuan
add_filter('woocommerce_cart_item_quantity', function ($product_quantity, $cart_item, $cart_item_key) {
    if (!empty($cart_item['input_satuan'])) {
        $satuan = $cart_item['input_satuan'];
        $unit   = !empty($cart_item['input_unit']) ? ucfirst($cart_item['input_unit']) : 'Yard';
        $name   = "cart[{$cart_item_key}][qty]";
        $id     = "quantity_{$cart_item_key}";
        $product_quantity = sprintf(
            '<input type="number" id="%s" class="input-text qty text" step="1" min="0" name="%s" value="%s" title="%s" size="4" inputmode="numeric" />',
            esc_attr($id),
            esc_attr($name),
            esc_attr($satuan),
            esc_attr__("Qty", "woocommerce")
        );
    }
    return $product_quantity;
}, 10, 3);

// ===============================
// 3️⃣ Override tampilan Quantity di halaman Cart
// ===============================
add_filter('woocommerce_cart_item_quantity', function ($product_quantity, $cart_item_key, $cart_item) {
    if (!empty($cart_item['input_satuan'])) {
        $unit_value = floatval($cart_item['input_satuan']);
        $unit_label = !empty($cart_item['input_unit']) ? ucfirst($cart_item['input_unit']) : '';
        $product_quantity = sprintf(
            '<span class="custom-cart-qty">%s %s</span>',
            wc_clean($unit_value),
            esc_html($unit_label)
        );
    }
    return $product_quantity;
}, 20, 3);

// ===============================
// 4️⃣ Override subtotal di tabel Cart
// ===============================
add_filter('woocommerce_cart_item_subtotal', function ($subtotal, $cart_item, $cart_item_key) {
    if (!empty($cart_item['input_satuan'])) {
        $unit_value = floatval($cart_item['input_satuan']);
        $price      = $cart_item['data']->get_price();
        $total      = $price * $unit_value;
        $subtotal   = wc_price($total);
    }
    return $subtotal;
}, 20, 3);

// ===============================
// 5️⃣ Kalkulasi ulang harga di cart berdasarkan input_satuan
// ===============================
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (did_action('woocommerce_before_calculate_totals') >= 2) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (!empty($cart_item['input_satuan'])) {
            $harga_awal = floatval($cart_item['data']->get_regular_price());
            $jumlah     = floatval($cart_item['input_satuan']);
            $harga_baru = $harga_awal * $jumlah;

            // Set harga produk baru
            $cart_item['data']->set_price($harga_baru);

            // ✅ Update juga line_total agar checkout membaca nilai baru
            $cart->cart_contents[$cart_item_key]['line_total']    = $harga_baru;
            $cart->cart_contents[$cart_item_key]['line_subtotal'] = $harga_baru;

            // (opsional) tambahkan log untuk verifikasi
            error_log("UPDATE checkout item {$cart_item_key} => " . $harga_baru);
        }
    }
});

// ===============================
// ✅ Checkout: tampilkan hanya jumlah (tanpa × harga) dan biarkan subtotal WooCommerce menampilkan total yang benar
// ===============================
add_filter('woocommerce_checkout_cart_item_quantity', function ($quantity_html, $cart_item, $cart_item_key) {
    if (!empty($cart_item['input_satuan'])) {
        $unit_value = floatval($cart_item['input_satuan']);
        $unit_label = !empty($cart_item['input_unit']) ? ucfirst($cart_item['input_unit']) : '';
        $base_price = floatval($cart_item['data']->get_regular_price());

        // Hanya tampilkan (tidak merubah perhitungan)
        $quantity_html = sprintf(
            '<div class="custom-checkout-qty">%s %s × %s</div>',
            esc_html($unit_value),
            esc_html($unit_label),
            wc_price($base_price)
        );
    }
    return $quantity_html;
}, 20, 3);

add_action('wp_head', function () {
    if (is_checkout()) {
        echo '<style>
            /* Sembunyikan wrapper × harga di checkout */
            .woocommerce-checkout .product-price-quantity {
                display: none !important;
            }

            .woocommerce-checkout .product-subtotal {
                display: none !important;
            }
        </style>';
    }
});

// ✅ Pastikan subtotal checkout tidak dikalikan ulang
add_filter('woocommerce_cart_item_subtotal', function ($subtotal, $cart_item, $cart_item_key) {
    if (!empty($cart_item['input_satuan'])) {
        error_log('===== CART ITEM (CHECKOUT + CART) =====');
        error_log(print_r($cart_item, true));

        $base_price = floatval($cart_item['data']->get_regular_price());
        $unit_value = floatval($cart_item['input_satuan']);
        $subtotal   = wc_price($base_price * $unit_value);
    }
    return $subtotal;
}, 20, 3);

// ===============================
// ✅ END
// ===============================


add_action('woocommerce_after_single_product', function () {
    global $product;

    $product_id = $product->get_id();
    $enable_converter = get_post_meta($product_id, '_enable_unit_converter', true);
    // Pastikan $product valid dan unit converter aktif
    if ($enable_converter === '') {
        return;
    }
?>
    <style>
        /* Overlay full screen */
        /* #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            transition: opacity 0.4s ease;
        } */

        /* Animasi spinner */
        /* .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #6a6969ff;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        } */

        /* @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        } */

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
    <!-- <div id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Memuat data produk, harap tunggu...</div>
    </div> -->

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
    $enable_converter = get_post_meta($product_id, '_enable_unit_converter', true);
    // Cek apakah konversi aktif
    if ($enable_converter !== 'yes') {
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
                // $('.price, .woocommerce-variation-price, #price-per-yard-display').hide();
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

// Redirect ke WhatsApp setelah order sukses (versi lengkap + open tab baru)
// Simpan custom field dari cart ke order
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    $fields = ['input_satuan', 'input_unit', 'warna', 'gambar'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $cart_item_data[$field] = sanitize_text_field($_POST[$field]);
        }
    }
    return $cart_item_data;
}, 10, 2);

// Tambahkan meta ke order item
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    $fields = ['input_satuan', 'input_unit', 'warna', 'gambar'];
    foreach ($fields as $field) {
        if (!empty($values[$field])) {
            $item->add_meta_data($field, $values[$field]);
        }
    }
}, 10, 4);

// Format quantity di halaman order
add_filter('woocommerce_order_item_quantity_html', function ($qty_display, $item) {
    if (is_admin() || defined('DOING_AJAX')) return $qty_display;

    $input_satuan = $item->get_meta('input_satuan');
    $input_unit   = $item->get_meta('input_unit');

    if ($input_satuan && $input_unit) {
        return sprintf(
            '<span class="custom-order-qty">× %s %s</span>',
            esc_html($input_satuan),
            esc_html(ucfirst($input_unit))
        );
    }
    return $qty_display;
}, 10, 2);

// Redirect ke WhatsApp setelah order sukses
add_action('woocommerce_thankyou', 'redirect_to_whatsapp_after_order_complete', 10, 1);
function redirect_to_whatsapp_after_order_complete($order_id)
{
    if (!$order_id) return;
    $order = wc_get_order($order_id);
    if (!$order) return;

    $wa_number = get_whatsapp_number();
    if (!$wa_number) return;
    $wa_number = preg_replace('/^0/', '62', $wa_number);

    // Data customer
    $customer_name    = trim($order->get_formatted_billing_full_name());
    $customer_phone   = $order->get_billing_phone();
    $customer_email   = $order->get_billing_email();

    // Alamat -> ubah <br> ke newline
    $customer_address = wp_strip_all_tags($order->get_formatted_billing_address());
    $customer_address = str_replace(['<br />', '<br>', '<br/>'], "\n", $customer_address);
    $customer_address = trim($customer_address);

    $order_total = wp_strip_all_tags($order->get_formatted_order_total());

    // Produk
    $items_text = '';
    foreach ($order->get_items() as $item) {
        $product_name = $item->get_name();
        $product      = $item->get_product();

        $input_satuan = $item->get_meta('input_satuan');
        $input_unit   = $item->get_meta('input_unit');
        $warna        = $item->get_meta('warna');
        $gambar_url   = $item->get_meta('gambar');

        $unit_label  = $input_unit ? ucfirst($input_unit) : 'Unit';
        $unit_value  = $input_satuan ? floatval($input_satuan) : $item->get_quantity();
        $base_price  = $product ? floatval($product->get_regular_price()) : 0;
        $total_price = $base_price * $unit_value;

        $base_price_formatted  = 'Rp' . number_format($base_price, 0, ',', '.');
        $total_price_formatted = 'Rp' . number_format($total_price, 0, ',', '.');

        $items_text .= "• *{$product_name}* (x{$unit_value} {$unit_label})\n";
        if ($warna)      $items_text .= "  Warna: {$warna}\n";
        if ($gambar_url) $items_text .= "  Gambar: {$gambar_url}\n";
        $items_text .= "  Harga: {$base_price_formatted}\n";
        $items_text .= "  Total: {$total_price_formatted}\n\n";
    }

    // Susun pesan (tanpa convert ulang encoding)
    $message  = "🛍️ *Pesanan Baru dari DamaraKain.com*\n\n";
    $message .= "📤 *Data Pemesan*\n";
    $message .= "Nama: {$customer_name}\n";
    $message .= "Telepon: {$customer_phone}\n";
    $message .= "Email: {$customer_email}\n\n";
    $message .= "📦 *Alamat Pengiriman*\n{$customer_address}\n\n";
    $message .= "🧾 *Detail Pesanan (#{$order_id})*\n{$items_text}";
    $message .= "💰 *Total Order:* {$order_total}\n\n";
    $message .= "Mohon segera ditindaklanjuti ya 🙏";

    // Pastikan string bersih tanpa karakter kontrol
    $message = preg_replace('/[^\P{C}\n]+/u', '', $message);

    // Encode untuk URL WhatsApp
    $encoded_message = rawurlencode($message);

    // Redirect otomatis ke WhatsApp
    if (!is_admin()) {
        echo "<script>
            setTimeout(function(){
                // window.open('https://wa.me/{$wa_number}?text={$encoded_message}', '_blank');
                //  window.location.replace('https://wa.me/{$wa_number}?text={$encoded_message}');
                window.location.href = 'https://wa.me/{$wa_number}?text={$encoded_message}';
            }, 1500);
        </script>";
    }
}
