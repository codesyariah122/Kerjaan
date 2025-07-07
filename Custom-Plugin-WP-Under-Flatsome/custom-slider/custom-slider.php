<?php

/**
 * Plugin Name: Custom Slider Plugin
 * Description: A custom slider with custom post type and Swiper.js integration.
 * Version: 1.0
 * Author: <a href="https://pujiermanto-portfolio.vercel.app">Puji Ermanto<puji_dev@websiteaing.com> | AKA Joni Kemod | AKA BRANDALS | AKA Dadang Yang Sebenarnya | AKA Mamang gitu loh</a>
 */

include_once plugin_dir_path(__FILE__) . 'post-type.php';
include_once plugin_dir_path(__FILE__) . 'shortcode.php';

function cs_enqueue_assets()
{
    wp_enqueue_style('swiper', 'https://unpkg.com/swiper@9/swiper-bundle.min.css');
    wp_enqueue_style('cs-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_script('swiper', 'https://unpkg.com/swiper@9/swiper-bundle.min.js', [], false, true);
    wp_enqueue_script('cs-swiper', plugin_dir_url(__FILE__) . 'assets/js/swiper-init.js', ['swiper'], false, true);
}
add_action('wp_enqueue_scripts', 'cs_enqueue_assets');
