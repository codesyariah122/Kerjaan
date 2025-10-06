<?php
if (! defined('ABSPATH')) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class PPDB_Widget extends Widget_Base
{

    public function get_name()
    {
        return 'ppdb_widget';
    }

    public function get_title()
    {
        return 'PPDB Images';
    }

    public function get_icon()
    {
        return 'eicon-gallery-grid';
    }

    public function get_categories()
    {
        return ['general'];
    }

    protected function _register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('PPDB Settings', 'ppdb-elementor-widget'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'ppdb_title',
            [
                'label'       => __('PPDB Title', 'ppdb-elementor-widget'),
                'type'        => Controls_Manager::TEXT,
                'default'     => 'PPDB-PG',
                'placeholder' => __('Contoh: PPDB-PG, PPDB-TKA', 'ppdb-elementor-widget'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        echo ppdb_render_content($settings['ppdb_title']);
    }
}

// ============================
// Fungsi Render (Widget + Shortcode)
// ============================
function ppdb_render_content($title = '')
{
    $selected_title = sanitize_title($title); // normalisasi key
    $options        = get_option('customize'); // atau jet_engine()->options->get_option('konten-ppdb-online');
    $ppdb           = $options['konten-ppdb-online'] ?? [];

    if (empty($ppdb)) {
        return '<p>No PPDB content found.</p>';
    }

    ob_start();
    echo '<div class="ppdb-gallery">';

    foreach ($ppdb as $item) {
        foreach ($item as $key => $image_json) {
            if (empty($image_json)) continue;

            // cek apakah array atau string JSON
            $image_data = is_array($image_json) ? $image_json : json_decode($image_json, true);
            if (!is_array($image_data) || empty($image_data['url'])) continue;

            // normalisasi key
            $key_normalized = sanitize_title($key);

            // jika title di shortcode diisi, cocokkan dengan key
            if ($selected_title && $key_normalized !== $selected_title) continue;

            $img_url = $image_data['url'];
            echo '<div class="ppdb-item">';
            // echo '<h4>' . esc_html($key) . '</h4>';
            echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($key) . '" />';
            echo '</div>';
        }
    }

    echo '</div>';
    return ob_get_clean();
}

// ============================
// Shortcode
// ============================
function ppdb_shortcode($atts)
{
    $atts = shortcode_atts(['title' => ''], $atts, 'ppdb');
    return ppdb_render_content($atts['title']);
}
add_shortcode('ppdb', 'ppdb_shortcode');
