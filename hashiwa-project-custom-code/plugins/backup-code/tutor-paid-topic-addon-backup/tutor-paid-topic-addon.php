<?php
/*
Plugin Name: Tutor Paid Topic Addon (Rupiah) - Stable v1.7.0
Description: Simpan harga per topic per course di Tutor LMS React Builder. Support AJAX save harga & buy per topic.
Version: 1.7.0
Author: Puji Ermanto
Author URI: https://pujiermanto-blog.vercel.app
License: GPLv2 or later
Text Domain: tutor-paid-topic-addon
*/

if (!defined('ABSPATH')) exit;

/* =======================================================
   🔹 Include additional modules
======================================================= */
require_once plugin_dir_path(__FILE__) . 'includes/custom-registration-role.php';
require_once plugin_dir_path(__FILE__) . 'includes/injecting-frontend.php';

/* =======================================================
   1️⃣ Buat tabel custom
======================================================= */
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table = "wpsu_tutor_topic_price"; // hardcode ke tabel yang benar
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        course_id BIGINT(20) UNSIGNED NOT NULL,
        topic_id BIGINT(20) UNSIGNED NOT NULL,
        topic_title VARCHAR(255) NOT NULL,
        price INT(11) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_topic (course_id, topic_id)
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
        '1.7.0',
        true
    );

    wp_localize_script('tpt-addon-script', 'TPT_Ajax', [
        'resturl' => esc_url(rest_url('tutor-paid-topic/v1/')),
        'nonce'   => wp_create_nonce('wp_rest')
    ]);
});

/* =======================================================
   6️⃣ Frontend Course Card Badge Harga
======================================================= */
add_action('wp_enqueue_scripts', function () {
    if (!is_post_type_archive('courses') && !is_tax('course-category') && !is_front_page()) return;

    wp_enqueue_script(
        'tpt-frontend-script',
        plugin_dir_url(__FILE__) . 'tutor-paid-topic-frontend.js',
        ['jquery'],
        '1.0.0',
        true
    );

    wp_localize_script('tpt-frontend-script', 'TPT_Ajax', [
        'resturl' => esc_url(rest_url('tutor-paid-topic/v1/')),
        'nonce'   => wp_create_nonce('wp_rest')
    ]);
});

