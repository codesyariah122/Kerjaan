<?php

/**
 * WooCommerce Custom Product Info + SweetAlert + Integrasi WhatsApp Chat dari Plugin Floating WhatsApp Chat
 * @author Puji Ermanto From Tokoweb <pujiermanto@tokoweb.co> | Alias Mansiur | Alias Dadang Sukodadi
 */

// =====================================================
//  Menu Admin: WhatsApp Chat jadi menu utama
// =====================================================
// 

add_action('admin_menu', function () {
    add_menu_page(
        'Floating WhatsApp Chat',
        'WhatsApp Chat',
        'manage_options',
        'floating-whatsapp-chat-main',
        function () {
            if (function_exists('fwc_settings_page')) {
                fwc_settings_page();
            } else {
                echo '<div class="wrap"><h1>Floating WhatsApp Chat</h1><p>⚠️ Plugin Floating WhatsApp Chat belum aktif atau callback tidak ditemukan.</p></div>';
            }
        },
        'dashicons-format-chat',
        25
    );
}, 11);


// =====================================================
// WooCommerce Custom Product Info
// =====================================================
add_action('woocommerce_product_options_general_product_data', function () {
    echo '<div class="options_group">';

    woocommerce_wp_select([
        'id' => '_product_status',
        'label' => 'Status Barang',
        'options' => [
            '' => 'Pilih status',
            'ready' => 'Ready Stock',
            'preorder' => 'Pre Order'
        ]
    ]);

    woocommerce_wp_text_input([
        'id' => '_estimasi_po',
        'label' => 'Estimasi PO (contoh: 2 minggu)',
    ]);

    woocommerce_wp_select([
        'id' => '_kondisi_barang',
        'label' => 'Kondisi Barang',
        'options' => [
            '' => 'Pilih kondisi',
            'baru' => 'Baru',
            'bekas' => 'Bekas'
        ]
    ]);

    woocommerce_wp_text_input([
        'id' => '_minimal_order',
        'label' => 'Minimal Order Qty',
        'type' => 'number',
        'desc_tip' => true,
        'description' => 'Jumlah minimal pembelian produk ini'
    ]);

    echo '</div>';
});

