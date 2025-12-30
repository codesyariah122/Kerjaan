<?php
/*
Plugin Name: Tutor Paid Topic Addon V2
Description: Inject harga per topic langsung di Course Builder Tutor LMS 3.x+ (React SPA) dan otomatis buat WooCommerce Product
Version: 2.3
Author: Puji Ermanto
*/

if (!defined('ABSPATH')) exit;

// =====================
// 1️⃣ Load JS admin (fix untuk Tutor React Builder)
// =====================
add_action('admin_enqueue_scripts', function ($hook) {
    $is_tutor_builder = isset($_GET['page']) && (
        $_GET['page'] === 'tutor-course-builder' ||
        $_GET['page'] === 'tutor' ||
        strpos($hook, 'tutor') !== false
    );

    if ($is_tutor_builder) {
        wp_enqueue_script(
            'tpt-admin-js',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            ['jquery'],
            time(), // agar tidak kena cache
            true
        );
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'
        );
        wp_localize_script('tpt-admin-js', 'TPT_Ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('tpt_nonce')
        ]);
    }
});

/**
 * 2️⃣ AJAX - Simpan harga per topic & buat produk WooCommerce
 */
add_action('wp_ajax_tpt_save_price', function () {
    check_ajax_referer('tpt_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

    $title     = sanitize_text_field($_POST['title']);
    $course_id = intval($_POST['course_id']);
    $price     = intval($_POST['price']);

    if (!$title || !$course_id) wp_send_json_error('Invalid data');

    global $wpdb;
    $posts_table = $wpdb->prefix . 'posts';

    $topic_post_id = null;
    $attempts = 0;

    while (!$topic_post_id && $attempts < 3) {
        // cari topic langsung di course
        $topic_post_id = $wpdb->get_var($wpdb->prepare("
            SELECT ID FROM $posts_table
            WHERE post_title = %s
            AND post_parent = %d
            AND post_type IN ('topics','topic')
            LIMIT 1
        ", $title, $course_id));

        // cari lewat section parent kalau belum ketemu
        if (!$topic_post_id) {
            $topic_post_id = $wpdb->get_var($wpdb->prepare("
                SELECT t1.ID
                FROM {$wpdb->posts} t1
                INNER JOIN {$wpdb->posts} t2 ON t1.post_parent = t2.ID
                WHERE t1.post_title = %s
                AND t2.post_parent = %d
                AND t1.post_type IN ('topics','topic')
                LIMIT 1
            ", $title, $course_id));
        }

        if (!$topic_post_id) {
            usleep(300000);
            $attempts++;
        }
    }

    if (!$topic_post_id) wp_send_json_error('Topic not found in DB');

    update_post_meta($topic_post_id, '_tpt_price', $price);

    // buat / update produk WooCommerce
    $wc_id = get_post_meta($topic_post_id, '_tpt_wc_id', true);
    if (!$wc_id) {
        $title = get_the_title($topic_post_id);
        $product = new WC_Product_Simple();
        $product->set_name($title);
        $product->set_regular_price($price);
        $product->set_status('publish'); // ✅ publish agar muncul di WooCommerce
        $product->set_catalog_visibility('hidden');
        $product->save();

        update_post_meta($topic_post_id, '_tpt_wc_id', $product->get_id());
        update_post_meta($product->get_id(), '_tpt_topic_id', $topic_post_id);
        $wc_id = $product->get_id();
    } else {
        $product = wc_get_product($wc_id);
        if ($product) {
            $product->set_regular_price($price);
            $product->set_status('publish'); // pastikan tetap publish
            $product->save();
        }
    }

    wp_send_json_success([
        'topic_id' => $topic_post_id,
        'price'    => $price,
        'wc_id'    => $wc_id,
    ]);
});


/**
 * 🔧  Fix Tutor LMS "Price is required" error
 *  Inject dummy pricing before Tutor validates input
 */
add_filter('tutor_course_builder_save_data_before_validation', function ($data) {
    // Jika tidak ada field pricing dari React, tambahkan dummy
    if (empty($data['pricing']) || empty($data['pricing']['regular_price'])) {

        // Cari harga topic pertama (kalau ada)
        if (!empty($data['course_id'])) {
            global $wpdb;
            $topics = $wpdb->get_results($wpdb->prepare("
                SELECT ID FROM {$wpdb->posts}
                WHERE post_parent = %d
                AND post_type IN ('topics','topic')
                ORDER BY menu_order ASC LIMIT 1
            ", intval($data['course_id'])));

            if ($topics) {
                $first_topic_id = $topics[0]->ID;
                $first_price = intval(get_post_meta($first_topic_id, '_tpt_price', true));
                if ($first_price > 0) {
                    $data['pricing']['regular_price'] = $first_price;
                } else {
                    $data['pricing']['regular_price'] = 0;
                }
            } else {
                $data['pricing']['regular_price'] = 0;
            }
        } else {
            $data['pricing']['regular_price'] = 0;
        }
    }

    return $data;
}, 10, 1);


// =====================
// 3️⃣ Hook: buat/update WC product setelah course disimpan
// =====================
add_action('tutor_course_after_save', function ($course_id, $request_data) {
    $topics = tutor_utils()->get_course_topics($course_id);

    foreach ($topics as $topic_id) {
        $price = get_post_meta($topic_id, '_tpt_price', true);
        if (!$price) continue;

        $wc_id = get_post_meta($topic_id, '_tpt_wc_id', true);

        if (!$wc_id) {
            $title = get_the_title($topic_id);
            if (!$title) continue;

            $product = new WC_Product_Simple();
            $product->set_name($title);
            $product->set_regular_price($price);
            $product->set_catalog_visibility('hidden');
            $product->save();

            update_post_meta($topic_id, '_tpt_wc_id', $product->get_id());
            update_post_meta($product->get_id(), '_tpt_topic_id', $topic_id);
        } else {
            $product = wc_get_product($wc_id);
            if ($product) $product->set_regular_price($price)->save();
        }
    }
}, 10, 2);

// =====================
// 4️⃣ AJAX get price
// =====================
add_action('wp_ajax_tpt_get_price', function () {
    check_ajax_referer('tpt_nonce', 'nonce');

    $title     = sanitize_text_field($_POST['title']);
    $course_id = intval($_POST['course_id']);

    global $wpdb;
    $posts_table = $wpdb->prefix . 'posts';

    $topic_post_id = $wpdb->get_var($wpdb->prepare("
        SELECT ID FROM $posts_table
        WHERE post_title = %s
        AND post_parent = %d
        AND post_type IN ('topics','topic')
        LIMIT 1
    ", $title, $course_id));

    if (!$topic_post_id) {
        $topic_post_id = $wpdb->get_var($wpdb->prepare("
            SELECT l.ID
            FROM $posts_table l
            INNER JOIN $posts_table t ON t.ID = l.post_parent
            WHERE l.post_title = %s
            AND t.post_parent = %d
            AND l.post_type = 'lesson'
            LIMIT 1
        ", $title, $course_id));
    }

    $price = $topic_post_id ? get_post_meta($topic_post_id, '_tpt_price', true) : 0;
    wp_send_json_success([
        'price'    => intval($price),
        'topic_id' => intval($topic_post_id)
    ]);
});

// =====================
// 5️⃣ AJAX add to cart
// =====================
add_action('wp_ajax_tpt_add_to_cart', 'tpt_add_to_cart');
add_action('wp_ajax_nopriv_tpt_add_to_cart', 'tpt_add_to_cart');
function tpt_add_to_cart()
{
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) wp_send_json_error('Invalid product');

    if (WC()->cart->add_to_cart($product_id)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to add to cart');
    }
}

// =====================
// 6️⃣ Update completed topic saat order completed
// =====================
add_action('woocommerce_order_status_completed', function ($order_id) {
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();

    foreach ($order->get_items() as $item) {
        $topic_id = get_post_meta($item->get_product_id(), '_tpt_topic_id', true);
        if ($topic_id) {
            $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
            if (!in_array($topic_id, $completed)) {
                $completed[] = $topic_id;
                update_user_meta($user_id, '_tpt_completed_topics', $completed);
            }
        }
    }
});

// =====================
// 7️⃣ REST API: get all topic prices
// =====================
add_action('rest_api_init', function () {
    register_rest_route('tpt/v1', '/get-topic-prices', [
        'methods'  => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $course_id = intval($request->get_param('course_id'));
            if (!$course_id) return new WP_REST_Response([
                'status_code' => 400,
                'message'     => 'Missing or invalid course_id',
            ], 400);

            global $wpdb;

            $topics = $wpdb->get_results($wpdb->prepare("
                SELECT p.ID, p.post_title
                FROM {$wpdb->posts} p
                WHERE p.post_parent IN (
                    SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d
                )
                AND p.post_type IN ('lesson','topic')
                AND p.post_status = 'publish'
                ORDER BY p.menu_order ASC
            ", $course_id));

            if (!$topics) return new WP_REST_Response([
                'status_code' => 404,
                'message'     => 'No topics found for this course',
            ], 404);

            $data = [];
            $prices = [];
            foreach ($topics as $topic) {
                $price = intval(get_post_meta($topic->ID, '_tpt_price', true) ?: 0);
                $data[] = ['id' => intval($topic->ID), 'title' => $topic->post_title, 'price' => $price];
                if ($price > 0) $prices[] = $price;
            }

            return new WP_REST_Response([
                'status_code' => 200,
                'message'     => 'Course contents fetched successfully',
                'data'        => [
                    'course_id' => $course_id,
                    'topics'    => $data,
                    'price_min' => $prices ? min($prices) : 0,
                    'price_max' => $prices ? max($prices) : 0
                ]
            ], 200);
        },
        'permission_callback' => '__return_true'
    ]);
});

// =====================
// 8️⃣ Frontend: inject Buy Topic button dengan loader
// =====================
add_action('wp_footer', function () {
    if (!is_singular('courses')) return;
    $completed = get_user_meta(get_current_user_id(), '_tpt_completed_topics', true) ?: [];
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const userCompleted = <?php echo json_encode($completed); ?>;

            document.querySelectorAll('.tutor-accordion-item').forEach(item => {
                const topicId = parseInt(item.dataset.topicId);
                const wcId = parseInt(item.dataset.wcId);
                if (!wcId || !topicId) return;

                const prevTopicId = item.dataset.prevTopicId ? parseInt(item.dataset.prevTopicId) : 0;
                const canBuy = !prevTopicId || userCompleted.includes(prevTopicId);

                const btn = document.createElement('button');
                btn.textContent = canBuy ? "Buy Topic" : "Selesaikan Bab sebelumnya";
                btn.disabled = !canBuy;
                btn.className = "tpt-btn-buy-topic";
                Object.assign(btn.style, {
                    marginTop: "8px",
                    padding: "6px 18px",
                    borderRadius: "6px",
                    border: "none",
                    cursor: canBuy ? "pointer" : "not-allowed",
                    background: canBuy ? "#ED2D56" : "#ccc",
                    color: "#fff",
                    fontWeight: "600"
                });

                if (canBuy) {
                    btn.addEventListener('click', () => {
                        const originalText = btn.textContent;
                        btn.textContent = "Loading...";
                        btn.disabled = true;
                        jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                            action: "tpt_add_to_cart",
                            product_id: wcId
                        }).done(() => {
                            btn.textContent = "Added!";
                            btn.style.background = "#999";
                        }).fail(() => {
                            btn.textContent = originalText;
                            btn.disabled = false;
                        });
                    });
                }

                item.querySelector('.tutor-course-content-list')?.appendChild(btn);
            });
        });
    </script>
<?php
});


// Backup baru tanggal 2 Dec 2025 : 
<?php
/*
Plugin Name: Tutor Paid Topic Addon V2
Description: Inject harga per topic langsung di Course Builder Tutor LMS 3.x+ (React SPA) dan otomatis buat WooCommerce Product
Version: 2.3
Author: Puji Ermanto
*/

if (!defined('ABSPATH')) exit;

// Load frontend module
if (file_exists(plugin_dir_path(__FILE__) . 'module/frontend.php')) {
    require_once plugin_dir_path(__FILE__) . 'module/frontend.php';
}

// Load role & registration service
// foreach (glob(plugin_dir_path(__FILE__) . 'service/*.php') as $service_file) {
//     require_once $service_file;
// }
// =========================================================
// Load service files setelah semua plugin lain siap
// =========================================================
add_action('plugins_loaded', function () {
    $service_path = plugin_dir_path(__FILE__) . 'service/*.php';
    foreach (glob($service_path) as $service_file) {
        require_once $service_file;
    }
}, 20);

// Hanya load manual kalau ingin pastikan ini selalu terakhir
$unlock_file = plugin_dir_path(__FILE__) . 'service/tutor-enroll-unlock.php';
if (file_exists($unlock_file)) {
    require_once $unlock_file;
}

/**
 * 🔁 Patch: Auto Sync Enrolled Courses Cache (ganti template_redirect lama)
 */
// add_action('template_redirect', function () {
//     if (!is_page(['dashboard', 'enrolled-courses'])) return;

//     $user_id = get_current_user_id();
//     if (!$user_id) return;

//     global $wpdb;
//     $table = "{$wpdb->prefix}tutor_enrolled";

//     // Ambil semua course_id yang user sudah enroll
//     $enrolled_course_ids = $wpdb->get_col($wpdb->prepare("
//         SELECT course_id FROM $table WHERE user_id = %d
//     ", $user_id));

//     if (!$enrolled_course_ids) return;

//     // Update tutor_total_enroll
//     update_user_meta($user_id, 'tutor_total_enroll', count($enrolled_course_ids));

//     // Update tutor_enrolled_courses
//     update_user_meta($user_id, 'tutor_enrolled_courses', $enrolled_course_ids);

//     // Update tutor_enrolled_courses_cache
//     $cache_key = 'tutor_enrolled_courses_cache';
//     $cached = [];
//     foreach ($enrolled_course_ids as $course_id) {
//         $cached[$course_id] = [
//             'course_id' => $course_id,
//             'enrolled_date' => current_time('mysql')
//         ];
//     }
//     update_user_meta($user_id, $cache_key, $cached);

//     // Pastikan user role tutor_student
//     $user = get_userdata($user_id);
//     if ($user && !in_array('tutor_student', (array) $user->roles)) {
//         $user->set_role('tutor_student');
//     }

//     error_log("[TPT-FIX] ✅ Auto-sync Tutor enrolled cache untuk user $user_id");
// }, 20);

/**
 * 🎨 Load Cinematic Style for Tutor LMS Lessons
 */
add_action('wp_enqueue_scripts', function () {
    // hanya load di halaman lesson Tutor
    if (is_singular('lesson')) {
        wp_enqueue_style(
            'tpt-cinematic-style',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/style.css') // versi dinamis anti-cache
        );
    }
});

// =====================
// 1️⃣ Load JS admin (fix untuk Tutor React Builder)
// =====================
add_action('admin_enqueue_scripts', function ($hook) {
    $is_tutor_builder = isset($_GET['page']) && (
        $_GET['page'] === 'tutor-course-builder' ||
        $_GET['page'] === 'tutor' ||
        strpos($hook, 'tutor') !== false
    );

    if ($is_tutor_builder) {
        wp_enqueue_script(
            'tpt-admin-js',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            ['jquery'],
            time(), // agar tidak kena cache
            true
        );
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'
        );
        wp_localize_script('tpt-admin-js', 'TPT_Ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('tpt_nonce')
        ]);
    }
});

/**
 * 🎬 Load Cinematic Style + YouTube Player Fix
 */
add_action('wp_enqueue_scripts', function () {
    if (is_singular('lesson')) {
        // CSS cinematic
        wp_enqueue_style(
            'tpt-cinematic-style',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/style.css')
        );

        // JS player auto-convert
        wp_enqueue_script(
            'tpt-player-fix',
            plugin_dir_url(__FILE__) . 'assets/player.js',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/player.js'),
            true
        );
    }
});


/**
 * 2️⃣ AJAX - Simpan harga per topic & buat produk WooCommerce
 */
add_action('wp_ajax_tpt_save_price', function () {
    check_ajax_referer('tpt_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

    $title     = sanitize_text_field($_POST['title']);
    $course_id = intval($_POST['course_id']);
    $price     = intval($_POST['price']);

    if (!$title || !$course_id) wp_send_json_error('Invalid data');

    global $wpdb;
    $posts_table = $wpdb->prefix . 'posts';

    $topic_post_id = null;
    $attempts = 0;

    while (!$topic_post_id && $attempts < 3) {
        // cari topic langsung di course
        $topic_post_id = $wpdb->get_var($wpdb->prepare("
            SELECT ID FROM $posts_table
            WHERE post_title = %s
            AND post_parent = %d
            AND post_type IN ('topics','topic')
            LIMIT 1
        ", $title, $course_id));

        // cari lewat section parent kalau belum ketemu
        if (!$topic_post_id) {
            $topic_post_id = $wpdb->get_var($wpdb->prepare("
                SELECT t1.ID
                FROM {$wpdb->posts} t1
                INNER JOIN {$wpdb->posts} t2 ON t1.post_parent = t2.ID
                WHERE t1.post_title = %s
                AND t2.post_parent = %d
                AND t1.post_type IN ('topics','topic')
                LIMIT 1
            ", $title, $course_id));
        }

        if (!$topic_post_id) {
            usleep(300000);
            $attempts++;
        }
    }

    if (!$topic_post_id) wp_send_json_error('Topic not found in DB');

    update_post_meta($topic_post_id, '_tpt_price', $price);

    // buat / update produk WooCommerce
    // $wc_id = get_post_meta($topic_post_id, '_tpt_wc_id', true);

    // $course_title = get_the_title($course_id);
    // $topic_title  = get_the_title($topic_post_id);
    // $product_name = $course_title . ' – ' . $topic_title;

    // if (!$wc_id) {
    //     $product = new WC_Product_Simple();
    //     $product->set_name($product_name);
    //     $product->set_regular_price($price);
    //     $product->set_status('publish'); // publish agar muncul
    //     $product->set_catalog_visibility('hidden');
    //     $product->save();

    //     update_post_meta($topic_post_id, '_tpt_wc_id', $product->get_id());
    //     update_post_meta($product->get_id(), '_tpt_topic_id', $topic_post_id);
    //     update_post_meta($product->get_id(), '_tutor_course_id', $course_id); // 🔹 tambahkan juga course ID

    //     $wc_id = $product->get_id();
    // } else {
    //     $product = wc_get_product($wc_id);
    //     if ($product) {
    //         $product->set_name($product_name);
    //         $product->set_regular_price($price);
    //         $product->set_status('publish');
    //         $product->save();
    //     }
    // }
    // buat / update produk WooCommerce
    $wc_id = get_post_meta($topic_post_id, '_tpt_wc_id', true);

    $course_title = get_the_title($course_id);
    $topic_title  = get_the_title($topic_post_id);
    $product_name = $course_title . ' – ' . $topic_title;

    if (!$wc_id) {
        $product = new WC_Product_Simple();
        $product->set_name($product_name);
        $product->set_regular_price($price);
        $product->set_status('publish'); // publish agar muncul
        $product->set_catalog_visibility('hidden');
        $product->save();

        update_post_meta($topic_post_id, '_tpt_wc_id', $product->get_id());
        update_post_meta($product->get_id(), '_tpt_topic_id', $topic_post_id);
        update_post_meta($product->get_id(), '_tutor_course_id', $course_id); // 🔹 tambahkan juga course ID

        $wc_id = $product->get_id();
    } else {
        $product = wc_get_product($wc_id);
        if ($product) {
            $product->set_name($product_name);
            $product->set_regular_price($price);
            $product->set_status('publish');
            $product->save();
        }
    }


    wp_send_json_success([
        'topic_id' => $topic_post_id,
        'price'    => $price,
        'wc_id'    => $wc_id,
    ]);
});


/**
 * 🔧  Fix Tutor LMS "Price is required" error
 *  Inject dummy pricing before Tutor validates input
 */
add_filter('tutor_course_builder_save_data_before_validation', function ($data) {
    // Jika tidak ada field pricing dari React, tambahkan dummy
    if (empty($data['pricing']) || empty($data['pricing']['regular_price'])) {

        // Cari harga topic pertama (kalau ada)
        if (!empty($data['course_id'])) {
            global $wpdb;
            $topics = $wpdb->get_results($wpdb->prepare("
                SELECT ID FROM {$wpdb->posts}
                WHERE post_parent = %d
                AND post_type IN ('topics','topic')
                ORDER BY menu_order ASC LIMIT 1
            ", intval($data['course_id'])));

            if ($topics) {
                $first_topic_id = $topics[0]->ID;
                $first_price = intval(get_post_meta($first_topic_id, '_tpt_price', true));
                if ($first_price > 0) {
                    $data['pricing']['regular_price'] = $first_price;
                } else {
                    $data['pricing']['regular_price'] = 0;
                }
            } else {
                $data['pricing']['regular_price'] = 0;
            }
        } else {
            $data['pricing']['regular_price'] = 0;
        }
    }

    return $data;
}, 10, 1);

// =====================
// Hook: Save price & WC product
// =====================
add_action('save_post', function ($post_id, $post, $update) {
    if ($post->post_type !== 'tutor_lesson') return;

    $course_id = get_post_meta($post_id, '_tutor_course_id', true);
    if (!$course_id) return;

    $price = isset($_POST['_tpt_price']) ? floatval($_POST['_tpt_price']) : 0;
    update_post_meta($post_id, '_tpt_price', $price);

    // Patch: buat / update WC product unik per course
    tpt_create_or_update_wc_product($post_id, $price, $course_id);
}, 10, 3);

// =====================
// Function: Create / update WooCommerce product per topic
// =====================
// function tpt_create_or_update_wc_product($topic_id, $price, $course_id)
// {
//     if (!$topic_id || !$course_id) return false;

//     $topic_title  = get_the_title($topic_id);
//     $course_title = get_the_title($course_id);

//     $product_name = $course_title . ' – ' . $topic_title;

//     $wc_id = get_post_meta($topic_id, '_tpt_wc_id', true);

//     if (!$wc_id) {
//         $product = new WC_Product_Simple();
//         $product->set_name($product_name);
//         $product->set_regular_price($price);
//         $product->set_status('publish');
//         $product->set_catalog_visibility('hidden');
//         $product->save();

//         update_post_meta($topic_id, '_tpt_wc_id', $product->get_id());
//         update_post_meta($product->get_id(), '_tpt_topic_id', $topic_id);

//         $wc_id = $product->get_id();
//     } else {
//         $product = wc_get_product($wc_id);
//         if ($product) {
//             $product->set_name($product_name);
//             $product->set_regular_price($price);
//             $product->set_status('publish');
//             $product->save();
//         }
//     }

//     return $wc_id;
// }
// Versi patch :
function tpt_create_or_update_wc_product($topic_id, $price, $course_id)
{
    if (!$topic_id || !$course_id) return false;

    $topic_title  = get_the_title($topic_id);
    $course_title = get_the_title($course_id);

    $product_name = $course_title . ' – ' . $topic_title;

    $wc_id = get_post_meta($topic_id, '_tpt_wc_id', true);

    if (!$wc_id) {
        // 🔹 Buat produk baru
        $product = new WC_Product_Simple();
        $product->set_name($product_name);
        $product->set_regular_price($price);
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->save();

        // 🔹 Simpan relasi meta
        update_post_meta($topic_id, '_tpt_wc_id', $product->get_id());
        update_post_meta($product->get_id(), '_tpt_topic_id', $topic_id);
        update_post_meta($product->get_id(), '_tutor_course_id', $course_id); // ✅ Tambahkan baris ini

        $wc_id = $product->get_id();
    } else {
        // 🔹 Update produk lama
        $product = wc_get_product($wc_id);
        if ($product) {
            $product->set_name($product_name);
            $product->set_regular_price($price);
            $product->set_status('publish');
            $product->save();

            // ✅ Pastikan meta selalu sinkron
            update_post_meta($product->get_id(), '_tutor_course_id', $course_id);
            update_post_meta($product->get_id(), '_tpt_topic_id', $topic_id);
        }
    }

    return $wc_id;
}


// =====================
// REST API endpoint: Buy topic
// =====================
add_action('rest_api_init', function () {
    register_rest_route('tutor-paid-topic/v2', '/buy', [
        'methods' => 'POST',
        'callback' => function ($request) {
            $topic_id  = $request->get_param('topic_id');
            $course_id = $request->get_param('course_id');
            $user_id   = get_current_user_id();

            if (!$topic_id || !$course_id || !$user_id) {
                return ['status' => 'error', 'message' => 'Missing parameter'];
            }

            $wc_id = get_post_meta($topic_id, '_tpt_wc_id', true);
            if (!$wc_id) return ['status' => 'error', 'message' => 'WC Product not found'];

            // Tambahkan ke cart
            WC()->cart->add_to_cart($wc_id);

            return ['status' => 'success', 'message' => 'Topic added to cart'];
        },
        'permission_callback' => '__return_true', // <=== ini tambahan
    ]);
});

// add_action('rest_api_init', function () {
//     register_rest_route('tpt/v1', '/user-progress', [
//         'methods' => 'GET',
//         'callback' => function (WP_REST_Request $r) {
//             $user_id = intval($r->get_param('user_id')) ?: get_current_user_id();
//             return [
//                 'completed' => get_user_meta($user_id, '_tpt_completed_topics', true),
//                 'purchased' => get_user_meta($user_id, '_tpt_purchased_topics', true),
//             ];
//         },
//         'permission_callback' => '__return_true'
//     ]);
// });


// =====================
// 4️⃣ AJAX get price
// =====================
add_action('wp_ajax_tpt_get_price', function () {
    check_ajax_referer('tpt_nonce', 'nonce');

    $title     = sanitize_text_field($_POST['title']);
    $course_id = intval($_POST['course_id']);

    global $wpdb;
    $posts_table = $wpdb->prefix . 'posts';

    $topic_post_id = $wpdb->get_var($wpdb->prepare("
        SELECT ID FROM $posts_table
        WHERE post_title = %s
        AND post_parent = %d
        AND post_type IN ('topics','topic')
        LIMIT 1
    ", $title, $course_id));

    if (!$topic_post_id) {
        $topic_post_id = $wpdb->get_var($wpdb->prepare("
            SELECT l.ID
            FROM $posts_table l
            INNER JOIN $posts_table t ON t.ID = l.post_parent
            WHERE l.post_title = %s
            AND t.post_parent = %d
            AND l.post_type = 'lesson'
            LIMIT 1
        ", $title, $course_id));
    }

    $price = $topic_post_id ? get_post_meta($topic_post_id, '_tpt_price', true) : 0;
    wp_send_json_success([
        'price'    => intval($price),
        'topic_id' => intval($topic_post_id)
    ]);
});

// =====================
// 5️⃣ AJAX add to cart
// =====================
add_action('wp_ajax_tpt_add_to_cart', 'tpt_add_to_cart');
add_action('wp_ajax_nopriv_tpt_add_to_cart', 'tpt_add_to_cart');
function tpt_add_to_cart()
{
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) wp_send_json_error('Invalid product');

    if (WC()->cart->add_to_cart($product_id)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to add to cart');
    }
}

// =====================
// 6️⃣ Update completed topic saat order completed
// =====================
add_action('woocommerce_order_status_completed', function ($order_id) {
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();

    foreach ($order->get_items() as $item) {
        $topic_id = get_post_meta($item->get_product_id(), '_tpt_topic_id', true);
        if ($topic_id) {
            $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
            if (!in_array($topic_id, $completed)) {
                $completed[] = $topic_id;
                update_user_meta($user_id, '_tpt_completed_topics', $completed);
            }
        }
    }
});

// =====================
// 7️⃣ REST API: get all topic prices
// =====================
add_action('rest_api_init', function () {
    register_rest_route('tpt/v1', '/get-topic-prices', [
        'methods'  => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $course_id = intval($request->get_param('course_id'));
            if (!$course_id) return new WP_REST_Response([
                'status_code' => 400,
                'message'     => 'Missing or invalid course_id',
            ], 400);

            global $wpdb;

            // Ambil semua topic dengan post_type = 'topics' (fix untuk DB kamu)
            $topics = $wpdb->get_results($wpdb->prepare("
                SELECT ID, post_title
                FROM {$wpdb->posts}
                WHERE post_parent = %d
                AND post_type = 'topics'
                AND post_status = 'publish'
                ORDER BY menu_order ASC
            ", $course_id));

            if (!$topics) return new WP_REST_Response([
                'status_code' => 404,
                'message'     => 'No topics found for this course',
            ], 404);

            $data = [];
            $prices = [];
            foreach ($topics as $topic) {
                $price = intval(get_post_meta($topic->ID, '_tpt_price', true) ?: 0);
                $data[] = [
                    'id'    => intval($topic->ID),
                    'title' => $topic->post_title,
                    'price' => $price
                ];
                if ($price > 0) $prices[] = $price;
            }

            return new WP_REST_Response([
                'status_code' => 200,
                'message'     => 'Course topics fetched successfully',
                'data'        => [
                    'course_id' => $course_id,
                    'topics'    => $data,
                    'price_min' => $prices ? min($prices) : 0,
                    'price_max' => $prices ? max($prices) : 0
                ]
            ], 200);
        },
        'permission_callback' => '__return_true'
    ]);

    // Route tpt-card/v2/get-course-prices
    register_rest_route('tpt-card/v2', '/get-course-prices', [
        'methods' => 'GET',
        'callback' => function ($request) {
            $course_id = $request->get_param('course_id');
            if (!$course_id) return [];

            $topics = tutor_utils()->get_course_topics($course_id);
            $data = [];

            foreach ($topics as $topic) {
                $price = get_post_meta($topic->ID, '_tpt_price', true);
                $data[] = [
                    'topic_id' => $topic->ID,
                    'title' => get_the_title($topic->ID),
                    'price' => $price ? $price : 0
                ];
            }

            return $data;
        },
        'permission_callback' => '__return_true', // <=== ini tambahan
    ]);
});

// =====================
// 8️⃣ Frontend: inject Buy Topic button dengan loader
// =====================
add_action('wp_footer', function () {
    if (!is_singular('courses')) return;
    $completed = get_user_meta(get_current_user_id(), '_tpt_completed_topics', true) ?: [];
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const userCompleted = <?php echo json_encode($completed); ?>;

            document.querySelectorAll('.tutor-accordion-item').forEach(item => {
                const topicId = parseInt(item.dataset.topicId);
                const wcId = parseInt(item.dataset.wcId);
                if (!wcId || !topicId) return;

                const prevTopicId = item.dataset.prevTopicId ? parseInt(item.dataset.prevTopicId) : 0;
                const canBuy = !prevTopicId || userCompleted.includes(prevTopicId);

                const btn = document.createElement('button');
                btn.textContent = canBuy ? "Buy Topic" : "Selesaikan Bab sebelumnya";
                btn.disabled = !canBuy;
                btn.className = "tpt-btn-buy-topic";
                Object.assign(btn.style, {
                    marginTop: "8px",
                    padding: "6px 18px",
                    borderRadius: "6px",
                    border: "none",
                    cursor: canBuy ? "pointer" : "not-allowed",
                    background: canBuy ? "#ED2D56" : "#ccc",
                    color: "#fff",
                    fontWeight: "600"
                });

                if (canBuy) {
                    btn.addEventListener('click', () => {
                        const originalText = btn.textContent;
                        btn.textContent = "Loading...";
                        btn.disabled = true;
                        jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                            action: "tpt_add_to_cart",
                            product_id: wcId
                        }).done(() => {
                            btn.textContent = "Added!";
                            btn.style.background = "#999";
                        }).fail(() => {
                            btn.textContent = originalText;
                            btn.disabled = false;
                        });
                    });
                }

                item.querySelector('.tutor-course-content-list')?.appendChild(btn);
            });
        });
    </script>
    <?php
});


/**
 * 🛠 PATCH: Auto Sync Tutor LMS enrolled courses cache
 * Bisa dipanggil via URL / cron / template_redirect
 */
// add_action('init', function () {
//     global $wpdb;
//     $table_enroll = $wpdb->prefix . 'tutor_enrolled';

//     $user_ids = $wpdb->get_col("SELECT DISTINCT user_id FROM $table_enroll");
//     if (!$user_ids) return;

//     foreach ($user_ids as $user_id) {
//         $enrolled_course_ids = $wpdb->get_col($wpdb->prepare("
//             SELECT course_id FROM $table_enroll WHERE user_id = %d
//         ", $user_id)) ?: [];

//         update_user_meta($user_id, 'tutor_total_enroll', count($enrolled_course_ids));
//         update_user_meta($user_id, 'tutor_enrolled_courses', $enrolled_course_ids);

//         $cached = [];
//         foreach ($enrolled_course_ids as $course_id) {
//             $cached[$course_id] = [
//                 'course_id' => $course_id,
//                 'enrolled_date' => current_time('mysql')
//             ];
//         }
//         update_user_meta($user_id, 'tutor_enrolled_courses_cache', $cached);

//         $user = get_userdata($user_id);
//         if ($user && !in_array('tutor_student', (array)$user->roles)) {
//             $user->set_role('tutor_student');
//         }

//         error_log("[TPT-PATCH] ✅ Sync enroll cache untuk user $user_id selesai, courses: " . implode(',', $enrolled_course_ids));
//     }
// });

/**
 * 🧩 PATCH: Sinkronisasi otomatis ketika status order berubah
 * - Tambah topic ke _tpt_purchased_topics saat completed
 * - Hapus topic dari _tpt_purchased_topics kalau order dibatalkan / on-hold
 */
add_action('woocommerce_order_status_changed', function ($order_id, $old_status, $new_status) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    $user_id = $order->get_user_id();
    if (!$user_id) return;

    global $wpdb;

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        if (!$product_id) continue;

        $topic_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_tpt_wc_id' AND meta_value = %d
        ", $product_id));

        if (!$topic_id) continue;

        // Ambil meta lama
        $purchased = get_user_meta($user_id, '_tpt_purchased_topics', true);
        if (!is_array($purchased)) $purchased = [];

        if ($new_status === 'completed') {
            // ✅ Tambah topic ke daftar purchased
            if (!in_array($topic_id, $purchased)) {
                $purchased[] = $topic_id;
                update_user_meta($user_id, '_tpt_purchased_topics', array_unique($purchased));
                error_log("[TPT] ✅ Topic $topic_id DITAMBAHKAN ke _tpt_purchased_topics (order completed)");
            }
        } elseif (in_array($new_status, ['on-hold', 'pending', 'cancelled', 'refunded'])) {
            // ❌ Hapus topic dari daftar purchased
            $new_purchased = array_diff($purchased, [$topic_id]);
            update_user_meta($user_id, '_tpt_purchased_topics', $new_purchased);
            error_log("[TPT] ❌ Topic $topic_id DIHAPUS dari _tpt_purchased_topics (order $new_status)");
        }
    }
}, 10, 3);

/**
 * 🔁 Force refresh _tpt_purchased_topics setiap kali user buka halaman course
 * agar status On Hold -> Completed langsung ter-update tanpa logout.
 */
add_action('template_redirect', function () {
    if (!is_singular(['courses', 'tutor_course', 'lesson'])) return;

    $user_id = get_current_user_id();
    if (!$user_id) return;

    // Ambil semua order WooCommerce user
    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => ['completed'],
        'limit'       => -1,
    ]);

    if (!$orders) return;

    global $wpdb;
    $topics = [];

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $pid = $item->get_product_id();
            $tid = $wpdb->get_var($wpdb->prepare("
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_tpt_wc_id' AND meta_value = %d
            ", $pid));
            if ($tid) $topics[] = intval($tid);
        }
    }

    if (!empty($topics)) {
        $existing = get_user_meta($user_id, '_tpt_purchased_topics', true);
        if (!is_array($existing)) $existing = [];

        $merged = array_unique(array_merge($existing, $topics));
        update_user_meta($user_id, '_tpt_purchased_topics', $merged);
    }
}, 30);

function tpt_reset_user_data($user_id)
{
    if (!current_user_can('administrator') || !$user_id) return;
    global $wpdb;

    // 1️⃣ Hapus enrolled courses (Tutor LMS)
    $wpdb->delete($wpdb->prefix . 'tutor_enrolled', ['user_id' => $user_id]);

    // 2️⃣ Hapus meta Tutor LMS dan Paid Topic
    $keys = [
        'tutor_total_enroll',
        'tutor_enrolled_courses',
        'tutor_enrolled_courses_cache',
        '_tutor_course_progress',
        '_tutor_course_progress_cache',
        '_tutor_lesson_completed',
        '_tutor_lesson_completed_cache',
        '_tutor_quiz_completed',
        '_tutor_assignments',
        '_tpt_completed_topics',
        '_tpt_purchased_topics'
    ];
    foreach ($keys as $key) {
        delete_user_meta($user_id, $key);
    }

    // 3️⃣ Hapus semua meta prefiks tutor_
    $all_meta = get_user_meta($user_id);
    foreach ($all_meta as $meta_key => $value) {
        if (strpos($meta_key, 'tutor_') === 0) {
            delete_user_meta($user_id, $meta_key);
        }
    }

    // 4️⃣ Hapus user dari post meta _tutor_enrolled_students
    $courses = get_posts(['post_type' => 'tutor_course', 'posts_per_page' => -1]);
    foreach ($courses as $course) {
        $students = get_post_meta($course->ID, '_tutor_enrolled_students', true);
        if (is_array($students) && in_array($user_id, $students)) {
            $students = array_values(array_diff($students, [$user_id]));
            update_post_meta($course->ID, '_tutor_enrolled_students', $students);
        }
    }

    // 5️⃣ Hapus tabel pendukung Tutor LMS
    $tables = ['tutor_quiz_attempts', 'tutor_assignments', 'tutor_lesson_history'];
    foreach ($tables as $t) {
        $table_name = $wpdb->prefix . $t;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
            $wpdb->delete($table_name, ['user_id' => $user_id]);
        }
    }

    // 6️⃣ Hapus WooCommerce order terkait user
    $orders = wc_get_orders([
        'customer' => $user_id,
        'limit'    => -1,
        'status'   => ['completed', 'processing', 'on-hold', 'pending']
    ]);
    foreach ($orders as $order) {
        $order->delete(true);
    }

    // 7️⃣ Reset meta cache utama ke 0
    update_user_meta($user_id, 'tutor_total_enroll', 0);
    update_user_meta($user_id, 'tutor_enrolled_courses', []);
    update_user_meta($user_id, 'tutor_enrolled_courses_cache', []);

    // 8️⃣ Hapus transient global + per-user dari wp_options
    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_tutor_%'
        OR option_name LIKE '_transient_timeout_tutor_%'
        OR option_name LIKE %s
        OR option_name LIKE %s
        OR option_name LIKE %s
    ", '%tutor_student_stats%', '%tutor_dashboard_facts%', '%{$user_id}%'));

    // 9️⃣ Pastikan juga cache Tutor per-user ikut dihapus
    delete_transient('tutor_enrolled_courses_' . $user_id);
    delete_transient('tutor_course_progress_' . $user_id);
    delete_transient('tutor_student_courses_cache');
    delete_transient('tutor_student_stats_' . $user_id);

    // 🔟 Tambahkan skrip untuk hapus localStorage di browser admin (saat halaman Reset dimuat)
    add_action('admin_footer', function () {
        echo "<script>
            Object.keys(localStorage).forEach(k => {
                if (k.includes('tutor_')) localStorage.removeItem(k);
            });
            console.log('%c[TPT-RESET] Tutor LMS localStorage cleared.','color:#ED2D56;font-weight:bold');
        </script>";
    });

    // 🔁 Bersihkan cache dan transient Tutor LMS Dashboard
    $pattern = [
        "_transient_tutor_dashboard_facts%",
        "_transient_tutor_student_stats%",
        "_transient_timeout_tutor_dashboard_facts%",
        "_transient_timeout_tutor_student_stats%",
    ];

    foreach ($pattern as $p) {
        $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->options}
        WHERE option_name LIKE %s
    ", $p));
    }

    // 🔁 Bersihkan cache versi user ID spesifik
    $wpdb->query($wpdb->prepare("
    DELETE FROM {$wpdb->options}
    WHERE option_name LIKE %s
", '%tutor_dashboard_facts_' . $user_id . '%'));

    delete_transient('tutor_student_stats_' . $user_id);
    delete_transient('tutor_dashboard_facts_' . $user_id);
    delete_transient('tutor_dashboard_facts');

    // ✅ Log sukses
    error_log("[TPT-RESET] Semua data Tutor LMS & Paid Topic Addon untuk user $user_id berhasil dihapus total (DB + cache + transient).");
}

/**
 * 🔹 Tambahkan menu admin untuk reset user (opsional)
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Reset User Data',
        'Reset User Data',
        'manage_options',
        'tpt-reset-user',
        function () {
            if (isset($_POST['tpt_user_id'])) {
                $user_id = intval($_POST['tpt_user_id']);
                tpt_reset_user_data($user_id);
                echo '<div class="notice notice-success"><p>User ID ' . $user_id . ' berhasil di-reset.</p></div>';
            }
    ?>
        <div class="wrap">
            <h1>Reset Tutor LMS User Data</h1>
            <form method="post">
                <input type="number" name="tpt_user_id" placeholder="Masukkan User ID" required>
                <button type="submit" class="button button-primary">Reset Data</button>
            </form>
        </div>
<?php
        },
        'dashicons-admin-users',
        81
    );
});

/**
 * 🔹 REST API Endpoint: User Progress (real-time, no cache)
 * Dipakai untuk sync localStorage tpt_purchased_topics di frontend
 */
add_action('rest_api_init', function () {
    register_rest_route('tpt/v1', '/user-progress', [
        'methods'  => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $user_id = intval($request->get_param('user_id'));
            if (!$user_id) {
                return new WP_REST_Response(['error' => 'Invalid user ID'], 400);
            }

            // Ambil data langsung dari database
            $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
            $purchased = get_user_meta($user_id, '_tpt_purchased_topics', true) ?: [];

            return new WP_REST_Response([
                'data' => [
                    'completed' => array_values(array_filter($completed)),
                    'purchased' => array_values(array_filter($purchased)),
                ]
            ], 200);
        },
        'permission_callback' => '__return_true'
    ]);
});

/**
 * 🧩 PATCH FINAL: Force Refresh Tutor Dashboard Facts Realtime
 * Menghapus cache dashboard untuk user aktif setiap kali buka halaman dashboard
 */
add_action('template_redirect', function () {
    if (!is_user_logged_in()) return;

    global $wpdb;
    $user_id = get_current_user_id();

    // Deteksi halaman dashboard Tutor LMS
    if (is_page('dashboard') || (isset($_GET['tutor_dashboard']) && $_GET['tutor_dashboard'])) {

        // 1️⃣ Hapus transient dashboard & student stats
        delete_transient('tutor_dashboard_facts_' . $user_id);
        delete_transient('tutor_student_stats_' . $user_id);
        delete_transient('tutor_dashboard_facts');
        delete_transient('tutor_student_stats');

        // 2️⃣ Hapus cache di wp_options (bypass WP transient API)
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s OR option_name LIKE %s
        ", '%tutor_dashboard_facts_' . $user_id . '%', '%tutor_student_stats_' . $user_id . '%'));

        // 3️⃣ Log debugging
        error_log("[TPT-PATCH] Tutor dashboard facts cache dihapus untuk user {$user_id}");
    }
}, 50);


/**
 * 🧨 FINAL FIX: Override Dashboard Stats (Tutor LMS) — FIXED tanpa tabel completions
 */
add_filter('tutor_dashboard/stats', function ($stats) {
    global $wpdb;
    $user_id = get_current_user_id();
    if (!$user_id) return $stats;

    // Hitung ulang enrolled dari DB (aman, tabel pasti ada)
    $enrolled_count = (int) $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->prefix}tutor_enrolled WHERE user_id = %d
    ", $user_id));

    // Hitung completed dari meta user (fallback, karena tabel completions tidak ada)
    $completed_topics = get_user_meta($user_id, '_tpt_completed_topics', true);
    $completed_count  = is_array($completed_topics) ? count($completed_topics) : 0;

    // Kalau tidak ada data sama sekali, paksa nol
    if ($enrolled_count === 0 && $completed_count === 0) {
        $stats['total_enrolled_courses']  = 0;
        $stats['total_completed_courses'] = 0;
        $stats['total_active_courses']    = 0;
    }

    // Pastikan meta Tutor juga diset ulang agar sinkron ke depan
    update_user_meta($user_id, 'tutor_total_enroll', $enrolled_count);
    update_user_meta($user_id, 'tutor_enrolled_courses', []);
    update_user_meta($user_id, 'tutor_enrolled_courses_cache', []);

    error_log("[TPT-FINAL] Tutor dashboard override: enrolled={$enrolled_count}, completed={$completed_count} (user {$user_id})");

    return $stats;
}, 999);




/**
 * 🔁 Force refresh Tutor LMS enrolled courses cache setiap load page
 */
// add_action('template_redirect', function () {
//     if (!is_user_logged_in()) return;

//     $user_id = get_current_user_id();
//     if (!$user_id) return;

//     global $wpdb;
//     $table_enroll = $wpdb->prefix . 'tutor_enrolled';

//     $enrolled_course_ids = $wpdb->get_col($wpdb->prepare("
//         SELECT course_id FROM $table_enroll WHERE user_id=%d
//     ", $user_id)) ?: [];

//     // Update Tutor LMS meta
//     update_user_meta($user_id, 'tutor_total_enroll', count($enrolled_course_ids));
//     update_user_meta($user_id, 'tutor_enrolled_courses', $enrolled_course_ids);

//     // Update cache
//     $cached = [];
//     foreach ($enrolled_course_ids as $course_id) {
//         $cached[$course_id] = [
//             'course_id' => $course_id,
//             'enrolled_date' => current_time('mysql')
//         ];
//     }
//     update_user_meta($user_id, 'tutor_enrolled_courses_cache', $cached);

//     // Debug log (opsional)
//     error_log("[TPT-FORCE-SYNC] User $user_id enrolled courses refreshed: " . implode(',', $enrolled_course_ids));
// });
