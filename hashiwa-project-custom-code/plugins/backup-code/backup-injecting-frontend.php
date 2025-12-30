<?php

/**
 * 🔹 Show Topic Price Range on Course Card (Acadia Theme)
 * Author: Puji Ermanto
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) exit;

/**
 * 1️⃣ REST API Endpoint - Get total or range price by course ID
 */
add_action('rest_api_init', function () {
    register_rest_route('tpt-card/v1', '/get-course-prices', [
        'methods' => 'GET',
        'callback' => function ($req) {
            global $wpdb;
            $table = "{$wpdb->prefix}tutor_topic_price";
            $course_id = intval($req['course_id'] ?? 0);
            $topic_title = sanitize_text_field($req['topic_title'] ?? '');

            if (!$course_id) {
                return ['has_price' => false];
            }

            // 🟢 Jika ada topic_title (permintaan harga per topik)
            if ($topic_title) {
                $price = $wpdb->get_var($wpdb->prepare(
                    "SELECT price FROM $table WHERE course_id = %d AND topic_title = %s",
                    $course_id,
                    $topic_title
                ));

                return [
                    'has_price' => !empty($price),
                    'min_price' => intval($price),
                ];
            }

            // 🔵 Jika tidak ada topic_title → ambil range harga seluruh topik di course ini
            $topics = $wpdb->get_results($wpdb->prepare("
                SELECT post_title 
                FROM {$wpdb->posts}
                WHERE post_type = 'topics'
                  AND post_parent = %d
                  AND post_status = 'publish'
            ", $course_id));

            $prices = [];

            foreach ($topics as $topic) {
                $price = $wpdb->get_var($wpdb->prepare(
                    "SELECT price FROM $table WHERE topic_title = %s AND course_id = %d",
                    $topic->post_title,
                    $course_id
                ));
                if (!empty($price) && $price > 0) {
                    $prices[] = intval($price);
                }
            }

            if (empty($prices)) {
                return ['has_price' => false];
            }

            return [
                'has_price'   => true,
                'total_price' => array_sum($prices),
                'min_price'   => min($prices),
                'max_price'   => max($prices),
            ];
        },
        'permission_callback' => '__return_true',
    ]);
});


/**
 * 2️⃣ Inject JS on Frontend - Replace default Tutor price in Acadia course card
 */
add_action('wp_footer', function () {
?>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const cards = document.querySelectorAll('.tp-course-item');

            for (const card of cards) {
                const btn = card.querySelector('[data-course-id]');
                const courseId = btn ? btn.dataset.courseId : null;
                if (!courseId) continue;

                try {
                    // Gunakan parameter unik untuk cegah cache
                    const res = await fetch(`/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}&t=${Date.now()}`);
                    const data = await res.json();

                    // Hapus harga default Tutor bawaan
                    const defaultPriceEls = card.querySelectorAll('.tutor-item-price');
                    defaultPriceEls.forEach(el => el.remove());

                    // Kosongkan area pricing jika tidak ada harga
                    const priceArea = card.querySelector('.tp-course-pricing, .tp-course-btn .tutor-course-price');
                    if (!data.has_price) {
                        if (priceArea) priceArea.innerHTML = ''; // kosongin
                        continue;
                    }

                    // Buat tampilan harga topik
                    const priceWrapper = document.createElement('div');
                    priceWrapper.className = 'tpt-course-price-wrapper';

                    const min = Number(data.min_price || 0);
                    const max = Number(data.max_price || 0);

                    if (min === max) {
                        priceWrapper.innerHTML = `
                    <span class="tpt-course-total">Harga Per Topic:</span>
                    <span class="tpt-course-price">Rp ${min.toLocaleString('id-ID')}</span>`;
                    } else {
                        priceWrapper.innerHTML = `
                    <span class="tpt-course-total">Harga Per Topic:</span>
                    <span class="tpt-course-price">Rp ${min.toLocaleString('id-ID')} – Rp ${max.toLocaleString('id-ID')}</span>`;
                    }

                    // Tambahkan harga baru ke area pricing
                    if (priceArea) {
                        priceArea.innerHTML = ''; // clear sisa HTML lama
                        priceArea.appendChild(priceWrapper);
                    }
                } catch (e) {
                    console.warn('Gagal ambil harga topik:', e);
                }
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const singlePricing = document.querySelector('.tutor-course-sidebar-card-pricing');
            if (!singlePricing) return;

            const courseBtn = document.querySelector('[data-course-id]');
            const courseId = courseBtn ? courseBtn.dataset.courseId : null;
            if (!courseId) return;

            // ----- Tambahkan overlay loading -----
            const overlay = document.createElement('div');
            overlay.id = 'tutor-single-loading-overlay';
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.background = 'rgba(255,255,255,0.8)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.zIndex = '9999';
            overlay.innerHTML = `
        <div style="padding:20px 30px; background:#fff; border-radius:8px; font-weight:bold; color:#ED2D56;">
            Memperbarui harga, mohon tunggu...
        </div>
    `;
            document.body.appendChild(overlay);

            try {
                const res = await fetch(`/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}&t=${Date.now()}`);
                const data = await res.json();

                if (!data.has_price) {
                    singlePricing.style.display = "none";
                    return;
                }

                singlePricing.innerHTML = '';

                const min = Number(data.min_price || 0);
                const max = Number(data.max_price || 0);

                const wrapper = document.createElement('div');
                wrapper.className = 'tpt-course-price-wrapper';
                wrapper.innerHTML = `
            <span class="tpt-course-total">Harga Per Topic:</span>
            <span class="tpt-course-price">${
                min === max
                    ? 'Rp ' + min.toLocaleString('id-ID')
                    : 'Rp ' + min.toLocaleString('id-ID') + ' – Rp ' + max.toLocaleString('id-ID')
            }</span>
        `;
                singlePricing.appendChild(wrapper);
            } catch (err) {
                console.warn('Gagal ambil harga topic di single course:', err);
            } finally {
                // Hapus overlay setelah selesai
                const overlayEl = document.getElementById('tutor-single-loading-overlay');
                if (overlayEl) overlayEl.remove();
            }
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const curriculum = document.querySelector('.tutor-accordion');
            if (!curriculum) return;

            const courseBtn = document.querySelector('[data-course-id]');
            const courseId = courseBtn ? courseBtn.dataset.courseId : null;
            if (!courseId) return;

            // ----- Tambahkan overlay loading -----
            const overlay = document.createElement('div');
            overlay.id = 'tutor-curriculum-loading-overlay';
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.background = 'rgba(255,255,255,0.8)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.zIndex = '9999';
            overlay.innerHTML = `
        <div style="padding:20px 30px; background:#fff; border-radius:8px; font-weight:bold; color:#ED2D56;">
            Memperbarui harga per topic, mohon tunggu...
        </div>
    `;
            document.body.appendChild(overlay);

            try {
                const res = await fetch(`/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}&t=${Date.now()}`);
                const data = await res.json();
                if (!data.has_price) return;

                const topicHeaders = document.querySelectorAll('.tutor-accordion-item-header');
                if (!topicHeaders.length) return;

                for (const header of topicHeaders) {
                    const topicTitleNode = Array.from(header.childNodes).find(n => n.nodeType === Node.TEXT_NODE);
                    const topicTitle = topicTitleNode ? topicTitleNode.textContent.trim() : header.textContent.trim();

                    const topicRes = await fetch(`/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}&topic_title=${encodeURIComponent(topicTitle)}&t=${Date.now()}`);
                    const topicData = await topicRes.json();

                    if (!topicData || !topicData.min_price) continue;

                    const badge = document.createElement('span');
                    badge.className = 'tpt-topic-badge';
                    badge.innerHTML = `💰 Rp ${Number(topicData.min_price).toLocaleString('id-ID')}`;

                    header.appendChild(badge);
                }
            } catch (err) {
                console.warn('Gagal ambil harga per topic:', err);
            } finally {
                // Hapus overlay setelah selesai
                const overlayEl = document.getElementById('tutor-curriculum-loading-overlay');
                if (overlayEl) overlayEl.remove();
            }
        });
    </script>


    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            // Cek apakah kita di halaman cart Tutor LMS
            const tutorCart = document.querySelector('.tutor-cart-course-list');
            if (!tutorCart) return;

            // ----- Tambahkan overlay loading -----
            const overlay = document.createElement('div');
            overlay.id = 'tutor-cart-loading-overlay';
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.background = 'rgba(255,255,255,0.8)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.zIndex = '9999';
            overlay.innerHTML = `
        <div style="padding:20px 30px; background:#fff; border-radius:8px; font-weight:bold; color:#ED2D56;">
            Memperbarui harga, mohon tunggu...
        </div>
    `;
            document.body.appendChild(overlay);

            const cartItems = tutorCart.querySelectorAll('.tutor-cart-course-item');

            for (const item of cartItems) {
                const link = item.querySelector('.tutor-cart-course-title a');
                const courseUrl = link ? link.href : null;
                if (!courseUrl) continue;

                // Ambil ID course dari tombol remove
                const removeBtn = item.querySelector('.tutor-cart-remove-button');
                const courseId = removeBtn ? removeBtn.dataset.courseId : null;
                if (!courseId) continue;

                try {
                    const res = await fetch(`/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}&t=${Date.now()}`);
                    const data = await res.json();
                    if (!data.has_price) continue;

                    // Gunakan harga topic pertama (min_price)
                    const price = Number(data.min_price || 0);
                    if (!price) continue;

                    // Ubah tampilan harga di cart
                    const priceWrap = item.querySelector('.tutor-cart-course-price');
                    if (priceWrap) {
                        priceWrap.innerHTML = `
                    <div class="tutor-fw-bold" style="color:#ED2D56;">
                        Rp ${price.toLocaleString('id-ID')}
                    </div>
                    <div class="tutor-cart-discount-price" style="display:none;"></div>
                `;
                    }
                } catch (err) {
                    console.warn("Gagal ambil harga topic di Tutor Cart:", err);
                }
            }

            // Update subtotal dan grand total di kanan
            setTimeout(() => {
                let total = 0;
                document.querySelectorAll('.tutor-cart-course-price .tutor-fw-bold').forEach(el => {
                    const val = parseInt(el.textContent.replace(/\D/g, '')) || 0;
                    total += val;
                });

                const subTotal = document.querySelector('.tutor-cart-summery-top .tutor-cart-summery-item div:last-child');
                const grandTotal = document.querySelector('.tutor-cart-summery-bottom .tutor-cart-summery-item div:last-child');

                if (subTotal) subTotal.textContent = `Rp ${total.toLocaleString('id-ID')}`;
                if (grandTotal) grandTotal.textContent = `Rp ${total.toLocaleString('id-ID')}`;

                // Hapus overlay setelah selesai update
                const overlayEl = document.getElementById('tutor-cart-loading-overlay');
                if (overlayEl) overlayEl.remove();
            }, 1000);
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            // ✅ Pastikan ini halaman checkout Tutor LMS
            const checkout = document.querySelector('.tutor-checkout-details');
            if (!checkout) return;

            // 🟡 Tambahkan overlay loading
            const overlay = document.createElement('div');
            overlay.id = 'checkout-loading-overlay';
            overlay.style.cssText = `
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(255,255,255,0.8);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #fff;
        font-weight: 600;
    `;
            overlay.textContent = 'Memuat harga per topic...';
            checkout.style.position = 'relative';
            checkout.appendChild(overlay);

            const items = checkout.querySelectorAll('.tutor-checkout-course-item');
            let total = 0;

            for (const item of items) {
                const courseId = item.dataset.courseId;
                if (!courseId) continue;

                try {
                    const res = await fetch(`/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}&t=${Date.now()}`);
                    const data = await res.json();
                    if (!data.has_price) continue;

                    const price = Number(data.min_price || 0);
                    if (!price) continue;
                    total += price;

                    // 🔁 Ganti tampilan harga di detail item
                    const priceWrap = item.querySelector('.tutor-text-right');
                    if (priceWrap) {
                        priceWrap.innerHTML = `
                    <div class="tutor-fw-bold" style="color:#ED2D56;">
                        Rp ${price.toLocaleString('id-ID')}
                    </div>
                    <div class="tutor-checkout-discount-price" style="display:none;"></div>
                `;
                    }
                } catch (err) {
                    console.warn("❌ Gagal ambil harga topic di checkout:", err);
                }
            }

            // 🧾 Update subtotal dan grand total di kanan
            setTimeout(() => {
                const subTotalEl = checkout.querySelector('.tutor-checkout-summary-item .tutor-fw-bold');
                const grandTotalEl = checkout.querySelector('.tutor-checkout-grand-total');

                if (subTotalEl) subTotalEl.textContent = `Rp ${total.toLocaleString('id-ID')}`;
                if (grandTotalEl) grandTotalEl.textContent = `Rp ${total.toLocaleString('id-ID')}`;

                // 🚫 Hapus elemen "Sale discount"
                const saleDiscount = checkout.querySelectorAll('.tutor-checkout-summary-item');
                saleDiscount.forEach(el => {
                    const label = el.querySelector('div');
                    if (label && label.textContent.trim().toLowerCase().includes('sale discount')) {
                        el.remove();
                    }
                });

                // ✅ Hapus overlay setelah semua selesai
                overlay.remove();
            }, 800);
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            // ✅ Cek apakah ada form checkout Tutor LMS
            const checkoutForm = document.querySelector('.tutor-checkout-details');
            if (!checkoutForm) return;

            // Ambil data user via WordPress
            <?php if (is_user_logged_in()) :
                $current_user = wp_get_current_user(); ?>
                const userData = {
                    first_name: "<?php echo esc_js($current_user->first_name); ?>",
                    last_name: "<?php echo esc_js($current_user->last_name); ?>",
                    email: "<?php echo esc_js($current_user->user_email); ?>"
                };
            <?php else: ?>
                const userData = null;
            <?php endif; ?>

            if (!userData) return;

            // Isi otomatis field billing
            const firstNameInput = document.querySelector('input[name="billing_first_name"]');
            const lastNameInput = document.querySelector('input[name="billing_last_name"]');
            const emailInput = document.querySelector('input[name="billing_email"]');

            if (firstNameInput) firstNameInput.value = userData.first_name || '';
            if (lastNameInput) lastNameInput.value = userData.last_name || '';
            if (emailInput) emailInput.value = userData.email || '';
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const checkoutForm = document.querySelector('.tutor-checkout-details');
            if (!checkoutForm) return;

            const checkoutDataInput = document.getElementById('checkout_data');
            if (!checkoutDataInput) return;

            let checkoutData = JSON.parse(checkoutDataInput.value);
            let total = 0;

            for (let i = 0; i < checkoutData.items.length; i++) {
                const item = checkoutData.items[i];
                const courseId = item.item_id;

                try {
                    const res = await fetch(`/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}&t=${Date.now()}`);
                    const data = await res.json();

                    if (!data.has_price) continue;

                    // Gunakan harga min_price per topic
                    const price = Number(data.min_price || 0);
                    item.sale_price = price;
                    item.display_price = price;
                    total += price;

                    // Update tampilan di checkout
                    const priceWrap = checkoutForm.querySelector(`.tutor-checkout-course-item[data-course-id="${courseId}"] .tutor-text-right`);
                    if (priceWrap) {
                        priceWrap.innerHTML = `
                    <div class="tutor-fw-bold" style="color:#ED2D56;">
                        Rp ${price.toLocaleString('id-ID')}
                    </div>
                    <div class="tutor-checkout-discount-price" style="display:none;"></div>
                `;
                    }

                } catch (err) {
                    console.warn("Gagal update harga topic di checkout:", err);
                }
            }

            // Update hidden input checkout_data
            checkoutData.subtotal_price = total;
            checkoutData.total_price = total;
            checkoutDataInput.value = JSON.stringify(checkoutData);

            // Update tampilan subtotal & grand total
            const subTotalEl = checkoutForm.querySelector('.tutor-checkout-summary-item .tutor-fw-bold');
            const grandTotalEl = checkoutForm.querySelector('.tutor-checkout-grand-total');
            if (subTotalEl) subTotalEl.textContent = `Rp ${total.toLocaleString('id-ID')}`;
            if (grandTotalEl) grandTotalEl.textContent = `Rp ${total.toLocaleString('id-ID')}`;
        });
    </script>



    <style>
        /* Styling harga baru */
        .tpt-course-price-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-size: 15px;
        }

        .tpt-course-total {
            color: #555;
            font-weight: 500;
        }

        .tpt-course-price {
            color: #ED2D56;
            font-weight: 700;
        }

        /* Sembunyikan harga default Tutor bawaan Acadia */
        .tutor-item-price,
        .tutor-course-price {
            display: none !important;
        }

        .single-course .tpt-course-price-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-size: 16px;
        }

        .single-course .tpt-course-total {
            color: #555;
            font-weight: 500;
        }

        .single-course .tpt-course-price {
            color: #ED2D56;
            font-weight: 700;
        }

        .tpt-topic-badge {
            display: inline-block;
            background: #ED2D56;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            padding: 2px 8px;
            margin-left: 8px;
            vertical-align: middle;
        }

        /* ==========================
   3️⃣ Samakan tombol eCourse di kategori dengan home
========================== */
        .tutor-course-booking-availability .list-item-button {
            background: var(--btn-gold-gradient, linear-gradient(90deg, #ED2D56 0%, #ff6584 100%)) !important;
            color: #fff !important;
            font-weight: 600 !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            border-radius: 50px !important;
            padding: 10px 20px !important;
            cursor: pointer !important;
            text-transform: capitalize !important;
            transition: all 0.3s ease-in-out !important;
            box-shadow: 0 4px 10px rgba(237, 45, 86, 0.3) !important;
            border: none !important;
        }

        .tutor-course-booking-availability .list-item-button:hover {
            background: #fff !important;
            color: #ED2D56 !important;
            border: 1.5px solid #ED2D56 !important;
            box-shadow: 0 6px 15px rgba(237, 45, 86, 0.4) !important;
        }

        /* Pastikan ikon dan teks rata tengah */
        .tutor-course-booking-availability .list-item-button .tutor-icon-cart-line {
            margin-right: 8px !important;
        }
    </style>
<?php
});













