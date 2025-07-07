<?php

/**
 * Plugin Name: Custom File Uploader
 * Description: Plugin untuk upload file dokumen dan menampilkannya via shortcode.
 * Version: 1.0
 * Author: Puji Ermanto<dev@codesyariah.co.id> | Aka Mamam Yuk | Aka Janji mas joni
 */

if (! defined('ABSPATH')) exit;

// function enqueue_lottiefiles_script_in_head()
// {
//     wp_enqueue_script(
//         'lottiefiles-player',
//         'https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js',
//         [],
//         null,
//         false // false = load di head
//     );
// }
// add_action('wp_enqueue_scripts', 'enqueue_lottiefiles_script_in_head');

function cfu_post_edit_form_tag()
{
    echo ' enctype="multipart/form-data"';
}
add_action('post_edit_form_tag', 'cfu_post_edit_form_tag');
// Register Custom Post Type
function cfu_register_post_type()
{
    register_post_type('upload_file', [
        'labels' => [
            'name' => 'Upload File',
            'singular_name' => 'File',
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'thumbnail'],
        'menu_icon' => 'dashicons-media-document'
    ]);
}
add_action('init', 'cfu_register_post_type');

// Add Meta Box for File Upload
function cfu_add_meta_boxes()
{
    add_meta_box('cfu_file_upload', 'Upload File', 'cfu_file_upload_callback', 'upload_file', 'normal', 'default');
}
add_action('add_meta_boxes', 'cfu_add_meta_boxes');

function cfu_file_upload_callback($post)
{
    $file_url = get_post_meta($post->ID, '_cfu_file', true);
    echo '<input type="file" name="cfu_file_upload" />';
    if ($file_url) {
        echo '<p>Current File: <a href="' . esc_url($file_url) . '" target="_blank">Download</a></p>';
    }
}

