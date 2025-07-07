add_action('wp_footer', function () {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll('input[type="radio"][name="unit"]').forEach(radio => {
            console.log(`Radio: ${radio.value} | checked: ${radio.checked}`);
        });
    });
    </script>
    <?php
});


function wpstrip($text) {
    return trim(strip_tags(html_entity_decode($text)));
}

function format_nomor_whatsapp($nomor) {
    $nomor = preg_replace('/[^0-9]/', '', $nomor); // hapus karakter non-angka

    if (strpos($nomor, '0') === 0) {
        // Ganti 0 di awal dengan 62
        $nomor = '62' . substr($nomor, 1);
    }

    return $nomor;
}

add_action('woocommerce_thankyou', 'kirim_order_ke_whatsapp', 10, 1);
function kirim_order_ke_whatsapp($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);

    // Ambil nomor WA dari CPT
    $args = [
        'post_type'      => 'whatsapp_admin',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];
    $posts = get_posts($args);
    if (!empty($posts)) {
        $nomor_mentah = get_field('nomor_whatsapp_admin', $posts[0]->ID);
        $nomor_wa = format_nomor_whatsapp($nomor_mentah);
    } else {
        $nomor_wa = '6281234567890';
    }

    if (!$nomor_wa) return;

    // Ambil data order (bersihkan HTML)
    $nama    = wpstrip($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
    $email   = wpstrip($order->get_billing_email());
    $telepon = wpstrip($order->get_billing_phone());
    $alamat  = wpstrip($order->get_formatted_billing_address());
    $total   = wpstrip($order->get_formatted_order_total());

    // Produk
    $items = $order->get_items();
    $produk_list = '';
    foreach ($items as $item) {
		$product_name   = wpstrip($item->get_name());
		$quantity       = $item->get_quantity();
		$variation_id   = $item->get_variation_id();
		$variation_warna = isset($item['variation_warna']) ? wpstrip($item['variation_warna']) : '-';

		// Ambil gambar dari variation ID (jika ada)
		$image_url = '';
		if ($variation_id) {
			$image_id = get_post_meta($variation_id, 'upload_image_id', true);
			if ($image_id) {
				$image_url = wp_get_attachment_url($image_id);
			}
		}

		$produk_list .= "- {$product_name} (x{$quantity})";
		$produk_list .= " - Warna: {$variation_warna}";
		if ($image_url) {
			$produk_list .= " - Gambar: {$image_url}";
		}
		$produk_list .= "\n";
	}


    // Format pesan
    $pesan = "📦 *Pesanan Baru #{$order_id}*\n\n"
        . "*Nama:* {$nama}\n"
        . "*Email:* {$email}\n"
        . "*Telepon:* {$telepon}\n"
        . "*Alamat:* {$alamat}\n"
        . "*Total:* {$total}\n"
        . "*Produk:*\n{$produk_list}";

    $pesan_encoded = urlencode($pesan);
    $link_wa = "https://wa.me/{$nomor_wa}?text={$pesan_encoded}";

    // Auto redirect ke WA
    echo "<script>
        window.onload = function() {
            window.open('{$link_wa}', '_blank');
        };
    </script>";
}