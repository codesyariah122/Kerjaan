<?php

add_filter('tutor_course_card_price_html', function ($price_html, $course_id) {
    global $wpdb;
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT ID, post_title, pm.meta_value AS price
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_tpt_price'
        WHERE p.post_parent = %d AND p.post_type IN ('topics','topic')
        ORDER BY p.menu_order ASC
    ", $course_id));
    if (!$topics) return $price_html;

    $prices = array_map('intval', wp_list_pluck($topics, 'price'));
    $min = min($prices);
    $max = max($prices);

    return ($min === $max)
        ? 'Rp ' . number_format($min, 0, ',', '.')
        : 'Rp ' . number_format($min, 0, ',', '.') . ' – Rp ' . number_format($max, 0, ',', '.');
}, 10, 2);


// ======================================================
// 🔹 FRONTEND: Lock Indicator + Badge + Buy Button
// ======================================================
add_action('wp_footer', function () {
    if (!is_singular(['courses', 'tutor_course', 'lesson'])) return;

    $user_id   = get_current_user_id();
    $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
    $purchased = get_user_meta($user_id, '_tpt_purchased_topics', true) ?: [];

    global $wpdb;

    // 🔹 Cari course ID aktif
    $course_id = get_post_meta(get_the_ID(), '_tutor_course_id', true);
    if (!$course_id) {
        $parent = wp_get_post_parent_id(get_the_ID());
        if ($parent) $course_id = wp_get_post_parent_id($parent);
    }
    if (!$course_id) return;

    // 🔹 Ambil semua topic + harga dari WooCommerce product link
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT t.ID, t.post_title, p2.meta_value AS wc_id, p3.meta_value AS price
        FROM {$wpdb->posts} t
        LEFT JOIN {$wpdb->postmeta} p2 ON p2.post_id = t.ID AND p2.meta_key = '_tpt_wc_id'
        LEFT JOIN {$wpdb->postmeta} p3 ON p3.post_id = p2.meta_value AND p3.meta_key = '_price'
        WHERE t.post_parent = %d
          AND t.post_type IN ('topics','topic')
          AND t.post_status='publish'
        ORDER BY t.menu_order ASC
    ", $course_id));

    if (!$topics) return;
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const completed = <?php echo json_encode($completed); ?>;
            const purchased = <?php echo json_encode($purchased); ?>;
            const topics = <?php echo json_encode($topics); ?>;

            document.querySelectorAll('.tutor-course-topic').forEach((el, index, all) => {
                const match = el.className.match(/tutor-course-topic-(\d+)/);
                const topicId = match ? parseInt(match[1]) : 0;
                if (!topicId) return;

                const tdata = topics.find(t => parseInt(t.ID) === topicId);
                if (!tdata) return;

                const price = parseInt(tdata.price || 0);
                const wcId = parseInt(tdata.wc_id || 0);
                const prev = index > 0 ? all[index - 1] : null;
                const prevId = prev ? parseInt(prev.className.match(/tutor-course-topic-(\d+)/)?.[1] || 0) : 0;

                const isFirst = index === 0;
                // const unlocked = isFirst || (completed.includes(prevId) && purchased.includes(topicId));
                const unlocked = isFirst || completed.includes(prevId);

                const header = el.querySelector('.tutor-accordion-item-header');
                const body = el.querySelector('.tutor-accordion-item-body');
                if (!header) return;

                // 🔒 Locked state
                if (!unlocked) {
                    header.style.opacity = '0.6';
                    header.style.pointerEvents = 'none';

                    const badge = document.createElement('span');
                    badge.innerHTML = `<i class="fa fa-lock"></i> Locked · Rp ${price ? price.toLocaleString('id-ID') : '0'}`;
                    badge.style.cssText = `
                background:#FFE4E9;
                color:#ED2D56;
                padding:4px 10px;
                border-radius:8px;
                font-size:12px;
                font-weight:600;
                margin-left:8px;
                display:inline-flex;
                align-items:center;
                gap:6px;
            `;
                    header.querySelector('.tutor-course-topic-title')?.appendChild(badge);

                    // 🔘 Tombol Buy Topic
                    // const canBuy = completed.includes(prevId) || isFirst;
                    const canBuy = isFirst || completed.includes(prevId);
                    const btn = document.createElement('button');
                    btn.textContent = canBuy ? "Buy Topic" : "Selesaikan Bab Sebelumnya";
                    btn.disabled = !canBuy;
                    btn.style.cssText = `
                display:block;
                margin:10px auto;
                padding:6px 14px;
                border:none;
                border-radius:8px;
                font-weight:600;
                cursor:${canBuy ? 'pointer' : 'not-allowed'};
                background:${canBuy ? '#ED2D56' : '#ccc'};
                color:white;
            `;

                    if (canBuy && wcId) {
                        btn.addEventListener('click', () => {
                            btn.textContent = "Loading...";
                            jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                                action: "tpt_add_to_cart",
                                product_id: wcId
                            }).done(() => {
                                window.location.href = "<?php echo wc_get_cart_url(); ?>";
                            }).fail(() => {
                                btn.textContent = "Buy Topic";
                            });
                        });
                    }

                    body?.appendChild(btn);
                }
            });
        });
    </script>
