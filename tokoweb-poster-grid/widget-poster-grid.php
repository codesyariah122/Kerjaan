<?php

/**
 * Plugin Name: Tokoweb Poster Grid
 * Description: Plugin untuk menampilkan grid poster (gambar + judul) menggunakan Custom Post Type dan widget Elementor.
 * Version: 1.0
 * Author: Puji Ermanto <pujiermanto@gmail.com> | AKA Sumpena | AKA Deden Iynyuuss
 */

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class Tokoweb_Elementor_Poster_Grid extends Widget_Base
{
    public function get_name()
    {
        return 'tokoweb_poster_grid';
    }

    public function get_title()
    {
        return __('Tokoweb Poster Grid', 'tokoweb');
    }

    public function get_icon()
    {
        return 'eicon-posts-grid';
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
                'label' => __('Konten', 'tokoweb'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Jumlah Poster', 'tokoweb'),
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
            ]
        );

        $this->end_controls_section();
    }

    public function render()
    {
        $settings = $this->get_settings_for_display();

        $query = new WP_Query([
            'post_type' => 'tokoweb_poster',
            'posts_per_page' => $settings['posts_per_page'],
        ]);

        if ($query->have_posts()) {
            echo '<div class="tokoweb-poster-grid">';
            while ($query->have_posts()) {
                $query->the_post();
                $thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                echo '<div class="poster-item">';
                if ($thumb) {
                    echo '<img src="' . esc_url($thumb) . '" alt="' . esc_attr(get_the_title()) . '">';
                }
                echo '<h4 class="poster-title">' . get_the_title() . '</h4>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>Tidak ada poster ditemukan.</p>';
        }

        wp_reset_postdata();
    }
}
