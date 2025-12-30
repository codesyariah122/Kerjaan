<?php
if (!defined('ABSPATH')) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Load Font Awesome
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'fa-contact-widget',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
        [],
        '6.5.0'
    );
});

/**
 * Widget Kontak Page
 */
class Custom_Kontak_Contact_Page_Widget extends Widget_Base
{
    public function get_name()
    {
        return 'custom_kontak_contact_page';
    }

    public function get_title()
    {
        return 'Kontak Page Widget';
    }

    public function get_icon()
    {
        return 'eicon-map-pin';
    }

    public function get_categories()
    {
        return ['general'];
    }

    /**
     * --- CONTROL PANEL ---
     */
    protected function register_controls()
    {

        // Judul Seksi
        $this->start_controls_section('section_title', [
            'label' => 'Judul Atas'
        ]);

        $this->add_control('top_title', [
            'label' => 'Judul',
            'type' => Controls_Manager::TEXT,
            'default' => 'Let us know how we can help',
        ]);

        $this->end_controls_section();

        // Pilih CPT
        $this->start_controls_section('section_contact', [
            'label' => 'Data Kontak (CPT)'
        ]);

        $posts = get_posts([
            'post_type'      => 'kontak_informasi',
            'posts_per_page' => -1,
        ]);

        $options = [];
        foreach ($posts as $p) {
            $options[$p->ID] = $p->post_title;
        }

        $this->add_control('kontak_id', [
            'label' => 'Pilih Kontak',
            'type' => Controls_Manager::SELECT,
            'options' => $options,
            'default' => key($options)
        ]);

        $this->end_controls_section();
    }

    /**
     * Deteksi negara user via IP
     */
    private function detect_user_country()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $response = @file_get_contents("https://ipapi.co/{$ip}/json/");

        if ($response) {
            $data = json_decode($response, true);
            if (!empty($data['country_name'])) {
                return strtolower($data['country_name']);
            }
        }

        return 'indonesia';
    }

    /**
     * Render maps → jika string berisi <iframe> tampilkan langsung
     */
    private function render_map($alamat)
    {

        // Jika user memasukkan kode embed Google Maps langsung
        if (stripos($alamat, '<iframe') !== false) {
            echo $alamat; // titik jadi 100% presisi
            return;
        }

        // Fallback: alamat berupa teks
        echo '<iframe loading="lazy" src="https://www.google.com/maps?q=' . urlencode($alamat) . '&z=18&output=embed"></iframe>';
    }

    /**
     * --- RENDER OUTPUT FRONTEND ---
     */
    protected function render()
    {
        $s = $this->get_settings_for_display();
        $post_id = $s['kontak_id'];

        if (!$post_id) {
            echo "<p>Pilih data kontak.</p>";
            return;
        }

        /* --- GET CPT DATA --- */
        $email    = get_post_meta($post_id, 'email', true);
        $telepon  = get_post_meta($post_id, 'telepon', true);
        $alamat_negara = get_post_meta($post_id, 'alamat_negara', true);

        $alamat_id = '';
        $alamat_jp = '';

        if (!empty($alamat_negara)) {
            foreach ($alamat_negara as $row) {

                if (strtolower($row['negara']) === 'indonesia') {
                    $alamat_id = $row['alamat'];
                }
                if (strtolower($row['negara']) === 'japan') {
                    $alamat_jp = $row['alamat'];
                }
            }
        }

        $user_country = $this->detect_user_country();

        $show_id   = ($user_country === 'indonesia');
        $show_jp   = ($user_country === 'japan');
        $show_both = (!$show_id && !$show_jp);

        /* --- STYLING --- */
        echo '<style>
        .kontak-card-wrap {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 40px 0;
            flex-wrap: wrap;
        }
        .kontak-card {
            width: 350px;
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #f0f0f0;
        }
        .kontak-card i {
            font-size: 34px;
            opacity: .6;
        }
        .kontak-card h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 22px;
            color: #0b2a48;
        }
        .kontak-card p {
            margin: 0;
            color: #555;
        }
        .kontak-maps {
            margin-top: 50px;
            display: grid;
            gap: 40px;
        }
        iframe {
            width: 100%;
            height: 350px;
            border: none;
            border-radius: 16px;
        }
        </style>';

        /* --- TITLE --- */
        echo "<h2 style='text-align:center;margin-bottom:40px;font-size:32px;color:#0b2a48;font-weight:700;'>{$s['top_title']}</h2>";

        /* --- CARDS --- */
        echo '<div class="kontak-card-wrap">';

        // EMAIL
        echo '<div class="kontak-card">
            <i class="fa-solid fa-envelope"></i>
            <h3>Feedbacks</h3>
            <p>Speak to our Friendly team.</p>
            <p style="margin-top:10px;font-weight:600;">' . esc_html($email) . '</p>
        </div>';

        // TELEPON
        echo '<div class="kontak-card">
            <i class="fa-solid fa-phone"></i>
            <h3>Call Us</h3>
            <p>Mon–Fri from 8am to 5pm</p>
            <p style="margin-top:10px;font-weight:600;">' . esc_html($telepon) . '</p>
        </div>';

        // ALAMAT (auto switch)
        $alamat_visit = $show_jp ? $alamat_jp : $alamat_id;

        echo '<div class="kontak-card">
            <i class="fa-solid fa-location-dot"></i>
            <h3>Visit Us</h3>
            <p>Visit our office HQ.</p>
            <p style="margin-top:10px;font-weight:600;">' . esc_html($alamat_visit) . '</p>
        </div>';

        echo '</div>'; // end wrap

        /* --- MAPS --- */
        echo '<div class="kontak-maps">';

        if ($show_id || $show_both) {
            $this->render_map($alamat_id);
        }

        if ($show_jp || $show_both) {
            $this->render_map($alamat_jp);
        }

        echo '</div>';
    }
}
