<?php

function duta_login_form_shortcode()
{
    ob_start();

    if (is_user_logged_in()) {
        echo '<div>Anda sudah login. <a href="' . esc_url(site_url('/dashboard-member')) . '">Lihat Dashboard</a></div>';
        return ob_get_clean();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duta_login_nonce']) && wp_verify_nonce($_POST['duta_login_nonce'], 'duta_login_action')) {
        $creds = array(
            'user_login'    => sanitize_text_field($_POST['username']),
            'user_password' => $_POST['password'],
            'remember'      => true
        );

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            echo '<div style="color: red;">Login gagal: ' . esc_html($user->get_error_message()) . '</div>';
        } else {
            wp_redirect(site_url('/dashboard-member'));
            exit;
        }
    }

?>

    <form method="post">
        <?php wp_nonce_field('duta_login_action', 'duta_login_nonce'); ?>
        <p><label>Username atau Email<br><input type="text" name="username" required></label></p>
        <p><label>Password<br><input type="password" name="password" required></label></p>
        <p><button type="submit">Login</button></p>
    </form>

<?php

    return ob_get_clean();
}
