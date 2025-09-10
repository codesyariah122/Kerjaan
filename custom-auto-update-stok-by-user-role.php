<?php

/**
 * =========================
 * Stok otomatis Mitra (Full AJAX + rollback + live frontend)
 * =========================
 */

// Add to cart via AJAX → kurangi stok sementara & simpan session
add_action('wp_ajax_mitra_add_to_cart', 'mitra_add_to_cart_ajax');
function mitra_add_to_cart_ajax()
{
    if (!current_user_can('mitra') || !isset($_POST['variation_id'], $_POST['quantity'])) {
        wp_send_json_error();
    }

    $variation_id = intval($_POST['variation_id']);
    $quantity = intval($_POST['quantity']);
    $product = wc_get_product($variation_id);
    if (!$product || !$product->managing_stock()) {
        wp_send_json_error();
    }

    // Simpan reservation di session
    $reserved = WC()->session->get('mitra_reserved_stock') ?: [];
    $found = false;
    foreach ($reserved as &$r) {
        if ($r['variation_id'] == $variation_id) {
            $r['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $reserved[] = [
            'variation_id' => $variation_id,
            'quantity' => $quantity
        ];
    }
    WC()->session->set('mitra_reserved_stock', $reserved);

    // Kurangi stok sementara di DB
    $product->set_stock_quantity(max(0, $product->get_stock_quantity() - $quantity));
    $product->save();

    wp_send_json_success(['stok' => $product->get_stock_quantity()]);
}

// Simpan reserved stok ke order meta saat checkout
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    $reserved = WC()->session->get('mitra_reserved_stock') ?: [];
    if (!empty($reserved)) {
        $order->update_meta_data('_mitra_reserved_stock', $reserved);
    }
}, 10, 2);

// Rollback stok jika order gagal atau dibatalkan
function mitra_rollback_reserved_stock($order_id)
{
    $order = wc_get_order($order_id);
    $reserved = $order->get_meta('_mitra_reserved_stock') ?: [];
    foreach ($reserved as $item) {
        $product = wc_get_product($item['variation_id']);
        if ($product && $product->managing_stock()) {
            $product->set_stock_quantity($product->get_stock_quantity() + $item['quantity']);
            $product->save();
        }
    }
}
add_action('woocommerce_order_status_failed', 'mitra_rollback_reserved_stock');
add_action('woocommerce_order_status_cancelled', 'mitra_rollback_reserved_stock');

// Remove cart item via AJAX → rollback stok & update cart
add_action('wp_ajax_woocommerce_remove_cart_item', 'mitra_remove_cart_item_ajax', 20);
function mitra_remove_cart_item_ajax()
{
    if (!current_user_can('mitra')) return;

    if (isset($_POST['cart_item_key'])) {
        $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
        $cart = WC()->cart;
        $cart_item = $cart->get_cart_item($cart_item_key);

        if ($cart_item) {
            $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : $cart_item['product_id'];
            $quantity = $cart_item['quantity'];

            // rollback stok dari session
            $reserved = WC()->session->get('mitra_reserved_stock') ?: [];
            $new_reserved = [];
            foreach ($reserved as $res) {
                if ($res['variation_id'] == $variation_id) {
                    $product = wc_get_product($variation_id);
                    if ($product && $product->managing_stock()) {
                        $product->set_stock_quantity($product->get_stock_quantity() + $res['quantity']);
                        $product->save();
                    }
                } else {
                    $new_reserved[] = $res;
                }
            }
            WC()->session->set('mitra_reserved_stock', $new_reserved);

            // hapus item dari cart WooCommerce
            $cart->remove_cart_item($cart_item_key);
        }
    }
    WC()->cart->calculate_totals();
    WC_AJAX::get_refreshed_fragments();
}

// AJAX helper ambil stok terbaru
add_action('wp_ajax_mitra_get_stock', 'mitra_get_stock');
function mitra_get_stock()
{
    if (!current_user_can('mitra') || !isset($_POST['variation_id'])) {
        wp_send_json_error();
    }

    $variation_id = intval($_POST['variation_id']);
    $product = wc_get_product($variation_id);
    if ($product && $product->managing_stock()) {
        wp_send_json_success(['stok' => $product->get_stock_quantity()]);
    }
    wp_send_json_error();
}