/* =======================================================
   3️⃣ REST API
======================================================= */
add_action('rest_api_init', function () {
    global $wpdb;
    $table = "wpsu_tutor_topic_price";
    $table_posts = $wpdb->base_prefix . 'posts';

    // 🔹 Cek apakah user sudah membeli topic
    register_rest_route('tutor-paid-topic/v1', '/check-access', [
        'methods' => 'GET',
        'callback' => function ($req) use ($wpdb) {
            $user_id = get_current_user_id();
            $topic_id = intval($req['topic_id'] ?? 0);
            $course_id = intval($req['course_id'] ?? 0);

            if (!$topic_id || !$course_id) {
                return ['access' => false, 'reason' => 'missing_ids'];
            }
            if (!$user_id) {
                return ['access' => false, 'reason' => 'not_logged_in'];
            }

            $order_exists = $wpdb->get_var($wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->prefix}posts p
            INNER JOIN {$wpdb->prefix}postmeta pm_course ON p.ID=pm_course.post_id AND pm_course.meta_key='_tutor_order_course_id'
            INNER JOIN {$wpdb->prefix}postmeta pm_topic ON p.ID=pm_topic.post_id AND pm_topic.meta_key='_tutor_order_topic_id'
            INNER JOIN {$wpdb->prefix}postmeta pm_user ON p.ID=pm_user.post_id AND pm_user.meta_key='_tutor_order_user_id'
            WHERE p.post_type='tutor_order'
              AND p.post_status='completed'
              AND pm_course.meta_value=%d
              AND pm_topic.meta_value=%d
              AND pm_user.meta_value=%d
            LIMIT 1
        ", $course_id, $topic_id, $user_id));

            return [
                'access' => (bool)$order_exists,
                'debug' => [
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                    'topic_id' => $topic_id,
                    'found_order' => $order_exists
                ]
            ];
        },
        'permission_callback' => '__return_true'
    ]);


    // 🔹 Get topic_id by title
    // register_rest_route('tutor-paid-topic/v1', '/get-topic-id', [
    //     'methods' => 'GET',
    //     'callback' => function ($req) use ($wpdb, $table_posts) {
    //         $title = sanitize_text_field($req['title'] ?? '');
    //         if (!$title) return ['topic_id' => 0];

    //         // Gunakan LIKE agar lebih toleran terhadap variasi spasi/HTML
    //         $topic_id = $wpdb->get_var($wpdb->prepare(
    //             "SELECT ID FROM {$table_posts} 
    //  WHERE post_type='topics' 
    //  AND post_title = %s 
    //  ORDER BY ID ASC LIMIT 1",
    //             $title
    //         ));

    //         return ['topic_id' => intval($topic_id)];
    //     },
    //     'permission_callback' => '__return_true'
    // ]);
    register_rest_route('tutor-paid-topic/v1', '/get-topic-id', [
        'methods' => 'GET',
        'callback' => function ($req) use ($wpdb, $table_posts) {
            $title = sanitize_text_field($req['title'] ?? '');
            if (!$title) return ['topic_id' => 0];

            // Gunakan LOWER dan LIKE agar tidak sensitif kapital/spasi
            $topic_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$table_posts}
             WHERE post_type='topics'
             AND LOWER(TRIM(post_title)) LIKE LOWER(TRIM(%s))
             ORDER BY ID ASC LIMIT 1",
                $title
            ));

            return ['topic_id' => intval($topic_id)];
        },
        'permission_callback' => '__return_true'
    ]);


    // 🔹 Save price
    register_rest_route('tutor-paid-topic/v1', '/save-price', [
        'methods' => 'POST',
        'callback' => function ($req) use ($wpdb, $table) {
            $data = $req->get_json_params();
            $title = trim(sanitize_text_field($data['title'] ?? ''));
            $price = intval($data['price'] ?? 0);
            $course_id = intval($data['course_id'] ?? 0);
            $topic_id = intval($data['topic_id'] ?? 0);

            if (!$title || !$course_id || !$topic_id) {
                return new WP_Error('invalid_data', 'Judul, Course ID atau Topic ID kosong.', ['status' => 400]);
            }

            $wpdb->replace($table, [
                'course_id'   => $course_id,
                'topic_id'    => $topic_id,
                'topic_title' => $title,
                'price'       => $price
            ]);

            // Update regular course price kalau topik pertama
            $first_topic_title = $wpdb->get_var($wpdb->prepare(
                "SELECT post_title FROM {$wpdb->prefix}posts 
                 WHERE post_type='topics' AND post_parent=%d
                 ORDER BY menu_order ASC LIMIT 1",
                $course_id
            ));
            $is_first = ($first_topic_title && strtolower(trim($first_topic_title)) === strtolower($title));
            if ($is_first) {
                update_post_meta($course_id, '_tutor_course_price_type', 'paid');
                update_post_meta($course_id, '_tutor_course_price', $price);
                update_post_meta($course_id, '_regular_price', $price);
                update_post_meta($course_id, '_sale_price', '');
            }

            return [
                'success' => true,
                'message' => "Harga topik '{$title}' disimpan (Rp {$price})",
                'synced'  => $is_first
            ];
        },
        'permission_callback' => '__return_true'
    ]);

    // 🔹 Get price
    register_rest_route('tutor-paid-topic/v1', '/get-price', [
        'methods' => 'GET',
        'callback' => function ($req) use ($wpdb, $table) {
            $title = sanitize_text_field($req['title'] ?? '');
            $course_id = intval($req['course_id'] ?? 0);

            if (!$title || !$course_id) return ['price' => 0];

            $data = $wpdb->get_row($wpdb->prepare(
                "SELECT price FROM $table WHERE course_id=%d AND topic_title=%s",
                $course_id,
                $title
            ));
            return $data ?: ['price' => 0];
        },
        'permission_callback' => '__return_true'
    ]);

    // 🔹 Buy topic (per topik)
    register_rest_route('tutor-paid-topic/v1', '/buy-topic', [
        'methods' => 'POST',
        'callback' => function ($req) use ($wpdb, $table) {
            $user_id = get_current_user_id();
            if (!$user_id) return new WP_Error('not_logged_in', 'User harus login', ['status' => 401]);

            $data = $req->get_json_params();
            $topic_id = intval($data['topic_id'] ?? 0);
            if (!$topic_id) return new WP_Error('invalid_data', 'Topic ID kosong', ['status' => 400]);

            $price_row = $wpdb->get_row($wpdb->prepare("SELECT course_id, price FROM $table WHERE topic_id=%d", $topic_id));
            if (!$price_row) return new WP_Error('no_price', 'Harga topik belum ditentukan', ['status' => 400]);

            $course_id = $price_row->course_id;
            $price = $price_row->price;

            // Simulasi order Tutor LMS
            $wpdb->insert("{$wpdb->prefix}posts", [
                'post_type'   => 'tutor_order',
                'post_status' => 'completed',
                'post_author' => $user_id,
                'post_title'  => "Order Topic {$topic_id}",
                'post_date'   => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1)
            ]);
            $order_id = $wpdb->insert_id;

            // Meta order
            add_post_meta($order_id, '_tutor_order_course_id', $course_id);
            add_post_meta($order_id, '_tutor_order_topic_id', $topic_id);
            add_post_meta($order_id, '_tutor_order_user_id', $user_id);
            add_post_meta($order_id, '_tutor_order_total', $price);

            return ['success' => true, 'order_id' => $order_id, 'message' => "Topik berhasil dibeli"];
        },
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ]);
});

