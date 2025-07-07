<?php
function custom_pricing_fields() {
    global $post;
    
    echo '<div class="options_group">';

    // Weekday Price
    woocommerce_wp_text_input( array(
        'id' => '_weekday_price',
        'label' => __('Weekday Price', 'woocommerce'),
        'type' => 'number',
        'custom_attributes' => array(
            'step' => 'any',
            'min' => '0'
        )
    ));

    // Weekend Price
    woocommerce_wp_text_input( array(
        'id' => '_weekend_price',
        'label' => __('Weekend Price', 'woocommerce'),
        'type' => 'number',
        'custom_attributes' => array(
            'step' => 'any',
            'min' => '0'
        )
    ));

    // Holiday Price
    woocommerce_wp_text_input( array(
        'id' => '_holiday_price',
        'label' => __('Holiday Price', 'woocommerce'),
        'type' => 'number',
        'custom_attributes' => array(
            'step' => 'any',
            'min' => '0'
        )
    ));

    echo '</div>';
}
add_action('woocommerce_product_options_general_product_data', 'custom_pricing_fields');


function save_custom_pricing_fields($post_id) {
    // Weekday Price
    $weekday_price = isset($_POST['_weekday_price']) ? sanitize_text_field($_POST['_weekday_price']) : '';
    update_post_meta($post_id, '_weekday_price', $weekday_price);

    // Weekend Price
    $weekend_price = isset($_POST['_weekend_price']) ? sanitize_text_field($_POST['_weekend_price']) : '';
    update_post_meta($post_id, '_weekend_price', $weekend_price);

    // Holiday Price
    $holiday_price = isset($_POST['_holiday_price']) ? sanitize_text_field($_POST['_holiday_price']) : '';
    update_post_meta($post_id, '_holiday_price', $holiday_price);
}
add_action('woocommerce_process_product_meta', 'save_custom_pricing_fields');


function custom_dynamic_pricing( $price, $product ) {
    // Ambil tanggal dan hari saat ini
    $current_day = date('w'); // 0 (Minggu) - 6 (Sabtu)
    $current_date = date('Y-m-d'); // Format Tanggal: 2024-08-10

    // Ambil harga dari custom fields
    $weekday_price = get_post_meta($product->get_id(), '_weekday_price', true);
    $weekend_price = get_post_meta($product->get_id(), '_weekend_price', true);
    $holiday_price = get_post_meta($product->get_id(), '_holiday_price', true);

    // Tentukan tanggal Hari Raya (misalnya Idul Fitri)
    $holiday_dates = array('2024-04-10', '2024-04-11'); // Ganti dengan tanggal Hari Raya Anda

    // Tentukan harga berdasarkan hari
    if ( in_array( $current_date, $holiday_dates ) && !empty($holiday_price) ) {
        $price = $holiday_price; // Harga untuk Hari Raya
    } elseif ( in_array( $current_day, array( 5, 6, 0 ) ) && !empty($weekend_price) ) {
        $price = $weekend_price; // Harga untuk Weekend (Jumat, Sabtu, Minggu)
    } elseif ( !empty($weekday_price) ) {
        $price = $weekday_price; // Harga untuk Weekday (Senin - Kamis)
    }

    return $price;
}

add_filter( 'woocommerce_product_get_price', 'custom_dynamic_pricing', 10, 2 );
add_filter( 'woocommerce_product_variation_get_price', 'custom_dynamic_pricing', 10, 2 );


function add_font_awesome() {
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">';
}
add_action('wp_head', 'add_font_awesome');

function format_whatsapp_number($number) {
    // Hilangkan semua karakter yang bukan angka
    $number = preg_replace('/[^0-9]/', '', $number);

    // Jika nomor dimulai dengan 0, ganti dengan 62
    if (substr($number, 0, 1) === '0') {
        $number = '62' . substr($number, 1);
    }

    return $number;
}


