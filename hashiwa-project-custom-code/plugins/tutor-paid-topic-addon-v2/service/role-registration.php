<?php

/**
 * ============================================================
 * 🔐 Tutor Student Role & Registration Handler (Full Patch)
 * ============================================================
 */

/**
 * Debug helper
 */
if (!function_exists('tpt_debug_log')) {
    function tpt_debug_log($message, $data = null)
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) return;
        $log = "[TPT-DEBUG] " . $message;
        if ($data !== null) $log .= " => " . print_r($data, true);
        error_log($log);
    }
}

/**
 * Pastikan role tutor_student tersedia
 */
add_action('init', function () {
    if (!get_role('tutor_student')) {
        add_role('tutor_student', 'Tutor Student', [
            'read'         => true,
            'upload_files' => true,
            'level_0'      => true,
        ]);
        tpt_debug_log('Role tutor_student dibuat otomatis.');
    }
});

/**
 * REST API Endpoint untuk Aktivasi Akun
 */
add_action('rest_api_init', function () {
    register_rest_route('tpt/v1', '/activate', [
        'methods' => 'GET',
        'callback' => function ($request) {
            $key = sanitize_text_field($request->get_param('key'));
            $user_id = intval($request->get_param('user'));

            if (!$key || !$user_id) {
                return new WP_REST_Response(['error' => 'Invalid activation link'], 400);
            }

            $stored_key = get_user_meta($user_id, '_tpt_activation_key', true);
            $is_activated = get_user_meta($user_id, '_tpt_activated', true);

            if ($is_activated || $stored_key !== $key) {
                return new WP_REST_Response(['error' => 'Activation link invalid or already used'], 400);
            }

            update_user_meta($user_id, '_tpt_activated', true);
            delete_user_meta($user_id, '_tpt_activation_key');

            wp_redirect(site_url('/dashboard?activated=1'));
            exit;
        },
        'permission_callback' => '__return_true'
    ]);
});

/**
 * Saat user register via Tutor LMS
 * Generate NIM otomatis
 */
add_action('tutor_after_student_signup', function ($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return;

    // Set role tutor_student
    wp_update_user([
        'ID'   => $user_id,
        'role' => 'tutor_student',
    ]);

    // Update meta Tutor LMS
    update_user_meta($user_id, 'tutor_profile_completed', 1);
    update_user_meta($user_id, 'tutor_last_activity', current_time('mysql'));
    update_user_meta($user_id, 'tutor_total_enroll', 0);

    // Set pending activation
    update_user_meta($user_id, '_tpt_activated', false);
    $activation_key = wp_generate_password(32, false);
    update_user_meta($user_id, '_tpt_activation_key', $activation_key);

    // 🔹 Generate NIM otomatis format HJA-YY-XXXX
    $current_year = date('y'); // 2 digit tahun sekarang
    $prefix = 'HJA-' . $current_year . '-';

    // Ambil tutor_student terakhir dengan NIM tahun ini
    $users_with_nim = get_users([
        'role'        => 'tutor_student',
        'meta_key'    => 'nim',
        'meta_value'  => $prefix,
        'meta_compare' => 'LIKE',
        'orderby'     => 'ID',
        'order'       => 'DESC',
        'number'      => 1,
    ]);

    $last_nim = $users_with_nim ? get_user_meta($users_with_nim[0]->ID, 'nim', true) : '';
    $next_number = $last_nim ? intval(substr($last_nim, -4)) + 1 : 1;
    $nim = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);

    update_user_meta($user_id, 'nim', $nim);
    tpt_debug_log("NIM otomatis disimpan untuk user_id $user_id", $nim);

    // Kirim email aktivasi
    $activation_link = site_url('/wp-json/tpt/v1/activate?key=' . $activation_key . '&user=' . $user_id);
    $subject = 'Aktivasi Akun Tutor Student - ' . get_bloginfo('name');
    $message = "
    Halo {$user->display_name},

    Terima kasih telah mendaftar sebagai Tutor Student di " . get_bloginfo('name') . ".

    Untuk mengaktifkan akun Anda dan mulai belajar, klik link berikut:
    {$activation_link}

    Jika Anda tidak mendaftar, abaikan email ini.

    Salam,
    Tim " . get_bloginfo('name') . "
    ";
    wp_mail($user->user_email, $subject, $message);

    // Redirect ke dashboard activation_required
    wp_logout();
    wp_redirect(site_url('/dashboard?activation_required=1'));
    exit;
});


/**
 * Cegah login jika akun belum aktivasi
 */
// add_filter('authenticate', function ($user, $username, $password) {
//     if (is_wp_error($user)) return $user;
//     if (!$username) return $user;

//     $user_obj = get_user_by('login', $username);
//     if (!$user_obj) return $user;

//     $is_activated = get_user_meta($user_obj->ID, '_tpt_activated', true);
//     if ($is_activated != '1') {
//         add_filter('login_redirect', function () {
//             return site_url('/dashboard?activation_required=1');
//         }, 10, 3);