/* =======================================================
   4️⃣ Batasi akses topic berdasarkan pembayaran (Versi Debug Aman)
======================================================= */
add_action('template_redirect', function () {
    if (!is_user_logged_in()) return;
    if (!is_singular('lesson')) return;

    global $post, $wpdb;
    $lesson_id = $post->ID;
    $course_id = tutor_utils()->get_course_id_by_lesson($lesson_id);

    // Ambil topic ID dengan cara aman
    $topic_id = wp_get_post_parent_id($lesson_id);
    if (!$topic_id) {
        // fallback: ambil dari meta jika ada
        $topic_id = get_post_meta($lesson_id, '_tutor_topic_id', true);
    }

    $user_id = get_current_user_id();
    $topic_title = get_the_title($topic_id);

    // Debug log
    error_log("🔎 [TPT DEBUG] User=$user_id | Course=$course_id | Topic=$topic_id ($topic_title)");

    if (!$course_id || !$topic_id) {
        error_log("⚠️ [TPT DEBUG] Missing course_id/topic_id → akses dilewatkan.");
        return;
    }

    // Cek apakah topik pertama → selalu bebas
    $first_topic_title = $wpdb->get_var($wpdb->prepare("
        SELECT post_title FROM {$wpdb->posts}
        WHERE post_type='topics' AND post_parent=%d
        ORDER BY menu_order ASC LIMIT 1
    ", $course_id));
    if ($first_topic_title && strtolower($first_topic_title) === strtolower($topic_title)) {
        error_log("✅ [TPT DEBUG] Topik pertama: akses otomatis diizinkan.");
        return;
    }

    // Cek apakah topik punya harga
    $price_row = $wpdb->get_row($wpdb->prepare("
        SELECT price FROM wpsu_tutor_topic_price WHERE topic_id=%d
    ", $topic_id));

    if (!$price_row || $price_row->price <= 0) {
        error_log("ℹ️ [TPT DEBUG] Topik tanpa harga, akses dibuka.");
        return;
    }

    // Cek order
    $order_exists = $wpdb->get_var($wpdb->prepare("
        SELECT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm_course ON p.ID=pm_course.post_id AND pm_course.meta_key='_tutor_order_course_id'
        INNER JOIN {$wpdb->postmeta} pm_topic ON p.ID=pm_topic.post_id AND pm_topic.meta_key='_tutor_order_topic_id'
        INNER JOIN {$wpdb->postmeta} pm_user ON p.ID=pm_user.post_id AND pm_user.meta_key='_tutor_order_user_id'
        WHERE p.post_type='tutor_order'
          AND p.post_status='completed'
          AND pm_course.meta_value=%d
          AND pm_topic.meta_value=%d
          AND pm_user.meta_value=%d
        LIMIT 1
    ", $course_id, $topic_id, $user_id));

    if ($order_exists) {
        error_log("✅ [TPT DEBUG] Order ditemukan: #$order_exists → akses diizinkan.");
        return;
    }

    // Jika tidak ditemukan order
    error_log("⛔ [TPT DEBUG] Tidak ada order untuk user $user_id → redirect ke /cart-2");
    wp_redirect(add_query_arg([
        'locked_topic' => urlencode($topic_title),
        'course_id' => $course_id
    ], site_url('/cart-2')));
    exit;
});


/* =======================================================
   Enqueue Global Frontend Script untuk Buy Topic di Dashboard
======================================================= */
add_action('wp_enqueue_scripts', function () {
    // Hanya load di halaman single course / lesson
    if (!is_singular(['courses', 'tutor_course', 'lesson'])) return;

    wp_register_script(
        'tpt-frontend-global',
        plugin_dir_url(__FILE__) . 'tutor-paid-topic-global.js',
        ['jquery'],
        '1.7.0',
        true
    );

    wp_localize_script('tpt-frontend-global', 'TPT_Ajax', [
        'resturl' => esc_url(rest_url('tutor-paid-topic/v1/')),
        'nonce'   => wp_create_nonce('wp_rest')
    ]);

    wp_enqueue_script('tpt-frontend-global');
});
/* =======================================================
   🧩 FINAL FIX (versi sinkronisasi aman)
======================================================= */
add_action('wp_footer', function () {
    if (!is_singular(['courses', 'tutor_course', 'lesson'])) return;
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const sidebar = document.querySelector('.tutor-course-single-sidebar-wrapper');
            if (!sidebar) return;

            <?php
            $course_id_real = get_post_type() === 'courses' || get_post_type() === 'tutor_course'
                ? get_the_ID()
                : tutor_utils()->get_course_id_by_lesson(get_the_ID());
            ?>
            const courseId = <?php echo intval($course_id_real); ?>;

            const topics = sidebar.querySelectorAll('.tutor-course-topic');
            if (!topics.length) return;

            topics.forEach((topic, i) => {
                if (i === 0) return; // topik pertama tetap gratis
                const header = topic.querySelector('.tutor-course-topic-title');
                if (!header || header.querySelector('.tpt-locked')) return;

                // 🧭 Ambil topic ID aman
                let topicId = null;
                const topicClass = topic.className.match(/tutor-course-topic-(\d+)/);
                if (topicClass) topicId = topicClass[1];
                else if (topic.dataset.topicId) topicId = topic.dataset.topicId;
                else {
                    const firstLesson = topic.querySelector('a[href*="/lessons/"]');
                    if (firstLesson) {
                        const href = firstLesson.getAttribute('href');
                        const match = href.match(/lesson-(\d+)/);
                        if (match) topicId = match[1];
                    }
                }
                if (!topicId) return;

                const lessons = topic.querySelectorAll('a[href*="/lessons/"], a[href*="/quizzes/"]');
                if (!lessons.length) return;

                const badge = document.createElement('span');
                badge.className = 'tpt-locked';
                badge.style.cssText = 'color:#ED2D56;font-weight:600;margin-left:8px;';
                header.appendChild(badge);

                const setLocked = () => {
                    badge.textContent = ' 🔒 Locked';
                    lessons.forEach(a => {
                        a.style.pointerEvents = 'none';
                        a.style.opacity = '0.5';
                    });
                };
                const setUnlocked = () => {
                    badge.textContent = ' 🔓 Unlocked';
                    lessons.forEach(a => {
                        a.style.pointerEvents = 'auto';
                        a.style.opacity = '1';
                    });
                };

                const buyBtn = document.createElement('button');
                buyBtn.textContent = 'Buy Topic';
                buyBtn.style.cssText = 'background:#ED2D56;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-weight:600;margin-left:10px;';
                header.appendChild(buyBtn);

                // 🔍 Auto check status akses
                fetch(`${TPT_Ajax.resturl}check-access?topic_id=${topicId}&course_id=${courseId}`, {
                        headers: {
                            'X-WP-Nonce': TPT_Ajax.nonce
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res?.access) {
                            setUnlocked();
                            buyBtn.remove();
                        } else setLocked();
                    });

                // 💳 Buy handler
                buyBtn.addEventListener('click', e => {
                    e.preventDefault();
                    buyBtn.disabled = true;
                    buyBtn.textContent = 'Processing...';

                    fetch(TPT_Ajax.resturl + 'buy-topic', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': TPT_Ajax.nonce
                            },
                            body: JSON.stringify({
                                topic_id: topicId
                            })
                        })
                        .then(r => r.json())
                        .then(resp => {
                            if (resp.success) {
                                console.log(`✅ Pembelian sukses → Topic ${topicId}`);
                                // 🔄 Re-check akses agar langsung update
                                return fetch(`${TPT_Ajax.resturl}check-access?topic_id=${topicId}&course_id=${courseId}`, {
                                        headers: {
                                            'X-WP-Nonce': TPT_Ajax.nonce
                                        }
                                    })
                                    .then(r => r.json())
                                    .then(recheck => {
                                        if (recheck?.access) {
                                            setUnlocked();
                                            buyBtn.remove();
                                        }
                                    });
                            } else {
                                alert('Gagal membeli topik!');
                                buyBtn.disabled = false;
                                buyBtn.textContent = 'Buy Topic';
                            }
                        })
                        .catch(err => {
                            console.error('🚨 Error Buy Topic:', err);
                            buyBtn.disabled = false;
                            buyBtn.textContent = 'Buy Topic';
                        });
                });
            });
        });
    </script>
<?php
});

/* =======================================================
   8️⃣ Dashboard Course Enhancement: Description + Progress + Price + Lock
======================================================= */
add_action('wp_footer', function () {
    if (!is_singular(['courses', 'tutor_course'])) return;

    global $post, $wpdb;
    $course_id = $post->ID;
    $course_desc = get_post_field('post_content', $course_id);
    $user_id = get_current_user_id();

    // Hitung progress (safe)
    $lessons = tutor_utils()->get_lessons($course_id);

    // pastikan selalu array agar count() dan foreach aman
    if (!is_array($lessons)) {
        $lessons = [];
    }

    $completed = 0;
    if ($user_id) {
        foreach ($lessons as $lesson) {
            $status = tutor_utils()->get_lesson_progress($lesson->ID, $user_id);
            if ($status === 'completed') {
                $completed++;
            }
        }
    }

    $total = count($lessons);
    $percent = $total ? round(($completed / $total) * 100) : 0;
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const wrapper = document.querySelector('.tutor-course-spotlight-wrapper');
            if (!wrapper) return;

            // 🔹 Tambahkan deskripsi
            const descDiv = document.createElement('div');
            descDiv.className = 'tpt-course-desc';
            descDiv.style.cssText = 'margin-bottom:15px;padding:10px;border-left:4px solid #ED2D56;background:#fff;border-radius:6px;';
            descDiv.innerHTML = `<?php echo wp_kses_post($course_desc); ?>`;
            wrapper.prepend(descDiv);

            // 🔹 Tambahkan progress bar
            const progressDiv = document.createElement('div');
            progressDiv.className = 'tpt-course-progress';
            progressDiv.style.cssText = 'margin-bottom:15px;';
            progressDiv.innerHTML = `
        <div style="background:#f0f0f0;border-radius:8px;height:16px;overflow:hidden;">
            <div style="width:<?php echo $percent; ?>%;background:#ED2D56;height:100%;transition:width 0.5s;"></div>
        </div>
        <small style="display:block;margin-top:4px;font-size:12px;color:#555;"><?php echo $percent; ?>% Completed</small>
    `;
            wrapper.prepend(progressDiv);

            // 🔹 Lock lesson & tambahkan harga + Buy Topic
            const sidebar = document.querySelector('.tutor-course-single-sidebar-wrapper');
            if (!sidebar) return;

            const topics = sidebar.querySelectorAll('.tutor-course-topic');
            if (!topics.length) return;

            topics.forEach((topic, i) => {
                if (i === 0) return; // topik pertama tetap aktif

                const header = topic.querySelector('.tutor-course-topic-title');
                if (!header || header.querySelector('.tpt-locked')) return;

                // 🔹 Badge Locked
                const badge = document.createElement('span');
                badge.className = 'tpt-locked';
                badge.textContent = ' 🔒 Locked';
                badge.style.cssText = 'color:#ED2D56;font-weight:600;margin-left:8px;';
                header.appendChild(badge);

                // 🔹 Ambil lesson link & ID
                const lessons = topic.querySelectorAll('a[href*="/lessons/"], a[href*="/quizzes/"]');
                if (!lessons.length) return;
                let topicId = null;
                const topicClass = topic.className.match(/tutor-course-topic-(\d+)/);
                if (topicClass) topicId = topicClass[1];

                // 🔹 Badge Harga Topik
                fetch(TPT_Ajax.resturl + 'get-price?title=' + encodeURIComponent(header.textContent.trim()) + '&course_id=<?php echo $course_id; ?>', {
                        headers: {
                            'X-WP-Nonce': TPT_Ajax.nonce
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data?.price && data.price > 0) {
                            const priceBadge = document.createElement('span');
                            priceBadge.className = 'tpt-price-badge';
                            priceBadge.textContent = `Rp ${Number(data.price).toLocaleString()}`;
                            priceBadge.style.cssText = 'margin-left:10px;font-size:13px;background:#ED2D56;color:#fff;padding:2px 8px;border-radius:8px;font-weight:600;';
                            header.appendChild(priceBadge);
                        }
                    });

                // 🔹 Tombol Buy Topic
                const buyBtn = document.createElement('button');
                buyBtn.textContent = 'Buy Topic';
                buyBtn.style.cssText = 'background:#ED2D56;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-weight:600;margin-left:10px;';
                header.appendChild(buyBtn);

                buyBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    fetch(TPT_Ajax.resturl + 'buy-topic', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': TPT_Ajax.nonce
                            },
                            body: JSON.stringify({
                                topic_id: topicId
                            })
                        })
                        .then(r => r.json())
                        .then(resp => {
                            if (resp.success) {
                                badge.textContent = ' 🔓 Unlocked';
                                buyBtn.remove();
                                lessons.forEach(a => a.style.pointerEvents = 'auto');
                                console.log('Topik dibuka!', resp);
                            } else console.warn(resp);
                        }).catch(console.error);
                });

                // 🔹 Lock lesson links
                lessons.forEach(a => {
                    a.style.pointerEvents = 'none';
                    a.style.opacity = '0.5';
                });
            });
        });
    </script>
