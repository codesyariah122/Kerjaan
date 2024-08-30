<?php
// @author: Puji Ermanto <pujiermanto@gmail.com>
// Endpoint Post Types

function register_dealership_endpoint() {
    register_rest_route('custom/v1', '/dealerships', array(
        'methods'  => 'GET',
        'callback' => 'get_dealerships_data',
    ));
}
add_action('rest_api_init', 'register_dealership_endpoint');

function get_dealerships_data() {
    $args = array(
        'post_type' => 'dealerships',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    $dealerships = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $dealerships[] = array(
                'title' => get_the_title(),
                'lat'   => get_post_meta(get_the_ID(), 'location_latitude', true),
                'lng'   => get_post_meta(get_the_ID(), 'location_longitude', true),
                'address' => get_post_meta(get_the_ID(), 'address', true),
            );
        }
    }

    wp_reset_postdata();
    return $dealerships;
}


// Car model endpoint
function register_carmodels_endpoint() {
    register_rest_route('custom/v1', '/car-model', array(
        'methods'  => 'GET',
        'callback' => 'get_carmodels_data',
    ));
}
add_action('rest_api_init', 'register_carmodels_endpoint');

function get_carmodels_data() {
    $args = array(
        'post_type' => 'car-model',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    $carmodels = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $carmodels[] = array(
                'title' => get_the_title(),
                'images'   => get_post_meta(get_the_ID(), 'images', true)
            );
        }
    }

    wp_reset_postdata();
    return $carmodels;
}
