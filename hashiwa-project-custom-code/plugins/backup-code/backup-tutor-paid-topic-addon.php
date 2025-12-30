<?php
/*
Plugin Name: Tutor Paid Topic Addon (Rupiah) - Stable v1.6.1
Description: Simpan harga per topic per course di Tutor LMS React Builder. Fix REST + visual badge.
Version: 1.6.1
Author: Puji Ermanto
*/

if (!defined('ABSPATH')) exit;

/* =======================================================
   1️⃣ Buat tabel custom
======================================================= */
register_activation_hook(__FILE__, function () {
    global $wpdb;
    // $table = "{$wpdb->prefix}tutor_topic_price";
    $table = "wpsu_tutor_topic_price"; // hardcode ke tabel yang benar

    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        course_id BIGINT(20) UNSIGNED NOT NULL,
        topic_title VARCHAR(255) NOT NULL,
        price INT(11) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_topic (course_id, topic_title)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

/* =======================================================
   2️⃣ Enqueue JS
======================================================= */
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'tutor') === false) return;

    wp_enqueue_script(
        'tpt-addon-script',
        plugin_dir_url(__FILE__) . 'tutor-paid-topic.js',
        ['jquery'],
        '1.6.1',
        true
    );

    wp_localize_script('tpt-addon-script', 'TPT_Ajax', [
        'resturl' => esc_url(rest_url('tutor-paid-topic/v1/')),
        'nonce'   => wp_create_nonce('wp_rest')
    ]);
});

/* =======================================================
   3️⃣ REST API
======================================================= */
add_action('rest_api_init', function () {
    global $wpdb;
    $table = "{$wpdb->prefix}tutor_topic_price";

    register_rest_route('tutor-paid-topic/v1', '/save-price', [
        'methods' => 'POST',
        'callback' => function ($req) {
            global $wpdb;
            $table = "wpsu_tutor_topic_price"; // ← fix prefix
            $data = $req->get_json_params();
            $title = sanitize_text_field($data['title'] ?? '');
            $price = intval($data['price'] ?? 0);
            $course_id = intval($data['course_id'] ?? 0);

            if (!$title || !$course_id) {
                return new WP_Error('invalid_data', 'Judul atau Course ID kosong.', ['status' => 400]);
            }

            $wpdb->show_errors();
            $wpdb->replace($table, [
                'course_id'   => $course_id,
                'topic_title' => $title,
                'price'       => $price
            ]);
            error_log("Tutor Paid Topic | SQL: " . $wpdb->last_query);
            error_log("Tutor Paid Topic | Error: " . $wpdb->last_error);

            return ['success' => true, 'message' => "Harga topik '{$title}' disimpan (Rp {$price})"];
        },
        'permission_callback' => '__return_true'
    ]);


    register_rest_route('tutor-paid-topic/v1', '/get-price', [
        'methods' => 'GET',
        'callback' => function ($req) use ($wpdb, $table) {
            $title = sanitize_text_field($req['title'] ?? '');
            $course_id = intval($req['course_id'] ?? 0);

            if (!$title || !$course_id) return ['price' => 0];

            $data = $wpdb->get_row($wpdb->prepare(
                "SELECT price FROM $table WHERE course_id = %d AND topic_title = %s",
                $course_id,
                $title
            ));
            return $data ?: ['price' => 0];
        },
        'permission_callback' => '__return_true'
    ]);
});

/* =======================================================
   4️⃣ Sembunyikan harga bawaan Tutor LMS
======================================================= */
add_action('admin_print_footer_scripts', function () {
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const hidePriceFields = () => {
                document.querySelectorAll('.css-1xhi066 [data-cy="form-field-wrapper"]').forEach(wrapper => {
                    const label = wrapper.querySelector('label');
                    if (!label) return;
                    const text = label.textContent.trim();
                    if (text === 'Regular Price' || text === 'Sale Price') wrapper.style.display = 'none';
                });
            };
            const obs = new MutationObserver(() => hidePriceFields());
            obs.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    </script>
<?php
});