add_action('woocommerce_process_product_meta', function ($post_id) {
    $fields = ['_product_status', '_estimasi_po', '_kondisi_barang', '_minimal_order'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
});


// =====================================================
// Tampilkan Info di Halaman Produk
// =====================================================
add_action('woocommerce_single_product_summary', function () {
    global $product;
    if (empty($product) || !is_object($product)) return;
    $status     = get_post_meta($product->get_id(), '_product_status', true);
    $estimasi   = get_post_meta($product->get_id(), '_estimasi_po', true);
    $kondisi    = get_post_meta($product->get_id(), '_kondisi_barang', true);
    $ekspedisi  = get_post_meta($product->get_id(), '_ekspedisi', true);
    $min_order  = get_post_meta($product->get_id(), '_minimal_order', true);

    echo '
    <style>
    .product-extra-info {
    background: #f9fafb;
    border: 1px solid #e3e3e3;
    border-radius: 8px;
    padding: 15px 18px;
    margin-top: 10px;
    font-size: 15px;
    }
    .product-extra-info p {
    margin: 6px 0;
    display: flex;
    justify-content: space-between;
    font-weight: 500;
    }
    .product-extra-info strong {
    color: #222;
    }
    .po-warning {
    background: #fff8e1;
    color: #8a6d3b;
    padding: 10px;
    border-radius: 6px;
    margin-top: 10px;
    font-weight: 500;
    }
    </style>
    ';

    echo '<div class="product-extra-info">';
    if ($status) {
        echo "<p><strong>Status Barang:</strong> " . ucfirst($status);
        if ($status === 'preorder' && $estimasi) echo " ({$estimasi})";
        echo "</p>";
    }

    if ($kondisi) echo "<p><strong>Kondisi:</strong> " . ucfirst($kondisi) . "</p>";
    if ($ekspedisi) echo "<p><strong>Ekspedisi:</strong> {$ekspedisi}</p>";
    // if ($min_order) echo "<p><strong>Minimal Order:</strong> {$min_order} pcs</p>";
    if ($min_order) {
        echo "<p><strong>Minimal Order:</strong> {$min_order} pcs</p>";
    }

    if ($status === 'preorder') {
        echo '<div class="po-warning">⚠️ Barang ini <strong>Pre-Order (Full Payment)</strong>. Pengiriman akan dilakukan setelah barang tersedia.</div>';
    }

    echo '</div>';
}, 25);


// =====================================================
// Validasi Minimal Order + SweetAlert (fix AJAX submit)
// =====================================================
add_action('wp_footer', function () {
    if (!is_product()) return;

    global $product;

    $product_id = $product->get_id();
    $status     = get_post_meta($product_id, '_product_status', true); // 'preorder' atau 'ready'
    $min_order  = get_post_meta($product_id, '_minimal_order', true);

    // ✅ Jika bukan produk preorder, hentikan
    // if ($status !== 'preorder' || !$min_order) return;
    if (!$min_order) return;
?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector("form.cart");
            const qtyInput = form ? form.querySelector("input.qty") : null;
            const minOrder = <?php echo (int) $min_order; ?>;

            if (!form || !qtyInput) return;

            // Set batas minimum qty
            qtyInput.setAttribute("min", minOrder);
            if (parseInt(qtyInput.value) < minOrder) qtyInput.value = minOrder;

            form.addEventListener("submit", function(e) {
                const qty = parseInt(qtyInput.value);

                if (qty < minOrder) {
                    e.preventDefault();
                    Swal.fire({
                        icon: "warning",
                        title: "Minimal Order!",
                        text: `Minimal pembelian produk ini adalah ${minOrder} pcs.`,
                        confirmButtonText: "Oke, ubah ke minimal",
                        confirmButtonColor: "#3085d6"
                    }).then(() => {
                        qtyInput.value = minOrder;
                    });
                    return false;
                }
            });
        });
    </script>

<?php
});


// Validasi Minimal Order di Backend
add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id, $quantity) {
    $status    = get_post_meta($product_id, '_product_status', true);
    $min_order = (int) get_post_meta($product_id, '_minimal_order', true);

    if ($min_order > 0 && $quantity < $min_order) {
        wc_add_notice("Minimal pembelian untuk produk ini adalah {$min_order} pcs.", 'error');
        return false;
    }

    // Khusus preorder tetap wajib jumlahnya pas
    if ($status === 'preorder' && $quantity != $min_order) {
        wc_add_notice("Untuk produk Pre-Order, pembelian harus tepat {$min_order} pcs.", 'error');
        return false;
    }

    return $passed;
}, 10, 3);




// =====================================================
// Tombol Share Produk
// =====================================================
add_action('woocommerce_single_product_summary', function () {
    global $product;
    $url        = urlencode(get_permalink($product->get_id()));
    $title      = urlencode($product->get_name());
    $share_link = get_permalink($product->get_id());
?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-papmT/q0jw7q+Tu+5m7OZtY4+1ZylC7Y6El3FZBzj+bQvZB6iGCuI94hNlzPsmzCckFi13Wrk8q4N8/XX8gNw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        .product-share {
            margin-top: 25px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .product-share strong {
            font-weight: 600;
            color: #111827;
            margin-right: 8px;
            font-size: 15px;
        }

        .product-share a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            font-size: 18px;
            color: #fff !important;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        }

        .product-share a.whatsapp {
            background: #25D366;
        }

        .product-share a.copylink {
            background: #2563eb;
        }

        .product-share a:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.15);
        }

        .product-share a i {
            pointer-events: none;
        }
    </style>

    <div class="product-share">
        <strong>Bagikan:</strong>

        <a href="https://wa.me/?text=<?php echo $title . '%20' . $url; ?>"
            target="_blank"
            class="whatsapp"
            title="Bagikan ke WhatsApp">
            <i class="fa-brands fa-whatsapp"></i>
        </a>

        <a href="#"
            class="copylink"
            title="Salin tautan produk"
            onclick="navigator.clipboard.writeText('<?php echo esc_js($share_link); ?>');
            Swal.fire({
                icon: 'success',
                title: 'Tautan Disalin!',
                text: 'Link produk telah disalin ke clipboard 🎉',
                timer: 1600,
                showConfirmButton: false
            });
            return false;">
            <i class="fa-solid fa-link"></i>
        </a>
    </div>
<?php
}, 40);