// Backup part ke 2
<?php

/**
 * Tutor LMS Paid Topic - Full Snippet Gabungan
 * Author: Puji Ermanto | Version: 2.0
 * Description: Menyimpan harga per topik ke DB & tampilkan di frontend secara rapi
 */

if (!defined('ABSPATH')) exit;

// =========================
// 1️⃣ Simpan Harga Per Topic
// =========================
add_action('tutor_after_topic_save', 'tpt_save_custom_topic_price', 10, 2);
function tpt_save_custom_topic_price($topic_id, $topic_data)
{
    if (isset($_POST['custom_topic_price'])) {
        $price = floatval($_POST['custom_topic_price']);
        update_post_meta($topic_id, '_custom_topic_price', $price);

        global $wpdb;
        $table = $wpdb->prefix . 'tutor_topic_price';
        $wpdb->replace(
            $table,
            [
                'course_id' => $topic_data['course_id'],
                'topic_title' => get_the_title($topic_id),
                'price' => $price,
                'created_at' => current_time('mysql'),
            ],
            ['course_id', 'topic_title']
        );
    }
}

// =========================
// 2️⃣ Hitung Harga Per Topic Saat Order
// =========================
add_action('tutor_after_order_create', 'tpt_save_topic_prices_to_order', 10, 2);
function tpt_save_topic_prices_to_order($order_id, $order_data)
{
    global $wpdb;

    $user_id = $order_data['user_id'];
    $course_id = $order_data['course_id'];
    $topics = get_post_meta($course_id, '_tutor_topics', true) ?: [];
    $total_price = 0;
    $topic_prices = [];
    $table = $wpdb->prefix . 'tutor_topic_price';

    foreach ($topics as $topic_id) {
        $price = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT price FROM $table WHERE course_id=%d AND topic_title=%s ORDER BY id ASC LIMIT 1",
            $course_id,
            get_the_title($topic_id)
        )));
        $topic_prices[$topic_id] = $price;
        $total_price += $price;
    }

    update_post_meta($order_id, '_topic_prices', $topic_prices);
    update_post_meta($order_id, '_tutor_order_total', $total_price);

    $orders_table = $wpdb->prefix . 'tutor_orders';
    $wpdb->update(
        $orders_table,
        ['subtotal_price' => $total_price, 'total_price' => $total_price],
        ['id' => $order_id]
    );

    wp_update_post(['ID' => $order_id, 'post_author' => $user_id]);
}