//         return new WP_Error('activation_required', 'Akun Anda belum diaktifkan. Periksa email untuk link aktivasi.');
//     }

//     return $user;
// }, 20, 3);
add_filter('authenticate', function ($user, $username, $password) {
    if (is_wp_error($user)) return $user;
    if (!$username) return $user;

    $user_obj = get_user_by('login', $username);
    if (!$user_obj) return $user;

    // ✅ Tambahkan filter: hanya berlaku untuk role tutor_student
    if (!in_array('tutor_student', (array) $user_obj->roles)) {
        return $user; // lewati validasi aktivasi
    }

    $is_activated = get_user_meta($user_obj->ID, '_tpt_activated', true);
    if ($is_activated != '1') {
        add_filter('login_redirect', function () {
            return site_url('/dashboard?activation_required=1');
        }, 10, 3);

        return new WP_Error('activation_required', 'Akun Anda belum diaktifkan. Periksa email untuk link aktivasi.');
    }

    return $user;
}, 20, 3);


/**
 * Force logout & redirect jika user login tapi belum aktivasi
 */
// add_action('init', function () {
//     if (is_user_logged_in()) {
//         $user_id = get_current_user_id();
//         $is_activated = get_user_meta($user_id, '_tpt_activated', true);
//         $current_uri = $_SERVER['REQUEST_URI'];

//         // Block semua akses jika belum aktivasi, kecuali halaman activation_required
//         if ($is_activated === false && !str_contains($current_uri, 'activation_required=1')) {
//             wp_logout();
//             wp_safe_redirect(site_url('/dashboard?activation_required=1'));
//             exit;
//         }
//     }
// });
add_action('init', function () {
    if (!is_user_logged_in()) return;

    $user = wp_get_current_user();

    // ✅ Hanya untuk role tutor_student
    if (!in_array('tutor_student', (array) $user->roles)) {
        return;
    }

    $is_activated = get_user_meta($user->ID, '_tpt_activated', true);
    $current_uri = $_SERVER['REQUEST_URI'];

    if ($is_activated === false && !str_contains($current_uri, 'activation_required=1')) {
        wp_logout();
        wp_safe_redirect(site_url('/dashboard?activation_required=1'));
        exit;
    }
});


/**
 * Cegah akses dashboard Tutor LMS sebelum konten load
 */
add_action('tutor_dashboard_after_page_load', function () {
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    $is_activated = get_user_meta($user_id, '_tpt_activated', true);

    if ($is_activated != 1) {
        wp_logout();
        wp_redirect(site_url('/dashboard?activation_required=1'));
        exit;
    }
});

/**
 * SweetAlert + Flash text alert di login form
 */
add_action('wp_footer', function () {
    if (isset($_GET['activated']) && $_GET['activated'] == 1) {
        echo <<<HTML
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
    icon: 'success',
    title: 'Akun diaktifkan!',
    html: 'Akun Anda telah berhasil diaktifkan.<br>Silakan login sekarang untuk mengakses dashboard dan mulai belajar.',
    confirmButtonText: 'OK'
});
</script>
HTML;
    }

    if (isset($_GET['activation_required']) && $_GET['activation_required'] == 1) {
        echo <<<HTML
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
Swal.fire({
    icon: 'warning',
    title: 'Aktivasi Diperlukan!',
    html: 'Akun Anda belum diaktifkan.<br>Silakan cek email dan klik link aktivasi sebelum login.',
    confirmButtonText: 'OK'
});
</script>
HTML;

        echo <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginFormWrapper = document.querySelector('.tutor-login-form-wrapper');
    if (loginFormWrapper) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-warning';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = '<strong>Info:</strong> Akun Anda belum diaktifkan. Periksa email untuk link aktivasi sebelum login.';
        loginFormWrapper.prepend(alertDiv);
    }
});
</script>
HTML;
    }
});

/**
 * ============================================================
 * 🔹 Tampilkan kolom NIM di list table Users
 * ============================================================
 */
add_filter('manage_users_columns', function ($columns) {
    $columns['nim'] = 'NIM';
    return $columns;
});

add_action('manage_users_custom_column', function ($value, $column_name, $user_id) {
    if ($column_name === 'nim') {
        $nim = get_user_meta($user_id, 'nim', true);
        return $nim ? esc_html($nim) : '-';
    }
    return $value;
}, 10, 3);

add_filter('manage_users_sortable_columns', function ($columns) {
    $columns['nim'] = 'nim';
    return $columns;
});

add_action('pre_get_users', function ($query) {
    if (!is_admin()) return;
    if (isset($_GET['orderby']) && $_GET['orderby'] === 'nim') {
        $query->query_vars['meta_key'] = 'nim';
        $query->query_vars['orderby'] = 'meta_value';
    }
});
