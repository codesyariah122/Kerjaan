<?php
/*
Plugin Name: Woo Export Import Lite
Description: Simple exporter/importer for WooCommerce products, orders, users and analytics. Exports CSV and simple Excel (.xls). Imports CSV for products/users/orders. Extendable.
Version: 0.1
Author: ChatGPT (generated)
*/

if (!defined('ABSPATH')) exit;

class Woo_Export_Import_Lite {
    private static $instance = null;
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_post_wei_export', array($this, 'handle_export'));
        add_action('admin_post_wei_import', array($this, 'handle_import'));
    }
    public function add_admin_page() {
        add_submenu_page('tools.php', 'WC Export Import', 'WC Export/Import', 'manage_options', 'wei-export-import', array($this, 'render_admin_page'));
    }
    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>Woo Export / Import Lite</h1>
            <h2>Export</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wei_export">
                <?php wp_nonce_field('wei_export_nonce','wei_export_nonce_field'); ?>
                <table class="form-table">
                    <tr><th>Entity</th><td>
                        <select name="entity">
                            <option value="products">Products</option>
                            <option value="orders">Orders</option>
                            <option value="users">Users</option>
                            <option value="analytics">Analytics (sales summary)</option>
                        </select>
                    </td></tr>
                    <tr><th>Format</th><td>
                        <select name="format">
                            <option value="csv">CSV</option>
                            <option value="xls">Excel (.xls)</option>
                        </select>
                    </td></tr>
                    <tr><th>Delimiter (CSV)</th><td><input type="text" name="delimiter" value="," maxlength="1" style="width:50px"></td></tr>
                    <tr><th>Filename</th><td><input type="text" name="filename" value="" placeholder="optional - leave blank for auto"></td></tr>
                </table>
                <?php submit_button('Export'); ?>
            </form>

            <h2>Import (CSV)</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="wei_import">
                <?php wp_nonce_field('wei_import_nonce','wei_import_nonce_field'); ?>
                <table class="form-table">
                    <tr><th>Entity</th><td>
                        <select name="entity">
                            <option value="products">Products</option>
                            <option value="orders">Orders</option>
                            <option value="users">Users</option>
                        </select>
                    </td></tr>
                    <tr><th>CSV file</th><td><input type="file" name="csv_file" accept=".csv,text/csv"></td></tr>
                    <tr><th>Delimiter</th><td><input type="text" name="delimiter" value="," maxlength="1" style="width:50px"></td></tr>
                </table>
                <?php submit_button('Import'); ?>
            </form>

            <p><strong>Notes:</strong> This is a lightweight starter plugin. Exports CSV and a simple Excel-compatible .xls (HTML table). For full .xlsx support and richer import mapping use PhpSpreadsheet or integrate with WP All Import hooks. Always backup database before import.</p>
        </div>
        <?php
    }

    private function sanitize_filename($name) {
        $name = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $name);
        return $name;
    }

    public function handle_export() {
        if (!current_user_can('manage_options')) wp_die('No.');
        if (!isset($_POST['wei_export_nonce_field']) || !wp_verify_nonce($_POST['wei_export_nonce_field'],'wei_export_nonce')) {
            wp_die('Invalid nonce');
        }
        $entity = sanitize_text_field($_POST['entity']);
        $format = sanitize_text_field($_POST['format']);
        $delimiter = isset($_POST['delimiter']) ? substr(sanitize_text_field($_POST['delimiter']),0,1) : ',';
        $filename = sanitize_text_field($_POST['filename']);
        if (empty($filename)) {
            $filename = $entity . '-' . date('Ymd-His');
        }
        $filename = $this->sanitize_filename($filename);

        // gather data
        $rows = array();
        switch ($entity) {
            case 'products':
                $rows = $this->gather_products();
                break;
            case 'orders':
                $rows = $this->gather_orders();
                break;
            case 'users':
                $rows = $this->gather_users();
                break;
            case 'analytics':
                $rows = $this->gather_analytics();
                break;
            default:
                wp_die('Unknown entity');
        }

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="'. $filename .'.csv"');
            // output BOM for excel utf-8
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            if (!empty($rows)) {
                // headers
                fputcsv($out, array_keys($rows[0]), $delimiter);
                foreach ($rows as $r) {
                    fputcsv($out, $r, $delimiter);
                }
            }
            fclose($out);
            exit;
        } else { // xls simple (HTML table)
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="'. $filename .'.xls"');
            echo "<table border=1>";
            if (!empty($rows)) {
                // header
                echo '<tr>';
                foreach (array_keys($rows[0]) as $h) {
                    echo '<th>' . esc_html($h) . '</th>';
                }
                echo '</tr>';
                foreach ($rows as $r) {
                    echo '<tr>';
                    foreach ($r as $c) {
                        echo '<td>' . esc_html($c) . '</td>';
                    }
                    echo '</tr>';
                }
            }
            echo "</table>";
            exit;
        }
    }

    public function handle_import() {
        if (!current_user_can('manage_options')) wp_die('No.');
        if (!isset($_POST['wei_import_nonce_field']) || !wp_verify_nonce($_POST['wei_import_nonce_field'],'wei_import_nonce')) {
            wp_die('Invalid nonce');
        }
        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg('wei_import_msg','nofile', wp_get_referer()));
            exit;
        }
        $entity = sanitize_text_field($_POST['entity']);
        $delimiter = isset($_POST['delimiter']) ? substr(sanitize_text_field($_POST['delimiter']),0,1) : ',';
        $tmp = $_FILES['csv_file']['tmp_name'];

        $handle = fopen($tmp, 'r');
        if (!$handle) {
            wp_redirect(add_query_arg('wei_import_msg','openfail', wp_get_referer()));
            exit;
        }
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            wp_redirect(add_query_arg('wei_import_msg','badcsv', wp_get_referer()));
            exit;
        }
        $count = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $data = array();
            foreach ($header as $i => $col) {
                $data[$col] = isset($row[$i]) ? $row[$i] : '';
            }
            // simple import handlers
            if ($entity === 'products') {
                $this->import_product_row($data);
            } elseif ($entity === 'users') {
                $this->import_user_row($data);
            } elseif ($entity === 'orders') {
                $this->import_order_row($data);
            }
            $count++;
        }
        fclose($handle);
        wp_redirect(add_query_arg('wei_import_msg','imported_'.$count, wp_get_referer()));
        exit;
    }

    // ---- Data gatherers ----
    private function gather_products() {
        if (!function_exists('wc_get_products')) {
            return array();
        }
        $args = array(
            'limit' => -1,
            'status' => 'publish',
        );
        $prods = wc_get_products($args);
        $rows = array();
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
            $rows[] = $row;
        }
        return $rows;
    }

    private function gather_orders() {
        if (!class_exists('WC_Order_Query')) return array();
        $orders = wc_get_orders(array('limit'=>-1));
        $rows = array();
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
            // products in order as JSON summary
            $items = array();
            foreach ($o->get_items() as $item) {
                $items[] = $item->get_name() . ' x' . $item->get_quantity() . ' (' . $item->get_total() . ')';
            }
            $row['Items'] = implode(' | ', $items);
            $rows[] = $row;
        }
        return $rows;
    }

    private function gather_users() {
        $users = get_users(array('number'=>-1));
        $rows = array();
        foreach ($users as $u) {
            $row = array();
            $row['ID'] = $u->ID;
            $row['User Login'] = $u->user_login;
            $row['Display Name'] = $u->display_name;
            $row['Email'] = $u->user_email;
            $row['Registered'] = $u->user_registered;
            $row['Role'] = implode(',', $u->roles);
            $rows[] = $row;
        }
        return $rows;
    }

    private function gather_analytics() {
        // Simple analytics: sales per day (orders)
        if (!class_exists('WC_Order_Query')) return array();
        $orders = wc_get_orders(array('limit'=>-1));
        $agg = array();
        foreach ($orders as $o) {
            $d = $o->get_date_created() ? $o->get_date_created()->date('Y-m-d') : '';
            if (!isset($agg[$d])) $agg[$d] = array('orders'=>0,'revenue'=>0);
            $agg[$d]['orders'] += 1;
            $agg[$d]['revenue'] += (float) $o->get_total();
        }
        $rows = array();
        foreach ($agg as $day => $vals) {
            $rows[] = array('Date'=>$day, 'Orders'=>$vals['orders'], 'Revenue'=>number_format($vals['revenue'],2,'.',''));
        }
        return $rows;
    }

    // helpers
    private function get_terms_list($post_id, $taxonomy) {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields'=>'names'));
        if (is_wp_error($terms)) return '';
        return implode('|', $terms);
    }

    private function get_product_attributes($product) {
        $attrs = array();
        foreach ($product->get_attributes() as $attr) {
            $attrs[] = $attr->get_name() . ':' . implode(',', $attr->get_options());
        }
        return implode('|', $attrs);
    }

    // ---- Import handlers (very basic!) ----
    private function import_product_row($data) {
        // expects columns like ID (optional), Title, SKU, Price, Regular Price, Stock, Type, Categories (pipe separated), Tags
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
        // categories/tags simple handling
        if (!empty($data['Categories'])) {
            $cats = array_map('trim', explode('|',$data['Categories']));
            wp_set_post_terms($pid, $cats, 'product_cat', true);
        }
        if (!empty($data['Tags'])) {
            $tags = array_map('trim', explode('|',$data['Tags']));
            wp_set_post_terms($pid, $tags, 'product_tag', true);
        }
    }

    private function import_user_row($data) {
        // expects: User Login or Email (to find), Display Name, Email, Role
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
            wp_update_user(array('ID'=>$user_id, 'display_name'=>sanitize_text_field($data['Display Name'] ?? '')));
        } else {
            // create simple user with random password
            if (!$login && $email) $login = sanitize_text_field(strstr($email,'@',true) ?: 'user'.time());
            $pass = wp_generate_password(12);
            $user_id = wp_create_user($login, $pass, $email);
            if ($user_id && !is_wp_error($user_id)) {
                wp_update_user(array('ID'=>$user_id,'display_name'=>sanitize_text_field($data['Display Name'] ?? '')));
            }
        }
        if ($user_id && !empty($data['Role'])) {
            $role = sanitize_text_field($data['Role']);
            $user = new WP_User($user_id);
            $user->set_role($role);
        }
    }

    private function import_order_row($data) {
        // Very basic order import: creates an order with minimal info
        if (!function_exists('wc_create_order')) return;
        $order = wc_create_order();
        if (!empty($data['Billing Email'])) $order->set_billing_email(sanitize_email($data['Billing Email']));
        if (!empty($data['Billing Name'])) {
            $parts = explode(' ', $data['Billing Name'], 2);
            $order->set_billing_first_name($parts[0]);
            if (count($parts)>1) $order->set_billing_last_name($parts[1]);
        }
        if (!empty($data['Total'])) $order->set_total(floatval($data['Total']));
        $order->save();
    }
}

add_action('plugins_loaded', array('Woo_Export_Import_Lite','init'));
