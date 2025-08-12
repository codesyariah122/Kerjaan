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
        // Load optional PhpSpreadsheet
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

        // Import actions
        add_action('admin_post_wae_import_products', array($this, 'import_products'));
        add_action('admin_post_wae_import_orders', array($this, 'import_orders'));
        add_action('admin_post_wae_import_users', array($this, 'import_users'));

        // Enqueue SweetAlert
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function register_menu()
    {
        $cap = 'manage_woocommerce';
        add_menu_page('Woo Advanced Export', 'Woo Advanced Export', $cap, 'wae_main', array($this, 'page_export'), 'dashicons-download', 56);
        add_submenu_page('wae_main', 'Manage Mappings', 'Manage Mappings', $cap, 'wae_mappings', array($this, 'page_mappings'));
        add_submenu_page('wae_main', 'Export Products', 'Export Products', $cap, 'wae_export', array($this, 'page_export_products'));
        add_submenu_page('wae_main', 'Import Products', 'Import Products', $cap, 'wae_import_products', array($this, 'page_import_products'));
        add_submenu_page('wae_main', 'Export Orders', 'Export Orders', $cap, 'wae_export_orders', array($this, 'page_export_orders'));
        add_submenu_page('wae_main', 'Import Orders', 'Import Orders', $cap, 'wae_import_orders', array($this, 'page_import_orders'));
        add_submenu_page('wae_main', 'Export Users', 'Export Users', $cap, 'wae_export_users', array($this, 'page_export_users'));
        add_submenu_page('wae_main', 'Import Users', 'Import Users', $cap, 'wae_import_users', array($this, 'page_import_users'));
    }

    // Import pages
    public function page_import_products()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