<?php
});



// ====================================================== Backup patch terbaru 28 Nov 2025 ======================================================
add_filter('tutor_course_card_price_html', function ($price_html, $course_id) {
    global $wpdb;
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT ID, post_title, pm.meta_value AS price
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_tpt_price'
        WHERE p.post_parent = %d AND p.post_type IN ('topics','topic')
        ORDER BY p.menu_order ASC
    ", $course_id));
    if (!$topics) return $price_html;

    $prices = array_map('intval', wp_list_pluck($topics, 'price'));
    $min = min($prices);
    $max = max($prices);

    return ($min === $max)
        ? 'Rp ' . number_format($min, 0, ',', '.')
        : 'Rp ' . number_format($min, 0, ',', '.') . ' – Rp ' . number_format($max, 0, ',', '.');
}, 10, 2);


// ======================================================
// 🔹 FRONTEND: Lock Indicator + Badge + Buy Button
// ======================================================
add_action('wp_footer', function () {
    if (!is_singular(['courses', 'tutor_course', 'lesson'])) return;

    $user_id   = get_current_user_id();
    $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
    $purchased = get_user_meta($user_id, '_tpt_purchased_topics', true) ?: [];

    global $wpdb;

    // 🔹 Cari course ID aktif
    $course_id = get_post_meta(get_the_ID(), '_tutor_course_id', true);
    if (!$course_id) {
        $parent = wp_get_post_parent_id(get_the_ID());
        if ($parent) $course_id = wp_get_post_parent_id($parent);
    }
    if (!$course_id) return;

    // 🔹 Ambil semua topic + harga dari WooCommerce product link
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT t.ID, t.post_title, p2.meta_value AS wc_id, p3.meta_value AS price
        FROM {$wpdb->posts} t
        LEFT JOIN {$wpdb->postmeta} p2 ON p2.post_id = t.ID AND p2.meta_key = '_tpt_wc_id'
        LEFT JOIN {$wpdb->postmeta} p3 ON p3.post_id = p2.meta_value AND p3.meta_key = '_price'
        WHERE t.post_parent = %d
          AND t.post_type IN ('topics','topic')
          AND t.post_status='publish'
        ORDER BY t.menu_order ASC
    ", $course_id));

    if (!$topics) return;
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const completed = <?php echo json_encode($completed); ?>;
            const purchased = <?php echo json_encode($purchased); ?>;
            const topics = <?php echo json_encode($topics); ?>;

            function renderBuyButtons() {
                console.log("🧩 Rendering Buy Buttons...");
                document.querySelectorAll('.tutor-course-topic').forEach((el, index, all) => {
                    if (el.dataset.buyRendered) return; // supaya gak double render
                    el.dataset.buyRendered = "1";

                    const match = el.className.match(/tutor-course-topic-(\d+)/);
                    const topicId = match ? parseInt(match[1]) : 0;
                    if (!topicId) return;

                    const tdata = topics.find(t => parseInt(t.ID) === topicId);
                    if (!tdata) return;

                    const price = parseInt(tdata.price || 0);
                    const wcId = parseInt(tdata.wc_id || 0);
                    const prev = index > 0 ? all[index - 1] : null;
                    const prevId = prev ? parseInt(prev.className.match(/tutor-course-topic-(\d+)/)?.[1] || 0) : 0;
                    const isFirst = index === 0;

                    const unlocked = isFirst || (completed.includes(prevId) && purchased.includes(topicId));

                    const header = el.querySelector('.tutor-accordion-item-header');
                    const title = header?.querySelector('.tutor-course-topic-title');
                    const headerRow = header?.querySelector('.tutor-row');
                    if (!header || !title || !headerRow) return;

                    // Jika topic terkunci
                    if (!unlocked) {
                        header.style.opacity = "0.6";
                        header.style.pointerEvents = "none";
                        header.style.position = "relative";

                        // 🔒 Badge Locked
                        const badge = document.createElement('span');
                        badge.innerHTML = `<i class="fa fa-lock"></i> Locked · Rp ${price ? price.toLocaleString('id-ID') : '0'}`;
                        badge.className = 'tpt-lock-badge';
                        badge.style.cssText = `
                background:#FFE4E9;
                color:#ED2D56;
                padding:4px 10px;
                border-radius:8px;
                font-size:12px;
                font-weight:600;
                margin-left:8px;
                display:inline-flex;
                align-items:center;
                gap:6px;
            `;
                        title.appendChild(badge);

                        // 🔘 Tombol Buy Topic (di kanan header)
                        const canBuy = completed.includes(prevId) || isFirst;
                        const btn = document.createElement('button');
                        btn.textContent = canBuy ? "Buy Topic" : "Selesaikan Bab Sebelumnya";
                        btn.disabled = !canBuy;
                        btn.className = 'tpt-btn-buy-topic';
                        btn.style.cssText = `
                display:inline-flex;
                align-items:center;
                justify-content:center;
                padding:6px 14px;
                border:none;
                border-radius:6px;
                font-weight:600;
                font-size:13px;
                cursor:${canBuy ? 'pointer' : 'not-allowed'};
                background:${canBuy ? '#ED2D56' : '#ccc'};
                color:white;
                margin-left:10px;
                transition:all .25s ease;
                pointer-events:auto;
                z-index:99;
            `;

                        if (canBuy) {
                            btn.addEventListener('mouseenter', () => {
                                btn.style.background = '#fff';
                                btn.style.color = '#ED2D56';
                                btn.style.border = '1px solid #ED2D56';
                            });
                            btn.addEventListener('mouseleave', () => {
                                btn.style.background = '#ED2D56';
                                btn.style.color = '#fff';
                                btn.style.border = 'none';
                            });

                            // 🛒 Add to Cart AJAX
                            btn.addEventListener('click', () => {
                                btn.disabled = true;
                                btn.innerHTML = `
                        <span class="tpt-spinner" style="
                            display:inline-block;
                            width:14px;
                            height:14px;
                            border:2px solid #fff;
                            border-top-color:transparent;
                            border-radius:50%;
                            margin-right:6px;
                            animation:tpt-spin 0.8s linear infinite;
                        "></span> Menambahkan...`;
                                btn.style.opacity = "0.8";

                                jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                                    action: "tpt_add_to_cart",
                                    product_id: wcId
                                }).done(() => {
                                    btn.innerHTML = `<i class="fa fa-check"></i> Berhasil!`;
                                    btn.style.background = "#444";
                                    btn.style.opacity = "1";
                                    setTimeout(() => window.location.href = "<?php echo wc_get_cart_url(); ?>", 800);
                                }).fail(() => {
                                    btn.innerHTML = "Buy Topic";
                                    btn.disabled = false;
                                    btn.style.opacity = "1";
                                });
                            });
                        }

                        // 🧩 Buat container kanan (badge + tombol)
                        const rightCol = document.createElement('div');
                        rightCol.className = 'tutor-col-auto tutor-align-self-center';
                        rightCol.style.display = 'flex';
                        rightCol.style.alignItems = 'center';
                        rightCol.style.gap = '6px';
                        rightCol.appendChild(badge);
                        rightCol.appendChild(btn);

                        // hapus badge lama jika ada, lalu tempel ulang
                        const oldRight = headerRow.querySelector('.tutor-col-auto');
                        if (oldRight) oldRight.remove();
                        headerRow.appendChild(rightCol);
                    }
                });
            }


            // Jalankan pertama kali
            renderBuyButtons();

            // Ulangi jika Tutor LMS merender ulang konten (SPA / AJAX)
            document.body.addEventListener("tutor_course_topics_rendered", renderBuyButtons);

            // Fallback: cek ulang tiap 2 detik (untuk kasus DOM load terlambat)
            setInterval(() => {
                if (document.querySelectorAll('.tutor-course-topic button').length === 0) {
                    renderBuyButtons();
                }
            }, 2000);
        });
    </script>

