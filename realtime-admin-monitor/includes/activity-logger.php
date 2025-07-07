<?php

function log_admin_activity()
{
    check_ajax_referer('admin_monitor_nonce', 'nonce');

    $current_user = wp_get_current_user();
    if (!in_array('administrator', $current_user->roles)) {
        wp_send_json_error('Not allowed');
    }

    $user_id = $current_user->ID;
    $activity = [
        'last_active' => current_time('timestamp'),
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'],
        'ip'          => $_SERVER['REMOTE_ADDR'],
    ];

    update_option('admin_monitor_' . $user_id, $activity);

    // Cek admin lain
    $all_users = get_users(['role' => 'Administrator']);
    foreach ($all_users as $user) {
        if ($user->ID !== $user_id) {
            $other = get_option('admin_monitor_' . $user->ID);
            if ($other && (time() - $other['last_active']) < 60) {
                wp_send_json_error(['active_user' => $user->user_login, 'device' => $other['user_agent']]);
            }
        }
    }

    wp_send_json_success(['status' => 'active']);
}

// AJAX: fetch activity for all admins
add_action('wp_ajax_get_admin_activity_stats', 'get_admin_activity_stats');

function get_admin_activity_stats()
{
    check_ajax_referer('admin_monitor_nonce', 'nonce');

    $admins = get_users(['role' => 'Administrator']);
    $now = current_time('timestamp');
    $rows = [];

    foreach ($admins as $admin) {
        $activity = get_option('admin_monitor_' . $admin->ID);
        if (!$activity) {
            $rows[] = [
                'user' => $admin->user_login,
                'last_active' => '<em>Tidak ada aktivitas</em>',
                'device' => '-',
                'percentage' => 0,
            ];
            continue;
        }

        $last_active = $activity['last_active'];
        $user_agent = $activity['user_agent'];
        $diff = $now - $last_active;
        $percentage = ($diff < 60) ? 100 : max(0, 100 - ($diff / 60) * 100);

        $rows[] = [
            'user' => $admin->user_login,
            'last_active' => date('Y-m-d H:i:s', $last_active),
            'device' => $user_agent,
            'percentage' => round($percentage)
        ];
    }

    wp_send_json_success($rows);
}