// Save the uploaded file
function cfu_save_post($post_id)
{
    if (!empty($_FILES['cfu_file_upload']['name'])) {
        $supported_types = ['application/pdf', 'application/msword', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

        if (in_array($_FILES['cfu_file_upload']['type'], $supported_types)) {
            $upload = wp_handle_upload($_FILES['cfu_file_upload'], ['test_form' => false]);
            if (isset($upload['url'])) {
                update_post_meta($post_id, '_cfu_file', esc_url_raw($upload['url']));
            }
        }
    }
}
add_action('save_post', 'cfu_save_post');

// Shortcode for Frontend Display
function cfu_frontend_display($atts)
{
    $atts = shortcode_atts(['id' => ''], $atts, 'cfu_file');

    if (!$atts['id']) return 'No file selected.';

    $post = get_post($atts['id']);
    $file_url = get_post_meta($post->ID, '_cfu_file', true);

    if (!$post || !$file_url) return 'File not found.';

    $file_path = str_replace(site_url('/'), ABSPATH, $file_url); // Konversi URL ke path server
    $file_name = basename($file_path);
    $file_size = file_exists($file_path) ? size_format(filesize($file_path), 2) : 'Unknown';
    $file_type = file_exists($file_path) ? mime_content_type($file_path) : 'Unknown';
    $thumbnail = get_the_post_thumbnail_url($post->ID, 'medium');

    ob_start(); ?>
    <div class="cfu-wrapper">
        <div class="cfu-flex-container">
            <div class="cfu-preview">
                <?php if ($thumbnail): ?>
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($post->post_title); ?>" style="border: 1px solid #ccc; border-radius: 4px;">
                <?php else: ?>
                    <iframe src="<?php echo esc_url($file_url); ?>" width="100%" height="500px" style="border:1px solid #ccc;"></iframe>
                <?php endif; ?>
            </div>
            <div class="cfu-info">
                <ul class="list-group">
                    <li class="list-group-item"><strong>Judul:</strong> <?php echo esc_html($post->post_title); ?></li>
                    <li class="list-group-item"><strong>Nama File:</strong> <?php echo esc_html($file_name); ?></li>
                    <li class="list-group-item"><strong>Ukuran File:</strong> <?php echo esc_html($file_size); ?></li>
                    <li class="list-group-item"><strong>Tipe File:</strong> <?php echo esc_html($file_type); ?></li>
                    <li class="list-group-item"><strong>Tanggal Upload:</strong> <?php echo get_the_date('', $post); ?></li>
                </ul>
                <a id="download-file" class="btn btn-primary mt-3" href="<?php echo esc_url($file_url); ?>">Download File</a>
            </div>
        </div>
    </div>

    <style>
        .cfu-flex-container {
            display: flex;
            flex-wrap: wrap;
            column-gap: 0.3rem;
            /* jarak horizontal */
            row-gap: 0.5rem;
            /* jarak vertikal */
            align-items: flex-start;
        }

        .cfu-preview,
        .cfu-info {
            flex: 1 1 48%;
        }

        @media (max-width: 768px) {

            .cfu-preview,
            .cfu-info {
                flex: 1 1 100%;
            }
        }

        .cfu-wrapper {
            max-width: 100vw;
            margin: 2rem auto;
            padding: 1rem;
            /* border: 1px solid #ddd; */
            /* border-radius: 8px; */
            background: #fff;
            /* box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); */
        }

        .cfu-preview {
            margin-bottom: 0.35rem;
        }

        .cfu-preview iframe {
            display: block;
            max-width: 100%;
            margin: 0 auto;
            border-radius: 4px;
        }

        .cfu-preview img {
            max-width: 600px;
            height: auto;
            border-radius: 4px;
        }

        .cfu-info {
            /* padding: 0 1rem;
             */
            padding: 0 0.35rem;
        }

        .list-group {
            padding-left: 0;
            margin-bottom: 1rem;
            list-style: none;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }

        .list-group-item {
            position: relative;
            display: block;
            padding: 0.75rem 1.25rem;
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .btn.btn-primary {
            background-color: #007bff;
            color: #fff;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
        }

        .btn.btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
<?php
    return ob_get_clean();
}


// Enqueue SweetAlert and custom script
function cfu_enqueue_sweetalert_script()
{
    // Render Ninja Form content secara dinamis
    $ninja_form_popup = do_shortcode("[ninja_form id='5']");

?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Popup & Overlay */
        #cfu-ninja-form-popup {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.25);
            /* Lebih terang */
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            /* Kurangi kegelapan */
            max-width: 90%;
            width: 500px;
            backdrop-filter: blur(15px);
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }

        #cfu-popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            /* Lebih transparan */
            z-index: 9998;
        }

        #cfu-ninja-form-popup .close-btn {
            float: right;
            cursor: pointer;
            font-size: 24px;
            font-weight: bold;
            margin-top: -10px;
            margin-right: -10px;
            color: #000000;
            transition: color 0.3s ease;
        }

        #cfu-ninja-form-popup .close-btn:hover {
            color: #ED2D56;
        }

        #cfu-ninja-form-popup h2 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: #000000;
        }

        #cfu-ninja-form-popup p {
            color: #000000;
        }



        /* Form fields required note */
        .custom-nf-form .nf-form-fields-required {
            margin-bottom: 20px;
            font-size: 0.95rem;
            color: #ffdddd;
        }

        /* Labels */
        /* .custom-nf-form label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
        } */

        /* Input Fields */
        /* .custom-nf-form nf-fields-wrap nf-field .nf-field-element input.ninja-forms-field[type="text"],
        .custom-nf-form nf-fields-wrap nf-field .nf-field-element input.ninja-forms-field[type="tel"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: none;
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            transition: background 0.3s ease, box-shadow 0.3s ease;
            font-size: 1rem;
            font-family: inherit;
        }
        .custom-nf-form nf-fields-wrap nf-field .nf-field-element input.ninja-forms-field[type="text"]:focus,
        .custom-nf-form nf-fields-wrap nf-field .nf-field-element input.ninja-forms-field[type="tel"]:focus {
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 0 2px #ffffff40;
        } */
        #nf-label-field-35,
        #nf-label-field-34 {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            color: #000000;
            /* Change label color */
        }

        #nf-field-35 {
            width: 100% !important;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: none;
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            color: #000000;
            transition: background 0.3s ease, box-shadow 0.3s ease;
            font-size: 1rem;
            font-family: inherit;
        }

        #nf-field-34 {
            width: 100% !important;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: none;
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            color: #000000;
            transition: background 0.3s ease, box-shadow 0.3s ease;
            font-size: 1rem;
            font-family: inherit;
        }

        #nf-field-35:focus,
        #nf-field-34:focus {
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 0 2px #ffffff40;
        }

        #nf-field-33 {
            width: 100%;
            /* padding: 0.75rem; */
            margin-top: 1.5rem;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, rgb(23, 4, 187), rgb(92, 103, 255));
            color: #ffffff;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.3s ease;
            font-family: inherit;
        }

        #nf-field-33:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 129, 0.4);
        }

        /* .custom-nf-form .nf-form input[type="submit"] {
            width: 100%;
            padding: 0.75rem;
            margin-top: 1.5rem;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #ED2D56, #ff6b81);
            color: #fff;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.3s ease;
            font-family: inherit;
        }

        .custom-nf-form .nf-form input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 129, 0.4);
        } */

        /* Error message styling */
        .custom-nf-form .nf-error-wrap {
            color: #ffbbbb;
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }

        /* Honeypot hidden */
        .custom-nf-form .nf-form-hp {
            display: none;
        }
    </style>

    <div id="cfu-popup-overlay"></div>
    <div id="cfu-ninja-form-popup">
        <span class="close-btn" onclick="cfuClosePopup()">×</span>
        <h2>Download Compro</h2>
        <p>
            Silahkan isi data berikut untuk unduh profile perusahaan SKI Birojasa
        </p>
        <!-- Wrap ninja form with custom class for styling -->
        <div class="custom-nf-form">
            <?php echo $ninja_form_popup; ?>
        </div>
    </div>

    <script>
        function cfuClosePopup() {
            document.getElementById('cfu-ninja-form-popup').style.display = 'none';
            document.getElementById('cfu-popup-overlay').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const downloadBtn = document.querySelector('.cfu-info a.btn');

            if (downloadBtn) {
                downloadBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Show overlay loading spinner
                    const overlay = document.getElementById('cfu-popup-overlay');
                    overlay.style.display = 'block';
                    overlay.innerHTML = `
                        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
                            <dotlottie-player
                                src="https://lottie.host/51d98366-f849-40d4-b9d4-1d4cc4bd64d9/rvXb53DPyL.lottie"
                                background="transparent"
                                speed="1"
                                style="width: 150px; height: 150px"
                                loop
                                autoplay
                                ></dotlottie-player>
                            <div style="color: #fff; margin-top: -5rem; font-weight: 800; font-size: 1.3rem;">Memuat...</div>
                        </div>
                    `;

                    // After 2 seconds, hide spinner and show popup
                    setTimeout(() => {
                        overlay.innerHTML = ''; // remove spinner
                        document.getElementById('cfu-ninja-form-popup').style.display = 'block';
                    }, 3000);
                });
            }
        });
    </script>
<?php
}
add_action('wp_footer', 'cfu_enqueue_sweetalert_script');

add_action('wp_footer', 'handle_nf_submit_via_jquery');
function handle_nf_submit_via_jquery()
{
?>
    <script>
        jQuery(document).ready(function($) {
            console.log('jQuery ready');

            $(document).on('nfFormSubmitResponse', function(e, response) {
                console.log('jQuery nfFormSubmitResponse caught', response);

                const downloadBtn = document.getElementById('download-file');
                if (downloadBtn) {
                    const a = document.createElement('a');
                    a.href = downloadBtn.href;
                    a.setAttribute('download', '');
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }
                setTimeout(() => {
                    document.getElementById('cfu-ninja-form-popup').style.display = 'none';
                    document.getElementById('cfu-popup-overlay').style.display = 'none';
                }, 2000)
            });
        });
    </script>
<?php
}



add_shortcode('cfu_file', 'cfu_frontend_display');
