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

        // 👉 Kontrol di Elementor panel
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
                    'default' => '#f97316',
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

            // Header
            echo '<div class="ppdb-header" style="text-align:center; margin-bottom:30px;">';
            if (!empty($settings['logo']['url'])) {
                echo '<div class="ppdb-logo"><img src="' . esc_url($settings['logo']['url']) . '" alt="Logo"></div>';
            }
            echo '<h2 class="ppdb-headline">' . esc_html($settings['headline']) . '</h2>';
            echo '</div>';

            // Grid
            echo '<div class="ppdb-grid">';

            $alur = get_posts([
                'post_type'      => 'alur_pendaftaran',
                'posts_per_page' => -1,
                'orderby'        => 'meta_value_num',
                'meta_key'       => '_alur_number',
                'order'          => 'ASC',
            ]);

            foreach ($alur as $step) {
                $num     = get_post_meta($step->ID, '_alur_number', true);
                $icon    = trim(get_post_meta($step->ID, '_alur_icon', true));
                $desc    = get_post_meta($step->ID, '_alur_desc', true);
                $col     = get_post_meta($step->ID, '_alur_column', true); // left / right

                // Icon
                $icon_html = '';
                if ($icon) {
                    if (preg_match('/^(fa[srb]?\s|fa-)/', $icon)) {
                        if (strpos($icon, 'fa-') === 0) $icon = 'fas ' . $icon;
                        $icon_html = '<i class="' . esc_attr($icon) . '"></i>';
                    } elseif (filter_var($icon, FILTER_VALIDATE_URL)) {
                        $icon_html = '<img src="' . esc_url($icon) . '" alt="icon">';
                    } else {
                        $icon_html = esc_html($icon);
                    }
                }

                $posClass = ($col === 'right') ? 'right' : 'left';

                echo '<div class="ppdb-step ' . esc_attr($posClass) . '">';
                echo '  <div class="ppdb-number">' . esc_html($num) . '</div>'; // angka di luar bubble
                echo '  <div class="ppdb-inner">'; // bubble isi teks + icon
                echo '      <div class="ppdb-body">';
                echo '          <div class="ppdb-title-text">' . esc_html($step->post_title) . '</div>';
                if ($desc) echo '      <div class="ppdb-desc">' . esc_html($desc) . '</div>';
                echo '      </div>';
                echo '      <div class="ppdb-icon">' . $icon_html . '</div>';
                echo '  </div>';
                echo '</div>';
            }

            echo '</div></div>';
        }
    }

    if (method_exists($widgets_manager, 'register')) {
        $widgets_manager->register(new Elementor_Alur_Widget());
    } else {
        $widgets_manager->register_widget_type(new Elementor_Alur_Widget());
    }
}, 10, 1);