<?php
});

/* =======================================================
   8️⃣ Dashboard Course Enhancement: Description + Progress + Price + Lock
======================================================= */
add_action('wp_footer', function () {
    if (!is_singular(['courses', 'tutor_course'])) return;

    global $post, $wpdb;
    $course_id = $post->ID;
    $course_desc = get_post_field('post_content', $course_id);
    $user_id = get_current_user_id();

    // Hitung progress
    $lessons = tutor_utils()->get_lessons($course_id);
    $completed = 0;

    if ($user_id && is_array($lessons)) {
        foreach ($lessons as $lesson) {
            $status = tutor_utils()->get_lesson_progress($lesson->ID, $user_id);
            if ($status === 'completed') {
                $completed++;
            }
        }
    }

    $total = is_array($lessons) ? count($lessons) : 0;
    $percent = $total ? round(($completed / $total) * 100) : 0;
?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const wrapper = document.querySelector('.tutor-course-spotlight-wrapper');
            if (!wrapper) return;

            // 🔹 Tambahkan deskripsi
            const descDiv = document.createElement('div');
            descDiv.className = 'tpt-course-desc';
            descDiv.style.cssText = 'margin-bottom:15px;padding:10px;border-left:4px solid #ED2D56;background:#fff;border-radius:6px;';
            descDiv.innerHTML = `<?php echo wp_kses_post($course_desc); ?>`;
            wrapper.prepend(descDiv);

            // 🔹 Tambahkan progress bar
            const progressDiv = document.createElement('div');
            progressDiv.className = 'tpt-course-progress';
            progressDiv.style.cssText = 'margin-bottom:15px;';
            progressDiv.innerHTML = `
        <div style="background:#f0f0f0;border-radius:8px;height:16px;overflow:hidden;">
            <div style="width:<?php echo $percent; ?>%;background:#ED2D56;height:100%;transition:width 0.5s;"></div>
        </div>
        <small style="display:block;margin-top:4px;font-size:12px;color:#555;"><?php echo $percent; ?>% Completed</small>
    `;
            wrapper.prepend(progressDiv);

            // 🔹 Lock lesson & tambahkan harga + Buy Topic
            const sidebar = document.querySelector('.tutor-course-single-sidebar-wrapper');
            if (!sidebar) return;

            const topics = sidebar.querySelectorAll('.tutor-course-topic');
            if (!topics.length) return;

            topics.forEach((topic, i) => {
                if (i === 0) return; // topik pertama tetap aktif

                const header = topic.querySelector('.tutor-course-topic-title');
                if (!header || header.querySelector('.tpt-locked')) return;

                // 🔹 Badge Locked
                const badge = document.createElement('span');
                badge.className = 'tpt-locked';
                badge.textContent = ' 🔒 Locked';
                badge.style.cssText = 'color:#ED2D56;font-weight:600;margin-left:8px;';
                header.appendChild(badge);

                // 🔹 Ambil lesson link & ID
                const lessons = topic.querySelectorAll('a[href*="/lessons/"], a[href*="/quizzes/"]');
                if (!lessons.length) return;
                const topicId = lessons[0].dataset.lessonId;

                // 🔹 Badge Harga Topik
                fetch(TPT_Ajax.resturl + 'get-price?title=' + encodeURIComponent(header.textContent.trim()) + '&course_id=<?php echo $course_id; ?>', {
                        headers: {
                            'X-WP-Nonce': TPT_Ajax.nonce
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data?.price && data.price > 0) {
                            const priceBadge = document.createElement('span');
                            priceBadge.className = 'tpt-price-badge';
                            priceBadge.textContent = `Rp ${Number(data.price).toLocaleString()}`;
                            priceBadge.style.cssText = 'margin-left:10px;font-size:13px;background:#ED2D56;color:#fff;padding:2px 8px;border-radius:8px;font-weight:600;';
                            header.appendChild(priceBadge);
                        }
                    });

                // 🔹 Tombol Buy Topic
                const buyBtn = document.createElement('button');
                buyBtn.textContent = 'Buy Topic';
                buyBtn.style.cssText = 'background:#ED2D56;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;font-weight:600;margin-left:10px;';
                header.appendChild(buyBtn);

                // 💳 Buy handler
                buyBtn.addEventListener('click', e => {
                    e.preventDefault();
                    buyBtn.disabled = true;
                    buyBtn.textContent = 'Processing...';

                    fetch(TPT_Ajax.resturl + 'buy-topic', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': TPT_Ajax.nonce
                            },
                            body: JSON.stringify({
                                topic_id: topicId
                            })
                        })
                        .then(r => r.json())
                        .then(resp => {
                            if (resp.success) {
                                console.log(`✅ Pembelian sukses → Topic ${topicId}`);

                                // 🔓 Langsung ubah tampilan tanpa setUnlocked()
                                badge.textContent = ' 🔓 Unlocked';
                                lessons.forEach(a => {
                                    a.style.pointerEvents = 'auto';
                                    a.style.opacity = '1';
                                });
                                buyBtn.remove();

                                // 🔄 Background recheck (non-blocking)
                                fetch(`${TPT_Ajax.resturl}check-access?topic_id=${topicId}&course_id=${courseId}`, {
                                        headers: {
                                            'X-WP-Nonce': TPT_Ajax.nonce
                                        }
                                    })
                                    .then(r => r.json())
                                    .then(recheck => {
                                        if (recheck?.access)
                                            console.log(`🔁 Recheck OK untuk topic ${topicId}`);
                                        else
                                            console.warn(`⚠️ Recheck belum synced untuk topic ${topicId}`);
                                    })
                                    .catch(err => console.error('🚨 Error recheck:', err));
                            } else {
                                alert('Gagal membeli topik!');
                                buyBtn.disabled = false;
                                buyBtn.textContent = 'Buy Topic';
                            }
                        })
                        .catch(err => {
                            console.error('🚨 Error Buy Topic:', err);
                            buyBtn.disabled = false;
                            buyBtn.textContent = 'Buy Topic';
                        });
                });

                // 🔹 Lock lesson links
                lessons.forEach(a => {
                    a.style.pointerEvents = 'none';
                    a.style.opacity = '0.5';
                });
            });
        });
    </script>
    <?php
});


/* =======================================================
   9️⃣ Replace Tutor LMS Logo with Hashiwa LMS Logo in Admin
======================================================= */
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'tutor_course_page_create-course') :
    ?>
        <script>
            (function() {
                function replaceTutorLogo() {
                    const btn = document.querySelector('button.css-1wb3486');
                    if (!btn) return;

                    const svg = btn.querySelector('svg');
                    if (svg && !svg.dataset.replaced) {
                        svg.outerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 108 24" width="108" height="24" data-replaced="true">
                        <path fill="#ED2D56" d="M10 0h88v24H10z"/>
                        <text x="54" y="16" font-size="12" font-family="Arial" fill="#fff" text-anchor="middle">Hashiwa LMS</text>
                    </svg>
                `;
                    }
                }

                // Observe seluruh body, tapi filter hanya perubahan di button
                const observer = new MutationObserver(() => replaceTutorLogo());
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });

                // Jalankan sekali langsung
                replaceTutorLogo();
            })();
        </script>
<?php
    endif;
});