// =========================
// 3️⃣ Tampilkan Harga di Dashboard Student
// =========================
add_filter('tutor_order_item_price', 'tpt_display_custom_topic_prices', 10, 3);
function tpt_display_custom_topic_prices($price_html, $item_id, $order_id)
{
    $topic_prices = get_post_meta($order_id, '_topic_prices', true);
    if ($topic_prices && is_array($topic_prices)) {
        $price_html = '';
        foreach ($topic_prices as $topic_id => $price) {
            $price_html .= get_the_title($topic_id) . ': ' . tutor_price($price) . '<br>';
        }
    }
    return $price_html;
}

// =========================
// 4️⃣ REST API Endpoint untuk Frontend
// =========================
add_action('rest_api_init', function () {
    register_rest_route('tpt-card/v1', '/get-course-prices', [
        'methods' => 'GET',
        'callback' => function ($req) {
            global $wpdb;
            $table = $wpdb->prefix . 'tutor_topic_price';
            $course_id = intval($req['course_id'] ?? 0);
            $topic_title = sanitize_text_field($req['topic_title'] ?? '');
            if (!$course_id) return ['has_price' => false];

            if ($topic_title) {
                $price = $wpdb->get_var($wpdb->prepare(
                    "SELECT price FROM $table WHERE course_id=%d AND topic_title=%s",
                    $course_id,
                    $topic_title
                ));
                return ['has_price' => !empty($price), 'min_price' => intval($price)];
            }

            $topics = $wpdb->get_results($wpdb->prepare(
                "SELECT post_title FROM {$wpdb->posts} WHERE post_type='topics' AND post_parent=%d AND post_status='publish'",
                $course_id
            ));
            $prices = [];
            foreach ($topics as $topic) {
                $price = $wpdb->get_var($wpdb->prepare(
                    "SELECT price FROM $table WHERE topic_title=%s AND course_id=%d",
                    $topic->post_title,
                    $course_id
                ));
                if (!empty($price) && $price > 0) $prices[] = intval($price);
            }
            if (empty($prices)) return ['has_price' => false];

            return ['has_price' => true, 'total_price' => array_sum($prices), 'min_price' => min($prices), 'max_price' => max($prices)];
        },
        'permission_callback' => '__return_true'
    ]);
    register_rest_route('tpt-card/v1', '/get-order-total', [
        'methods' => 'GET',
        'callback' => function ($req) {
            $order_id = intval($req['order_id'] ?? 0);
            if (!$order_id) return ['total_price' => 0];
            $total = get_post_meta($order_id, '_tutor_order_total', true);
            return ['total_price' => floatval($total)];
        },
        'permission_callback' => '__return_true'
    ]);
});


