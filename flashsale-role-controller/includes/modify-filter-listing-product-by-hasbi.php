<?
// ==================== SHORTCODE FUNCTIONS ====================
    /**
     * @modify Puji Ermanto<pujiermanto@gmail.com>
     * @add visible_role for meta_query
     * @return visible_role filter listing
     * */
    public function launch_products_shortcode($atts) {
        $atts = shortcode_atts(array(
            'status' => 'all',
            'limit' => 12,
            'columns' => 3,
            'show_countdown' => 'true',
            'show_price' => 'true',
            'show_excerpt' => 'false',
            'order' => 'launch_date',
            'title' => '',
            'description' => '',
            'class' => '',
            'style' => 'default'
        ), $atts, 'launch_products');
    
        // ====== Build meta_query with role visibility ======
        $role = is_user_logged_in() ? wp_get_current_user()->roles[0] : 'user';
    
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key' => '_enable_launch_countdown',
                'value' => 'yes',
                'compare' => '='
            ),
            array(
                'relation' => 'OR',
                array(
                    'key'     => '_visible_for_role',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key'     => '_visible_for_role',
                    'value'   => '',
                    'compare' => '='
                ),
                array(
                    'key'     => '_visible_for_role',
                    'value'   => $role === 'mitra' ? ['mitra', 'user'] : $role,
                    'compare' => $role === 'mitra' ? 'IN' : '='
                )
            )
        );

    
        // ====== Filter launching status ======
        if ($atts['status'] !== 'all') {
            $current_time = current_time('timestamp');
    
            if ($atts['status'] === 'upcoming') {
                $meta_query[] = array(
                    'key' => '_launch_date',
                    'value' => date('Y-m-d H:i', $current_time),
                    'compare' => '>',
                    'type' => 'DATETIME'
                );
            } elseif ($atts['status'] === 'launched') {
                $meta_query[] = array(
                    'key' => '_launch_date',
                    'value' => date('Y-m-d H:i', $current_time),
                    'compare' => '<=',
                    'type' => 'DATETIME'
                );
            }
        }
    
        // ====== Build WP_Query ======
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'meta_query' => $meta_query
        );
    
        // ====== Sorting ======
        if ($atts['order'] === 'launch_date') {
            $args['meta_key'] = '_launch_date';
            $args['orderby'] = 'meta_value';
            $args['order'] = 'ASC';
        } elseif ($atts['order'] === 'date') {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } elseif ($atts['order'] === 'title') {
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
        } elseif ($atts['order'] === 'price') {
            $args['meta_key'] = '_price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
        }
    
        $products = new WP_Query($args);
    
        if (!$products->have_posts()) {
            return '<div class="launch-products-empty">
                        <p>' . __('Tidak ada produk launch yang ditemukan.', 'wc-launch-countdown') . '</p>
                    </div>';
        }
    
        // ====== Define visual flags ======
        $columns = max(1, min(6, intval($atts['columns'])));
        $show_countdown = $atts['show_countdown'] === 'true';
        $show_price = $atts['show_price'] === 'true';
        $show_excerpt = $atts['show_excerpt'] === 'true';
    
        ob_start();
        ?>
        <div class="launch-products-container <?php echo esc_attr($atts['class']); ?> style-<?php echo esc_attr($atts['style']); ?>">
            <?php if ($atts['title']): ?>
                <h2 class="launch-products-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
    
            <?php if ($atts['description']): ?>
                <p class="launch-products-description"><?php echo esc_html($atts['description']); ?></p>
            <?php endif; ?>
    
            <div class="launch-products-grid columns-<?php echo $columns; ?>">
                <?php while ($products->have_posts()): $products->the_post(); ?>
                    <?php
                    $product = wc_get_product(get_the_ID());
                    $launch_date = get_post_meta(get_the_ID(), '_launch_date', true);
                    $countdown_message = get_post_meta(get_the_ID(), '_countdown_message', true) ?: __('Launching Soon', 'wc-launch-countdown');
                    $launch_message = get_post_meta(get_the_ID(), '_launch_message', true) ?: __('Available Now!', 'wc-launch-countdown');
                    $launch_timestamp = strtotime($launch_date);
                    $current_timestamp = current_time('timestamp');
                    $is_launched = $current_timestamp >= $launch_timestamp;
                    $reminder_count = $this->get_reminder_count(get_the_ID());
                    ?>
    
                    <div class="launch-product-item <?php echo $is_launched ? 'launched' : 'upcoming'; ?>" data-product-id="<?php echo get_the_ID(); ?>">
                        <div class="product-image">
                            <a href="<?php echo $product->get_permalink(); ?>" class="product-link">
                                <?php 
                                if (has_post_thumbnail()) {
                                    echo get_the_post_thumbnail(get_the_ID(), 'medium', array('class' => 'product-img'));
                                } else {
                                    echo '<div class="product-img-placeholder">📦</div>';
                                }
                                ?>
                            </a>
    
                            <?php if (!$is_launched): ?>
                                <div class="launch-badge upcoming-badge">
                                    <span>🕒</span> <?php _e('Coming Soon', 'wc-launch-countdown'); ?>
                                </div>
                            <?php else: ?>
                                <div class="launch-badge launched-badge">
                                    <span>✅</span> <?php _e('Available Now', 'wc-launch-countdown'); ?>
                                </div>
                            <?php endif; ?>
    
                            <?php if ($reminder_count > 0): ?>
                                <div class="reminder-count-badge">
                                    <span>🔔</span> <?php echo $reminder_count; ?>
                                </div>
                            <?php endif; ?>
                        </div>
    
                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="<?php echo $product->get_permalink(); ?>" class="product-title-link">
                                    <?php echo $product->get_name(); ?>
                                </a>
                            </h3>
    
                            <?php if ($show_excerpt && $product->get_short_description()): ?>
                                <div class="product-excerpt">
                                    <?php echo wp_trim_words($product->get_short_description(), 15); ?>
                                </div>
                            <?php endif; ?>
    
                            <?php if ($show_price): ?>
                                <div class="product-price">
                                    <?php echo $product->get_price_html(); ?>
                                </div>
                            <?php endif; ?>
    
                            <?php if ($show_countdown && !$is_launched && $launch_date): ?>
                                <div class="mini-countdown" data-launch="<?php echo $launch_timestamp; ?>">
                                    <div class="countdown-label">
                                        <span class="countdown-icon">⏰</span>
                                        <?php echo esc_html($countdown_message); ?>
                                    </div>
                                    <div class="countdown-timer">
                                        <span class="countdown-part">
                                            <span class="countdown-number days">0</span>
                                            <span class="countdown-text"><?php _e('Hari', 'wc-launch-countdown'); ?></span>
                                        </span>
                                        <span class="countdown-separator">:</span>
                                        <span class="countdown-part">
                                            <span class="countdown-number hours">0</span>
                                            <span class="countdown-text"><?php _e('Jam', 'wc-launch-countdown'); ?></span>
                                        </span>
                                        <span class="countdown-separator">:</span>
                                        <span class="countdown-part">
                                            <span class="countdown-number minutes">0</span>
                                            <span class="countdown-text"><?php _e('Menit', 'wc-launch-countdown'); ?></span>
                                        </span>
                                    </div>
                                </div>
                            <?php elseif ($is_launched): ?>
                                <div class="launch-status launched">
                                    <span class="status-icon">🎉</span>
                                    <span class="status-text"><?php echo esc_html($launch_message); ?></span>
                                </div>
                            <?php endif; ?>
    
                            <div class="product-actions">
                                <?php if ($is_launched && $product->is_purchasable() && $product->is_in_stock()): ?>
                                    <?php if ($product->get_type() === 'simple'): ?>
                                        <form class="cart" method="post" enctype="multipart/form-data">
                                            <button type="submit" name="add-to-cart" value="<?php echo $product->get_id(); ?>" class="add-to-cart-btn single_add_to_cart_button">
                                                <span class="btn-icon">🛒</span>
                                                <span class="btn-text"><?php _e('Add to Cart', 'wc-launch-countdown'); ?></span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?php echo $product->get_permalink(); ?>" class="view-product-btn">
                                            <span class="btn-icon">👁️</span>
                                            <span class="btn-text"><?php _e('Select Options', 'wc-launch-countdown'); ?></span>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?php echo $product->get_permalink(); ?>" class="view-product-btn">
                                        <span class="btn-icon">👁️</span>
                                        <span class="btn-text"><?php _e('View Product', 'wc-launch-countdown'); ?></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
    
            <?php if ($atts['status'] === 'upcoming'): ?>
                <div class="launch-products-footer">
                    <p class="footer-reminder">
                        <span class="reminder-icon">🔔</span>
                        <em><?php _e('Set reminder untuk produk yang Anda minati!', 'wc-launch-countdown'); ?></em>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    
        wp_reset_postdata();
    
        if ($show_countdown) {
            $this->add_shortcode_countdown_script();
        }
    
        return ob_get_clean();
    }


