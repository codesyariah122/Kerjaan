<?php
if (!defined('ABSPATH')) exit;

class WAE_Exporter_Users
{
    public function export_users_generator($columns = [], $batch = 200)
    {
        $offset = 0;
        while (true) {
            $args = [
                'number' => $batch,
                'offset' => $offset,
                'orderby' => 'ID',
                'order' => 'ASC',
                'fields' => 'all',
            ];
            $users = get_users($args);
            if (empty($users)) break;
            foreach ($users as $user) {
                $row = [];
                foreach ($columns as $col) {
                    switch ($col) {
                        case 'ID':
                            $row['ID'] = $user->ID;
                            break;
                        case 'user_login':
                            $row['User Login'] = $user->user_login;
                            break;
                        case 'user_email':
                            $row['Email'] = $user->user_email;
                            break;
                        case 'user_registered':
                            $row['Registered'] = $user->user_registered;
                            break;
                        case 'first_name':
                            $row['First Name'] = get_user_meta($user->ID, 'first_name', true);
                            break;
                        case 'last_name':
                            $row['Last Name'] = get_user_meta($user->ID, 'last_name', true);
                            break;
                        case 'role':
                            $roles = $user->roles; // Ambil role pengguna
                            $row['Role'] = !empty($roles) ? implode(', ', $roles) : ''; // Gabungkan role jika ada
                            break;
                        default:
                            $row[$col] = '';
                    }
                }
                yield $row;
            }
            $offset += $batch;
        }
    }

    public function output_rows($rows_gen, $columns, $format, $delimiter, $filename, $has_phpspreadsheet = false)
    {
        $rows = iterator_to_array($rows_gen);

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            $out = fopen('php://output', 'w');

            $first = true;
            foreach ($rows as $row) {
                if ($first) {
                    fputcsv($out, array_keys($row), $delimiter);
                    $first = false;
                }
                fputcsv($out, $row, $delimiter);
            }
            fclose($out);
            return;
        } elseif ($format === 'xls') {
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
            echo "<table border=1>";
            $first = true;
            foreach ($rows as $row) {
                if ($first) {
                    echo '<tr>';
                    foreach (array_keys($row) as $h) {
                        echo '<th>' . esc_html($h) . '</th>';
                    }
                    echo '</tr>';
                    $first = false;
                }
                echo '<tr>';
                foreach ($row as $c) {
                    echo '<td>' . esc_html($c) . '</td>';
                }
                echo '</tr>';
            }
            echo "</table>";
            return;
        } elseif ($format === 'xlsx' && $has_phpspreadsheet) {
            try {
                $spread = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spread->getActiveSheet();
                $rowNum = 1;
                $writtenHeader = false;

                foreach ($rows as $row) {
                    if (!$writtenHeader) {
                        $col = 1;
                        foreach (array_keys($row) as $h) {
                            $sheet->setCellValueByColumnAndRow($col, $rowNum, $h);
                            $col++;
                        }
                        $rowNum++;
                        $writtenHeader = true;
                    }

                    $col = 1;
                    foreach ($row as $c) {
                        $sheet->setCellValueByColumnAndRow($col, $rowNum, $c);
                        $col++;
                    }
                    $rowNum++;
                }

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spread);
                $writer->save('php://output');
                return;
            } catch (\Exception $e) {
                wp_die('XLSX export failed: ' . esc_html($e->getMessage()));
            }
        } else {
            // fallback ke XLS (HTML) kalau XLSX gak bisa
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
            echo "<table border=1>";
            $first = true;
            foreach ($rows as $row) {
                if ($first) {
                    echo '<tr>';
                    foreach (array_keys($row) as $h) {
                        echo '<th>' . esc_html($h) . '</th>';
                    }
                    echo '</tr>';
                    $first = false;
                }
                echo '<tr>';
                foreach ($row as $c) {
                    echo '<td>' . esc_html($c) . '</td>';
                }
                echo '</tr>';
            }
            echo "</table>";
            return;
        }
    }
}
