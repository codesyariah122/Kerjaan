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
                            $row['User  Login'] = $user->user_login;
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

        // Hitung jumlah pengguna berdasarkan peran
        $role_counts = [];
        foreach ($rows as $row) {
            $roles = explode(', ', $row['Role']);
            foreach ($roles as $role) {
                if (!isset($role_counts[$role])) {
                    $role_counts[$role] = 0;
                }
                $role_counts[$role]++;
            }
        }

        // Siapkan data analitik
        $role_counts = [];
        $total_users = count($rows);
        $total_days_since_registered = 0;
        $registrations_per_month = [];
        $email_domains = [];

        foreach ($rows as $row) {
            // Hitung role
            $roles = explode(', ', $row['Role']);
            foreach ($roles as $role) {
                if (!isset($role_counts[$role])) {
                    $role_counts[$role] = 0;
                }
                $role_counts[$role]++;
            }

            // Hitung umur akun (rata-rata)
            if (!empty($row['Registered'])) {
                $reg_date = new DateTime($row['Registered']);
                $now = new DateTime();
                $diff = $now->diff($reg_date)->days;
                $total_days_since_registered += $diff;

                // Hitung per bulan registrasi
                $month_key = $reg_date->format('Y-m');
                if (!isset($registrations_per_month[$month_key])) {
                    $registrations_per_month[$month_key] = 0;
                }
                $registrations_per_month[$month_key]++;
            }

            // Hitung domain email
            if (!empty($row['Email']) && strpos($row['Email'], '@') !== false) {
                $domain = substr(strrchr($row['Email'], "@"), 1);
                if (!isset($email_domains[$domain])) {
                    $email_domains[$domain] = 0;
                }
                $email_domains[$domain]++;
            }
        }

        // Rata-rata umur akun
        $average_account_age = $total_users > 0 ? round($total_days_since_registered / $total_users, 1) : 0;

        // Top 5 email domain
        arsort($email_domains);
        $top_email_domains = array_slice($email_domains, 0, 5, true);

        // Siapkan data analitik
        $analytics_data = [];
        $analytics_data[] = ['Total Users', $total_users];
        $analytics_data[] = ['Average Account Age (days)', $average_account_age];
        $analytics_data[] = ['', '']; // pemisah

        // Role analytics
        $analytics_data[] = ['User Role', 'Count', 'Percentage'];
        foreach ($role_counts as $role => $count) {
            $percentage = round(($count / $total_users) * 100, 2) . '%';
            $analytics_data[] = [$role, $count, $percentage];
        }

        $analytics_data[] = ['', '']; // pemisah

        // Registrasi per bulan
        $analytics_data[] = ['Registration Month', 'Count'];
        ksort($registrations_per_month);
        foreach ($registrations_per_month as $month => $count) {
            $analytics_data[] = [$month, $count];
        }

        $analytics_data[] = ['', '']; // pemisah

        // Top email domains
        $analytics_data[] = ['Top Email Domains', 'Count'];
        foreach ($top_email_domains as $domain => $count) {
            $analytics_data[] = [$domain, $count];
        }

        $order_stats = [
            'customer' => ['orders' => 0, 'total' => 0],
            'mitra'    => ['orders' => 0, 'total' => 0],
        ];

        // Ambil semua order WooCommerce
        if (class_exists('WC_Order_Query')) {
            $query = new WC_Order_Query([
                'limit'        => -1,
                'status'       => ['wc-completed', 'wc-processing'], // bisa ditambah sesuai kebutuhan
                'return'       => 'ids',
            ]);
            $order_ids = $query->get_orders();

            foreach ($order_ids as $order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $customer_id = $order->get_customer_id();
                    $order_total = $order->get_total();

                    // Cek role user ini
                    $user_info = get_userdata($customer_id);
                    if ($user_info && !empty($user_info->roles)) {
                        foreach ($user_info->roles as $role) {
                            if ($role === 'customer') {
                                $order_stats['customer']['orders']++;
                                $order_stats['customer']['total'] += $order_total;
                            } elseif ($role === 'mitra') {
                                $order_stats['mitra']['orders']++;
                                $order_stats['mitra']['total'] += $order_total;
                            }
                        }
                    }
                }
            }
        }

        // Masukkan ke analytics_data
        $analytics_data[] = ['', '']; // pemisah
        $analytics_data[] = ['Order Analytics', 'Orders', 'Total Amount', 'Average per User'];
        foreach ($order_stats as $role => $stats) {
            $role_user_count = isset($role_counts[$role]) ? $role_counts[$role] : 0;
            $avg_order = $role_user_count > 0 ? round($stats['orders'] / $role_user_count, 2) : 0;
            $analytics_data[] = [
                ucfirst($role),
                $stats['orders'],
                wc_price($stats['total']),
                $avg_order
            ];
        }

        if ($format === 'csv') {
            // CSV untuk data pengguna
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            $out = fopen('php://output', 'w');

            // Tulis data pengguna
            fputcsv($out, array_keys($rows[0]), $delimiter);
            foreach ($rows as $row) {
                fputcsv($out, $row, $delimiter);
            }
            fclose($out);
            return;
        } elseif ($format === 'xls') {
            // XLS untuk data pengguna
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
            echo "<table border=1>";
            echo '<tr>';
            foreach (array_keys($rows[0]) as $h) {
                echo '<th>' . esc_html($h) . '</th>';
            }
            echo '</tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $c) {
                    echo '<td>' . esc_html($c) . '</td>';
                }
                echo '</tr>';
            }
            echo "</table>";

            // Tambahkan analitik di sheet baru
            echo "<br><h2>User Role Analytics</h2><table border=1>";
            foreach ($analytics_data as $analytics_row) {
                echo '<tr>';
                foreach ($analytics_row as $c) {
                    echo '<td>' . esc_html($c) . '</td>';
                }
                echo '</tr>';
            }
            echo "</table>";
            return;
        } elseif ($format === 'xlsx' && $has_phpspreadsheet) {
            try {
                $spread = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $userSheet = $spread->getActiveSheet();
                $userSheet->setTitle('Users');

                // Tulis data pengguna
                $rowNum = 1;
                $writtenHeader = false;

                foreach ($rows as $row) {
                    if (!$writtenHeader) {
                        $col = 1;
                        foreach (array_keys($row) as $h) {
                            $userSheet->setCellValueByColumnAndRow($col, $rowNum, $h);
                            $col++;
                        }
                        $rowNum++;
                        $writtenHeader = true;
                    }

                    $col = 1;
                    foreach ($row as $c) {
                        $userSheet->setCellValueByColumnAndRow($col, $rowNum, $c);
                        $col++;
                    }
                    $rowNum++;
                }

                // Tambahkan sheet baru untuk analitik
                $analyticsSheet = $spread->createSheet();
                $analyticsSheet->setTitle('Analytics');

                // Tulis data analitik
                $analyticsRowNum = 1;
                foreach ($analytics_data as $analytics_row) {
                    $col = 1;
                    foreach ($analytics_row as $c) {
                        $analyticsSheet->setCellValueByColumnAndRow($col, $analyticsRowNum, $c);
                        $col++;
                    }
                    $analyticsRowNum++;
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
            echo '<tr>';
            foreach (array_keys($rows[0]) as $h) {
                echo '<th>' . esc_html($h) . '</th>';
            }
            echo '</tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $c) {
                    echo '<td>' . esc_html($c) . '</td>';
                }
                echo '</tr>';
            }
            echo "</table>";

            // Tambahkan analitik di sheet baru
            echo "<br><h2>User Role Analytics</h2><table border=1>";
            foreach ($analytics_data as $analytics_row) {
                echo '<tr>';
                foreach ($analytics_row as $c) {
                    echo '<td>' . esc_html($c) . '</td>';
                }
                echo '</tr>';
            }
            echo "</table>";
            return;
        }
    }
}