# save aman 
// ==================== SHORTCODE FUNCTIONS ====================
    
    // public function launch_products_shortcode($atts) {
    //     $atts = shortcode_atts(array(
    //         'status' => 'all',
    //         'limit' => 12,
    //         'columns' => 3,
    //         'show_countdown' => 'true',
    //         'show_price' => 'true',
    //         'show_excerpt' => 'false',
    //         'order' => 'launch_date',
    //         'title' => '',
    //         'description' => '',
    //         'class' => '',
    //         'style' => 'default'
    //     ), $atts, 'launch_products');
        
    //     // Query produk dengan countdown enabled
    //     $args = array(
    //         'post_type' => 'product',
    //         'posts_per_page' => intval($atts['limit']),
    //         'post_status' => 'publish',
    //         'meta_query' => array(
    //             array(
    //                 'key' => '_enable_launch_countdown',
    //                 'value' => 'yes',
    //                 'compare' => '='
    //             )
    //         )
    //     );
        
    //     // Filter berdasarkan status
    //     if ($atts['status'] !== 'all') {
    //         $current_time = current_time('timestamp');
            
    //         if ($atts['status'] === 'upcoming') {
    //             $args['meta_query'][] = array(
    //                 'key' => '_launch_date',
    //                 'value' => date('Y-m-d H:i', $current_time),
    //                 'compare' => '>',
    //                 'type' => 'DATETIME'
    //             );
    //         } elseif ($atts['status'] === 'launched') {
    //             $args['meta_query'][] = array(
    //                 'key' => '_launch_date',
    //                 'value' => date('Y-m-d H:i', $current_time),
    //                 'compare' => '<=',
    //                 'type' => 'DATETIME'
    //             );
    //         }
    //     }
        
    //     // Order by
    //     if ($atts['order'] === 'launch_date') {
    //         $args['meta_key'] = '_launch_date';
    //         $args['orderby'] = 'meta_value';
    //         $args['order'] = 'ASC';
    //     } elseif ($atts['order'] === 'date') {
    //         $args['orderby'] = 'date';
    //         $args['order'] = 'DESC';
    //     } elseif ($atts['order'] === 'title') {
    //         $args['orderby'] = 'title';
    //         $args['order'] = 'ASC';
    //     } elseif ($atts['order'] === 'price') {
    //         $args['meta_key'] = '_price';
    //         $args['orderby'] = 'meta_value_num';
    //         $args['order'] = 'ASC';
    //     }
        
    //     $products = new WP_Query($args);
        
    //     if (!$products->have_posts()) {
    //         return '<div class="launch-products-empty">
    //                     <p>' . __('Tidak ada produk launch yang ditemukan.', 'wc-launch-countdown') . '</p>
    //                 </div>';
    //     }
        
    //     $columns = max(1, min(6, intval($atts['columns'])));
    //     $show_countdown = $atts['show_countdown'] === 'true';
    //     $show_price = $atts['show_price'] === 'true';
    //     $show_excerpt = $atts['show_excerpt'] === 'true';
        
    //     ob_start();
    //     ?>
        
    //     <div class="launch-products-container <?php echo esc_attr($atts['class']); ?> style-<?php echo esc_attr($atts['style']); ?>">
    //         <?php if ($atts['title']): ?>
    //             <h2 class="launch-products-title"><?php echo esc_html($atts['title']); ?></h2>
    //         <?php endif; ?>
            
    //         <?php if ($atts['description']): ?>
    //             <p class="launch-products-description"><?php echo esc_html($atts['description']); ?></p>
    //         <?php endif; ?>
            
    //         <div class="launch-products-grid columns-<?php echo $columns; ?>">
    //             <?php while ($products->have_posts()): $products->the_post(); ?>
    //                 <?php
    //                 $product = wc_get_product(get_the_ID());
    //                 $launch_date = get_post_meta(get_the_ID(), '_launch_date', true);
    //                 $countdown_message = get_post_meta(get_the_ID(), '_countdown_message', true);
    //                 $launch_message = get_post_meta(get_the_ID(), '_launch_message', true);
                    
    //                 if (!$countdown_message) $countdown_message = __('Launching Soon', 'wc-launch-countdown');
    //                 if (!$launch_message) $launch_message = __('Available Now!', 'wc-launch-countdown');
                    
    //                 $launch_timestamp = strtotime($launch_date);
    //                 $current_timestamp = current_time('timestamp');
    //                 $is_launched = $current_timestamp >= $launch_timestamp;
                    
    //                 $reminder_count = $this->get_reminder_count(get_the_ID());
    //                 ?>
                    
    //                 <div class="launch-product-item <?php echo $is_launched ? 'launched' : 'upcoming'; ?>" data-product-id="<?php echo get_the_ID(); ?>">
    //                     <div class="product-image">
    //                         <a href="<?php echo $product->get_permalink(); ?>" class="product-link">
    //                             <?php 
    //                             if (has_post_thumbnail()) {
    //                                 echo get_the_post_thumbnail(get_the_ID(), 'medium', array('class' => 'product-img'));
    //                             } else {
    //                                 echo '<div class="product-img-placeholder">📦</div>';
    //                             }
    //                             ?>
    //                         </a>
                            
    //                         <?php if (!$is_launched): ?>
    //                             <div class="launch-badge upcoming-badge">
    //                                 <span>🕒</span> <?php _e('Coming Soon', 'wc-launch-countdown'); ?>
    //                             </div>
    //                         <?php else: ?>
    //                             <div class="launch-badge launched-badge">
    //                                 <span>✅</span> <?php _e('Available Now', 'wc-launch-countdown'); ?>
    //                             </div>
    //                         <?php endif; ?>
                            
    //                         <?php if ($reminder_count > 0): ?>
    //                             <div class="reminder-count-badge">
    //                                 <span>🔔</span> <?php echo $reminder_count; ?>
    //                             </div>
    //                         <?php endif; ?>
    //                     </div>
                        
    //                     <div class="product-info">
    //                         <h3 class="product-title">
    //                             <a href="<?php echo $product->get_permalink(); ?>" class="product-title-link">
    //                                 <?php echo $product->get_name(); ?>
    //                             </a>
    //                         </h3>
                            
    //                         <?php if ($show_excerpt && $product->get_short_description()): ?>
    //                             <div class="product-excerpt">
    //                                 <?php echo wp_trim_words($product->get_short_description(), 15); ?>
    //                             </div>
    //                         <?php endif; ?>
                            
    //                         <?php if ($show_price): ?>
    //                             <div class="product-price">
    //                                 <?php echo $product->get_price_html(); ?>
    //                             </div>
    //                         <?php endif; ?>
                            
    //                         <?php if ($show_countdown && !$is_launched && $launch_date): ?>
    //                             <div class="mini-countdown" data-launch="<?php echo $launch_timestamp; ?>">
    //                                 <div class="countdown-label">
    //                                     <span class="countdown-icon">⏰</span>
    //                                     <?php echo esc_html($countdown_message); ?>
    //                                 </div>
    //                                 <div class="countdown-timer">
    //                                     <span class="countdown-part">
    //                                         <span class="countdown-number days">0</span>
    //                                         <span class="countdown-text"><?php _e('Hari', 'wc-launch-countdown'); ?></span>
    //                                     </span>
    //                                     <span class="countdown-separator">:</span>
    //                                     <span class="countdown-part">
    //                                         <span class="countdown-number hours">0</span>
    //                                         <span class="countdown-text"><?php _e('Jam', 'wc-launch-countdown'); ?></span>
    //                                     </span>
    //                                     <span class="countdown-separator">:</span>
    //                                     <span class="countdown-part">
    //                                         <span class="countdown-number minutes">0</span>
    //                                         <span class="countdown-text"><?php _e('Menit', 'wc-launch-countdown'); ?></span>
    //                                     </span>
    //                                 </div>
    //                             </div>
    //                         <?php elseif ($is_launched): ?>
    //                             <div class="launch-status launched">
    //                                 <span class="status-icon">🎉</span>
    //                                 <span class="status-text"><?php echo esc_html($launch_message); ?></span>
    //                             </div>
    //                         <?php endif; ?>
                            
    //                         <div class="product-actions">
    //                             <?php if ($is_launched && $product->is_purchasable() && $product->is_in_stock()): ?>
    //                                 <?php if ($product->get_type() === 'simple'): ?>
    //                                     <form class="cart" method="post" enctype="multipart/form-data">
    //                                         <button type="submit" name="add-to-cart" value="<?php echo $product->get_id(); ?>" class="add-to-cart-btn single_add_to_cart_button">
    //                                             <span class="btn-icon">🛒</span>
    //                                             <span class="btn-text"><?php _e('Add to Cart', 'wc-launch-countdown'); ?></span>
    //                                         </button>
    //                                     </form>
    //                                 <?php else: ?>
    //                                     <a href="<?php echo $product->get_permalink(); ?>" class="view-product-btn">
    //                                         <span class="btn-icon">👁️</span>
    //                                         <span class="btn-text"><?php _e('Select Options', 'wc-launch-countdown'); ?></span>
    //                                     </a>
    //                                 <?php endif; ?>
    //                             <?php else: ?>
    //                                 <a href="<?php echo $product->get_permalink(); ?>" class="view-product-btn">
    //                                     <span class="btn-icon">👁️</span>
    //                                     <span class="btn-text"><?php _e('View Product', 'wc-launch-countdown'); ?></span>
    //                                 </a>
    //                             <?php endif; ?>
    //                         </div>
    //                     </div>
    //                 </div>
                    
    //             <?php endwhile; ?>
    //         </div>
            
    //         <?php if ($atts['status'] === 'upcoming'): ?>
    //             <div class="launch-products-footer">
    //                 <p class="footer-reminder">
    //                     <span class="reminder-icon">🔔</span>
    //                     <em><?php _e('Set reminder untuk produk yang Anda minati!', 'wc-launch-countdown'); ?></em>
    //                 </p>
    //             </div>
    //         <?php endif; ?>
    //     </div>
        
    //     <?php
    //     wp_reset_postdata();
        
    //     // Add JavaScript untuk countdown
    //     if ($show_countdown) {
    //         $this->add_shortcode_countdown_script();
    //     }
        
    //     return ob_get_clean();
    // }

