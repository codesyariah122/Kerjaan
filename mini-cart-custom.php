<?php
if (! defined('ABSPATH')) exit;
do_action('woocommerce_before_mini_cart');

$cart_items = WC()->cart->get_cart();
$first_cart_item = reset($cart_items);
$first_product = $first_cart_item['data'];
$product_id = $first_product->is_type('variation') ? $first_product->get_parent_id() : $first_product->get_id();

$enable_converter = get_post_meta($product_id, '_enable_unit_converter', true);
$enable_converter = ($enable_converter === 'yes') ? 'yes' : 'no';

$unit_label = ($enable_converter === 'yes') ? 'Yard' : 'Kg';
$stock_quantity = ($first_product->get_manage_stock()) ? $first_product->get_stock_quantity() : null;

$input_satuan = isset($first_cart_item['input_satuan']) ? floatval($first_cart_item['input_satuan']) : $first_cart_item['quantity'];
$unit_price = $first_product->get_price();
$total_price = $unit_price * $input_satuan;

// WhatsApp Admin
$nomor_wa = '';
$whatsapp_query = new WP_Query([
    'post_type' => 'whatsapp_admin',
    'posts_per_page' => 1
]);
if ($whatsapp_query->have_posts()) {
    $whatsapp_query->the_post();
    $raw_wa = get_field('nomor_whatsapp_admin');
    $nomor_wa = preg_replace('/[^0-9]/', '', $raw_wa);
    if (strpos($nomor_wa, '0') === 0) $nomor_wa = '62' . substr($nomor_wa, 1);
}
wp_reset_postdata();
?>
<style>
    .custom-mini-cart-popup {
        position: fixed;
        top: 0;
        right: 0;
        width: 400px;
        height: 100%;
        background: #fff;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.2);
        padding: 20px;
        z-index: 9999;
        overflow-y: auto;
        display: block;
        font-family: 'Segoe UI', sans-serif;
    }

    .mini-cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .label {
        font-weight: 600;
    }

    .satuan-section {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;
    }

    .satuan-header {
        display: flex;
        justify-content: flex-start;
        width: 100%;
        align-items: center;
        margin-bottom: 5px;
        gap: 117px;
    }

    .stock-and-input {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        gap: 10px;
        margin-top: .8rem;
    }

    .stock {
        color: #e53935;
        font-weight: bold;
        font-size: 14px;
        white-space: nowrap;
    }

    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .radio-option input[type="radio"] {
        appearance: none;
        width: 18px;
        height: 18px;
        border: 2px solid #888;
        border-radius: 50%;
        display: inline-block;
        position: relative;
        cursor: pointer;
        z-index: 1;
    }

    .radio-option input[type="radio"]:checked::before {
        content: '';
        display: block;
        width: 10px !important;
        height: 10px !important;
        background-color: #0f172a !important;
        border-radius: 50% !important;
        position: absolute !important;
        top: 3px !important;
        left: 3px !important;
    }

    .quantity-input-wrapper {
        display: flex;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        overflow: hidden;
        width: 200px;
        background-color: white;
    }

    .quantity-input-wrapper input[type="number"] {
        border: none;
        padding: 10px;
        font-size: 18px;
        width: 100%;
        box-sizing: border-box;
        outline: none;
        background: transparent;
    }

    .unit-label {
        background-color: #1e293b;
        color: white;
        padding: 10px;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 50px;
    }

    .price {
        font-weight: bold;
        color: #1e293b;
        margin-top: 5px;
    }

    .checkout-button {
        display: block;
        width: 100%;
        text-align: center;
        background-color: #222;
        color: #fff;
        padding: 15px;
        border-radius: 5px;
        text-transform: uppercase;
        font-weight: bold;
        margin-top: 20px;
    }

    .checkout-button:hover {
        color: #fff;
    }

    .info-text {
        font-size: 12px;
        color: #777;
        margin-bottom: 20px;
    }

    .custom-mini-cart-popup {
        display: flex;
        flex-direction: column;
    }

    .mini-cart-footer {
        margin-top: auto;
    }
</style>

