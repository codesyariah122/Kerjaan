<?php
// Menu admin untuk Pertanyaan Tracer
add_action('admin_menu', function () {
    add_submenu_page(
        'tracer-admin',
        'Pertanyaan Tracer',
        'Pertanyaan Tracer',
        'manage_options',
        'tracer-questions',
        'tracer_questions_page'
    );
});

// Halaman Kelola Pertanyaan
function tracer_questions_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'tracer_questions';

    // 🔘 Tambah/Edit pertanyaan
    if (isset($_POST['save_question'])) {
        $data = [
            'pertanyaan' => sanitize_text_field($_POST['pertanyaan']),
            'tipe_input' => sanitize_text_field($_POST['tipe_input']),
            'opsi' => sanitize_text_field($_POST['opsi']),
            'required' => isset($_POST['required']) ? 1 : 0,
            'urutan' => intval($_POST['urutan']),
        ];
        if ($_POST['id']) {
            $wpdb->update($table, $data, ['id' => $_POST['id']]);
        } else {
            $wpdb->insert($table, $data);
        }
    }

    // 🗑️ Hapus pertanyaan
    if (isset($_GET['hapus'])) {
        $wpdb->delete($table, ['id' => intval($_GET['hapus'])]);
    }

    // Ambil semua pertanyaan
    $questions = $wpdb->get_results("SELECT * FROM $table ORDER BY urutan ASC");

    // Ambil data jika sedang edit
    $edit = null;
    if (isset($_GET['edit'])) {
        $edit = $wpdb->get_row("SELECT * FROM $table WHERE id = " . intval($_GET['edit']));
    }

    // Menu admin: Jawaban Alumni
    add_action('admin_menu', function () {
        add_submenu_page(
            'tracer-admin',
            'Jawaban Alumni',
            'Jawaban Alumni',
            'manage_options',
            'tracer-responses',
            'tracer_responses_page'
        );
    });

    // ==== EKSPOR CSV ====
    add_action('admin_init', function () {
        if (isset($_GET['tracer_export']) && current_user_can('manage_options')) {
            global $wpdb;
            $table_res = $wpdb->prefix . 'tracer_responses';
            $table_q   = $wpdb->prefix . 'tracer_questions';

            $questions = $wpdb->get_results("SELECT id, pertanyaan FROM $table_q ORDER BY urutan ASC, id ASC");
            $headers = array_column($questions, 'pertanyaan', 'id');

            $results = $wpdb->get_results("SELECT * FROM $table_res ORDER BY submitted_at DESC");

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="jawaban-tracer.csv"');

            $out = fopen('php://output', 'w');

            // Baris Header
            $csv_header = array_merge(['NIM'], array_values($headers), ['Tanggal Submit']);
            fputcsv($out, $csv_header);

            // Baris Data
            foreach ($results as $row) {
                $jawaban = json_decode($row->jawaban, true);
                $data = [$row->nim];
                foreach ($questions as $q) {
                    $data[] = $jawaban[$q->id] ?? '';
                }
                $data[] = $row->submitted_at;
                fputcsv($out, $data);
            }

            fclose($out);
            exit;
        }
    });


    function tracer_responses_page()
    {
        global $wpdb;
        $table_res = $wpdb->prefix . 'tracer_responses';
        $table_q   = $wpdb->prefix . 'tracer_questions';

        $questions = $wpdb->get_results("SELECT id, pertanyaan FROM $table_q ORDER BY urutan ASC, id ASC");
        $headers = array_column($questions, 'pertanyaan', 'id');

        $results = $wpdb->get_results("SELECT * FROM $table_res ORDER BY submitted_at DESC");

        echo '<div class="wrap"><h2>Jawaban Alumni</h2>';
        if (!$results) {
            echo '<p>Belum ada jawaban alumni.</p></div>';
            return;
        }

        echo '<p><a href="' . admin_url('admin.php?page=tracer-responses&tracer_export=1') . '" class="button button-primary">🟢 Export ke CSV</a></p>';

        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>NIM</th>';
        foreach ($questions as $q) {
            echo '<th>' . esc_html($q->pertanyaan) . '</th>';
        }
        echo '<th>Waktu Submit</th>';
        echo '</tr></thead><tbody>';

        foreach ($results as $row) {
            $jawaban = json_decode($row->jawaban, true);
            echo '<tr>';
            echo '<td>' . esc_html($row->nim) . '</td>';
            foreach ($questions as $q) {
                $val = isset($jawaban[$q->id]) ? esc_html($jawaban[$q->id]) : '-';
                echo '<td>' . $val . '</td>';
            }
            echo '<td>' . esc_html($row->submitted_at) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }


?>
    <div class="wrap">
        <h2>Pertanyaan Tracer Study</h2>

        <form method="post">
            <input type="hidden" name="id" value="<?= esc_attr($edit->id ?? '') ?>">
            <table class="form-table">
                <tr>
                    <th>Pertanyaan</th>
                    <td><input type="text" name="pertanyaan" required value="<?= esc_attr($edit->pertanyaan ?? '') ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Tipe Input</th>
                    <td>
                        <select name="tipe_input">
                            <option value="text" <?= selected($edit->tipe_input ?? '', 'text') ?>>Text</option>
                            <option value="select" <?= selected($edit->tipe_input ?? '', 'select') ?>>Select</option>
                            <option value="radio" <?= selected($edit->tipe_input ?? '', 'radio') ?>>Radio</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Opsi (jika select/radio)</th>
                    <td><input type="text" name="opsi" value="<?= esc_attr($edit->opsi ?? '') ?>" class="regular-text"> <br><small>Pisahkan dengan koma: contoh: Ya,Tidak,Mungkin</small></td>
                </tr>
                <tr>
                    <th>Wajib diisi?</th>
                    <td><input type="checkbox" name="required" <?= !empty($edit->required) ? 'checked' : '' ?>></td>
                </tr>
                <tr>
                    <th>Urutan Tampil</th>
                    <td><input type="number" name="urutan" value="<?= esc_attr($edit->urutan ?? 0) ?>"></td>
                </tr>
            </table>
            <button type="submit" name="save_question" class="button button-primary">Simpan Pertanyaan</button>
        </form>

        <hr>
        <h3>Daftar Pertanyaan</h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pertanyaan</th>
                    <th>Tipe</th>
                    <th>Required</th>
                    <th>Urutan</th>
                    <th>Opsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): ?>
                    <tr>
                        <td><?= $q->id ?></td>
                        <td><?= esc_html($q->pertanyaan) ?></td>
                        <td><?= esc_html($q->tipe_input) ?></td>
                        <td><?= $q->required ? 'Ya' : 'Tidak' ?></td>
                        <td><?= $q->urutan ?></td>
                        <td><?= esc_html($q->opsi) ?></td>
                        <td>
                            <a href="?page=tracer-questions&edit=<?= $q->id ?>">Edit</a> |
                            <a href="?page=tracer-questions&hapus=<?= $q->id ?>" onclick="return confirm('Hapus pertanyaan ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php
}
