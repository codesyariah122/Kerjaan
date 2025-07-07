<?php
// Shortcode untuk logout alumni: hapus session
add_shortcode('tracer_logout', function () {
    if (session_id() == '') session_start();
    unset($_SESSION['tracer_alumni_nim']);
    return '<p>Logout berhasil.</p>';
});