<div class="custom-mini-cart-popup">
    <div class="mini-cart-header">
        <h3><?php echo esc_html($first_product->get_name()); ?></h3>
    </div>

    <p class="info-text">Stok yang ditampilkan dapat berubah sewaktu-waktu</p>

    <div class="mini-cart-item">
        <div class="satuan-section">
            <div class="satuan-header">
                <div class="label">Satuan</div>
                <div class="radio-option">
                    <label>
                        <input type="radio" name="unit" value="yard" <?php checked($enable_converter, 'yes'); ?>> Yard
                    </label>
                </div>
            </div>

            <div class="stock-and-input">
                <p class="stock">Stok (<?php echo esc_html($unit_label); ?>): <span class="stock-value"><?php echo esc_html($stock_quantity); ?></span> <?php echo esc_html($unit_label); ?></p>
                <div class="quantity-input-wrapper">
                    <input type="number" id="input-satuan" value="<?php echo esc_attr($input_satuan); ?>" min="0">
                    <span class="unit-label"><?php echo esc_html($unit_label); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="mini-cart-footer">
        <p class="price" id="total-price">Total Harga: Rp <?php echo number_format($total_price, 0, ',', '.'); ?> / <?php echo esc_html($unit_label); ?></p>

        <?php
        if (isset($first_cart_item['variation_id'])) {
            $variation_id = $first_cart_item['variation_id'];
            $image_id = get_post_meta($variation_id, 'upload_image_id', true);

            if ($image_id) {
                $image_url = wp_get_attachment_url($image_id);
                echo '<img src="' . esc_url($image_url) . '" alt="Warna" style="width: 50px; height: auto;">';
            }
            echo '<p class="price">Warna: ' . esc_html($first_cart_item['variation_warna']) . '</p>';
        }

        ?>

        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="checkout-button"><i class="fa-solid fa-bag-shopping"></i> BELI</a>


        <?php if ($nomor_wa && $first_product):
            $site_name      = get_bloginfo('name');
            $product_title  = $first_product->get_name();
            $product_id     = $first_product->get_id();
            $product_url    = get_permalink($product_id);

            $harga_format        = 'Rp ' . number_format($unit_price, 0, ',', '.');
            $total_bayar_format  = 'Rp ' . number_format($total_price, 0, ',', '.');

            // Coba ambil variation ID
            $variation_id = isset($first_cart_item['variation_id']) ? $first_cart_item['variation_id'] : 0;

            // Ambil warna dari cart item (yang disimpan di hook add_cart_item_data)
            $variation_warna = isset($first_cart_item['variation_warna']) ? $first_cart_item['variation_warna'] : '';

            if (!$variation_warna && $first_product->is_type('variation')) {
                $attributes = $first_product->get_attributes();
                foreach ($attributes as $key => $value) {
                    if (strpos($key, 'warna') !== false) {
                        $variation_warna = $value;
                        break;
                    }
                }
            }

            if ($variation_warna !== '') {
                $variation_warna = ucwords(str_replace('-', ' ', $variation_warna));
            } else {
                $variation_warna = '-';
            }


            // Ambil gambar variasi (jika ada)
            $image_url = '';
            if ($variation_id) {
                $image_id = get_post_meta($variation_id, 'upload_image_id', true);
                if ($image_id) {
                    $image_url = wp_get_attachment_url($image_id);
                }
            }

            // Format pesan
            $pesan_text = "Halo Admin {$site_name}, saya tertarik untuk membeli produk berikut:\n\n"
                . "🧵 *Nama Produk:* {$product_title}" . ($variation_warna !== '-' ? " - {$variation_warna}" : "") . "\n"
                . "📦 *Jumlah:* {$input_satuan} {$unit_label}\n"
                . "💰 *Harga per {$unit_label}:* {$harga_format}\n"
                . "🧾 *Total Bayar:* {$total_bayar_format}\n";

            if ($image_url) {
                $pesan_text .= "🖼️ *Gambar:* {$image_url}\n";
            }

            $pesan_text .= "🔗 *Link Produk:* {$product_url}\n\n"
                . "Mohon konfirmasinya ya, terima kasih 🙏";

            $wa_link = "https://wa.me/{$nomor_wa}?text=" . urlencode($pesan_text);
        ?>
            <a href="<?php echo htmlspecialchars($wa_link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="checkout-button" style="margin-top: 10px; background-color: #25D366;">
                <i class="fab fa-whatsapp" style="font-size: 18px; color: white;"></i> Beli via WhatsApp
            </a>
        <?php endif; ?>

    </div>
</div>

<script type="text/javascript">
    function formatRupiah(angka) {
        return "Rp " + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    document.addEventListener("DOMContentLoaded", function() {
        const quantityInput = document.getElementById('input-satuan');
        const totalPriceEl = document.getElementById('total-price');
        const unitPrice = <?php echo json_encode($unit_price); ?>;
        const unitLabel = <?php echo json_encode($unit_label); ?>;

        function updateTotal() {
            const qty = parseFloat(quantityInput.value);
            const total = !isNaN(qty) ? qty * unitPrice : 0;
            totalPriceEl.textContent = `Total Harga: ${formatRupiah(total)} / ${unitLabel}`;
        }

        quantityInput.addEventListener('input', updateTotal);
        updateTotal();

    });
</script>
<?php do_action('woocommerce_after_mini_cart'); ?>