// Backup baru untuk override 
<?php
/*
Plugin Name: Tutor Paid Topic Addon (Rupiah) - Stable v1.6.1
Description: Simpan harga per topic per course di Tutor LMS React Builder. Fix REST + visual badge.
Version: 1.6.1
Author: Puji Ermanto
*/

if (!defined('ABSPATH')) exit;

/**
 * -------------------------
 * Activation: create table
 * -------------------------
 */
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table = $wpdb->prefix . 'tutor_topic_price';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        course_id BIGINT(20) UNSIGNED NOT NULL,
        topic_title VARCHAR(255) NOT NULL,
        price INT(11) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_topic (course_id, topic_title)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

/**
 * -------------------------
 * Helper: table name with fallback
 * -------------------------
 */
function tpt_table_name($name = 'tutor_topic_price')
{
    global $wpdb;
    $wp_table = $wpdb->prefix . $name;
    $alt_table = 'wpsu_' . $name;
    if ($wpdb->get_var("SHOW TABLES LIKE '{$wp_table}'") === $wp_table) return $wp_table;
    if ($wpdb->get_var("SHOW TABLES LIKE '{$alt_table}'") === $alt_table) return $alt_table;
    return $wp_table;
}

/**
 * -------------------------
 * REST API: save/get/get-course-prices
 * -------------------------
 */
