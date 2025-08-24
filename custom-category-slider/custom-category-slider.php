<?php
/*
Plugin Name: Custom Category Slider Pro
Description: Category slider dengan setting, Elementor widget, AJAX & lazy loading.
Version: 1.0
Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Vickerness | AKA Dunkelheit | AKA Tatang Kegelapan
Author URI: https://pujiermanto-blog.vercel.app
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;

// ---------------------------------------
// Enqueue Scripts & Styles
function add_fontawesome_to_head()
{
    wp_enqueue_style(
        'fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        array(),
        '6.5.2'
    );
}

add_action('wp_enqueue_scripts', 'add_fontawesome_to_head');
function ccsp_enqueue_scripts()
{
    wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
    wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
    wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], '1.8.1', true);

    // Custom CSS
    wp_add_inline_style('slick-css', '
        .category {
            background-color: transparent !important;
        }
        .category-slider {
            // padding: 2px 0;
            width: 100%;
        }

        .category-slider .category-item {
            background: transparent !important;
            border: none;
            box-shadow: none;
            text-align: center;
        }
        .category-slider .category-item h3 {
            margin: 0;
            font-size: inherit;
            font-weight: inherit;
            color: inherit;
        }
        .category-slider .category-item a {
            display: block;
            color: #333;
            text-decoration: none;
        }

        .category-slider .category-item img {
            max-width: 100px;
            margin: 0 auto 10px;
        }

        .category-slider .category-item .category-title {
            font-size: 1rem;
            font-weight: 600;
        }

        .category-slider .category-item .category-icon {
            font-size: 2rem;
            color: #ED2D56;
        }

        .category-slider .category-item h3 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            color: #333;
        }

        .slider-arrow {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 20px;
        }

        .slider-arrow .btn-icon {
            background: #ff3505;
            border: 1px solid #f94014;
            border-radius: 12px;
            width: 38px;
            height: 38px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #fff;
            font-size: 20px;
            line-height: 1;
        }

        .slider-arrow .btn-icon:hover {
            background: #ED2D56;
            border-color: #ad9ca0ff;
            color: #fff;
        }

        .slider-arrow .btn-icon.slick-disabled {
            opacity: 0.3;
            cursor: default;
            pointer-events: none;
        }
        .category-slider .category-item {
            padding: 20px 10px;
            text-align: center;
            border-radius: 12px;
            background-color: transparent;
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            // box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .category-slider .category-item .category-img {
            overflow: hidden;
        }

        .category-slider .category-item .category-img img {
            transition: transform 0.3s ease;
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .category-slider .category-item:hover .category-img img {
            transform: scale(1.3);
            z-index: 99;
        }


        .category-slider .category-item .category-img {
            /* background: rgba(255,255,255,0.8);  hapus atau ganti */
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            padding: 10px;
            border-radius: 50%;
            width: 100px;
            height: 100px;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 80;
        }
        
        .category-slider .category-item .category-img img {
            width: 90%;
            height: 140px; 
            object-fit: cover;
            display: block;
        }

        .category-slider .category-item .category-img img:hover{
            z-index: 99!important;
        }

        /* Tambah gap & kecilkan item di mobile (<=480px) */
        @media (max-width: 480px) {
            .slick-list .slick-track {
                margin-left: -1rem;
            }
            .category-slider .category-item {
                margin: 0 10px;
            }
            .category-slider .category-item .category-img {
                width: 140px;
                height: 140px;
                padding: 10px;
            }
            .category-slider .category-item .category-img img {
                height: 150px;
                width: 300px;
            }
            .category-slider .category-item-title {
                font-size: 1.5rem;
            }
        }
        @media (min-width: 481px) and (max-width: 767px) {
            .slick-list .slick-track {
                margin-left: -1rem;
            }
            .category-slider .category-item {
                margin: 0 4px;
            }
            .category-slider .category-item .category-img {
                width: 150px;
                height: 150px;
                padding: 15px;
            }
            .category-slider .category-item .category-img img {
                height: 150px;
                width: 300px;
            }
            .category-slider .category-item-title {
                font-size: 1.5rem;
            }
        }

        @media (min-width: 768px) and (max-width: 900px) {
            .slick-list .slick-track {
                margin-left: -1rem;
            }
            .category-slider .category-item {
                margin: 0 4px;
            }
            .category-slider .category-item .category-img {
                width: 150px;
                height: 150px;
                padding: 15px;
            }
            .category-slider .category-item .category-img img {
                height: 150px;
                width: 300px;
            }
            .category-slider .category-item-title {
                font-size: 1.5rem;
            }
        }

            /* Galaxy Tab Landscape (1024px) */
        @media (min-width: 901px) and (max-width: 1024px) {
            .slick-list .slick-track {
                margin-left: -1rem;
            }
            .category-slider .category-item {
                margin: 0 4px;
            }
            .category-slider .category-item .category-img {
                width: 150px;
                height: 150px;
                padding: 15px;
            }
            .category-slider .category-item .category-img img {
                height: 150px;
                width: 300px;
            }
            .category-slider .category-item-title {
                font-size: 1.5rem;
            }
        }
    ');

    // Slick init, nanti bisa dipanggil ulang via ajax
    wp_add_inline_script('slick-js', "
        function ccsp_init_slick(){
            jQuery('.category-slider').not('.slick-initialized').slick({
                slidesToShow: 5,
                slidesToScroll: 1,
                arrows: true,
                prevArrow: '#ccsp-slider-arrows .slider-prev',
                nextArrow: '#ccsp-slider-arrows .slider-next',
                dots: false,
                infinite: false,
                lazyLoad: 'ondemand',
                responsive: [
                    { breakpoint: 1024, settings: { slidesToShow: 4 }},
                    { breakpoint: 768, settings: { slidesToShow: 2 }},
                    { breakpoint: 600, settings: { slidesToShow: 3 }},
                    { breakpoint: 480, settings: { slidesToShow: 2 }}
                ]
            });
        }
        jQuery(document).ready(function($){
            ccsp_init_slick();
        });
    ");
}
add_action('wp_enqueue_scripts', 'ccsp_enqueue_scripts');

