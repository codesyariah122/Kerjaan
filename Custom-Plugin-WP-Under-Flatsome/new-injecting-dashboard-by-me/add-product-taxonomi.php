<?php
function register_product_service_taxonomy()
{
    register_taxonomy('service_category', 'product', array(
        'labels' => array(
            'name' => 'Service',
            'singular_name' => 'Service',
            'search_items' => 'Cari Service',
            'all_items' => 'Semua Service',
            'edit_item' => 'Edit Service',
            'update_item' => 'Update Service',
            'add_new_item' => 'Tambah Service Baru',
            'new_item_name' => 'Nama Service Baru',
            'menu_name' => 'Service'
        ),
        'hierarchical' => true, // ini diubah dari false ke true
        'show_ui' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'product-service'),
    ));
}
add_action('init', 'register_product_service_taxonomy');
