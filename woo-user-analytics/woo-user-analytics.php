<?php

/**
 * Plugin Name: WooCommerce User Analytics
 * Description: Menambahkan laporan data pengguna di WooCommerce Analytics (versi halaman PHP langsung).
 * Version: 1.2
 * Author: Puji Ermanto <pujiermanto@gmail.com> | AKA Dadang Sukamenak
 * Author URI: https://pujiermanto-portfolio.vercel.app
 */

if (!defined('ABSPATH')) exit;

// Tambahkan menu di WooCommerce -> Analytics
add_action('admin_menu', function () {
    add_submenu_page(
        'woocommerce',
        __('User Analytics', 'woo-user-analytics'),
        __('User Analytics', 'woo-user-analytics'),
        'view_woocommerce_reports',
        'wc-user-analytics',
        'wcua_render_page'
    );
    // Submenu baru untuk Order Analytics
    add_submenu_page(
        'woocommerce',
        __('User Order Analytics', 'woo-user-analytics'),
        __('User Order Analytics', 'woo-user-analytics'),
        'view_woocommerce_reports',
        'wc-user-order-analytics',
        'wcua_render_order_page'
    );
});


// Tambahkan ke daftar menu Analytics di WooCommerce Admin (SPA)
// add_filter('woocommerce_analytics_report_menu_items', function ($report_pages) {
//     $report_pages['user-analytics'] = array(
//         'title' => __('User Analytics', 'woo-user-analytics'),
//         'parent' => 'woocommerce-analytics',
//         'path' => admin_url('admin.php?page=wc-user-analytics'),
//     );
//     return $report_pages;
// });

function wcua_render_order_page()
{
?>
    <div class="wrap">
        <h1><?php _e('User Order Analytics', 'woo-user-analytics'); ?></h1>
        <canvas id="userOrderAnalyticsChart" style="max-width:800px;"></canvas>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            fetch('<?php echo esc_url(rest_url('wc-analytics/v1/user-order-analytics')); ?>', {
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    const labels = data.map(row => row.date);
                    const counts = data.map(row => parseInt(row.order_count));
                    new Chart(document.getElementById('userOrderAnalyticsChart'), {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Jumlah Order Per Hari',
                                data: counts,
                                borderColor: 'rgba(255, 99, 132, 1)',
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            }
                        }
                    });
                });
        </script>
    </div>
<?php
}


function wcua_render_page()
{
?>
    <div class="wrap">
        <h1><?php _e('User Analytics', 'woo-user-analytics'); ?></h1>
        <canvas id="userAnalyticsChart" style="max-width:800px;"></canvas>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            fetch('<?php echo esc_url(rest_url('wc-analytics/v1/user-analytics')); ?>', {
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    const labels = data.map(row => row.date);
                    const counts = data.map(row => row.count);
                    new Chart(document.getElementById('userAnalyticsChart'), {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'User Baru',
                                data: counts,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            }
                        }
                    });
                });
        </script>
    </div>
<?php
}

// REST API endpoint untuk data user baru
add_action('rest_api_init', function () {
    register_rest_route('wc-analytics/v1', '/user-analytics', array(
        'methods' => 'GET',
        'callback' => 'wcua_get_user_data',
        'permission_callback' => '__return_true'
    ));
});

function wcua_get_user_data()
{
    global $wpdb;
    $results = $wpdb->get_results("
        SELECT DATE(user_registered) as date, COUNT(*) as count
        FROM {$wpdb->users}
        GROUP BY DATE(user_registered)
        ORDER BY date ASC
    ");
    return rest_ensure_response($results);
}

// REST API endpoint untuk data order users
add_action('rest_api_init', function () {
    register_rest_route('wc-analytics/v1', '/user-order-analytics', array(
        'methods' => 'GET',
        'callback' => 'wcua_get_user_order_data',
        'permission_callback' => '__return_true'
    ));
});

function wcua_get_user_order_data()
{
    global $wpdb;

    // Query untuk ambil jumlah order per tanggal, filter hanya order yang sudah selesai (status 'completed')
    $results = $wpdb->get_results("
        SELECT DATE(post_date) as date, COUNT(ID) as order_count
        FROM {$wpdb->prefix}posts
        WHERE post_type = 'shop_order' 
          AND post_status = 'wc-completed'
        GROUP BY DATE(post_date)
        ORDER BY date ASC
    ");

    return rest_ensure_response($results);
}


// Tracking last login
add_action('wp_login', function ($user_login) {
    $user = get_user_by('login', $user_login);
    if ($user) {
        update_user_meta($user->ID, 'last_login', current_time('mysql'));
    }
}, 10, 1);
