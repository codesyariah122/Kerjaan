<?php
function cs_slider_shortcode($atts)
{
    ob_start();
    $query = new WP_Query([
        'post_type' => 'cs_slider',
        'posts_per_page' => -1
    ]);
?>
    <div class="cs-swiper-container swiper">
        <div class="swiper-wrapper">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <div class="swiper-slide">
                    <div class="slide-wrapper" style="background-image: url('<?php the_post_thumbnail_url('medium'); ?>')"></div>
                </div>
            <?php endwhile;
            wp_reset_postdata(); ?>
        </div>
        <!-- Navigasi dihapus -->
    </div>

<?php
    return ob_get_clean();
}
add_shortcode('custom_slider', 'cs_slider_shortcode');
?>