// Update stok frontend live + handle remove cart
add_action('wp_footer', function () {
    if (!is_product() && !is_cart()) return;
?>
    <script>
        jQuery(function($) {
            if (!<?php echo current_user_can('mitra') ? 'true' : 'false'; ?>) return;

            function update_stock(variation_id) {
                if (!variation_id) return;
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'mitra_get_stock',
                    variation_id: variation_id
                }, function(response) {
                    if (response.success && response.data.stok !== undefined) {
                        $('.woocommerce-variation-availability p.stock').text('Stok ' + response.data.stok);
                        $('input.wc_input_stock').val(response.data.stok);
                    }
                });
            }

            // AJAX Add to Cart
            $('form.cart').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var variation_id = $form.find('input.variation_id').val();
                var quantity = parseInt($form.find('input.qty').val()) || 1;

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'mitra_add_to_cart',
                    variation_id: variation_id,
                    quantity: quantity
                }, function(response) {
                    if (response.success && response.data.stok !== undefined) {
                        update_stock(variation_id);
                        $.post('<?php echo wc_get_cart_url(); ?>', {
                            action: 'woocommerce_get_refreshed_fragments'
                        }, function(fragments) {
                            if (fragments && fragments.fragments) {
                                $.each(fragments.fragments, function(key, value) {
                                    $(key).replaceWith(value);
                                });
                            }
                        });
                    }
                });
            });

            // Handle remove item di semua cart
            $('body').on('click', '.remove_from_cart_button, .c-cart__shop-remove', function(e) {
                e.preventDefault();
                var $btn = $(this);

                // Ambil cart_item_key dari data attribute atau dari href
                var cart_item_key = $btn.data('cart_item_key');
                if (!cart_item_key) {
                    var href = $btn.attr('href') || '';
                    var match = href.match(/remove_item=([^&]+)/);
                    if (match) cart_item_key = match[1];
                }

                if (!cart_item_key) return;

                var $row = $btn.closest('tr.cart_item');

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'woocommerce_remove_cart_item',
                    cart_item_key: cart_item_key
                }, function() {
                    // hapus row langsung
                    if ($row.length) $row.fadeOut(200, function() {
                        $(this).remove();
                    });

                    // update stok live
                    $('.woocommerce-variation-availability p.stock').each(function() {
                        var variation_id = $(this).closest('form.cart, .product').find('input.variation_id').val();
                        if (variation_id) update_stock(variation_id);
                    });

                    // update mini-cart & totals
                    $.post('<?php echo wc_get_cart_url(); ?>', {
                        action: 'woocommerce_get_refreshed_fragments'
                    }, function(fragments) {
                        if (fragments && fragments.fragments) {
                            $.each(fragments.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                        }

                        if ($('.shop_table.cart').length) {
                            window.location.reload();
                        }
                    });
                });
            });


            // update stok live saat pilih variasi
            $('form.cart').on('found_variation', function(e, variation) {
                update_stock(variation.variation_id);
            });

            // update stok live saat qty diubah
            $('form.cart').on('change', 'input.qty', function() {
                var variation_id = $('form.cart input.variation_id').val();
                update_stock(variation_id);
            });

            // initial update
            var initial_variation = $('form.cart input.variation_id').val();
            if (initial_variation) update_stock(initial_variation);
        });
    </script>
<?php
});


/**
 * =========================
 * Countdown Pembayaran Mitra (Deadline Per Produk)
 * =========================
 */

// 1️⃣ Tambah field di tab Umum produk
// Tambah field deadline (posisi sementara di tab umum)
add_action('woocommerce_product_options_general_product_data', function () {
    global $post;

    $saved = get_post_meta($post->ID, '_mitra_payment_deadline', true);

    $value = '';
    if ($saved) {
        $dt = new DateTime('@' . $saved);
        $dt->setTimezone(wp_timezone());
        $value = $dt->format('Y-m-d\TH:i');
    }

    echo '<div class="options_group" id="deadline-wrapper">';
    woocommerce_wp_text_input([
        'id'          => '_mitra_payment_deadline',
        'label'       => 'Deadline Pembayaran Mitra',
        'value'       => $value,
        'desc_tip'    => true,
        'description' => 'Set tanggal & jam batas pembayaran (format: YYYY-MM-DD HH:MM)',
        'type'        => 'datetime-local',
    ]);
    echo '</div>';
});