// ---------------------------------------
// Settings page di admin
function ccsp_add_admin_menu()
{
    add_options_page('Category Slider Settings', 'Category Slider', 'manage_options', 'ccsp_settings', 'ccsp_settings_page');
}
add_action('admin_menu', 'ccsp_add_admin_menu');

function ccsp_settings_init()
{
    register_setting('ccsp_settings_group', 'ccsp_settings');

    add_settings_section(
        'ccsp_settings_section',
        __('Category Slider Settings', 'ccsp'),
        null,
        'ccsp_settings'
    );

    add_settings_field(
        'ccsp_title',
        __('Section Title', 'ccsp'),
        'ccsp_title_render',
        'ccsp_settings',
        'ccsp_settings_section'
    );

    add_settings_field(
        'ccsp_taxonomy',
        __('Taxonomy', 'ccsp'),
        'ccsp_taxonomy_render',
        'ccsp_settings',
        'ccsp_settings_section'
    );

    add_settings_field(
        'ccsp_limit',
        __('Number of Categories', 'ccsp'),
        'ccsp_limit_render',
        'ccsp_settings',
        'ccsp_settings_section'
    );
}

add_action('admin_init', 'ccsp_settings_init');

// Tambah field background di edit kategori
function ccsp_add_bg_image_field($term)
{
    $bg_image_id = get_term_meta($term->term_id, 'ccsp_bg_image_id', true);
    $bg_image_url = $bg_image_id ? wp_get_attachment_url($bg_image_id) : '';
?>
    <tr class="form-field term-bg-image-wrap">
        <th scope="row" valign="top"><label><?php _e('Background Image', 'ccsp'); ?></label></th>
        <td>
            <div id="ccsp_bg_image_preview" style="float: left; margin-right: 10px;">
                <?php if ($bg_image_url) : ?>
                    <img src="<?php echo esc_url($bg_image_url); ?>" width="60" height="60" />
                <?php endif; ?>
            </div>
            <input type="hidden" id="ccsp_bg_image_id" name="ccsp_bg_image_id" value="<?php echo esc_attr($bg_image_id); ?>" />
            <button type="button" class="upload_bg_image_button button"><?php _e('Upload/Add Image', 'ccsp'); ?></button>
            <button type="button" class="remove_bg_image_button button" <?php echo $bg_image_url ? '' : 'style="display:none;"'; ?>><?php _e('Remove Image', 'ccsp'); ?></button>
            <div class="clear"></div>
        </td>
    </tr>
<?php
}
add_action('product_cat_edit_form_fields', 'ccsp_add_bg_image_field', 10);

// Simpan meta background saat disimpan
function ccsp_save_bg_image_meta($term_id)
{
    if (isset($_POST['ccsp_bg_image_id'])) {
        $image_id = intval($_POST['ccsp_bg_image_id']);
        if ($image_id) {
            update_term_meta($term_id, 'ccsp_bg_image_id', $image_id);
        } else {
            delete_term_meta($term_id, 'ccsp_bg_image_id');
        }
    }
}
add_action('edited_product_cat', 'ccsp_save_bg_image_meta', 10);

