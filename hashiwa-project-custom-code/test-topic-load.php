<?php
require_once __DIR__ . '/wp-load.php';

global $wpdb;

// Ganti course ID
$course_id = 17012;

// Ambil semua topic IDs langsung dari DB
$topics = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT ID FROM {$wpdb->prefix}posts 
         WHERE post_type = 'tutor_topic' 
         AND post_status = 'publish'
         AND ID IN (
            SELECT meta_value FROM {$wpdb->prefix}postmeta 
            WHERE meta_key = 'course_id' AND meta_value = %d
         )",
        $course_id
    )
);

if (empty($topics)) {
    die("Course ID {$course_id} tidak punya topic di DB.\n");
}

echo "Total topics: " . count($topics) . "\n\n";

// Coba load satu per satu via admin-ajax.php
foreach ($topics as $i => $topic_id) {
    $start = microtime(true);

    $response = wp_remote_post(admin_url('admin-ajax.php'), [
        'body' => [
            'action' => 'tutor_topic_get_content',
            'topic_id' => $topic_id,
            'course_id' => $course_id,
        ],
        'timeout' => 15,
    ]);

    $time = round(microtime(true) - $start, 2);

    if (is_wp_error($response)) {
        echo "Topic {$topic_id} [" . ($i + 1) . "]: ERROR - " . $response->get_error_message() . " (time: {$time}s)\n";
        continue;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        echo "Topic {$topic_id} [" . ($i + 1) . "]: FAIL - HTTP {$code} (time: {$time}s)\n";
        continue;
    }

    echo "Topic {$topic_id} [" . ($i + 1) . "]: OK (time: {$time}s)\n";
}