// JS untuk mindahin field ke bawah select role
add_action('admin_footer', function () {
    global $pagenow;
    if ($pagenow !== 'post.php' && $pagenow !== 'post-new.php') return;
?>
    <script>
        jQuery(function($) {
            // pindahkan #deadline-wrapper ke bawah field _visible_for_role
            var $roleField = $('#_visible_for_role').closest('p, .form-field');
            if ($roleField.length) {
                $('#deadline-wrapper').insertAfter($roleField);
            }
        });
    </script>
<?php
});


// 2️⃣ Simpan field deadline
add_action('woocommerce_admin_process_product_object', function ($product) {
    if (isset($_POST['_mitra_payment_deadline'])) {
        $val = sanitize_text_field($_POST['_mitra_payment_deadline']);
        if ($val) {
            try {
                $dt = new DateTime($val, wp_timezone());
                $product->update_meta_data('_mitra_payment_deadline', $dt->getTimestamp());
            } catch (Exception $e) {
                $product->update_meta_data('_mitra_payment_deadline', strtotime($val));
            }
        } else {
            $product->delete_meta_data('_mitra_payment_deadline');
        }
    }
});

// 3️⃣ Styling countdown
add_action('wp_head', function () {
?>
    <style>
        #mitra-countdown-box {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            max-width: 100%;
        }

        #mitra-countdown-box>div:first-child {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        #mitra-countdown {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        #mitra-countdown .count-box {
            background: #8B4513;
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            min-width: 70px;
            text-align: center;
            flex: 1 1 auto;
        }

        #mitra-countdown .count-box div:first-child {
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
        }

        #mitra-countdown .count-box div:last-child {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        @media (max-width: 480px) {
            #mitra-countdown {
                gap: 6px;
            }

            #mitra-countdown .count-box {
                padding: 8px 10px;
                min-width: 55px;
            }

            #mitra-countdown .count-box div:first-child {
                font-size: 16px;
            }
        }
    </style>
<?php
});

// 4️⃣ Countdown + Hide tombol checkout
add_action('wp_footer', function () {
    $cart = WC()->cart->get_cart();
    if (empty($cart)) return;

    // ambil produk pertama di cart
    $first = reset($cart);
    $product_id = $first['product_id'];
    if (!$product_id) return;

    $end_time = get_post_meta($product_id, '_mitra_payment_deadline', true);
    if (!$end_time) return;
?>
    <script>
        jQuery(function($) {
            var end = <?php echo $end_time * 1000; ?>;

            var $btn = $(
                '.c-cart__checkout-btn, ' +
                '.wc-proceed-to-checkout .checkout-button, ' +
                '.woocommerce-mini-cart__buttons .checkout'
            );

            if ($('body').hasClass('woocommerce-cart')) {
                $btn.hide().after(`
                <div id="mitra-countdown-box">
                    <div>⚡ Waktu Pembayaran Mitra</div>
                    <div id="mitra-countdown"></div>
                </div>
            `);
            } else {
                $btn.hide();
            }

            function hideMiniCartCheckout() {
                $('.woocommerce-mini-cart__buttons .checkout').hide();
            }
            hideMiniCartCheckout();
            $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
                hideMiniCartCheckout();
            });

            function updateCountdown() {
                var now = Date.now();
                var dist = end - now;

                if (dist <= 0) {
                    $('#mitra-countdown-box').remove();
                    $btn.show().text('Lanjutkan Pembayaran').css({
                        'pointer-events': 'auto',
                        'opacity': '1',
                        'cursor': 'pointer'
                    });
                    return;
                }

                var d = Math.floor(dist / (1000 * 60 * 60 * 24));
                var h = Math.floor((dist % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var m = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60));
                var s = Math.floor((dist % (1000 * 60)) / 1000);

                $('#mitra-countdown').html(`
                <div class="count-box"><div>${d}</div><div>Days</div></div>
                <div class="count-box"><div>${h}</div><div>Hours</div></div>
                <div class="count-box"><div>${m}</div><div>Minutes</div></div>
                <div class="count-box"><div>${s}</div><div>Seconds</div></div>
            `);

                setTimeout(updateCountdown, 1000);
            }

            if ($('body').hasClass('woocommerce-cart')) {
                updateCountdown();
            }
        });
    </script>
<?php
});
