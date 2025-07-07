<?php
function tampilkan_produk_cards()
{
    ob_start();
?>
    <style>
        .produk-cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: center;
        }

        .produk-card {
            flex: 0 0 calc(25% - 20px);
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            background-image: url('https://www.transparenttextures.com/patterns/arches.png');
            /* Pattern transparan */
            background-blend-mode: overlay;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.35s ease;
            display: flex;
            flex-direction: column;
            position: relative;
            opacity: 0;
            transform: translateY(30px);
        }

        .produk-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.15);
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }

        .produk-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
        }

        .fade-in {
            animation: fadeInUp 0.5s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .produk-image {
            height: 200px;
            background-size: cover;
            background-position: center;
        }

        .produk-info {
            display: flex;
            flex-direction: column;
            flex: 1;
            align-items: center;
            /* Tambahkan ini agar anaknya di tengah horizontal */
            text-align: center;
        }

        .produk-info h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #333;
            text-transform: uppercase;
            font-weight: 600;
        }

        .produk-capacity {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .produk-harga-tags {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
            width: 90%;
        }

        .tag {
            background: #f3f3f3;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #444;
        }

        .produk-booking-btn {
            background: linear-gradient(to right, #25D366, #128C7E);
            color: white;
            padding: 12px 18px;
            border-radius: 12px;
            text-align: center;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            text-decoration: none;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            justify-content: center;
            margin-top: auto;
            margin-bottom: 1rem;
            width: 85%;
        }

        .produk-booking-btn i {
            font-size: 18px;
        }

        .produk-booking-btn:hover {
            background: linear-gradient(to right, #1DA955, #0d6b5e);
            transform: scale(1.05);
            color: #fffce0;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .btn-load-more {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px 25px;
            margin-top: 1rem;
            background: linear-gradient(135deg, rgb(0, 115, 177), rgb(0, 62, 124));
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 6px 15px rgba(0, 62, 124, 0.5);
            transition: all 0.35s ease;
            user-select: none;
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
            text-align: center;
        }

        .btn-load-more i {
            font-size: 18px;
            transition: transform 0.5s ease;
        }

        .btn-load-more:hover {
            background: linear-gradient(135deg, rgb(0, 62, 124), rgb(0, 115, 177));
            box-shadow: 0 8px 20px rgba(0, 115, 177, 0.7);
            transform: translateY(-3px);
        }

        .btn-load-more:hover i {
            transform: rotate(360deg);
        }

        .tag.ai-group {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-align: left;
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .tag.ai-group .ai-title {
            text-align: center;
            font-weight: 700;
            color: #222;
            margin-bottom: 4px;
        }

        .tag.ai-group .ai-item {
            display: flex;
            justify-content: space-between;
            color: #444;
        }

        .tag.ai-group .harga {
            color: #007bff;
            font-weight: 700;
        }

        .produk-services {
            background: #eef6ff;
            padding: 10px;
            border-radius: 10px;
            font-size: 13px;
            color: #004085;
            margin-bottom: 1rem;
        }

        .badge-call {
            display: inline-block;
            background-color: #ffc107;
            color: #212529;
            padding: 6px 8px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            min-width: 100px;
            /* atau width: 100px; jika ingin fix */
            text-align: center;
        }

        @media (max-width: 1024px) {
            .produk-card {
                flex: 0 0 calc(50% - 20px);
            }
        }

        @media (max-width: 600px) {
            .produk-card {
                flex: 0 0 100%;
            }
        }
    </style>

    <div class="produk-cards-container" id="produkCardsContainer"></div>
    <div style="text-align:center; margin-top:20px;">
        <button id="loadMoreBtn" class="btn-load-more">
            <i class="fas fa-sync-alt"></i> Load More
        </button>
    </div>

    <script>
        let produkOffset = 0;
        const produkPerPage = 8;

        function loadProdukCards() {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=load_produk_cards&offset=' + produkOffset + '&limit=' + produkPerPage)
                .then(res => res.text())
                .then(data => {
                    document.getElementById('produkCardsContainer').insertAdjacentHTML('beforeend', data);
                    produkOffset += produkPerPage;

                    if (data.trim() === '') {
                        document.getElementById('loadMoreBtn').style.display = 'none';
                    }
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadProdukCards();
            document.getElementById('loadMoreBtn').addEventListener('click', loadProdukCards);
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('produk_cards', 'tampilkan_produk_cards');

// Handler Ajax
function load_produk_cards_callback()
{
    $offset = intval($_GET['offset'] ?? 0);
    $limit = intval($_GET['limit'] ?? 8);

    $produk_args = [
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'ASC',
    ];
    $produk_query = new WP_Query($produk_args);

    if ($produk_query->have_posts()) {
        while ($produk_query->have_posts()) {
            $produk_query->the_post();
            $post_id = get_the_ID();
            $title = get_the_title();
            $capacity = get_post_meta($post_id, 'capacity', true);
            $price_lokal = get_post_meta($post_id, 'price_lokal', true);
            $price_luarkota = get_post_meta($post_id, 'price_luarkota', true);
            $price_ai_lokal = get_post_meta($post_id, 'price_ai_lokal', true);
            $price_ai_luarkota = get_post_meta($post_id, 'price_ai_luarkota', true);
            $price_airport = get_post_meta($post_id, 'price_airport', true);
            $phone = get_post_meta($post_id, 'phone', true);
            $thumb = get_the_post_thumbnail_url($post_id, 'medium');

    ?>
            <div class="produk-card fade-in">
                <div class="produk-image" style="background-image: url('<?php echo esc_url($thumb); ?>');"></div>
                <div class="produk-info">
                    <h3><?php echo esc_html($title); ?></h3>
                    <p class="produk-capacity">👥 <?php echo esc_html($capacity); ?> Orang</p>
                    <?php
                    $related_services = get_field('service', $post_id);
                    if ($related_services) {
                        echo '<div class="tag produk-services">';
                        echo '<strong>Layanan:</strong><br>';
                        foreach ($related_services as $service_post) {
                            echo '<span class="service-item">📌 ' . esc_html(get_the_title($service_post)) . '</span><br>';
                        }
                        echo '</div>';
                    }
                    ?>
                    <div class="produk-harga-tags">
                        <span class="tag">Dalam Kota:
                            <?php echo ($price_lokal == 0 || $price_lokal == '') ? '<span class="badge badge-call">Call for Price</span>' : 'Rp' . number_format($price_lokal, 0, ',', '.'); ?>
                        </span>
                        <span class="tag">Luar Kota:
                            <?php echo ($price_luarkota == 0 || $price_luarkota == '') ? '<span class="badge badge-call">Call for Price</span>' : 'Rp' . number_format($price_luarkota, 0, ',', '.'); ?>
                        </span>
                        <div class="tag ai-group">
                            <div class="ai-title">All Inclusive</div>
                            <div class="ai-item">
                                <span>Dalam Kota :</span>
                                <span class="harga">
                                    <?php echo ($price_ai_lokal == 0 || $price_ai_lokal == '') ? '<span class="badge badge-call">Call for Price</span>' : 'Rp' . number_format($price_ai_lokal, 0, ',', '.'); ?>
                                </span>
                            </div>
                            <div class="ai-item">
                                <span>Luar Kota :</span>
                                <span class="harga">
                                    <?php echo ($price_ai_luarkota == 0 || $price_ai_luarkota == '') ? '<span class="badge badge-call">Call for Price</span>' : 'Rp' . number_format($price_ai_luarkota, 0, ',', '.'); ?>
                                </span>
                            </div>
                        </div>

                        <span class="tag">Airport:
                            <?php echo ($price_airport == 0 || $price_airport == '') ? '<span class="badge badge-call">Call for Price</span>' : 'Rp' . number_format($price_airport, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <?php
                    $phone_raw = get_post_meta($post_id, 'phone', true);
                    $cleanPhone = preg_replace('/[^0-9]/', '', $phone_raw);
                    if (substr($cleanPhone, 0, 1) === '0') {
                        $cleanPhone = '62' . substr($cleanPhone, 1);
                    }

                    $related_services = get_field('service', $post_id);
                    $service_titles = [];
                    if ($related_services) {
                        foreach ($related_services as $service_post) {
                            $service_titles[] = html_entity_decode(get_the_title($service_post));
                        }
                    }

                    $service_list = "";
                    foreach ($service_titles as $title) {
                        $service_list .= "🔹 " . $title . "\n";
                    }

                    $message = "🚨 *Booking Permintaan Produk*\n\n"
                        . "👋 Halo Admin *kelilingindonesiatimur.id*,\n\n"
                        . "Saya tertarik dengan produk berikut:\n"
                        . "🚗 *" . get_the_title($post_id) . "*\n\n"
                        . "Dengan pilihan layanan:\n"
                        . $service_list . "\n"
                        . "Mohon informasi lebih lanjut terkait ketersediaan dan harga.\n\n"
                        . "🙏 Terima kasih!";

                    $whatsappMessage = urlencode($message);
                    $whatsappLink = "https://wa.me/{$cleanPhone}?text={$whatsappMessage}";
                    ?>

                    <a href="<?php echo htmlspecialchars($whatsappLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="produk-booking-btn">
                        <i class="fab fa-whatsapp"></i> Booking via WhatsApp
                    </a>

                </div>
            </div>
<?php
        }
        wp_reset_postdata();
    }

    wp_die();
}
add_action('wp_ajax_load_produk_cards', 'load_produk_cards_callback');
add_action('wp_ajax_nopriv_load_produk_cards', 'load_produk_cards_callback');
