<?php

/**
 *Plugin Name: Woo Export Import Pro
 *Description: Advanced exporter/importer for WooCommerce (XLSX via PhpSpreadsheet if installed via composer). Exports: Products, Orders, Users, Analytics. Includes composer.json but not vendor/ - run composer install in plugin folder to enable XLSX.
 *Version: 0.2
 * Author: Puji Ermanto <pujiermanto@gmail.com> | AKA Dadang Sukamenak
 * Author URI: https://pujiermanto-portfolio.vercel.app
 */

if (!defined('ABSPATH')) exit;

class WEIP_Pro
{
    private static $instance = null;
    private $has_phpspreadsheet = false;

    public static function init()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // detect phpspreadsheet
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                $this->has_phpspreadsheet = true;
            }
        }

        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_post_wei_export', array($this, 'handle_export'));
        add_action('admin_post_wei_import', array($this, 'handle_import'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    public function add_admin_page()
    {
        add_submenu_page('tools.php', 'WC Export Import Pro', 'WC Export/Import Pro', 'manage_options', 'wei-export-import-pro', array($this, 'render_admin_page'));
    }

    public function render_admin_page()
    {
        if (!current_user_can('manage_options')) return;
?>
        <div class="wrap">
            <h1>Woo Export / Import Pro</h1>
            <?php if (!$this->has_phpspreadsheet): ?>
                <div class="notice notice-warning">
                    <p><strong>PhpSpreadsheet not found.</strong> To enable proper .xlsx export/import, run <code>composer install</code> in the plugin folder or upload the <code>vendor/</code> folder. See instructions below.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p>PhpSpreadsheet is available — .xlsx export/import enabled.</p>
                </div>
            <?php endif; ?>

            <h2>Export</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wei_export">
                <?php wp_nonce_field('wei_export_nonce', 'wei_export_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th>Entity</th>
                        <td>
                            <select name="entity">
                                <option value="products">Products</option>
                                <option value="orders">Orders</option>
                                <option value="users">Users</option>
                                <option value="analytics">Analytics (sales summary)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Format</th>
                        <td>
                            <select name="format">
                                <option value="csv">CSV</option>
                                <option value="xls">Excel (.xls)</option>
                                <option value="xlsx">Excel (.xlsx) <?php if (!$this->has_phpspreadsheet) echo ' (requires composer)'; ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Delimiter (CSV)</th>
                        <td><input type="text" name="delimiter" value="," maxlength="1" style="width:50px"></td>
                    </tr>
                    <tr>
                        <th>Filename</th>
                        <td><input type="text" name="filename" value="" placeholder="optional - leave blank for auto"></td>
                    </tr>
                    <tr>
                        <th>Batch size (for large stores)</th>
                        <td><input type="number" name="batch" value="200" min="50" max="5000"></td>
                    </tr>
                </table>
                <?php submit_button('Export'); ?>
            </form>

            <h2>Import (CSV / XLSX)</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="wei_import">
                <?php wp_nonce_field('wei_import_nonce', 'wei_import_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th>Entity</th>
                        <td>
                            <select name="entity">
                                <option value="products">Products</option>
                                <option value="orders">Orders</option>
                                <option value="users">Users</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>File (CSV or XLSX)</th>
                        <td><input type="file" name="file" accept=".csv,.xlsx,.xls,text/csv"></td>
                    </tr>
                    <tr>
                        <th>Delimiter (CSV only)</th>
                        <td><input type="text" name="delimiter" value="," maxlength="1" style="width:50px"></td>
                    </tr>
                    <tr>
                        <th>Mapping profile</th>
                        <td><em>Mapping profiles will be added in next step.</em></td>
                    </tr>
                </table>
                <?php submit_button('Import'); ?>
            </form>

            <h3>Instructions to enable .xlsx (composer)</h3>
            <ol>
                <li>SSH into your server and navigate to <code>wp-content/plugins/woo-export-import-pro</code> (or upload plugin folder).</li>
                <li>Run: <code>composer install --no-dev -o</code> (Composer must be installed on server).</li>
                <li>After vendor/ is present, reload this plugin page — .xlsx support will be available.</li>
            </ol>
            <p>If you cannot run composer on the server, run <code>composer install</code> locally and upload the resulting <code>vendor/</code> folder into the plugin directory, or ask me to produce a ZIP with vendor included.</p>

            <p><strong>Notes:</strong> Always backup DB before import. For stores with very large datasets, consider smaller batch size or specialized streaming libraries (eg. Spout) to avoid memory issues.</p>
        </div>
<?php
    }

    public function admin_notices()
    {
        // Show notice on plugins page if phpSpreadsheet missing and user is admin
        if (!current_user_can('manage_options')) return;
        $screen = get_current_screen();
        // only show on plugin or tools page for non-intrusive experience
        if (isset($screen->id) && in_array($screen->id, array('plugins', 'tools_page_wei-export-import-pro'))) {
            if (!$this->has_phpspreadsheet) {
                echo '<div class="notice notice-warning"><p><strong>Woo Export Import Pro:</strong> PhpSpreadsheet not found. Run <code>composer install</code> in plugin folder to enable .xlsx support.</p></div>';
            }
        }
    }

    public function handle_export()
    {
        if (!current_user_can('manage_options')) wp_die('No.');
        if (!isset($_POST['wei_export_nonce_field']) || !wp_verify_nonce($_POST['wei_export_nonce_field'], 'wei_export_nonce')) {
            wp_die('Invalid nonce');
        }
        $entity = sanitize_text_field($_POST['entity']);
        $format = sanitize_text_field($_POST['format']);
        $delimiter = isset($_POST['delimiter']) ? substr(sanitize_text_field($_POST['delimiter']), 0, 1) : ',';
        $filename = sanitize_text_field($_POST['filename']);
        $batch = isset($_POST['batch']) ? max(50, intval($_POST['batch'])) : 200;
        if (empty($filename)) {
            $filename = $entity . '-' . date('Ymd-His');
        }
        $filename = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $filename);

        switch ($entity) {
            case 'products':
                $gather = array($this, 'gather_products');
                break;
            case 'orders':
                $gather = array($this, 'gather_orders');
                break;
            case 'users':
                $gather = array($this, 'gather_users');
                break;
            case 'analytics':
                $gather = array($this, 'gather_analytics');
                break;
            default:
                wp_die('Unknown entity');
        }

        // produce export depending on format
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            $first = true;
            foreach (call_user_func($gather, $batch) as $row) {
                if ($first) {
                    fputcsv($out, array_keys($row), $delimiter);
                    $first = false;
                }
                fputcsv($out, $row, $delimiter);
            }
            if ($out) fclose($out);
            exit;
        } elseif ($format === 'xls') {
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
            echo "<table border=1>";
            $first = true;
            foreach (call_user_func($gather, $batch) as $row) {
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
            exit;
        } elseif ($format === 'xlsx') {
            if (!$this->has_phpspreadsheet) {
                // fallback to xls if lib not present
                header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
                echo "<table border=1>";
                $first = true;
                foreach (call_user_func($gather, $batch) as $row) {
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
                exit;
            }
            // use PhpSpreadsheet
            try {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $rowIndex = 1;
                $headers_written = false;
                foreach (call_user_func($gather, $batch) as $row) {
                    if (!$headers_written) {
                        $col = 'A';
                        foreach (array_keys($row) as $h) {
                            $sheet->setCellValue($col . $rowIndex, $h);
                            $col++;
                        }
                        $rowIndex++;
                        $headers_written = true;
                    }
                    $col = 'A';
                    foreach ($row as $c) {
                        $sheet->setCellValue($col . $rowIndex, $c);
                        $col++;
                    }
                    $rowIndex++;
                }
                // send file
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
                exit;
            } catch (\Exception $e) {
                wp_die('Failed to generate XLSX: ' . esc_html($e->getMessage()));
            }
        }
    }

    public function handle_import()
    {
        if (!current_user_can('manage_options')) wp_die('No.');
        if (!isset($_POST['wei_import_nonce_field']) || !wp_verify_nonce($_POST['wei_import_nonce_field'], 'wei_import_nonce')) {
            wp_die('Invalid nonce');
        }
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg('wei_import_msg', 'nofile', wp_get_referer()));
            exit;
        }
        $entity = sanitize_text_field($_POST['entity']);
        $delimiter = isset($_POST['delimiter']) ? substr(sanitize_text_field($_POST['delimiter']), 0, 1) : ',';
        $tmp = $_FILES['file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $rows = array();
        if (in_array($ext, array('xls', 'csv'))) {
            $handle = fopen($tmp, 'r');
            if (!$handle) {
                wp_redirect(add_query_arg('wei_import_msg', 'openfail', wp_get_referer()));
                exit;
            }
            $header = fgetcsv($handle, 0, $delimiter);
            if (!$header) {
                fclose($handle);
                wp_redirect(add_query_arg('wei_import_msg', 'badcsv', wp_get_referer()));
                exit;
            }
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $data = array();
                foreach ($header as $i => $col) $data[$col] = isset($row[$i]) ? $row[$i] : '';
                $rows[] = $data;
            }
            fclose($handle);
        } elseif ($ext === 'xlsx') {
            if (!$this->has_phpspreadsheet) {
                wp_redirect(add_query_arg('wei_import_msg', 'need_phpspreadsheet', wp_get_referer()));
                exit;
            }
            try {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $spread = $reader->load($tmp);
                $sheet = $spread->getActiveSheet();
                $data = $sheet->toArray(null, true, true, true);
                // convert to rows with header
                $header = array();
                foreach ($data as $r => $cols) {
                    if ($r == 1) {
                        foreach ($cols as $c) $header[] = (string)$c;
                        continue;
                    }
                    $row = array();
                    $i = 0;
                    foreach ($cols as $c) {
                        $colname = isset($header[$i]) ? $header[$i] : 'col' . $i;
                        $row[$colname] = $c;
                        $i++;
                    }
                    $rows[] = $row;
                }
            } catch (\Exception $e) {
                wp_redirect(add_query_arg('wei_import_msg', 'xlsxfail', wp_get_referer()));
                exit;
            }
        } else {
            wp_redirect(add_query_arg('wei_import_msg', 'badext', wp_get_referer()));
            exit;
        }

        $count = 0;
        foreach ($rows as $data) {
            if ($entity === 'products') $this->import_product_row($data);
            if ($entity === 'users') $this->import_user_row($data);
            if ($entity === 'orders') $this->import_order_row($data);
            $count++;
        }
        wp_redirect(add_query_arg('wei_import_msg', 'imported_' . $count, wp_get_referer()));
        exit;
    }

    // ---- Gatherers with simple batching ----
    public function gather_products($batch = 200)
    {
        if (!function_exists('wc_get_products')) return array();
        $page = 1;
        while (true) {
            $args = array('limit' => $batch, 'page' => $page, 'status' => array('publish', 'private', 'draft'));
            $prods = wc_get_products($args);
            if (empty($prods)) break;
            foreach ($prods as $p) {
                $row = array();
                $row['ID'] = $p->get_id();
                $row['Title'] = $p->get_name();
                $row['SKU'] = $p->get_sku();
                $row['Price'] = $p->get_price();
                $row['Regular Price'] = $p->get_regular_price();
                $row['Sale Price'] = $p->get_sale_price();
                $row['Stock'] = $p->get_stock_quantity();
                $row['Type'] = $p->get_type();
                $row['Categories'] = $this->get_terms_list($p->get_id(), 'product_cat');
                $row['Tags'] = $this->get_terms_list($p->get_id(), 'product_tag');
                $row['Attributes'] = $this->get_product_attributes($p);
                // images
                $imgs = $p->get_image_id() ? wp_get_attachment_url($p->get_image_id()) : '';
                // gallery
                $gallery = $p->get_gallery_image_ids();
                if (!empty($gallery) && is_array($gallery)) {
                    $srcs = array();
                    foreach ($gallery as $gid) {
                        $srcs[] = wp_get_attachment_url($gid);
                    }
                    if ($srcs) $imgs .= '|' . implode('|', $srcs);
                }
                $row['Images'] = $imgs;
                // variations summary
                if ($p->is_type('variable')) {
                    $variations = $p->get_children();
                    $vlist = array();
                    foreach ($variations as $vid) {
                        $vp = wc_get_product($vid);
                        if ($vp) $vlist[] = $vp->get_sku() . ':' . $vp->get_price() . ':' . $vp->get_stock_quantity();
                    }
                    $row['Variations'] = implode('|', $vlist);
                } else {
                    $row['Variations'] = '';
                }
                yield $row;
            }
            $page++;
            // allow time
            if (function_exists('wp_suspend_cache_addition')) {
                wp_suspend_cache_addition(true);
                wp_suspend_cache_addition(false);
            }
        }
    }

    public function gather_orders($batch = 200)
    {
        if (!class_exists('WC_Order_Query')) return array();
        $page = 1;
        while (true) {
            $orders = wc_get_orders(array('limit' => $batch, 'page' => $page));
            if (empty($orders)) break;
            foreach ($orders as $o) {
                $row = array();
                $row['ID'] = $o->get_id();
                $row['Order Number'] = $o->get_order_number();
                $row['Date'] = $o->get_date_created() ? $o->get_date_created()->date('Y-m-d H:i:s') : '';
                $row['Status'] = $o->get_status();
                $row['Total'] = $o->get_total();
                $row['Currency'] = $o->get_currency();
                $row['Customer ID'] = $o->get_user_id();
                $row['Billing Name'] = $o->get_billing_first_name() . ' ' . $o->get_billing_last_name();
                $row['Billing Email'] = $o->get_billing_email();
                $items = array();
                foreach ($o->get_items() as $item) {
                    $items[] = $item->get_name() . ' x' . $item->get_quantity() . ' (' . $item->get_total() . ')';
                }
                $row['Items'] = implode(' | ', $items);
                yield $row;
            }
            $page++;
        }
    }

    public function gather_users($batch = 500)
    {
        $offset = 0;
        while (true) {
            $users = get_users(array('number' => $batch, 'offset' => $offset));
            if (empty($users)) break;
            foreach ($users as $u) {
                $row = array();
                $row['ID'] = $u->ID;
                $row['User Login'] = $u->user_login;
                $row['Display Name'] = $u->display_name;
                $row['Email'] = $u->user_email;
                $row['Registered'] = $u->user_registered;
                $row['Role'] = implode(',', $u->roles);
                yield $row;
            }
            $offset += $batch;
        }
    }

    public function gather_analytics($batch = 200)
    {
        if (!class_exists('WC_Order_Query')) return array();
        // simple: sales per day
        $agg = array();
        $page = 1;
        while (true) {
            $orders = wc_get_orders(array('limit' => $batch, 'page' => $page));
            if (empty($orders)) break;
            foreach ($orders as $o) {
                $d = $o->get_date_created() ? $o->get_date_created()->date('Y-m-d') : '';
                if (!isset($agg[$d])) $agg[$d] = array('orders' => 0, 'revenue' => 0);
                $agg[$d]['orders'] += 1;
                $agg[$d]['revenue'] += (float)$o->get_total();
            }
            $page++;
        }
        foreach ($agg as $day => $vals) {
            yield array('Date' => $day, 'Orders' => $vals['orders'], 'Revenue' => number_format($vals['revenue'], 2, '.', ''));
        }
    }

    // helpers
    private function get_terms_list($post_id, $taxonomy)
    {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
        if (is_wp_error($terms)) return '';
        return implode('|', $terms);
    }

    private function get_product_attributes($product)
    {
        $attrs = array();
        foreach ($product->get_attributes() as $attr) {
            $attrs[] = $attr->get_name() . ':' . implode(',', $attr->get_options());
        }
        return implode('|', $attrs);
    }

    // ---- Import handlers (basic) ----
    private function import_product_row($data)
    {
        $id = !empty($data['ID']) ? intval($data['ID']) : 0;
        $post = array(
            'post_title' => sanitize_text_field($data['Title'] ?? ''),
            'post_type' => 'product',
            'post_status' => 'publish',
        );
        if ($id) {
            $post['ID'] = $id;
            wp_update_post($post);
            $pid = $id;
        } else {
            $pid = wp_insert_post($post);
        }
        if (!$pid) return;
        if (!empty($data['SKU'])) update_post_meta($pid, '_sku', sanitize_text_field($data['SKU']));
        if (!empty($data['Price'])) update_post_meta($pid, '_price', sanitize_text_field($data['Price']));
        if (!empty($data['Regular Price'])) update_post_meta($pid, '_regular_price', sanitize_text_field($data['Regular Price']));
        if (!empty($data['Sale Price'])) update_post_meta($pid, '_sale_price', sanitize_text_field($data['Sale Price']));
        if (!empty($data['Stock'])) update_post_meta($pid, '_stock', intval($data['Stock']));
        if (!empty($data['Categories'])) {
            $cats = array_map('trim', explode('|', $data['Categories']));
            wp_set_post_terms($pid, $cats, 'product_cat', true);
        }
        if (!empty($data['Tags'])) {
            $tags = array_map('trim', explode('|', $data['Tags']));
            wp_set_post_terms($pid, $tags, 'product_tag', true);
        }
    }

    private function import_user_row($data)
    {
        $email = isset($data['Email']) ? sanitize_email($data['Email']) : '';
        $login = isset($data['User Login']) ? sanitize_text_field($data['User Login']) : '';
        $user_id = 0;
        if ($email) {
            $u = get_user_by('email', $email);
            if ($u) $user_id = $u->ID;
        }
        if (!$user_id && $login) {
            $u = get_user_by('login', $login);
            if ($u) $user_id = $u->ID;
        }
        if ($user_id) {
            wp_update_user(array('ID' => $user_id, 'display_name' => sanitize_text_field($data['Display Name'] ?? '')));
        } else {
            if (!$login && $email) $login = sanitize_text_field(strstr($email, '@', true) ?: 'user' . time());
            $pass = wp_generate_password(12);
            $user_id = wp_create_user($login, $pass, $email);
            if ($user_id && !is_wp_error($user_id)) {
                wp_update_user(array('ID' => $user_id, 'display_name' => sanitize_text_field($data['Display Name'] ?? '')));
            }
        }
        if ($user_id && !empty($data['Role'])) {
            $role = sanitize_text_field($data['Role']);
            $user = new WP_User($user_id);
            $user->set_role($role);
        }
    }

    private function import_order_row($data)
    {
        if (!function_exists('wc_create_order')) return;
        $order = wc_create_order();
        if (!empty($data['Billing Email'])) $order->set_billing_email(sanitize_email($data['Billing Email']));
        if (!empty($data['Billing Name'])) {
            $parts = explode(' ', $data['Billing Name'], 2);
            $order->set_billing_first_name($parts[0]);
            if (count($parts) > 1) $order->set_billing_last_name($parts[1]);
        }
        if (!empty($data['Total'])) $order->set_total(floatval($data['Total']));
        $order->save();
    }
}

add_action('plugins_loaded', array('WEIP_Pro', 'init'));
