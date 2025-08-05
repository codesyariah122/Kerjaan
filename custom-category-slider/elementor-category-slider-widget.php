<?php
if (!defined('ABSPATH')) exit;

use Elementor\Group_Control_Typography;
use Elementor\Controls_Manager;

class CCSP_Category_Slider_Widget extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'ccsp_category_slider';
    }

    public function get_title()
    {
        return __('Category Slider', 'ccsp');
    }

    public function get_icon()
    {
        return 'eicon-slider-push';
    }

    public function get_categories()
    {
        return ['basic'];
    }

    protected function register_controls()
    {
        $this->start_controls_section('content_section', [
            'label' => __('Settings', 'ccsp'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('title', [
            'label' => __('Section Title', 'ccsp'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Browse By Categories',
        ]);

        $this->add_control('taxonomy', [
            'label' => __('Taxonomy', 'ccsp'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'product_cat',
            'description' => 'Masukkan taxonomy, contoh: product_cat',
        ]);

        $this->add_control('limit', [
            'label' => __('Number of Categories', 'ccsp'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 12,
            'min' => 1,
            'max' => 50,
        ]);

        // **Tambah upload background image control**
        $this->add_control('category_bg_image', [
            'label' => __('Category Background Image', 'ccsp'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'description' => 'Background image yang dipakai di setiap kotak kategori',
        ]);

        $this->end_controls_section();

        $this->start_controls_section('arrow_style_section', [
            'label' => __('Slider Arrows', 'ccsp'),
            'tab' => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('arrow_color', [
            'label' => __('Arrow Icon Color', 'ccsp'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .slider-arrow .btn-icon' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('arrow_bg_color', [
            'label' => __('Arrow Background', 'ccsp'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .slider-arrow .btn-icon' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('arrow_border_color', [
            'label' => __('Arrow Border', 'ccsp'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .slider-arrow .btn-icon' => 'border-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('arrow_hover_bg_color', [
            'label' => __('Arrow Hover Background', 'ccsp'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .slider-arrow .btn-icon:hover' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('arrow_hover_border_color', [
            'label' => __('Arrow Hover Border', 'ccsp'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .slider-arrow .btn-icon:hover' => 'border-color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();


        $this->start_controls_section('typography_section', [
            'label' => __('Typography', 'ccsp'),
            'tab' => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control(
            'category_title_color',
            [
                'label' => __('Category Title Color', 'ccsp'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .category-item-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'section_title_typography',
                'label' => __('Section Title Typography', 'ccsp'),
                'selector' => '{{WRAPPER}} .section-title .title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'category_title_typography',
                'label' => __('Category Title Typography', 'ccsp'),
                'selector' => '{{WRAPPER}} .category-item-title',
            ]
        );

        // Kalau mau atur align text-nya juga
        $this->add_responsive_control(
            'section_title_align',
            [
                'label' => __('Section Title Alignment', 'ccsp'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'ccsp'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'ccsp'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'ccsp'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .section-title .title' => 'text-align: {{VALUE}};',
                ],
                'toggle' => true,
            ]
        );

        $this->add_responsive_control(
            'section_title_margin',
            [
                'label' => __('Section Title Margin', 'ccsp'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .section-title .title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );



        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        // Panggil render slider dan passing background image URL
        echo ccsp_render_category_slider([
            'title' => $settings['title'],
            'taxonomy' => $settings['taxonomy'],
            'limit' => $settings['limit'],
            'bg_image_url' => $settings['category_bg_image']['url'] ?? '',
        ]);
    }
}
