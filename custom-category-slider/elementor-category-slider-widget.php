<?php
if (!defined('ABSPATH')) exit;

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
