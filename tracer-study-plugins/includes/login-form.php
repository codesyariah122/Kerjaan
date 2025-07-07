<?php
add_shortcode('tracer_login_form', function () {
    if (session_id() == '') session_start();

    if (isset($_SESSION['tracer_alumni_nim'])) {
        // Sudah login → redirect
        wp_redirect(site_url('/isi-tracer-study'));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        global $wpdb;
        $table = $wpdb->prefix . 'tracer_alumni_login';

        $nim = sanitize_text_field($_POST['nim']);
        $tgl = sanitize_text_field($_POST['tanggal_lahir']);

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE nim = %s AND tanggal_lahir = %s", $nim, $tgl));
        if ($row) {
            $_SESSION['tracer_alumni_nim'] = $nim;
            wp_redirect(site_url('/tracer-study')); // ganti slug sesuai halaman isi tracer kamu
            exit;
        } else {
            echo '<p style="color:red">NIM atau tanggal lahir tidak cocok.</p>';
        }
    }

    ob_start(); ?>
    <form method="post">
        <label>NIM:<br><input type="text" name="nim" required></label><br><br>
        <label>Tanggal Lahir:<br><input type="date" name="tanggal_lahir" required></label><br><br>
        <button type="submit">Login</button>
    </form>
<?php
    return ob_get_clean();
});
