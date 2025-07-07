<?php

/**
 * Plugin Name: Import Produk CSV dengan Service
 * Description: Plugin untuk import produk dari CSV dengan field relationship service.
 * Version: 1.0
 * Author: Puji Ermanto<puji_dev@codesyariah122.co.id> | AKA Mamam Yuk | AKA Anjing kumaha aing weh anjing
 */

// Cegah akses langsung
if (!defined('ABSPATH')) exit;

class ImportProdukCSVPlugin
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_post_import_produk_csv', [$this, 'handle_csv_upload']);
    }

    // Tambah menu admin
    public function add_admin_menu()
    {
        add_menu_page(
            'Import Produk CSV',   // page title
            'Import Produk CSV',   // menu title
            'manage_options',      // capability
            'import-produk-csv',   // menu slug
            [$this, 'render_upload_page'], // callback
            'dashicons-upload'     // icon
        );
    }

    // Render form upload
    public function render_upload_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Anda tidak memiliki akses.');
        }

        // Pesan sukses/error dari proses import
        if (isset($_GET['imported'])) {
            $count = intval($_GET['imported']);
            echo "<div class='updated'><p>Berhasil mengimpor $count produk.</p></div>";
        }
?>
        <div class="wrap">
            <h1>Import Produk CSV dengan Service</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_produk_csv" />
                <?php wp_nonce_field('import_produk_csv_verify'); ?>
                <input type="file" name="produk_csv" accept=".csv" required />
                <input type="submit" class="button button-primary" value="Import CSV" />
            </form>
        </div>
<?php
    }

    // Proses upload dan import CSV
    public function handle_csv_upload()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Anda tidak memiliki akses.');
        }

        check_admin_referer('import_produk_csv_verify');

        if (empty($_FILES['produk_csv']['tmp_name'])) {
            wp_die('File CSV tidak ditemukan.');
        }

        $file = $_FILES['produk_csv']['tmp_name'];

        $count = $this->import_produk_csv($file);

        wp_redirect(admin_url('admin.php?page=import-produk-csv&imported=' . $count));
        exit;
    }

    // Fungsi import CSV
    private function import_produk_csv($file)
    {
        $service_posts = get_posts([
            'post_type' => 'service',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish',
        ]);

        if (empty($service_posts)) {
            // Bisa log juga kalau mau
        }

        $imported_count = 0;

        if (($handle = fopen($file, 'r')) !== FALSE) {
            $row = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($row++ == 0) continue; // skip header

                list($produk, $service_name, $capacity, $price_lokal, $price_luarkota, $price_ai_lokal, $price_ai_luarkota, $price_airport, $phone) = $data;

                $existing = get_page_by_title($produk, OBJECT, 'product');
                if ($existing) continue;

                $post_id = wp_insert_post([
                    'post_title' => wp_strip_all_tags($produk),
                    'post_type' => 'product',
                    'post_status' => 'publish',
                ]);

                if ($post_id) {
                    update_post_meta($post_id, 'capacity', $capacity);
                    update_post_meta($post_id, 'price_lokal', (int)$price_lokal);
                    update_post_meta($post_id, 'price_luarkota', (int)$price_luarkota);
                    update_post_meta($post_id, 'price_ai_lokal', (int)$price_ai_lokal);
                    update_post_meta($post_id, 'price_ai_luarkota', (int)$price_ai_luarkota);
                    update_post_meta($post_id, 'price_airport', (int)$price_airport);
                    update_post_meta($post_id, 'phone', trim($phone));

                    // Cari ID service by name
                    $service_id = null;
                    foreach ($service_posts as $sid) {
                        $title = get_the_title($sid);
                        if (strcasecmp($title, $service_name) === 0) {
                            $service_id = $sid;
                            break;
                        }
                    }
                    if (!$service_id && !empty($service_posts)) {
                        $service_id = $service_posts[array_rand($service_posts)];
                    }
                    if ($service_id) {
                        update_post_meta($post_id, 'service', [$service_id]);
                    }

                    $imported_count++;
                }
            }
            fclose($handle);
        }

        return $imported_count;
    }
}

new ImportProdukCSVPlugin();
