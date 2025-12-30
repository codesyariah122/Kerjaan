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
            overlay.style.background = 'rgba(255,255,255,0.5)';
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
        document.addEventListener("DOMContentLoaded", () => {
            let isRendering = false;
            let lastRender = 0;

            const renderTopicBadges = async () => {
                if (isRendering) return; // cegah dobel render
                isRendering = true;

                const curriculum = document.querySelector('.tutor-accordion');
                if (!curriculum) {
                    isRendering = false;
                    return;
                }

                const courseBtn = document.querySelector('[data-course-id]');
                const courseId = courseBtn ? courseBtn.dataset.courseId : null;
                if (!courseId) {
                    isRendering = false;
                    return;
                }

                // Tampilkan overlay hanya sekali
                let overlay = document.getElementById('tutor-curriculum-loading-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = 'tutor-curriculum-loading-overlay';
                    Object.assign(overlay.style, {
                        position: 'fixed',
                        top: '0',
                        left: '0',
                        width: '100%',
                        height: '100%',
                        background: 'rgba(255,255,255,0.5)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        zIndex: '9999'
                    });
                    overlay.innerHTML = `
                <div style="padding:20px 30px; background:#fff; border-radius:8px; font-weight:bold; color:#ED2D56;">
                    Memperbarui harga per topic, mohon tunggu...
                </div>`;
                    document.body.appendChild(overlay);
                }

                try {
                    const res = await fetch(`/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}&t=${Date.now()}`);
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    if (!data.has_price) return;

                    const topicHeaders = document.querySelectorAll('.tutor-accordion-item-header');
                    if (!topicHeaders.length) return;

                    for (const header of topicHeaders) {
                        const oldBadge = header.querySelector('.tpt-topic-badge');
                        if (oldBadge) oldBadge.remove();

                        const topicTitleNode = Array.from(header.childNodes).find(n => n.nodeType === Node.TEXT_NODE);
                        const topicTitle = topicTitleNode ? topicTitleNode.textContent.trim() : header.textContent.trim();

                        try {
                            const topicRes = await fetch(`/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}&topic_title=${encodeURIComponent(topicTitle)}&t=${Date.now()}`);
                            if (!topicRes.ok) continue;
                            const topicData = await topicRes.json();

                            if (!topicData || !topicData.min_price) continue;

                            const badge = document.createElement('span');
                            badge.className = 'tpt-topic-badge';
                            badge.innerHTML = `💰 Rp ${Number(topicData.min_price).toLocaleString('id-ID')}`;
                            header.appendChild(badge);
                        } catch (e) {
                            console.warn('Gagal ambil harga topic:', e);
                        }
                    }
                } catch (err) {
                    console.error('❌ REST API error saat ambil harga per topic:', err);
                } finally {
                    const overlayEl = document.getElementById('tutor-curriculum-loading-overlay');
                    if (overlayEl) overlayEl.remove();
                    isRendering = false;
                    lastRender = Date.now();
                }
            };

            // Jalankan awal (dengan delay 1.2s untuk aman)
            setTimeout(renderTopicBadges, 1200);

            // 🔁 Observer tapi throttle: maksimal 1x tiap 5 detik
            const observer = new MutationObserver(() => {
                const now = Date.now();
                if (now - lastRender < 5000) return; // throttle 5 detik
                renderTopicBadges();
            });

            observer.observe(document.querySelector('.tutor-accordion') || document.body, {
                childList: true,
                subtree: true
            });

            // 🔄 Jalankan ulang setelah login sukses via popup Tutor
            document.addEventListener('tutor_user_login_success', () => {
                console.log('✅ Tutor login success — rerender badge per topic');
                renderTopicBadges();
            });
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
            overlay.style.background = 'rgba(255,255,255,0.5)';
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
        background: rgba(255,255,255,0.5);
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const payBtn = document.getElementById('tutor-checkout-pay-now-button');

            if (!payBtn) return;

            // ambil hidden input checkout_data (sesuaikan id/name kalau beda)
            const checkoutDataInput = document.getElementById('checkout_data') || document.querySelector('input[name="checkout_data"]');

            if (!checkoutDataInput) {
                console.warn('[TPT] checkout_data input tidak ditemukan');
                return;
            }

            async function recalcCheckoutData() {
                let checkoutData;
                try {
                    checkoutData = JSON.parse(checkoutDataInput.value);
                } catch (e) {
                    console.warn('[TPT] checkout_data JSON parse error', e);
                    return null;
                }

                let total = 0;

                // update setiap item berdasarkan course_id/item_id (sama logic yg sudah kamu pakai)
                for (let i = 0; i < checkoutData.items.length; i++) {
                    const item = checkoutData.items[i];
                    const courseId = item.item_id || item.course_id || item.product_id;
                    if (!courseId) continue;

                    try {
                        const res = await fetch(`/wp-json/tpt-card/v1/get-course-prices?course_id=${courseId}&t=${Date.now()}`);
                        const data = await res.json();
                        if (!data || !data.has_price) {
                            // jangan ubah jika tidak ada harga custom
                            continue;
                        }

                        // ambil min_price sebagai harga per item (sama seperti yg kamu pakai)
                        const price = Number(data.min_price || 0);

                        // pastikan struktur yg Tutor simpan: regular_price, sale_price, display_price, sale_price mungkin string/float
                        item.regular_price = price;
                        item.sale_price = price;
                        item.display_price = price;

                        total += price;
                    } catch (err) {
                        console.warn('[TPT] gagal ambil harga untuk course', courseId, err);
                    }
                }

                // perbarui subtotal / total fields (struktur bisa beda tergantung versi Tutor)
                checkoutData.subtotal_price = total;
                checkoutData.total_price = total;
                // if Tutor expects prices in decimals with 2 places:
                // checkoutData.subtotal_price = parseFloat(total.toFixed(2));

                // tulis kembali ke hidden input
                checkoutDataInput.value = JSON.stringify(checkoutData);
                return checkoutData;
            }

            // override click to ensure recalc selesai sebelum submit
            payBtn.addEventListener('click', function(e) {
                // cegah submit default sementara
                e.preventDefault();
                e.stopPropagation();

                (async () => {
                    await recalcCheckoutData();

                    // biarkan proses submit bawaan Tutor berjalan:
                    // 1) jika tombol ada di form, simulasikan klik lagi tanpa preventDefault
                    // 2) jika Tutor mengikat event lain, gunakan setTimeout kecil supaya event bawaan dieksekusi setelah update
                    setTimeout(() => {
                        // hapus handler sementara supaya tidak loop
                        payBtn.removeEventListener('click', arguments.callee);
                        // trigger click asli lagi
                        payBtn.click();
                    }, 80); // 50-150ms biasanya cukup; kalau masih race, naikkan ke 200ms
                })();
            }, {
                once: false
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            console.log("🔍 Memantau status login Tutor...");

            const redirectURL = '/cart-2'; // arahkan ke cart
            const checkoutURL = '/?post_type=page&p=19'; // ganti kalau checkout-mu beda
            const pathname = window.location.pathname + window.location.search;

            // 🧠 Jangan jalankan di halaman cart atau checkout
            if (
                pathname.includes('/cart-2') ||
                pathname.includes('post_type=page&p=19') ||
                pathname.includes('/checkout')
            ) {
                console.log("🛑 Sudah di cart / checkout — watcher berhenti.");
                return;
            }

            // Cegah redirect berulang
            if (sessionStorage.getItem('tpt_redirected') === '1') {
                console.log("🟡 Redirect sudah pernah dilakukan — skip.");
                return;
            }

            // 🔎 Deteksi login Tutor popup (event internal)
            document.addEventListener('tutor_user_login_success', () => {
                console.log('✅ Login sukses via popup Tutor — redirect sekali ke cart...');
                sessionStorage.setItem('tpt_redirected', '1');
                window.location.href = redirectURL;
            });

            // 🔁 Cek kalau sudah login saat page load pertama (misal reload sesudah login)
            if (document.body.classList.contains('logged-in')) {
                console.log("🟢 User sudah login (bukan dari popup) — tidak perlu redirect otomatis.");
            }

            // 🔄 Optional: reset flag saat logout (kalau theme punya tombol logout custom)
            document.addEventListener('user_logged_out', () => {
                sessionStorage.removeItem('tpt_redirected');
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.tutor-video-player .tutorPlayer').forEach(video => {
                const source = video.querySelector('source');
                if (!source) return;

                let src = source.src;
                if (!src) return;

                // jika format watch?v=, ubah jadi embed
                const ytMatch = src.match(/youtube\.com\/watch\?v=([^\&\?]+)/);
                if (ytMatch && ytMatch[1]) {
                    src = `https://www.youtube.com/embed/${ytMatch[1]}`;
                }

                // cek sudah embed atau tidak
                if (!src.includes('youtube.com/embed')) return;

                const iframe = document.createElement('iframe');
                iframe.src = src;
                iframe.allow = "autoplay; fullscreen";
                iframe.allowFullscreen = true;
                iframe.style.border = '0';
                iframe.style.width = '100%';

                // Hitung tinggi proporsional 16:9 berdasarkan width parent
                const parentWidth = video.parentNode.offsetWidth;
                iframe.style.height = `${parentWidth * 9 / 16}px`;

                // Replace video dengan iframe
                video.parentNode.replaceChild(iframe, video);

                // Optional: update height saat resize window
                window.addEventListener('resize', () => {
                    const newWidth = iframe.parentNode.offsetWidth;
                    iframe.style.height = `${newWidth * 9 / 16}px`;
                });
            });
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

        /* Buat wrapper responsive 16:9 (fallback jika JS gagal) */
        .tutor-video-player {
            width: 100%;
            max-width: 900px;
            /* optional */
            margin: 2rem auto;
            position: relative;
        }

        .tutor-video-player iframe {
            display: block;
            width: 100%;
            height: auto;
            /* height JS akan override ini */
        }
    </style>
<?php
});