<?php
});


// Patch terbaru banget , 28 Nov 2025 - 10:38 AM
<?php
if (!defined('ABSPATH')) exit;

/**
 * 🔹 Show Topic Price Range on Course Card
 */
add_filter('tutor_course_card_price_html', function ($price_html, $course_id) {
    global $wpdb;
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT ID, post_title, pm.meta_value AS price
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_tpt_price'
        WHERE p.post_parent = %d AND p.post_type IN ('topics','topic')
        ORDER BY p.menu_order ASC
    ", $course_id));

    if (!$topics) return $price_html;

    $prices = array_map('intval', wp_list_pluck($topics, 'price'));
    $min = min($prices);
    $max = max($prices);

    return ($min === $max)
        ? 'Rp ' . number_format($min, 0, ',', '.')
        : 'Rp ' . number_format($min, 0, ',', '.') . ' – Rp ' . number_format($max, 0, ',', '.');
}, 10, 2);


/**
 * 2️⃣ Inject JS on Frontend - Replace default Tutor price in Acadia course card with loading spinner
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

                const priceArea = card.querySelector('.tp-course-pricing, .tp-course-btn .tutor-course-price');
                if (!priceArea) continue;

                // Tambahkan spinner sebelum API call
                const spinner = document.createElement('div');
                spinner.className = 'tpt-price-spinner';
                spinner.innerHTML = '<span></span><span></span><span></span>';
                priceArea.innerHTML = '';
                priceArea.appendChild(spinner);

                try {
                    const res = await fetch(`/wp-json/tpt/v1/get-topic-prices?course_id=${courseId}&t=${Date.now()}`);
                    const data = await res.json();

                    const min = Number(data.data.price_min || 0);
                    const max = Number(data.data.price_max || 0);

                    // Hapus harga default Tutor bawaan
                    const defaultPriceEls = card.querySelectorAll('.tutor-item-price');
                    defaultPriceEls.forEach(el => el.remove());

                    // Buat tampilan harga topik
                    const priceWrapper = document.createElement('div');
                    priceWrapper.className = 'tpt-course-price-wrapper';

                    if (min === 0 && max === 0) {
                        priceWrapper.innerHTML = '';
                    } else if (min === max) {
                        priceWrapper.innerHTML = `<span class="tpt-course-price">Rp ${min.toLocaleString('id-ID')}</span>`;
                    } else {
                        priceWrapper.innerHTML = `<span class="tpt-course-price">Rp ${min.toLocaleString('id-ID')} – Rp ${max.toLocaleString('id-ID')}</span>`;
                    }

                    priceArea.innerHTML = '';
                    priceArea.appendChild(priceWrapper);

                } catch (e) {
                    console.warn('Gagal ambil harga topik:', e);
                    priceArea.innerHTML = '<span class="tpt-price-error">Gagal memuat harga</span>';
                }
            }
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
            color: #3e64de;
            font-weight: 700;
        }

        /* Spinner */
        .tpt-price-spinner {
            display: flex;
            align-items: center;
            gap: 3px;
            height: 16px;
        }

        .tpt-price-spinner span {
            display: block;
            width: 4px;
            height: 16px;
            background: #3e64de;
            animation: tpt-spinner 1s infinite ease-in-out;
        }

        .tpt-price-spinner span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .tpt-price-spinner span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes tpt-spinner {

            0%,
            40%,
            100% {
                transform: scaleY(0.4);
            }

            20% {
                transform: scaleY(1.0);
            }
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

        .tpt-price-error {
            color: #ED2D56;
            font-weight: 500;
            font-size: 14px;
        }
    </style>
<?php
});

