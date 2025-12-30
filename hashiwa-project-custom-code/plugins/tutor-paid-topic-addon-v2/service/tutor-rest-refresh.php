<?php

/**
 * ============================================================
 * 🔄 Tutor REST Refresh + Re-Enroll Fix
 * ============================================================
 */
add_action('rest_api_init', function () {
    register_rest_route('tpt/v1', '/refresh-enroll-cache', [
        'methods'  => ['GET', 'POST'],
        'callback' => function (WP_REST_Request $req) {
            global $wpdb;

            $user_id = intval($req->get_param('user_id'));
            if (!$user_id) {
                return ['status' => 'error', 'message' => 'Missing user_id'];
            }

            if (!function_exists('tutor_utils')) {
                return ['status' => 'error', 'message' => 'Tutor utils not available'];
            }

            // 🔹 1. Ambil semua course yang pernah dibeli user
            $enrolled_courses = $wpdb->get_col($wpdb->prepare("
                SELECT course_id FROM {$wpdb->prefix}tutor_enrolled
                WHERE user_id = %d
            ", $user_id));

            // 🔹 2. Kalau belum ada satupun, coba cari dari WooCommerce order
            if (empty($enrolled_courses)) {
                $order_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT ID FROM {$wpdb->posts}
                    WHERE post_type='shop_order'
                    AND post_status IN ('wc-completed','wc-processing')
                    AND ID IN (
                        SELECT post_id FROM {$wpdb->postmeta}
                        WHERE meta_key='_customer_user' AND meta_value=%d
                    )
                ", $user_id));

                if ($order_ids) {
                    foreach ($order_ids as $order_id) {
                        $items = wc_get_order($order_id)->get_items();
                        foreach ($items as $item) {
                            $course_id = get_post_meta($item->get_product_id(), '_tutor_course_id', true);
                            if ($course_id) {
                                $enrolled_courses[] = intval($course_id);
                            }
                        }
                    }
                }
            }

            // 🔹 3. Pastikan benar-benar di-enroll di sistem Tutor
            if ($enrolled_courses) {
                foreach (array_unique($enrolled_courses) as $course_id) {

                    // ✅ Pastikan course ID benar-benar valid post type 'courses'
                    $post_type = get_post_type($course_id);
                    if ($post_type !== 'courses') {
                        error_log("[TPT-REFRESH] ⚠️ Course ID {$course_id} dilewati (post type: {$post_type})");
                        continue;
                    }

                    // ✅ Hindari error WooCommerce order kosong
                    if (!tutor_utils()->is_enrolled($course_id, $user_id)) {
                        if (function_exists('tpt_safe_enroll')) {
                            tpt_safe_enroll($course_id, $user_id);
                        }
                    }
                }
            }


            // 🔹 4. Refresh cache enrollment Tutor
            tutor_utils()->refresh_course_enrolled_cache($user_id);

            return [
                'status'  => 'success',
                'message' => "Enroll cache + DB sinkron untuk user {$user_id}",
                'courses' => $enrolled_courses,
            ];
        },
        'permission_callback' => '__return_true'
    ]);
});

add_action('rest_api_init', function () {
    register_rest_route('tpt/v1', '/reset-lesson', [
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $r) {
            $user_id = intval($r->get_param('user_id'));
            $lesson_id = intval($r->get_param('lesson_id'));
            if (!$user_id || !$lesson_id) {
                return ['status' => 'error', 'message' => 'Missing user_id or lesson_id'];
            }

            global $wpdb;
            $wpdb->delete("{$wpdb->usermeta}", [
                'user_id' => $user_id,
                'meta_key' => "_tutor_completed_lesson_id_{$lesson_id}"
            ]);

            // bersihkan cache progress juga
            $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->usermeta}
                WHERE user_id = %d AND meta_key LIKE 'tutor_%%progress%%'
            ", $user_id));

            return ['status' => 'success', 'message' => "Lesson $lesson_id reset untuk user $user_id"];
        },
        'permission_callback' => '__return_true'
    ]);
});