// public function launch_products_shortcode($atts) {
//         $atts = shortcode_atts(array(
//             'status' => 'all',
//             'limit' => 12,
//             'columns' => 3,
//             'show_countdown' => 'true',
//             'show_price' => 'true',
//             'show_excerpt' => 'false',
//             'order' => 'launch_date',
//             'title' => '',
//             'description' => '',
//             'class' => '',
//             'style' => 'default'
//         ), $atts, 'launch_products');
        
//         // Query produk dengan countdown enabled
//         $args = array(
//             'post_type' => 'product',
//             'posts_per_page' => intval($atts['limit']),
//             'post_status' => 'publish',
//             'meta_query' => array(
//                 array(
//                     'key' => '_enable_launch_countdown',
//                     'value' => 'yes',
//                     'compare' => '='
//                 )
//             )
//         );
        
//         // Filter berdasarkan status
//         if ($atts['status'] !== 'all') {
//             $current_time = current_time('timestamp');
            
//             if ($atts['status'] === 'upcoming') {
//                 $args['meta_query'][] = array(
//                     'key' => '_launch_date',
//                     'value' => date('Y-m-d H:i', $current_time),
//                     'compare' => '>',
//                     'type' => 'DATETIME'
//                 );
//             } elseif ($atts['status'] === 'launched') {
//                 $args['meta_query'][] = array(
//                     'key' => '_launch_date',
//                     'value' => date('Y-m-d H:i', $current_time),
//                     'compare' => '<=',
//                     'type' => 'DATETIME'
//                 );
//             }
//         }
        