// =========================
// 5️⃣ Inject JS & CSS Frontend
// =========================
add_action('wp_footer', function () { ?>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const fetchPrice = async (courseId, topicTitle = null) => {
                try {
                    const url = `/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}${topicTitle ? '&topic_title='+encodeURIComponent(topicTitle):''}&t=${Date.now()}`;
                    const res = await fetch(url);
                    return await res.json();
                } catch (e) {
                    console.warn("Gagal fetch harga", e);
                    return null;
                }
            };

            // ----- Course Card -----
            document.querySelectorAll('.tp-course-item').forEach(async card => {
                const btn = card.querySelector('[data-course-id]');
                const courseId = btn ? btn.dataset.courseId : null;
                if (!courseId) return;

                const data = await fetchPrice(courseId);
                const priceArea = card.querySelector('.tp-course-pricing, .tp-course-btn .tutor-course-price');
                if (!priceArea) return;

                if (!data || !data.has_price) {
                    priceArea.innerHTML = '';
                    return;
                }

                const min = Number(data.min_price || 0);
                const max = Number(data.max_price || 0);
                const wrapper = document.createElement('div');
                wrapper.className = 'tpt-course-price-wrapper';
                wrapper.innerHTML = min === max ?
                    `<span class="tpt-course-total">Harga Per Topic:</span><span class="tpt-course-price">Rp ${min.toLocaleString('id-ID')}</span>` :
                    `<span class="tpt-course-total">Harga Per Topic:</span><span class="tpt-course-price">Rp ${min.toLocaleString('id-ID')} – Rp ${max.toLocaleString('id-ID')}</span>`;
                priceArea.innerHTML = '';
                priceArea.appendChild(wrapper);
            });

            // ----- Single Course, Curriculum, Cart, Checkout -----
            const initSingleAndCart = async () => {
                const courseBtn = document.querySelector('[data-course-id]');
                if (!courseBtn) return;
                const courseId = courseBtn.dataset.courseId;

                // Overlay loading helper
                const createOverlay = (id, text) => {
                    const ov = document.createElement('div');
                    ov.id = id;
                    ov.style.position = 'fixed';
                    ov.style.top = '0';
                    ov.style.left = '0';
                    ov.style.width = '100%';
                    ov.style.height = '100%';
                    ov.style.background = 'rgba(255,255,255,0.8)';
                    ov.style.display = 'flex';
                    ov.style.alignItems = 'center';
                    ov.style.justifyContent = 'center';
                    ov.style.zIndex = '9999';
                    ov.innerHTML = `<div style="padding:20px 30px; background:#fff; border-radius:8px; font-weight:bold; color:#ED2D56;">${text}</div>`;
                    document.body.appendChild(ov);
                    return ov;
                };

                // Single Course Sidebar
                const singlePricing = document.querySelector('.tutor-course-sidebar-card-pricing');
                if (singlePricing) {
                    const overlay = createOverlay('tpt-single-overlay', 'Memperbarui harga, mohon tunggu...');
                    const data = await fetchPrice(courseId);
                    if (data && data.has_price) {
                        const min = Number(data.min_price || 0),
                            max = Number(data.max_price || 0);
                        const wrapper = document.createElement('div');
                        wrapper.className = 'tpt-course-price-wrapper';
                        wrapper.innerHTML = min === max ?
                            `<span class="tpt-course-total">Harga Per Topic:</span><span class="tpt-course-price">Rp ${min.toLocaleString('id-ID')}</span>` :
                            `<span class="tpt-course-total">Harga Per Topic:</span><span class="tpt-course-price">Rp ${min.toLocaleString('id-ID')} – Rp ${max.toLocaleString('id-ID')}</span>`;
                        singlePricing.innerHTML = '';
                        singlePricing.appendChild(wrapper);
                    }
                    overlay.remove();
                }

                // Curriculum
                const curriculum = document.querySelector('.tutor-accordion');
                if (curriculum) {
                    const overlay = createOverlay('tpt-curriculum-overlay', 'Memperbarui harga per topic, mohon tunggu...');
                    const topicHeaders = document.querySelectorAll('.tutor-accordion-item-header');
                    for (const header of topicHeaders) {
                        const topicTitleNode = Array.from(header.childNodes).find(n => n.nodeType === Node.TEXT_NODE);
                        const topicTitle = topicTitleNode ? topicTitleNode.textContent.trim() : header.textContent.trim();
                        const topicData = await fetchPrice(courseId, topicTitle);
                        if (topicData && topicData.min_price) {
                            const badge = document.createElement('span');
                            badge.className = 'tpt-topic-badge';
                            badge.innerHTML = `💰 Rp ${Number(topicData.min_price).toLocaleString('id-ID')}`;
                            header.appendChild(badge);
                        }
                    }
                    overlay.remove();
                }

                // Cart
                const tutorCart = document.querySelector('.tutor-cart-course-list');
                if (tutorCart) {
                    const overlay = createOverlay('tpt-cart-overlay', 'Memperbarui harga, mohon tunggu...');
                    const cartItems = tutorCart.querySelectorAll('.tutor-cart-course-item');
                    let total = 0;
                    for (const item of cartItems) {
                        const removeBtn = item.querySelector('.tutor-cart-remove-button');
                        const cId = removeBtn ? removeBtn.dataset.courseId : null;
                        if (!cId) continue;
                        const data = await fetchPrice(cId);
                        if (!data || !data.has_price) continue;
                        const price = Number(data.min_price || 0);
                        total += price;
                        const priceWrap = item.querySelector('.tutor-cart-course-price');
                        if (priceWrap) priceWrap.innerHTML = `<div class="tutor-fw-bold" style="color:#ED2D56;">Rp ${price.toLocaleString('id-ID')}</div><div class="tutor-cart-discount-price" style="display:none;"></div>`;
                    }
                    // update subtotal & grand total
                    const subTotal = document.querySelector('.tutor-cart-summery-top .tutor-cart-summery-item div:last-child');
                    const grandTotal = document.querySelector('.tutor-cart-summery-bottom .tutor-cart-summery-item div:last-child');
                    if (subTotal) subTotal.textContent = `Rp ${total.toLocaleString('id-ID')}`;
                    if (grandTotal) grandTotal.textContent = `Rp ${total.toLocaleString('id-ID')}`;
                    overlay.remove();
                }

                // Checkout
                const checkoutForm = document.querySelector('.tutor-checkout-details');
                if (checkoutForm) {
                    const overlay = createOverlay('tpt-checkout-overlay', 'Memuat harga per topic...');

                    const items = checkoutForm.querySelectorAll('.tutor-checkout-course-item');
                    let total = 0;

                    for (const item of items) {
                        const courseId = item.dataset.courseId;
                        if (!courseId) continue;
                        try {
                            const data = await fetchPrice(courseId);
                            if (!data || !data.has_price) continue;
                            const price = Number(data.min_price || 0);
                            total += price;

                            // Update harga per item realtime
                            const priceWrap = item.querySelector('.tutor-text-right');
                            if (priceWrap) {
                                priceWrap.innerHTML = `<div class="tutor-fw-bold" style="color:#ED2D56;">Rp ${price.toLocaleString('id-ID')}</div><div class="tutor-checkout-discount-price" style="display:none;"></div>`;
                            }
                        } catch (e) {
                            console.warn("Gagal fetch harga topic:", e);
                        }
                    }

                    // Update subtotal & grand total
                    const subTotalEl = checkoutForm.querySelector('.tutor-checkout-summary-item .tutor-fw-bold');
                    const grandTotalEl = checkoutForm.querySelector('.tutor-checkout-grand-total');
                    if (subTotalEl) subTotalEl.textContent = `Rp ${total.toLocaleString('id-ID')}`;
                    if (grandTotalEl) grandTotalEl.textContent = `Rp ${total.toLocaleString('id-ID')}`;

                    // Hapus Sale Discount
                    const saleDiscount = checkoutForm.querySelectorAll('.tutor-checkout-summary-item');
                    saleDiscount.forEach(el => {
                        const label = el.querySelector('div');
                        if (label && label.textContent.trim().toLowerCase().includes('sale discount')) el.remove();
                    });

                    overlay.remove();
                }

            };
            initSingleAndCart();
        });
    </script>

    <style>
        .tpt-course-price-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-size: 15px;
        }

        .tpt-course-total {
            color: #555;
            font-weight: 500;
        }

        .tpt-course-price {
            color: #ED2D56;
            font-weight: 700;
        }

        .tpt-topic-badge {
            display: inline-block;
            background: #ED2D56;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            padding: 2px 8px;
            margin-left: 8px;
            vertical-align: middle;
        }

        .tutor-item-price,
        .tutor-course-price {
            display: none !important;
        }
    </style>
