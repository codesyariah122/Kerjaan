<?php
function duta_edit_profile_shortcode()
{
    if (!is_user_logged_in()) {
        return '<p>Silakan login terlebih dahulu.</p>';
    }

    $current_user = wp_get_current_user();
    $user_id = get_current_user_id();
    $phone = get_user_meta($user_id, 'phone', true);
    $address = get_user_meta($user_id, 'address', true);
    $uploaded_file = get_user_meta($user_id, 'uploaded_doc_url', true);

    // Proses form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duta_save_profile']) && wp_verify_nonce($_POST['_wpnonce'], 'duta_edit_profile')) {
        if (!empty($_POST['display_name'])) {
            wp_update_user([
                'ID' => $user_id,
                'display_name' => sanitize_text_field($_POST['display_name']),
            ]);
        }

        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
        update_user_meta($user_id, 'address', sanitize_textarea_field($_POST['address']));

        if (!empty($_FILES['user_doc']['name'])) {
            $uploaded = media_handle_upload('user_doc', 0);
            if (!is_wp_error($uploaded)) {
                update_user_meta($user_id, 'uploaded_doc_url', wp_get_attachment_url($uploaded));
            }
        }

        echo '<div class="notice notice-success"><p>✅ Profil berhasil diperbarui.</p></div>';
    }

    ob_start(); ?>
    <h2>Edit Profil</h2>
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('duta_edit_profile'); ?>
        <p><label>Nama:</label><br>
            <input type="text" name="display_name" value="<?= esc_attr($current_user->display_name); ?>">
        </p>

        <p><label>Email:</label><br>
            <input type="email" name="email" value="<?= esc_attr($current_user->user_email); ?>" readonly>
        </p>

        <p><label>No. Telepon:</label><br>
            <input type="text" name="phone" value="<?= esc_attr($phone); ?>">
        </p>

        <p><label>Alamat:</label><br>
            <textarea name="address"><?= esc_textarea($address); ?></textarea>
        </p>

        <p><label>Upload Dokumen (PDF/JPG/PNG):</label><br>
            <input type="file" name="user_doc">
            <?php if ($uploaded_file): ?>
                <br><a href="<?= esc_url($uploaded_file); ?>" target="_blank">Lihat Dokumen</a>
            <?php endif; ?>
        </p>

        <p><button type="submit" name="duta_save_profile">💾 Simpan</button></p>
    </form>
<?php
    return ob_get_clean();
}