// ======================================================
// 🔹 FRONTEND: Lock Indicator + Badge + Buy Button
// ======================================================
add_action('wp_footer', function () {
    if (!is_singular(['courses', 'tutor_course', 'lesson'])) return;

    $user_id   = get_current_user_id();
    $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
    $purchased = get_user_meta($user_id, '_tpt_purchased_topics', true) ?: [];

    global $wpdb;

    // 🔹 Cari course ID aktif
    $course_id = get_post_meta(get_the_ID(), '_tutor_course_id', true);
    if (!$course_id) {
        $parent = wp_get_post_parent_id(get_the_ID());
        if ($parent) $course_id = wp_get_post_parent_id($parent);
    }
    if (!$course_id) return;

    // 🔹 Ambil semua topic + harga dari WooCommerce product link
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT t.ID, t.post_title, p2.meta_value AS wc_id, p3.meta_value AS price
        FROM {$wpdb->posts} t
        LEFT JOIN {$wpdb->postmeta} p2 ON p2.post_id = t.ID AND p2.meta_key = '_tpt_wc_id'
        LEFT JOIN {$wpdb->postmeta} p3 ON p3.post_id = p2.meta_value AND p3.meta_key = '_price'
        WHERE t.post_parent = %d
          AND t.post_type IN ('topics','topic')
          AND t.post_status='publish'
        ORDER BY t.menu_order ASC
    ", $course_id));

    if (!$topics) return;
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const completed = <?php echo json_encode($completed); ?>;
            const purchased = <?php echo json_encode($purchased); ?>;
            const topics = <?php echo json_encode($topics); ?>;

            function renderBuyButtons() {
                console.log("🧩 Rendering Buy Buttons...");
                document.querySelectorAll('.tutor-course-topic').forEach((el, index, all) => {
                    if (el.dataset.buyRendered) return; // supaya gak double render
                    el.dataset.buyRendered = "1";

                    const match = el.className.match(/tutor-course-topic-(\d+)/);
                    const topicId = match ? parseInt(match[1]) : 0;
                    if (!topicId) return;

                    const tdata = topics.find(t => parseInt(t.ID) === topicId);
                    if (!tdata) return;

                    const price = parseInt(tdata.price || 0);
                    const wcId = parseInt(tdata.wc_id || 0);
                    const prev = index > 0 ? all[index - 1] : null;
                    const prevId = prev ? parseInt(prev.className.match(/tutor-course-topic-(\d+)/)?.[1] || 0) : 0;
                    const isFirst = index === 0;

                    const unlocked = isFirst || (completed.includes(prevId) && purchased.includes(topicId));

                    const header = el.querySelector('.tutor-accordion-item-header');
                    const title = header?.querySelector('.tutor-course-topic-title');
                    const headerRow = header?.querySelector('.tutor-row');
                    if (!header || !title || !headerRow) return;

                    // Jika topic terkunci
                    if (!unlocked) {
                        header.style.opacity = "0.6";
                        header.style.pointerEvents = "none";
                        header.style.position = "relative";

                        // 🔒 Badge Locked
                        const badge = document.createElement('span');
                        badge.innerHTML = `<i class="fa fa-lock"></i> Locked · Rp ${price ? price.toLocaleString('id-ID') : '0'}`;
                        badge.className = 'tpt-lock-badge';
                        badge.style.cssText = `
                        background:#FFE4E9;
                        color:#ED2D56;
                        padding:4px 10px;
                        border-radius:8px;
                        font-size:12px;
                        font-weight:600;
                        display:inline-flex;
                        align-items:center;
                        gap:6px;
                    `;

                        // 🔘 Tombol Buy hanya jika bisa beli
                        const canBuy = completed.includes(prevId) || isFirst;

                        const rightCol = document.createElement('div');
                        rightCol.className = 'tutor-col-auto tutor-align-self-center';
                        rightCol.style.display = 'flex';
                        rightCol.style.alignItems = 'center';
                        rightCol.style.gap = '6px';

                        rightCol.appendChild(badge); // badge selalu tampil

                        if (canBuy) {
                            const btn = document.createElement('button');
                            btn.textContent = "Buy Topic";
                            btn.className = 'tpt-btn-buy-topic';
                            btn.style.cssText = `
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            padding:6px 14px;
                            border:none;
                            border-radius:6px;
                            font-weight:600;
                            font-size:13px;
                            cursor:pointer;
                            background:#ED2D56;
                            color:white;
                            margin-left:10px;
                            transition:all .25s ease;
                            pointer-events:auto;
                            z-index:99;
                        `;

                            btn.addEventListener('mouseenter', () => {
                                btn.style.background = '#fff';
                                btn.style.color = '#ED2D56';
                                btn.style.border = '1px solid #ED2D56';
                            });
                            btn.addEventListener('mouseleave', () => {
                                btn.style.background = '#ED2D56';
                                btn.style.color = '#fff';
                                btn.style.border = 'none';
                            });

                            btn.addEventListener('click', () => {
                                btn.disabled = true;
                                btn.innerHTML = `
                                <span class="tpt-spinner" style="
                                    display:inline-block;
                                    width:14px;
                                    height:14px;
                                    border:2px solid #fff;
                                    border-top-color:transparent;
                                    border-radius:50%;
                                    margin-right:6px;
                                    animation:tpt-spin 0.8s linear infinite;
                                "></span> Menambahkan...`;
                                btn.style.opacity = "0.8";

                                jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                                    action: "tpt_add_to_cart",
                                    product_id: wcId
                                }).done(() => {
                                    btn.innerHTML = `<i class="fa fa-check"></i> Berhasil!`;
                                    btn.style.background = "#444";
                                    btn.style.opacity = "1";
                                    setTimeout(() => window.location.href = "<?php echo wc_get_cart_url(); ?>", 800);
                                }).fail(() => {
                                    btn.innerHTML = "Buy Topic";
                                    btn.disabled = false;
                                    btn.style.opacity = "1";
                                });
                            });

                            rightCol.appendChild(btn);
                        }

                        // hapus badge lama jika ada, lalu tempel ulang
                        const oldRight = headerRow.querySelector('.tutor-col-auto');
                        if (oldRight) oldRight.remove();
                        headerRow.appendChild(rightCol);
                    }
                });
            }

            // Jalankan pertama kali
            renderBuyButtons();

            // Ulangi jika Tutor LMS merender ulang konten (SPA / AJAX)
            document.body.addEventListener("tutor_course_topics_rendered", renderBuyButtons);

            // Fallback: cek ulang tiap 2 detik (untuk kasus DOM load terlambat)
            setInterval(() => {
                if (document.querySelectorAll('.tutor-course-topic button').length === 0) {
                    renderBuyButtons();
                }
            }, 2000);
        });
    </script>
