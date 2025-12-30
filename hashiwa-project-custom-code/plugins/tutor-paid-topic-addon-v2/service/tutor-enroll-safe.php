<?php

/**
 * ============================================================
 * 🧩 Safe Enroll Helper untuk Tutor Paid Topic Addon
 * ============================================================
 */

// if (!function_exists('tpt_safe_enroll')) {
//     function tpt_safe_enroll($course_id, $user_id)
//     {
//         global $wpdb;

//         // Validasi basic
//         if (!$course_id || !$user_id) return false;
//         if (get_post_type($course_id) !== 'courses') {
//             error_log("[TPT-SAFE] ⚠️ ID $course_id bukan courses");
//             return false;
//         }

//         // Cek apakah sudah ter-enroll
//         $exists = $wpdb->get_var($wpdb->prepare("
//             SELECT COUNT(*) FROM {$wpdb->prefix}tutor_enrolled
//             WHERE course_id = %d AND user_id = %d
//         ", $course_id, $user_id));

//         if ($exists) {
//             error_log("[TPT-SAFE] ✅ User {$user_id} sudah ter-enroll di course {$course_id}");
//             return true;
//         }

//         // Insert manual ke tabel enroll
//         $wpdb->insert(
//             "{$wpdb->prefix}tutor_enrolled",
//             [
//                 'course_id'          => $course_id,
//                 'user_id'            => $user_id,
//                 'enrolled_date'      => current_time('mysql'),
//                 'is_completed'       => 0,
//                 'completion_percent' => 0,
//                 'order_id'           => 0,
//             ],
//             ['%d', '%d', '%s', '%d', '%d', '%d']
//         );

//         // Update cache Tutor
//         if (function_exists('tutor_utils')) {
//             tutor_utils()->refresh_course_enrolled_cache($user_id);
//         }

//         error_log("[TPT-SAFE] ✅ User {$user_id} berhasil enroll manual ke course {$course_id}");
//         return true;
//     }
// }

// /**
//  * ============================================================
//  * 🧩 Safe Enroll Helper untuk Tutor Paid Topic Addon
//  * ============================================================
//  */

// if (!function_exists('tpt_safe_enroll')) {
//     function tpt_safe_enroll($course_id, $user_id)
//     {
//         global $wpdb;

//         // Validasi basic
//         if (!$course_id || !$user_id) return false;
//         if (get_post_type($course_id) !== 'courses') {
//             error_log("[TPT-SAFE] ⚠️ ID $course_id bukan courses");
//             return false;
//         }

//         // Cek apakah sudah ter-enroll
//         $exists = $wpdb->get_var($wpdb->prepare("
//             SELECT COUNT(*) FROM {$wpdb->prefix}tutor_enrolled
//             WHERE course_id = %d AND user_id = %d
//         ", $course_id, $user_id));

//         if ($exists) {
//             error_log("[TPT-SAFE] ✅ User {$user_id} sudah ter-enroll di course {$course_id}");
//             return true;
//         }

//         // Insert manual ke tabel enroll
//         $wpdb->insert(
//             "{$wpdb->prefix}tutor_enrolled",
//             [
//                 'course_id'          => $course_id,
//                 'user_id'            => $user_id,
//                 'enrolled_date'      => current_time('mysql'),
//                 'is_completed'       => 0,
//                 'completion_percent' => 0,
//                 'order_id'           => 0,
//             ],
//             ['%d', '%d', '%s', '%d', '%d', '%d']
//         );

//         // Update cache Tutor
//         if (function_exists('tutor_utils')) {
//             tutor_utils()->refresh_course_enrolled_cache($user_id);
//         }

//         error_log("[TPT-SAFE] ✅ User {$user_id} berhasil enroll manual ke course {$course_id}");
//         return true;
//     }
// }

// /**
//  * ============================================================
//  * 🔹 Patch: Auto-enroll full course untuk Tutor Paid Topic
//  * ============================================================
//  */

