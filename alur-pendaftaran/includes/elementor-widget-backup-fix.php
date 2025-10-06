<?php
if (! defined('ABSPATH')) exit;

add_action('elementor/widgets/widgets_registered', function ($widgets_manager) {
    if (! class_exists('\\Elementor\\Widget_Base')) return;

    class Elementor_Alur_Widget extends \Elementor\Widget_Base
    {
        public function get_name()
        {
            return 'alur_widget';
        }
        public function get_title()
        {
            return esc_html__('Alur Pendaftaran', 'alur-pendaftaran');
        }
        public function get_icon()
        {
            return 'eicon-post-list';
        }
        public function get_categories()
        {
            return ['general'];
        }

        // 👉 Tambahkan kontrol di panel Elementor
        protected function register_controls()
        {
            $this->start_controls_section(
                'section_header',
                ['label' => esc_html__('Header', 'alur-pendaftaran')]
            );

            $this->add_control(
                'logo',
                [
                    'label' => esc_html__('Logo', 'alur-pendaftaran'),
                    'type' => \Elementor\Controls_Manager::MEDIA,
                    'default' => [
                        'url' => \Elementor\Utils::get_placeholder_image_src(),
                    ],
                ]
            );

            $this->add_control(
                'headline',
                [
                    'label' => esc_html__('Judul Headline', 'alur-pendaftaran'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => esc_html__('Alur Pendaftaran PPDB', 'alur-pendaftaran'),
                ]
            );

            $this->add_control(
                'headline_color',
                [
                    'label' => esc_html__('Warna Judul', 'alur-pendaftaran'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .ppdb-headline' => 'color: {{VALUE}};',
                    ],
                    'default' => '#f97316', // oranye default
                ]
            );

            $this->add_control(
                'headline_align',
                [
                    'label' => esc_html__('Posisi Judul', 'alur-pendaftaran'),
                    'type' => \Elementor\Controls_Manager::CHOOSE,
                    'options' => [
                        'left' => ['title' => 'Left', 'icon' => 'eicon-text-align-left'],
                        'center' => ['title' => 'Center', 'icon' => 'eicon-text-align-center'],
                        'right' => ['title' => 'Right', 'icon' => 'eicon-text-align-right'],
                    ],
                    'default' => 'center',
                    'toggle' => true,
                    'selectors' => [
                        '{{WRAPPER}} .ppdb-headline' => 'text-align: {{VALUE}};',
                    ],
                ]
            );

            $this->end_controls_section();
        }

        protected function render()
        {
            $settings = $this->get_settings_for_display();

            echo '<div class="ppdb-flow">';

            // 👉 Bagian header
            echo '<div class="ppdb-header" style="text-align:center; margin-bottom:20px;">';
            if (!empty($settings['logo']['url'])) {
                echo '<div class="ppdb-logo"><img src="' . esc_url($settings['logo']['url']) . '" alt="Logo" style="max-height:80px;"></div>';
            }
            echo '<h2 class="ppdb-headline">' . esc_html($settings['headline']) . '</h2>';
            echo '</div>';

            // 👉 Bagian steps
            echo '<div class="ppdb-grid">';

            $alur = get_posts([
                'post_type'      => 'alur_pendaftaran',
                'posts_per_page' => -1,
                'orderby'        => 'meta_value_num',
                'meta_key'       => '_alur_number',
                'order'          => 'ASC',
            ]);

            foreach ($alur as $step) {
                $num  = get_post_meta($step->ID, '_alur_number', true);
                $icon = trim(get_post_meta($step->ID, '_alur_icon', true));
                $desc = get_post_meta($step->ID, '_alur_desc', true);
                $col  = get_post_meta($step->ID, '_alur_column', true);

                $reverse  = ($col === 'right') ? 'reverse' : '';
                $numClass = ($col === 'right') ? 'ppdb-number left' : 'ppdb-number';

                // ✅ Render icon fleksibel (Font Awesome, emoji, atau url svg/png)
                $icon_html = '';
                if ($icon) {
                    if (preg_match('/^(fa[srb]?\s|fa-)/', $icon)) {
                        // Kalau hanya "fa-envelope" → tambahkan prefix "fas"
                        if (strpos($icon, 'fa-') === 0) {
                            $icon = 'fas ' . $icon;
                        }
                        $icon_html = '<i class="' . esc_attr($icon) . '"></i>';
                    } elseif (filter_var($icon, FILTER_VALIDATE_URL)) {
                        // Jika input url (svg/png/jpg) → <img>
                        $icon_html = '<img src="' . esc_url($icon) . '" alt="icon" style="max-height:40px;">';
                    } else {
                        // Default anggap emoji atau teks
                        $icon_html = esc_html($icon);
                    }
                }

                echo '<div class="ppdb-card ' . esc_attr($reverse) . '">';
                if ($col === 'right') {
                    echo '<div class="' . esc_attr($numClass) . '">' . esc_html($num) . '</div>';
                    echo '<div class="ppdb-text">' . esc_html($step->post_title) . '<br>' . esc_html($desc) . '</div>';
                    echo '<div class="ppdb-icon">' . $icon_html . '</div>';
                } else {
                    echo '<div class="ppdb-icon">' . $icon_html . '</div>';
                    echo '<div class="ppdb-text">' . esc_html($step->post_title) . '<br>' . esc_html($desc) . '</div>';
                    echo '<div class="' . esc_attr($numClass) . '">' . esc_html($num) . '</div>';
                }
                echo '</div>';
            }

            echo '</div></div>';
        }
    }

    // Register widget
    if (method_exists($widgets_manager, 'register')) {
        $widgets_manager->register(new Elementor_Alur_Widget());
    } else {
        $widgets_manager->register_widget_type(new Elementor_Alur_Widget());
    }
}, 10, 1);
