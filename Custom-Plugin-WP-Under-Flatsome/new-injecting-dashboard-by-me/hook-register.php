<?php
function register_custom_menu_location()
{
    register_nav_menu('bottom-menu', 'Bottom Navbar Menu');
}
add_action('after_setup_theme', 'register_custom_menu_location');

function custom_admin_dashboard_text()
{
    global $wp_version;

    // Ganti teks "Welcome to WordPress!" menjadi sesuai keinginan Anda
    $welcome_text = 'Tokoweb.co Porto Management';

    // Ganti teks "Learn more about the 6.5.5 version." sesuai keinginan Anda
    $version_text = 'Develope By Tokoweb.co';

    // Mengganti teks menggunakan filter
    add_filter('gettext', function ($translated_text, $text, $domain) use ($welcome_text, $version_text, $wp_version) {
        if ($text === 'Welcome to WordPress!') {
            $translated_text = $welcome_text;
        }
        if ($text === 'Learn more about the %s version.') {
            $translated_text = sprintf($version_text, $wp_version);
        }
        return $translated_text;
    }, 10, 3);
}
add_action('admin_init', 'custom_admin_dashboard_text');

function move_menus_to_top()
{
    global $menu;

    $snippets_key = null;
    foreach ($menu as $key => $menu_item) {
        if ($menu_item[2] === 'snippets' && $menu_item[2] === 'music_review' && $menu_item[2] === 'film_review') {
            $snippets_key = $key;
        }
    }

    $new_menu = [];
    if ($snippets_key !== null) {
        $new_menu[] = $menu[$snippets_key];
        unset($menu[$snippets_key]);
    }

    $menu = array_merge($new_menu, $menu);
}
add_action('admin_menu', 'move_menus_to_top', 9);

function replace_admin_menu_icons()
{
?>
    <style>
        /* Gambar ikon */
        #toplevel_page_snippets .wp-menu-image,
        #menu-posts-film_review .wp-menu-image,
        #menu-posts-music_review .wp-menu-image {
            background-image: url("https://portomanagement.demo-tokoweb.my.id/wp-content/uploads/2025/04/fav-1-1-2.webp") !important;
            background-size: 20px 20px !important;
            background-repeat: no-repeat;
            background-position: center center;
            width: 30px !important;
            height: 30px !important;
        }

        /* Sembunyikan icon default dari ::before */
        #toplevel_page_snippets .wp-menu-image::before,
        #menu-posts-film_review .wp-menu-image::before,
        #menu-posts-music_review .wp-menu-image::before {
            content: '' !important;
        }

        /* Sembunyikan <img> jika ada */
        #toplevel_page_snippets .wp-menu-image img,
        #menu-posts-film_review .wp-menu-image img,
        #menu-posts-music_review .wp-menu-image img {
            display: none !important;
        }
    </style>
<?php
}
add_action('admin_head', 'replace_admin_menu_icons');
