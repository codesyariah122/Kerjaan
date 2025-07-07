<?php
function load_fontawesome_css()
{
    wp_enqueue_style(
        'fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        [],
        '6.5.2'
    );
}
add_action('wp_enqueue_scripts', 'load_fontawesome_css');
