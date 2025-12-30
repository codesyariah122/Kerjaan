<?php

/**
 * CUSTOM PAID TOPIC TUTOR LMS - All-in-One (Rupiah)
 * Author: Puji Ermanto <pujiermanto@gmail.com>
 */

/**
 * Tambah meta field 'topic_price' di topic editor
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'topic_price_box',
        'Topic Price',
        function ($post) {
            $price = get_post_meta($post->ID, '_topic_price', true);
            echo '<input type="number" step="0.01" min="0" name="topic_price" value="' . esc_attr($price) . '" />';
            echo '<p class="description">Isi harga untuk topic/bab ini (Rp). Kosongkan 0 = gratis.</p>';
        },
        'tutor_course_topic',
        'side',
        'default'
    );
});

/**
 * Save topic price
 */
add_action('save_post_tutor_course_topic', function ($post_id) {
    if (isset($_POST['topic_price'])) {
        update_post_meta($post_id, '_topic_price', floatval($_POST['topic_price']));
    }
});

/**
 * Check if user has paid topic
 */
function has_paid_topic($user_id, $topic_id)
{
    return get_user_meta($user_id, 'paid_topic_' . $topic_id, true) == 1;
}

/**
 * Filter lesson content: jika topic belum dibayar, tampilkan tombol Pay
 */
add_filter('tutor_lesson_content', function ($content) {
    if (!is_user_logged_in()) return $content;

    global $post;
    $lesson_id = $post->ID;
    $user_id   = get_current_user_id();
    $course_id = tutor_utils()->get_course_by_lesson($lesson_id)->ID;

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
        $topic_price = get_post_meta($topic_id, '_topic_price', true);
        if ($topic_price > 0 && !has_paid_topic($user_id, $topic_id)) {
            $pay_url = add_query_arg([
                'action'   => 'pay_topic',
                'topic_id' => $topic_id,
            ], home_url('/'));

            return '<div class="paid-topic-lock">
                <p>Topic ini berbayar: Rp ' . number_format($topic_price, 0, ",", ".") . '</p>
                <a href="' . $pay_url . '" class="tutor-btn tutor-btn-primary">Bayar & Buka</a>
            </div>';
        }
    }

    return $content;
});

/**
 * Handle topic payment (dummy)
 */
add_action('init', function () {
    if (isset($_GET['action']) && $_GET['action'] === 'pay_topic' && isset($_GET['topic_id'])) {
        $topic_id = intval($_GET['topic_id']);
        $user_id  = get_current_user_id();

        update_user_meta($user_id, 'paid_topic_' . $topic_id, 1);

        $lessons = tutor_utils()->get_topic_lessons($topic_id);
        if ($lessons) {
            wp_redirect(get_permalink($lessons[0]->ID));
        } else {
            wp_redirect(home_url());
        }
        exit;
    }
});

/**
 * Ambil harga pertama topic berbayar untuk course card (Rp)
 */
add_filter('tutor_course_price', function ($price, $course_id) {
    $topics = tutor_utils()->get_course_topics($course_id);
    foreach ($topics as $topic) {
        $topic_price = get_post_meta($topic->ID, '_topic_price', true);
        if ($topic_price > 0) return 'Rp ' . number_format($topic_price, 0, ",", ".");
    }
    return 'Rp 0';
}, 10, 2);

/**
 * Tambah input price di Curriculum editor (inline JS)
 */
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if (strpos($screen->id, 'create-course') === false) return;
?>
    <script>
        (function($) {
            function addPriceField(topicEl) {
                if ($(topicEl).find('.topic-price').length) return;
                var input = $('<input type="number" step="0.01" min="0" class="topic-price" placeholder="Harga Rp">')
                    .css({
                        marginLeft: '10px',
                        width: '100px'
                    });
                $(topicEl).find('input[name="title"]').first().after(input);
            }

            // cek semua topic yg sudah ada
            $('input[name="title"]').each(function() {
                addPriceField($(this).closest('div'));
            });

            // MutationObserver untuk menangkap topic baru
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(m) {
                    m.addedNodes.forEach(function(node) {
                        if (node.nodeType !== 1) return;

                        // jika ada input title baru
                        $(node).find('input[name="title"]').each(function() {
                            addPriceField($(this).closest('div'));
                        });

                        // cek node itu sendiri
                        if ($(node).is('input[name="title"]')) {
                            addPriceField($(node).closest('div'));
                        }
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // save harga saat submit
            $(document).on('submit', 'form#create-course', function() {
                var topic_prices = {};
                $('input.topic-price').each(function() {
                    var topicId = $(this).closest('[data-id]').attr('data-id') || '';
                    var price = parseFloat($(this).val()) || 0;
                    if (topicId) topic_prices[topicId] = price;
                });
                $('<input>').attr({
                    type: 'hidden',
                    name: 'topic_prices',
                    value: JSON.stringify(topic_prices)
                }).appendTo(this);
            });
        })(jQuery);
    </script>
<?php
});