//         // Order by
//         if ($atts['order'] === 'launch_date') {
//             $args['meta_key'] = '_launch_date';
//             $args['orderby'] = 'meta_value';
//             $args['order'] = 'ASC';
//         } elseif ($atts['order'] === 'date') {
//             $args['orderby'] = 'date';
//             $args['order'] = 'DESC';
//         } elseif ($atts['order'] === 'title') {
//             $args['orderby'] = 'title';
//             $args['order'] = 'ASC';
//         } elseif ($atts['order'] === 'price') {
//             $args['meta_key'] = '_price';
//             $args['orderby'] = 'meta_value_num';
//             $args['order'] = 'ASC';
//         }
        
//         $products = new WP_Query($args);
        
//         if (!$products->have_posts()) {
//             return '<div class="launch-products-empty">
//                         <p>' . __('Tidak ada produk launch yang ditemukan.', 'wc-launch-countdown') . '</p>
//                     </div>';
//         }
        
//         $columns = max(1, min(6, intval($atts['columns'])));
//         $show_countdown = $atts['show_countdown'] === 'true';
//         $show_price = $atts['show_price'] === 'true';
//         $show_excerpt = $atts['show_excerpt'] === 'true';
        
//         ob_start();
//         ?>
        
//         <div class="launch-products-container <?php echo esc_attr($atts['class']); ?> style-<?php echo esc_attr($atts['style']); ?>">
//             <?php if ($atts['title']): ?>
//                 <h2 class="launch-products-title"><?php echo esc_html($atts['title']); ?></h2>
//             <?php endif; ?>
            