// if (!function_exists('tpt_safe_enroll_full_course')) {
//     function tpt_safe_enroll_full_course($course_id, $user_id, $order_id = 0)
//     {
//         global $wpdb;

//         $table = $wpdb->prefix . 'tutor_enrolled';

//         // Cek apakah user sudah enrolled
//         $exists = $wpdb->get_var($wpdb->prepare("
//             SELECT enrolled_id FROM $table WHERE user_id = %d AND course_id = %d
//         ", $user_id, $course_id));

//         if (!$exists) {
//             $wpdb->insert($table, [
//                 'course_id' => $course_id,
//                 'user_id' => $user_id,
//                 'order_id' => $order_id,
//                 'enrolled_date' => current_time('mysql'),
//                 'completion_percent' => 0,
//                 'is_completed' => 0
//             ]);
//             error_log("[TPT-FULL-ENROLL] ✅ User $user_id enrolled full course $course_id via topic purchase");
//         }

//         // Refresh cache Tutor LMS
//         if (function_exists('tutor_utils')) {
//             tutor_utils()->refresh_course_enrolled_cache($user_id);
//         }
//     }

//     // Hook: setelah order completed, pastikan full course enrollment
//     add_action('woocommerce_order_status_completed', function ($order_id) {
//         $order = wc_get_order($order_id);
//         $user_id = $order->get_user_id();

//         if (!$user_id || !$order) return;

//         foreach ($order->get_items() as $item) {
//             $topic_id  = get_post_meta($item->get_product_id(), '_tpt_topic_id', true);
//             $course_id = get_post_meta($item->get_product_id(), '_tutor_course_id', true);

//             if ($course_id && $topic_id) {
//                 tpt_safe_enroll_full_course($course_id, $user_id, $order_id);
//             }
//         }
//     });
// }

// /**
//  * ============================================================
//  * 🔹 Mass-update: Enroll semua user yang sudah beli topic tapi belum full course
//  * ============================================================
//  */

// if (!function_exists('tpt_mass_enroll_full_course')) {
//     function tpt_mass_enroll_full_course($limit = 100)
//     {
//         global $wpdb;

//         $prefix = $wpdb->prefix;

//         // Ambil semua order completed yang punya product topic
//         $orders = $wpdb->get_results("
//             SELECT p.ID as order_id, pm.meta_value as user_id
//             FROM {$prefix}posts p
//             INNER JOIN {$prefix}postmeta pm ON p.ID = pm.post_id AND pm.meta_key='_customer_user'
//             WHERE p.post_type='shop_order' AND p.post_status='wc-completed'
//             ORDER BY p.ID DESC
//             LIMIT $limit
//         ");

//         foreach ($orders as $order_data) {
//             $order_id = $order_data->order_id;
//             $user_id  = intval($order_data->user_id);

//             $order = wc_get_order($order_id);
//             if (!$order) continue;

//             foreach ($order->get_items() as $item) {
//                 $topic_id  = get_post_meta($item->get_product_id(), '_tpt_topic_id', true);
//                 $course_id = get_post_meta($item->get_product_id(), '_tutor_course_id', true);

//                 if ($course_id && $topic_id) {
//                     tpt_safe_enroll_full_course($course_id, $user_id, $order_id);
//                 }
//             }
//         }

//         error_log("[TPT-MASS-ENROLL] ✅ Mass enroll selesai untuk $limit order terakhir.");
//     }
// }

// if (!defined('ABSPATH')) exit;

// /**
//  * 🔁 Pastikan topik pertama unlocked untuk user baru
//  */
// add_action('tutor_after_enroll_course', function ($course_id, $user_id) {
//     $topics = tutor_utils()->get_course_topics($course_id);
//     if (!$topics) return;

//     $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];

//     // Hanya topik pertama otomatis unlocked
//     $first_topic = $topics[0]->ID;
//     if (!in_array($first_topic, $completed)) {
//         $completed[] = $first_topic;
//         update_user_meta($user_id, '_tpt_completed_topics', $completed);
//     }
// }, 10, 2);