?>
        <div class="wrap">
            <h1>Import Products</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="wae_import_products">
                <?php wp_nonce_field('wae_import_nonce', 'wae_import_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th>File</th>
                        <td><input type="file" name="import_file" required></td>
                    </tr>
                    <tr>
                        <th>Format</th>
                        <td>
                            <select name="format">
                                <option value="csv">CSV</option>
                                <option value="xls">XLS (HTML)</option>
                                <option value="xlsx">XLSX</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Import Products'); ?>
            </form>
        </div>
    <?php
    }

    public function page_import_orders()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
    ?>
        <div class="wrap">
            <h1>Import Orders</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="wae_import_orders">
                <?php wp_nonce_field('wae_import_nonce', 'wae_import_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th>File</th>
                        <td><input type="file" name="import_file" required></td>
                    </tr>
                    <tr>
                        <th>Format</th>
                        <td>
                            <select name="format">
                                <option value="csv">CSV</option>
                                <option value="xls">XLS (HTML)</option>
                                <option value="xlsx">XLSX</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Import Orders'); ?>
            </form>
        </div>
    <?php
    }

    public function page_import_users()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
    ?>
        <div class="wrap">
            <h1>Import Users</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="wae_import_users">
                <?php wp_nonce_field('wae_import_nonce', 'wae_import_nonce_field'); ?>
                <table class="form-table">
                    <tr>
                        <th>File</th>
                        <td><input type="file" name="import_file" required></td>
                    </tr>
                    <tr>
                        <th>Format</th>
                        <td>
                            <select name="format">
                                <option value="csv">CSV</option>
                                <option value="xls">XLS (HTML)</option>
                                <option value="xlsx">XLSX</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Import Users'); ?>
            </form>
        </div>
    <?php
    }

    // Implement import functions here...

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

    // Other existing methods...
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
            <h1>Woo Advanced Export & Import</h1>
            <p>Pilih jenis data yang ingin Anda export atau import:</p>

            <p>
                <a class="wae-button" href="<?php echo admin_url('admin.php?page=wae_mappings'); ?>">Manage Mapping Profiles</a>
            </p>

            <div class="wae-tabs">
                <button class="wae-tab-button active" onclick="openTab(event, 'export')">Export</button>
                <button class="wae-tab-button" onclick="openTab(event, 'import')">Import</button>
            </div>

            <div id="export" class="wae-tab-content active">
                <div class="wae-flex-container">
                    <div class="wae-flex-item">
                        <a class="wae-button" href="<?php echo admin_url('admin.php?page=wae_export'); ?>">Export Products</a>
                    </div>
                    <div class="wae-flex-item">
                        <a class="wae-button" href="<?php echo admin_url('admin.php?page=wae_export_orders'); ?>">Export Orders</a>
                    </div>
                    <div class="wae-flex-item">
                        <a class="wae-button" href="<?php echo admin_url('admin.php?page=wae_export_users'); ?>">Export Users</a>
                    </div>
                </div>
            </div>

            <div id="import" class="wae-tab-content">
                <div class="wae-flex-container">
                    <div class="wae-flex-item">
                        <a class="wae-button" href="<?php echo admin_url('admin.php?page=wae_import_products'); ?>">Import Products</a>
                    </div>
                    <div class="wae-flex-item">
                        <a class="wae-button" href="<?php echo admin_url('admin.php?page=wae_import_orders'); ?>">Import Orders</a>
                    </div>
                    <div class="wae-flex-item">
                        <a class="wae-button" href="<?php echo admin_url('admin.php?page=wae_import_users'); ?>">Import Users</a>
                    </div>
                </div>
            </div>

            <hr>
        </div>

        <style>
            .wae-tabs {
                display: flex;
                margin-bottom: 20px;
            }

            .wae-tab-button {
                background-color: #007cba;
                color: white;
                border: none;
                padding: 10px 15px;
                cursor: pointer;
                border-radius: 5px 5px 0 0;
                margin-right: 5px;
                transition: background-color 0.3s;
            }

            .wae-tab-button:hover {
                background-color: #005a8c;
            }

            .wae-tab-button.active {
                background-color: #005a8c;
            }

            .wae-tab-content {
                display: none;
                padding: 15px;
                border: 1px solid #007cba;
                border-radius: 0 0 5px 5px;
            }

            .wae-tab-content.active {
                display: block;
            }

            .wae-flex-container {
                display: flex;
                justify-content: flex-start;
                gap: 5px;
                /* Reduced space between buttons */
            }

            .wae-flex-item {
                flex: none;
                /* Prevent items from stretching */
            }

            .wae-list {
                list-style-type: none;
                padding: 0;
            }

            .wae-button {
                display: inline-block;
                padding: 10px 15px;
                background-color: #007cba;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                transition: background-color 0.3s;
                text-align: center;
                /* Center text in button */
            }

            .wae-button:hover {
                background-color: #005a8c;
                color: #fffdcc;
            }
        </style>

        <script>
            function openTab(evt, tabName) {
                var i, tabcontent, tabbuttons;
                tabcontent = document.getElementsByClassName("wae-tab-content");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].classList.remove("active");
                }
                tabbuttons = document.getElementsByClassName("wae-tab-button");
                for (i = 0; i < tabbuttons.length; i++) {
                    tabbuttons[i].classList.remove("active");
                }
                document.getElementById(tabName).classList.add("active");
                evt.currentTarget.classList.add("active");
            }
        </script>
    <?php
    }

    public function page_export_products()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');
        $profiles = $this->get_profiles();
    ?>
        <div class="wrap export-products-container">
            <h1>Export Products</h1>
            <?php if (!$this->has_phpspreadsheet): ?>
                <div class="notice notice-warning">
                    <p>Note: .xlsx requires PhpSpreadsheet (run composer in plugin folder to enable).</p>
                </div>
            <?php endif; ?>

            <p>
                <a class="wae-button" href="<?php echo admin_url('admin.php?page=wae_mappings'); ?>">Manage Mapping Profiles</a>
            </p>

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
                        <td><input type="text" name="delimiter" value="," maxlength="1"></td>
                    </tr>
                    <tr>
                        <th>Batch size</th>
                        <td><input type="number" name="batch" value="200" min="50" max="5000"></td>
                    </tr>
                </table>
                <?php submit_button('Export Products', 'primary', 'submit', true); ?>
            </form>
        </div>

        <style>
            .export-products-container {
                margin: 20px 0;
            }

            .export-products-container h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            .notice {
                background-color: #fff3cd;
                border-color: #ffeeba;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }

            .form-table {
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }

            .form-table th {
                text-align: left;
                padding: 10px;
                background-color: #f9f9f9;
                border: 1px solid #ddd;
            }

            .form-table td {
                padding: 10px;
                border: 1px solid #ddd;
            }

            .form-table input[type="text"],
            .form-table input[type="number"],
            .form-table select {
                width: 100%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box;
            }

            .form-table input[type="text"]:focus,
            .form-table input[type="number"]:focus,
            .form-table select:focus {
                border-color: #007cba;
                outline: none;
            }

            .wae-button {
                background-color: #007cba;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                transition: background-color 0.3s;
            }

            .wae-button:hover {
                background-color: #005a8c;
                color: #fff;
            }
        </style>

    <?php
    }

    public function page_export_orders()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');

        // Ambil semua mapping profiles
        $profiles = $this->get_profiles();

        // Default kolom untuk order export
        $fields = array(
            'ID' => 'Order ID',
            'date_created' => 'Date Created',
            'billing_email' => 'Billing Email',
            'total' => 'Total',
            'status' => 'Status',
            'billing_first_name' => 'Billing First Name',
            'billing_last_name' => 'Billing Last Name',
            'shipping_address_1' => 'Shipping Address 1',
            'shipping_address_2' => 'Shipping Address 2',
            'billing_address_1' => 'Billing Address 1',
            'billing_address_2' => 'Billing Address 2',
            'payment_method' => 'Payment Method',
            'payment_via' => 'Payment Via',
            'product_name' => 'Product Name',
            'quantity' => 'Quantity',
            'item_total' => 'Item Total',
            'item_color' => 'Item Color',
        );

        // Status yang bisa dipilih
        $statuses = array('completed', 'processing', 'on-hold', 'canceled');
    ?>
        <div class="wrap export-orders-container">
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
                        <th><label for="profile">Mapping Profile</label></th>
                        <td>
                            <select name="profile" id="profile">
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
                        <th><label for="order_status">Status</label></th>
                        <td>
                            <select name="order_status[]" id="order_status" multiple>
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
                                <label style="display:block">
                                    <input type="checkbox" name="columns[]" value="<?php echo esc_attr($k); ?>" checked>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="format">Format</label></th>
                        <td>
                            <select name="format" id="format">
                                <option value="csv">CSV</option>
                                <option value="xls">XLS (HTML)</option>
                                <option value="xlsx">XLSX <?php if (!$this->has_phpspreadsheet) echo '(requires composer)'; ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="delimiter">Delimiter (CSV)</label></th>
                        <td><input type="text" name="delimiter" id="delimiter" value="," maxlength="1"></td>
                    </tr>
                    <tr>
                        <th><label for="batch">Batch size</label></th>
                        <td><input type="number" name="batch" id="batch" value="200" min="50" max="5000"></td>
                    </tr>
                </table>
                <?php submit_button('Export Orders', 'primary', 'submit', true); ?>
            </form>
        </div>

        <style>
            .export-orders-container {
                margin: 20px 0;
            }

            .export-orders-container h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            .notice {
                background-color: #fff3cd;
                border-color: #ffeeba;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }

            .form-table {
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }

            .form-table th {
                text-align: left;
                padding: 10px;
                background-color: #f9f9f9;
                border: 1px solid #ddd;
            }

            .form-table td {
                padding: 10px;
                border: 1px solid #ddd;
            }

            .form-table input[type="text"],
            .form-table input[type="number"],
            .form-table select {
                width: 100%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box;
            }

            .form-table input[type="text"]:focus,
            .form-table input[type="number"]:focus,
            .form-table select:focus {
                border-color: #007cba;
                outline: none;
            }

            .wae-button {
                background-color: #007cba;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                transition: background-color 0.3s;
            }

            .wae-button:hover {
                background-color: #005a8c;
                color: #fff;
            }
        </style>
    <?php
    }

    public function page_export_users()
    {
        if (!current_user_can('manage_woocommerce')) wp_die('No.');

        // Default kolom untuk user export
        $fields = array(
            'ID' => 'User  ID',
            'user_login' => 'User  Login',
            'user_email' => 'User  Email',
            'user_registered' => 'Registered Date',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'role' => 'User  Role',
        );

        // Ambil semua mapping profile
        $profiles_all = $this->get_profiles();

        // Filter profile hanya untuk tipe users
        $profiles = array_filter($profiles_all, function ($p) {
            return isset($p['type']) && $p['type'] === 'users';
        });

        // Ambil semua pengguna
        $all_users = get_users(array('fields' => 'all'));

        // Hitung jumlah pengguna berdasarkan peran
        $role_counts = array();
        foreach ($all_users as $user) {
            foreach ($user->roles as $role) {
                if (!isset($role_counts[$role])) {
                    $role_counts[$role] = 0;
                }
                $role_counts[$role]++;
            }
        }
    ?>
        <div class="wrap export-users-container">
            <h1>Export Users</h1>
            <?php if (!$this->has_phpspreadsheet): ?>
                <div class="notice notice-warning">
                    <p>Note: .xlsx requires PhpSpreadsheet (run composer in plugin folder to enable).</p>
                </div>
            <?php endif; ?>

            <!-- Tampilkan statistik pengguna -->
            <h2>User Analytics</h2>
            <table class="form-table">
                <tr>
                    <th>User Role</th>
                    <th>Count</th>
                </tr>
                <?php foreach ($role_counts as $role => $count): ?>
                    <tr>
                        <td><?php echo esc_html(ucfirst($role)); ?></td>
                        <td><?php echo esc_html($count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wae_export_users">
                <?php wp_nonce_field('wae_export_nonce', 'wae_export_nonce_field'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="profile">Mapping Profile</label></th>
                        <td>
                            <select name="profile" id="profile">
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
                        <th>Columns (check to include)</th>
                        <td>
                            <?php foreach ($fields as $k => $label): ?>
                                <label style="display:block">
                                    <input type="checkbox" name="columns[]" value="<?php echo esc_attr($k); ?>" checked>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="format">Format</label></th>
                        <td>
                            <select name="format" id="format">
                                <option value="csv">CSV</option>
                                <option value="xls">XLS (HTML)</option>
                                <option value="xlsx">XLSX <?php if (!$this->has_phpspreadsheet) echo '(requires composer)'; ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="delimiter">Delimiter (CSV)</label></th>
                        <td><input type="text" name="delimiter" id="delimiter" value="," maxlength="1" style="width:50px"></td>
                    </tr>
                    <tr>
                        <th><label for="batch">Batch size</label></th>
                        <td><input type="number" name="batch" id="batch" value="200" min="50" max="5000"></td>
                    </tr>
                </table>
                <?php submit_button('Export Users', 'primary', 'submit', true); ?>
            </form>
        </div>

        <style>
            .export-users-container {
                margin: 20px 0;
            }

            .export-users-container h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            .notice {
                background-color: #fff3cd;
                border-color: #ffeeba;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }

            .form-table {
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }

            .form-table th {
                text-align: left;
                padding: 10px;
                background-color: #f9f9f9;
                border: 1px solid #ddd;
            }

            .form-table td {
                padding: 10px;
                border: 1px solid #ddd;
            }

            .form-table input[type="text"],
            .form-table input[type="number"],
            .form-table select {
                width: 100%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box;
            }

            .form-table input[type="text"]:focus,
            .form-table input[type="number"]:focus,
            .form-table select:focus {
                border-color: #007cba;
                outline: none;
            }

            .wae-button {
                background-color: #007cba;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                transition: background-color 0.3s;
            }

            .wae-button:hover {
                background-color: #005a8c;
                color: #fff;
            }
        </style>
    <?php
    }


    public function page_mappings()
    {
        // if (!current_user_can('manage_woocommerce')) wp_die('No.');
        if (!current_user_can('manage_woocommerce')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }


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
            <div class="tabs">
                <button class="tab-button <?php echo $type === 'products' ? 'active' : ''; ?>" onclick="changeTab('products')">Products</button>
                <button class="tab-button <?php echo $type === 'orders' ? 'active' : ''; ?>" onclick="changeTab('orders')">Orders</button>
                <button class="tab-button <?php echo $type === 'users' ? 'active' : ''; ?>" onclick="changeTab('users')">Users</button>
            </div>

            <div class="tab-content">
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
        </div>

        <style>
            .tabs {
                display: flex;
                margin-bottom: 20px;
                border-bottom: 2px solid #007cba;
            }

            .tab-button {
                background-color: transparent;
                border: none;
                padding: 10px 20px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s, color 0.3s;
            }

            .tab-button:hover {
                background-color: #f1f1f1;
            }

            .tab-button.active {
                border-bottom: 2px solid #007cba;
                color: #007cba;
                font-weight: bold;
            }

            .tab-content {
                padding: 15px;
                border: 1px solid #007cba;
                border-radius: 5px;
                background-color: #fff;
            }

            .widefat {
                width: 100%;
                border-collapse: collapse;
            }

            .widefat th,
            .widefat td {
                padding: 10px;
                border: 1px solid #ddd;
            }

            .widefat th {
                background-color: #f9f9f9;
            }

            .button-link-delete {
                color: red;
                cursor: pointer;
                background: none;
                border: none;
                text-decoration: underline;
            }
        </style>

        <script>
            function changeTab(type) {
                window.location.href = "<?php echo admin_url('admin.php?page=wae_mappings&type='); ?>" + type;
            }
        </script>
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