<?php
});


// Backup terbaru 10 Desember 2025
<?php

/**
 * Add Custom Post Type "Kontak Informasi" inside Tutor LMS menu
 * 
 * @author Puji Ermanto <pujiermanto@gmail.com> | AKA Jhony Rotten
 * @version 1.1
 * @description Customisasi dashboard admin.
 */

function newsletter_signup_form_shortcode()
{
    return '
    <form action="https://your-newsletter-service.com/subscribe" method="post" target="_blank" novalidate>
      <label for="email" style="display:block; margin-bottom: 8px;">Subscribe to our newsletter:</label>
      <input type="email" id="email" name="email" placeholder="Your email address" required style="padding: 8px; width: 250px; max-width: 100%;">
      <button type="submit" style="padding: 8px 16px; background-color: #ED2D56; color: white; border: none; cursor: pointer; margin-left: 8px;">Subscribe</button>
    </form>';
}
add_shortcode('newsletter_signup', 'newsletter_signup_form_shortcode');

function register_custom_menu_location()
{
    register_nav_menu('bottom-menu', 'Bottom Navbar Menu');
}
add_action('after_setup_theme', 'register_custom_menu_location');

function custom_admin_dashboard_text()
{
    global $wp_version;

    // Ganti teks "Welcome to WordPress!" menjadi sesuai keinginan Anda
    $welcome_text = 'HASHIWA JAPANESE ACADEMY';

    // Ganti teks "Learn more about the 6.5.5 version." sesuai keinginan Anda
    $version_text = 'Bridge Beyond Border';

    // Mengganti teks menggunakan filter
    add_filter('gettext', function ($translated_text, $text, $domain) use ($welcome_text, $version_text, $wp_version) {
        if ($text === 'Welcome to WordPress!') {
            $translated_text = $welcome_text;
        }
        if ($text === 'Learn more about the %s version.') {
            $translated_text = sprintf($version_text, $wp_version);
        }
        return $translated_text;
    }, 10, 3);
}
add_action('admin_init', 'custom_admin_dashboard_text');

