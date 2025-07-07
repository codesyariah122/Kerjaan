<?php
function custom_bottom_navbar_from_wp_menu()
{
    $menu_location = 'bottom-menu';

    if (has_nav_menu($menu_location)) {
        $menu_items = wp_get_nav_menu_items(get_nav_menu_locations()[$menu_location]);

?>
        <style>
            .bottom-navbar {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                background: #ffffff;
                box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
                display: flex;
                justify-content: space-around;
                align-items: center;
                padding: 8px 0;
                z-index: 9999;
            }

            .bottom-navbar a {
                text-decoration: none;
                color: #555;
                font-size: 12px;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                flex: 1;
                transition: color 0.3s ease;
            }

            .bottom-navbar a i {
                font-size: 18px;
                margin-bottom: 4px;
            }

            .bottom-navbar a.active,
            .bottom-navbar a:hover {
                color: #ED2D56;
            }

            .bottom-navbar .menu-item-has-children>a:after {
                content: " ▼";
                font-size: 10px;
                margin-left: 5px;
            }

            .bottom-navbar .sub-menu {
                display: none;
                position: absolute;
                bottom: 50px;
                /* Adjust according to navbar height */
                background-color: #ffffff;
                box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
                padding: 5px 0;
                min-width: 100%;
            }

            .bottom-navbar .menu-item-has-children:hover .sub-menu {
                display: block;
            }

            @media (min-width: 768px) {
                .bottom-navbar {
                    display: none;
                }
            }

            body {
                padding-bottom: 60px;
                /* for navbar space */
            }
        </style>

        <div class="bottom-navbar">
            <?php foreach ($menu_items as $item):
                $is_active = (home_url(add_query_arg([], $item->url)) == home_url(add_query_arg([], $_SERVER['REQUEST_URI']))) ? 'active' : '';

                // Gunakan ikon yang sesuai berdasarkan kelas Font Awesome
                $icon_class = !empty($item->attr_title) ? esc_attr($item->attr_title) : 'fas fa-circle'; // fallback icon

                // Tentukan ikon berdasarkan ID atau nama
                if ($item->title == 'About') {
                    $icon_class = 'fas fa-users'; // Ganti sesuai menu
                } elseif ($item->title == 'News') {
                    $icon_class = 'fas fa-newspaper'; // Ganti sesuai menu
                } elseif ($item->title == 'Companies') {
                    $icon_class = 'fas fa-people-roof'; // Ganti sesuai menu
                } elseif ($item->title == 'Insight') {
                    $icon_class = 'fas fa-image';
                } elseif ($item->title == 'Services') {
                    $icon_class = 'fas fa-people-carry';
                }
            ?>

                <div class="menu-item<?php echo !empty($item->menu_item_parent) ? ' menu-item-has-children' : ''; ?>">
                    <a href="<?php echo esc_url($item->url); ?>" class="<?php echo $is_active; ?>">
                        <i class="<?php echo $icon_class; ?>"></i>
                        <span><?php echo esc_html($item->title); ?></span>
                    </a>

                    <?php if (!empty($item->menu_item_parent)): ?>
                        <div class="sub-menu">
                            <?php
                            $submenu_items = wp_get_nav_menu_items(get_nav_menu_locations()[$menu_location], array('parent' => $item->ID));
                            foreach ($submenu_items as $submenu_item):
                            ?>
                                <a href="<?php echo esc_url($submenu_item->url); ?>"><?php echo esc_html($submenu_item->title); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Font Awesome jika belum ada -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js" crossorigin="anonymous"></script>
<?php
    }
}
add_action('wp_footer', 'custom_bottom_navbar_from_wp_menu');