<?php
});



// Backup lagi mau tahap patch selanjutnya , realtime unlocked topic after purchase
<?php
if (!defined('ABSPATH')) exit;

/**
 * 🔹 Show Topic Price Range on Course Card
 */
add_filter('tutor_course_card_price_html', function ($price_html, $course_id) {
    global $wpdb;
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT ID, post_title, pm.meta_value AS price
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_tpt_price'
        WHERE p.post_parent = %d AND p.post_type IN ('topics','topic')
        ORDER BY p.menu_order ASC
    ", $course_id));

    if (!$topics) return $price_html;

    $prices = array_map('intval', wp_list_pluck($topics, 'price'));
    $min = min($prices);
    $max = max($prices);

    return ($min === $max)
        ? 'Rp ' . number_format($min, 0, ',', '.')
        : 'Rp ' . number_format($min, 0, ',', '.') . ' – Rp ' . number_format($max, 0, ',', '.');
}, 10, 2);


/**
 * 🔹 Inject JS on Frontend - Replace default Tutor price in Acadia course card with loading spinner
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

                const priceArea = card.querySelector('.tp-course-pricing, .tp-course-btn .tutor-course-price');
                if (!priceArea) continue;

                // Spinner sebelum API call
                const spinner = document.createElement('div');
                spinner.className = 'tpt-price-spinner';
                spinner.innerHTML = '<span></span><span></span><span></span>';
                priceArea.innerHTML = '';
                priceArea.appendChild(spinner);

                try {
                    const res = await fetch(`/wp-json/tpt/v1/get-topic-prices?course_id=${courseId}&t=${Date.now()}`);
                    const data = await res.json();

                    const min = Number(data.data.price_min || 0);
                    const max = Number(data.data.price_max || 0);

                    const defaultPriceEls = card.querySelectorAll('.tutor-item-price');
                    defaultPriceEls.forEach(el => el.remove());

                    const priceWrapper = document.createElement('div');
                    priceWrapper.className = 'tpt-course-price-wrapper';

                    if (min === 0 && max === 0) {
                        priceWrapper.innerHTML = '';
                    } else if (min === max) {
                        priceWrapper.innerHTML = `<span class="tpt-course-price">Rp ${min.toLocaleString('id-ID')}</span>`;
                    } else {
                        priceWrapper.innerHTML = `<span class="tpt-course-price">Rp ${min.toLocaleString('id-ID')} – Rp ${max.toLocaleString('id-ID')}</span>`;
                    }

                    priceArea.innerHTML = '';
                    priceArea.appendChild(priceWrapper);

                } catch (e) {
                    console.warn('Gagal ambil harga topik:', e);
                    priceArea.innerHTML = '<span class="tpt-price-error">Gagal memuat harga</span>';
                }
            }
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
            color: #3e64de;
            font-weight: 700;
        }

        /* Spinner */
        .tpt-price-spinner {
            display: flex;
            align-items: center;
            gap: 3px;
            height: 16px;
        }

        .tpt-price-spinner span {
            display: block;
            width: 4px;
            height: 16px;
            background: #3e64de;
            animation: tpt-spinner 1s infinite ease-in-out;
        }

        .tpt-price-spinner span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .tpt-price-spinner span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes tpt-spinner {

            0%,
            40%,
            100% {
                transform: scaleY(0.4);
            }

            20% {
                transform: scaleY(1.0);
            }
        }

        /* Sembunyikan harga default Tutor bawaan */
        .tutor-item-price,
        .tutor-course-price {
            display: none !important;
        }

        .single-course .tpt-course-price-wrapper {
            font-size: 16px;
            color: #ED2D56;
            font-weight: 700;
        }

        .tpt-price-error {
            color: #ED2D56;
            font-weight: 500;
            font-size: 14px;
        }
    </style>
