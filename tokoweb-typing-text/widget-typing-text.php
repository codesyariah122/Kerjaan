<?php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;

if (!defined('ABSPATH')) exit;

class Tokoweb_Elementor_Typing_Text extends Widget_Base
{
    public function get_name()
    {
        return 'tokoweb_typing_text';
    }

    public function get_title()
    {
        return __('Tokoweb Typing Text', 'tokoweb');
    }

    public function get_icon()
    {
        return 'eicon-type-tool';
    }

    public function get_categories()
    {
        return ['general'];
    }

    protected function _register_controls()
    {
        // --- Konten ---
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Konten', 'tokoweb'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'typing_post_title',
            [
                'label' => __('Judul Typing Text', 'tokoweb'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Masukkan judul dari CPT Typing Text', 'tokoweb'),
            ]
        );

        $this->end_controls_section();

        // --- Style ---
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Gaya Teks', 'tokoweb'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => __('Warna Teks', 'tokoweb'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .tokoweb-typing, {{WRAPPER}} .tokoweb-typing + .typed-cursor' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'label' => __('Tipografi', 'tokoweb'),
                'selector' => '{{WRAPPER}} .tokoweb-typing, {{WRAPPER}} .tokoweb-typing + .typed-cursor',
            ]
        );

        $this->end_controls_section();
    }

    public function render()
    {
        $title = $this->get_settings('typing_post_title');
        if (!$title) return;

        echo do_shortcode('[tokoweb_typing title="' . esc_attr($title) . '"]');
    }
}
