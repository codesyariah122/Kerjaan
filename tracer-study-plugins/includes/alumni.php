<?php
// Menu admin: Data Alumni
add_action('admin_menu', function () {
    add_submenu_page(
        'tracer-admin',
        'Data Alumni',
        'Data Alumni',
        'manage_options',
        'tracer-alumni',
        'tracer_alumni_page'
    );
});

// Halaman Data Alumni
function tracer_alumni_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'tracer_alumni_login';

    // Tambah/Edit alumni
    if (isset($_POST['save_alumni'])) {
        $data = [
            'nim' => sanitize_text_field($_POST['nim']),
            'nama' => sanitize_text_field($_POST['nama']),
            'tanggal_lahir' => sanitize_text_field($_POST['tanggal_lahir']),
        ];
        if ($_POST['id']) {
            $wpdb->update($table, $data, ['id' => intval($_POST['id'])]);
        } else {
            $wpdb->insert($table, $data);
        }
    }

    // Hapus alumni
    if (isset($_GET['hapus'])) {
        $wpdb->delete($table, ['id' => intval($_GET['hapus'])]);
    }

    // Ambil semua data alumni
    $data_alumni = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");

    // Ambil data jika sedang edit
    $edit = null;
    if (isset($_GET['edit'])) {
        $edit = $wpdb->get_row("SELECT * FROM $table WHERE id = " . intval($_GET['edit']));
    }

?>
    <div class="wrap">
        <h2>Data Alumni</h2>
        <form method="post">
            <input type="hidden" name="id" value="<?= esc_attr($edit->id ?? '') ?>">
            <table class="form-table">
                <tr>
                    <th>NIM</th>
                    <td><input type="text" name="nim" required value="<?= esc_attr($edit->nim ?? '') ?>"></td>
                </tr>
                <tr>
                    <th>Nama</th>
                    <td><input type="text" name="nama" required value="<?= esc_attr($edit->nama ?? '') ?>"></td>
                </tr>
                <tr>
                    <th>Tanggal Lahir</th>
                    <td><input type="date" name="tanggal_lahir" required value="<?= esc_attr($edit->tanggal_lahir ?? '') ?>"></td>
                </tr>
            </table>
            <button type="submit" name="save_alumni" class="button button-primary">Simpan</button>
        </form>

        <hr>
        <h3>Daftar Alumni</h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Tanggal Lahir</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data_alumni as $a): ?>
                    <tr>
                        <td><?= $a->id ?></td>
                        <td><?= esc_html($a->nim) ?></td>
                        <td><?= esc_html($a->nama) ?></td>
                        <td><?= esc_html($a->tanggal_lahir) ?></td>
                        <td>
                            <a href="?page=tracer-alumni&edit=<?= $a->id ?>">Edit</a> |
                            <a href="?page=tracer-alumni&hapus=<?= $a->id ?>" onclick="return confirm('Hapus alumni ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php
}
