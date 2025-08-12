<?php

/**
 * Plugin Name: WooCommerce Export Import Plus
 * Description: Export WooCommerce products (lengkap dengan meta kustom, variasi produk, atribut terpisah) + Mapping Profile.
 * Version: 2.0
 * Author: Puji Ermanto <pujiermanto@gmail.com> | AKA Dadang Sukamenak
 * Author URI: https://pujiermanto-portfolio.vercel.app
 */

if (!defined('ABSPATH')) exit;

class WAE_Plugin
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

    public function __construct()
    {
        // load optional phpspreadsheet
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                $this->has_phpspreadsheet = true;
            }
        }
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_post_wae_save_profile', array($this, 'save_profile'));
        add_action('admin_post_wae_delete_profile', array($this, 'delete_profile'));
        add_action('admin_post_wae_export_products', array($this, 'export_products'));
        add_action('admin_post_wae_export_orders', array($this, 'export_orders'));
        add_action('admin_post_wae_export_users', array($this, 'export_users'));
        // Enqueue SweetAlert
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array('jquery'), null, true);
        add_action('admin_footer', array($this, 'add_delete_confirmation_script'));
    }

    public function add_delete_confirmation_script()
    {
?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.button-link-delete').on('click', function(e) {
                    e.preventDefault(); // Mencegah penghapusan langsung
                    var deleteUrl = $(this).closest('form').attr('action'); // Ambil URL penghapusan

                    Swal.fire({
                        title: 'Masukkan Password',
                        input: 'password',
                        inputAttributes: {
                            autocapitalize: 'off'
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Hapus',
                        showLoaderOnConfirm: true,
                        preConfirm: (password) => {
                            if (password === 'pujiganteng') {
                                // Jika password benar, lanjutkan dengan penghapusan
                                window.location.href = deleteUrl; // Redirect ke URL penghapusan
                            } else {
                                Swal.showValidationMessage('Password salah!');
                            }
                        },
                        allowOutsideClick: () => !Swal.isLoading()
                    });
                });
            });
        </script>
    <?php
    }


    public function register_menu()
    {
        $cap = 'manage_woocommerce';
        add_menu_page('Woo Advanced Export', 'Woo Advanced Export', $cap, 'wae_main', array($this, 'page_export'), 'dashicons-download', 56);
        add_submenu_page('wae_main', 'Export Products', 'Export Products', $cap, 'wae_export', array($this, 'page_export_products'));
        add_submenu_page('wae_main', 'Mapping Profiles', 'Mapping Profiles', $cap, 'wae_mappings', array($this, 'page_mappings'));
        add_submenu_page('wae_main', 'Export Orders', 'Export Orders', $cap, 'wae_export_orders', array($this, 'page_export_orders'));
        add_submenu_page('wae_main', 'Export Users', 'Export Users', $cap, 'wae_export_users', array($this, 'page_export_users'));
    }

    private function get_profiles()
    {
        $raw = get_option('wae_mapping_profiles', '{}');
        $arr = json_decode($raw, true);
        if (!is_array($arr)) $arr = array();
        return $arr;
    }

    private function save_profiles($profiles)
    {
        update_option('wae_mapping_profiles', wp_json_encode($profiles), false);
    }

    public function page_export()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
    ?>
        <div class="wrap">
            <h1>Woo Advanced Export</h1>
            <p>Pilih jenis data yang ingin Anda export:</p>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=wae_export'); ?>">Export Products</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=wae_export_orders'); ?>">Export Orders</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=wae_export_users'); ?>">Export Users</a></li>
            </ul>
            <hr>
            <p><a href="<?php echo admin_url('admin.php?page=wae_mappings'); ?>">Manage Mapping Profiles</a></p>
        </div>
    <?php
    }


    public function page_export_products()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
        $profiles = $this->get_profiles();
    ?>
        <div class="wrap">
            <h1>Export Products</h1>
            <?php if (!$this->has_phpspreadsheet): ?>
                <div class="notice notice-warning">
                    <p>Note: .xlsx requires PhpSpreadsheet (run composer in plugin folder to enable).</p>
                </div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wae_export_products">
                <?php wp_nonce_field('wae_export_nonce', 'wae_export_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th>Mapping Profile</th>
                        <td>
                            <select name="profile">
                                <option value="">-- Default (all fields) --</option>
                                <?php foreach ($profiles as $key => $p): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Format</th>
                        <td>
                            <select name="format">
                                <option value="csv">CSV</option>
                                <option value="xls">XLS (HTML)</option>
                                <option value="xlsx">XLSX <?php if (!$this->has_phpspreadsheet) echo '(requires composer)'; ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Delimiter (CSV)</th>
                        <td><input type="text" name="delimiter" value="," maxlength="1" style="width:50px"></td>
                    </tr>
                    <tr>
                        <th>Batch size</th>
                        <td><input type="number" name="batch" value="200" min="50" max="5000"></td>
                    </tr>
                </table>
                <?php submit_button('Export Products'); ?>
            </form>
            <hr>
            <p><a href="<?php echo admin_url('admin.php?page=wae_mappings'); ?>">Manage Mapping Profiles</a></p>
        </div>
    <?php
    }

    public function page_export_orders()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');

        // Ambil semua mapping profiles
        $profiles = $this->get_profiles();

        // Default kolom untuk order export (bisa kamu sesuaikan)
        $fields = array(
            'ID' => 'Order ID',
            'date_created' => 'Date Created',
            'billing_email' => 'Billing Email',
            'total' => 'Total',
            'status' => 'Status',
            'billing_first_name' => 'Billing First Name',
            'billing_last_name' => 'Billing Last Name',
            'shipping_address_1' => 'Shipping Address 1',
            'shipping_address_2' => 'Shipping Address 2', // Kolom baru
            'billing_address_1' => 'Billing Address 1', // Kolom baru
            'billing_address_2' => 'Billing Address 2', // Kolom baru
            'payment_method' => 'Payment Method', // Kolom baru
            'payment_via' => 'Payment Via',
            'product_name' => 'Product Name', // Kolom item order
            'quantity' => 'Quantity', // Kolom item order
            'item_total' => 'Item Total', // Kolom item order
            'item_color' => 'Item Color', // Kolom item order
            // tambah kolom lain sesuai kebutuhan
        );

        // Status yang bisa dipilih
        $statuses = array('completed', 'processing', 'on-hold', 'canceled');
    ?>
        <div class="wrap">
            <h1>Export Orders</h1>
            <?php if (!$this->has_phpspreadsheet): ?>
                <div class="notice notice-warning">
                    <p>Note: .xlsx requires PhpSpreadsheet (run composer in plugin folder to enable).</p>
                </div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wae_export_orders">
                <?php wp_nonce_field('wae_export_nonce', 'wae_export_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th>Mapping Profile</th>
                        <td>
                            <select name="profile">
                                <option value="">-- Default (all fields) --</option>
                                <?php foreach ($profiles as $key => $p): ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($p['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <select name="order_status[]" multiple>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Hold Ctrl (Windows) or Command (Mac) to select multiple statuses.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Columns (check to include)</th>
                        <td>
                            <?php foreach ($fields as $k => $label): ?>
                                <label style="display:block"><input type="checkbox" name="columns[]" value="<?php echo esc_attr($k); ?>" checked> <?php echo esc_html($label); ?></label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Format</th>
                        <td>
                            <select name="format">
                                <option value="csv">CSV</option>
                                <option value="xls">XLS (HTML)</option>
                                <option value="xlsx">XLSX <?php if (!$this->has_phpspreadsheet) echo '(requires composer)'; ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Delimiter (CSV)</th>
                        <td><input type="text" name="delimiter" value="," maxlength="1" style="width:50px"></td>
                    </tr>
                    <tr>
                        <th>Batch size</th>
                        <td><input type="number" name="batch" value="200" min="50" max="5000"></td>
                    </tr>
                </table>
                <?php submit_button('Export Orders'); ?>
            </form>
        </div>
    <?php
    }

    public function page_export_users()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');

        // Default kolom untuk user export (bisa kamu sesuaikan)
        $fields = array(
            'ID' => 'User ID',
            'user_login' => 'User Login',
            'user_email' => 'User Email',
            'user_registered' => 'Registered Date',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'role' => 'User Role',
            // tambah kolom lain sesuai kebutuhan
        );

        // Ambil semua mapping profile
        $profiles_all = $this->get_profiles();

        // Filter profile hanya untuk tipe users
        $profiles = array_filter($profiles_all, function ($p) {
            return isset($p['type']) && $p['type'] === 'users';
        });

    ?>
        <div class="wrap">
            <h1>Export Users</h1>
            <?php if (!$this->has_phpspreadsheet): ?>
                <div class="notice notice-warning">
                    <p>Note: .xlsx requires PhpSpreadsheet (run composer in plugin folder to enable).</p>
                </div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wae_export_users">
                <?php wp_nonce_field('wae_export_nonce', 'wae_export_nonce_field'); ?>

                <table class="form-table">

                    <!-- Mapping Profile Select -->
                    <tr>
                        <th>Mapping Profile</th>
                        <td>
                            <select name="profile">
                                <option value="">-- Default (all fields) --</option>
                                <?php foreach ($profiles as $key => $p): ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($p['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <!-- Columns (checkbox) -->
                    <tr>
                        <th>Columns (check to include)</th>
                        <td>
                            <?php foreach ($fields as $k => $label): ?>
                                <label style="display:block"><input type="checkbox" name="columns[]" value="<?php echo esc_attr($k); ?>" checked> <?php echo esc_html($label); ?></label>
                            <?php endforeach; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Format</th>
                        <td>
                            <select name="format">
                                <option value="csv">CSV</option>
                                <option value="xls">XLS (HTML)</option>
                                <option value="xlsx">XLSX <?php if (!$this->has_phpspreadsheet) echo '(requires composer)'; ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Delimiter (CSV)</th>
                        <td><input type="text" name="delimiter" value="," maxlength="1" style="width:50px"></td>
                    </tr>
                    <tr>
                        <th>Batch size</th>
                        <td><input type="number" name="batch" value="200" min="50" max="5000"></td>
                    </tr>
                </table>
                <?php submit_button('Export Users'); ?>
            </form>
        </div>
    <?php
    }



    public function page_mappings()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');

        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'products';

        // Default fields per tipe
        $fields_products = array(
            'ID' => 'ID',
            'post_title' => 'Title',
            'sku' => 'SKU',
            'price' => 'Price',
            'regular_price' => 'Regular Price',
            'sale_price' => 'Sale Price',
            'stock' => 'Stock',
            'type' => 'Type',
            'categories' => 'Categories',
            'tags' => 'Tags',
            'attributes' => 'Attributes',
            'images' => 'Images',
            'variations' => 'Variations'
        );

        $fields_orders = array(
            'ID' => 'Order ID',
            'date_created' => 'Date Created',
            'billing_email' => 'Billing Email',
            'total' => 'Total',
            'status' => 'Status',
            'billing_first_name' => 'Billing First Name',
            'billing_last_name' => 'Billing Last Name',
            'shipping_address_1' => 'Shipping Address 1',
            'order_status' => 'Status',

        );

        $fields_users = array(
            'ID' => 'User ID',
            'user_login' => 'User Login',
            'user_email' => 'User Email',
            'user_registered' => 'Registered Date',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'role' => 'User Role',
            // tambahkan field lainnya
        );

        // Pilih fields berdasarkan tipe
        $fields = [];
        if ($type === 'products') {
            $fields = $fields_products;
        } elseif ($type === 'orders') {
            $fields = $fields_orders;
        } elseif ($type === 'users') {
            $fields = $fields_users;
        }

        // Load semua profiles, lalu filter berdasarkan type juga (kamu bisa modifikasi struktur profile-nya)
        $profiles = $this->get_profiles();

        // Jika ingin pisahkan profiles per type, bisa simpan profile dengan key prefixed misal: products_profile1, orders_profile1 dst

    ?>
        <div class="wrap">
            <h1>Mapping Profiles</h1>
            <p>
                <a href="<?php echo admin_url('admin.php?page=wae_mappings&type=products'); ?>">Products</a> |
                <a href="<?php echo admin_url('admin.php?page=wae_mappings&type=orders'); ?>">Orders</a> |
                <a href="<?php echo admin_url('admin.php?page=wae_mappings&type=users'); ?>">Users</a>
            </p>

            <h2>Existing Profiles for <?php echo ucfirst($type); ?></h2>
            <?php
            // Tampilkan hanya profiles untuk tipe ini, misal filter key profile dengan prefix $type
            $filtered_profiles = [];
            foreach ($profiles as $key => $p) {
                if (strpos($key, $type . '_') === 0) {
                    $filtered_profiles[$key] = $p;
                }
            }
            if (empty($filtered_profiles)) {
                echo '<p>No profiles yet.</p>';
            } else {
            ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Columns</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_profiles as $key => $p): ?>
                            <tr>
                                <td><?php echo esc_html($p['name']); ?></td>
                                <td><?php echo esc_html(implode(', ', $p['columns'])); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'wae_mappings', 'edit' => $key, 'type' => $type), admin_url('admin.php'))); ?>">Edit</a> |
                                    <form style="display:inline" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <?php wp_nonce_field('wae_delete_nonce', 'wae_delete_nonce_field'); ?>
                                        <input type="hidden" name="action" value="wae_delete_profile">
                                        <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
                                        <button class="button-link-delete" onclick="return confirm('Delete profile?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php
            }
            ?>

            <h2><?php echo isset($_GET['edit']) ? 'Edit' : 'Create'; ?> Profile for <?php echo ucfirst($type); ?></h2>
            <?php
            $editing = null;
            if (isset($_GET['edit'])) {
                $key = sanitize_text_field($_GET['edit']);
                if (isset($profiles[$key])) $editing = $profiles[$key];
            }
            ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('wae_save_nonce', 'wae_save_nonce_field'); ?>
                <input type="hidden" name="action" value="wae_save_profile">
                <input type="hidden" name="type" value="<?php echo esc_attr($type); ?>">
                <table class="form-table">
                    <tr>
                        <th>Profile Name</th>
                        <td><input type="text" name="name" value="<?php echo esc_attr($editing['name'] ?? ''); ?>" required></td>
                    </tr>
                    <tr>
                        <th>Columns (check to include)</th>
                        <td>
                            <?php foreach ($fields as $k => $label): ?>
                                <label style="display:block">
                                    <input type="checkbox" name="columns[]" value="<?php echo esc_attr($k); ?>"
                                        <?php if ($editing && in_array($k, $editing['columns'])) echo 'checked'; ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(isset($editing) ? 'Update Profile' : 'Create Profile'); ?>
            </form>
        </div>