function move_menus_to_top()
{
    global $menu;

    $snippets_key = null;
    foreach ($menu as $key => $menu_item) {
        if ($menu_item[2] === 'snippets' && $menu_item[2] === 'music_review' && $menu_item[2] === 'film_review') {
            $snippets_key = $key;
        }
    }

    $new_menu = [];
    if ($snippets_key !== null) {
        $new_menu[] = $menu[$snippets_key];
        unset($menu[$snippets_key]);
    }

    $menu = array_merge($new_menu, $menu);
}
add_action('admin_menu', 'move_menus_to_top', 9);

function replace_admin_menu_icons()
{
    $base_url = esc_url(home_url());
    $icon_path = '/wp-content/uploads/2025/11/fav-1-1-2.webp'; // path relatif ke root
?>
    <style>
        /* Hapus dashicon bawaan */
        #toplevel_page_snippets .wp-menu-image.dashicons-before::before {
            content: none !important;
        }

        /* Ganti dengan ikon custom */
        #toplevel_page_snippets .wp-menu-image {
            background-image: url('<?php echo $base_url . $icon_path; ?>') !important;
            background-size: 20px 20px !important;
            background-repeat: no-repeat !important;
            background-position: center center !important;
            width: 30px !important;
            height: 30px !important;
        }

        /* Sembunyikan tag <img> kalau ada */
        #toplevel_page_snippets .wp-menu-image img {
            display: none !important;
        }
    </style>
    <?php
}
add_action('admin_head', 'replace_admin_menu_icons');

