<?php
if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
});


// Tambahkan CSS khusus untuk kartu produk
function custom_shop_product_styles() {
    if (is_shop()) {
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
                width: 100%;
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
            .custom-product-item .price.weekday {
                color: #333;
                font-weight: bold;
            }
            .custom-product-item .price.weekend,
            .custom-product-item .price.holiday {
                color: #555;
                font-weight: normal;
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
                flex-wrap: wrap;
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

            /* Media queries for responsive design */
            @media (max-width: 1200px) {
                .custom-product-item {
                    width: calc(33.33% - 20px); /* Tiga kolom */
                }
            }
            @media (max-width: 768px) {
                .custom-product-item {
                    width: calc(50% - 20px); /* Dua kolom */
                }
            }
            @media (max-width: 480px) {
                .custom-product-item {
                    width: 100%; /* Satu kolom */
                }
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'custom_shop_product_styles');

// Ganti tampilan produk di halaman Shop
function custom_shop_product_loop_start() {
    if (is_shop()) {
        echo '<div class="custom-product-list">';
    }
}
add_action('woocommerce_before_shop_loop', 'custom_shop_product_loop_start', 5);

function custom_shop_product_loop_end() {
    if (is_shop()) {
        echo '</div>';
    }
}
add_action('woocommerce_after_shop_loop', 'custom_shop_product_loop_end', 15);

// Ganti tampilan setiap item produk di halaman Shop
function custom_shop_product_loop_item() {
    global $product;

    // Mengambil informasi harga
    $weekday_price = get_post_meta($product->get_id(), '_weekday_price', true);
    $weekend_price = get_post_meta($product->get_id(), '_weekend_price', true);
    $holiday_price = get_post_meta($product->get_id(), '_holiday_price', true);

    ?>
    <div class="custom-product-item">
        <?php if (has_post_thumbnail()) : ?>
            <a href="<?php echo wp_get_attachment_url(get_post_thumbnail_id()); ?>" data-lightbox="product-gallery-<?php echo $product->get_id(); ?>" data-title="<?php the_title(); ?>">
                <?php echo get_the_post_thumbnail($product->get_id(), 'large'); ?>
            </a>
        <?php endif; ?>
        
        <?php
        // Tampilkan thumbnail produk tambahan
        $product_images = get_post_meta($product->get_id(), '_product_image_gallery', true);
        $image_ids = explode(',', $product_images);
        $limited_images = array_slice($image_ids, 0, 5); // Batasi hingga 5 gambar

        if (!empty($limited_images)) : ?>
            <div class="product-gallery-thumbnails">
                <?php foreach ($limited_images as $image_id) : ?>
                    <?php $image_url = wp_get_attachment_url($image_id); ?>
                    <a href="<?php echo esc_url($image_url); ?>" data-lightbox="product-gallery-<?php echo $product->get_id(); ?>" data-title="<?php the_title(); ?>">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title(); ?>">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2><?php echo $product->get_name(); ?></h2>

        <div class="price weekday">Weekday Price: <?php echo wc_price($weekday_price); ?></div>
        <?php if ($weekend_price) : ?>
            <div class="price weekend">Weekend Price: <?php echo wc_price($weekend_price); ?></div>
        <?php endif; ?>
        <?php if ($holiday_price) : ?>
            <div class="price holiday">Holiday Price: <?php echo wc_price($holiday_price); ?></div>
        <?php endif; ?>

        <a href="<?php the_permalink(); ?>" class="detail-button">
            <i class="fa-solid fa-circle-info"></i> Lihat Detail
        </a>

        <?php
        $whatsapp_number = '';
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

        if ($whatsapp_number) : ?>
            <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>?text=I%20want%20to%20book%20the%20product%20-%20<?php echo urlencode(get_the_title()); ?>" class="book-now-button">
                <i class="fa-brands fa-whatsapp"></i> Pesan Sekarang
            </a>
        <?php endif; ?>
    </div>
    <?php
}
add_action('woocommerce_shop_loop_item_title', 'custom_shop_product_loop_item', 10);