<?php
    }

    public function save_profile()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
        if (!isset($_POST['wae_save_nonce_field']) || !wp_verify_nonce($_POST['wae_save_nonce_field'], 'wae_save_nonce')) wp_die('Invalid nonce');

        $name = sanitize_text_field($_POST['name'] ?? '');
        $cols = isset($_POST['columns']) ? array_map('sanitize_text_field', (array)$_POST['columns']) : array();
        $type = sanitize_text_field($_POST['type'] ?? 'products'); // default ke products

        if (empty($name) || empty($cols)) {
            wp_redirect(add_query_arg('msg', 'bad', wp_get_referer()));
            exit;
        }
        $profiles = $this->get_profiles();

        // Buat key unik dengan prefix tipe
        $key = $type . '_' . sanitize_title($name);

        $profiles[$key] = array(
            'name' => $name,
            'columns' => $cols,
            'type' => $type,
        );
        $this->save_profiles($profiles);

        wp_redirect(add_query_arg('msg', 'saved', admin_url('admin.php?page=wae_mappings&type=' . $type)));
        exit;
    }

    public function delete_profile()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
        if (!isset($_POST['wae_delete_nonce_field']) || !wp_verify_nonce($_POST['wae_delete_nonce_field'], 'wae_delete_nonce')) wp_die('Invalid nonce');
        $key = sanitize_text_field($_POST['key'] ?? '');
        $profiles = $this->get_profiles();
        if (isset($profiles[$key])) {
            unset($profiles[$key]);
            $this->save_profiles($profiles);
        }
        wp_redirect(add_query_arg('msg', 'deleted', admin_url('admin.php?page=wae_mappings')));
        exit;
    }

    public function export_products()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
        if (!isset($_POST['wae_export_nonce_field']) || !wp_verify_nonce($_POST['wae_export_nonce_field'], 'wae_export_nonce')) wp_die('Invalid nonce');
        $profile_key = sanitize_text_field($_POST['profile'] ?? '');
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $delimiter = isset($_POST['delimiter']) ? substr(sanitize_text_field($_POST['delimiter']), 0, 1) : ',';
        $batch = isset($_POST['batch']) ? max(50, intval($_POST['batch'])) : 200;
        $profiles = $this->get_profiles();
        $columns = array();
        if ($profile_key && isset($profiles[$profile_key])) {
            $columns = $profiles[$profile_key]['columns'];
        } else {
            // default columns
            $columns = array('ID', 'post_title', 'sku', 'price', 'regular_price', 'sale_price', 'stock', 'type', 'categories', 'tags', 'attributes', 'images', 'variations');
        }
        $filename = 'products-' . date('Ymd-His');

        $generator = new WAE_Exporter();
        $rows_gen = $generator->export_products_generator($columns, $batch, $format);

        // if ($format === 'csv') {
        //     header('Content-Type: text/csv; charset=UTF-8');
        //     header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
        //     echo "\xEF\xBB\xBF";
        //     $out = fopen('php://output','w');
        //     $first = true;
        //     for ($row = $rows_gen->current(); $rows_gen->next()) {
        //         pass;
        //     }
        //     // but PHP generators not accessible like that here; instead we'll iterate in exporter class directly
        // }

        // fallback: let exporter handle sending
        // $generator->output_rows($rows_gen, $columns, $format, $delimiter, $filename, $this->has_phpspreadsheet);
        $generator->output_rows($rows_gen, $columns, $format, $delimiter, $filename, $this->has_phpspreadsheet);
        exit;
    }

    public function export_orders()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
        if (!isset($_POST['wae_export_nonce_field']) || !wp_verify_nonce($_POST['wae_export_nonce_field'], 'wae_export_nonce')) wp_die('Invalid nonce');

        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $delimiter = isset($_POST['delimiter']) ? substr(sanitize_text_field($_POST['delimiter']), 0, 1) : ',';
        $batch = isset($_POST['batch']) ? max(50, intval($_POST['batch'])) : 200;

        // Ambil semua profiles dan cek profile yang dipilih
        $profiles = $this->get_profiles();
        $profile_key = sanitize_text_field($_POST['profile'] ?? '');

        if ($profile_key && isset($profiles[$profile_key]) && isset($profiles[$profile_key]['type']) && $profiles[$profile_key]['type'] === 'orders') {
            $columns = $profiles[$profile_key]['columns'];
        } else {
            // fallback columns jika tidak pakai profile
            $columns = isset($_POST['columns']) ? array_map('sanitize_text_field', (array)$_POST['columns']) : array('ID', 'date_created', 'billing_email', 'total', 'status');
        }

        // Ambil status yang dipilih
        $selected_statuses = isset($_POST['order_status']) ? array_map('sanitize_text_field', (array)$_POST['order_status']) : array('completed', 'processing', 'on-hold');

        $filename = 'orders-' . date('Ymd-His');

        $generator = new WAE_Exporter_Orders();
        $rows_gen = $generator->export_orders_generator($columns, $batch, $selected_statuses); // Pass status yang dipilih

        $generator->output_rows($rows_gen, $columns, $format, $delimiter, $filename, $this->has_phpspreadsheet);
        exit;
    }

    public function export_users()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
        if (!isset($_POST['wae_export_nonce_field']) || !wp_verify_nonce($_POST['wae_export_nonce_field'], 'wae_export_nonce')) wp_die('Invalid nonce');

        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $delimiter = isset($_POST['delimiter']) ? substr(sanitize_text_field($_POST['delimiter']), 0, 1) : ',';
        $batch = isset($_POST['batch']) ? max(50, intval($_POST['batch'])) : 200;

        $profiles = $this->get_profiles();
        $profile_key = sanitize_text_field($_POST['profile'] ?? '');
        $columns = array();

        if ($profile_key && isset($profiles[$profile_key]) && isset($profiles[$profile_key]['columns'])) {
            $columns = $profiles[$profile_key]['columns'];
        } else {
            // fallback default columns
            $columns = isset($_POST['columns']) ? array_map('sanitize_text_field', (array) $_POST['columns']) : array('ID', 'user_login', 'user_email', 'user_registered', 'first_name', 'last_name');
        }

        $filename = 'users-' . date('Ymd-His');

        $generator = new WAE_Exporter_Users();
        $rows_gen = $generator->export_users_generator($columns, $batch);

        $generator->output_rows($rows_gen, $columns, $format, $delimiter, $filename, $this->has_phpspreadsheet);
        exit;
    }
}

if (!class_exists('WAE_Exporter')) {
    require_once __DIR__ . '/includes/class-exporter.php';
}

if (!class_exists('WAE_Exporter_Orders')) {
    require_once __DIR__ . '/includes/class-exporter-orders.php';
}

if (!class_exists('WAE_Exporter_Users')) {
    require_once __DIR__ . '/includes/class-exporter-users.php';
}

add_action('plugins_loaded', array('WAE_Plugin', 'init'));
