<?php

/**
 * Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package UiCore
 */

@ini_set( 'upload_max_size' , '256M' );
@ini_set( 'post_max_size', '256M');
@ini_set( 'max_execution_time', '300' );

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'ui_child_enqueue_styles');
function ui_child_enqueue_styles() {
	if (!class_exists('\UiCore\Core')){
		wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	}
     wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css' );
}


function deactivate_unlimited_elements_plugin() {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    if ( is_plugin_active( 'unlimited-elements-for-elementor-premium/unlimited_elements_premium.php' ) ) {
        deactivate_plugins( 'unlimited-elements-for-elementor-premium/unlimited_elements_premium.php' );
    }
}
add_action( 'admin_init', 'deactivate_unlimited_elements_plugin' );
/* YOU CAN START EDITING HERE! */
/*
 * a list of complete hooks and filters can be found here
 * https://help.uicore.co/docs/hooks-and-filters
 *
*/


/**
* @Author : Puji Ermanto <pujiermanto@gmail.com>
* Was Here @2024
* https://codesyariah-webdev.vercel.app
**/
// Rest api for jet engine post types
// @author: Puji Ermanto <pujiermanto@gmail.com>
// Endpoint Post Types

function register_dealership_endpoint() {
    register_rest_route('custom/v1', '/dealerships', array(
        'methods'  => 'GET',
        'callback' => 'get_dealerships_data',
    ));
}
add_action('rest_api_init', 'register_dealership_endpoint');

function get_dealerships_data() {
    $args = array(
        'post_type' => 'dealerships',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    $dealerships = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $dealerships[] = array(
                'title' => get_the_title(),
                'lat'   => get_post_meta(get_the_ID(), 'location_latitude', true),
                'lng'   => get_post_meta(get_the_ID(), 'location_longitude', true),
                'address' => get_post_meta(get_the_ID(), 'address', true),
            );
        }
    }

    wp_reset_postdata();
    return $dealerships;
}


// Car model endpoint
function register_carmodels_endpoint() {
    register_rest_route('custom/v1', '/car-model', array(
        'methods'  => 'GET',
        'callback' => 'get_carmodels_data',
    ));
}
add_action('rest_api_init', 'register_carmodels_endpoint');

function get_carmodels_data() {
    $args = array(
        'post_type' => 'car-model',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    $carmodels = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $carmodels[] = array(
                'title' => get_the_title(),
                'images'   => get_post_meta(get_the_ID(), 'images', true)
            );
        }
    }

    wp_reset_postdata();
    return $carmodels;
}

// Office Excel Custom plugins
add_action('restrict_manage_posts', 'add_export_button_for_test_drive');

function add_export_button_for_test_drive() {
    global $typenow;

    if ($typenow == 'test-drive') {
        echo '<input type="submit" name="export_test_drive" class="button button-primary" value="Export to Excel">';
    }
}

add_action('init', 'handle_export_test_drive');

function handle_export_test_drive() {
    if (isset($_GET['export_test_drive']) && $_GET['export_test_drive'] == 'Export to Excel') {
        if (isset($_GET['post_type']) && $_GET['post_type'] == 'test-drive') {
            // Mengecek apakah ada post yang dipilih melalui checkbox
            if (isset($_GET['post']) && is_array($_GET['post'])) {
                $selected_post_ids = $_GET['post'];
                export_test_drive_to_excel($selected_post_ids);
            }
        }
    }
}

function export_test_drive_to_excel($post_ids) {
    require_once ABSPATH . 'excel-library/export-excel/vendor/autoload.php';
    if (count($post_ids) === 1) {
        // Jika hanya satu post, gunakan ID post tersebut sebagai nama file
        $filename = 'test-drive-records-' . $post_ids[0] . '.xlsx';
    } else {
        // Jika lebih dari satu post, gunakan tanggal saja sebagai nama file
        $filename = 'test-drive-records-' . date('Y-m-d') . '.xlsx';
    }

    $writer = \Box\Spout\Writer\Common\Creator\WriterEntityFactory::createXLSXWriter();
    $writer->openToBrowser($filename);
    $headerRow = \Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray(['Post ID', 'Title', 'Date', 'Time', 'First Name', 'Last Name', 'Phone', 'Email', 'Car Model', 'Dealership', 'Message']);
    $writer->addRow($headerRow);

    $args = array(
        'post_type'   => 'test-drive',
        'post__in'    => $post_ids,
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );
    $posts = get_posts($args);

    foreach ($posts as $post) {
        $row = \Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray([
            $post->ID,
            $post->post_title,
            $post->post_date,
            get_post_meta($post->ID, 'time', true),  // Asumsi 'time' adalah meta field
            get_post_meta($post->ID, 'first_name', true),
            get_post_meta($post->ID, 'last_name', true),
            get_post_meta($post->ID, 'phone_number', true),
            get_post_meta($post->ID, 'email', true),
            get_post_meta($post->ID, 'car_model', true),
            get_post_meta($post->ID, 'dealership', true),
            get_post_meta($post->ID, 'message', true)
        ]);
        $writer->addRow($row);
    }

    $writer->close();
    exit;
}