function enqueue_sweetalert_admin()
{
    wp_enqueue_script('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
}
add_action('admin_enqueue_scripts', 'enqueue_sweetalert_admin');
/**
 * Proteksi halaman Code Snippets dengan password + SweetAlert2
 */
add_action('admin_init', 'restrict_snippets_access_by_password');
function restrict_snippets_access_by_password()
{
    $allowed_password = '123';

    // Halaman Code Snippets yang dibatasi
    $restricted_pages = [
        'snippets',
        'edit-snippet',
        'add-snippet',
        'import-code-snippets',
        'snippets-settings',
        'code-snippets-welcome',
        'code_snippets_upgrade',
    ];

    // Cek jika sedang di halaman yang dibatasi
    if (is_admin() && isset($_GET['page']) && in_array($_GET['page'], $restricted_pages, true)) {

        // Muat SweetAlert2
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script(
                'sweetalert2',
                'https://cdn.jsdelivr.net/npm/sweetalert2@11',
                [],
                null,
                true
            );
        });

        $password = isset($_GET['password']) ? sanitize_text_field($_GET['password']) : '';

        if ($password !== $allowed_password) {
            add_action('admin_footer', function () use ($password) {
                $wrong_pw = $password !== '';
    ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {

                        <?php if ($wrong_pw) : ?>
                            Swal.fire({
                                icon: 'error',
                                title: 'Password Salah!',
                                text: 'Silakan coba lagi.',
                                confirmButtonText: 'Ulangi'
                            }).then(() => {
                                window.location.href = '<?php echo admin_url(); ?>';
                            });
                        <?php else : ?>
                            Swal.fire({
                                title: 'Masukkan Password',
                                html: `
									<div style="position:relative;margin-bottom:6px;">
										<input id="swal-input-password" type="password" class="swal2-input" placeholder="Password">
										<button type="button" id="toggle-password" style="position:absolute;top:8px;right:8px;background:transparent;border:none;cursor:pointer;">
											👁️
										</button>
									</div>
									<small id="password-hint" style="display:block;font-size:13px;color:#888;margin-top:-8px;">
										Hint: 3 digit angka favoritmu 😉
									</small>
								`,
                                focusConfirm: false,
                                showCancelButton: true,
                                confirmButtonText: 'Submit',
                                cancelButtonText: 'Batal',
                                preConfirm: () => {
                                    const pw = document.getElementById('swal-input-password').value;
                                    if (!pw) {
                                        Swal.showValidationMessage('Password tidak boleh kosong');
                                        return false;
                                    }
                                    return pw;
                                },
                                didOpen: () => {
                                    const btn = document.getElementById('toggle-password');
                                    const input = document.getElementById('swal-input-password');
                                    btn.addEventListener('click', () => {
                                        const type = input.type === 'password' ? 'text' : 'password';
                                        input.type = type;
                                        btn.textContent = type === 'password' ? '👁️' : '🙈';
                                    });
                                }
                            }).then((result) => {
                                if (result.isConfirmed && result.value) {
                                    const baseURL = window.location.href.split('&password=')[0];
                                    window.location.href = baseURL + '&password=' + encodeURIComponent(result.value);
                                } else {
                                    window.location.href = '<?php echo admin_url(); ?>';
                                }
                            });
                        <?php endif; ?>

                    });
                </script>
        <?php
            });
        }
    }
}


