<?php
function mapasituju_layanan_kami_shortcode()
{
    ob_start();

    // Query layanan
    $layanan = new WP_Query(array(
        'post_type' => 'service',
        'posts_per_page' => -1
    ));

    if ($layanan->have_posts()) :
?>
        <style>
            .layanan-kami-wrapper {
                display: flex;
                justify-content: center;
            }

            .layanan-kami-grid {
                display: grid;
                /*                 grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); */
                grid-template-columns: repeat(3, 1fr);
                gap: 30px;
                padding: 20px;
            }

            .layanan-card {
                background: #fff;
                border-radius: 20px;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
                padding: 30px;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                position: relative;
                overflow: hidden;
                text-align: center;
            }

            .layanan-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 20px 30px rgba(0, 0, 0, 0.15);
            }

            .layanan-card img.icon {
                width: 60px;
                height: 60px;
                object-fit: contain;
                margin-bottom: 20px;
            }

            .layanan-card h3 {
                font-size: 1.5rem;
                font-weight: 700;
                color: #333;
                margin-bottom: 15px;
            }

            .layanan-card .desc {
                font-size: 1rem;
                color: #555;
                margin-bottom: 20px;
            }

            .layanan-card .fitur {
                font-size: 0.95rem;
                color: #777;
            }

            .layanan-card::before,
            .layanan-card::after {
                content: "";
                position: absolute;
                width: 120px;
                height: 120px;
                background: #ed2d56;
                border-radius: 50%;
                opacity: 0.1;
                z-index: 0;
            }

            .layanan-card::before {
                top: -40px;
                right: -40px;
            }

            .layanan-card::after {
                bottom: -40px;
                left: -40px;
            }

            .layanan-card .fitur ul {
                list-style: none;
                padding-left: 1.5em;
            }

            .layanan-card .fitur ul li {
                text-align: left;
                position: relative;
                padding-left: 1.5em;
                display: flex;
                align-items: flex-start;
                /* supaya icon dan teks sejajar atas */
            }


            .layanan-card .fitur ul li::before {
                content: "✨";
                position: absolute;
                left: 0;
                top: 0.15em;
                font-size: 1.1em;
            }

            @media (max-width: 992px) {
                .layanan-kami-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (max-width: 600px) {
                .layanan-kami-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="layanan-kami-wrapper">
            <div class="layanan-kami-grid">
                <?php while ($layanan->have_posts()) : $layanan->the_post(); ?>
                    <?php
                    $icon = get_field('icon');
                    ?>
                    <div class="layanan-card">
                        <?php if ($icon): ?>
                            <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($icon['alt']); ?>" class="icon">
                        <?php endif; ?>
                        <h3><?php the_title(); ?></h3>
                        <div class="desc"><?php echo get_the_excerpt(); ?></div>
                        <div class="fitur">
                            <?php the_field('fitur'); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
<?php
    else :
        echo '<p>No services found.</p>';
    endif;

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('layanan_kami_cards', 'mapasituju_layanan_kami_shortcode');
