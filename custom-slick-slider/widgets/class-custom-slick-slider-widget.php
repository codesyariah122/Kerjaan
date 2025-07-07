<?php

/**
 * Author : PujiErmanto<pujiermanto@gmail.com> | AKA Vickerness | AKA Dunkelheit
 * @return widget_elementor_hook
 */
class Custom_Slick_Slider_Widget extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'custom_slick_slider';
    }

    public function get_title()
    {
        return __('Custom Slick Slider', 'custom-slick-slider');
    }

    public function get_icon()
    {
        return 'eicon-slider-full-screen';
    }

    public function get_categories()
    {
        return ['general'];
    }

    public function get_keywords()
    {
        return ['slider', 'slick', 'carousel', 'hero'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'slider_content_section',
            [
                'label' => __('Slides Content', 'custom-slick-slider'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control('bg_image', [
            'label' => __('Background Image', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => 'https://via.placeholder.com/1200x600'],
        ]);

        $repeater->add_control('sub_title', [
            'label' => __('Sub Title', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Sub Title Here',
        ]);

        $repeater->add_control('title', [
            'label' => __('Title', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Main Title Here',
        ]);

        $repeater->add_control('description', [
            'label' => __('Description', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => 'Short description about this slide.',
        ]);

        $repeater->add_control('button_text', [
            'label' => __('Button Text', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Shop Now',
        ]);

        $repeater->add_control('button_link', [
            'label' => __('Button Link', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://your-link.com',
        ]);

        $repeater->add_control('vertical_alignment', [
            'label' => __('Vertical Align (Text + Image)', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'center',
            'options' => [
                'start' => __('Top', 'custom-slick-slider'),
                'center' => __('Center', 'custom-slick-slider'),
                'end' => __('Bottom', 'custom-slick-slider'),
            ],
        ]);

        $repeater->add_control('image_alt', [
            'label' => __('Image Alt Text', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        $repeater->add_control('image_alignment', [
            'label' => __('Image Position', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'left' => [
                    'title' => __('Left', 'custom-slick-slider'),
                    'icon' => 'eicon-h-align-left',
                ],
                'right' => [
                    'title' => __('Right', 'custom-slick-slider'),
                    'icon' => 'eicon-h-align-right',
                ],
            ],
            'default' => 'right',
            'toggle' => true,
        ]);

        $repeater->add_control('image_radius', [
            'label' => __('Image Border Radius', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 100,
                ],
            ],
            'default' => [
                'size' => 12,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .slider-image img' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $repeater->add_control('image_max_width', [
            'label' => __('Image Max Width (px)', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 100,
            'max' => 1200,
            'default' => 500,
        ]);

        $repeater->add_control('pattern_bg', [
            'label' => __('Pattern Background', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => ''],
            'description' => __('Optional decorative background image (e.g., pattern PNG/SVG)', 'custom-slick-slider'),
        ]);

        $repeater->add_control('pattern_size', [
            'label' => __('Pattern Size', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'auto' => 'Auto',
                'contain' => 'Contain',
                'cover' => 'Cover',
                'initial' => 'Initial',
                '100px' => '100px',
                '200px' => '200px',
                'custom' => 'Custom',
            ],
            'default' => 'auto',
        ]);

        $repeater->add_control('pattern_position', [
            'label' => __('Pattern Position', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'left top' => 'Left Top',
                'center center' => 'Center',
                'right bottom' => 'Right Bottom',
                'custom' => 'Custom',
            ],
            'default' => 'center center',
        ]);

        $repeater->add_control('pattern_repeat', [
            'label' => __('Pattern Repeat', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'no-repeat' => 'No Repeat',
                'repeat' => 'Repeat',
                'repeat-x' => 'Repeat X',
                'repeat-y' => 'Repeat Y',
            ],
            'default' => 'repeat',
        ]);

        $repeater->add_control('pattern_opacity', [
            'label' => __('Pattern Opacity (%)', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 100,
                ],
            ],
            'default' => [
                'size' => 100,
            ],
        ]);

        $this->add_control('is_full_width', [
            'label' => __('Full Width Slider', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => __('Yes', 'custom-slick-slider'),
            'label_off' => __('No', 'custom-slick-slider'),
            'return_value' => 'yes',
            'default' => '',
        ]);

        $this->add_control('slides', [
            'label' => __('Slides', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'default' => [],
            'title_field' => '{{{ title }}}',
        ]);

        $this->add_control('image_size', [
            'label' => __('Image Size', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'full',
            'options' => [
                'thumbnail' => __('Thumbnail', 'custom-slick-slider'),
                'medium'    => __('Medium', 'custom-slick-slider'),
                'large'     => __('Large', 'custom-slick-slider'),
                'full'      => __('Full', 'custom-slick-slider'),
            ],
        ]);

        $this->add_control('slider_height', [
            'label' => __('Slider Height (Desktop)', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 300,
                    'max' => 1200,
                ],
            ],
            'default' => [
                'size' => 600,
                'unit' => 'px',
            ],
        ]);

        $this->add_control('slider_height_mobile', [
            'label' => __('Slider Height (Mobile)', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 200,
                    'max' => 1000,
                ],
            ],
            'default' => [
                'size' => 400,
                'unit' => 'px',
            ],
        ]);

        $this->add_control('dots_position', [
            'label' => __('Dots Position', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'inside' => 'Inside (bottom 30px)',
                'outside' => 'Outside (di luar bawah)',
                'bottom-10' => 'Bottom 10px',
                'none' => 'Hide Dots',
            ],
            'default' => 'inside',
        ]);

        $this->add_control('reverse_layout', [
            'label' => __('Reverse Layout (Image Left)', 'custom-slick-slider'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => __('Yes', 'custom-slick-slider'),
            'label_off' => __('No', 'custom-slick-slider'),
            'return_value' => 'yes',
            'default' => '',
        ]);


        $this->end_controls_section();
    }

    public function render()
    {
        $settings = $this->get_settings_for_display();
        if (!empty($settings['slider_height_mobile']['size'])) {
            echo '<style>
            @media (max-width: 768px) {
                .slider-item {
                    --slider-height-mobile: ' . esc_attr($settings['slider_height_mobile']['size']) . 'px;
                }
            }
        </style>';
        }
?>
        <div class="animated-slider<?php echo ($settings['is_full_width'] === 'yes') ? ' slider-fullwidth' : ''; ?>" id="home-slider-6">

            <?php foreach ($settings['slides'] as $slide): ?>
                <?php
                $has_pattern = !empty($slide['pattern_bg']['url']);
                ?>
                <?php
                $style = 'min-height: ' . esc_attr($settings['slider_height']['size']) . 'px;';
                if (!empty($slide['pattern_bg']['url'])) {
                    $style .= ' background-image: url(' . esc_url($slide['pattern_bg']['url']) . ');';
                    $style .= ' background-size: ' . esc_attr($slide['pattern_size']) . ';';
                    $style .= ' background-position: ' . esc_attr($slide['pattern_position']) . ';';
                    $style .= ' background-repeat: ' . esc_attr($slide['pattern_repeat']) . ';';
                    $style .= ' opacity: ' . (intval($slide['pattern_opacity']['size']) / 100) . ';';
                }
                ?>
                <div class="slider-item position-relative <?php echo !empty($slide['pattern_bg']['url']) ? 'has-pattern' : ''; ?> dots-<?php echo esc_attr($settings['dots_position']); ?> <?php echo $settings['reverse_layout'] === 'yes' ? 'layout-reverse' : ''; ?>" style="<?php echo $style; ?>">

                    <?php
                    $is_image_right = $slide['image_alignment'] === 'right';
                    ?>
                    <div class="row align-items-<?php echo esc_attr($slide['vertical_alignment'] ?? 'center'); ?>">
                        <?php if (!$is_image_right): ?>
                            <div class="col-md-6 slider-image text-center">
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($slide['bg_image']['id'], $settings['image_size'])); ?>"
                                    alt="<?php echo esc_attr($slide['image_alt']); ?>"
                                    class="img-fluid"
                                    style="max-width: <?php echo esc_attr($slide['image_max_width']); ?>px; border-radius: <?php echo esc_attr($slide['image_radius']['size'] ?? 12); ?>px;" />
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6 slider-content">
                            <?php if (!empty($slide['sub_title'])): ?>
                                <span class="sub-title"><?php echo esc_html($slide['sub_title']); ?></span>
                            <?php endif; ?>

                            <?php if (!empty($slide['title'])): ?>
                                <h1 class="title"><?php echo esc_html($slide['title']); ?></h1>
                            <?php endif; ?>

                            <?php if (!empty($slide['description'])): ?>
                                <p class="text-lg"><?php echo esc_html($slide['description']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($slide['button_link']['url'])): ?>
                                <a href="<?php echo esc_url($slide['button_link']['url']); ?>" class="btn btn-lg btn-primary"
                                    <?php echo $slide['button_link']['is_external'] ? 'target="_blank"' : ''; ?>>
                                    <?php echo esc_html($slide['button_text']); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($is_image_right): ?>
                            <div class="col-md-6 slider-image text-center">
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($slide['bg_image']['id'], $settings['image_size'])); ?>"
                                    alt="<?php echo esc_attr($slide['image_alt']); ?>"
                                    class="img-fluid"
                                    style="max-width: <?php echo esc_attr($slide['image_max_width']); ?>px; border-radius: <?php echo esc_attr($slide['image_radius']['size'] ?? 12); ?>px;" />
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
<?php
    }
}
