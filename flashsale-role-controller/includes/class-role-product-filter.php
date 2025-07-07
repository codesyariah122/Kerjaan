<?php
class FRC_Role_Product_Filter
{
    public function __construct()
    {
        // Tambah field "Visible For Role" di edit produk
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_custom_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_custom_field']);

        // Filter produk di archive/shop/homepage
        add_action('pre_get_posts', [$this, 'filter_products_by_role']);
        add_action('woocommerce_product_query', [$this, 'filter_products_in_loop']);

        // Batasi akses ke halaman single product
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
            $meta_query = $this->build_meta_query();
            $query->set('meta_query', $meta_query);
        }
    }

    public function filter_products_in_loop($q)
    {
        $meta_query = $q->get('meta_query') ?: [];
        $meta_query = array_merge($meta_query, $this->build_meta_query());
        $q->set('meta_query', $meta_query);
    }

    private function build_meta_query()
    {
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
            // Guest hanya bisa lihat produk umum & produk untuk user
            $meta_query[] = [
                'key'     => '_visible_for_role',
                'value'   => 'user',
                'compare' => '='
            ];
        }

        return $meta_query;
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

            if ($visible_role === 'user') {
                // Jika tidak login, redirect login
                if (!is_user_logged_in()) {
                    wp_redirect(wp_login_url(get_permalink($product_id)));
                    exit;
                }

                $user = wp_get_current_user();
                if (!in_array('user', $user->roles)) {
                    wp_redirect(home_url());
                    exit;
                }
            }
        }
    }
}
