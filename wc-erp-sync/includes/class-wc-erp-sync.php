<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_ERP_Sync
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_settings_page'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('woocommerce_checkout_order_processed', [$this, 'send_order_to_erp'], 10, 1);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public static function activate()
    {
        if (!get_option('wc_erp_sync_settings')) {
            update_option('wc_erp_sync_settings', [
                'api_key' => '',
                'endpoint' => '',
            ]);
        }
    }

    public static function deactivate() {}

    public function enqueue_assets($hook)
    {
        if ($hook !== 'toplevel_page_wc-erp-sync') return;

        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);

        wp_add_inline_style('wp-admin', '
        .erp-sync-card {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 700px;
            margin-top: 20px;
        }
        .erp-sync-card h1 {
            font-size: 24px;
            margin-bottom: 15px;
        }
        .erp-sync-table th {
            width: 200px;
            text-align: left;
            padding-top: 10px;
        }
        .api-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .api-input-wrapper input {
            width: 100%;
            padding-right: 40px !important;
            box-sizing: border-box;
        }
        .lock-btn {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
        }
        .lock-btn:hover {
            opacity: 0.7;
        }
        .generate-btn {
            background-color: #2271b1;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .generate-btn:hover {
            background-color: #135e96;
        }
        /* 👁️ Ikon show password di dalam SweetAlert */
        .swal2-input-wrapper {
            position: relative;
        }
        .swal2-showpass-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
    ');

        wp_add_inline_script('jquery-core', '
        jQuery(document).ready(function($) {

            const apiField = $("input[name=\'api_key\']");
            apiField.wrap("<div class=\'api-input-wrapper\'></div>");
            const lockBtn = $("<button>")
                .attr("type","button")
                .addClass("lock-btn")
                .text("🔒")
                .insertAfter(apiField);

            apiField.prop("disabled", true);

            // 🔐 Toggle lock/unlock
            lockBtn.on("click", function() {
                const locked = apiField.prop("disabled");
                apiField.prop("disabled", !locked);
                $(this).text(locked ? "🔓" : "🔒");
            });

            // 🧠 Generate API Key with password check
            $("#generate-api-key").on("click", function(e) {
                e.preventDefault();

                Swal.fire({
                    title: "Masukkan Password Admin",
                    input: "password",
                    inputPlaceholder: "Masukkan password...",
                    showCancelButton: true,
                    confirmButtonText: "Konfirmasi",
                    cancelButtonText: "Batal",
                    inputAttributes: { autocapitalize: "off" },
                    didOpen: () => {
                        // Tambahkan tombol 👁️ show/hide password
                        const input = Swal.getInput();
                        const wrapper = input.parentElement;
                        const btn = document.createElement("button");
                        btn.type = "button";
                        btn.innerHTML = "👁️";
                        btn.className = "swal2-showpass-btn";
                        btn.addEventListener("click", () => {
                            input.type = input.type === "password" ? "text" : "password";
                            btn.innerHTML = input.type === "password" ? "👁️" : "🙈";
                        });
                        wrapper.appendChild(btn);
                    },
                    preConfirm: (value) => {
                        if (value !== "@#$duta28S") {
                            Swal.showValidationMessage("❌ Password salah!");
                        }
                        return value;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value === "@#$duta28S") {
                        const randomKey = Array.from(crypto.getRandomValues(new Uint8Array(24)))
                            .map(b => ("0" + b.toString(16)).slice(-2))
                            .join("");
                        apiField.val(randomKey).prop("disabled", true);
                        lockBtn.text("🔒");
                        Swal.fire("✅ Berhasil", "API Key baru berhasil digenerate!", "success");
                    }
                });
            });

            // ✅ Toast on save
            if (window.location.href.includes("settings-updated=true")) {
                const toast = $("<div>")
                    .text("Settings Saved Successfully ✅")
                    .css({
                        position: "fixed",
                        bottom: "20px",
                        right: "20px",
                        background: "#2271b1",
                        color: "#fff",
                        padding: "10px 15px",
                        borderRadius: "6px",
                        boxShadow: "0 2px 6px rgba(0,0,0,0.2)",
                        zIndex: "9999"
                    });
                $("body").append(toast);
                setTimeout(() => toast.fadeOut(500, () => toast.remove()), 3000);
            }
        });
    ');
    }

    public function register_settings_page()
    {
        add_menu_page(
            'ERP Sync',
            'ERP Sync',
            'manage_options',
            'wc-erp-sync',
            [$this, 'settings_page_html'],
            'dashicons-randomize',
            56
        );
    }

    public function settings_page_html()
    {
        if (!current_user_can('manage_options')) return;

        $settings = get_option('wc_erp_sync_settings');

        // 🚀 Auto-set endpoint jika kosong
        if (empty($settings['endpoint'])) {
            $settings['endpoint'] = esc_url_raw(rest_url('erp/v1/product'));
            update_option('wc_erp_sync_settings', $settings);
        }

        // ✅ Handle Save
        if (isset($_POST['save_erp_sync'])) {
            check_admin_referer('save_erp_sync');
            update_option('wc_erp_sync_settings', [
                'api_key'  => sanitize_text_field($_POST['api_key']),
                'endpoint' => esc_url_raw($_POST['endpoint']),
            ]);
            echo '<div class="updated"><p><strong>Settings saved successfully!</strong></p></div>';
        }

?>
        <div class="wrap">
            <div class="erp-sync-card">
                <h1><span class="dashicons dashicons-randomize"></span> ERP Sync Integration</h1>
                <p class="description">Connect your WooCommerce store with your ERP system using secure REST API integration.</p>
                <hr>

                <form method="post">
                    <?php wp_nonce_field('save_erp_sync'); ?>
                    <table class="form-table erp-sync-table">
                        <tr>
                            <th scope="row">ERP API Key</th>
                            <td>
                                <input type="text" name="api_key" value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" class="regular-text" placeholder="Click Generate Key below">
                                <p><button id="generate-api-key" class="generate-btn" type="button">Generate Key 🔑</button></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">ERP Endpoint URL</th>
                            <td>
                                <input type="url" name="endpoint" value="<?php echo esc_attr($settings['endpoint']); ?>" class="regular-text">
                                <p class="description">This URL is automatically generated based on your site domain.</p>
                            </td>
                        </tr>
                    </table>

                    <p style="margin-top: 20px;">
                        <button type="submit" name="save_erp_sync" class="button button-primary button-large">💾 Save Changes</button>
                    </p>
                </form>
            </div>
        </div>
<?php
    }

    // === REST API & ERP Sync Core ===
    // public function register_rest_routes()
    // {
    //     register_rest_route('erp/v1', '/product', [
    //         'methods' => 'POST',
    //         'callback' => [$this, 'handle_product_sync'],
    //         'permission_callback' => [$this, 'check_api_key_permission'],
    //     ]);
    // }

    public function register_rest_routes()
    {
        // === Endpoint utama ===
        register_rest_route('erp/v1', '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'get_products_list'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);

        // === Endpoint schema (Swagger UI) ===
        register_rest_route('erp/v1', '/schema', [
            'methods' => 'GET',
            'callback' => [$this, 'get_api_schema'],
            'permission_callback' => '__return_true', // publik
        ]);

        // === Endpoint daftar order ===
        register_rest_route('erp/v1', '/orders', [
            'methods' => 'GET',
            'callback' => [$this, 'get_orders_list'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);

        // === Endpoint detail order ===
        register_rest_route('erp/v1', '/orders/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_order_detail'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);

        // =====================================================================
        // Arah: ERP → WooCommerce
        // =====================================================================
        register_rest_route('erp/v1', '/product-sync', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_product_sync'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);

        // === Endpoint detail produk berdasarkan SKU_ERP ===
        register_rest_route('erp/v1', '/product/(?P<sku_erp>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_product_by_sku_erp'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);


        // === Update produk dari ERP ke WooCommerce ===
        register_rest_route('erp/v1', '/product-sync', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_product_from_erp'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);

        // === Hapus produk berdasarkan SKU ERP ===
        register_rest_route('erp/v1', '/product-delete/(?P<sku_erp>[a-zA-Z0-9_-]+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_product_from_erp'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);

        // === Endpoint Categories (product_cat) ===
        register_rest_route('erp/v1', '/categories', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categories'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);
        register_rest_route('erp/v1', '/categories', [
            'methods' => 'POST',
            'callback' => [$this, 'create_category'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);
        register_rest_route('erp/v1', '/categories/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_category'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);
        register_rest_route('erp/v1', '/categories/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_category'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);

        // === Endpoint Brands (product_brand) ===
        register_rest_route('erp/v1', '/brands', [
            'methods' => 'GET',
            'callback' => [$this, 'get_brands'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);
        register_rest_route('erp/v1', '/brands', [
            'methods' => 'POST',
            'callback' => [$this, 'create_brand'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);
        register_rest_route('erp/v1', '/brands/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_brand'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);
        register_rest_route('erp/v1', '/brands/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_brand'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);

        // === Informasi Toko WooCommerce ===
        register_rest_route('erp/v1', '/store-info', [
            'methods' => 'GET',
            'callback' => [$this, 'get_store_info'],
            'permission_callback' => [$this, 'check_api_key_permission'],
        ]);
    }

    public function get_api_schema()
    {
        return [
            'openapi' => '3.0.1',
            'info' => [
                'title' => 'WC ERP API',
                'version' => '1.1.0',
                'description' => 'Dokumentasi API integrasi WooCommerce & ERP (CRUD Produk + Orders)',
            ],
            'servers' => [
                ['url' => esc_url_raw(rest_url('erp/v1'))],
            ],
            'paths' => [
                '/store-info' => [
                    'get' => [
                        'summary' => 'Ambil Informasi Toko WooCommerce',
                        'description' => 'Mengambil informasi umum toko seperti nama, alamat, nomor telepon, dan koordinat lokasi.',
                        'parameters' => [[
                            'name' => 'X-ERP-KEY',
                            'in' => 'header',
                            'required' => true,
                            'schema' => ['type' => 'string'],
                            'description' => 'API key ERP untuk autentikasi'
                        ]],
                        'responses' => [
                            '200' => [
                                'description' => 'Informasi toko berhasil diambil',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'success' => ['type' => 'boolean'],
                                                'data' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'store_name' => ['type' => 'string'],
                                                        'store_url' => ['type' => 'string'],
                                                        'store_email' => ['type' => 'string'],
                                                        'address' => ['type' => 'string'],
                                                        'address_2' => ['type' => 'string'],
                                                        'city' => ['type' => 'string'],
                                                        'postcode' => ['type' => 'string'],
                                                        'country' => ['type' => 'string'],
                                                        'phone' => ['type' => 'string'],
                                                        'latitude' => ['type' => 'string'],
                                                        'longitude' => ['type' => 'string']
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ]
                ],

                '/products' => [
                    'get' => [
                        'summary' => 'Ambil Daftar Produk',
                        'description' => 'Menarik data produk dari WooCommerce untuk sinkronisasi ERP, termasuk informasi accessories yang terkait.',
                        'parameters' => [[
                            'name' => 'X-ERP-KEY',
                            'in' => 'header',
                            'required' => true,
                            'schema' => ['type' => 'string'],
                            'description' => 'API key ERP untuk autentikasi'
                        ]],
                        'responses' => [
                            '200' => [
                                'description' => 'Daftar produk berhasil diambil',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'integer', 'example' => 123],
                                                    'sku' => ['type' => 'string', 'example' => 'FLUKE-117'],
                                                    'name' => ['type' => 'string', 'example' => 'Fluke 117 Digital Multimeter'],
                                                    'price' => ['type' => 'number', 'format' => 'float', 'example' => 2850000],
                                                    'stock' => ['type' => 'integer', 'example' => 10],
                                                    'categories' => [
                                                        'type' => 'array',
                                                        'items' => ['type' => 'string', 'example' => 'Multimeter']
                                                    ],
                                                    'images' => [
                                                        'type' => 'array',
                                                        'items' => ['type' => 'string', 'example' => 'https://dutapersada.co.id/wp-content/uploads/2025/01/fluke-117.jpg']
                                                    ],
                                                    'accessories' => [
                                                        'type' => 'array',
                                                        'description' => 'Daftar produk aksesoris yang terkait dengan produk ini',
                                                        'items' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'id' => ['type' => 'integer', 'example' => 456],
                                                                'sku' => ['type' => 'string', 'example' => 'TL75'],
                                                                'name' => ['type' => 'string', 'example' => 'Fluke TL75 Test Leads Set'],
                                                                'price' => ['type' => 'number', 'format' => 'float', 'example' => 350000],
                                                                'image' => ['type' => 'string', 'example' => 'https://dutapersada.co.id/wp-content/uploads/2025/01/tl75.jpg']
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '401' => ['description' => 'API key tidak valid'],
                        ]
                    ]
                ],

                '/product/{sku_erp}' => [
                    'get' => [
                        'summary' => 'Ambil Detail Produk berdasarkan SKU ERP',
                        'description' => 'Menampilkan detail produk spesifik termasuk informasi accessories yang terkait.',
                        'parameters' => [
                            [
                                'name' => 'sku_erp',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string'],
                                'description' => 'SKU produk dari ERP'
                            ],
                            [
                                'name' => 'X-ERP-KEY',
                                'in' => 'header',
                                'required' => true,
                                'schema' => ['type' => 'string'],
                                'description' => 'API key ERP untuk autentikasi'
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Detail produk berhasil diambil',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer', 'example' => 123],
                                                'sku' => ['type' => 'string', 'example' => 'FLUKE-117'],
                                                'name' => ['type' => 'string', 'example' => 'Fluke 117 Digital Multimeter'],
                                                'price' => ['type' => 'number', 'format' => 'float', 'example' => 2850000],
                                                'stock' => ['type' => 'integer', 'example' => 10],
                                                'categories' => [
                                                    'type' => 'array',
                                                    'items' => ['type' => 'string', 'example' => 'Multimeter']
                                                ],
                                                'description' => ['type' => 'string', 'example' => 'Fluke 117 adalah multimeter digital dengan fitur AutoVolt dan LoZ yang ideal untuk teknisi listrik profesional.'],
                                                'images' => [
                                                    'type' => 'array',
                                                    'items' => ['type' => 'string', 'example' => 'https://dutapersada.co.id/wp-content/uploads/2025/01/fluke-117.jpg']
                                                ],
                                                'accessories' => [
                                                    'type' => 'array',
                                                    'description' => 'Daftar produk aksesoris yang terkait dengan produk ini',
                                                    'items' => [
                                                        'type' => 'object',
                                                        'properties' => [
                                                            'id' => ['type' => 'integer', 'example' => 456],
                                                            'sku' => ['type' => 'string', 'example' => 'TL75'],
                                                            'name' => ['type' => 'string', 'example' => 'Fluke TL75 Test Leads Set'],
                                                            'price' => ['type' => 'number', 'format' => 'float', 'example' => 350000],
                                                            'image' => ['type' => 'string', 'example' => 'https://dutapersada.co.id/wp-content/uploads/2025/01/tl75.jpg']
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '404' => ['description' => 'Produk tidak ditemukan'],
                            '401' => ['description' => 'API key tidak valid'],
                        ]
                    ]
                ],

                '/categories' => [
                    'get' => [
                        'summary' => 'Ambil Daftar Kategori Produk',
                        'description' => 'Mengambil daftar semua kategori produk WooCommerce.',
                        'parameters' => [[
                            'name' => 'X-ERP-KEY',
                            'in' => 'header',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]],
                        'responses' => [
                            '200' => ['description' => 'Daftar kategori berhasil diambil'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ],
                    'post' => [
                        'summary' => 'Buat Kategori Produk Baru',
                        'description' => 'ERP membuat kategori produk baru di WooCommerce.',
                        'parameters' => [[
                            'name' => 'X-ERP-KEY',
                            'in' => 'header',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'slug' => ['type' => 'string'],
                                            'description' => ['type' => 'string'],
                                            'parent' => ['type' => 'integer', 'description' => 'ID parent category']
                                        ],
                                        'required' => ['name']
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => ['description' => 'Kategori berhasil dibuat'],
                            '400' => ['description' => 'Data tidak lengkap'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ]
                ],
                '/categories/{id}' => [
                    'put' => [
                        'summary' => 'Update Kategori Produk',
                        'description' => 'ERP memperbarui kategori produk berdasarkan ID.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer']
                            ],
                            [
                                'name' => 'X-ERP-KEY',
                                'in' => 'header',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'slug' => ['type' => 'string'],
                                            'description' => ['type' => 'string'],
                                            'parent' => ['type' => 'integer']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Kategori berhasil diperbarui'],
                            '404' => ['description' => 'Kategori tidak ditemukan'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ],
                    'delete' => [
                        'summary' => 'Hapus Kategori Produk',
                        'description' => 'ERP menghapus kategori produk berdasarkan ID.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer']
                            ],
                            [
                                'name' => 'X-ERP-KEY',
                                'in' => 'header',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Kategori berhasil dihapus'],
                            '404' => ['description' => 'Kategori tidak ditemukan'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ]
                ],

                '/brands' => [
                    'get' => [
                        'summary' => 'Ambil Daftar Brand Produk',
                        'description' => 'Mengambil daftar semua brand produk WooCommerce.',
                        'parameters' => [[
                            'name' => 'X-ERP-KEY',
                            'in' => 'header',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]],
                        'responses' => [
                            '200' => ['description' => 'Daftar brand berhasil diambil'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ],
                    'post' => [
                        'summary' => 'Buat Brand Produk Baru',
                        'description' => 'ERP membuat brand produk baru di WooCommerce.',
                        'parameters' => [[
                            'name' => 'X-ERP-KEY',
                            'in' => 'header',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'slug' => ['type' => 'string'],
                                            'description' => ['type' => 'string']
                                        ],
                                        'required' => ['name']
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => ['description' => 'Brand berhasil dibuat'],
                            '400' => ['description' => 'Data tidak lengkap'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ]
                ],
                '/brands/{id}' => [
                    'put' => [
                        'summary' => 'Update Brand Produk',
                        'description' => 'ERP memperbarui brand produk berdasarkan ID.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer']
                            ],
                            [
                                'name' => 'X-ERP-KEY',
                                'in' => 'header',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'slug' => ['type' => 'string'],
                                            'description' => ['type' => 'string']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Brand berhasil diperbarui'],
                            '404' => ['description' => 'Brand tidak ditemukan'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ],
                    'delete' => [
                        'summary' => 'Hapus Brand Produk',
                        'description' => 'ERP menghapus brand produk berdasarkan ID.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer']
                            ],
                            [
                                'name' => 'X-ERP-KEY',
                                'in' => 'header',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Brand berhasil dihapus'],
                            '404' => ['description' => 'Brand tidak ditemukan'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ]
                ],

                '/orders' => [
                    'get' => [
                        'summary' => 'Ambil Daftar Order',
                        'description' => 'ERP menarik daftar order terbaru dari WooCommerce.',
                        'parameters' => [[
                            'name' => 'X-ERP-KEY',
                            'in' => 'header',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]],
                        'responses' => [
                            '200' => ['description' => 'Daftar order berhasil diambil'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ]
                ],

                '/orders/{id}' => [
                    'get' => [
                        'summary' => 'Ambil Detail Order',
                        'description' => 'Menampilkan detail order tertentu berdasarkan ID.',
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer']
                            ],
                            [
                                'name' => 'X-ERP-KEY',
                                'in' => 'header',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Detail order ditemukan'],
                            '404' => ['description' => 'Order tidak ditemukan'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ]
                ],

                '/product-sync' => [
                    'post' => [
                        'summary' => 'Tambah Produk dari ERP ke WooCommerce',
                        'description' => 'ERP mengirimkan data produk baru ke WooCommerce.',
                        'parameters' => [[
                            'name' => 'X-ERP-KEY',
                            'in' => 'header',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'sku_erp' => ['type' => 'string'],
                                            'name'    => ['type' => 'string'],
                                            'price'   => ['type' => 'number'],
                                            'stock'   => ['type' => 'integer'],
                                            'images'  => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string'],
                                                'description' => 'Array of image URLs to upload for the product (e.g., ["https://example.com/image1.jpg", "https://example.com/image2.jpg"])'

                                            ],
                                            // Field baru
                                            'product_url' => ['type' => 'string', 'description' => 'URL produk eksternal (untuk produk affiliate)'],
                                            'button_text' => ['type' => 'string', 'description' => 'Teks tombol untuk produk eksternal', 'default' => 'Beli produk'],
                                            'regular_price' => ['type' => 'number', 'description' => 'Harga normal produk'],
                                            'sale_price' => ['type' => 'number', 'description' => 'Harga obral produk'],
                                            'sale_price_dates_from' => ['type' => 'string', 'format' => 'date', 'description' => 'Tanggal mulai obral (YYYY-MM-DD)'],
                                            'sale_price_dates_to' => ['type' => 'string', 'format' => 'date', 'description' => 'Tanggal akhir obral (YYYY-MM-DD)'],
                                            'product_status' => ['type' => 'string', 'enum' => ['ready', 'preorder'], 'description' => 'Status barang'],
                                            'estimasi_po' => ['type' => 'string', 'description' => 'Estimasi PO (contoh: "2 minggu")'],
                                            'kondisi_barang' => ['type' => 'string', 'enum' => ['baru', 'bekas'], 'description' => 'Kondisi barang'],
                                            'minimal_order' => ['type' => 'integer', 'description' => 'Minimal order quantity'],
                                            'product_layout' => ['type' => 'string', 'enum' => ['full-width', 'left-sidebar', 'right-sidebar'], 'description' => 'Layout produk'],
                                            'product_style' => ['type' => 'string', 'enum' => ['normal', 'extended'], 'description' => 'Style produk'],
                                        ],
                                        'required' => ['sku_erp', 'name']
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Produk berhasil ditambahkan'],
                            '400' => ['description' => 'Data tidak lengkap'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ]
                ],

                '/product-update' => [
                    'put' => [
                        'summary' => 'Update Produk dari ERP ke WooCommerce',
                        'description' => 'ERP memperbarui data produk yang sudah ada berdasarkan SKU ERP.',
                        'parameters' => [[
                            'name' => 'X-ERP-KEY',
                            'in' => 'header',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'sku_erp' => ['type' => 'string'],
                                            'name'    => ['type' => 'string'],
                                            'price'   => ['type' => 'number'],
                                            'stock'   => ['type' => 'integer']
                                        ],
                                        'required' => ['sku_erp']
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Produk berhasil diperbarui'],
                            '404' => ['description' => 'Produk tidak ditemukan'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ]
                ],

                '/product-delete/{sku_erp}' => [
                    'delete' => [
                        'summary' => 'Hapus Produk di WooCommerce dari ERP',
                        'description' => 'ERP menghapus produk di WooCommerce berdasarkan SKU ERP.',
                        'parameters' => [
                            [
                                'name' => 'sku_erp',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ],
                            [
                                'name' => 'X-ERP-KEY',
                                'in' => 'header',
                                'required' => true,
                                'schema' => ['type' => 'string']
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Produk berhasil dihapus'],
                            '404' => ['description' => 'Produk tidak ditemukan'],
                            '401' => ['description' => 'API key tidak valid']
                        ]
                    ]
                ],
            ]
        ];
    }

    // ==========================================================================
    // Handler api-docs
    // ==========================================================================
    public function get_store_info($request)
    {
        $store_info = [
            'store_name'   => get_bloginfo('name'),
            'store_url'    => get_bloginfo('url'),
            'store_email'  => get_bloginfo('admin_email'),
            'address'      => get_option('woocommerce_store_address'),
            'address_2'    => get_option('woocommerce_store_address_2'),
            'city'         => get_option('woocommerce_store_city'),
            'postcode'     => get_option('woocommerce_store_postcode'),
            'country'      => get_option('woocommerce_default_country'),
            'phone'        => get_option('woocommerce_store_phone'),
            'latitude'     => get_option('woocommerce_store_latitude'),
            'longitude'    => get_option('woocommerce_store_longitude'),
        ];

        return rest_ensure_response([
            'success' => true,
            'data'    => $store_info,
        ]);
    }


    public function get_products_list($request)
    {
        // Ambil parameter dari URL (optional)
        $category = sanitize_text_field($request->get_param('category'));
        $sku_erp  = sanitize_text_field($request->get_param('sku_erp'));
        $status   = sanitize_text_field($request->get_param('status')) ?: 'publish';
        $per_page = intval($request->get_param('per_page')) ?: 50;
        $page     = intval($request->get_param('page')) ?: 1;

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'post_status'    => $status,
        ];

        // Filter by category
        if (!empty($category)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $category,
                ],
            ];
        }

        // Filter by SKU ERP
        if (!empty($sku_erp)) {
            $args['meta_query'] = [
                [
                    'key'   => '_sku_erp',
                    'value' => $sku_erp,
                    'compare' => '=',
                ],
            ];
        }

        $query = new WP_Query($args);
        $data = [];

        foreach ($query->posts as $product_post) {
            $product = wc_get_product($product_post->ID);

            // 🔹 Ambil data accessories dari meta (pastikan key sesuai dengan meta yang disimpan)
            $accessories_raw = get_post_meta($product_post->ID, '_accessory_ids', true);
            $accessories = !empty($accessories_raw) ? maybe_unserialize($accessories_raw) : [];

            // 🔹 Ambil nama produk accessories (biar hasil lebih informatif)
            $accessory_names = [];
            if (!empty($accessories)) {
                foreach ($accessories as $acc_id) {
                    $acc_product = wc_get_product($acc_id);
                    if ($acc_product) {
                        $accessory_names[] = [
                            'id'   => $acc_id,
                            'name' => $acc_product->get_name(),
                            'sku'  => $acc_product->get_sku(),
                        ];
                    }
                }
            }

            $data[] = [
                'id'            => $product_post->ID,
                'sku'           => $product->get_sku(),
                'sku_erp'       => get_post_meta($product_post->ID, '_sku_erp', true),
                'name'          => $product->get_name(),
                'price'         => $product->get_price(),
                'regular_price' => $product->get_regular_price(),
                'sale_price'    => $product->get_sale_price(),
                'stock'         => $product->get_stock_quantity(),
                'status'        => $product->get_status(),
                'image_url'     => wp_get_attachment_url($product->get_image_id()),
                'categories'    => wp_get_post_terms($product_post->ID, 'product_cat', ['fields' => 'names']),
                'accessories'   => $accessory_names, // ✅ sudah terisi dengan data accessories
                'updated_at'    => get_the_modified_date('Y-m-d H:i:s', $product_post->ID),
            ];
        }


        return rest_ensure_response([
            'success' => true,
            'page'    => $page,
            'per_page' => $per_page,
            'total'   => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'data'    => $data,
        ]);
    }


    public function get_product_by_sku_erp($request)
    {
        $sku_erp = sanitize_text_field($request['sku_erp']);

        // Cari produk berdasarkan meta _sku_erp
        $args = [
            'post_type'  => 'product',
            'meta_query' => [
                [
                    'key'     => '_sku_erp',
                    'value'   => $sku_erp,
                    'compare' => '=',
                ],
            ],
            'posts_per_page' => 1,
        ];

        $query = new WP_Query($args);

        if (empty($query->posts)) {
            return new WP_Error(
                'not_found',
                'Produk dengan SKU ERP tersebut tidak ditemukan.',
                ['status' => 404]
            );
        }

        $product_post = $query->posts[0];
        $product = wc_get_product($product_post->ID);

        // 🔹 Ambil data accessories (support 2 meta key)
        $accessories_raw = get_post_meta($product_post->ID, '_accessory_ids', true);
        if (empty($accessories_raw)) {
            $accessories_raw = get_post_meta($product_post->ID, '_accessories', true);
        }
        $accessory_ids = !empty($accessories_raw) ? maybe_unserialize($accessories_raw) : [];

        // 🔹 Ambil detail tiap produk accessory
        $accessories = [];
        if (!empty($accessory_ids) && is_array($accessory_ids)) {
            foreach ($accessory_ids as $acc_id) {
                $acc_product = wc_get_product($acc_id);
                if ($acc_product) {
                    $accessories[] = [
                        'id'          => $acc_product->get_id(),
                        'sku'         => $acc_product->get_sku(),
                        'name'        => $acc_product->get_name(),
                        'price'       => $acc_product->get_price(),
                        'regular_price' => $acc_product->get_regular_price(),
                        'sale_price'  => $acc_product->get_sale_price(),
                        'image_url'   => wp_get_attachment_url($acc_product->get_image_id()),
                        'status'      => $acc_product->get_status(),
                    ];
                }
            }
        }

        // 🔹 Susun data produk utama
        $data = [
            'id'            => $product_post->ID,
            'sku'           => $product->get_sku(),
            'sku_erp'       => $sku_erp,
            'name'          => $product->get_name(),
            'price'         => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price'    => $product->get_sale_price(),
            'stock'         => $product->get_stock_quantity(),
            'status'        => $product->get_status(),
            'image_url'     => wp_get_attachment_url($product->get_image_id()),
            'gallery'       => array_map('wp_get_attachment_url', $product->get_gallery_image_ids()),
            'categories'    => wp_get_post_terms($product_post->ID, 'product_cat', ['fields' => 'names']),
            'tags'          => wp_get_post_terms($product_post->ID, 'product_tag', ['fields' => 'names']),
            'description'   => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'accessories'   => $accessories,
            'created_at'    => get_the_date('Y-m-d H:i:s', $product_post->ID),
            'updated_at'    => get_the_modified_date('Y-m-d H:i:s', $product_post->ID),
        ];

        return rest_ensure_response([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function get_categories($request)
    {
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);

        if (is_wp_error($terms)) {
            return new WP_Error('error', 'Failed to retrieve categories', ['status' => 500]);
        }

        $data = [];
        foreach ($terms as $term) {
            $data[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'parent' => $term->parent,
                'count' => $term->count,  // Jumlah produk
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function create_category($request)
    {
        $params = $request->get_json_params();

        if (empty($params['name'])) {
            return new WP_Error('missing_name', 'Category name is required', ['status' => 400]);
        }

        $args = [
            'name' => sanitize_text_field($params['name']),
            'slug' => !empty($params['slug']) ? sanitize_title($params['slug']) : '',
            'description' => !empty($params['description']) ? sanitize_textarea_field($params['description']) : '',
            'parent' => !empty($params['parent']) ? intval($params['parent']) : 0,
        ];

        $term = wp_insert_term($args['name'], 'product_cat', $args);

        if (is_wp_error($term)) {
            return new WP_Error('create_failed', $term->get_error_message(), ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Category created successfully',
            'category_id' => $term['term_id'],
        ], 201);
    }

    public function update_category($request)
    {
        $id = intval($request['id']);
        $params = $request->get_json_params();

        if (!term_exists($id, 'product_cat')) {
            return new WP_Error('not_found', 'Category not found', ['status' => 404]);
        }

        $args = [];
        if (!empty($params['name'])) $args['name'] = sanitize_text_field($params['name']);
        if (!empty($params['slug'])) $args['slug'] = sanitize_title($params['slug']);
        if (isset($params['description'])) $args['description'] = sanitize_textarea_field($params['description']);
        if (isset($params['parent'])) $args['parent'] = intval($params['parent']);

        $updated = wp_update_term($id, 'product_cat', $args);

        if (is_wp_error($updated)) {
            return new WP_Error('update_failed', $updated->get_error_message(), ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Category updated successfully',
            'category_id' => $id,
        ]);
    }

    public function delete_category($request)
    {
        $id = intval($request['id']);

        if (!term_exists($id, 'product_cat')) {
            return new WP_Error('not_found', 'Category not found', ['status' => 404]);
        }

        $deleted = wp_delete_term($id, 'product_cat');

        if (is_wp_error($deleted)) {
            return new WP_Error('delete_failed', $deleted->get_error_message(), ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Category deleted successfully',
            'deleted_id' => $id,
        ]);
    }

    public function get_brands($request)
    {
        if (!taxonomy_exists('product_brand')) {
            return new WP_Error('not_found', 'Brand taxonomy not found', ['status' => 404]);
        }

        $terms = get_terms([
            'taxonomy' => 'product_brand',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);

        if (is_wp_error($terms)) {
            return new WP_Error('error', 'Failed to retrieve brands', ['status' => 500]);
        }

        $data = [];
        foreach ($terms as $term) {
            $data[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'count' => $term->count,
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function create_brand($request)
    {
        if (!taxonomy_exists('product_brand')) {
            return new WP_Error('not_found', 'Brand taxonomy not found', ['status' => 404]);
        }

        $params = $request->get_json_params();

        if (empty($params['name'])) {
            return new WP_Error('missing_name', 'Brand name is required', ['status' => 400]);
        }

        $args = [
            'name' => sanitize_text_field($params['name']),
            'slug' => !empty($params['slug']) ? sanitize_title($params['slug']) : '',
            'description' => !empty($params['description']) ? sanitize_textarea_field($params['description']) : '',
        ];

        $term = wp_insert_term($args['name'], 'product_brand', $args);

        if (is_wp_error($term)) {
            return new WP_Error('create_failed', $term->get_error_message(), ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Brand created successfully',
            'brand_id' => $term['term_id'],
        ], 201);
    }

    public function update_brand($request)
    {
        if (!taxonomy_exists('product_brand')) {
            return new WP_Error('not_found', 'Brand taxonomy not found', ['status' => 404]);
        }

        $id = intval($request['id']);
        $params = $request->get_json_params();

        if (!term_exists($id, 'product_brand')) {
            return new WP_Error('not_found', 'Brand not found', ['status' => 404]);
        }

        $args = [];
        if (!empty($params['name'])) $args['name'] = sanitize_text_field($params['name']);
        if (!empty($params['slug'])) $args['slug'] = sanitize_title($params['slug']);
        if (isset($params['description'])) $args['description'] = sanitize_textarea_field($params['description']);

        $updated = wp_update_term($id, 'product_brand', $args);

        if (is_wp_error($updated)) {
            return new WP_Error('update_failed', $updated->get_error_message(), ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Brand updated successfully',
            'brand_id' => $id,
        ]);
    }

    public function delete_brand($request)
    {
        if (!taxonomy_exists('product_brand')) {
            return new WP_Error('not_found', 'Brand taxonomy not found', ['status' => 404]);
        }

        $id = intval($request['id']);

        if (!term_exists($id, 'product_brand')) {
            return new WP_Error('not_found', 'Brand not found', ['status' => 404]);
        }

        $deleted = wp_delete_term($id, 'product_brand');

        if (is_wp_error($deleted)) {
            return new WP_Error('delete_failed', $deleted->get_error_message(), ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Brand deleted successfully',
            'deleted_id' => $id,
        ]);
    }


    public function get_orders_list($request)
    {
        $args = [
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => ['completed', 'processing'],
        ];

        $orders = wc_get_orders($args);
        $data = [];

        foreach ($orders as $order) {
            $data[] = [
                'order_id'   => $order->get_id(),
                'order_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'customer'   => [
                    'id'      => $order->get_user_id(),
                    'name'    => $order->get_formatted_billing_full_name(),
                    'email'   => $order->get_billing_email(),
                    'address' => $order->get_billing_address_1(),
                ],
                'total'      => $order->get_total(),
                'status'     => $order->get_status(),
            ];
        }

        return rest_ensure_response($data);
    }

    public function get_order_detail($request)
    {
        $order_id = intval($request['id']);
        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error('not_found', 'Order not found', ['status' => 404]);
        }

        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = [
                'sku_erp'   => get_post_meta($product->get_id(), '_sku_erp', true),
                'product'   => $item->get_name(),
                'qty'       => $item->get_quantity(),
                'unit_price' => $product ? $product->get_price() : 0,
                'subtotal'  => $item->get_total(),
            ];
        }

        $shipping = $order->get_shipping_methods();
        $shipping_data = [];
        foreach ($shipping as $method) {
            $shipping_data[] = [
                'courier_name' => $method->get_name(),
                'total_weight' => $order->get_meta('_weight'),
                'shipping_fee' => $method->get_total(),
            ];
        }

        $payment = [
            'method' => $order->get_payment_method_title(),
            'amount' => $order->get_total(),
        ];

        $data = [
            'order_id'    => $order->get_id(),
            'transaction_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'customer'    => [
                'id'      => $order->get_user_id(),
                'name'    => $order->get_formatted_billing_full_name(),
                'address' => $order->get_billing_address_1(),
            ],
            'items'       => $items,
            'shipping'    => $shipping_data,
            'payment'     => $payment,
        ];

        return rest_ensure_response($data);
    }

    // public function get_categories($request)
    // {
    //     $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
    //     $data = [];

    //     foreach ($terms as $term) {
    //         $data[] = [
    //             'id' => $term->term_id,
    //             'name' => $term->name,
    //             'slug' => $term->slug,
    //         ];
    //     }

    //     return rest_ensure_response($data);
    // }

    // public function get_brands($request)
    // {
    //     if (!taxonomy_exists('product_brand')) {
    //         return new WP_Error('not_found', 'Brand taxonomy not found', ['status' => 404]);
    //     }

    //     $terms = get_terms(['taxonomy' => 'product_brand', 'hide_empty' => false]);
    //     $data = [];

    //     foreach ($terms as $term) {
    //         $data[] = [
    //             'id' => $term->term_id,
    //             'name' => $term->name,
    //             'slug' => $term->slug,
    //         ];
    //     }

    //     return rest_ensure_response($data);
    // }


    public function check_api_key_permission($request)
    {
        $settings = get_option('wc_erp_sync_settings');
        $key = $request->get_header('x-erp-key');
        return $key && $key === ($settings['api_key'] ?? '');
    }

    // ================================================================================
    // Product sync
    // ================================================================================
    public function handle_product_sync($request)
    {
        $params = $request->get_json_params();

        if (empty($params['sku_erp']) || empty($params['name'])) {
            return new WP_Error('missing_data', 'Missing SKU_ERP or Name', ['status' => 400]);
        }

        $sku_erp  = sanitize_text_field($params['sku_erp']);
        $name     = sanitize_text_field($params['name']);
        $price    = isset($params['price']) ? floatval($params['price']) : null;  // Legacy
        $stock    = isset($params['stock']) ? intval($params['stock']) : null;
        $images   = isset($params['images']) ? $params['images'] : [];

        // Field baru
        $product_url = isset($params['product_url']) ? esc_url_raw($params['product_url']) : '';
        $button_text = isset($params['button_text']) ? sanitize_text_field($params['button_text']) : '';
        $regular_price = isset($params['regular_price']) ? floatval($params['regular_price']) : null;
        $sale_price = isset($params['sale_price']) ? floatval($params['sale_price']) : null;
        $sale_price_dates_from = isset($params['sale_price_dates_from']) ? sanitize_text_field($params['sale_price_dates_from']) : '';
        $sale_price_dates_to = isset($params['sale_price_dates_to']) ? sanitize_text_field($params['sale_price_dates_to']) : '';
        $product_status = isset($params['product_status']) ? sanitize_text_field($params['product_status']) : '';
        $estimasi_po = isset($params['estimasi_po']) ? sanitize_text_field($params['estimasi_po']) : '';
        $kondisi_barang = isset($params['kondisi_barang']) ? sanitize_text_field($params['kondisi_barang']) : '';
        $minimal_order = isset($params['minimal_order']) ? intval($params['minimal_order']) : null;
        $product_layout = isset($params['product_layout']) ? sanitize_text_field($params['product_layout']) : '';
        $product_style = isset($params['product_style']) ? sanitize_text_field($params['product_style']) : '';

        // Cari produk seperti sebelumnya...
        $product_id = wc_get_product_id_by_sku($sku_erp);
        if (!$product_id) {
            $product_query = new WP_Query([
                'post_type'  => 'product',
                'meta_query' => [
                    [
                        'key'   => '_sku_erp',
                        'value' => $sku_erp,
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);
            if (!empty($product_query->posts)) {
                $product_id = $product_query->posts[0];
            }
        }

        if ($product_id) {
            wp_update_post([
                'ID'         => $product_id,
                'post_title' => $name,
            ]);
            $status = 'updated';
        } else {
            $product_id = wp_insert_post([
                'post_title'  => $name,
                'post_type'   => 'product',
                'post_status' => 'publish',
            ]);
            $status = 'created';
        }

        // Update meta dasar
        update_post_meta($product_id, '_sku', $sku_erp);
        update_post_meta($product_id, '_sku_erp', $sku_erp);
        if ($stock !== null) update_post_meta($product_id, '_stock', $stock);

        // Handle harga: Prioritaskan regular_price/sale_price, fallback ke price lama
        if ($regular_price !== null) {
            update_post_meta($product_id, '_regular_price', $regular_price);
            update_post_meta($product_id, '_price', $regular_price);  // Sync
        } elseif ($price !== null) {
            update_post_meta($product_id, '_regular_price', $price);
            update_post_meta($product_id, '_price', $price);
        }

        if ($sale_price !== null) {
            update_post_meta($product_id, '_sale_price', $sale_price);
            update_post_meta($product_id, '_price', $sale_price);  // Sync jika ada sale
        }

        // Tanggal obral
        if (!empty($sale_price_dates_from)) {
            update_post_meta($product_id, '_sale_price_dates_from', strtotime($sale_price_dates_from));
        }
        if (!empty($sale_price_dates_to)) {
            update_post_meta($product_id, '_sale_price_dates_to', strtotime($sale_price_dates_to));
        }

        // Field baru lainnya
        if (!empty($product_url)) update_post_meta($product_id, '_product_url', $product_url);
        if (!empty($button_text)) update_post_meta($product_id, '_button_text', $button_text);
        if (!empty($product_status)) update_post_meta($product_id, '_product_status', $product_status);
        if (!empty($estimasi_po)) update_post_meta($product_id, '_estimasi_po', $estimasi_po);
        if (!empty($kondisi_barang)) update_post_meta($product_id, '_kondisi_barang', $kondisi_barang);
        if ($minimal_order !== null) update_post_meta($product_id, '_minimal_order', $minimal_order);
        if (!empty($product_layout)) update_post_meta($product_id, '_product_layout', $product_layout);
        if (!empty($product_style)) update_post_meta($product_id, '_product_style', $product_style);

        // Handle gambar (seperti sebelumnya)...
        // [Kode upload gambar tetap sama]

        return [
            'success'     => true,
            'status'      => $status,
            'product_id'  => $product_id,
            'sku_erp'     => $sku_erp,
            'images_uploaded' => count($uploaded_images ?? []),
        ];
    }

    /**
     * 🧩 UPDATE Produk (ERP -> WooCommerce)
     */
    public function update_product_from_erp($request)
    {
        $params  = $request->get_json_params();
        $sku_erp = sanitize_text_field($params['sku_erp'] ?? '');

        if (empty($sku_erp)) {
            return new WP_Error('missing_sku', 'Missing sku_erp', ['status' => 400]);
        }

        // 🔍 Cari berdasarkan SKU WooCommerce dulu
        $product_id = wc_get_product_id_by_sku($sku_erp);

        // Jika tidak ditemukan, cari di meta _sku_erp
        if (!$product_id) {
            $product_query = new WP_Query([
                'post_type'  => 'product',
                'meta_query' => [
                    [
                        'key'   => '_sku_erp',
                        'value' => $sku_erp,
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);
            if (!empty($product_query->posts)) {
                $product_id = $product_query->posts[0];
            }
        }

        // 🚨 Kalau tetap tidak ketemu
        if (!$product_id) {
            return new WP_Error('not_found', 'Product not found', ['status' => 404]);
        }

        // === Update post title (nama produk) ===
        $update_data = [];
        if (!empty($params['name'])) {
            $update_data['post_title'] = sanitize_text_field($params['name']);
        }

        if (!empty($update_data)) {
            $update_data['ID'] = $product_id;
            wp_update_post($update_data);
        }

        // === Update harga ===
        if (isset($params['price'])) {
            $price = floatval($params['price']);
            update_post_meta($product_id, '_price', $price);
            update_post_meta($product_id, '_regular_price', $price);
        }

        // === Update stok ===
        if (isset($params['stock'])) {
            $stock = intval($params['stock']);
            update_post_meta($product_id, '_stock', $stock);
            wc_update_product_stock_status($product_id, $stock > 0 ? 'instock' : 'outofstock');
        }

        // === Update SKU ERP agar sinkron ===
        update_post_meta($product_id, '_sku', $sku_erp);
        update_post_meta($product_id, '_sku_erp', $sku_erp);

        return rest_ensure_response([
            'success'     => true,
            'message'     => 'Product updated successfully',
            'product_id'  => $product_id,
            'sku_erp'     => $sku_erp,
        ]);
    }

    /**
     * 🗑️ DELETE Produk (ERP -> WooCommerce)
     */
    public function delete_product_from_erp($request)
    {
        $sku_erp = sanitize_text_field($request['sku_erp']);

        // 🔍 Cari produk berdasarkan meta _sku_erp
        $product_query = new WP_Query([
            'post_type'  => 'product',
            'meta_query' => [
                [
                    'key'   => '_sku_erp',
                    'value' => $sku_erp,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);

        if (empty($product_query->posts)) {
            return new WP_Error('not_found', 'Product not found', ['status' => 404]);
        }

        $product_id = $product_query->posts[0];

        // 🗑️ Hapus produk
        $deleted = wp_delete_post($product_id, true);

        if (!$deleted) {
            return new WP_Error('delete_failed', 'Failed to delete product', ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Product deleted successfully',
            'deleted_id' => $product_id,
            'sku_erp' => $sku_erp
        ]);
    }



    public function send_order_to_erp($order_id)
    {
        $settings = get_option('wc_erp_sync_settings');
        if (empty($settings['endpoint']) || empty($settings['api_key'])) return;

        $order = wc_get_order($order_id);
        if (!$order) return;

        $data = [
            'order_id'   => $order->get_id(),
            'order_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'total'      => $order->get_total(),
            'status'     => $order->get_status(),
            'billing'    => $order->get_address('billing'),
            'items'      => [],
        ];

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $data['items'][] = [
                'sku_erp' => $product ? $product->get_sku() : '',
                'name'    => $item->get_name(),
                'qty'     => $item->get_quantity(),
                'price'   => $item->get_total(),
            ];
        }

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-ERP-KEY'    => $settings['api_key'],
            ],
            'body'    => wp_json_encode($data),
            'timeout' => 20,
        ];

        $response = wp_remote_post($settings['endpoint'], $args);
        update_post_meta($order_id, '_erp_sync_response', wp_remote_retrieve_body($response));
    }
}
