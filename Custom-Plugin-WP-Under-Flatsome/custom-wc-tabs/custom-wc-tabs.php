<?php
/*
Plugin Name: Custom WC Tabs with Location Filter
Description: Menampilkan produk berdasarkan lokasi dengan tab navigasi dan loader.
Version: 1.1
Author: Puji Ermanto<dev@codesyariah.co.id> | AKA Mamam Yuk | AKA Janji Mas Joni
*/

// function enqueue_lottiefiles_script_in_head()
// {
//     wp_enqueue_script(
//         'lottiefiles-player',
//         'https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs',
//         [],
//         null,
//         false // false = load di head
//     );
// }
// add_action('wp_enqueue_scripts', 'enqueue_lottiefiles_script_in_head');

function custom_wc_tabs_shortcode()
{
    ob_start();
?>
    <style>
        .custom-wc-tabs {
            position: relative;
            width: 100%;
            display: block;
        }

        .custom-wc-tabs ul.tabs.sticky {
            position: fixed;
            top: 11.7rem;
            left: 0;
            width: 100%;
            background-color: #fff;
            /* biar keliatan clear */
            z-index: 9999;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }


        /* .custom-wc-tabs ul.tabs {
            display: flex;
            overflow-x: auto;
            white-space: nowrap;
            border-bottom: 2px solid #eee;
            padding: 0;
            margin: 0 0 20px 0;
            scrollbar-width: thin;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .custom-wc-tabs ul.tabs::-webkit-scrollbar {
            display: none;
        }

        .custom-wc-tabs ul.tabs li {
            list-style: none;
            padding: 12px 20px;
            cursor: pointer;
            color: #333;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            flex-shrink: 0;
            font-weight: 500;
        }

        .custom-wc-tabs ul.tabs li {
            position: relative;
            color: #555;
            font-weight: 500;
            cursor: pointer;
            padding: 12px 20px;
            transition: color 0.3s ease;
        }

        .custom-wc-tabs ul.tabs li.active {
            color: #0056b3;
            font-weight: 700;
        }

        .custom-wc-tabs ul.tabs li.active::after {
            content: '';
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 0;
            height: 3px;
            border-radius: 3px 3px 0 0;
            background: linear-gradient(90deg, #007bff, #00c6ff);
            animation: slideIn 0.4s forwards;
        } */
        /* .custom-wc-tabs ul.tabs {
            display: flex;
            overflow-x: auto;
            white-space: nowrap;
            padding: 10px 0;
            margin: 0 0 20px 0;
            scrollbar-width: thin;
            gap: 10px;
            background-color: rgba(255, 255, 255, 0.5);
        }

        .custom-wc-tabs ul.tabs::-webkit-scrollbar {
            display: none;
        } */

        .custom-wc-tabs ul.tabs {
            display: flex;
            overflow-x: auto;
            white-space: nowrap;
            padding: 10px 0;
            margin: 0 0 20px 0;
            scrollbar-width: thin;
            scrollbar-color: #00c6ff #f0f0f0;
            gap: 10px;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 8px;
            scrollbar-gutter: stable both-edges;
        }

        /* Untuk Chrome, Edge, dan Safari */
        .custom-wc-tabs ul.tabs::-webkit-scrollbar {
            height: 8px;
        }

        .custom-wc-tabs ul.tabs::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 10px;
        }

        .custom-wc-tabs ul.tabs::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #007bff, #00c6ff);
            border-radius: 10px;
        }

        .custom-wc-tabs ul.tabs::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(90deg, #0056b3, #00aaff);
        }

        .custom-wc-tabs ul.tabs li {
            list-style: none;
            padding: 10px 16px;
            cursor: pointer;
            background-color: #ffffff;
            color: #000;
            font-weight: 700;
            border-radius: 6px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
            flex-shrink: 0;
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        .custom-wc-tabs ul.tabs li:hover {
            background-color: #f0f0f0;
        }

        .custom-wc-tabs ul.tabs li.active {
            background: linear-gradient(90deg, #007bff, #00c6ff);
            /* biru terang */
            color: #ffffff;
            border-color: linear-gradient(90deg, #007bff, #00c6ff);
        }

        @keyframes slideIn {
            from {
                width: 0;
                left: 50%;
                right: 50%;
                opacity: 0;
            }

            to {
                width: calc(100% - 40px);
                left: 20px;
                right: 20px;
                opacity: 1;
            }
        }

        .custom-wc-tabs .product-wrapper {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 15px;
            clear: both;
            box-sizing: border-box;
        }

        .custom-wc-tabs .product-list {
            display: grid;
            /* grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); */
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            /* ubah dari 240 jadi 180 */
            gap: 20px;
        }

        .custom-wc-tabs .product-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            padding: 12px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease;
        }

        .custom-wc-tabs .product-card:hover {
            transform: translateY(-4px);
        }

        .custom-wc-tabs .product-card .image-wrapper {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .custom-wc-tabs .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
            display: block;
        }

        .custom-wc-tabs .product-card img:hover {
            transform: scale(1.05);
        }

        .custom-wc-tabs .category-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background-color: rgba(0, 123, 255, 0.85);
            color: #fff;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 12px;
            text-transform: uppercase;
            pointer-events: none;
            user-select: none;
            z-index: 10;
            white-space: nowrap;
        }

        .custom-wc-tabs .product-card h3 {
            font-size: 1rem;
            margin: 0 0 8px;
        }

        .custom-wc-tabs .product-card .price {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .custom-wc-tabs .product-card .button {
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
        }

        .product-loader {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            border-radius: 12px;
        }

        .spinner {
            border: 4px solid #eee;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .custom-wc-tabs .product-list {
                grid-template-columns: 1fr !important;
            }

            .custom-wc-tabs ul.tabs.sticky {
                top: 8rem;
            }
        }
    </style>

    <div class="custom-wc-tabs">
        <ul class="tabs">
            <li class="tab-button active" data-location="all">All</li>
            <?php
            $lokasi_parent = get_term_by('name', 'Lokasi', 'product_cat');

            if ($lokasi_parent && !is_wp_error($lokasi_parent)) {
                $terms = get_terms([
                    'taxonomy' => 'product_cat',
                    'hide_empty' => true,
                    'parent' => $lokasi_parent->term_id
                ]);

                foreach ($terms as $term) {
                    echo '<li class="tab-button" data-location="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</li>';
                }
            }
            ?>
        </ul>

        <!-- <div class="product-loader">
            <div class="spinner"></div>
        </div> -->
        <div class="product-loader">
            <dotlottie-player
                src="https://lottie.host/1daaa55d-9271-40ce-8679-34774e07e254/2kUEuB2wFr.lottie"
                background="transparent"
                speed="1"
                style="width: 150px; height: 150px"
                loop
                autoplay></dotlottie-player>
        </div>

        <div class="product-wrapper">
            <div class="product-list">
                <?php
                $args = [
                    'post_type' => 'product',
                    'posts_per_page' => 4,
                    'post_status' => 'publish'
                ];
                $loop = new WP_Query($args);
                while ($loop->have_posts()) : $loop->the_post();
                    global $product;
                    $terms = get_the_terms(get_the_ID(), 'product_cat');
                    $location_slug = $terms && !is_wp_error($terms) ? $terms[0]->slug : '';
                ?>
                    <div class="product-card" data-location="<?php echo esc_attr($location_slug); ?>">
                        <a href="<?php the_permalink(); ?>">
                            <div class="image-wrapper">
                                <?php if (has_post_thumbnail()) {
                                    the_post_thumbnail('medium');
                                } ?>
                                <?php if ($terms && !is_wp_error($terms)) : ?>
                                    <div class="category-badge"><?php echo esc_html($terms[0]->name); ?></div>
                                <?php endif; ?>
                            </div>
                            <h3><?php the_title(); ?></h3>
                        </a>
                        <div class="price"><?php echo $product->get_price_html(); ?></div>
                    </div>
                <?php endwhile;
                wp_reset_postdata(); ?>
            </div>
        </div>
    </div>

    <!-- JavaScript AJAX -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.custom-wc-tabs .tab-button');
            const productList = document.querySelector('.custom-wc-tabs .product-list');
            const loader = document.querySelector('.product-loader');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    const location = this.getAttribute('data-location');
                    loader.style.display = 'flex';

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'get_products_by_location',
                                location: location
                            })
                        })
                        .then(response => response.text())
                        .then(data => {
                            setTimeout(() => {
                                productList.innerHTML = data;
                                loader.style.display = 'none';
                            }, 2500)
                        });
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelector('.custom-wc-tabs ul.tabs');
            const productCard = document.querySelector('.custom-wc-tabs .product-card');

            if (tabs && productCard) {
                const offsetTop = productCard.offsetTop;
                console.log(window.scrollY);
                console.log(offsetTop);

                window.addEventListener('scroll', function() {
                    if (window.scrollY >= offsetTop) {
                        tabs.classList.add('sticky');
                    } else {
                        tabs.classList.remove('sticky');
                    }
                });
            }
        });
    </script>



    <?php
    return ob_get_clean();
}
add_shortcode('custom_wc_tabs', 'custom_wc_tabs_shortcode');