//             <?php if ($atts['description']): ?>
//                 <p class="launch-products-description"><?php echo esc_html($atts['description']); ?></p>
//             <?php endif; ?>
            
//             <div class="launch-products-grid columns-<?php echo $columns; ?>">
//                 <?php while ($products->have_posts()): $products->the_post(); ?>
//                     <?php
//                     $product = wc_get_product(get_the_ID());
//                     $launch_date = get_post_meta(get_the_ID(), '_launch_date', true);
//                     $countdown_message = get_post_meta(get_the_ID(), '_countdown_message', true);
//                     $launch_message = get_post_meta(get_the_ID(), '_launch_message', true);
                    
//                     if (!$countdown_message) $countdown_message = __('Launching Soon', 'wc-launch-countdown');
//                     if (!$launch_message) $launch_message = __('Available Now!', 'wc-launch-countdown');
                    
//                     $launch_timestamp = strtotime($launch_date);
//                     $current_timestamp = current_time('timestamp');
//                     $is_launched = $current_timestamp >= $launch_timestamp;
                    
//                     $reminder_count = $this->get_reminder_count(get_the_ID());
//                     ?>
                    
//                     <div class="launch-product-item <?php echo $is_launched ? 'launched' : 'upcoming'; ?>" data-product-id="<?php echo get_the_ID(); ?>">
//                         <div class="product-image">
//                             <a href="<?php echo $product->get_permalink(); ?>" class="product-link">
//                                 <?php 
//                                 if (has_post_thumbnail()) {
//                                     echo get_the_post_thumbnail(get_the_ID(), 'medium', array('class' => 'product-img'));
//                                 } else {
//                                     echo '<div class="product-img-placeholder">📦</div>';
//                                 }
//                                 ?>
//                             </a>
                            