// Pastikan SweetAlert2 aktif di single product
add_action('wp_footer', function () {
    if (!is_product()) return;
?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php
});


// =====================================================
// Ganti semua link WhatsApp front-end dengan nomor dari plugin Floating WhatsApp Chat
// =====================================================
add_action('wp_footer', function () {
    $wa_number = get_option('fwc_whatsapp_number');
    if (!$wa_number) return;

    $wa_number = preg_replace('/^0/', '62', $wa_number);
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newNumber = '<?php echo esc_js($wa_number); ?>';
            document.querySelectorAll('a[href*="wa.me"], a[href*="api.whatsapp.com"]').forEach(link => {
                link.href = link.href.replace(/\d{8,15}/, newNumber);
            });
        });
    </script>
<?php
});

// =====================================================
// Tambah Badge "Pre Order" di Thumbnail Produk (Shop / Loop)
// =====================================================
add_action('woocommerce_before_shop_loop_item_title', function () {
    global $product;
    $status = get_post_meta($product->get_id(), '_product_status', true);

    if ($status === 'preorder') {
        echo '<span class="preorder-badge">Pre-Order</span>';
    } elseif ($status === 'ready') {
        echo '<span class="ready-badge">Ready Stock</span>';
    }
}, 9);

// Styling badge agar tampil profesional
add_action('wp_head', function () {
    echo '
    <style>
    /* Pastikan container produk relatif */
    ul.products li.product,
    .woocommerce ul.products li.product,
    .woocommerce-loop-product__link {
        position: relative !important;
        overflow: visible !important;
    }

    /* Badge styling */
    ul.products li.product .preorder-badge,
    ul.products li.product .ready-badge,
    .woocommerce ul.products li.product .preorder-badge,
    .woocommerce ul.products li.product .ready-badge {
        position: absolute !important;
        top: 6px !important;
        right: 6px !important; /* ⬅️ Paksa ke kanan atas */
        z-index: 10 !important;
        padding: 4px 9px !important;
        border-radius: 5px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #fff !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.15) !important;
        line-height: 1.2 !important;
        letter-spacing: 0.3px !important;
    }

    /* Warna */
    .preorder-badge { background: #f59e0b !important; }
    .ready-badge { background: #16a34a !important; }

    /* Efek hover */
    ul.products li.product:hover .preorder-badge,
    ul.products li.product:hover .ready-badge {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.18);
    }
    </style>
    ';
});



// =====================================================
// Kolom "Status Barang" & "Min Order" di Admin List Produk
// =====================================================
add_filter('manage_edit-product_columns', function ($columns) {
    // Sisipkan setelah kolom 'sku'
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'sku') {
            $new['product_status'] = 'Status Barang';
            $new['min_order'] = 'Min Order';
        }
    }
    return $new;
});

add_action('manage_product_posts_custom_column', function ($column, $post_id) {
    if ($column === 'product_status') {
        $status = get_post_meta($post_id, '_product_status', true);
        if ($status === 'preorder') {
            echo '<span style="color:#f59e0b;font-weight:600;">Pre-Order</span>';
        } elseif ($status === 'ready') {
            echo '<span style="color:#16a34a;font-weight:600;">Ready</span>';
        } else {
            echo '<span style="color:#999;">-</span>';
        }
    }

    if ($column === 'min_order') {
        $min = get_post_meta($post_id, '_minimal_order', true);
        echo $min ? '<span style="font-weight:600;">' . esc_html($min) . '</span>' : '<span style="color:#999;">-</span>';
    }
}, 10, 2);