function custom_woocommerce_product_list_shortcode() {
    ob_start();

    // Ambil nomor WhatsApp dari custom post type yang menggunakan JetEngine
    $args = array(
        'post_type' => 'whatsapp-order',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'tambahkan-nomor',
                'compare' => 'EXISTS',
            ),
        ),
    );

    $query = new WP_Query($args);
    $whatsapp_number = '';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $repeater_field = get_post_meta(get_the_ID(), 'tambahkan-nomor', true);
            if ($repeater_field) {
                foreach ($repeater_field as $row) {
                    $whatsapp_number = $row['nomor_whatsapp'];
                }
            }
        }
        wp_reset_postdata();
    }

    // Query produk WooCommerce
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
    );

    $products_query = new WP_Query($args);

    if ($products_query->have_posts()) :
        ?>
        <style>
            .custom-product-list {
				display: flex;
				flex-wrap: wrap;
				gap: 30px;
				justify-content: center;
			}

			.custom-product-item {
				border: 1px solid #ddd;
				border-radius: 15px;
				overflow: hidden;
				box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
				padding: 20px;
				text-align: center;
				width: calc(25% - 30px); /* Empat kolom */
				box-sizing: border-box;
				transition: transform 0.3s ease, box-shadow 0.3s ease;
			}

			.custom-product-item:hover {
				transform: translateY(-8px);
				box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
			}

			.custom-product-item img {
				max-width: 100%;
				height: auto;
				cursor: pointer;
				border-radius: 10px;
			}

			.custom-product-item h2 {
				font-size: 22px;
				margin: 15px 0;
				color: #333;
				font-weight: bold;
			}

			.custom-product-item .price {
				font-size: 18px;
				color: #777;
				margin: 10px 0;
			}

			.book-now-button,
			.detail-button {
				text-align: center;
				padding: 12px 24px;
				border-radius: 5px;
				text-decoration: none;
				font-weight: bold;
				display: inline-block;
				margin-top: 15px;
				transition: background-color 0.3s ease;
			}

			.book-now-button {
				background-color: #25D366;
				color: #ffffff;
			}

			.book-now-button:hover {
				background-color: #128C7E;
			}

			.book-now-button .fa-whatsapp {
				margin-right: 8px;
				font-size: 18px;
			}

			.detail-button {
				background-color: #007bff;
				color: #ffffff;
			}

			.detail-button:hover {
				background-color: #0056b3;
			}

			.detail-button .fa-eye {
				margin-right: 8px;
				font-size: 18px;
			}

			.product-gallery-thumbnails {
				display: flex;
				gap: 10px;
				margin-top: 10px;
				justify-content: center;
			}

			.product-gallery-thumbnails img {
				width: 100px; /* Ukuran thumbnail */
				height: auto;
				cursor: pointer;
				border: 2px solid #ddd;
				border-radius: 5px;
				transition: border-color 0.3s ease;
			}

			.product-gallery-thumbnails img:hover {
				border-color: #25D366;
			}

			/* Media query untuk perangkat seluler */
			@media (max-width: 768px) {
				.custom-product-item {
					width: calc(50% - 20px); /* Dua kolom pada layar kecil */
				}
			}

			@media (max-width: 480px) {
				.custom-product-item {
					width: calc(100% - 20px); /* Satu kolom pada layar sangat kecil */
				}
			}

            /* Popup styles */
            .popup {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                justify-content: center;
                align-items: center;
                z-index: 9999;
            }
            .popup-content {
                background: #fff;
                padding: 20px;
                border-radius: 5px;
                width: 300px;
                text-align: center;
            }
            .popup-content input {
                margin: 5px 0;
                padding: 10px;
                width: calc(100% - 20px);
                border-radius: 5px;
                border: 1px solid #ddd;
            }
            .popup-content button {
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                background: #25D366;
                color: #fff;
                cursor: pointer;
                font-size: 16px;
                margin-top: 10px;
            }
            .popup-content button:hover {
                background: #128C7E;
            }
        </style>
        <div class="custom-product-list">
            <?php while ($products_query->have_posts()) : $products_query->the_post(); ?>
                <div class="custom-product-item">
                    <?php if (has_post_thumbnail()) : ?>
                        <a href="<?php echo wp_get_attachment_url(get_post_thumbnail_id()); ?>" data-lightbox="product-gallery-<?php echo get_the_ID(); ?>" data-title="<?php the_title(); ?>">
                            <?php the_post_thumbnail('large'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $product_images = get_post_meta(get_the_ID(), '_product_image_gallery', true);
                    $image_ids = explode(',', $product_images);

                    if (!empty($image_ids)) : ?>
                        <div class="product-gallery-thumbnails">
                            <?php foreach ($image_ids as $image_id) : ?>
                                <?php $image_url = wp_get_attachment_url($image_id); ?>
                                <a href="<?php echo esc_url($image_url); ?>" data-lightbox="product-gallery-<?php echo get_the_ID(); ?>" data-title="<?php the_title(); ?>">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title(); ?>">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h2><?php the_title(); ?></h2>

                    <?php
                    $product = wc_get_product(get_the_ID());
                    $weekday_price = get_post_meta(get_the_ID(), '_weekday_price', true);
                    $weekend_price = get_post_meta(get_the_ID(), '_weekend_price', true);
                    $holiday_price = get_post_meta(get_the_ID(), '_holiday_price', true);

                    echo '<div class="price weekday">Weekday Price: ' . wc_price($weekday_price) . '</div>';
                    if ($weekend_price) {
                        echo '<div class="price weekend">Weekend Price: ' . wc_price($weekend_price) . '</div>';
                    }
                    if ($holiday_price) {
                        echo '<div class="price holiday">Holiday Price: ' . wc_price($holiday_price) . '</div>';
                    }
                    ?>

                    <a href="<?php the_permalink(); ?>" class="detail-button">
                        <i class="fa-solid fa-circle-info"></i> Lihat Detail
                    </a>

                    <?php if ($whatsapp_number) : $formatted_whatsapp_number = format_whatsapp_number($whatsapp_number); ?>
                        <a href="#" class="book-now-button"
						   data-whatsapp-number="<?php echo $formatted_whatsapp_number; ?>"
						   data-product-name="<?php the_title(); ?>">
						   <i class="fa-brands fa-whatsapp"></i> Pesan Sekarang
						</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Popup HTML -->
        <div class="popup" id="bookingPopup">
            <div class="popup-content">
				<h2>Informasi Pemesanan</h2>
				<input type="datetime-local" id="orderDatetime" placeholder="Pilih tanggal dan waktu">
				<button id="submitOrder">Kirim</button>
			</div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
				const popup = document.getElementById('bookingPopup');
				const bookNowButtons = document.querySelectorAll('.book-now-button');
				const submitButton = document.getElementById('submitOrder');
				const orderDatetime = document.getElementById('orderDatetime');

				// Set nilai default input datetime-local dengan tanggal hari ini
				const now = new Date();
				const offset = now.getTimezoneOffset();
				const localISOTime = new Date(now.getTime() - (offset * 60 * 1000)).toISOString().slice(0, 16);
				orderDatetime.value = localISOTime;

				bookNowButtons.forEach(button => {
					button.addEventListener('click', function (event) {
						event.preventDefault();
						popup.style.display = 'flex';
						// Simpan nomor WhatsApp, nama produk, dan harga dari atribut data
						popup.dataset.whatsappNumber = button.getAttribute('data-whatsapp-number');
						popup.dataset.productName = button.getAttribute('data-product-name');
					});
				});

				submitButton.addEventListener('click', function () {
					const datetime = orderDatetime.value;

					const whatsappNumber = popup.dataset.whatsappNumber;
					const productName = popup.dataset.productName;

					if (datetime) {
						// Format tanggal untuk WhatsApp
						const formattedDatetime = new Date(datetime).toLocaleString('id-ID', {
							weekday: 'long',
							day: '2-digit',
							month: '2-digit',
							year: 'numeric',
							hour: '2-digit',
							minute: '2-digit',
						});

						const message = `Pesanan baru:\nTanggal dan Waktu: ${formattedDatetime}\n\nPaket Pesona Nirwana : ${productName} - Pesona Nirwana\n`;
						const whatsappUrl = `https://api.whatsapp.com/send?phone=${whatsappNumber}&text=${encodeURIComponent(message)}`;

// 						window.location.href = whatsappUrl;
						window.open(whatsappUrl, '_blank');
						popup.style.display = 'none';
					} else {
						alert('Harap isi semua informasi.');
					}
				});

				// Tutup popup jika area di luar popup diklik
				popup.addEventListener('click', function (event) {
					if (event.target === popup) {
						popup.style.display = 'none';
					}
				});
			});
        </script>
    <?php
    endif;
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('custom_product_list', 'custom_woocommerce_product_list_shortcode');


function display_custom_prices_on_product_page() {
    global $product;
    
    // Ambil harga dari custom fields
    $weekday_price = get_post_meta($product->get_id(), 'weekday_price', true);
    $weekend_price = get_post_meta($product->get_id(), 'weekend_price', true);
    $holiday_price = get_post_meta($product->get_id(), 'holiday_price', true);

    // Tampilkan harga hanya jika field tidak kosong
    if ($weekday_price || $weekend_price || $holiday_price) {
        echo '<div class="custom-prices">';
        
        if ($weekday_price) {
            echo '<p>Weekday Price: ' . wc_price($weekday_price) . '</p>';
        }
        
        if ($weekend_price) {
            echo '<p>Weekend Price: ' . wc_price($weekend_price) . '</p>';
        }
        
        if ($holiday_price) {
            echo '<p>Holiday Price: ' . wc_price($holiday_price) . '</p>';
        }

        echo '</div>';
    }
}
add_action('woocommerce_single_product_summary', 'display_custom_prices_on_product_page', 25);
