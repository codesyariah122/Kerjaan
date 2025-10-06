<?php
add_action('add_meta_boxes', function () {
    add_meta_box('alur_meta', 'Detail Langkah', 'alur_meta_box_cb', 'alur_pendaftaran', 'normal', 'default');
});

function alur_meta_box_cb($post)
{
    $number = get_post_meta($post->ID, '_alur_number', true);
    $icon = get_post_meta($post->ID, '_alur_icon', true);
    $desc = get_post_meta($post->ID, '_alur_desc', true);
    $column = get_post_meta($post->ID, '_alur_column', true) ?: 'left';
?>
    <p><label>Nomor:</label><br>
        <input type="number" name="alur_number" value="<?= esc_attr($number) ?>">
    </p>

    <p><label>Icon (emoji / class FA / svg url):</label><br>
        <input type="text" name="alur_icon" value="<?= esc_attr($icon) ?>" style="width:100%">
        <small style="display:block; margin-top:5px; color:#555;">
            👉 Contoh: <code>fa-envelope</code> atau <code>fas fa-user</code><br>
            🔗 <a href="https://fontawesome.com/icons" target="_blank">Cari ikon Font Awesome</a> &nbsp;|&nbsp;
            <a href="https://fontawesome.com/docs/web/style/classic" target="_blank">Dokumentasi Font Awesome</a>
        </small>
    </p>

    <p><label>Deskripsi:</label><br>
        <textarea name="alur_desc" style="width:100%"><?= esc_html($desc) ?></textarea>
    </p>

    <p><label>Posisi Kolom:</label><br>
        <select name="alur_column">
            <option value="left" <?= selected($column, 'left') ?>>Kiri</option>
            <option value="right" <?= selected($column, 'right') ?>>Kanan</option>
        </select>
    </p>
<?php
}


add_action('save_post', function ($post_id) {
    if (get_post_type($post_id) !== 'alur_pendaftaran') return;
    update_post_meta($post_id, '_alur_number', $_POST['alur_number'] ?? '');
    update_post_meta($post_id, '_alur_icon', $_POST['alur_icon'] ?? '');
    update_post_meta($post_id, '_alur_desc', $_POST['alur_desc'] ?? '');
    update_post_meta($post_id, '_alur_column', $_POST['alur_column'] ?? 'left');
});
