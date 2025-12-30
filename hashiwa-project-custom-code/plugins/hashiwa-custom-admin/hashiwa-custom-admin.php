<?php

/**
 * Plugin Name: Hashiwa Custom Admin
 * Description: Custom shortcode, admin UI, protection for Code Snippets, and WooCommerce menu modifications.
 * Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Marwoto
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

add_action('plugins_loaded', function () {

    /* ======================================================
       FRONTEND SHORTCODE
    ======================================================= */
    add_shortcode('newsletter_signup', function () {
        return '
<form action="https://hashiwa.tokoweb.live/subscribe" method="post" target="_blank" novalidate>
    <label for="email">Subscribe to our newsletter:</label>
    <input type="email" id="email" name="email" placeholder="Your email address" required>
    <button type="submit" style="background:#ED2D56;color:white;border:none;padding:8px 16px;cursor:pointer;">Subscribe</button>
</form>';
    });

    /* ======================================================
       ADMIN AREA ONLY
    ======================================================= */
    if (!is_admin()) return;

    /* REGISTER BOTTOM MENU */
    add_action('after_setup_theme', function () {
        register_nav_menu('bottom-menu', 'Bottom Navbar Menu');
    });

    /* ADMIN TEXT REPLACER */
    add_action('admin_init', function () {
        global $wp_version;
        add_filter('gettext', function ($translated, $text) use ($wp_version) {
            if ($text === 'Welcome to WordPress!') return 'HASHIWA JAPANESE ACADEMY';
            if ($text === 'Learn more about the %s version.') return sprintf('Bridge Beyond Border', $wp_version);
            return $translated;
        }, 10, 3);
    });

    /* CHANGE ICON FOR CODE SNIPPETS MENU */
    add_action('admin_head', function () {
        $icon_url = esc_url(home_url('/wp-content/uploads/2025/11/fav-1-1-2.webp'));
        echo "
        <style>
            #toplevel_page_snippets .wp-menu-image.dashicons-before::before { content:none!important; }
            #toplevel_page_snippets .wp-menu-image {
                background-image:url('{$icon_url}')!important;
                background-size:20px!important;
                background-repeat:no-repeat!important;
                background-position:center!important;
                width:30px!important;height:30px!important;
            }
            #toplevel_page_snippets .wp-menu-image img { display:none!important; }
        </style>";
    });

    /* LOAD SWEETALERT ONLY ON SNIPPETS PAGES */
    add_action('admin_enqueue_scripts', function () {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'snippets') === false) return;

        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);

        wp_add_inline_script('sweetalert2', "
            window.snippetsProtector = {
                ajaxUrl: '" . admin_url('admin-ajax.php') . "',
                nonce: '" . wp_create_nonce('snippets_pw_check') . "'
            };
        ");
    });

    /* AJAX PASSWORD CHECKER */
    add_action('wp_ajax_check_snippet_password', function () {
        check_ajax_referer('snippets_pw_check', 'nonce');

        $correct_pw = '123'; // ⬅ bisa diganti
        $input = sanitize_text_field($_POST['password'] ?? '');

        if ($input === $correct_pw) wp_send_json_success();
        wp_send_json_error();
    });

    /* PASSWORD OVERLAY PROTECTOR */
    add_action('admin_footer', function () {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) return;

        $allowed = [
            'toplevel_page_snippets',
            'snippets_page_edit-snippet',
            'snippets_page_add-snippet',
            'snippets_page_import-code-snippets',
            'snippets_page_snippets-settings',
        ];

        if (!in_array($screen->id, $allowed, true)) return;
?>
        <style>
            #snippets-protect-overlay {
                position: fixed;
                inset: 0;
                background: white;
                z-index: 999999;
            }
        </style>

        <div id="snippets-protect-overlay"></div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: "Masukkan Password",
                    input: "password",
                    inputPlaceholder: "Password",
                    confirmButtonText: "Submit",
                    allowOutsideClick: false,
                    preConfirm: (pw) => {
                        if (!pw) {
                            Swal.showValidationMessage("Password tidak boleh kosong");
                            return false;
                        }

                        return fetch(snippetsProtector.ajaxUrl, {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded"
                                },
                                body: new URLSearchParams({
                                    action: "check_snippet_password",
                                    password: pw,
                                    nonce: snippetsProtector.nonce
                                })
                            })
                            .then(r => r.json())
                            .then(d => {
                                if (!d.success) throw new Error("Password salah!");
                                return true;
                            })
                            .catch(err => Swal.showValidationMessage(err.message));
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        const overlay = document.getElementById("snippets-protect-overlay");
                        if (overlay) overlay.remove();
                    }
                });
            });
        </script>
<?php
    });

    /* MODIFY LMS MENU NAME */
    add_action('admin_menu', function () {
        global $menu;
        foreach ($menu as $k => $item) {
            if (!empty($item[2]) && $item[2] === 'tutor') {
                $menu[$k][0] = 'Hashiwa LMS';
                break;
            }
        }
    }, 999);

    /* WOOCOMMERCE — ORDER TOPICS MENU */
    add_action('admin_menu', function () {
        remove_submenu_page('woocommerce', 'edit.php?post_type=shop_order');

        add_menu_page(
            'Order Topics',
            'Order Topics',
            'manage_woocommerce',
            'edit.php?post_type=shop_order',
            '',
            'dashicons-media-spreadsheet',
            56
        );
    });
});
