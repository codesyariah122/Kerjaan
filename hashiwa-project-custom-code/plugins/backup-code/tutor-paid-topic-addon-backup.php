<?php
/*
Plugin Name: Tutor Paid Topic Addon (Rupiah) - All-in-One
Plugin URI:  https://tokoweb.co/
Description: Tambah harga per topic (Rp) di Tutor LMS Curriculum Builder (React). Save via AJAX saat Save Topic (OK) + fallback save saat Update Course.
Version:     1.0.0
Author:      Puji Ermanto (adapted)
Author URI:  mailto:pujiermanto@gmail.com
License:     GPLv2 or later
Text Domain: tutor-paid-topic-addon
*/

if (!defined('ABSPATH')) exit;

/**
 * ---------------------------
 * Admin: meta box for topic
 * ---------------------------
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'tpa_topic_price_box',
        __('Topic Price', 'tutor-paid-topic-addon'),
        function ($post) {
            $price = get_post_meta($post->ID, '_topic_price', true);
?>
        <label><?php _e('Harga (Rp)', 'tutor-paid-topic-addon'); ?></label><br />
        <input type="number" step="0.01" min="0" name="topic_price" value="<?php echo esc_attr($price); ?>" style="width:120px;" />
        <p class="description"><?php _e('Isi harga untuk topic/bab ini (Rp). Kosongkan / 0 = gratis.', 'tutor-paid-topic-addon'); ?></p>
    <?php
        },
        'tutor_course_topic',
        'side',
        'default'
    );
});

/**
 * Save topic price when saving a topic in classic editor (post)
 */
add_action('save_post_tutor_course_topic', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['topic_price'])) {
        $price = floatval($_POST['topic_price']);
        update_post_meta($post_id, '_topic_price', $price);
    }
});

/**
 * Save topic prices when whole course form submits (fallback)
 * Expecting a hidden input "topic_prices" containing JSON map { topic_id: price }
 */
add_action('tutor_course_saved', function ($course_id, $post_data) {
    if (!isset($_POST['topic_prices'])) return;

    $topic_prices = json_decode(stripslashes($_POST['topic_prices']), true);
    if (!$topic_prices || !is_array($topic_prices)) return;

    foreach ($topic_prices as $topic_id => $price) {
        update_post_meta(intval($topic_id), '_topic_price', floatval($price));
    }
}, 10, 2);


/**
 * AJAX: save single topic price (used by JS on Save Topic)
 */
add_action('wp_ajax_tpa_save_topic_price', function () {
    // capability & nonce if provided
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'no_cap']);
    }

    $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
    $price    = isset($_POST['price']) ? floatval($_POST['price']) : 0;

    if (!$topic_id) {
        wp_send_json_error(['message' => 'no_id']);
    }

    update_post_meta($topic_id, '_topic_price', $price);

    wp_send_json_success(['topic_id' => $topic_id, 'price' => $price]);
});

/**
 * AJAX: get topic price (for showing in collapsed topic list)
 */
add_action('wp_ajax_tpa_get_topic_price', function () {
    $topic_id = intval($_POST['topic_id']);
    $price    = get_post_meta($topic_id, '_topic_price', true);
    wp_send_json_success(['price' => floatval($price)]);
});

/**
 * AJAX: bulk save topic prices (used as fallback when course save is via React/AJAX)
 */
add_action('wp_ajax_tpa_save_topic_prices_bulk', function () {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'no_cap']);
    }

    $topic_prices_raw = isset($_POST['topic_prices']) ? $_POST['topic_prices'] : '';
    if (empty($topic_prices_raw)) {
        wp_send_json_error(['message' => 'no_data']);
    }

    $topic_prices = json_decode(stripslashes($topic_prices_raw), true);
    if (!$topic_prices || !is_array($topic_prices)) {
        wp_send_json_error(['message' => 'invalid']);
    }

    $updated = 0;
    foreach ($topic_prices as $topic_id => $price) {
        // Only accept numeric post IDs
        if (!is_numeric($topic_id)) continue;
        update_post_meta(intval($topic_id), '_topic_price', floatval($price));
        $updated++;
    }

    wp_send_json_success(['updated' => $updated]);
});