add_action('wp_ajax_get_products_by_location', 'get_products_by_location_callback');
add_action('wp_ajax_nopriv_get_products_by_location', 'get_products_by_location_callback');

function get_products_by_location_callback()
{
    $location_slug = sanitize_text_field($_POST['location']);
    $args = [
        'post_type' => 'product',
        'posts_per_page' => 4,
        'post_status' => 'publish'
    ];

    if ($location_slug !== 'all') {
        $args['tax_query'] = [
            [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $location_slug
            ]
        ];
    }

    $query = new WP_Query($args);
    ob_start();

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
            global $product;
            $terms = get_the_terms(get_the_ID(), 'product_cat');
            $location_slug = $terms && !is_wp_error($terms) ? $terms[0]->slug : '';
    ?>
            <div class="product-card" data-location="<?php echo esc_attr($location_slug); ?>">
                <a href="<?php the_permalink(); ?>">
                    <div class="image-wrapper">
                        <?php if (has_post_thumbnail()) {
                            the_post_thumbnail('medium');
                        } ?>
                        <?php if ($terms && !is_wp_error($terms)) : ?>
                            <div class="category-badge"><?php echo esc_html($terms[0]->name); ?></div>
                        <?php endif; ?>
                    </div>
                    <h3><?php the_title(); ?></h3>
                </a>
                <div class="price"><?php echo $product->get_price_html(); ?></div>
            </div>
<?php
        endwhile;
    else :
        echo '<p>Tidak ada produk ditemukan.</p>';
    endif;

    wp_reset_postdata();
    echo ob_get_clean();
    wp_die();
}