function ccsp_admin_enqueue_scripts($hook)
{
    if ($hook === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'product_cat') {
        wp_enqueue_media();
        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($){
                var file_frame;
                $('.upload_bg_image_button').on('click', function(e){
                    e.preventDefault();
                    var button = $(this);
                    if(file_frame) {
                        file_frame.open();
                        return;
                    }
                    file_frame = wp.media.frames.file_frame = wp.media({
                        title: 'Choose Background Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    file_frame.on('select', function(){
                        var attachment = file_frame.state().get('selection').first().toJSON();
                        $('#ccsp_bg_image_id').val(attachment.id);
                        $('#ccsp_bg_image_preview').html('<img src=\"' + attachment.url + '\" width=\"60\" height=\"60\" />');
                        button.next('.remove_bg_image_button').show();
                    });
                    file_frame.open();
                });
                $('.remove_bg_image_button').on('click', function(){
                    $('#ccsp_bg_image_id').val('');
                    $('#ccsp_bg_image_preview').html('');
                    $(this).hide();
                    return false;
                });
            });
        ");
    }
}
add_action('admin_enqueue_scripts', 'ccsp_admin_enqueue_scripts');

function ccsp_get_settings()
{
    return get_option('ccsp_settings', [
        'title' => 'Browse By Categories',
        'taxonomy' => 'product_cat',
        'limit' => 12,
    ]);
}

function ccsp_title_render()
{
    $options = ccsp_get_settings();
?>
    <input type='text' name='ccsp_settings[title]' value='<?php echo esc_attr($options['title']); ?>' style="width:300px;">
<?php
}

function ccsp_taxonomy_render()
{
    $options = ccsp_get_settings();
?>
    <input type='text' name='ccsp_settings[taxonomy]' value='<?php echo esc_attr($options['taxonomy']); ?>' placeholder="e.g. product_cat or category" style="width:300px;">
    <p class="description">Masukkan taxonomy yang ingin ditampilkan, misal 'product_cat' untuk WooCommerce</p>
<?php
}

function ccsp_limit_render()
{
    $options = ccsp_get_settings();
?>
    <input type='number' min='1' max='50' name='ccsp_settings[limit]' value='<?php echo intval($options['limit']); ?>' style="width:60px;">
<?php
}

function ccsp_settings_page()
{
?>
    <div class="wrap">
        <h1>Category Slider Settings</h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('ccsp_settings_group');
            do_settings_sections('ccsp_settings');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

// ---------------------------------------
// Render category slider HTML (dipisah supaya bisa dipanggil ajax juga)
function ccsp_render_category_slider($args = [])
{
    $options = ccsp_get_settings();

    $title = $args['title'] ?? $options['title'];
    $taxonomy = $args['taxonomy'] ?? $options['taxonomy'];
    $limit = isset($args['limit']) ? intval($args['limit']) : intval($options['limit']);
    $bg_image_url = $args['bg_image_url'] ?? '';

    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
        'number' => $limit,
        'orderby'    => 'id',
        'order'      => 'DESC',
    ]);
    if (empty($terms) || is_wp_error($terms)) {
        return '<p>No categories found.</p>';
    }

    ob_start();
?>
    <section class="category category-6 pb-100">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="section-title title-inline mb-20 d-flex align-items-center justify-content-between">
                        <h2 class="title mb-20"><?php echo esc_html($title); ?></h2>
                        <div class="slider-arrow mb-20" id="ccsp-slider-arrows">
                            <button type="button" class="btn-icon slider-btn slider-prev">
                                <i class="fa-solid fa-angle-left"></i></button>
                            <button type="button" class="btn-icon slider-btn slider-next">
                                <i class="fa-solid fa-angle-right"></i></button>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="category-slider">
                        <?php foreach ($terms as $term):
                            $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                            $img_url = $thumb_id ? wp_get_attachment_url($thumb_id) : wc_placeholder_img_src();

                            // kalau ingin bg image khusus per kategori, bisa dari meta. 
                            // tapi kalau bg image global dari widget, pakai $bg_image_url
                        ?>
                            <div class="category-item">
                                <a href="<?php echo esc_url(get_term_link($term)); ?>" title="<?php echo esc_attr($term->name); ?>">
                                    <div class="category-img" style="background-image: url('<?php echo esc_url($bg_image_url); ?>'); background-size: cover; background-position: center;">
                                        <img class="lazyload" data-lazy="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($term->name); ?>" />
                                    </div>
                                    <h3 class="category-item-title"><?php echo esc_html($term->name); ?></h3>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php
    return ob_get_clean();
}


// ---------------------------------------
// AJAX handler untuk reload slider (optional, kalau mau pakai ajax load ulang kategori)
function ccsp_ajax_load_categories()
{
    check_ajax_referer('ccsp_nonce', 'nonce');

    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 12;

    echo ccsp_render_category_slider([
        'title' => $title,
        'taxonomy' => $taxonomy,
        'limit' => $limit,
    ]);
    wp_die();
}
add_action('wp_ajax_ccsp_load_categories', 'ccsp_ajax_load_categories');
add_action('wp_ajax_nopriv_ccsp_load_categories', 'ccsp_ajax_load_categories');

// ---------------------------------------
// Elementor widget registration
add_action('elementor/widgets/widgets_registered', 'ccsp_register_elementor_widget');

function ccsp_register_elementor_widget()
{
    require_once(__DIR__ . '/elementor-category-slider-widget.php');

    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \CCSP_Category_Slider_Widget());
}
