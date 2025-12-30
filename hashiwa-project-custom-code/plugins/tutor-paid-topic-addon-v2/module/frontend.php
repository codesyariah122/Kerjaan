<?php
if (!defined('ABSPATH')) exit;

/**
 * 🔹 Show Topic Price Range on Course Card
 */
add_filter('tutor_course_card_price_html', function ($price_html, $course_id) {
    global $wpdb;
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT ID, pm.meta_value AS price
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
 * 🔹 FRONTEND: Lock Indicator + Badge + Buy Button (Final with Order Status)
 */
add_action('wp_footer', function () {
    if (!is_singular(['courses', 'tutor_course', 'lesson', 'tutor_quiz'])) return;

    $user_id = get_current_user_id();
    if (!$user_id) return;

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
        SELECT t.ID, p2.meta_value AS wc_id, pm_price.meta_value AS price
        FROM {$wpdb->posts} t
        LEFT JOIN {$wpdb->postmeta} p2 ON p2.post_id = t.ID AND p2.meta_key = '_tpt_wc_id'
        LEFT JOIN {$wpdb->postmeta} pm_price ON pm_price.post_id = t.ID AND pm_price.meta_key = '_tpt_price'
        WHERE t.post_parent = %d
          AND t.post_type IN ('topics','topic')
          AND t.post_status='publish'
        ORDER BY t.menu_order ASC
    ", $course_id));

    if (!$topics) return;

    // Ambil semua order WooCommerce user
    $orders_completed = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => ['completed'],
        'limit'       => -1,
    ]);
    // 🩹 Ambil semua order yang belum selesai (pending, on-hold, processing, atau custom gateway)
    $orders_pending = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => ['pending', 'on-hold', 'processing'],
        'limit'       => -1,
    ]);

    // Tambahan: ambil juga order manual atau gateway custom yg belum complete
    $orders_custom = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => array_diff(wc_get_order_statuses(), ['wc-completed', 'wc-cancelled', 'wc-refunded']),
        'limit'       => -1,
    ]);

    $orders_pending = array_merge($orders_pending, $orders_custom);

    $orderedCompleted = [];
    $orderedPending = [];

    foreach ($orders_completed as $order) {
        foreach ($order->get_items() as $item) {
            $pid = $item->get_product_id();
            $tid = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_tpt_wc_id' AND meta_value=%d", $pid));
            if ($tid) $orderedCompleted[] = intval($tid);
        }
    }
    foreach ($orders_pending as $order) {
        foreach ($order->get_items() as $item) {
            $pid = $item->get_product_id();
            $tid = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_tpt_wc_id' AND meta_value=%d", $pid));
            if ($tid) $orderedPending[] = intval($tid);
        }
    }

    // 🔍 Kumpulkan status spesifik tiap topic (on-hold vs processing)
    $topicStatuses = [];

    $all_orders = wc_get_orders([
        'customer_id' => $user_id,
        'limit' => -1,
    ]);

    foreach ($all_orders as $order) {
        foreach ($order->get_items() as $item) {
            $pid = $item->get_product_id();
            $tid = $wpdb->get_var($wpdb->prepare("
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_tpt_wc_id' AND meta_value = %d
            ", $pid));
            if ($tid) {
                $topicStatuses[intval($tid)] = str_replace('wc-', '', $order->get_status());
            }
        }
    }
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const topics = <?php echo json_encode($topics); ?>;
            const completed = <?php echo json_encode($completed); ?>;
            // const purchased = <?php echo json_encode($purchased); ?>;
            let purchased = JSON.parse(localStorage.getItem('tpt_purchased_topics') || '[]') || <?php echo json_encode($purchased); ?>;
            const orderedCompleted = <?php echo json_encode($orderedCompleted); ?>;
            const orderedPending = <?php echo json_encode($orderedPending); ?>;
            const topicStatuses = <?php echo json_encode($topicStatuses); ?>;

            // 🩹 PATCH: Normalisasi ID ke integer
            const normalize = arr => Array.isArray(arr) ? arr.map(v => parseInt(v)) : [];
            // ======================================================
            // 🧠 New: Ambil data lesson progress per topic (real-time)
            // ======================================================
            const userLessonCompleted = <?php
                                        $lessons = get_user_meta($user_id, '_tutor_lesson_completed', true);
                                        if (!is_array($lessons)) $lessons = [];
                                        echo json_encode($lessons);
                                        ?>;

            //     const topicLessonsMap = <?php
                                            //                             // Buat mapping: topic_id => array of lesson_ids
                                            //                             $topicLessonsMap = [];
                                            //                             foreach ($topics as $t) {
                                            //                                 $lessons = $wpdb->get_col($wpdb->prepare("
                                            //     SELECT ID FROM {$wpdb->posts}
                                            //     WHERE post_parent = %d AND post_type = 'lesson' AND post_status = 'publish'
                                            // ", $t->ID));
                                            //                                 $topicLessonsMap[intval($t->ID)] = array_map('intval', $lessons);
                                            //                             }
                                            //                             echo json_encode($topicLessonsMap);
                                            //                             
                                            ?>;

            //     // Helper untuk cek apakah semua lesson di topic sudah complete
            //     function isTopicFullyCompleted(topicId) {
            //         const lessons = topicLessonsMap[topicId] || [];
            //         if (lessons.length === 0) return true; // kalau nggak ada lesson, anggap complete
            //         return lessons.every(lid => userLessonCompleted.includes(lid));
            //     }
            const topicLessonsMap = <?php
                                    $topicLessonsMap = [];
                                    $topicQuizMap = [];
                                    foreach ($topics as $t) {
                                        $lessons = $wpdb->get_col($wpdb->prepare("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_parent = %d AND post_type = 'lesson' AND post_status = 'publish'
        ", $t->ID));
                                        $quizzes = $wpdb->get_col($wpdb->prepare("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_parent = %d AND post_type = 'tutor_quiz' AND post_status = 'publish'
        ", $t->ID));

                                        $topicLessonsMap[intval($t->ID)] = array_map('intval', $lessons);
                                        $topicQuizMap[intval($t->ID)] = array_map('intval', $quizzes);
                                    }
                                    echo json_encode($topicLessonsMap);
                                    ?>;

            const topicQuizMap = <?php echo json_encode($topicQuizMap); ?>;

            // Helper untuk cek apakah semua lesson & quiz di topic sudah complete
            function isTopicFullyCompleted(topicId) {
                const lessons = topicLessonsMap[topicId] || [];
                const quizzes = topicQuizMap[topicId] || [];

                // Jika tidak ada lesson & quiz, baru dianggap complete
                if (lessons.length === 0 && quizzes.length === 0) return true;

                const allLessonsDone = lessons.every(lid => userLessonCompleted.includes(lid));
                const allQuizzesDone = quizzes.every(qid => userLessonCompleted.includes(qid) || completedInt.includes(topicId));

                return allLessonsDone && allQuizzesDone;
            }


            const completedInt = normalize(completed);
            const purchasedInt = normalize(purchased);
            const orderedCompletedInt = normalize(orderedCompleted);
            const orderedPendingInt = normalize(orderedPending);

            function renderBuyButtons() {
                document.querySelectorAll('.tutor-course-topic').forEach((el, index, all) => {
                    if (el.dataset.rendered) return;
                    el.dataset.rendered = "1";

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

                    const header = el.querySelector('.tutor-accordion-item-header');
                    const headerRow = header?.querySelector('.tutor-row');
                    if (!header || !headerRow) return;

                    const rightCol = document.createElement('div');
                    rightCol.className = 'tutor-col-auto tutor-align-self-center';
                    rightCol.style.display = 'flex';
                    rightCol.style.alignItems = 'center';
                    rightCol.style.gap = '6px';

                    // =====================
                    // STATUS HANDLER (FINAL PATCH v3 - dengan overlay visual lock)
                    // =====================
                    const isCompleted = orderedCompletedInt.includes(topicId) || purchasedInt.includes(topicId);
                    const isPending = orderedPendingInt.includes(topicId);
                    const prevCompleted = isFirst || (
                        completedInt.includes(prevId) &&
                        (topicLessonsMap[prevId] || []).every(lid => userLessonCompleted.includes(lid))
                    );
                    const canBuyNow = prevCompleted && !isCompleted && !isPending;

                    // Default: header terkunci
                    header.style.opacity = "0.6";
                    header.style.pointerEvents = "none";
                    header.style.cursor = "not-allowed";

                    // Tambahkan overlay (akan diaktifkan di kondisi locked)
                    const overlay = document.createElement('div');
                    overlay.className = 'tpt-locked-overlay';
                    overlay.style.cssText = `
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.3);
    backdrop-filter: blur(0px);
    z-index: 5;
    border-radius: 6px;
    display: none;
`;
                    header.style.position = 'relative';
                    header.appendChild(overlay);

                    if (isCompleted) {
                        const badge = document.createElement('span');
                        badge.textContent = "✅ Order: Completed";
                        badge.style.cssText = `
        background:#D4EDDA;
        color:#155724;
        padding:4px 10px;
        border-radius:6px;
        font-size:12px;
        font-weight:600;
    `;
                        rightCol.appendChild(badge);
                        header.style.opacity = "1";
                        header.style.pointerEvents = "auto";
                        header.style.cursor = "default";
                        overlay.style.display = 'none';

                        //                 } else if (isPending) {
                        //                     const badge = document.createElement('span');
                        //                     badge.textContent = "⏳ On Hold / Processing";
                        //                     badge.style.cssText = `
                        //     background:#FFF3CD;
                        //     color:#856404;
                        //     padding:4px 10px;
                        //     border-radius:6px;
                        //     font-size:12px;
                        //     font-weight:600;
                        // `;
                        //                     rightCol.appendChild(badge);
                        //                     header.style.opacity = "0.5";
                        //                     header.style.pointerEvents = "none";
                        //                     header.style.cursor = "not-allowed";
                        //                     overlay.style.display = 'block';

                        //                 }
                    } else if (isPending) {
                        const currentStatus = topicStatuses[topicId] || 'on-hold';
                        const badge = document.createElement('span');

                        switch (currentStatus) {
                            case 'processing':
                                badge.innerHTML = '<img draggable="false" role="img" alt="⚙️" src="https://s.w.org/images/core/emoji/17.0.2/svg/2699.svg" style="width:14px;height:14px;margin-right:4px;vertical-align:middle;"> Processing · Sedang Diproses';
                                badge.style.background = '#D1ECF1';
                                badge.style.color = '#0C5460';
                                header.style.opacity = "0.9";
                                break;

                            case 'pending':
                                badge.innerHTML = '<img draggable="false" role="img" alt="⏳" src="https://s.w.org/images/core/emoji/17.0.2/svg/23f3.svg" style="width:14px;height:14px;margin-right:4px;vertical-align:middle;"> Pending · Menunggu Konfirmasi';
                                badge.style.background = '#FFF3CD';
                                badge.style.color = '#856404';
                                header.style.opacity = "0.8";
                                break;

                            case 'on-hold':
                                badge.innerHTML = '<img draggable="false" role="img" alt="🕓" src="https://s.w.org/images/core/emoji/17.0.2/svg/1f553.svg" style="width:14px;height:14px;margin-right:4px;vertical-align:middle;"> On Hold · Menunggu Pembayaran';
                                badge.style.background = '#FFF3CD';
                                badge.style.color = '#856404';
                                header.style.opacity = "0.8";
                                break;

                            case 'failed':
                                badge.innerHTML = '<img draggable="false" role="img" alt="❌" src="https://s.w.org/images/core/emoji/17.0.2/svg/274c.svg" style="width:14px;height:14px;margin-right:4px;vertical-align:middle;"> Failed · Pembayaran Gagal';
                                badge.style.background = '#F8D7DA';
                                badge.style.color = '#721C24';
                                header.style.opacity = "0.7";
                                break;

                            case 'cancelled':
                                badge.innerHTML = '<img draggable="false" role="img" alt="🚫" src="https://s.w.org/images/core/emoji/17.0.2/svg/1f6ab.svg" style="width:14px;height:14px;margin-right:4px;vertical-align:middle;"> Cancelled · Dibatalkan';
                                badge.style.background = '#E2E3E5';
                                badge.style.color = '#383D41';
                                header.style.opacity = "0.7";
                                break;

                            default:
                                badge.innerHTML = `<img draggable="false" role="img" alt="🕓" src="https://s.w.org/images/core/emoji/17.0.2/svg/1f553.svg" style="width:14px;height:14px;margin-right:4px;vertical-align:middle;"> ${currentStatus}`;
                                badge.style.background = '#FFF3CD';
                                badge.style.color = '#856404';
                                header.style.opacity = "0.8";
                        }

                        badge.style.cssText += 'padding:4px 10px;border-radius:8px;font-size:12px;font-weight:600;';
                        rightCol.appendChild(badge);

                        overlay.style.display = 'block';
                        overlay.style.background = 'rgba(255,255,255,0.4)';
                        header.style.pointerEvents = "none";
                    } else if (canBuyNow) {
                        // 🔒 Locked badge tapi tombol aktif
                        const badge = document.createElement('span');
                        badge.textContent = `🔒 Locked · Rp ${price.toLocaleString('id-ID')}`;
                        badge.style.cssText = `
        background:#FFE4E9;
        color:#ED2D56;
        padding:4px 10px;
        border-radius:8px;
        font-size:12px;
        font-weight:600;
    `;
                        rightCol.appendChild(badge);

                        const btn = document.createElement('button');
                        btn.textContent = "Buy Topic";
                        btn.className = 'tpt-btn-buy-topic';
                        btn.style.cssText = `
        padding:6px 14px;
        border:none;
        border-radius:6px;
        font-weight:600;
        font-size:13px;
        cursor:pointer;
        background:#ED2D56;
        color:white;
        transition:all .25s ease;
        position:relative;
        z-index:10;
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
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation(); // ⛔ jangan buka collapsible
                            btn.disabled = true;
                            btn.innerHTML = `<span class="tpt-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;margin-right:6px;animation:tpt-spin 0.8s linear infinite;"></span> Menambahkan...`;
                            jQuery.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                                action: "tpt_add_to_cart",
                                product_id: wcId
                            }).done(() => {
                                btn.innerHTML = `<i class="fa fa-check"></i> Berhasil!`;
                                btn.style.background = "#444";
                                setTimeout(() => window.location.href = "<?php echo wc_get_cart_url(); ?>", 800);
                            }).fail(() => {
                                btn.innerHTML = "Buy Topic";
                                btn.disabled = false;
                            });
                        });
                        rightCol.appendChild(btn);

                        // ✅ Buy Topic aktif, tapi accordion tetap tidak bisa diklik selain tombol
                        header.style.opacity = "1";
                        header.style.pointerEvents = "none";
                        header.style.cursor = "not-allowed";
                        overlay.style.display = 'block';
                        overlay.style.background = 'rgba(255,255,255,0.3)';
                        overlay.style.backdropFilter = 'blur(0px)';
                        btn.style.pointerEvents = "auto";

                    } else if (!isFirst && !completedInt.includes(prevId)) {
                        const badge = document.createElement('span');
                        badge.textContent = `🔒 Selesaikan Bab sebelumnya`;
                        badge.style.cssText = `
        background:#FFE4E9;
        color:#ED2D56;
        padding:4px 10px;
        border-radius:8px;
        font-size:12px;
        font-weight:600;
    `;
                        rightCol.appendChild(badge);
                        header.style.opacity = "0.5";
                        header.style.pointerEvents = "none";
                        header.style.cursor = "not-allowed";
                        overlay.style.display = 'block';

                    } else {
                        const badge = document.createElement('span');
                        badge.textContent = `🔒 Locked · Rp ${price.toLocaleString('id-ID')}`;
                        badge.style.cssText = `
        background:#FFE4E9;
        color:#ED2D56;
        padding:4px 10px;
        border-radius:8px;
        font-size:12px;
        font-weight:600;
    `;
                        rightCol.appendChild(badge);
                        header.style.opacity = "0.5";
                        header.style.pointerEvents = "none";
                        header.style.cursor = "not-allowed";
                        overlay.style.display = 'block';
                    }


                    const oldRight = headerRow.querySelector('.tutor-col-auto');
                    if (oldRight) oldRight.remove();
                    headerRow.appendChild(rightCol);
                });
            }

            renderBuyButtons();
            document.body.addEventListener("tutor_course_topics_rendered", renderBuyButtons);
        });

        // 🔁 Auto-refresh _tpt_purchased_topics setiap 10 detik (tanpa logout)
        async function syncPurchasedTopics() {
            try {
                const res = await fetch(`/wp-json/tpt/v1/user-progress?user_id=${<?php echo get_current_user_id(); ?>}`);
                const data = await res.json();
                if (data && data.purchased) {
                    localStorage.setItem('tpt_purchased_topics', JSON.stringify(data.purchased));
                }
            } catch (err) {
                console.warn('❌ Gagal sync purchased topics:', err);
            }
        }

        // Panggil sekali setelah page load
        syncPurchasedTopics();

        // Ulangi setiap 10 detik untuk sinkronisasi real-time
        setInterval(syncPurchasedTopics, 10000);
        // 🔁 Auto-refresh order status tiap 10 detik (biar Processing → Completed update otomatis)
        async function syncOrderStatuses() {
            try {
                const res = await fetch(`/wp-json/tpt/v1/user-progress?user_id=${<?php echo get_current_user_id(); ?>}`);
                const data = await res.json();

                if (data?.data?.order_statuses) {
                    const topicStatuses = data.data.order_statuses;
                    console.log('%c[TPT] 🔄 Sync order statuses:', 'color:#2196F3;font-weight:bold;', topicStatuses);

                    // Update badge topic langsung
                    document.querySelectorAll('.tutor-course-topic').forEach(el => {
                        const match = el.className.match(/tutor-course-topic-(\d+)/);
                        const topicId = match ? parseInt(match[1]) : 0;
                        if (!topicId) return;

                        const header = el.querySelector('.tutor-accordion-item-header');
                        const badge = header?.querySelector('span');
                        if (!badge) return;

                        const status = topicStatuses[topicId];
                        if (status === 'completed') {
                            badge.innerHTML = '<img draggable="false" role="img" alt="✅" src="https://s.w.org/images/core/emoji/17.0.2/svg/2705.svg" style="width:14px;height:14px;margin-right:4px;vertical-align:middle;"> Order: Completed';
                            badge.style.background = '#D4EDDA';
                            badge.style.color = '#155724';
                            header.style.opacity = '1';
                            header.style.pointerEvents = 'auto';
                        }
                    });
                }
            } catch (err) {
                console.warn('❌ Gagal sync order statuses:', err);
            }
        }

        // Jalankan auto-sync setiap 10 detik
        setInterval(syncOrderStatuses, 10000);


        // 🧩 PATCH: Sinkronisasi ulang progress lesson setelah klik "Mark as Complete"
        // jQuery(document).ajaxSuccess(function(event, xhr, settings) {
        //     if (settings.data && settings.data.includes('tutor_complete_lesson')) {
        //         console.log('🔄 Lesson marked complete, refreshing lesson progress...');
        //         fetch(`/wp-json/tpt/v1/user-progress?user_id=${<?php echo get_current_user_id(); ?>}`)
        //             .then(res => res.json())
        //             .then(data => {
        //                 if (data && data.lesson_completed) {
        //                     localStorage.setItem('tpt_user_lesson_completed', JSON.stringify(data.lesson_completed));
        //                     const lessonId = parseInt(settings.data.match(/lesson_id=(\d+)/)?.[1] || 0);
        //                     if (lessonId && !userLessonCompleted.includes(lessonId)) {
        //                         userLessonCompleted.push(lessonId);
        //                         renderBuyButtons();
        //                     }
        //                 }
        //             })
        //             .catch(err => console.warn('❌ Gagal refresh lesson progress:', err));
        //     }
        // });
        // versi patch
        // 🧩 PATCH FINAL: Sinkronisasi progress lesson saat klik "Mark as Complete"
        jQuery(document).ajaxSuccess(function(event, xhr, settings) {
            if (settings.data && settings.data.includes('tutor_complete_lesson')) {
                console.log('%c[TPT] Lesson marked complete → sync progress...', 'color:#28a745;font-weight:600');

                fetch(`/wp-json/tpt/v1/user-progress?user_id=${<?php echo get_current_user_id(); ?>}`)
                    .then(res => res.json())
                    .then(data => {
                        const lessons = data?.data?.lesson_completed || [];
                        const completedTopics = data?.data?.completed || [];

                        // 🔁 Simpan di localStorage
                        localStorage.setItem('tpt_user_lesson_completed', JSON.stringify(lessons));

                        // 🔁 Update global memory agar renderBuyButtons() bisa akses
                        window.userLessonCompleted = lessons;
                        window.completedInt = completedTopics.map(v => parseInt(v));

                        // 🔁 Render ulang UI
                        if (typeof renderBuyButtons === "function") {
                            renderBuyButtons();
                        }

                        console.log('%c[TPT] Lesson sync success → re-rendered.', 'color:#00bcd4;font-weight:600');
                    })
                    .catch(err => console.warn('[TPT] ❌ Gagal sync lesson:', err));
            }
        });

        window.addEventListener('storage', () => {
            const updated = JSON.parse(localStorage.getItem('tpt_purchased_topics') || '[]');
            if (updated.length !== purchased.length) {
                purchased = updated;
                console.log('🔄 Purchased topics refreshed:', purchased);
                renderBuyButtons(); // re-render tampilan
            }
        });
    </script>
    <style>
        @keyframes tpt-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
<?php
});
/**
 * 🔹 Show Topic Price Range on Course Card (archive / listing)
 */
add_filter('tutor_course_card_price_html', function ($price_html, $course_id) {
    global $wpdb;
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT ID, pm.meta_value AS price
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
 * 🔹 Inject JS on Frontend - Replace default Tutor price in course cards
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

                // Spinner sementara
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
        /* Styling harga di card */
        .tpt-course-price-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-size: 15px;
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

        /* Hide harga default Tutor */
        .tutor-item-price,
        .tutor-course-price {
            display: none !important;
        }

        /* Single course style */
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
 * 🔹 Replace Single Course Price with Topic Range
 */
add_filter('tutor_course_price', function ($price_html, $course_id) {
    if (!is_singular('courses')) return $price_html;

    global $wpdb;
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT pm.meta_value AS price
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_tpt_price'
        WHERE p.post_parent = %d
        AND p.post_type IN ('topics','topic')
        AND p.post_status = 'publish'
    ", $course_id));

    if (!$topics) return $price_html;

    $prices = array_map('intval', wp_list_pluck($topics, 'price'));
    $min = min($prices);
    $max = max($prices);

    if ($min === 0 && $max === 0) return '';

    $range = ($min === $max)
        ? 'Rp ' . number_format($min, 0, ',', '.')
        : 'Rp ' . number_format($min, 0, ',', '.') . ' – Rp ' . number_format($max, 0, ',', '.');

    return '<span class="tpt-course-price-range">' . $range . '</span>';
}, 10, 2);

/**
 * 🔹 Inject range harga topik di sidebar Acadia (fix final DOM selector)
 */
add_action('wp_footer', function () {
    if (!is_singular('courses')) return;

    global $wpdb;
    $course_id = get_the_ID();
    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT pm.meta_value AS price
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm 
            ON pm.post_id = p.ID 
            AND pm.meta_key = '_tpt_price'
        WHERE p.post_parent = %d
        AND p.post_type IN ('topics','topic')
        AND p.post_status = 'publish'
    ", $course_id));

    if (!$topics) return;

    $prices = array_map('intval', wp_list_pluck($topics, 'price'));
    $min = min($prices);
    $max = max($prices);
    if ($min === 0 && $max === 0) return;

    $range = ($min === $max)
        ? 'Rp ' . number_format($min, 0, ',', '.')
        : 'Rp ' . number_format($min, 0, ',', '.') . ' – Rp ' . number_format($max, 0, ',', '.');
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Target harga WooCommerce versi Acadia
            const priceNode = document.querySelector('.acadia-course-single-sidebar-body .tp-course-details2-widget-price .woocommerce-Price-amount bdi');
            if (!priceNode) return;

            priceNode.innerHTML = `<?php echo $range; ?>`;

            // Ubah style biar sama kayak desain Evadne
            priceNode.style.fontSize = '20px';
            priceNode.style.fontWeight = '700';
            priceNode.style.color = '#ED2D56';
        });
    </script>
<?php
});

/**
 * 🔹 Re-inject Badge Harga di Tiap Topic Accordion (Fix agar jalan bareng patch sidebar Acadia)
 */
add_action('wp_footer', function () {
    if (!is_singular('courses')) return;

    global $wpdb;
    $course_id = get_the_ID();

    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT ID, post_title, pm.meta_value AS price
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm 
            ON pm.post_id = p.ID 
            AND pm.meta_key = '_tpt_price'
        WHERE p.post_parent = %d
          AND p.post_type IN ('topics','topic')
          AND p.post_status = 'publish'
        ORDER BY p.menu_order ASC
    ", $course_id));

    if (!$topics) return;
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const topics = <?php echo json_encode($topics); ?>;
            const headers = document.querySelectorAll('.tutor-accordion-item-header');

            headers.forEach(header => {
                const titleText = header.textContent.trim();
                const matched = topics.find(t => titleText.includes(t.post_title));
                if (!matched) return;

                const price = parseInt(matched.price || 0);
                if (!price || header.querySelector('.tpt-topic-badge')) return;

                const badge = document.createElement('span');
                badge.className = 'tpt-topic-badge';
                badge.textContent = `🔒 Rp ${price.toLocaleString('id-ID')}`;
                header.appendChild(badge);
            });
        });
    </script>

    <style>
        .tpt-topic-badge {
            display: inline-block;
            background: #FFE4E9;
            color: #ED2D56;
            font-size: 12px;
            font-weight: 600;
            border-radius: 8px;
            padding: 4px 10px;
            margin-left: 10px;
            vertical-align: middle;
            transition: all 0.25s ease;
        }

        .tpt-topic-badge:hover {
            background: #ED2D56;
            color: #fff;
        }
    </style>
<?php
});


/**
 * 🧩 PATCH: Dinamis badge di accordion (Login user → tampil order/progress)
 */
add_action('wp_footer', function () {
    if (!is_singular('courses')) return;

    $user_id = get_current_user_id();
    if (!$user_id) return; // biarkan locked jika belum login

    global $wpdb;
    $course_id = get_the_ID();
    $completed = get_user_meta($user_id, '_tpt_completed_topics', true) ?: [];
    $purchased = get_user_meta($user_id, '_tpt_purchased_topics', true) ?: [];

    $topics = $wpdb->get_results($wpdb->prepare("
        SELECT t.ID, t.post_title, p2.meta_value AS wc_id, pm_price.meta_value AS price
        FROM {$wpdb->posts} t
        LEFT JOIN {$wpdb->postmeta} p2 ON p2.post_id = t.ID AND p2.meta_key = '_tpt_wc_id'
        LEFT JOIN {$wpdb->postmeta} pm_price ON pm_price.post_id = t.ID AND pm_price.meta_key = '_tpt_price'
        WHERE t.post_parent = %d
          AND t.post_type IN ('topics','topic')
          AND t.post_status='publish'
        ORDER BY t.menu_order ASC
    ", $course_id));
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const topics = <?php echo json_encode($topics); ?>;
            const completed = <?php echo json_encode($completed); ?>;
            const purchased = <?php echo json_encode($purchased); ?>;

            const normalize = arr => Array.isArray(arr) ? arr.map(v => parseInt(v)) : [];
            const completedInt = normalize(completed);
            const purchasedInt = normalize(purchased);

            document.querySelectorAll('.tutor-accordion-item-header').forEach(header => {
                const title = header.textContent.trim();
                const topic = topics.find(t => title.includes(t.post_title));
                if (!topic) return;

                const topicId = parseInt(topic.ID);
                const price = parseInt(topic.price || 0);

                const badge = header.querySelector('.tpt-topic-badge');
                if (!badge) return;

                badge.innerHTML = '';
                badge.style = 'padding:4px 10px;border-radius:8px;font-size:12px;font-weight:600;';

                if (completedInt.includes(topicId)) {
                    badge.style.background = '#D4EDDA';
                    badge.style.color = '#155724';
                    badge.innerHTML = '✅ Order: Completed';
                } else if (purchasedInt.includes(topicId)) {
                    badge.style.background = '#D1ECF1';
                    badge.style.color = '#0C5460';
                    badge.innerHTML = '⏳ Purchased (On Hold)';
                } else {
                    badge.style.background = '#FFE4E9';
                    badge.style.color = '#ED2D56';
                    badge.innerHTML = `🔒 Locked · Rp ${price.toLocaleString('id-ID')}`;
                }
            });
        });
    </script>
<?php
});
