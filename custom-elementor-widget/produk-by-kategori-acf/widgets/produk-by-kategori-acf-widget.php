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

if (!defined('ABSPATH')) exit;

class Produk_By_Kategori_ACF_Widget extends Widget_Base
{
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

        $options = ['' => 'Semua Kategori'];
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

        $this->end_controls_section();
    }

    public function render()
    {
        $settings  = $this->get_settings_for_display();
        $slug      = $settings['kategori'];
        $jumlah    = intval($settings['jumlah']) ?: 8;
        $urutan    = $settings['urutan'] ?: 'DESC';

        $user = wp_get_current_user();
        $user_role = $user->roles[0] ?? '';

        // Role-based filter
        $meta_query = [
            'relation' => 'OR',
            [
                'key'     => '_visible_for_role',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => '_visible_for_role',
                'value'   => '',
                'compare' => '=',
            ],
        ];

        if ($user_role) {
            if ($user_role === 'mitra') {
                $meta_query[] = [
                    'relation' => 'OR',
                    [
                        'key'     => '_visible_for_role',
                        'value'   => 'mitra',
                        'compare' => '=',
                    ],
                    [
                        'key'     => '_visible_for_role',
                        'value'   => 'user',
                        'compare' => '=',
                    ],
                ];
            } else {
                $meta_query[] = [
                    'key'     => '_visible_for_role',
                    'value'   => $user_role,
                    'compare' => '=',
                ];
            }
        }

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $jumlah,
            'orderby'        => 'date',
            'order'          => $urutan,
            'meta_query'     => $meta_query,
        ];

        if (!empty($slug)) {
            $args['tax_query'] = [[
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $slug,
            ]];
        }

        if (current_user_can('administrator')) {
            unset($args['meta_query']); // biar admin lihat semua
        }

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            echo '<div class="woocommerce columns-4">';
            woocommerce_product_loop_start();
            while ($query->have_posts()) {
                $query->the_post();
                wc_get_template_part('content', 'product');
            }
            woocommerce_product_loop_end();
            echo '</div>';
        } else {
            echo '<p style="text-align:center;">Produk tidak ditemukan.</p>';
        }

        wp_reset_postdata();
    }
}