// 🔧 Ganti nama menu utama Tutor LMS di sidebar admin
add_action('admin_menu', function () {
    global $menu;
    foreach ($menu as $key => $item) {
        if (isset($item[2]) && $item[2] === 'tutor') {
            $menu[$key][0] = 'Hashiwa LMS'; // ubah label menu
            break;
        }
    }
}, 999);

// 🔧 Sembunyikan kolom Price di halaman admin Tutor LMS menggunakan CSS
add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'tutor_page_tutor') {
        echo '<style>
            /* Sembunyikan header kolom Price */
            .tutor-table thead th:nth-child(4) {
                display: none !important;
            }

            /* Sembunyikan kolom data Price */
            .tutor-table tbody td:nth-child(4),
            .tutor-table .list-item-price,
            .tutor-table td .tutor-item-price,
            .tutor-table td div.list-item-price span {
                display: none !important;
            }

            /* Atur ulang lebar kolom biar gak bolong */
            .tutor-table thead th,
            .tutor-table tbody td {
                width: auto !important;
            }
        </style>';
    }
});

/**
 * Tutor LMS: Force enable delete/trash order (bulk action)
 */

// 1️⃣ Tambahkan opsi Trash di bulk action Tutor LMS
add_filter('tutor_order_bulk_actions', function ($actions) {
    if (!isset($actions['trash'])) {
        $actions['trash'] = __('Trash', 'tutor');
    }
    return $actions;
});

// 2️⃣ Tangkap bulk action Trash Tutor LMS
add_action('tutor_orders_bulk_action_trash', function ($order_ids) {
    if (is_array($order_ids) && count($order_ids) > 0) {
        foreach ($order_ids as $order_id) {
            wp_trash_post(intval($order_id));
        }
    }
    wp_safe_redirect(admin_url('admin.php?page=tutor_orders'));
    exit;
});

// 3️⃣ Inject checkbox array yang benar di table
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'tutor_page_tutor_orders') : ?>
        <script>
            jQuery(document).ready(function($) {
                // Loop tiap row table
                $('.tutor-table tbody tr').each(function() {
                    var $row = $(this);
                    var link = $row.find('a[href*="action=edit&id="]').attr('href');
                    if (link) {
                        var id = link.match(/id=(\d+)/)[1];
                        var $chk = $row.find('td:first input[type="checkbox"]');
                        if ($chk.length === 0) {
                            $row.find('td:first').prepend('<input type="checkbox" class="tutor-form-check-input">');
                            $chk = $row.find('td:first input[type="checkbox"]');
                        }
                        $chk.attr({
                            'name': 'tutor-bulk-checkbox[]',
                            'value': id
                        });
                    }
                });

                // Checkbox "Select All"
                $('#tutor-bulk-checkbox-all').on('change', function() {
                    var checked = $(this).is(':checked');
                    $('input[name="tutor-bulk-checkbox[]"]').prop('checked', checked);
                });
            });
        </script>
<?php
    endif;
});
