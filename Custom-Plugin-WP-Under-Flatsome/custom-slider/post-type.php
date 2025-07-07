<?php
function cs_register_slider_post_type() {
    register_post_type('cs_slider', [
        'labels' => [
            'name' => 'Custom Sliders',
            'singular_name' => 'Slider Item'
        ],
        'public' => true,
        'supports' => ['title', 'thumbnail'],
        'menu_icon' => 'dashicons-images-alt2'
    ]);
}
add_action('init', 'cs_register_slider_post_type');
?>