function custom_enqueue_styles() {
    // Enqueue the Leaflet CSS
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet/dist/leaflet.css', array(), null);
	
    // Define custom CSS styles
    $custom_css = "
    #country_code, option {
	width: 350px;
    }
    
    #loading-overlay p {
        margin-top: 10px;
        font-size: 16px;
        color: #007bff;
    }

    #loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    .loading-spinner {
        border: 8px solid #f3f3f3;
        border-top: 8px solid #007bff;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .container {
        display: flex;
        height: 58vh;
        margin-left: -.5rem;
        width: 100%;
    }
    .locations {
        width: 50%;
        overflow-y: auto;
    }
    .location-card {
        padding: 10px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 7px;
        margin-bottom: 10px;
        line-height: 1.8rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        height: 18vh;
    }
    .location-card.selected {
        border-color: #007bff;
        background-color: #e7f0ff;
    }
    .location-card input[type='checkbox'] {
        display: none;
    }
    #map {
        width: 50%;
        height: 100%;
        border-radius: 7px;
        margin-left: 1rem;
    }
    @media screen and (max-width: 768px) {
        .container {
            flex-direction: column;
            height: 85vh;
        }
        .locations {
            width: 100%;
            height: 50%;
            overflow-y: auto;
        }
        #map {
            width: 100%;
            height: 50%;
            margin-top: 1rem;
            border-radius: 7px;
        }
    }
    @media screen and (max-width: 480px) {
        .container {
            flex-direction: column;
            height: 100vh;
        }
        .locations {
            width: 100%;
            height: 100%;
	    overflow-y: auto;
        }
        .location-card {
            flex-direction: column;
            align-items: flex-start;
            font-size: 0.9rem;
            height: 29.5%;
	    padding: 1rem;
            line-height: 1.5rem;
        }
        .location-card b {
            font-size: 1rem;
        }
        #map {
            width: 100%;
            height: 50vh;
            border-radius: 7px;
            margin-left: 0;
            margin-top: -1.3rem;
        }
    }
    ";

    // Add the custom CSS styles
    wp_add_inline_style('leaflet-css', $custom_css);
}
add_action('wp_enqueue_scripts', 'custom_enqueue_styles');

function custom_header_scripts() {
    // Menambahkan Leaflet JS dan plugin ke dalam header
    echo '<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>';
    echo '<script src="https://unpkg.com/leaflet-plugins/layer/Marker.SlideTo.js"></script>';
}
add_action('wp_head', 'custom_header_scripts');

// Handle checkbox di form test drive
function custom_checkbox_script() {
    ?>
    <script type="text/javascript">
        function handleCheckboxSelection() {
            // Handle the subscribe checkbox
            $('input[name="subscribe"]').each(function() {
                if ($(this).is(':checked')) {
                    // Set the hidden input value to "True" when checked
                    $('input[name="subscribe_value"]').val('True');
                } else {
                    // Set the hidden input value to "False" when not checked
                    $('input[name="subscribe_value"]').val('False');
                }
            });

            // Handle the policy checkbox
            $('input[name="policy"]').each(function() {
                if ($(this).is(':checked')) {
                    // Set the hidden input value to "True" when checked
                    $('input[name="policy_value"]').val('True');
                } else {
                    // Set the hidden input value to "False" when not checked
                    $('input[name="policy_value"]').val('False');
                }
            });
        }

        // When the document is ready, bind the change event to checkboxes
        jQuery(document).ready(function($) {
            $('input[name="subscribe"], input[name="policy"]').on('change', handleCheckboxSelection);

            // Call the function once on page load to set the initial values
            handleCheckboxSelection();
        });
    </script>
    <?php
}
add_action('wp_footer', 'custom_checkbox_script');

