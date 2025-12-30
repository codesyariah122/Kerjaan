<?php
add_action('init', function () {
    $user_id = 19; // user yang ingin dipaksa rebuild

    if (!function_exists('tutor_utils')) return;

    global $wpdb;

    $courses = $wpdb->get_results($wpdb->prepare("
        SELECT course_id, enrolled_date
        FROM {$wpdb->prefix}tutor_enrolled
        WHERE user_id = %d
    ", $user_id));

    if (!$courses) return;

    $cache_data = [];
    foreach ($courses as $row) {
        $cache_data[intval($row->course_id)] = [
            'course_id'     => intval($row->course_id),
            'enrolled_date' => $row->enrolled_date ?: current_time('mysql'),
        ];
    }

    // update user meta
    update_user_meta($user_id, 'tutor_enrolled_courses_cache', $cache_data);
    update_user_meta($user_id, 'tutor_enrolled_courses', array_keys($cache_data));
    update_user_meta($user_id, 'tutor_total_enroll', count($cache_data));

    // paksa refresh Tutor LMS internal cache
    tutor_utils()->refresh_course_enrolled_cache($user_id);

    error_log("[PATCH] 🔁 Cache forced rebuilt for user {$user_id}: " . json_encode(array_keys($cache_data)));
});

add_action('init', function () {
    $user_id = 19; // ganti sesuai user
    $course_id = 17012; // course yang ingin dimunculkan

    // pastikan course valid
    if (get_post_type($course_id) !== 'courses') return;

    // Ambil meta enrolled users
    $enrolled_users = get_post_meta($course_id, '_tutor_enrolled_user_ids', true);
    if (!is_array($enrolled_users)) $enrolled_users = [];

    // tambahkan user jika belum ada
    if (!in_array($user_id, $enrolled_users)) {
        $enrolled_users[] = $user_id;
        update_post_meta($course_id, '_tutor_enrolled_user_ids', $enrolled_users);
    }

    // update user meta cache Tutor LMS
    $cache_data = [
        $course_id => [
            'course_id'     => $course_id,
            'enrolled_date' => current_time('mysql'),
        ]
    ];
    update_user_meta($user_id, 'tutor_enrolled_courses_cache', $cache_data);
    update_user_meta($user_id, 'tutor_enrolled_courses', array_keys($cache_data));
    update_user_meta($user_id, 'tutor_total_enroll', count($cache_data));

    // refresh Tutor LMS cache
    if (function_exists('tutor_utils')) {
        tutor_utils()->refresh_course_enrolled_cache($user_id);
    }

    error_log("[PATCH] 🔁 User {$user_id} forced enrolled in course {$course_id}");
});
