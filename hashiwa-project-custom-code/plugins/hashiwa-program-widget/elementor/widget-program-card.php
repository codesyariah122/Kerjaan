<?php
if (!defined('ABSPATH')) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Hashiwa_Program_Card_Widget extends Widget_Base
{

    public function get_name()
    {
        return 'hashiwa_program_card';
    }

    public function get_title()
    {
        return 'Hashiwa Program Card';
    }

    public function get_icon()
    {
        return 'eicon-post-list';
    }

    public function get_categories()
    {
        return ['hashiwa-category'];
    }

    protected function register_controls()
    {

        // FILTER SECTION
        $this->start_controls_section(
            'filter_section',
            ['label' => 'Filter Program']
        );

        $terms = get_terms(['taxonomy' => 'jenis_program', 'hide_empty' => false]);
        $options = ['all' => 'Semua Jenis Program'];
        foreach ($terms as $term) {
            $options[$term->slug] = $term->name;
        }

        $this->add_control(
            'filter_jenis',
            [
                'label' => 'Jenis Program',
                'type' => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => $options
            ]
        );

        $this->add_control(
            'jumlah',
            [
                'label' => 'Jumlah Program',
                'type' => Controls_Manager::NUMBER,
                'default' => 3,
                'min' => 1,
                'max' => 20,
            ]
        );

        $this->add_control(
            'show_rating',
            [
                'label' => 'Tampilkan Rating',
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes'
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {

        $settings = $this->get_settings_for_display();

        // Query
        $args = [
            'post_type'      => 'program',
            'posts_per_page' => $settings['jumlah'],
            'orderby'        => 'menu_order',
            'order'          => 'ASC'
        ];

        if ($settings['filter_jenis'] !== 'all') {
            $args['tax_query'] = [[
                'taxonomy' => 'jenis_program',
                'field'    => 'slug',
                'terms'    => $settings['filter_jenis']
            ]];
        }

        $programs = new WP_Query($args);

        echo '<div class="hashiwa-program-grid">';

        while ($programs->have_posts()) : $programs->the_post();

            $thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
            $excerpt = wp_trim_words(get_the_excerpt(), 15);

            $durasi = get_field('durasi_program', get_the_ID());
            $rating = get_field('rating_program', get_the_ID());
            $cta_text = get_field('button_text', get_the_ID()) ?: 'Lihat Program';
            $cta_url = get_field('button_url', get_the_ID()) ?: get_permalink();
            $highlight = get_field('highlight_program', get_the_ID());

            $terms = get_the_terms(get_the_ID(), 'jenis_program');
            $badge = $terms ? $terms[0]->name : '';

            echo '<div class="program-card horizontal">';

            if ($highlight) {
                echo '<div class="highlight-badge-h">Unggulan</div>';
            }

            if ($thumb) {
                echo '<div class="pc-thumb-h"><img src="' . $thumb . '" alt=""></div>';
            }

            echo '<div class="pc-content-h">';

            // badge kategori
            echo '<span class="pc-badge">' . $badge . '</span>';

            echo '<h3 class="pc-title-h">' . get_the_title() . '</h3>';

            echo '<p class="pc-desc-h">' . $excerpt . '</p>';

            if ($durasi) {
                echo '<div class="pc-durasi-h"><i class="eicon-clock"></i> ' . $durasi . '</div>';
            }

            if ($settings['show_rating'] === 'yes' && $rating) {
                echo '<div class="pc-rating-h">';
                for ($i = 1; $i <= 5; $i++) {
                    echo ($i <= $rating)
                        ? '<span class="star full">★</span>'
                        : '<span class="star empty">☆</span>';
                }
                echo '</div>';
            }

            echo '<a href="' . $cta_url . '" class="pc-btn-h">' . $cta_text . '</a>';

            echo '</div>';

            echo '</div>';


        endwhile;
        wp_reset_postdata();

        echo '</div>';

        // Inline style
?>
        <style>
            /* GRID FULL WIDTH */
            .hashiwa-program-grid {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 35px;
            }

            /* HORIZONTAL CARD */
            .program-card.horizontal {
                display: flex;
                align-items: stretch;
                background: #fff;
                border-radius: 22px;
                overflow: hidden;
                box-shadow: 0 18px 48px rgba(0, 0, 0, 0.08);
                transition: 0.25s ease;
                position: relative;
            }

            .program-card.horizontal:hover {
                transform: translateY(-6px);
                box-shadow: 0 25px 55px rgba(0, 0, 0, 0.15);
            }

            /* LEFT THUMBNAIL */
            .pc-thumb-h {
                width: 40%;
                min-height: 240px;
            }

            .pc-thumb-h img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            /* RIGHT CONTENT AREA */
            .pc-content-h {
                padding: 28px 34px;
                width: 60%;
                display: flex;
                flex-direction: column;
            }

            /* BADGE */
            .pc-badge-h {
                display: inline-block;
                background: #FAD035;
                color: #000;
                padding: 7px 16px;
                font-weight: 700;
                border-radius: 40px;
                font-size: 13px;
                margin-bottom: 12px;
            }

            /* BADGE KATEGORI DI ATAS GAMBAR */
            .pc-badge {
                position: absolute;
                top: 20px;
                left: 20px;
                background: #ffffff;
                color: #111;
                padding: 6px 14px;
                border-radius: 40px;
                font-size: 12.5px;
                font-weight: 700;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            }

            /* BADGE HIGHLIGHT */
            .highlight-badge {
                position: absolute;
                top: 20px;
                right: 20px;
                background: #FAD035;
                color: #000;
                padding: 6px 14px;
                font-weight: 700;
                font-size: 12.5px;
                border-radius: 40px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            }

            /* TITLE */
            .pc-title-h {
                font-size: 24px;
                font-weight: 800;
                line-height: 1.35;
                color: #111;
                margin-bottom: 12px;
            }

            /* DESC */
            .pc-desc-h {
                font-size: 15px;
                line-height: 1.55;
                color: #555;
                margin-bottom: 18px;
            }

            /* DURASI */
            .pc-durasi-h {
                font-size: 15px;
                font-weight: 600;
                margin-bottom: 14px;
            }

            /* RATING */
            .pc-rating-h .star {
                font-size: 21px;
                color: #F5B100;
            }

            .pc-rating-h .star.empty {
                color: #ddd;
            }

            .pc-rating-h {
                margin-bottom: 20px;
            }

            /* CTA BUTTON */
            .pc-btn-h {
                margin-top: auto;
                display: inline-block;
                background: #FAD035;
                color: #000;
                text-align: center;
                padding: 14px 18px;
                font-size: 15px;
                font-weight: 800;
                border-radius: 12px;
                text-decoration: none;
            }

            .pc-btn-h:hover {
                background: #e8c020;
            }

            /* HIGHLIGHT BADGE */
            .highlight-badge-h {
                position: absolute;
                top: 18px;
                right: 18px;
                background: #FAD035;
                color: #000;
                padding: 7px 16px;
                border-radius: 40px;
                font-size: 13px;
                font-weight: 800;
                z-index: 10;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }


            /* RESPONSIVE */
            @media (max-width: 768px) {
                .program-card.horizontal {
                    flex-direction: column;
                }

                .pc-thumb-h {
                    width: 100%;
                    height: 220px;
                }

                .pc-content-h {
                    width: 100%;
                    padding: 24px;
                }
            }
        </style>

<?php
    }
}