add_action('rest_api_init', function () {
    global $wpdb;
    $table = tpt_table_name();

    register_rest_route('tutor-paid-topic/v1', '/save-price', [
        'methods' => 'POST',
        'callback' => function ($req) use ($wpdb, $table) {
            if (!current_user_can('edit_posts')) {
                return new WP_Error('forbidden', 'Not allowed', ['status' => 403]);
            }
            $data = $req->get_json_params();
            $title = sanitize_text_field($data['title'] ?? '');
            $price = intval($data['price'] ?? 0);
            $course_id = intval($data['course_id'] ?? 0);

            if (!$title || !$course_id) {
                return new WP_Error('invalid_data', 'Judul atau Course ID kosong.', ['status' => 400]);
            }

            $wpdb->replace($table, [
                'course_id' => $course_id,
                'topic_title' => $title,
                'price' => $price
            ], ['%d', '%s', '%d']);

            if ($wpdb->last_error) {
                return new WP_Error('sql_error', $wpdb->last_error, ['status' => 500]);
            }

            return rest_ensure_response(['success' => true, 'message' => "Harga topik '{$title}' disimpan (Rp {$price})"]);
        },
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('tutor-paid-topic/v1', '/get-price', [
        'methods' => 'GET',
        'callback' => function ($req) use ($wpdb, $table) {
            $title = sanitize_text_field($req['title'] ?? '');
            $course_id = intval($req['course_id'] ?? 0);
            if (!$title || !$course_id) return rest_ensure_response(['price' => 0]);

            $data = $wpdb->get_row($wpdb->prepare("SELECT price FROM $table WHERE course_id = %d AND topic_title = %s", $course_id, $title));
            return rest_ensure_response($data ?: ['price' => 0]);
        },
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('tutor-paid-topic/v1', '/get-course-prices', [
        'methods' => 'GET',
        'callback' => function ($req) use ($wpdb, $table) {
            $course_id = intval($req['course_id'] ?? 0);
            $topic_title = sanitize_text_field($req['topic_title'] ?? '');

            if (!$course_id) return rest_ensure_response(['has_price' => false]);

            if ($topic_title) {
                $price = $wpdb->get_var($wpdb->prepare("SELECT price FROM $table WHERE course_id = %d AND topic_title = %s", $course_id, $topic_title));
                return rest_ensure_response(['has_price' => !empty($price), 'min_price' => intval($price)]);
            }

            $rows = $wpdb->get_results($wpdb->prepare("SELECT price FROM $table WHERE course_id = %d AND price > 0", $course_id));
            $prices = array_map(function ($r) {
                return intval($r->price);
            }, $rows ?: []);
            if (empty($prices)) return rest_ensure_response(['has_price' => false]);
            return rest_ensure_response([
                'has_price' => true,
                'total_price' => array_sum($prices),
                'min_price' => min($prices),
                'max_price' => max($prices),
            ]);
        },
        'permission_callback' => '__return_true'
    ]);
});

/**
 * -------------------------
 * Admin: hide regular/sale price fields & add topic price input + badge
 * -------------------------
 */
add_action('admin_print_footer_scripts', function () {
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // hide Tutor regular/sale price form fields in React builder area
            const hidePriceFields = () => {
                document.querySelectorAll('.css-1xhi066 [data-cy="form-field-wrapper"]').forEach(wrapper => {
                    const label = wrapper.querySelector('label');
                    if (!label) return;
                    const text = label.textContent.trim();
                    if (text === 'Regular Price' || text === 'Sale Price') wrapper.style.display = 'none';
                });
            };

            // Render badges in topic list (throttled)
            let lastRender = 0;
            const renderAllTopicPrices = () => {
                const now = Date.now();
                if (now - lastRender < 3000) return;
                lastRender = now;

                const topics = document.querySelectorAll('.css-1uvctym, .tutor-topic-row, .tutor-topic-item');
                if (!topics.length) return;

                const params = new URLSearchParams(window.location.search);
                const courseId = params.get('course_id') || document.querySelector('[data-course-id]')?.dataset.courseId || '';

                topics.forEach(topic => {
                    const titleEl = topic.querySelector('.css-1jlm4v3, .topic-title, .tutor-topic-name') || topic;
                    if (!titleEl) return;
                    const topicTitle = titleEl.textContent.trim();
                    if (!topicTitle) return;

                    // remove old badges
                    titleEl.querySelectorAll('.tpt-price-badge').forEach(b => b.remove());

                    fetch(`${location.origin}/wp-json/tutor-paid-topic/v1/get-price?title=${encodeURIComponent(topicTitle)}&course_id=${courseId}`, {
                        headers: {
                            'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                        }
                    }).then(r => r.json()).then(data => {
                        if (data?.price && data.price > 0) {
                            const badge = document.createElement('span');
                            badge.className = 'tpt-price-badge';
                            badge.textContent = `Rp ${Number(data.price).toLocaleString('id-ID')}`;
                            badge.style.cssText = 'margin-left:10px;font-size:13px;background:#ED2D56;color:#fff;padding:2px 8px;border-radius:8px;font-weight:600;display:inline-block;vertical-align:middle;';
                            titleEl.appendChild(badge);
                        }
                    }).catch(err => console.warn("Fetch error (get-price):", err));
                });
            };

            // Add input to topic modal/editor & hook save button
            const attachTopicEditor = () => {
                document.querySelectorAll('.css-oks3g7, .tutor-topic-editor, .tutor-topic-modal').forEach(topicEl => {
                    if (topicEl.querySelector('.tutor-topic-price')) return;

                    const titleInput = topicEl.querySelector('input[name="title"], input[placeholder="Title"], input[placeholder="Judul"]');
                    const wrapper = topicEl.querySelector('.css-15gb5bw, .topic-meta, .editor-field') || topicEl;
                    if (!wrapper || !titleInput) return;

                    const input = document.createElement('input');
                    input.type = 'number';
                    input.placeholder = 'Masukkan harga topik (Rp)';
                    input.className = 'tutor-input-field tutor-topic-price';
                    input.style.cssText = 'margin-top:10px;width:100%;border:1px solid #ddd;padding:6px;border-radius:8px;';
                    wrapper.appendChild(input);

                    // try fill existing price when modal opens
                    const params = new URLSearchParams(window.location.search);
                    const courseId = params.get('course_id') || document.querySelector('[data-course-id]')?.dataset.courseId || '';

                    if (titleInput?.value) {
                        fetch(`${location.origin}/wp-json/tutor-paid-topic/v1/get-price?title=${encodeURIComponent(titleInput.value)}&course_id=${courseId}`, {
                            headers: {
                                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                            }
                        }).then(r => r.json()).then(data => {
                            if (data?.price) input.value = data.price;
                        }).catch(() => {});
                    }

                    const saveBtn = topicEl.querySelector('button[data-cy="save-topic"], button.save-topic, button.tutor-save-topic') || topicEl.querySelector('button');
                    if (saveBtn && !saveBtn.dataset.tptBound) {
                        saveBtn.dataset.tptBound = '1';
                        saveBtn.addEventListener('click', () => {
                            const title = titleInput?.value?.trim();
                            const price = input.value ? parseInt(input.value) : 0;
                            if (!title || !courseId) return;
                            fetch(`${location.origin}/wp-json/tutor-paid-topic/v1/save-price`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                                },
                                body: JSON.stringify({
                                    title,
                                    price,
                                    course_id: courseId
                                })
                            }).then(r => r.json()).then(resp => {
                                if (resp?.success) {
                                    setTimeout(() => renderAllTopicPrices(), 700);
                                }
                            }).catch(err => console.error('REST Error:', err));
                        });
                    }
                });
            };

            // Mutation observer
            const obs = new MutationObserver(() => {
                hidePriceFields();
                attachTopicEditor();
                renderAllTopicPrices();
            });
            obs.observe(document.body, {
                childList: true,
                subtree: true
            });

            // initial run
            setTimeout(() => {
                hidePriceFields();
                attachTopicEditor();
                renderAllTopicPrices();
            }, 1500);
        });
    </script>
    <style>
        .tpt-price-badge {
            margin-left: 10px;
            font-size: 13px;
            background: #ED2D56;
            color: #fff;
            padding: 2px 8px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
<?php
});

/**
 * -------------------------
 * Frontend: inject replacement JS + CSS (cards, single, curriculum, cart, checkout)
 * -------------------------
 */
add_action('wp_footer', function () {
    // inline script - uses REST endpoints above
?>
    <script>
        (function() {
            const restBase = '<?php echo esc_url_raw(rest_url('tutor-paid-topic/v1/')); ?>';

            function formatRupiah(n) {
                return 'Rp ' + Number(n).toLocaleString('id-ID');
            }

            document.addEventListener("DOMContentLoaded", async () => {
                // Course cards
                const cards = document.querySelectorAll('.tp-course-item, .tutor-course-card, .course-card');
                for (const card of cards) {
                    const btn = card.querySelector('[data-course-id], a[data-course-id], button[data-course-id]');
                    const courseId = btn ? btn.dataset.courseId : (card.dataset.courseId || null);
                    if (!courseId) continue;

                    try {
                        const res = await fetch(restBase + 'get-course-prices?course_id=' + encodeURIComponent(courseId) + '&t=' + Date.now());
                        const data = await res.json();

                        // remove default prices
                        card.querySelectorAll('.tutor-item-price, .tutor-course-price').forEach(el => el.remove());

                        const priceArea = card.querySelector('.tp-course-pricing, .tp-course-btn .tutor-course-price, .course-price, .course-card-price');
                        if (!data || !data.has_price) {
                            if (priceArea) priceArea.innerHTML = '';
                            continue;
                        }

                        const min = Number(data.min_price || 0);
                        const max = Number(data.max_price || 0);
                        const wrapper = document.createElement('div');
                        wrapper.className = 'tpt-course-price-wrapper';
                        if (min === max) {
                            wrapper.innerHTML = `<span class="tpt-course-total">Harga Per Topic:</span> <span class="tpt-course-price">${formatRupiah(min)}</span>`;
                        } else {
                            wrapper.innerHTML = `<span class="tpt-course-total">Harga Per Topic:</span> <span class="tpt-course-price">${formatRupiah(min)} – ${formatRupiah(max)}</span>`;
                        }
                        if (priceArea) {
                            priceArea.innerHTML = '';
                            priceArea.appendChild(wrapper);
                        }

                    } catch (e) {
                        console.warn('Gagal ambil harga topik (card):', e);
                    }
                }

                // Single course sidebar + curriculum badges
                const singlePricing = document.querySelector('.tutor-course-sidebar-card-pricing, .course-sidebar .course-pricing, .single-course .tutor-course-sidebar-card-pricing');
                const courseBtn = document.querySelector('[data-course-id], a[data-course-id], button[data-course-id]');
                const courseId = courseBtn ? courseBtn.dataset.courseId : (window.tutor_course_id || null);

                if (courseId && singlePricing) {
                    try {
                        const res = await fetch(restBase + 'get-course-prices?course_id=' + encodeURIComponent(courseId) + '&t=' + Date.now());
                        const data = await res.json();
                        if (!data || !data.has_price) {
                            singlePricing.style.display = 'none';
                        } else {
                            const min = Number(data.min_price || 0);
                            const max = Number(data.max_price || 0);
                            singlePricing.innerHTML = '';
                            const wrapper = document.createElement('div');
                            wrapper.className = 'tpt-course-price-wrapper';
                            wrapper.innerHTML = `<span class="tpt-course-total">Harga Per Topic:</span> <span class="tpt-course-price">${min === max ? formatRupiah(min) : (formatRupiah(min) + ' – ' + formatRupiah(max))}</span>`;
                            singlePricing.appendChild(wrapper);
                        }
                    } catch (e) {
                        console.warn('Gagal ambil harga topik (single):', e);
                    }
                }

                // curriculum badges
                const topicHeaders = document.querySelectorAll('.tutor-accordion-item-header, .tutor-topic-name, .curriculum-item .title');
                if (topicHeaders.length && courseId) {
                    for (const header of topicHeaders) {
                        try {
                            const topicTitleNode = Array.from(header.childNodes).find(n => n.nodeType === Node.TEXT_NODE);
                            const topicTitle = (topicTitleNode ? topicTitleNode.textContent : header.textContent).trim();
                            if (!topicTitle) continue;

                            const tres = await fetch(restBase + 'get-course-prices?course_id=' + encodeURIComponent(courseId) + '&topic_title=' + encodeURIComponent(topicTitle) + '&t=' + Date.now());
                            const tdata = await tres.json();
                            if (!tdata || !tdata.min_price) continue;

                            const badge = document.createElement('span');
                            badge.className = 'tpt-topic-badge';
                            badge.innerHTML = '💰 ' + formatRupiah(Number(tdata.min_price));
                            header.appendChild(badge);
                        } catch (err) {
                            /* ignore per-topic errors */
                        }
                    }
                }

                // Cart: replace price displays
                const tutorCart = document.querySelector('.tutor-cart-course-list, .tutor-cart');
                if (tutorCart) {
                    const cartItems = tutorCart.querySelectorAll('.tutor-cart-course-item, .cart-item');
                    for (const item of cartItems) {
                        const removeBtn = item.querySelector('.tutor-cart-remove-button');
                        const courseId = removeBtn ? removeBtn.dataset.courseId : (item.dataset.courseId || null);
                        if (!courseId) continue;
                        try {
                            const res = await fetch(restBase + 'get-course-prices?course_id=' + encodeURIComponent(courseId) + '&t=' + Date.now());
                            const data = await res.json();
                            if (!data || !data.has_price) continue;
                            const price = Number(data.min_price || 0);
                            if (!price) continue;
                            const priceWrap = item.querySelector('.tutor-cart-course-price, .cart-item-price');
                            if (priceWrap) {
                                priceWrap.innerHTML = `<div class="tutor-fw-bold" style="color:#ED2D56;">${formatRupiah(price)}</div><div class="tutor-cart-discount-price" style="display:none;"></div>`;
                            }
                        } catch (err) {
                            console.warn("Gagal ambil harga topic di Tutor Cart:", err);
                        }
                    }

                    // update subtotal display best-effort
                    setTimeout(() => {
                        let total = 0;
                        document.querySelectorAll('.tutor-cart-course-price .tutor-fw-bold, .cart-item-price .tutor-fw-bold').forEach(el => {
                            const val = parseInt(el.textContent.replace(/\D/g, '')) || 0;
                            total += val;
                        });
                        const subTotal = document.querySelector('.tutor-cart-summery-top .tutor-cart-summery-item div:last-child, .cart-subtotal .amount');
                        const grandTotal = document.querySelector('.tutor-cart-summery-bottom .tutor-cart-summery-item div:last-child, .order-total .amount');
                        if (subTotal) subTotal.textContent = formatRupiah(total);
                        if (grandTotal) grandTotal.textContent = formatRupiah(total);
                    }, 800);
                }

                // Checkout: update checkout_data hidden input (if exists)
                const checkoutDataInput = document.getElementById('checkout_data') || document.querySelector('input[name="checkout_data"]');
                if (checkoutDataInput) {
                    try {
                        let checkoutData = JSON.parse(checkoutDataInput.value || '{}');
                        let total = 0;
                        for (let i = 0; i < (checkoutData.items || []).length; i++) {
                            const item = checkoutData.items[i];
                            const courseId = item.item_id || item.course_id || item.product_id;
                            if (!courseId) continue;
                            try {
                                const res = await fetch(restBase + 'get-course-prices?course_id=' + encodeURIComponent(courseId) + '&t=' + Date.now());
                                const data = await res.json();
                                if (!data || !data.has_price) continue;
                                const price = Number(data.min_price || 0);
                                item.regular_price = price;
                                item.sale_price = price;
                                item.display_price = price;
                                total += price;
                                const priceWrap = document.querySelector('.tutor-checkout-course-item[data-course-id="' + courseId + '"] .tutor-text-right');
                                if (priceWrap) priceWrap.innerHTML = `<div class="tutor-fw-bold" style="color:#ED2D56;">${formatRupiah(price)}</div>`;
                            } catch (e) {}
                        }
                        checkoutData.subtotal_price = total;
                        checkoutData.total_price = total;
                        checkoutDataInput.value = JSON.stringify(checkoutData);

                        const subTotalEl = document.querySelector('.tutor-checkout-summary-item .tutor-fw-bold, .checkout-subtotal .amount');
                        const grandTotalEl = document.querySelector('.tutor-checkout-grand-total, .order-total .amount');
                        if (subTotalEl) subTotalEl.textContent = formatRupiah(total);
                        if (grandTotalEl) grandTotalEl.textContent = formatRupiah(total);
                    } catch (e) {}
                }
            });

            // append minimal CSS
            const css = '.tpt-course-price-wrapper{display:flex;align-items:center;gap:6px;margin-top:6px;font-size:15px}.tpt-course-total{color:#555;font-weight:500}.tpt-course-price{color:#ED2D56;font-weight:700}.tpt-topic-badge{display:inline-block;background:#ED2D56;color:#fff;font-size:13px;font-weight:600;border-radius:8px;padding:2px 8px;margin-left:8px;vertical-align:middle}.tpt-price-badge{margin-left:10px;font-size:13px;background:#ED2D56;color:#fff;padding:2px 8px;border-radius:8px;font-weight:600;display:inline-block;vertical-align:middle}';
            const style = document.createElement('style');
            style.appendChild(document.createTextNode(css));
            document.head.appendChild(style);
        })();
    </script>
<?php
});

/**
 * -------------------------
 * Helper: get min topic price for a course
 * -------------------------
 */
function tpt_get_min_topic_price($course_id)
{
    global $wpdb;
    $table = tpt_table_name();
    $val = $wpdb->get_var($wpdb->prepare("SELECT MIN(price) FROM $table WHERE course_id = %d AND price > 0", intval($course_id)));
    return floatval($val ?: 0);
}

/**
 * -------------------------
 * Override BEFORE order create (Tutor hook)
 * -------------------------
 */
add_filter('tutor_ecommerce_before_order_create', function ($order_data) {
    if (empty($order_data['items']) || !is_array($order_data['items'])) return $order_data;

    $new_total = 0;
    foreach ($order_data['items'] as $i => $item) {
        $course_id = intval($item['product_id'] ?? $item['course_id'] ?? $item['item_id'] ?? 0);
        if (!$course_id) continue;

        $price = tpt_get_min_topic_price($course_id);
        if (!$price) continue;

        $order_data['items'][$i]['price'] = $price;
        $order_data['items'][$i]['subtotal'] = $price;
        $order_data['items'][$i]['regular_price'] = $price;
        $order_data['items'][$i]['sale_price'] = $price;
        $order_data['items'][$i]['display_price'] = $price;

        $new_total += floatval($price);
    }

    $order_data['subtotal'] = $new_total;
    $order_data['total'] = $new_total;

    return $order_data;
}, 10, 1);

/**
 * -------------------------
 * After order create: update DB rows stored by Tutor
 * -------------------------
 */
// 2) Debug after order created — cek apa yang akhirnya disimpan di DB
add_action('tutor_ecommerce_after_order_create', function ($order_id, $order_data = []) {
    global $wpdb;

    error_log("[TPT DEBUG] tutor_ecommerce_after_order_create CALLED for order_id={$order_id}");
    error_log("[TPT DEBUG] order_data at after_create: " . print_r($order_data, true));

    $items_table = $wpdb->prefix . 'tutor_order_items';
    $orders_table = $wpdb->prefix . 'tutor_orders';

    if ($wpdb->get_var("SHOW TABLES LIKE 'wpsu_tutor_order_items'") === 'wpsu_tutor_order_items') {
        $items_table = 'wpsu_tutor_order_items';
    }
    if ($wpdb->get_var("SHOW TABLES LIKE 'wpsu_tutor_orders'") === 'wpsu_tutor_orders') {
        $orders_table = 'wpsu_tutor_orders';
    }

    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$items_table} WHERE order_id = %d", $order_id));
    error_log("[TPT DEBUG] DB items for order {$order_id}: " . print_r($items, true));

    $order_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders_table} WHERE id = %d", $order_id));
    error_log("[TPT DEBUG] DB order row: " . print_r($order_row, true));
}, 10, 2);