//                             <?php if (!$is_launched): ?>
//                                 <div class="launch-badge upcoming-badge">
//                                     <span>🕒</span> <?php _e('Coming Soon', 'wc-launch-countdown'); ?>
//                                 </div>
//                             <?php else: ?>
//                                 <div class="launch-badge launched-badge">
//                                     <span>✅</span> <?php _e('Available Now', 'wc-launch-countdown'); ?>
//                                 </div>
//                             <?php endif; ?>
                            
//                             <?php if ($reminder_count > 0): ?>
//                                 <div class="reminder-count-badge">
//                                     <span>🔔</span> <?php echo $reminder_count; ?>
//                                 </div>
//                             <?php endif; ?>
//                         </div>
                        
//                         <div class="product-info">
//                             <h3 class="product-title">
//                                 <a href="<?php echo $product->get_permalink(); ?>" class="product-title-link">
//                                     <?php echo $product->get_name(); ?>
//                                 </a>
//                             </h3>
                            
//                             <?php if ($show_excerpt && $product->get_short_description()): ?>
//                                 <div class="product-excerpt">
//                                     <?php echo wp_trim_words($product->get_short_description(), 15); ?>
//                                 </div>
//                             <?php endif; ?>
                            
//                             <?php if ($show_price): ?>
//                                 <div class="product-price">
//                                     <?php echo $product->get_price_html(); ?>
//                                 </div>
//                             <?php endif; ?>
                            
