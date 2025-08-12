<?php
if (!defined('ABSPATH')) exit;

class WAE_Exporter
{

    // returns a Generator yielding associative arrays according to $columns
    public function export_products_generator($columns = array(), $batch = 200, $format)
    {
        if (!function_exists('wc_get_products')) return (function () {
            yield array();
        })();
        $page = 1;
        while (true) {
            $args = array('limit' => $batch, 'page' => $page, 'status' => array('publish', 'draft', 'private'));
            $products = wc_get_products($args);
            if (empty($products)) break;
            foreach ($products as $p) {
                $row = array();
                // prepare common data
                $attrs = $this->get_product_attributes($p);
                $images = $p->get_image_id() ? wp_get_attachment_url($p->get_image_id()) : '';
                $gallery = $p->get_gallery_image_ids();
                if (!empty($gallery) && is_array($gallery)) {
                    $srcs = array();
                    foreach ($gallery as $gid) {
                        $srcs[] = wp_get_attachment_url($gid);
                    }
                    if ($srcs) $images .= (empty($images) ? '' : '|') . implode('|', $srcs);
                }
                $variations_summary = '';
                $variations_summary = '';
                if ($p->is_type('variable')) {
                    $vlist = array();
                    foreach ($p->get_children() as $vid) {
                        $vp = wc_get_product($vid);
                        if (!$vp) continue;
                        $vlist[] = sprintf(
                            'ID:%s, SKU:%s, Price:%s, Stock:%s, Attr:%s',
                            $vp->get_id(),
                            $vp->get_sku() ?: '-',
                            $vp->get_price() ?: '-',
                            $vp->get_stock_quantity() ?: '-',
                            $this->get_product_attributes($vp) ?: '-'
                        );
                    }
                    // pakai line break agar di cell Excel jadi multi-baris
                    // $variations_summary = implode("\n", $vlist);

                    if ($format === 'xls') {
                        // HTML list untuk XLS
                        $variations_summary = '<ul><li>' . implode('</li><li>', $vlist) . '</li></ul>';
                    } else {
                        // Newline untuk CSV dan XLSX
                        $variations_summary = implode("\n", $vlist);
                    }
                }

                foreach ($columns as $col) {
                    switch ($col) {
                        case 'ID':
                            $row['ID'] = $p->get_id();
                            break;
                        case 'post_title':
                            $row['Title'] = $p->get_name();
                            break;
                        case 'sku':
                            $row['SKU'] = $p->get_sku();
                            break;
                        case 'price':
                            $row['Price'] = $p->get_price();
                            break;
                        case 'regular_price':
                            $row['Regular Price'] = $p->get_regular_price();
                            break;
                        case 'sale_price':
                            $row['Sale Price'] = $p->get_sale_price();
                            break;
                        case 'stock':
                            $row['Stock'] = $p->get_stock_quantity();
                            break;
                        case 'type':
                            $row['Type'] = $p->get_type();
                            break;
                        case 'categories':
                            $row['Categories'] = $this->get_terms_list($p->get_id(), 'product_cat');
                            break;
                        case 'tags':
                            $row['Tags'] = $this->get_terms_list($p->get_id(), 'product_tag');
                            break;
                        case 'attributes':
                            $row['Attributes'] = $attrs;
                            break;
                        case 'images':
                            $img_urls = explode('|', $images);
                            $img_tags = array_map(function ($url) {
                                return '<img src="' . esc_url($url) . '" width="50" />';
                            }, $img_urls);
                            $row['Images'] = implode(' ', $img_tags);
                            break;

                        case 'variations':
                            $row['Variations'] = $variations_summary;
                            break;
                        default:
                            // custom meta
                            $meta = get_post_meta($p->get_id(), $col, true);
                            $row[$col] = is_array($meta) ? json_encode($meta) : $meta;
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
        $products = iterator_to_array($rows_gen);

        // CSV
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');

            $first = true;
            foreach ($products as &$row) {
                if (!empty($row['images'])) {
                    $urls = is_array($row['images']) ? $row['images'] : explode(',', $row['images']);
                    $img_tags = [];
                    foreach ($urls as $url) {
                        $url = trim($url);
                        if (!empty($url)) {
                            $img_tags[] = '<img src="' . esc_url($url) . '" width="60">';
                        }
                    }
                    // Gambar turun ke bawah
                    $row['images'] = implode("\n", $img_tags);
                }

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
            foreach ($products as &$row) {
                if (!empty($row['images'])) {
                    $urls = is_array($row['images']) ? $row['images'] : explode(',', $row['images']);
                    $img_tags = [];
                    foreach ($urls as $url) {
                        $url = trim($url);
                        if (!empty($url)) {
                            $img_tags[] = '<img src="' . esc_url($url) . '" width="60">';
                        }
                    }
                    // Gambar turun ke bawah
                    $row['images'] = implode('<br>', $img_tags);
                }

                if ($first) {
                    echo '<tr>';
                    foreach (array_keys($row) as $h) echo '<th>' . esc_html($h) . '</th>';
                    echo '</tr>';
                    $first = false;
                }

                echo '<tr>';
                foreach ($row as $c) echo '<td>' . $c . '</td>';
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

                foreach ($products as $row) {
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
                    $maxExtraRows = 0;
                    foreach ($row as $c) {
                        if (is_array($c) && !empty($c) && filter_var(reset($c), FILTER_VALIDATE_URL)) {
                            $imgRow = $rowNum;
                            foreach ($c as $url) {
                                $tmpFile = download_url($url);
                                if (!is_wp_error($tmpFile)) {
                                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                                    $drawing->setPath($tmpFile);
                                    $drawing->setHeight(50);
                                    $drawing->setCoordinates(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $imgRow);
                                    $drawing->setWorksheet($sheet);
                                }
                                $imgRow++;
                            }
                            $extraRows = count($c) - 1;
                            if ($extraRows > $maxExtraRows) {
                                $maxExtraRows = $extraRows;
                            }
                        } else {
                            $sheet->setCellValueByColumnAndRow($col, $rowNum, $c);
                        }
                        $col++;
                    }
                    $rowNum += ($maxExtraRows + 1);
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


        // Fallback ke XLS kalau XLSX nggak bisa
        else {
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
            echo "<table border=1>";
            $first = true;
            foreach ($products as $row) {
                if ($first) {
                    echo '<tr>';
                    foreach (array_keys($row) as $h) echo '<th>' . esc_html($h) . '</th>';
                    echo '</tr>';
                    $first = false;
                }
                echo '<tr>';
                foreach ($row as $c) echo '<td>' . esc_html($c) . '</td>';
                echo '</tr>';
            }
            echo "</table>";
            return;
        }
    }


    private function get_terms_list($post_id, $taxonomy)
    {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
        if (is_wp_error($terms)) return '';
        return implode('|', $terms);
    }

    private function get_product_attributes($product)
    {
        $attrs = array();
        if (method_exists($product, 'get_attributes')) {
            foreach ($product->get_attributes() as $attr) {

                // Pastikan kita handle semua tipe
                if (is_object($attr) && method_exists($attr, 'get_name')) {
                    $name = wc_attribute_label($attr->get_name());
                    $options = $attr->get_options();
                    if (is_array($options)) {
                        $options = implode(',', array_map('wc_clean', $options));
                    }
                    $attrs[] = $name . ':' . $options;
                } elseif (is_array($attr) && isset($attr['name'])) {
                    $name = wc_attribute_label($attr['name']);
                    $options = isset($attr['options']) ? implode(',', (array) $attr['options']) : '';
                    $attrs[] = $name . ':' . $options;
                } elseif (is_string($attr)) {
                    $attrs[] = $attr;
                }
            }
        }
        return implode('|', $attrs);
    }
}
