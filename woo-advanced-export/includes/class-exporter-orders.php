<?php
if (!defined('ABSPATH')) exit;

class WAE_Exporter_Orders
{
    public function export_orders_generator($columns = [], $batch = 200, $selected_statuses = [])
    {
        $page = 1;
        while (true) {
            $args = [
                'limit' => $batch,
                'page' => $page,
                'status' => $selected_statuses, // Gunakan status yang dipilih
            ];
            $orders = wc_get_orders($args);
            if (empty($orders)) break;

            foreach ($orders as $order) {
                $items = $order->get_items();
                $row = [];
                $total_item_amount = 0; // Inisialisasi total item

                foreach ($columns as $col) {
                    switch ($col) {
                        case 'ID':
                            $row['ID'] = $order->get_id();
                            break;
                        case 'date_created':
                            $row['Date Created'] = $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '';
                            break;
                        case 'billing_email':
                            $row['Billing Email'] = $order->get_billing_email();
                            break;
                        case 'total':
                            $row['Total'] = $order->get_total();
                            break;
                        case 'status':
                            $row['Status'] = $order->get_status();
                            break;
                        case 'billing_first_name':
                            $row['Billing First Name'] = $order->get_billing_first_name();
                            break;
                        case 'billing_last_name':
                            $row['Billing Last Name'] = $order->get_billing_last_name();
                            break;
                        case 'shipping_address_1':
                            $row['Shipping Address 1'] = $order->get_shipping_address_1();
                            break;
                        case 'payment_method':
                            $row['Payment Method'] = $order->get_payment_method(); // Ambil metode pembayaran
                            break;
                        case 'payment_via':
                            $row['Payment Via'] = $order->get_payment_method_title(); // Ambil judul metode pembayaran
                            break;
                        case 'product_name':
                            $product_names = [];
                            foreach ($items as $item) {
                                $product_names[] = $item->get_name();
                            }
                            $row['Product Name'] = implode(', ', $product_names);
                            break;
                        case 'quantity':
                            $quantities = [];
                            foreach ($items as $item) {
                                $quantities[] = $item->get_quantity();
                            }
                            $row['Quantity'] = implode("\n", $quantities); // Menggunakan newline untuk memisahkan
                            break;
                        case 'item_total':
                            $totals = [];
                            foreach ($items as $item) {
                                $item_total = $item->get_total();
                                $totals[] = $item_total;
                                $total_item_amount += $item_total; // Tambahkan ke total keseluruhan
                            }
                            $row['Item Total'] = implode("\n", $totals); // Menggunakan newline untuk memisahkan
                            $row['Total Item Amount'] = $total_item_amount; // Tambahkan total keseluruhan
                            break;
                        default:
                            $row[$col] = ''; // default kosong
                    }
                }
                yield $row;
            }
            $page++;
        }
    }

    public function output_rows($rows_gen, $columns, $format, $delimiter, $filename, $has_phpspreadsheet = false)
    {
        // Ambil semua row dari generator jadi array
        $rows = iterator_to_array($rows_gen);

        // CSV
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            echo "\xEF\xBB\xBF"; // BOM UTF-8
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
        }

        // XLS (HTML table)
        elseif ($format === 'xls') {
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

        // XLSX dengan PhpSpreadsheet
        elseif ($format === 'xlsx' && $has_phpspreadsheet) {
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
        }

        // fallback ke XLS kalau XLSX nggak bisa
        else {
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