<?php
});


/**
 * 🔹 FRONTEND: Lock Indicator + Badge + Buy Button
 */
add_action('wp_footer', function () {
    if (!is_singular(['courses', 'tutor_course', 'lesson'])) return;

    $user_id   = get_current_user_id();
    $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
    $purchased = get_user_meta($user_id, '_tpt_purchased_topics', true) ?: [];

    global $wpdb;

    $course_id = get_post_meta(get_the_ID(), '_tutor_course_id', true);
    if (!$course_id) {
        $parent = wp_get_post_parent_id(get_the_ID());
        if ($parent) $course_id = wp_get_post_parent_id($parent);
    }
    if (!$course_id) return;

    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT t.ID, t.post_title, p2.meta_value AS wc_id, p3.meta_value AS price
        FROM {$wpdb->posts} t
        LEFT JOIN {$wpdb->postmeta} p2 ON p2.post_id = t.ID AND p2.meta_key = '_tpt_wc_id'
        LEFT JOIN {$wpdb->postmeta} p3 ON p3.post_id = p2.meta_value AND p3.meta_key = '_price'
        WHERE t.post_parent = %d
          AND t.post_type IN ('topics','topic')
          AND t.post_status='publish'
        ORDER BY t.menu_order ASC
    ", $course_id));

    if (!$topics) return;

    // Orders completed
    $user_orders = wc_get_orders([
        'customer_id' => $user_id,
        'limit'       => -1,
        'status'      => ['completed'],
    ]);

    $orderedTopicIds = [];
    foreach ($user_orders as $order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id) {
                $topic_id = $wpdb->get_var($wpdb->prepare("
                    SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_tpt_wc_id' AND meta_value=%d
                ", $product_id));
                if ($topic_id) $orderedTopicIds[] = intval($topic_id);
            }
        }
    }

    // Orders pending/on-hold/processing
    $pending_orders = wc_get_orders([
        'customer_id' => $user_id,
        'limit' => -1,
        'status' => ['pending', 'on-hold', 'processing'],
    ]);
    $orderedNotCompleted = [];
    foreach ($pending_orders as $order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id) {
                $topic_id = $wpdb->get_var($wpdb->prepare("
                    SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_tpt_wc_id' AND meta_value=%d
                ", $product_id));
                if ($topic_id) $orderedNotCompleted[] = intval($topic_id);
            }
        }
    }
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const completed = <?php echo json_encode($completed); ?>;
            const purchased = <?php echo json_encode($purchased); ?>;
            const topics = <?php echo json_encode($topics); ?>;
            const orderedTopicIds = <?php echo json_encode($orderedTopicIds); ?>;
            const orderedNotCompleted = <?php echo json_encode($orderedNotCompleted); ?>;

            function renderBuyButtons() {
                document.querySelectorAll('.tutor-course-topic').forEach((el, index, all) => {
                    if (el.dataset.buyRendered) return;
                    el.dataset.buyRendered = "1";

                    const match = el.className.match(/tutor-course-topic-(\d+)/);
                    const topicId = match ? parseInt(match[1]) : 0;
                    if (!topicId) return;

                    const tdata = topics.find(t => parseInt(t.ID) === topicId);
                    if (!tdata) return;

                    const price = parseInt(tdata.price || 0);
                    const wcId = parseInt(tdata.wc_id || 0);
                    const prev = index > 0 ? all[index - 1] : null;
                    const prevId = prev ? parseInt(prev.className.match(/tutor-course-topic-(\d+)/)?.[1] || 0) : 0;
                    const isFirst = index === 0;

                    const alreadyOrdered = orderedTopicIds.includes(topicId);
                    const unlocked = isFirst || completed.includes(prevId) || purchased.includes(topicId);
                    const isLocked = !unlocked || orderedNotCompleted.includes(topicId);

                    const header = el.querySelector('.tutor-accordion-item-header');
                    const title = header?.querySelector('.tutor-course-topic-title');
                    const headerRow = header?.querySelector('.tutor-row');
                    if (!header || !title || !headerRow) return;

                    header.style.position = "relative";

                    const badge = document.createElement('span');
                    badge.className = 'tpt-lock-badge';
                    badge.style.cssText = `
                background:#FFE4E9;
                color:#ED2D56;
                padding:4px 10px;
                border-radius:8px;
                font-size:12px;
                font-weight:600;
                display:inline-flex;
                align-items:center;
                gap:6px;
            `;

                    const rightCol = document.createElement('div');
                    rightCol.className = 'tutor-col-auto tutor-align-self-center';
                    rightCol.style.display = 'flex';
                    rightCol.style.alignItems = 'center';
                    rightCol.style.gap = '6px';

                    if (isLocked) {
                        header.style.opacity = "0.6";
                        header.style.pointerEvents = "none";

                        badge.innerHTML = orderedNotCompleted.includes(topicId) ?
                            `⏳ On Hold · Rp ${price.toLocaleString('id-ID')}` :
                            `🔒 Locked · Rp ${price.toLocaleString('id-ID')}`;

                        rightCol.appendChild(badge);
                    } else if (alreadyOrdered) {
                        const orderBadge = document.createElement('span');
                        orderBadge.className = 'tpt-order-badge';
                        orderBadge.textContent = "Order: ✅";
                        orderBadge.style.cssText = `
                    background:#D4EDDA;
                    color:#155724;
                    padding:4px 10px;
                    border-radius:6px;
                    font-size:12px;
                    font-weight:600;
                    display:inline-flex;
                    align-items:center;
                `;
                        rightCol.appendChild(orderBadge);
                    } else {
                        const canBuy = completed.includes(prevId) || isFirst;
                        if (canBuy) {
                            const btn = document.createElement('button');
                            btn.textContent = "Buy Topic";
                            btn.className = 'tpt-btn-buy-topic';
                            btn.style.cssText = `
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        padding:6px 14px;
                        border:none;
                        border-radius:6px;
                        font-weight:600;
                        font-size:13px;
                        cursor:pointer;
                        background:#ED2D56;
                        color:white;
                        margin-left:10px;
                        transition:all .25s ease;
                        pointer-events:auto;
                        z-index:99;
                    `;

                            btn.addEventListener('mouseenter', () => {
                                btn.style.background = '#fff';
                                btn.style.color = '#ED2D56';
                                btn.style.border = '1px solid #ED2D56';
                            });
                            btn.addEventListener('mouseleave', () => {
                                btn.style.background = '#ED2D56';
                                btn.style.color = '#fff';
                                btn.style.border = 'none';
                            });

                            btn.addEventListener('click', () => {
                                btn.disabled = true;
                                btn.innerHTML = `<span class="tpt-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;margin-right:6px;animation:tpt-spin 0.8s linear infinite;"></span> Menambahkan...`;
                                btn.style.opacity = "0.8";

                                jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                                    action: "tpt_add_to_cart",
                                    product_id: wcId
                                }).done(() => {
                                    btn.innerHTML = `<i class="fa fa-check"></i> Berhasil!`;
                                    btn.style.background = "#444";
                                    btn.style.opacity = "1";
                                    setTimeout(() => window.location.href = "<?php echo wc_get_cart_url(); ?>", 800);
                                }).fail(() => {
                                    btn.innerHTML = "Buy Topic";
                                    btn.disabled = false;
                                    btn.style.opacity = "1";
                                });
                            });

                            rightCol.appendChild(btn);
                        }
                    }

                    const oldRight = headerRow.querySelector('.tutor-col-auto');
                    if (oldRight) oldRight.remove();
                    headerRow.appendChild(rightCol);
                });
            }

            renderBuyButtons();
            document.body.addEventListener("tutor_course_topics_rendered", renderBuyButtons);
            setInterval(() => {
                if (document.querySelectorAll('.tutor-course-topic button').length === 0) {
                    renderBuyButtons();
                }
            }, 2000);
        });
    </script>
<?php
});
