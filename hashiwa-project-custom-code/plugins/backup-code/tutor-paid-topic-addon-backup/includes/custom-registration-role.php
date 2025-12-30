<?php


/**
 * Debug helper
 */
function tutor_debug_log($message, $data = null)
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) return;

    $log = "[TUTOR-DEBUG] " . $message;

    if ($data !== null) {
        $log .= " => " . print_r($data, true);
    }

    error_log($log);
}

/**
 * Buat role tutor_student bila belum ada
 */
add_action('init', function () {
    if (!get_role('tutor_student')) {
        add_role('tutor_student', 'Tutor Student', [
            'read'     => true,
            'level_0'  => true,
        ]);
        tutor_debug_log('Role tutor_student dibuat.');
    } else {
        //         tutor_debug_log('Role tutor_student SUDAH ADA.');
    }
});


/**
 * HOOK RESMI Tutor LMS
 * Dipanggil setelah student selesai registrasi lewat form Tutor LMS
 */
add_action('tutor_after_student_signup', function ($user_id) {

    tutor_debug_log('HOOK tutor_after_student_signup DIPANGGIL', ['user_id' => $user_id]);

    // --- SET ROLE
    wp_update_user([
        'ID'   => $user_id,
        'role' => 'tutor_student',
    ]);
    tutor_debug_log('Role diubah menjadi tutor_student');

    // --- SET USERMETA WAJIB UNTUK MUNCUL DI STUDENT LIST
    update_user_meta($user_id, 'tutor_profile_completed', 1);
    update_user_meta($user_id, 'tutor_last_activity', current_time('mysql'));
    update_user_meta($user_id, 'tutor_total_enroll', 0);

    tutor_debug_log('Meta tutor_profile_completed, last_activity, total_enroll di-set.');

    // Tutor LMS register
    if (function_exists('tutor_utils')) {
        tutor_utils()->register_student($user_id);
        tutor_debug_log('Tutor LMS register_student DIPANGGIL');
    } else {
        tutor_debug_log('ERROR: tutor_utils TIDAK DITEMUKAN');
    }

    // --- DEBUG USERMETA
    $meta = [
        'tutor_profile_completed' => get_user_meta($user_id, 'tutor_profile_completed', true),
        'tutor_last_activity'     => get_user_meta($user_id, 'tutor_last_activity', true),
        'tutor_total_enroll'      => get_user_meta($user_id, 'tutor_total_enroll', true)
    ];
    tutor_debug_log('CEK USERMETA SETELAH UPDATE', $meta);
});

/**
 * 🔓 Unlock semua lesson di Topic Pertama jika pembayaran sesuai harga Topic 1
 * Author: Puji Ermanto
 */
add_action('woocommerce_order_status_completed', function ($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $user_id = $order->get_user_id();
    if (!$user_id) return;

    tutor_debug_log('🔔 Woo Order Completed Detected', ['order_id' => $order_id, 'user_id' => $user_id]);

    // Cek role user
    $user = get_userdata($user_id);
    if (!$user || !in_array('tutor_student', (array) $user->roles)) {
        tutor_debug_log('User bukan tutor_student, SKIP');
        return;
    }

    global $wpdb;
    $table_price = "{$wpdb->prefix}tutor_topic_price";

    // Loop produk dalam order
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $product_name = $item->get_name();
        $amount_paid = floatval($item->get_total());

        tutor_debug_log('🛒 Produk ditemukan dalam order', [
            'product_id' => $product_id,
            'product_name' => $product_name,
            'amount_paid' => $amount_paid,
        ]);

        // Cari course ID dari produk Tutor LMS (biasanya tersimpan di meta)
        $course_id = get_post_meta($product_id, '_tutor_course_id', true);
        if (!$course_id) {
            // fallback: jika produk = course langsung
            $post_type = get_post_type($product_id);
            if ($post_type === 'courses') {
                $course_id = $product_id;
            }
        }

        if (!$course_id) {
            tutor_debug_log('❌ Tidak bisa deteksi course_id untuk produk ini.');
            continue;
        }

        // Ambil topic pertama di course
        $topic_1 = $wpdb->get_row($wpdb->prepare("
            SELECT ID, post_title
            FROM {$wpdb->posts}
            WHERE post_type = 'topics'
              AND post_parent = %d
              AND post_status = 'publish'
            ORDER BY menu_order ASC, ID ASC
            LIMIT 1
        ", $course_id));

        if (!$topic_1) {
            tutor_debug_log('❌ Tidak ada topic pertama di course', ['course_id' => $course_id]);
            continue;
        }

        // Ambil harga topic pertama dari tabel custom
        $topic_price = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT price FROM $table_price WHERE course_id = %d AND topic_title = %s",
            $course_id,
            $topic_1->post_title
        )));

        tutor_debug_log('💰 Harga topic pertama', [
            'course_id' => $course_id,
            'topic_id'  => $topic_1->ID,
            'topic_title' => $topic_1->post_title,
            'topic_price' => $topic_price,
        ]);

        // Jika harga topic cocok dengan pembayaran
        if (abs($amount_paid - $topic_price) < 1) {
            tutor_debug_log('✅ Pembayaran cocok dengan harga Topic 1 — unlock semua lesson.');

            // Ambil semua lesson dalam topic 1
            $lessons = $wpdb->get_results($wpdb->prepare("
                SELECT ID, post_title
                FROM {$wpdb->posts}
                WHERE post_type = 'lesson'
                  AND post_parent = %d
                  AND post_status = 'publish'
                ORDER BY menu_order ASC
            ", $topic_1->ID));

            if ($lessons) {
                foreach ($lessons as $lesson) {
                    if (function_exists('tutor_utils')) {
                        tutor_utils()->mark_lesson_complete($lesson->ID, $user_id);
                        tutor_debug_log('📘 Lesson unlocked', [
                            'lesson_id' => $lesson->ID,
                            'lesson_title' => $lesson->post_title,
                        ]);
                    }
                }
            }
        } else {
            tutor_debug_log('⚠️ Pembayaran tidak cocok dengan harga Topic 1, tidak di-unlock.');
        }
    }
});



// add_action('init', function() {
//     echo '<pre>';
//     print_r( wp_roles()->roles );
//     echo '</pre>';
// });
