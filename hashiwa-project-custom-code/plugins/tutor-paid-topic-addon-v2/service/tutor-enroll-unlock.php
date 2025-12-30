<?php
if (!defined('ABSPATH')) exit;

/**
 * 🩹 Override Tutor lock system untuk lesson pertama
 * - Jika user sudah enrolled course dan topic pertama
 *   maka lesson pertama selalu bisa diakses tanpa redirect locked
 */
remove_action('template_redirect', 'tutor_lesson_access_restriction');

add_action('template_redirect', function () {
    if (!is_singular('lesson')) return;

    $lesson_id = get_the_ID();
    $topic_id = wp_get_post_parent_id($lesson_id);
    $course_id = get_post_meta($lesson_id, '_tutor_course_id', true);
    if (!$course_id && $topic_id) {
        $course_id = wp_get_post_parent_id($topic_id);
    }

    $user_id = get_current_user_id();
    if (!$user_id || !$course_id) return;

    global $wpdb;
    $enrolled = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->prefix}tutor_enrolled
        WHERE course_id = %d AND user_id = %d
    ", $course_id, $user_id));

    if (!$enrolled) {
        wp_safe_redirect(get_permalink($course_id) . '?locked=enroll');
        exit;
    }

    // Ambil topic pertama
    $all_topics = get_posts([
        'post_type' => ['topics', 'topic'],
        'post_parent' => $course_id,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'fields' => 'ids'
    ]);

    $first_topic_id = $all_topics ? $all_topics[0] : 0;

    // Lesson dari topic pertama selalu bisa diakses
    if ($topic_id && $topic_id == $first_topic_id) {
        return; // ✅ allow access
    }

    // Jika bukan topic pertama, cek meta purchased/completed
    $purchased = get_user_meta($user_id, '_tpt_purchased_topics', true) ?: [];
    $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];

    $topic_index = array_search($topic_id, $all_topics);
    $prev_topic_id = $topic_index > 0 ? $all_topics[$topic_index - 1] : 0;

    $can_access = in_array($topic_id, $purchased) && in_array($prev_topic_id, $completed);

    if (!$can_access) {
        // 🚫 Jangan redirect kalau sedang submit Mark as Complete
        if (isset($_POST['tutor_action']) && $_POST['tutor_action'] === 'tutor_complete_lesson') {
            return;
        }

        wp_safe_redirect(get_permalink($course_id) . '?locked=' . $topic_id);
        exit;
    }
}, 0); // priority 0 supaya jalan sebelum Tutor core

/**
 * 🔹 1. Auto-enroll user ke course setelah order completed
 */
add_action('woocommerce_order_status_completed', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    $user_id = $order->get_user_id();
    if (!$user_id) return;

    global $wpdb;
    $table = $wpdb->prefix . 'tutor_enrolled';

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $course_id  = get_post_meta($product_id, '_tutor_course_id', true);
        if (!$course_id) continue;

        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id=%d AND course_id=%d", $user_id, $course_id));
        if (!$exists) {
            $wpdb->insert($table, [
                'user_id' => $user_id,
                'course_id' => $course_id,
                'enrolled_on' => current_time('mysql')
            ]);
        }
    }
});

/**
 * 🔹 2. Filter akses topic (Tutor course_get_topics)
 * - Topic pertama: unlocked otomatis jika user sudah enroll course
 * - Topic selanjutnya: locked default kecuali topic sebelumnya sudah completed & topic ini sudah dibeli
 */
add_filter('tutor_course_get_topics', function ($topics, $course_id) {
    $user_id = get_current_user_id();
    if (!$user_id) return $topics;

    $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
    $purchased = get_user_meta($user_id, '_tpt_purchased_topics', true) ?: [];

    $prev_topic_id = null;
    foreach ($topics as $i => &$topic) {
        $topic_id = $topic->ID;

        // Topic pertama: auto unlock jika user sudah enroll course
        if ($i === 0) {
            // Topic pertama selalu terbuka
            $topic->lesson_accessibility = 'accessible';
            $topic->can_access = true;
            $topic->is_locked = false;
        } else {
            $can_unlock = in_array($prev_topic_id, $completed) && in_array($topic_id, $purchased);
            $topic->lesson_accessibility = $can_unlock ? 'accessible' : 'locked';
            $topic->can_access = $can_unlock;
            $topic->is_locked = !$can_unlock;
        }


        $prev_topic_id = $topic_id;
    }
    return $topics;
}, 10, 2);


/**
 * 🔹 3. Cegah akses langsung ke lesson dari topic yang belum dibeli
 */
