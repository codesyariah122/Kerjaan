<?php

/**
 * Shortcode: [tracer_form]
 * – Menampilkan form tracer dinamis berdasarkan tabel wp_tracer_questions
 * – Hanya dapat diakses setelah alumni login (session nim)
 * – Satu NIM hanya bisa submit sekali
 * – Jawaban disimpan sebagai JSON ke wp_tracer_responses
 */

add_shortcode('tracer_form', function () {
    // 🚫 Wajib login dulu
    if (!isset($_SESSION['tracer_alumni_nim'])) {
        return '<p>Silakan login alumni terlebih dahulu.</p>';
    }

    echo '<p style="text-align:right"><a href="' . site_url('/logout-alumni') . '" class="button" style="background:#777;color:#fff;padding:8px 12px;border-radius:4px;">Logout</a></p>';

    global $wpdb;
    $nim         = $_SESSION['tracer_alumni_nim'];
    $table_q     = $wpdb->prefix . 'tracer_questions';
    $table_resp  = $wpdb->prefix . 'tracer_responses';

    // Cek sudah pernah submit?
    $ada = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_resp WHERE nim = %s",
        $nim
    ));
    if ($ada > 0) {
        return '<p>Anda sudah mengisi tracer study. Terima kasih 🙏</p>';
    }

    // Ambil semua pertanyaan
    $questions = $wpdb->get_results("SELECT * FROM $table_q ORDER BY urutan ASC, id ASC");
    if (!$questions) {
        return '<p>Form tracer belum disiapkan oleh admin.</p>';
    }

    // =======  HANDLE SUBMIT  =======
    if (isset($_POST['tracer_submit'])) {
        $answers = [];
        $errors  = [];

        foreach ($questions as $q) {
            $fname = 'q_' . $q->id;
            $val   = isset($_POST[$fname]) ? trim($_POST[$fname]) : '';

            if ($q->required && $val === '') {
                $errors[] = "Pertanyaan \"{$q->pertanyaan}\" wajib diisi.";
            }

            $answers[$q->id] = sanitize_text_field($val);
        }

        // Jika lolos validasi → simpan JSON
        if (empty($errors)) {
            // Simpan jawaban lengkap di tracer_responses
            $wpdb->insert($table_resp, [
                'nim'      => $nim,
                'jawaban'  => wp_json_encode($answers, JSON_UNESCAPED_UNICODE),
            ]);

            // Simpan ringkasan data di tracer_alumni (optional, untuk summary di admin)
            $prodi = isset($answers['prodi_question_id']) ? $answers['prodi_question_id'] : ''; // sesuaikan ID pertanyaan
            $tahun_lulus = isset($answers['tahun_lulus_question_id']) ? $answers['tahun_lulus_question_id'] : '';
            $status_pekerjaan = isset($answers['status_pekerjaan_question_id']) ? $answers['status_pekerjaan_question_id'] : '';
            $gaji = isset($answers['gaji_question_id']) ? $answers['gaji_question_id'] : '';

            $wpdb->insert($wpdb->prefix . 'tracer_alumni', [
                'nim' => $nim,
                'prodi' => $prodi,
                'tahun_lulus' => $tahun_lulus,
                'status_pekerjaan' => $status_pekerjaan,
                'gaji' => $gaji,
            ]);

            return '<p style="color:green">Terima kasih, jawaban Anda telah tersimpan! 🎉</p>';
        } else {
            echo '<div style="color:red"><ul><li>'
                . implode('</li><li>', array_map('esc_html', $errors))
                . '</li></ul></div>';
        }
    }

    // =======  RENDER FORM  =======
    ob_start();
    echo '<form method="post">';
    foreach ($questions as $q) {
        $fname = 'q_' . $q->id;
        $req   = $q->required ? 'required' : '';
        $label = esc_html($q->pertanyaan) . ($q->required ? ' *' : '');

        echo '<p><label>' . $label . '<br>';

        switch ($q->tipe_input) {
            case 'select':
                $opsi = array_map('trim', explode(',', $q->opsi));
                echo '<select name="' . $fname . '" ' . $req . '>';
                echo '<option value="">-- pilih --</option>';
                foreach ($opsi as $opt) {
                    $selected = (isset($_POST[$fname]) && $_POST[$fname] === $opt) ? 'selected' : '';
                    echo '<option ' . $selected . '>' . esc_html($opt) . '</option>';
                }
                echo '</select>';
                break;

            case 'radio':
                $opsi = array_map('trim', explode(',', $q->opsi));
                foreach ($opsi as $opt) {
                    $checked = (isset($_POST[$fname]) && $_POST[$fname] === $opt) ? 'checked' : '';
                    echo '<label style="margin-right:8px;">'
                        . '<input type="radio" name="' . $fname . '" value="' . esc_attr($opt) . '" '
                        . $checked . ' ' . $req . '> ' . esc_html($opt)
                        . '</label>';
                }
                break;

            case 'text':
            default:
                $val = isset($_POST[$fname]) ? esc_attr($_POST[$fname]) : '';
                echo '<input type="text" name="' . $fname . '" value="' . $val . '" ' . $req . ' class="regular-text">';
        }

        echo '</label></p>';
    }
    echo '<button type="submit" name="tracer_submit" class="button button-primary">Kirim</button>';
    echo '</form>';


    return ob_get_clean();
});