/**
 * Add admin scripts + inline JS injection for Curriculum Builder
 */
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if (!$screen) return;
    if (strpos($screen->id, 'create-course') === false && $screen->id !== 'tutor_course') return;

    // Localize some parameters
    $ajax_url = admin_url('admin-ajax.php');
    ?>
    <script>
        (function($) {
            // store temporarily price keyed by a local temporary uid (for topics without ID)
            window.tpa_temp_prices = window.tpa_temp_prices || {}; // { temp_uid: price }
            window.tpa_last_saved_titles = window.tpa_last_saved_titles || []; // for matching after ajax

            // Util: find topic wrapper element from an input[name="title"]
            function findTopicRootFromTitleInput($input) {
                // climb up until we find an element that has the typical buttons area (save-topic / OK or collapse)
                var $el = $input.closest('div');
                for (var i = 0; i < 8; i++) {
                    if (!$el || $el.length === 0) break;
                    // heuristics: container has a button that looks like save/cancel, or data-cy save-topic OR has textarea summary
                    if ($el.find('[data-cy="save-topic"]').length || $el.find('textarea[name="summary"]').length || $el.find('[data-cy="delete-topic"]').length) {
                        return $el;
                    }
                    $el = $el.parent();
                }
                // fallback: return closest ancestor with multiple input[name="title"] sibling structure
                return $input.closest('div');
            }

            // Inject price input to existing topics
            function injectPriceFieldFor($titleInput) {
                var $root = findTopicRootFromTitleInput($titleInput);
                if (!$root || $root.length === 0) return;

                // avoid duplicate
                if ($root.find('.tpa-topic-price').length) return;

                // create input (horizontal, next to title)
                var $price = $('<input type="number" step="0.01" min="0" class="tpa-topic-price tutor-input-field" placeholder="Harga Rp" style="margin-left:10px; width:120px;">');

                // insert immediately after title input
                $titleInput.after($price);

                // try load existing price if data-id exists
                var topicId = $root.attr('data-id') || $root.data('id') || null;
                if (topicId) {
                    // sometimes the element might include the meta value in DOM (rare); otherwise leave blank
                    // We'll also try to read a prefilled data attribute if present
                    var existing = $root.find('input.tpa-topic-price').data('prefill');
                    if (existing) $price.val(existing);
                } else {
                    // assign a temp uid to this root so we can store price until topic gets an ID
                    var tmp = $root.attr('data-tpa-tmp-id');
                    if (!tmp) {
                        tmp = 'tpa_tmp_' + Math.random().toString(36).substr(2, 9);
                        $root.attr('data-tpa-tmp-id', tmp);
                    }
                    // restore if already entered earlier
                    if (window.tpa_temp_prices[tmp] !== undefined) {
                        $price.val(window.tpa_temp_prices[tmp]);
                    }
                }

                // on change store price
                $price.on('input', function() {
                    var v = parseFloat($(this).val()) || 0;
                    var tid = $root.attr('data-id') || $root.data('id') || null;
                    if (tid) {
                        // send immediately? we'll wait till user saves the topic; but we also keep local map
                        // store to DOM data attr
                        $root.data('tpa_saved_price', v);
                    } else {
                        var tmp = $root.attr('data-tpa-tmp-id');
                        if (!tmp) {
                            tmp = 'tpa_tmp_' + Math.random().toString(36).substr(2, 9);
                            $root.attr('data-tpa-tmp-id', tmp);
                        }
                        window.tpa_temp_prices[tmp] = v;
                    }
                });
            }

            // scan existing title fields and inject price inputs
            function scanAndInject() {
                $('input[name="title"]').each(function() {
                    injectPriceFieldFor($(this));
                });
            }

            // run initially after a short wait for React render
            setTimeout(scanAndInject, 800);

            // Mutation observer to inject for newly added topics
            var mo = new MutationObserver(function(muts) {
                muts.forEach(function(m) {
                    m.addedNodes.forEach(function(node) {
                        if (node.nodeType !== 1) return;
                        // find any title inputs inside
                        $(node).find('input[name="title"]').each(function() {
                            injectPriceFieldFor($(this));
                        });
                        // or if node itself is input title
                        if ($(node).is('input[name="title"]')) {
                            injectPriceFieldFor($(node));
                        }
                    });
                });
            });
            mo.observe(document.body, {
                childList: true,
                subtree: true
            });

            /**
             * Intercept Save Topic click (OK)
             * Strategy:
             *  - When user clicks OK (save topic), collect price from the same topic root.
             *  - If element has data-id (topic id), call our own AJAX to save immediately.
             *  - If no data-id, we stash the price using temporary uid. Then we also listen to jQuery.ajaxSuccess
             *    to detect tutor_ajax_update_topic response that returns the created topic_id; we then match by title and apply saved price.
             */

            // helper to call WP AJAX to save price
            function ajaxSavePrice(topicId, price) {
                if (!topicId) return;
                $.post('<?php echo esc_js($ajax_url); ?>', {
                    action: 'tpa_save_topic_price',
                    topic_id: topicId,
                    price: price
                }, function(resp) {
                    // optional: show a tiny success indicator
                    // console.log('tpa saved', resp);
                });
            }

            // hook click for any button that looks like Save/OK in builder
            $(document).on('click', 'button, [role="button"]', function(e) {
                var $btn = $(this);
                // quick heuristics: Tutor builder Save/OK often contains text 'Ok' or data-cy 'save-topic' or has svg + class css-u5asr1
                var text = ($btn.text() || '').trim().toLowerCase();
                var isSaveTopic = $btn.attr('data-cy') === 'save-topic' || text === 'ok' || text === 'save' || $btn.hasClass('css-u5asr1') || $btn.hasClass('tutor-btn');

                if (!isSaveTopic) return;

                // find closest root
                var $root = $btn.closest('div').find('input[name="title"]').first();
                if (!$root || $root.length === 0) {
                    // maybe the button is inside the same root; try climbing
                    $root = $btn.closest('[data-id]').find('input[name="title"]').first();
                }
                if (!$root || $root.length === 0) return;

                var $topicRoot = findTopicRootFromTitleInput($root);
                if (!$topicRoot || $topicRoot.length === 0) return;

                var price = parseFloat($topicRoot.find('.tpa-topic-price').val()) || 0;
                var topicId = $topicRoot.attr('data-id') || $topicRoot.data('id') || null;

                if (topicId) {
                    ajaxSavePrice(topicId, price);
                } else {
                    // store in temp map keyed by tmp id or by title
                    var tmp = $topicRoot.attr('data-tpa-tmp-id');
                    if (!tmp) {
                        tmp = 'tpa_tmp_' + Math.random().toString(36).substr(2, 9);
                        $topicRoot.attr('data-tpa-tmp-id', tmp);
                    }
                    window.tpa_temp_prices[tmp] = price;

                    // also push title to last_saved_titles for matching when ajaxSuccess returns
                    var titleText = $topicRoot.find('input[name="title"]').val() || '';
                    if (titleText) {
                        window.tpa_last_saved_titles.unshift({
                            title: titleText.trim(),
                            tmp: tmp,
                            time: Date.now()
                        });
                        // keep list short
                        if (window.tpa_last_saved_titles.length > 20) window.tpa_last_saved_titles.pop();
                    }
                }
            });

            /**
             * Intercept jQuery global ajaxSuccess to catch the Tutor's topic create/update response
             * Tutor's internal AJAX often posts to admin-ajax.php with action=tutor_ajax_update_topic or
             * may return JSON containing topic object with id and title.
             * We'll try to detect responses that include "topic_id" or "topic" data.
             */
            $(document).ajaxSuccess(function(event, xhr, settings) {
                try {
                    var data = null;
                    // attempt parse JSON response
                    var text = xhr && xhr.responseText ? xhr.responseText : null;
                    if (!text) return;
                    // often response contains JSON, but may be prefixed; try to parse safely
                    // try direct parse
                    try {
                        data = JSON.parse(text);
                    } catch (err) {
                        // try to extract json substring
                        var m = text.match(/\{[\s\S]*\}/);
                        if (m && m[0]) {
                            try {
                                data = JSON.parse(m[0]);
                            } catch (e) {
                                data = null;
                            }
                        }
                    }
                    if (!data) return;

                    // Check common shapes: data.data.topic_id or data.topic.id or data.topic_id
                    var topicId = null,
                        topicTitle = null;
                    if (data.topic_id) topicId = data.topic_id;
                    if (data.data && data.data.topic_id) topicId = data.data.topic_id;
                    if (data.data && data.data.topic && data.data.topic.id) {
                        topicId = data.data.topic.id;
                        topicTitle = data.data.topic.title || null;
                    }
                    if (data.topic && data.topic.id) {
                        topicId = data.topic.id;
                        topicTitle = data.topic.title || null;
                    }
                    // some responses: { success: true, data: { id: 123, title: '...' } }
                    if (data.data && data.data.id) {
                        topicId = data.data.id;
                        topicTitle = data.data.title || null;
                    }

                    if (!topicId) return;

                    // If we have topicTitle, try to match quickly
                    if (!topicTitle) {
                        // fallback: try to find dom element which recently was saved (use last_saved_titles list)
                        if (window.tpa_last_saved_titles && window.tpa_last_saved_titles.length) {
                            // find first candidate not too old
                            var now = Date.now();
                            var candidate = null;
                            for (var i = 0; i < window.tpa_last_saved_titles.length; i++) {
                                var it = window.tpa_last_saved_titles[i];
                                if (now - it.time < 15000) {
                                    candidate = it;
                                    break;
                                }
                            }
                            if (candidate) {
                                topicTitle = candidate.title;
                                var tmp = candidate.tmp;
                                // find DOM element with same title and attach data-id
                                $('input[name="title"]').each(function() {
                                    var t = $(this).val().trim();
                                    if (t === topicTitle) {
                                        var $root = findTopicRootFromTitleInput($(this));
                                        if ($root && $root.length) {
                                            $root.attr('data-id', topicId);
                                            // if we had temp price for tmp, save it
                                            if (window.tpa_temp_prices[tmp] !== undefined) {
                                                ajaxSavePrice(topicId, window.tpa_temp_prices[tmp]);
                                                delete window.tpa_temp_prices[tmp];
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    } else {
                        // have title — match DOM by title and set data-id and maybe save price
                        $('input[name="title"]').each(function() {
                            var t = ($(this).val() || '').trim();
                            if (t === (topicTitle || '').trim()) {
                                var $root = findTopicRootFromTitleInput($(this));
                                if ($root && $root.length) {
                                    $root.attr('data-id', topicId);
                                    // if root has tmp id and temp price, save
                                    var tmp = $root.attr('data-tpa-tmp-id');
                                    if (tmp && window.tpa_temp_prices[tmp] !== undefined) {
                                        ajaxSavePrice(topicId, window.tpa_temp_prices[tmp]);
                                        delete window.tpa_temp_prices[tmp];
                                    }
                                }
                            }
                        });
                    }

                } catch (e) {
                    // graceful failure
                    // console.log('TPA ajaxSuccess parse error', e);
                }
            });

            // Also on course Save (global fallback) we add hidden input with topic_prices map (for server-side tutor_course_saved)
            $(document).on('submit', 'form#create-course, form#post', function() {
                var map = {};
                $('input.tpa-topic-price').each(function() {
                    var $root = $(this).closest('[data-id], [data-tpa-tmp-id]');
                    var tid = $root.attr('data-id') || $root.data('id') || '';
                    var tmp = $root.attr('data-tpa-tmp-id') || '';
                    var v = parseFloat($(this).val()) || 0;
                    if (tid) map[tid] = v;
                    else if (tmp) map[tmp] = v; // server doesn't understand tmp key; fallback only works for entries with real id
                });
                // put JSON hidden
                if (!$(this).find('input[name="topic_prices"]').length) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'topic_prices',
                        value: JSON.stringify(map)
                    }).appendTo(this);
                } else {
                    $(this).find('input[name="topic_prices"]').val(JSON.stringify(map));
                }
            });

            /* ----------------------------------------------------
   SHOW PRICE IN COLLAPSED TOPIC LIST PANEL
----------------------------------------------------- */

            function injectTopicPriceInList() {
                // Elemen judul topik (collapsed)
                $('.css-1jlm4v3').each(function() {
                    var $title = $(this);
                    var $root = $title.closest('[data-id], [data-tpa-tmp-id]');

                    if (!$root.length) return;

                    var topicId = $root.attr('data-id');
                    if (!topicId) return;

                    // Cegah duplikasi
                    if ($title.find('.tpa-price-label').length) return;

                    $.post(ajaxurl, {
                        action: 'tpa_get_topic_price',
                        topic_id: topicId
                    }, function(r) {
                        if (!r.success) return;

                        var price = parseFloat(r.price);
                        if (price > 0) {
                            var formatted = 'Rp ' + Intl.NumberFormat('id-ID').format(price);

                            $title.append(
                                '<div class="tpa-price-label" style="font-size:12px;margin-top:2px;color:#1f9d55;">' +
                                formatted +
                                '</div>'
                            );
                        }
                    });
                });
            }

            // Jalankan terus karena panel pakai React (DOM berubah)
            setInterval(injectTopicPriceInList, 1000);

            // --------------------------------------------
            // Bulk save topic prices: send map to server
            // --------------------------------------------
            function collectTopicPricesMap() {
                var map = {};
                $('input.tpa-topic-price').each(function() {
                    var $root = $(this).closest('[data-id]');
                    var tid = $root.attr('data-id') || $root.data('id') || '';
                    if (tid && (/^\d+$/).test(tid)) {
                        var v = parseFloat($(this).val()) || 0;
                        map[tid] = v;
                    }
                });
                return map;
            }

            function sendTopicPricesBulk() {
                var map = collectTopicPricesMap();
                if (!map || Object.keys(map).length === 0) return;
                $.post(ajaxurl, {
                    action: 'tpa_save_topic_prices_bulk',
                    topic_prices: JSON.stringify(map)
                }, function(resp) {
                    // optional: console.log('tpa bulk saved', resp);
                });
            }

            // Trigger bulk save when user clicks common Save buttons (Tutor builder may use buttons)
            $(document).on('click', '[data-cy="save-course"], #publish, #save-post, button.save-course', function() {
                setTimeout(sendTopicPricesBulk, 400); // delay to allow Tutor to set data-id
            });

            // Autosave every 10s as extra fallback (lightweight)
            setInterval(sendTopicPricesBulk, 10000);

        })(jQuery);
    </script>
<?php
});

/**
 * ---------------------------
 * Lesson content filter (lock if unpaid)
 * ---------------------------
 */
function tpa_has_paid_topic($user_id, $topic_id)
{
    return get_user_meta($user_id, 'paid_topic_' . $topic_id, true) == 1;
}

add_filter('tutor_lesson_content', function ($content) {
    if (!is_user_logged_in()) return $content;
    global $post;
    if (!$post) return $content;

    $lesson_id = $post->ID;
    $user_id   = get_current_user_id();

    $course_obj = function_exists('tutor_utils') ? tutor_utils()->get_course_by_lesson($lesson_id) : null;
    if (!$course_obj) return $content;
    $course_id = $course_obj->ID;

    $topics = tutor_utils()->get_course_topics($course_id);
    $topic_id = 0;
    foreach ($topics as $topic) {
        $lessons = tutor_utils()->get_topic_lessons($topic->ID);
        foreach ($lessons as $lesson) {
            if ($lesson->ID == $lesson_id) {
                $topic_id = $topic->ID;
                break 2;
            }
        }
    }

    if ($topic_id) {
        $topic_price = floatval(get_post_meta($topic_id, '_topic_price', true));
        if ($topic_price > 0 && !tpa_has_paid_topic($user_id, $topic_id)) {
            $pay_url = add_query_arg([
                'action'   => 'tpa_pay_topic',
                'topic_id' => $topic_id,
            ], home_url('/'));

            return '<div class="paid-topic-lock">
                <p>Topic ini berbayar: Rp ' . number_format($topic_price, 0, ",", ".") . '</p>
                <a href="' . esc_url($pay_url) . '" class="tutor-btn tutor-btn-primary">Bayar & Buka</a>
            </div>';
        }
    }

    return $content;
});

/**
 * Dummy payment handler: mark user as paid for topic
 */
add_action('init', function () {
    if (isset($_GET['action']) && $_GET['action'] === 'tpa_pay_topic' && isset($_GET['topic_id'])) {
        $topic_id = intval($_GET['topic_id']);
        $user_id  = get_current_user_id();
        if ($user_id && $topic_id) {
            update_user_meta($user_id, 'paid_topic_' . $topic_id, 1);
        }
        $lessons = function_exists('tutor_utils') ? tutor_utils()->get_topic_lessons($topic_id) : false;
        if ($lessons) {
            wp_redirect(get_permalink($lessons[0]->ID));
        } else {
            wp_redirect(home_url());
        }
        exit;
    }
});

/**
 * Course price: show first paid topic price (Rp)
 */
add_filter('tutor_course_price', function ($price, $course_id) {
    $topics = tutor_utils()->get_course_topics($course_id);
    foreach ($topics as $topic) {
        $topic_price = floatval(get_post_meta($topic->ID, '_topic_price', true));
        if ($topic_price > 0) return 'Rp ' . number_format($topic_price, 0, ",", ".");
    }
    return 'Rp 0';
}, 10, 2);

// Backup baru :
<?php
/*
Plugin Name: Tutor Paid Topic Addon (Rupiah) - React Builder Ultimate
Description: Tambah harga per topic/bab di Tutor LMS React Builder terbaru. Support AJAX save saat Save Topic + fallback saat Update Course.
Version: 1.4.0
Author: Puji Ermanto
*/

if (!defined('ABSPATH')) exit;

// ---------------------------
// AJAX: ambil harga topik
// ---------------------------
add_action('wp_ajax_tpa_get_topic_price', function () {
    $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
    if (!$topic_id) wp_send_json_error(['message' => 'no_id']);
    $price = get_post_meta($topic_id, '_topic_price', true);
    wp_send_json_success(['price' => floatval($price)]);
});

// ---------------------------
// AJAX: save single topic price
// ---------------------------
add_action('wp_ajax_tpa_save_topic_price', function () {
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'no_cap']);
    $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    if (!$topic_id) wp_send_json_error(['message' => 'no_id']);
    update_post_meta($topic_id, '_topic_price', $price);
    wp_send_json_success(['topic_id' => $topic_id, 'price' => $price]);
});

