<?php
if (!defined('ABSPATH')) exit;

/**
 * ============================================================
 * 🧹 Tutor LMS Course Reset API
 *  Hapus semua enrollment & order berdasarkan course ID
 * ============================================================
 */
add_action('rest_api_init', function () {
    register_rest_route('tpt/v1', '/reset-course-data', [
        'methods'  => 'POST',
        'callback' => 'tpt_reset_course_data',
        'permission_callback' => function () {
            return current_user_can('manage_options'); // hanya admin
        },
        'args' => [
            'course_id' => [
                'required' => true,
                'type'     => 'integer'
            ],
        ]
    ]);
});

function tpt_reset_course_data(WP_REST_Request $request)
{
    global $wpdb;
    $course_id = intval($request->get_param('course_id'));

    if (!$course_id) {
        return new WP_REST_Response(['error' => 'Course ID tidak valid'], 400);
    }

    // 🔍 Validasi course
    $course_post = get_post($course_id);
    if (!$course_post || $course_post->post_type !== 'courses') {
        return new WP_REST_Response(['error' => 'Course tidak ditemukan'], 404);
    }

    // 1️⃣ Hapus semua data enrollment untuk course ini
    $deleted_enrollments = $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->prefix}tutor_enrolled WHERE course_id = %d
    ", $course_id));

    // 2️⃣ Hapus meta cache enrolled students
    delete_post_meta($course_id, '_tutor_enrolled_students');

    // 3️⃣ Hapus transient Tutor dashboard & student stats
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%tutor_student_stats%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%tutor_dashboard_facts%'");

    // 4️⃣ (Opsional) Hapus semua order WooCommerce yang berhubungan dengan topik course ini
    $topic_ids = $wpdb->get_col($wpdb->prepare("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_parent = %d AND post_type IN ('topics','topic')
    ", $course_id));

    $orders_deleted = 0;

    if ($topic_ids) {
        $product_ids = [];
        foreach ($topic_ids as $tid) {
            $wc_id = get_post_meta($tid, '_tpt_wc_id', true);
            if ($wc_id) $product_ids[] = intval($wc_id);
        }

        if ($product_ids) {
            $orders = wc_get_orders([
                'limit' => -1,
                'status' => array_keys(wc_get_order_statuses())
            ]);

            foreach ($orders as $order) {
                foreach ($order->get_items() as $item) {
                    if (in_array($item->get_product_id(), $product_ids)) {
                        $order->delete(true);
                        $orders_deleted++;
                        break;
                    }
                }
            }
        }
    }

    // 5️⃣ Hapus meta custom di user (jika ingin bersih total)
    $users = get_users(['role__in' => ['tutor_student']]);
    foreach ($users as $user) {
        delete_user_meta($user->ID, '_tpt_completed_topics');
        delete_user_meta($user->ID, '_tpt_purchased_topics');
        delete_user_meta($user->ID, '_tutor_lesson_completed');
    }

    // return new WP_REST_Response([
    //     'status' => 'success',
    //     'message' => sprintf(
    //         'Course "%s" berhasil direset. Dihapus: %d enrollment, %d order.',
    //         $course_post->post_title,
    //         $deleted_enrollments,
    //         $orders_deleted
    //     ),
    // ], 200);
    // 🧩 Tambahan patch: bersihkan semua cache Tutor LMS per-course
    $wpdb->query($wpdb->prepare(
        "
    DELETE FROM {$wpdb->options}
    WHERE option_name LIKE %s
       OR option_name LIKE %s
       OR option_name LIKE %s
       OR option_name LIKE %s
",
        '%tutor_dashboard_facts%',
        '%tutor_student_stats%',
        '%tutor_course_stats%',
        '%tutor_course_progress%'
    ));

    // Hapus meta cache internal Tutor
    delete_post_meta($course_id, '_tutor_course_stats_cache');
    delete_post_meta($course_id, '_tutor_enrolled_students');
    delete_post_meta($course_id, '_tutor_enrolled_ids_cache');
    delete_post_meta($course_id, '_tutor_total_enrolled');
    delete_post_meta($course_id, '_tutor_total_students');

    // ✅ Bersihkan object cache dan persistent cache Tutor LMS
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush(); // flush semua cache object WordPress (termasuk Tutor)
    }

    // ✅ Bersihkan cache plugin (kalau LiteSpeed atau W3 Total Cache)
    if (function_exists('litespeed_purge_all')) {
        litespeed_purge_all();
    } elseif (function_exists('w3tc_flush_all')) {
        w3tc_flush_all();
    }


    // ✅ Bersihkan meta yang menampilkan "2 Students" di frontend
    delete_post_meta($course_id, '_tutor_total_students');
    delete_post_meta($course_id, '_tutor_total_enrolled');
    delete_post_meta($course_id, '_tutor_enrolled_students');
    delete_post_meta($course_id, '_tutor_enrolled_ids_cache');

    // ✅ Hapus cache statistik per-course
    global $wpdb;
    $wpdb->query($wpdb->prepare("
    DELETE FROM {$wpdb->options}
    WHERE option_name LIKE %s
       OR option_name LIKE %s
       OR option_name LIKE %s
", '%tutor_course_stats%', '%tutor_dashboard_facts%', '%tutor_student_stats%'));

    // ✅ Jika Tutor punya helper cache deleter, panggil juga
    if (function_exists('tutor_delete_course_cache')) {
        tutor_delete_course_cache($course_id);
    }

    // ✅ Flush semua object cache WordPress
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }

    // Setelah itu kirim response sukses
    return new WP_REST_Response([
        'status' => 'success',
        'message' => sprintf(
            'Course "%s" berhasil direset & cache dibersihkan. Dihapus: %d enrollment, %d order.',
            $course_post->post_title,
            $deleted_enrollments,
            $orders_deleted
        ),
    ], 200);
}
