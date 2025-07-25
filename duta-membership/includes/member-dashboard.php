<?php

function duta_member_dashboard_shortcode()
{
    ob_start();

    if (!is_user_logged_in()) {
        echo '<p>Silakan login terlebih dahulu. <a href="' . site_url('/login-member') . '">Login di sini</a></p>';
        return ob_get_clean();
    }

    $current_user = wp_get_current_user();

    $company = get_user_meta($current_user->ID, 'company_name', true);
    $industry = get_user_meta($current_user->ID, 'industry', true);
    $phone = get_user_meta($current_user->ID, 'phone_number', true);
    $address = get_user_meta($current_user->ID, 'full_address', true);
?>

    <div class="duta-member-dashboard">
        <h2>Selamat Datang, <?php echo esc_html($current_user->display_name); ?>!</h2>
        <p><strong>Perusahaan:</strong> <?php echo esc_html($company); ?></p>
        <p><strong>Industri:</strong> <?php echo esc_html($industry); ?></p>
        <p><strong>No. Telepon:</strong> <?php echo esc_html($phone); ?></p>
        <p><strong>Alamat:</strong> <?php echo esc_html($address); ?></p>
        <p><a href="<?php echo wp_logout_url(site_url('/login')); ?>">Logout</a></p>

        <hr>
        <h3>📥 Download Katalog Produk</h3>
        <a href="<?php echo plugin_dir_url(__DIR__) . 'uploads/katalog.pdf'; ?>" target="_blank">Unduh Katalog PDF</a>

        <hr>
        <h3>📝 Kirim Permintaan Penawaran (RFQ)</h3>
        <?php echo do_shortcode('[duta_rfq_form]'); ?>

        <hr>
        <h3>📋 Riwayat Permintaan Anda</h3>
        <?php echo do_shortcode('[duta_rfq_history]'); ?>
    </div>

<?php
    return ob_get_clean();
}
