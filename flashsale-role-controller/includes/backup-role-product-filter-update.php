<?php
class FRC_Role_Product_Filter
{
    public function __construct()
    {
        // Tambah field "Visible For Role" di edit produk
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_custom_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_custom_field']);

        // Filter produk di shop/archive
        add_action('pre_get_posts', [$this, 'filter_products_by_role']);

        // Batasi akses ke halaman produk tunggal (single product)
        add_action('template_redirect', [$this, 'restrict_single_product_access']);
    }

    public function add_custom_field()
    {
        woocommerce_wp_select([
            'id' => '_visible_for_role',
            'label' => 'Visible For Role',
            'options' => [
                '' => 'Semua',
                'user' => 'User',
                'mitra' => 'Mitra'
            ]
        ]);
    }

    public function save_custom_field($post_id)
    {
        if (isset($_POST['_visible_for_role'])) {
            update_post_meta($post_id, '_visible_for_role', sanitize_text_field($_POST['_visible_for_role']));
        }
    }

    public function filter_products_by_role($query)
    {
        if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_category() || is_product_tag())) {
            error_log('Main query? ' . ($query->is_main_query() ? 'YA' : 'TIDAK'));
            $meta_query = [
                'relation' => 'OR',
                [
                    'key'     => '_visible_for_role',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key'     => '_visible_for_role',
                    'value'   => '',
                    'compare' => '='
                ]
            ];

            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                $meta_query[] = [
                    'key'     => '_visible_for_role',
                    'value'   => $user->roles[0],
                    'compare' => '='
                ];
            } else {
                // Untuk user tidak login, hanya produk kosong atau 'user' yang boleh muncul
                $meta_query[] = [
                    'key'     => '_visible_for_role',
                    'value'   => 'user',
                    'compare' => '='
                ];
            }

            $query->set('meta_query', $meta_query);
        }
    }

    public function restrict_single_product_access()
    {
        if (is_product()) {
            $product_id = get_the_ID();
            $visible_role = get_post_meta($product_id, '_visible_for_role', true);

            if ($visible_role === 'mitra') {
                if (!is_user_logged_in()) {
                    wp_redirect(wp_login_url(get_permalink($product_id)));
                    exit;
                }

                $user = wp_get_current_user();
                if (!in_array('mitra', $user->roles)) {
                    wp_redirect(home_url());
                    exit;
                }
            }

            // Produk 'user' atau '' tetap bisa dibuka oleh siapa saja (Optional Rule)
            // if ($visible_role === 'user' && !is_user_logged_in()) {
            //     wp_redirect(wp_login_url(get_permalink($product_id)));
            //     exit;
            // }
        }
    }
}



# 2
// <?php
// class FRC_Role_Product_Filter
// {
//     public function __construct()
//     {
//         add_action('woocommerce_product_options_general_product_data', [$this, 'add_custom_field']);
//         add_action('woocommerce_process_product_meta', [$this, 'save_custom_field']);
//         add_action('pre_get_posts', [$this, 'filter_products_by_role']);

//         // Tambahkan action untuk cek akses single product
//         add_action('template_redirect', [$this, 'restrict_single_product_access']);
//     }

//     public function add_custom_field()
//     {
//         woocommerce_wp_select([
//             'id' => '_visible_for_role',
//             'label' => 'Visible For Role',
//             'options' => [
//                 '' => 'Semua',
//                 'user' => 'User',
//                 'mitra' => 'Mitra'
//             ]
//         ]);
//     }

//     public function save_custom_field($post_id)
//     {
//         if (isset($_POST['_visible_for_role'])) {
//             update_post_meta($post_id, '_visible_for_role', sanitize_text_field($_POST['_visible_for_role']));
//         }
//     }

//     public function filter_products_by_role($query)
//     {
//         if (!is_admin() && $query->is_main_query() && is_shop()) {
//             if (is_user_logged_in()) {
//                 $user = wp_get_current_user();
//                 $role = $user->roles[0];
//                 $meta_query = [
//                     'relation' => 'OR',
//                     [
//                         'key' => '_visible_for_role',
//                         'compare' => 'NOT EXISTS'
//                     ],
//                     [
//                         'key' => '_visible_for_role',
//                         'value' => $role,
//                         'compare' => '='
//                     ]
//                 ];
//                 $query->set('meta_query', $meta_query);
//             }
//         }
//     }

//     public function restrict_single_product_access()
//     {
//         if (is_product()) {
//             $product_id = get_the_ID();
//             $visible_role = get_post_meta($product_id, '_visible_for_role', true);

//             if ($visible_role && $visible_role !== '') {
//                 if (!is_user_logged_in()) {
//                     wp_redirect(wp_login_url(get_permalink($product_id)));
//                     exit;
//                 }

//                 $user = wp_get_current_user();
//                 if (!in_array($visible_role, $user->roles)) {
//                     wp_redirect(home_url());
//                     exit;
//                 }
//             }
//         }
//     }
// }


#1
// <?php
// class FRC_Role_Product_Filter
// {
//     public function __construct()
//     {
//         add_action('woocommerce_product_options_general_product_data', [$this, 'add_custom_field']);
//         add_action('woocommerce_process_product_meta', [$this, 'save_custom_field']);
//         add_action('pre_get_posts', [$this, 'filter_products_by_role']);
//     }

//     public function add_custom_field()
//     {
//         woocommerce_wp_select([
//             'id' => '_visible_for_role',
//             'label' => 'Visible For Role',
//             'options' => [
//                 '' => 'Semua',
//                 'user' => 'User',
//                 'mitra' => 'Mitra'
//             ]
//         ]);
//     }

//     public function save_custom_field($post_id)
//     {
//         if (isset($_POST['_visible_for_role'])) {
//             update_post_meta($post_id, '_visible_for_role', sanitize_text_field($_POST['_visible_for_role']));
//         }
//     }

//     public function filter_products_by_role($query)
//     {
//         if (!is_admin() && $query->is_main_query() && is_shop()) {
//             if (is_user_logged_in()) {
//                 $user = wp_get_current_user();
//                 $role = $user->roles[0];
//                 $meta_query = [
//                     'relation' => 'OR',
//                     [
//                         'key' => '_visible_for_role',
//                         'compare' => 'NOT EXISTS'
//                     ],
//                     [
//                         'key' => '_visible_for_role',
//                         'value' => $role,
//                         'compare' => '='
//                     ]
//                 ];
//                 $query->set('meta_query', $meta_query);
//             }
//         }
//     }
// }