add_action('template_redirect', function () {
    if (!is_singular('lesson')) return;

    global $wpdb;
    $lesson_id = get_the_ID();
    $topic_id  = wp_get_post_parent_id($lesson_id);
    $course_id = get_post_meta($lesson_id, '_tutor_course_id', true);

    // ✅ jika _tutor_course_id kosong, ambil dari parent topic
    if (!$course_id && $topic_id) {
        $course_id = wp_get_post_parent_id($topic_id);
    }

    $user_id = get_current_user_id();
    if (!$user_id || !$course_id) return;

    $purchased = get_user_meta($user_id, '_tpt_purchased_topics', true) ?: [];
    $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];

    // Ambil semua topic urut berdasarkan menu order
    $all_topics = $wpdb->get_col($wpdb->prepare("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_parent = %d
        AND post_type IN ('topics','topic')
        AND post_status = 'publish'
        ORDER BY menu_order ASC
    ", $course_id));

    $first_topic_id = $all_topics ? $all_topics[0] : 0;

    // ✅ Cek apakah user sudah enroll di course
    $enrolled = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->prefix}tutor_enrolled
        WHERE course_id = %d AND user_id = %d
    ", $course_id, $user_id));

    $can_access = false;

    // ✅ Topik pertama auto unlock jika user sudah enroll course
    if ($topic_id == $first_topic_id && $enrolled) {
        $can_access = true;

        // pastikan meta completed ikut ditandai agar filter topic detect unlocked
        $completed_meta = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
        if (!in_array($first_topic_id, $completed_meta)) {
            $completed_meta[] = $first_topic_id;
            update_user_meta($user_id, '_tpt_completed_topics', array_unique($completed_meta));
        }
    } else {
        // topic berikut hanya jika sudah beli dan topic sebelumnya complete
        $topic_index = array_search($topic_id, $all_topics);
        $prev_topic_id = $topic_index > 0 ? $all_topics[$topic_index - 1] : 0;
        $can_access = in_array($topic_id, $purchased) && in_array($prev_topic_id, $completed);
    }

    if (!$can_access) {
        wp_safe_redirect(get_permalink($course_id) . '?locked=' . $topic_id);
        exit;
    }
});


/**
 * 🔹 4. Update completed topic otomatis ketika user menyelesaikan lesson terakhir
 */
// add_action('tutor_lesson_completed_after', function ($lesson_id, $user_id) {
//     $topic_id = intval(get_post_meta($lesson_id, '_tutor_topic_id', true));
//     if (!$topic_id) {
//         $topic_id = wp_get_post_parent_id($lesson_id);
//     }

//     if (!$topic_id) return;

//     $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
//     if (!in_array($topic_id, $completed)) {
//         $completed[] = $topic_id;
//         update_user_meta($user_id, '_tpt_completed_topics', $completed);
//     }
// }, 10, 2);
add_action('tutor_lesson_completed_after', function ($lesson_id, $user_id) {
    // ambil topic ID dari lesson
    $topic_id = (int) get_post_meta($lesson_id, '_tutor_topic_id', true);
    if (!$topic_id) {
        $topic_id = wp_get_post_parent_id($lesson_id);
    }

    // pastikan lesson benar-benar terakhir di topic
    $lessons = get_posts([
        'post_type' => 'lesson',
        'post_parent' => $topic_id,
        'fields' => 'ids',
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);

    $is_last_lesson = end($lessons) == $lesson_id;

    if ($is_last_lesson && $topic_id) {
        $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
        if (!in_array($topic_id, $completed)) {
            $completed[] = $topic_id;
            update_user_meta($user_id, '_tpt_completed_topics', array_unique($completed));
        }
    }
}, 10, 2);

add_action('tutor_lesson_completed_after', function ($lesson_id, $user_id) {
    // ambil topic ID dari lesson
    $topic_id = (int) get_post_meta($lesson_id, '_tutor_topic_id', true);
    if (!$topic_id) {
        $topic_id = wp_get_post_parent_id($lesson_id);
    }

    // pastikan lesson benar-benar terakhir di topic
    $lessons = get_posts([
        'post_type' => 'lesson',
        'post_parent' => $topic_id,
        'fields' => 'ids',
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ]);

    $is_last_lesson = end($lessons) == $lesson_id;

    if ($is_last_lesson && $topic_id) {
        $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
        if (!in_array($topic_id, $completed)) {
            $completed[] = $topic_id;
            update_user_meta($user_id, '_tpt_completed_topics', array_unique($completed));
        }
    }
}, 10, 2);

/**
 * 🧩 FIX: Pastikan Mark as Complete tetap berfungsi walau akses dibatasi
 * (Hook di priority tinggi agar Tutor core sudah inisialisasi penuh)
 */
add_action('template_redirect', function () {
    if (!is_user_logged_in() || empty($_POST['tutor_action'])) return;

    if ($_POST['tutor_action'] === 'tutor_complete_lesson') {

        $lesson_id = intval($_POST['lesson_id']);
        $user_id   = get_current_user_id();

        // Pastikan nonce valid
        if (!isset($_POST['_tutor_nonce']) || !wp_verify_nonce($_POST['_tutor_nonce'], 'tutor_complete_lesson_nonce')) {
            return;
        }

        // 🔹 Jalankan process bawaan Tutor
        if (function_exists('tutor_utils')) {
            $utils = tutor_utils();
            if (method_exists($utils, 'mark_lesson_as_completed')) {
                $utils->mark_lesson_as_completed($lesson_id, $user_id);
            } elseif (function_exists('tutor_process_lesson_completion')) {
                tutor_process_lesson_completion();
            }
        }

        // 🔹 Panggil ulang hook custom kita
        do_action('tutor_lesson_completed_after', $lesson_id, $user_id);

        // 🔁 Redirect supaya form nggak resubmit
        wp_safe_redirect(get_permalink($lesson_id));
        exit;
    }
}, 50); // <== priority 50 biar Tutor core udah jalan duluan