//                             <?php if ($show_countdown && !$is_launched && $launch_date): ?>
//                                 <div class="mini-countdown" data-launch="<?php echo $launch_timestamp; ?>">
//                                     <div class="countdown-label">
//                                         <span class="countdown-icon">⏰</span>
//                                         <?php echo esc_html($countdown_message); ?>
//                                     </div>
//                                     <div class="countdown-timer">
//                                         <span class="countdown-part">
//                                             <span class="countdown-number days">0</span>
//                                             <span class="countdown-text"><?php _e('Hari', 'wc-launch-countdown'); ?></span>
//                                         </span>
//                                         <span class="countdown-separator">:</span>
//                                         <span class="countdown-part">
//                                             <span class="countdown-number hours">0</span>
//                                             <span class="countdown-text"><?php _e('Jam', 'wc-launch-countdown'); ?></span>
//                                         </span>
//                                         <span class="countdown-separator">:</span>
//                                         <span class="countdown-part">
//                                             <span class="countdown-number minutes">0</span>
//                                             <span class="countdown-text"><?php _e('Menit', 'wc-launch-countdown'); ?></span>
//                                         </span>
//                                     </div>
//                                 </div>
//                             <?php elseif ($is_launched): ?>
//                                 <div class="launch-status launched">
//                                     <span class="status-icon">🎉</span>
//                                     <span class="status-text"><?php echo esc_html($launch_message); ?></span>
//                                 </div>
//                             <?php endif; ?>
                            
//                             <div class="product-actions">
//                                 <?php if ($is_launched && $product->is_purchasable() && $product->is_in_stock()): ?>
//                                     <?php if ($product->get_type() === 'simple'): ?>
//                                         <form class="cart" method="post" enctype="multipart/form-data">
//                                             <button type="submit" name="add-to-cart" value="<?php echo $product->get_id(); ?>" class="add-to-cart-btn single_add_to_cart_button">
//                                                 <span class="btn-icon">🛒</span>
//                                                 <span class="btn-text"><?php _e('Add to Cart', 'wc-launch-countdown'); ?></span>
//                                             </button>
//                                         </form>
//                                     <?php else: ?>
//                                         <a href="<?php echo $product->get_permalink(); ?>" class="view-product-btn">
//                                             <span class="btn-icon">👁️</span>
//                                             <span class="btn-text"><?php _e('Select Options', 'wc-launch-countdown'); ?></span>
//                                         </a>
//                                     <?php endif; ?>
//                                 <?php else: ?>
//                                     <a href="<?php echo $product->get_permalink(); ?>" class="view-product-btn">
//                                         <span class="btn-icon">👁️</span>
//                                         <span class="btn-text"><?php _e('View Product', 'wc-launch-countdown'); ?></span>
//                                     </a>
//                                 <?php endif; ?>
//                             </div>
//                         </div>
//                     </div>
                    
//                 <?php endwhile; ?>
//             </div>
            
//             <?php if ($atts['status'] === 'upcoming'): ?>
//                 <div class="launch-products-footer">
//                     <p class="footer-reminder">
//                         <span class="reminder-icon">🔔</span>
//                         <em><?php _e('Set reminder untuk produk yang Anda minati!', 'wc-launch-countdown'); ?></em>
//                     </p>
//                 </div>
//             <?php endif; ?>
//         </div>
        
//         <?php
//         wp_reset_postdata();
        
//         // Add JavaScript untuk countdown
//         if ($show_countdown) {
//             $this->add_shortcode_countdown_script();
//         }
        
//         return ob_get_clean();
//     }