// ---------------------------
// AJAX: bulk save topic prices (for React builder fallback)
// ---------------------------
add_action('wp_ajax_tpa_save_topic_prices_bulk', function () {
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'no_cap']);
    $raw = isset($_POST['topic_prices']) ? wp_unslash($_POST['topic_prices']) : '';
    if (empty($raw)) wp_send_json_error(['message' => 'no_data']);
    $map = json_decode($raw, true);
    if (!is_array($map)) wp_send_json_error(['message' => 'invalid']);
    $updated = 0;
    foreach ($map as $tid => $price) {
        if (!is_numeric($tid)) continue;
        update_post_meta(intval($tid), '_topic_price', floatval($price));
        $updated++;
    }
    wp_send_json_success(['updated' => $updated]);
});

// ---------------------------
// Filter: tambahkan meta topic_price ke curriculum saat load course
// ---------------------------
add_filter('tutor_course_curriculum', function ($curriculum, $course_id) {
    if (!is_array($curriculum)) return $curriculum;

    foreach ($curriculum as &$item) {
        if (isset($item['post_type']) && $item['post_type'] === 'topics' && isset($item['ID'])) {
            $topic_id = intval($item['ID']);
            $price = get_post_meta($topic_id, '_topic_price', true);
            // Pastikan meta array ada
            $item['meta'] = $item['meta'] ?? [];
            $item['meta']['topic_price'] = floatval($price);
        }
    }
    return $curriculum;
}, 10, 2);

