<?php
add_action('admin_menu', function () {
    add_menu_page('Tracer Alumni', 'Tracer Study', 'manage_options', 'tracer-admin', 'tracer_admin_page');
    add_submenu_page('tracer-admin', 'Import Alumni', 'Import Alumni', 'manage_options', 'tracer-import', 'tracer_import_page');
    add_submenu_page('tracer-admin', 'Statistik', 'Statistik', 'manage_options', 'tracer-chart', 'tracer_chart_page');

    // Tambah submenu baru untuk Jawaban Alumni
    add_submenu_page('tracer-admin', 'Jawaban Alumni', 'Jawaban Alumni', 'manage_options', 'tracer-responses', 'tracer_show_responses_page');
});

function tracer_admin_page()
{
    global $wpdb;
    $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tracer_alumni ORDER BY submitted_at DESC");
    echo "<div class='wrap'><h2>Data Tracer</h2><table class='widefat striped'><thead>
        <tr><th>NIM</th><th>Prodi</th><th>Tahun</th><th>Status</th><th>Gaji</th><th>Tanggal</th></tr>
    </thead><tbody>";
    foreach ($rows as $r) {
        echo "<tr><td>{$r->nim}</td><td>{$r->prodi}</td><td>{$r->tahun_lulus}</td><td>{$r->status_pekerjaan}</td><td>{$r->gaji}</td><td>{$r->submitted_at}</td></tr>";
    }
    echo "</tbody></table></div>";
}

function tracer_show_responses_page()
{
    global $wpdb;
    $table_q = $wpdb->prefix . 'tracer_questions';
    $table_r = $wpdb->prefix . 'tracer_responses';

    $pertanyaan = $wpdb->get_results("SELECT * FROM $table_q ORDER BY urutan ASC, id ASC");
    $responses  = $wpdb->get_results("SELECT * FROM $table_r ORDER BY submitted_at DESC");

    echo '<div class="wrap"><h2>Jawaban Alumni</h2>';
    if (!$responses) {
        echo '<p>Belum ada alumni yang mengisi tracer.</p></div>';
        return;
    }

    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>No</th><th>NIM</th>';
    foreach ($pertanyaan as $q) {
        echo '<th>' . esc_html($q->pertanyaan) . '</th>';
    }
    echo '<th>Waktu Submit</th>';
    echo '</tr></thead><tbody>';

    foreach ($responses as $i => $res) {
        $jawaban = json_decode($res->jawaban, true);
        echo '<tr>';
        echo '<td>' . ($i + 1) . '</td>';
        echo '<td>' . esc_html($res->nim) . '</td>';
        foreach ($pertanyaan as $q) {
            $val = isset($jawaban[$q->id]) ? esc_html($jawaban[$q->id]) : '-';
            echo '<td>' . $val . '</td>';
        }
        echo '<td>' . esc_html($res->submitted_at) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}