/**
 * -------------------------
 * Optional: override display price filter
 * -------------------------
 */
add_filter('tutor_course_price', function ($price_html, $course_id) {
    $min = tpt_get_min_topic_price($course_id);
    if ($min && is_numeric($min)) {
        return '<span class="tpt-course-price-inline">Rp ' . number_format($min, 0, ',', '.') . '</span>';
    }
    return $price_html;
}, 10, 2);

/**
 * ============================================
 *  FIX 100% — Override Harga Saat Checkout (SERVER-SIDE)
 * ============================================
 */
// 1) Debug tutor_lms_checkout_create_order_data
add_filter('tutor_lms_checkout_create_order_data', function ($order_data) {
    global $wpdb;
    $table = tpt_table_name();

    // DEBUG: pastikan hook jalan
    error_log("[TPT DEBUG] tutor_lms_checkout_create_order_data CALLED");
    error_log("[TPT DEBUG] Table used: " . $table);
    error_log("[TPT DEBUG] Incoming order_data keys: " . implode(',', array_keys($order_data)));

    $subtotal = 0;

    if (! empty($order_data['cart_items']) && is_array($order_data['cart_items'])) {
        foreach ($order_data['cart_items'] as $key => &$item) {
            $course_id = intval($item['product_id'] ?? $item['course_id'] ?? 0);
            error_log("[TPT DEBUG] cart item #{$key} product_id={$course_id} raw=" . print_r($item, true));

            if (!$course_id) continue;

            $min_price = $wpdb->get_var($wpdb->prepare(
                "SELECT MIN(price) FROM {$table} WHERE course_id = %d AND price > 0",
                $course_id
            ));

            error_log("[TPT DEBUG] db min_price for course {$course_id}: " . var_export($min_price, true));

            if (!$min_price) continue;
            $min_price = intval($min_price);

            // Override
            $item['price'] = $min_price;
            $item['regular_price'] = $min_price;
            $item['sale_price'] = $min_price;
            $item['line_subtotal'] = $min_price;
            $item['line_total'] = $min_price;

            $subtotal += $min_price;
        }
    } else {
        error_log("[TPT DEBUG] cart_items missing or empty");
    }

    $order_data['subtotal'] = $subtotal;
    $order_data['total'] = $subtotal;

    error_log("[TPT DEBUG] Calculated subtotal/total: {$subtotal}");
    error_log("[TPT DEBUG] Modified order_data: " . print_r($order_data, true));

    return $order_data;
}, 20, 1);


/* End of plugin */