// ---------------------------
// Hook: save course fallback untuk topik baru
// ---------------------------
add_action('tutor_course_saved', function ($course_id, $post_data) {
    if (empty($post_data['curriculum']) || !is_array($post_data['curriculum'])) return;

    // Simpan meta ke post_meta topic
    foreach ($post_data['curriculum'] as &$item) {  // Gunakan &$item untuk modify
        if ($item['type'] === 'topic' && isset($item['meta']['topic_price'])) {
            $topic_id = intval($item['id']);
            update_post_meta($topic_id, '_topic_price', floatval($item['meta']['topic_price']));
            // Sync ke curriculum array (opsional, tapi baik untuk konsistensi)
            $item['meta']['topic_price'] = floatval($item['meta']['topic_price']);
        }
    }

    // Update curriculum post meta dengan data terbaru (jika perlu)
    update_post_meta($course_id, '_tutor_course_curriculum', $post_data['curriculum']);
}, 10, 2);

// ---------------------------
// Admin JS: inject price input di React Builder
// ---------------------------
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if (!$screen) return;
    if (strpos($screen->id, 'create-course') === false && $screen->id !== 'tutor_course') return;

    $ajax_url = admin_url('admin-ajax.php');
?>
    <script>
        (function($) {
            window.tpa_temp_prices = window.tpa_temp_prices || {};

            function injectPrice($titleInput) {
                var $topicItem = $titleInput.closest('div.css-11s3wkf');
                if (!$topicItem.length) return;
                if ($topicItem.find('.tpa-topic-price').length) return;

                var $price = $('<input type="number" class="tpa-topic-price tutor-input-field" placeholder="Harga Rp" style="margin-left:10px;width:120px;">');
                $titleInput.after($price);

                var topicId = $topicItem.data('id') || $topicItem.find('input[name="id"]').val();
                if (topicId) {
                    // Ambil harga dari DB
                    $.post('<?php echo esc_js($ajax_url); ?>', {
                        action: 'tpa_get_topic_price',
                        topic_id: topicId
                    }, function(r) {
                        if (r.success) $price.val(r.data.price);
                    });
                } else {
                    var tmp = $topicItem.attr('data-tpa-tmp-id') || 'tpa_tmp_' + Math.random().toString(36).substr(2, 9);
                    $topicItem.attr('data-tpa-tmp-id', tmp);
                    if (window.tpa_temp_prices[tmp] !== undefined) $price.val(window.tpa_temp_prices[tmp]);
                }

                function savePriceToMeta() {
                    var val = parseFloat($price.val()) || 0;
                    $topicItem.data('meta', $topicItem.data('meta') || {});
                    $topicItem.data('meta').topic_price = val;

                    if (topicId) {
                        // langsung simpan via AJAX untuk topik lama
                        $.post('<?php echo esc_js($ajax_url); ?>', {
                            action: 'tpa_save_topic_price',
                            topic_id: topicId,
                            price: val
                        }, function(resp) {
                            if (resp && resp.success) showTpaToast('Harga topik disimpan');
                        });
                    } else {
                        var tmp = $topicItem.attr('data-tpa-tmp-id');
                        window.tpa_temp_prices[tmp] = val;
                    }
                }

                $price.on('input', savePriceToMeta);

                // Simpan saat klik tombol "Ok" topik
                $topicItem.find('button[data-cy="save-topic"]').off('click.tpa').on('click.tpa', savePriceToMeta);
            }

            function scanTopics() {
                $('input[name="title"]').each(function() {
                    injectPrice($(this));
                });
                // Retry jika belum ada topic dengan ID (untuk kasus load lambat)
                setTimeout(function() {
                    $('input[name="title"]').each(function() {
                        injectPrice($(this));
                    });
                }, 3000);
            }

            $(document).on('tutor_curriculum_loaded', scanTopics);

            setTimeout(scanTopics, 1500);

            // Small toast helper for confirmations
            function showTpaToast(msg) {
                try {
                    var $t = $('<div class="tpa-toast" style="position:fixed;bottom:20px;right:20px;background:#1f9d55;color:#fff;padding:8px 12px;border-radius:6px;z-index:99999;box-shadow:0 2px 8px rgba(0,0,0,0.2);font-size:13px;">' + msg + '</div>');
                    $('body').append($t);
                    setTimeout(function() {
                        $t.fadeOut(300, function() {
                            $t.remove();
                        });
                    }, 2200);
                } catch (e) {
                    /* ignore */
                }
            }

            var mo = new MutationObserver(function(muts) {
                muts.forEach(function(m) {
                    $(m.addedNodes).each(function() {
                        $(this).find('input[name="title"]').each(function() {
                            injectPrice($(this));
                        });
                        if ($(this).is('input[name="title"]')) injectPrice($(this));
                    });
                });
            });

            mo.observe(document.body, {
                childList: true,
                subtree: true
            });

            // **Hook sebelum Update Course** → pastikan semua harga tersimpan di meta
            $('button[data-cy="course-builder-submit-button"]').off('click.tpa').on('click.tpa', function() {
                $('div.css-11s3wkf').each(function() {
                    var $topicItem = $(this);
                    var $price = $topicItem.find('.tpa-topic-price');
                    if ($price.length) {
                        var val = parseFloat($price.val()) || 0;
                        $topicItem.data('meta', $topicItem.data('meta') || {});
                        $topicItem.data('meta').topic_price = val;
                    }
                });
            });

            // Keep temporary price map and recent titles to match created topics
            window.tpa_temp_prices = window.tpa_temp_prices || {};
            window.tpa_last_saved_titles = window.tpa_last_saved_titles || [];

            // When clicking OK on a topic without an ID, stash title+tmp so we can match later
            $(document).on('click', 'button[data-cy="save-topic"]', function() {
                var $btn = $(this);
                var $root = $btn.closest('div.css-11s3wkf');
                if (!$root.length) return;
                var topicId = $root.data('id') || null;
                if (topicId) return; // existing topic handled elsewhere

                var tmp = $root.attr('data-tpa-tmp-id') || ('tpa_tmp_' + Math.random().toString(36).substr(2, 9));
                $root.attr('data-tpa-tmp-id', tmp);
                var title = $root.find('input[name="title"]').val() || '';
                var price = parseFloat($root.find('.tpa-topic-price').val()) || 0;
                window.tpa_temp_prices[tmp] = price;
                window.tpa_last_saved_titles.unshift({
                    title: title.trim(),
                    tmp: tmp,
                    time: Date.now()
                });
                if (window.tpa_last_saved_titles.length > 30) window.tpa_last_saved_titles.pop();
            });

            // Collect numeric topic prices map
            function collectTopicPricesMap() {
                var map = {};
                $('div.css-11s3wkf').each(function() {
                    var $root = $(this);
                    var tid = $root.data('id') || $root.attr('data-id') || $root.find('input[name="id"]').val() || '';
                    if (tid && (/^\d+$/).test(tid)) {
                        var v = parseFloat($root.find('.tpa-topic-price').val()) || 0;
                        map[tid] = v;
                    }
                });
                return map;
            }

            function sendTopicPricesBulk() {
                var map = collectTopicPricesMap();
                if (!map || Object.keys(map).length === 0) return;
                $.post('<?php echo esc_js($ajax_url); ?>', {
                    action: 'tpa_save_topic_prices_bulk',
                    topic_prices: JSON.stringify(map)
                }, function(resp) {
                    if (resp && resp.success) {
                        var n = resp.data && resp.data.updated ? resp.data.updated : Object.keys(map).length;
                        showTpaToast(n + ' harga topik disimpan');
                    }
                });
            }

            // Trigger bulk save when course is submitted
            $(document).on('click', 'button[data-cy="course-builder-submit-button"]', function() {
                setTimeout(sendTopicPricesBulk, 400);
            });

            // Autosave periodically
            setInterval(sendTopicPricesBulk, 10000);

            // Listen to all AJAX responses to detect newly created topic and map tmp -> real ID
            $(document).ajaxSuccess(function(event, xhr, settings) {
                try {
                    var text = xhr && xhr.responseText ? xhr.responseText : null;
                    if (!text) return;
                    var data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        var m = text.match(/\{[\s\S]*\}/);
                        if (m) {
                            try {
                                data = JSON.parse(m[0]);
                            } catch (e2) {
                                data = null;
                            }
                        }
                    }
                    if (!data) return;

                    var topicId = null,
                        topicTitle = null;
                    if (data.topic_id) topicId = data.topic_id;
                    if (data.data && data.data.topic_id) topicId = data.data.topic_id;
                    if (data.data && data.data.topic && data.data.topic.id) {
                        topicId = data.data.topic.id;
                        topicTitle = data.data.topic.title || null;
                    }
                    if (data.topic && data.topic.id) {
                        topicId = data.topic.id;
                        topicTitle = data.topic.title || null;
                    }
                    if (!topicId) return;

                    // If we have title, find a matching DOM input; otherwise use last_saved_titles
                    if (!topicTitle) {
                        var now = Date.now();
                        var candidate = null;
                        for (var i = 0; i < window.tpa_last_saved_titles.length; i++) {
                            var it = window.tpa_last_saved_titles[i];
                            if (now - it.time < 15000) {
                                candidate = it;
                                break;
                            }
                        }
                        if (candidate) {
                            topicTitle = candidate.title;
                            var tmp = candidate.tmp;
                            // find DOM by title
                            $('input[name="title"]').each(function() {
                                var t = ($(this).val() || '').trim();
                                if (t === topicTitle) {
                                    var $root = $(this).closest('div.css-11s3wkf');
                                    if ($root.length) {
                                        $root.attr('data-id', topicId);
                                        // if have temp price, save it
                                        if (window.tpa_temp_prices[tmp] !== undefined) {
                                            $.post('<?php echo esc_js($ajax_url); ?>', {
                                                action: 'tpa_save_topic_price',
                                                topic_id: topicId,
                                                price: window.tpa_temp_prices[tmp]
                                            }, function(resp) {
                                                if (resp && resp.success) showTpaToast('Harga topik disimpan');
                                            });
                                            delete window.tpa_temp_prices[tmp];
                                        }
                                    }
                                }
                            });
                        }
                    } else {
                        // have title: match DOM
                        $('input[name="title"]').each(function() {
                            var t = ($(this).val() || '').trim();
                            if (t === (topicTitle || '').trim()) {
                                var $root = $(this).closest('div.css-11s3wkf');
                                if ($root.length) {
                                    $root.attr('data-id', topicId);
                                    var tmp = $root.attr('data-tpa-tmp-id');
                                    if (tmp && window.tpa_temp_prices[tmp] !== undefined) {
                                        $.post('<?php echo esc_js($ajax_url); ?>', {
                                            action: 'tpa_save_topic_price',
                                            topic_id: topicId,
                                            price: window.tpa_temp_prices[tmp]
                                        }, function(resp) {
                                            if (resp && resp.success) showTpaToast('Harga topik disimpan');
                                        });
                                        delete window.tpa_temp_prices[tmp];
                                    }
                                }
                            }
                        });
                    }

                } catch (e) {
                    // ignore parse failures
                }
            });

            // Additional observer: watch for elements that get a data-id attribute (newly mapped topics)
            var attrObserver = new MutationObserver(function(muts) {
                muts.forEach(function(m) {
                    if (m.type !== 'attributes') return;
                    if (m.attributeName !== 'data-id') return;
                    var node = m.target;
                    try {
                        var $node = $(node);
                        var tid = $node.data('id') || $node.attr('data-id');
                        var tmp = $node.attr('data-tpa-tmp-id');
                        if (tmp && tid && (/^\d+$/).test(tid)) {
                            // if we have a temp price stored, send it now
                            if (window.tpa_temp_prices && window.tpa_temp_prices[tmp] !== undefined) {
                                var price = window.tpa_temp_prices[tmp];
                                console.debug('[tpa] detected mapping tmp -> id:', tmp, '->', tid, 'price:', price);
                                $.post('<?php echo esc_js($ajax_url); ?>', {
                                    action: 'tpa_save_topic_price',
                                    topic_id: tid,
                                    price: price
                                }, function(resp) {
                                    if (resp && resp.success) {
                                        showTpaToast('Harga topik disimpan');
                                    }
                                });
                                delete window.tpa_temp_prices[tmp];
                            }
                        }
                    } catch (e) {
                        // ignore
                    }
                });
            });

            // Observe attribute changes on topic item containers
            attrObserver.observe(document.body, {
                subtree: true,
                attributes: true,
                attributeFilter: ['data-id']
            });

        })(jQuery);
    </script>
<?php
});

?>



// Titip backup terbaru 18 Nov 2025 - 10:50PM 
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
