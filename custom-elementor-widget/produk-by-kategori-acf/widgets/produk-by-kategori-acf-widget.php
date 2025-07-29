<?php

/**
 * Plugin Name: Produk by Kategori ACF
 * Description: Widget Elementor untuk menampilkan produk berdasarkan kategori, jumlah, urutan, dan role user.
 * Version: 1.2.0
 * Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Jhony Kemod | AKA Cowok paling ganteng di komplek ini | AKA Maman Salajami | AKA Deden Inyuuus
 * Author URI: https://pujiermanto-portfolio.vercel.app
 * Text Domain: produk-by-kategori-acf
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;


if (!defined('ABSPATH')) exit;

class Produk_By_Kategori_ACF_Widget extends Widget_Base
{
    private static $rendered_product_ids = [];

    public function get_name()
    {
        return 'produk_by_kategori_acf';
    }

    public function get_title()
    {
        return __('Produk by Kategori ACF', 'produk-by-kategori-acf');
    }

    public function get_icon()
    {
        return 'eicon-products';
    }

    public function get_categories()
    {
        return ['general'];
    }

    protected function _register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Pengaturan Produk', 'produk-by-kategori-acf'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        $options = [
            'current_post_title' => 'Gunakan Judul Post Saat Ini (Post Title)',
            '' => 'Semua Kategori'
        ];
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $options[$cat->slug] = $cat->name;
            }
        }

        $this->add_control(
            'kategori',
            [
                'label'   => __('Pilih Kategori Produk', 'produk-by-kategori-acf'),
                'type'    => Controls_Manager::SELECT2,
                'options' => $options,
                'default' => '',
            ]
        );

        $this->add_control(
            'jumlah',
            [
                'label' => __('Jumlah Produk', 'produk-by-kategori-acf'),
                'type' => Controls_Manager::NUMBER,
                'default' => 8,
                'min' => 1,
                'max' => 100,
            ]
        );

        $this->add_control(
            'urutan',
            [
                'label' => __('Urutkan Berdasarkan Tanggal', 'produk-by-kategori-acf'),
                'type' => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'ASC' => 'Ascending',
                    'DESC' => 'Descending',
                ]
            ]
        );

        $this->add_control(
            'heading_style',
            [
                'label' => __('Heading Kategori', 'produk-by-kategori-acf'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'kategori_heading_typography',
                'label'    => __('Typography', 'produk-by-kategori-acf'),
                'selector' => '{{WRAPPER}} .produk-kategori-heading',
            ]
        );

        $this->add_control(
            'view_all_button_heading',
            [
                'label' => __('Tombol View All Products', 'produk-by-kategori-acf'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'view_all_button_text',
            [
                'label' => __('Teks Tombol', 'produk-by-kategori-acf'),
                'type' => Controls_Manager::TEXT,
                'default' => __('View All Products', 'produk-by-kategori-acf'),
            ]
        );

        $this->add_control(
            'view_all_button_color',
            [
                'label' => __('Warna Teks', 'produk-by-kategori-acf'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .view-all-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'view_all_button_bg_color',
            [
                'label' => __('Warna Latar Belakang', 'produk-by-kategori-acf'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .view-all-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'view_all_button_padding',
            [
                'label' => __('Padding Tombol', 'produk-by-kategori-acf'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .view-all-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'view_all_button_radius',
            [
                'label' => __('Border Radius', 'produk-by-kategori-acf'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .view-all-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );


        $this->end_controls_section();
    }

    public function render()
    {
        $settings  = $this->get_settings_for_display();
        $slug      = $settings['kategori'];
        if ($slug === 'current_post_title') {
            $current_post_id = get_the_ID();
            $current_title = get_the_title($current_post_id);

            $term = get_term_by('name', $current_title, 'product_cat');

            if ($term && !is_wp_error($term)) {
                $slug = $term->slug;
            } else {
                echo '<p style="text-align:center;">Kategori berdasarkan judul "' . esc_html($current_title) . '" tidak ditemukan.</p>';
                return;
            }
        }

        $jumlah    = intval($settings['jumlah']) ?: 8;
        $urutan    = $settings['urutan'] ?: 'DESC';

        $user      = wp_get_current_user();
        $user_role = $user->roles[0] ?? '';

        $meta_query = [
            'relation' => 'OR',
            ['key' => '_visible_for_role', 'compare' => 'NOT EXISTS'],
            ['key' => '_visible_for_role', 'value' => '', 'compare' => '='],
        ];

        if ($user_role) {
            if ($user_role === 'mitra') {
                $meta_query[] = [
                    'relation' => 'OR',
                    ['key' => '_visible_for_role', 'value' => 'mitra', 'compare' => '='],
                    ['key' => '_visible_for_role', 'value' => 'user', 'compare' => '='],
                ];
            } else {
                $meta_query[] = [
                    'key'     => '_visible_for_role',
                    'value'   => $user_role,
                    'compare' => '=',
                ];
            }
        }

        // Gunakan properti static class, bukan variabel static lokal
        $global_ids = &self::$rendered_product_ids;

        if (!empty($slug)) {
            $args = [
                'post_type'      => 'product',
                'posts_per_page' => $jumlah,
                'orderby'        => 'date',
                'order'          => $urutan,
                'meta_query'     => current_user_can('administrator') ? [] : $meta_query,
                'tax_query'      => [[
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => [$slug],
                ]],
                'no_found_rows'  => true,
                'cache_results'  => false,
            ];

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                $term = get_term_by('slug', $slug, 'product_cat');
                if ($term && !is_wp_error($term) && $settings['kategori'] !== 'current_post_title') {
                    echo '<h2 class="produk-kategori-heading" style="text-align:center; margin-bottom:40px;">' . esc_html($term->name) . '</h2>';
                }


                echo '<div class="woocommerce columns-4">';
                woocommerce_product_loop_start();

                global $post;
                while ($query->have_posts()) {
                    $query->the_post();
                    $pid = get_the_ID();

                    if (in_array($pid, $global_ids)) continue;

                    $global_ids[] = $pid;
                    $post = get_post($pid);
                    setup_postdata($post);
                    wc_get_template_part('content', 'product');
                }

                woocommerce_product_loop_end();
                echo '</div>';
                $term_link = get_term_link($slug, 'product_cat');
                if (!is_wp_error($term_link)) {
                    $button_text = $settings['view_all_button_text'] ?: __('View All Products', 'produk-by-kategori-acf');

                    echo '<div style="text-align:center;margin-top:20px;">';
                    echo '<a href="' . esc_url($term_link) . '" class="view-all-button">' . esc_html($button_text) . '</a>';
                    echo '</div>';
                }
            } else {
                echo '<p style="text-align:center;">Produk tidak ditemukan.</p>';
            }
            wp_reset_postdata();
        }

        // Kalau semua kategori
        else {
            $args = [
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => $urutan,
                'meta_query'     => current_user_can('administrator') ? [] : $meta_query,
                'no_found_rows'  => true,
                'cache_results'  => false,
            ];

            $query = new WP_Query($args);
            $grouped_products = [];

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $pid = get_the_ID();

                    if (in_array($pid, $global_ids)) continue;

                    $categories = get_the_terms($pid, 'product_cat');
                    if (!empty($categories) && !is_wp_error($categories)) {
                        $cat = $categories[0];
                        $grouped_products[$cat->term_id]['term'] = $cat;
                        $grouped_products[$cat->term_id]['products'][] = $pid;
                        $global_ids[] = $pid;
                    }
                }
                wp_reset_postdata();

                foreach ($grouped_products as $group) {
                    $term = $group['term'];
                    $product_ids = array_slice($group['products'], 0, $jumlah);

                    echo '<h2 class="produk-kategori-heading" style="text-align:center; margin:30px 0 20px;">' . esc_html($term->name) . '</h2>';
                    echo '<div class="woocommerce columns-4">';
                    woocommerce_product_loop_start();

                    global $post;
                    foreach ($product_ids as $pid) {
                        $post = get_post($pid);
                        setup_postdata($post);
                        wc_get_template_part('content', 'product');
                    }

                    woocommerce_product_loop_end();
                    echo '</div>';

                    $term_link = get_term_link($term);
                    if (!is_wp_error($term_link)) {
                        $button_text = $settings['view_all_button_text'] ?: __('View All Products', 'produk-by-kategori-acf');

                        echo '<div style="text-align:center;margin-top:20px;">';
                        echo '<a href="' . esc_url($term_link) . '" class="view-all-button">' . esc_html($button_text) . '</a>';
                        echo '</div>';
                    }
                }
            } else {
                echo '<p style="text-align:center;">Produk tidak ditemukan.</p>';
            }
            wp_reset_postdata();
        }
    }
}
