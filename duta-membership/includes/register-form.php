<?php

function duta_register_form_shortcode()
{
    ob_start();

    // Jika form disubmit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duta_register_nonce']) && wp_verify_nonce($_POST['duta_register_nonce'], 'duta_register_action')) {

        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $company = sanitize_text_field($_POST['company']);
        $industry = sanitize_text_field($_POST['industry']);
        $phone = sanitize_text_field($_POST['phone']);
        $address = sanitize_textarea_field($_POST['address']);

        $errors = [];

        if (username_exists($username) || email_exists($email)) {
            $errors[] = 'Username atau email sudah digunakan.';
        }

        if (empty($errors)) {
            $user_id = wp_create_user($username, $password, $email);

            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'company_name', $company);
                update_user_meta($user_id, 'industry', $industry);
                update_user_meta($user_id, 'phone_number', $phone);
                update_user_meta($user_id, 'full_address', $address);
                wp_update_user(['ID' => $user_id, 'role' => 'subscriber']); // Atau role khusus 'member'

                echo '<div style="color: green;">Pendaftaran berhasil! Silakan login.</div>';
            } else {
                echo '<div style="color: red;">Terjadi kesalahan saat mendaftarkan pengguna.</div>';
            }
        } else {
            foreach ($errors as $error) {
                echo '<div style="color: red;">' . esc_html($error) . '</div>';
            }
        }
    }

?>

    <form method="post">
        <?php wp_nonce_field('duta_register_action', 'duta_register_nonce'); ?>

        <p><label>Username<br><input type="text" name="username" required></label></p>
        <p><label>Email<br><input type="email" name="email" required></label></p>
        <p><label>Password<br><input type="password" name="password" required></label></p>
        <p><label>Nama Perusahaan<br><input type="text" name="company" required></label></p>
        <p><label>Kategori Industri<br>
                <select name="industry" required>
                    <option value="">-- Pilih Industri --</option>
                    <option value="Telekomunikasi">Telekomunikasi</option>
                    <option value="Geologi">Geologi</option>
                    <option value="Sipil">Sipil</option>
                    <option value="Otomotif">Otomotif</option>
                    <option value="Tambang">Tambang</option>
                    <option value="Power Plant">Power Plant</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </label></p>
        <p><label>No. Telepon<br><input type="text" name="phone" required></label></p>
        <p><label>Alamat Lengkap<br><textarea name="address" required></textarea></label></p>

        <p><button type="submit">Daftar</button></p>
    </form>

<?php

    return ob_get_clean();
}