function custom_footer_scripts() {
    // Script custom kamu yang tetap di footer
    echo '<script>
        const carIcons = {};

        function showLoadingOverlay() {
            const overlay = document.getElementById("loading-overlay");
            overlay.style.display = "flex";
        }

        function hideLoadingOverlay() {
            const overlay = document.getElementById("loading-overlay");
            overlay.style.display = "none";
        }

        async function loadCarModelImages() {
            //showLoadingOverlay(); 

            try {
                const response = await fetch("https://aionindonesia.com/wp-json/wc/v3/products?consumer_key=ck_c9babaa0dc379d22bdcd1dcfbe4a109d300e8fb3&consumer_secret=cs_f565b5efb04a9cd3e21b1090e768583ffe6ef12e");
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }

                const data = await response.json();
                data.forEach(product => {
                    const title = product.name;
                    const images = product.images || [];
                    if (title && images.length > 0) {
                        carIcons[title] = images[0].src;
                    }
                });

            } catch (error) {
                console.error("Error fetching car model images:", error);
            }        
       }

        async function loadDealerships() {
            try {
                const response = await fetch("https://aionindonesia.com/wp-json/custom/v1/dealerships");
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }

                const buttonForm = document.querySelector(".submit-form");
                buttonForm.disabled = true;
                //buttonForm.style.visibility = "hidden";
                
                const data = await response.json();
                const locationsContainer = document.getElementById("locations");
                locationsContainer.innerHTML = "";
                
                if (Array.isArray(data)) {
                    data.forEach(dealership => {
                        if (dealership.title && dealership.lat && dealership.lng && dealership.address) {
                            const card = document.createElement("div");
                            card.className = "location-card";
                            card.dataset.lat = dealership.lat;
                            card.dataset.lng = dealership.lng;
                            card.innerHTML = `
                                <div data-dealership="${dealership.title}">
                                      <b>${dealership.title}</b><br/>
                                      <span>(${dealership.address})</span>
                                </div>
                            `;
                            locationsContainer.appendChild(card);

			    const button = document.querySelector(".jet-form-builder__action-button");
                            button.disabled = true;
                        }
                    });
                } else {
                    console.error("Invalid data format received from API");
                }
            } catch (error) {
                console.error("Error fetching dealership data:", error);
            }
        }

        function animateMarker(marker, toLatLng, duration) {
            var start = null;
            var fromLatLng = marker.getLatLng();

            function animate(timestamp) {
                if (!start) start = timestamp;
                var progress = timestamp - start;
                var progressRatio = Math.min(progress / duration, 1);

                var currentLat = fromLatLng.lat + (toLatLng.lat - fromLatLng.lat) * progressRatio;
                var currentLng = fromLatLng.lng + (toLatLng.lng - fromLatLng.lng) * progressRatio;

                marker.setLatLng([currentLat, currentLng]);

                if (progress < duration) {
                    requestAnimationFrame(animate);
                } else {
                    marker.setLatLng(toLatLng);
                }
            }

            requestAnimationFrame(animate);
        }

        function updateMarkerIcon(iconUrl) {
            if (marker) {
                marker.setIcon(L.icon({
                    iconUrl: iconUrl,
                    iconSize: [110, 80], 
                    iconAnchor: [19, 38], 
                    popupAnchor: [0, -38]
                }));
            }
        }

        var map = L.map("map").setView([-6.181388711133112, 106.9747525767128], 11);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: ""
        }).addTo(map);

        var marker = L.marker([-6.181388711133112, 106.9747525767128], {
            icon: L.icon({
                iconUrl: "https://aionindonesia.com/wp-content/uploads/2024/08/deaker.svg", 
                iconSize: [40, 40], 
                iconAnchor: [19, 38], 
                popupAnchor: [0, -38]
            })
        }).addTo(map);

        document.getElementById("locations").addEventListener("click", function(e) {
            if (e.target && e.target.closest(".location-card")) {
                const selectedCard = e.target.closest(".location-card");
                
                const dealershipTitle = selectedCard.querySelector("[data-dealership]").getAttribute("data-dealership");
    
                document.querySelectorAll(".location-card").forEach(card => {
                    card.classList.remove("selected");
                });

                selectedCard.classList.add("selected");
                
                var lat = parseFloat(selectedCard.dataset.lat);
                var lng = parseFloat(selectedCard.dataset.lng);
                var newLatLng = new L.LatLng(lat, lng);
                
                animateMarker(marker, newLatLng, 1000);
                map.panTo(newLatLng);

                const hiddenInput = document.querySelector("input[name=\'dealership\']");
		console.log(hiddenInput);

                if (hiddenInput) {
                    hiddenInput.value = dealershipTitle;
                    const buttonForm = document.querySelector(".submit-form");
                    buttonForm.disabled = false;
                    //buttonForm.style.visibility = "visible";
                }
            }
        });

        // document.getElementById("car_model").addEventListener("change", function(e) {
        //     const selectedCarModel = e.target.value;
        //     const iconUrl = carIcons[selectedCarModel] || "https://aionindonesia.com/wp-content/uploads/2024/08/deaker.svg"; 
        //     updateMarkerIcon(iconUrl);
        // });

        loadCarModelImages();
        loadDealerships();
    </script>';
}
add_action('wp_footer', 'custom_footer_scripts');
