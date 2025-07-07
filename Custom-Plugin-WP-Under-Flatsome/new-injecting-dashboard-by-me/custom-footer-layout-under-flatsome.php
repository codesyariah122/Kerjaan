<?php
function mapasitujutrans_footer_shortcode()
{
    // Query CPT kontak_informasi
    $args = array(
        'post_type' => 'kontak_informasi',
        'posts_per_page' => 1,
        'post_status' => 'publish',
    );
    $kontak_query = new WP_Query($args);

    // Default kontak (jika CPT kosong)
    $phone = '081-234-9779';
    $email = 'mapasitujutransnusantara@gmail.com';
    $address = 'Jayapura, Papua';
    $instagram = '@sewamobiljayapura';
    $tiktok = '@rentalmobiljayapura79';

    // Default warna footer (bisa kamu sesuaikan atau ambil dari custom field juga)
    $footer_color_start = '#003E7C';
    $footer_color_end = '#0073B1';

    if ($kontak_query->have_posts()) {
        while ($kontak_query->have_posts()) {
            $kontak_query->the_post();
            $phone = get_post_meta(get_the_ID(), 'phone', true) ?: $phone;
            $email = get_post_meta(get_the_ID(), 'email', true) ?: $email;
            $address = get_field('address', get_the_ID());
            $map = get_post_meta(get_the_ID(), 'google_map', true);
            $instagram = get_post_meta(get_the_ID(), 'instagram', true) ?: $instagram;
            $tiktok = get_post_meta(get_the_ID(), 'tiktok', true) ?: $tiktok;
            $credit = get_post_meta(get_the_ID(), 'credit', true);
            $credit_content = get_post_meta(get_the_ID(), 'credit_content', true);

            // Optional: ambil warna dari meta juga, misal 'footer_color_start' & 'footer_color_end'
            $logo_id_or_url = get_post_meta(get_the_ID(), 'logo', true);
            if (is_numeric($logo_id_or_url)) {
                $logo_url = wp_get_attachment_url($logo_id_or_url);
            } else {
                $logo_url = $logo_id_or_url;
            }
            if (empty($logo_url)) {
                $logo_url = get_template_directory_uri() . '/images/logo.png';
            }

            // Ambil nilai headline dan description
            $headline = get_post_meta(get_the_ID(), 'headline', true);
            $description = get_post_meta(get_the_ID(), 'description', true);

            // Ambil checkbox-nya (hasilnya berupa array jika dicentang, atau false/null jika tidak)
            $show_headline = get_post_meta(get_the_ID(), 'show_headline', true);
            $show_description = get_post_meta(get_the_ID(), 'show_description', true);

            // Default jika tidak dicentang
            $headline_output = '<p class="brand-desc">MAPASITUJU <br><strong>Trans Nusantara default snippet</strong></p>';
            $description_output = '<p class="tagline">Keliling Indonesia Timur <br> dengan mudah & terpercaya default snippet.</p>';

            // Jika dicentang, timpa dengan value dari custom field
            if (is_array($show_headline) && in_array('show', $show_headline) && !empty($headline)) {
                $headline_output = '<p class="brand-desc">' . esc_html($headline) . '</p>';
            }

            if (is_array($show_description) && in_array('show', $show_description) && !empty($description)) {
                $description_output = '<p class="tagline">' . esc_html($description) . '</p>';
            }
            $footer_color_start = get_post_meta(get_the_ID(), 'footer_color_start', true) ?: $footer_color_start;
            $footer_color_end = get_post_meta(get_the_ID(), 'footer_color_end', true) ?: $footer_color_end;
        }
        wp_reset_postdata();
    }

    ob_start();
?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <footer class="footer-mapasitujutrans">
        <div class="footer-container">
            <div class="footer-column">
                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo Mapasitujutrans" class="footer-logo">
                <p class="brand-desc"><?php echo $headline_output; ?></p>
                <p class="tagline"><?php echo $description_output; ?></p>
            </div>

            <div class="footer-column">
                <h4>Kontak</h4>
                <ul>
                    <li>
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?php echo esc_attr($phone); ?>">
                            <?php echo esc_html($phone); ?>
                        </a>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?php echo esc_attr($email); ?>">
                            <?php echo esc_html($email); ?>
                        </a>
                    </li>

                    <?php if (!empty($address['url']) && !empty($address['title'])) : ?>
                        <li><i class="fas fa-map-marker-alt"></i>
                            <a href="<?php echo esc_url($address['url']); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html($address['title']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-column">
                <h4>Ikuti Kami</h4>
                <ul class="social-icons">
                    <?php if (!empty($instagram)) :
                        $instagram_url = 'https://instagram.com/' . ltrim($instagram, '@');
                    ?>
                        <li><i class="fab fa-instagram"></i>
                            <a href="<?php echo esc_url($instagram_url); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html($instagram); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($tiktok)) :
                        $tiktok_url = 'https://www.tiktok.com/@' . ltrim($tiktok, '@');
                    ?>
                        <li><i class="fab fa-tiktok"></i>
                            <a href="<?php echo esc_url($tiktok_url); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html($tiktok); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($facebook)) :
                        $facebook_url = 'https://facebook.com/' . ltrim($facebook, '@');
                    ?>
                        <li><i class="fab fa-facebook"></i>
                            <a href="<?php echo esc_url($facebook_url); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html($facebook); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($youtube)) :
                        $youtube_url = 'https://www.youtube.com/' . ltrim($youtube, '@');
                    ?>
                        <li><i class="fab fa-youtube"></i>
                            <a href="<?php echo esc_url($youtube_url); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html($youtube); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>
            </div>
        </div>
        <?php if (!empty($map)) : ?>
            <div class="footer-map">
                <?= $map; ?>
            </div>
        <?php endif; ?>

        <div class="footer-bottom">
            <p>© 2025 <strong>MAPASITUJU Trans Nusantara</strong> – <?php if (!empty($credit) && in_array('show credit', $credit)): ?> Develope By <?= $credit_content ?><?php else: ?> All Rights Reserved<?php endif; ?></p>
        </div>
    </footer>

    <style>
        .footer-mapasitujutrans {
            background: linear-gradient(135deg, <?php echo esc_attr($footer_color_start); ?>, <?php echo esc_attr($footer_color_end); ?>);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            padding: 60px 20px 30px;
            position: relative;
            overflow: hidden;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .footer-column h4 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #FFD700;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-column li {
            margin-bottom: 10px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: color 0.3s ease;
        }

        .footer-column li i {
            color: #FFFFFF;
            font-size: 18px;
        }

        .footer-column li a {
            color: #FFFFFF;
            font-size: 16px;
        }

        .footer-column li:hover {
            color: #00FFFF;
        }

        .footer-logo {
            width: 140px;
            margin-bottom: 15px;
            filter: drop-shadow(0 0 4px rgba(0, 0, 0, 0.3));
        }

        .brand-desc {
            font-size: 20px;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 5px;
        }

        .tagline {
            font-size: 15px;
            font-style: italic;
            color: #e0f7ff;
        }

        .social-icons li i {
            color: #FFF;
        }

        .social-icons li a {
            color: #FFF;
        }

        .footer-bottom {
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: 40px;
            padding-top: 20px;
            font-size: 14px;
            color: #eeeeee;
        }

        .footer-bottom a {
            color: #fff;
        }

        .footer-map {
            margin-top: 30px;
            border-radius: 8px;
            overflow: hidden;
            /* 	  box-shadow: 0 4px 15px rgba(0,0,0,0.2); */
        }

        @media (max-width: 768px) {
            .footer-container {
                grid-template-columns: 1fr;
                /* satu kolom di mobile */
                text-align: center;
            }

            .footer-column {
                align-items: center;
                justify-content: center;
            }

            .footer-column li {
                justify-content: center;
            }

            .footer-column li a {
                display: inline-block;
            }

            .footer-logo {
                margin-left: auto;
                margin-right: auto;
            }

            .brand-desc,
            .tagline {
                text-align: center;
            }
        }
    </style>
<?php
    return ob_get_clean();
}
add_shortcode('mapasitujutrans_footer', 'mapasitujutrans_footer_shortcode');