// Agar bisa di-sortir
add_filter('manage_edit-product_sortable_columns', function ($columns) {
    $columns['product_status'] = 'product_status';
    $columns['min_order'] = 'min_order';
    return $columns;
});

// =====================================================
// Perbaiki tampilan kolom "Status Barang" & "Min Order" di dashboard produk
// =====================================================
add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'product') {
        echo '
        <style>
        .column-product_status, .column-min_order {
            width: 120px !important;
            text-align: center !important;
            white-space: nowrap !important;
        }
        .column-product_status span {
            display: inline-block !important;
            background: #fef3c7;
            color: #92400e;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }
        .column-product_status span[style*="color:#16a34a"] {
            background: #dcfce7;
            color: #166534;
        }
        .column-min_order span {
            display: inline-block;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
        </style>
        ';
    }
});

// =====================================================
//  Validasi Minimal Order di Halaman Cart (SweetAlert + Lock Proceed Button)
// =====================================================
add_action('wp_footer', function () {
    if (!is_cart()) return;
?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const qtyInputs = document.querySelectorAll('.woocommerce-cart-form input.qty');
            const checkoutBtn = document.querySelector('.checkout-button');

            let cartValid = true; // global flag

            // Fungsi untuk toggle tombol checkout
            function toggleCheckout(disable) {
                if (checkoutBtn) {
                    checkoutBtn.disabled = disable;
                    checkoutBtn.style.opacity = disable ? '0.5' : '1';
                    checkoutBtn.style.pointerEvents = disable ? 'none' : 'auto';
                }
            }

            qtyInputs.forEach(input => {
                const cartItem = input.closest('tr.cart_item');
                if (!cartItem) return;

                const productId = cartItem.querySelector('a.remove')?.getAttribute('data-product_id');
                if (!productId) return;

                // Ambil minimal order via REST API
                fetch(`/wp-json/tokoweb/v1/min-order/${productId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.min_order) return;
                        const minOrder = parseInt(data.min_order);

                        input.setAttribute('min', minOrder);

                        input.addEventListener('change', function(e) {
                            const newQty = parseInt(e.target.value);

                            if (newQty < minOrder) {
                                e.preventDefault();
                                e.target.value = minOrder;
                                cartValid = false;
                                toggleCheckout(true);

                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Minimal Order!',
                                    text: `Minimal pembelian produk ini adalah ${minOrder} pcs.`,
                                    confirmButtonColor: '#3085d6',
                                    confirmButtonText: 'Oke, ubah ke minimal'
                                }).then(() => {
                                    e.target.value = minOrder;
                                    jQuery('button[name="update_cart"]').prop('disabled', false).trigger('click');
                                });
                            } else {
                                cartValid = true;
                                toggleCheckout(false);
                            }
                        });
                    });
            });

            // Cegah checkout jika masih invalid
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function(e) {
                    if (!cartValid) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Checkout Diblokir!',
                            text: 'Ada produk yang tidak memenuhi minimal order. Perbaiki dulu sebelum checkout.',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                });
            }
        });
    </script>
<?php
});


// =====================================================
// REST API untuk ambil minimal order produk di Cart
// =====================================================
add_action('rest_api_init', function () {
    register_rest_route('tokoweb/v1', '/min-order/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => function ($data) {
            $product_id = intval($data['id']);
            $min_order = get_post_meta($product_id, '_minimal_order', true);
            return [
                'min_order' => $min_order ? (int)$min_order : 0
            ];
        },
        'permission_callback' => '__return_true'
    ]);
});


// =====================================================
// 🔒 Validasi Server-Side saat Update Qty di Cart (tetap aman walau JS dimatikan)
// =====================================================
// --- B) Perbaiki: gunakan add_filter untuk update cart validation (bukan add_action)
add_filter('woocommerce_update_cart_validation', function ($passed, $cart_item_key, $values, $quantity) {
    $product_id = $values['product_id'];
    $min_order = (int) get_post_meta($product_id, '_minimal_order', true);
    $status = get_post_meta($product_id, '_product_status', true);

    if ($status === 'preorder' && $quantity < $min_order) {
        wc_add_notice("Untuk produk Pre-Order, minimal pembelian adalah {$min_order} pcs.", 'error');
        return false;
    }

    if ($quantity < $min_order) {
        wc_add_notice("Minimal pembelian untuk produk ini adalah {$min_order} pcs.", 'error');
        return false;
    }

    return $passed;
}, 10, 4);

// =====================================================
// Gabungan: Filter Ekspedisi Sesuai Produk + Mode Preorder
// Fix nih
// =====================================================
// --- C) Saat semua item preorder, kembalikan WC_Shipping_Rate yang benar
add_filter('woocommerce_package_rates', function ($rates, $package) {
    if (empty($rates) || !is_array($rates)) {
        return $rates;
    }

    $allowed_shipping = [];
    $all_preorder = true;
    $has_preorder = false;

    foreach (WC()->cart->get_cart() as $item) {
        $product_id = $item['product_id'];
        $status = get_post_meta($product_id, '_product_status', true);
        $ekspedisi_str = get_post_meta($product_id, '_ekspedisi', true);

        if ($status !== 'preorder') {
            $all_preorder = false;
        } else {
            $has_preorder = true;
        }

        if ($ekspedisi_str) {
            $list = array_map('trim', explode(',', strtolower($ekspedisi_str)));
            $allowed_shipping = array_merge($allowed_shipping, $list);
        }
    }

    //  Semua produk preorder → return WC_Shipping_Rate object
    if ($all_preorder) {
        $rate_id = 'preorder_shipping';
        $preorder_rate = new WC_Shipping_Rate(
            $rate_id, // id unik rate
            '🚚 Pre-Order Shipping (Rp 0 – Ongkir menyusul)', // label
            0, // cost
            [], // taxes
            'preorder_shipping' // method_id
        );

        // Pastikan hasil return sesuai format expected
        return [$rate_id => $preorder_rate];
    }

    //  Campuran preorder + ready stock
    if ($has_preorder && !$all_preorder) {
        wc_clear_notices();
        wc_add_notice('⚠️ Keranjang Anda berisi produk <strong>Pre-Order</strong> dan <strong>Ready Stock</strong>. Pengiriman bisa terpisah.', 'notice');
    }

    //  Filter ekspedisi berdasarkan meta produk
    if (!empty($allowed_shipping)) {
        $allowed_shipping = array_unique($allowed_shipping);

        foreach ($rates as $key => $rate) {
            $label = '';
            $method = '';

            if (is_object($rate) && $rate instanceof WC_Shipping_Rate) {
                $label = strtolower($rate->get_label());
                $method = strtolower($rate->get_method_id());
            } elseif (is_object($rate)) {
                $label = strtolower($rate->label ?? '');
                $method = strtolower($rate->method_id ?? '');
            } elseif (is_array($rate)) {
                $label = strtolower($rate['label'] ?? '');
                $method = strtolower($rate['method_id'] ?? '');
            }

            $ok = false;
            foreach ($allowed_shipping as $eksp) {
                if (strpos($label, $eksp) !== false || strpos($method, $eksp) !== false) {
                    $ok = true;
                    break;
                }
            }

            if (!$ok) {
                unset($rates[$key]);
            }
        }
    }

    if (empty($rates)) {
        wc_add_notice('⚠️ Tidak ada ekspedisi yang sesuai dengan pengaturan produk.', 'error');
    }

    return $rates;
}, 20, 2);

//  Redirect WhatsApp Otomatis untuk Produk Pre-Order
add_action('woocommerce_thankyou', function ($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $has_preorder = false;
    foreach ($order->get_items() as $item) {
        $status = get_post_meta($item->get_product_id(), '_product_status', true);
        if ($status === 'preorder') {
            $has_preorder = true;
            break;
        }
    }

    if (!$has_preorder) return;

    // Nomor WhatsApp admin dari plugin Floating WhatsApp Chat
    $wa_admin = get_option('fwc_whatsapp_number');
    if ($wa_admin) {
        $wa_admin = preg_replace('/^0/', '62', $wa_admin);
    }

    // Nomor WhatsApp customer dari checkout
    $billing_phone = $order->get_billing_phone();
    $wa_user = preg_replace('/[^0-9]/', '', $billing_phone); // hanya angka
    if (strpos($wa_user, '0') === 0) {
        $wa_user = '62' . substr($wa_user, 1);
    }

    //  Ambil data order
    $billing_first = $order->get_billing_first_name();
    $billing_last  = $order->get_billing_last_name();
    $billing_addr  = $order->get_billing_address_1();
    $billing_city  = $order->get_billing_city();
    $billing_state = $order->get_billing_state();
    $billing_post  = $order->get_billing_postcode();
    $billing_email = $order->get_billing_email();
    $shipping_note = $order->get_customer_note();
    $total = wp_strip_all_tags(wc_price($order->get_total()));
    $shipping_method = wp_strip_all_tags($order->get_shipping_method());

    // Daftar produk
    $items_text = "";
    foreach ($order->get_items() as $item) {
        $product_name = $item->get_name();
        $qty = $item->get_quantity();
        $subtotal = wp_strip_all_tags(wc_price($item->get_subtotal()));
        $items_text .= "• {$product_name} × {$qty} ({$subtotal})%0A";
    }

    // Pesan untuk WhatsApp (tanpa emoji rusak)
    $message = "
📦 *Pesanan Pre-Order Baru*%0A
==============================%0A
*Nama:* {$billing_first} {$billing_last}%0A
*Alamat:* {$billing_addr}%0A
*Kota:* {$billing_city}%0A
*Provinsi:* {$billing_state}%0A
*Kode Pos:* {$billing_post}%0A
*No HP:* {$billing_phone}%0A
*Email:* {$billing_email}%0A
%0A==============================%0A
*Produk Dipesan:*%0A{$items_text}
==============================%0A
*Total:* {$total}%0A
*Metode Pengiriman:* {$shipping_method}%0A
*Catatan:* " . ($shipping_note ?: '-') . "%0A
==============================%0A
🕐 Pesanan ini dilakukan via situs: " . get_bloginfo('name') . "%0A";

    $encoded_msg = rawurlencode($message);

    // Buat dua link WhatsApp (Admin & Customer)
    $wa_url_admin = "https://wa.me/{$wa_admin}?text={$encoded_msg}";
    $wa_url_user  = "https://wa.me/{$wa_user}?text=" . rawurlencode("
Halo {$billing_first}! 👋%0A
Terima kasih telah melakukan *Pre-Order* di *" . get_bloginfo('name') . "*.%0A
Pesanan kamu sudah kami terima dengan detail berikut:%0A
==============================%0A
{$items_text}
==============================%0A
Total: {$total}%0A
Metode Pengiriman: {$shipping_method}%0A
Kami akan menghubungi kamu kembali jika ada info lebih lanjut.%0A
🙏 Terima kasih!
");

    // Redirect otomatis ke WA Admin & buka tab baru untuk user
    echo "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            window.open('{$wa_url_admin}', '_blank');
        }, 400);
        setTimeout(function() {
            window.open('{$wa_url_user}', '_blank');
        }, 1200);
    });
    </script>
    ";
});


add_action('woocommerce_product_options_sku', function () {
    woocommerce_wp_text_input([
        'id' => '_sku_erp',
        'label' => __('SKU ERP', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('Kode produk dari sistem ERP.', 'woocommerce'),
    ]);
});

add_action('woocommerce_admin_process_product_object', function ($product) {
    if (isset($_POST['_sku_erp'])) {
        $product->update_meta_data('_sku_erp', sanitize_text_field($_POST['_sku_erp']));
    }
});

add_filter('registration_errors', function ($errors, $sanitized_user_login, $user_email) {
    $blocked_domains = ['gmx.sg', 'ronaldofmail.com', 'kra24.work'];
    foreach ($blocked_domains as $domain) {
        if (strpos($user_email, $domain) !== false) {
            $errors->add('blocked_domain', __('Registrasi dengan domain email ini tidak diperbolehkan.'));
        }
    }
    return $errors;
}, 10, 3);
