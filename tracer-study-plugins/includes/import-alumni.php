<?php
function tracer_import_page()
{
    if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file, 'r')) !== FALSE) {
            global $wpdb;
            $table = $wpdb->prefix . 'tracer_alumni_login';
            $i = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($i == 0) {
                    $i++;
                    continue;
                } // skip header
                $wpdb->replace($table, [
                    'nim' => $data[0],
                    'nama' => $data[1],
                    'tanggal_lahir' => $data[2],
                ]);
            }
            fclose($handle);
            echo "<p style='color:green'>Import selesai.</p>";
        }
    }

    echo '<div class="wrap"><h2>Import Alumni CSV</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="csv_file" required>
            <button type="submit" name="import_csv" class="button button-primary">Import</button>
        </form></div>';
}
