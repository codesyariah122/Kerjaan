<?php
if (!defined('ABSPATH')) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Pastikan Font Awesome aktif di frontend
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
        [],
        '6.5.0'
    );
});

class Custom_Kontak_Widget extends Widget_Base
{
    public function get_name()
    {
        return 'custom_kontak';
    }
    public function get_title()
    {
        return 'Kontak Informasi';
    }
    public function get_icon()
    {
        return 'eicon-person';
    }
    public function get_categories()
    {
        return ['general'];
    }

    protected function register_controls()
    {
        $this->start_controls_section('section_content', [
            'label' => 'Pengaturan Kontak'
        ]);

        $posts = get_posts([
            'post_type' => 'kontak_informasi',
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
            'default' => key($options),
        ]);

        $this->add_control('show_socials', [
            'label' => 'Tampilkan Sosial Media',
            'type' => Controls_Manager::SWITCHER,
            'default' => 'yes',
            'label_on' => 'Ya',
            'label_off' => 'Tidak',
        ]);

        $this->end_controls_section();
    }

    private function get_user_country()
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

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $post_id = $settings['kontak_id'];

        if (!$post_id) {
            echo '<p>Pilih data kontak terlebih dahulu.</p>';
            return;
        }

        $email      = get_post_meta($post_id, 'email', true);
        $telepon    = get_post_meta($post_id, 'telepon', true);
        $instagram  = get_post_meta($post_id, 'instagram', true);
        $alamat_negara = get_post_meta($post_id, 'alamat_negara', true);

        $user_country = $this->get_user_country();
        if (!in_array($user_country, ['indonesia', 'japan'])) {
            $user_country = 'indonesia';
        }

        $alamat_indonesia = '';
        $alamat_japan = '';
        if (!empty($alamat_negara) && is_array($alamat_negara)) {
            foreach ($alamat_negara as $item) {
                $negara = strtolower($item['negara']);
                if ($negara === 'indonesia') $alamat_indonesia = $item['alamat'];
                if ($negara === 'japan') $alamat_japan = $item['alamat'];
            }
        }

        $alamat_user = ($user_country === 'japan') ? $alamat_japan : $alamat_indonesia;
        $city = 'Unknown';
        if (!empty($alamat_user)) {
            $parts = preg_split('/\s+/', trim($alamat_user));
            $city = ucfirst(strtolower(end($parts)));
        }

        // ==== STYLE GLOBAL (inline) ====
        echo '<style>
        .contact-wrapper {
            display: flex;
            flex-direction: column;
            gap: 20px;
            font-family: Poppins, sans-serif;
            color: #1e1e1e;
            line-height: 1.8;
            background: #f9fafb;
            padding: 24px 28px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .contact-wrapper h3 {
            font-size: 20px;
            font-weight: 700;
            color: #111;
            margin-bottom: 4px;
        }
        .contact-office {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 40px;
        }
        .contact-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 24px;
            border-top: 1px solid #e5e7eb;
            padding-top: 14px;
            margin-top: 8px;
            font-size: 16px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .contact-item i {
            font-size: 20px;
            transition: transform 0.3s ease, color 0.3s ease;
        }
        .contact-item:hover i {
            transform: scale(1.15);
        }
        .contact-item span {
            color: #222;
        }
        .fa-instagram { color: #E4405F; }
        .fa-phone { color: #1D9BF0; }
        .fa-location-dot { color: #34D399; }
    </style>';

        // ==== WRAPPER START ====
        echo '<div class="contact-wrapper">';

        // === BARIS 1 — OFFICE ===
        echo '<div class="contact-office">';
        echo '<div style="flex:1;min-width:300px;">';
        echo '<h3>INDONESIA OFFICE</h3>';
        echo '<p style="margin:0;font-size:16px;">' . esc_html($alamat_indonesia ?: 'Alamat belum diisi') . '</p>';
        echo '</div>';

        echo '<div style="flex:1;min-width:300px;text-align:left;">';
        echo '<h3>JAPAN OFFICE</h3>';
        echo '<p style="margin:0;font-size:16px;">' . esc_html($alamat_japan ?: 'Alamat belum diisi') . '</p>';
        echo '</div>';
        echo '</div>';

        // === BARIS 2 — KONTAK & SOSIAL ===
        echo '<div class="contact-info">';

        echo '<div class="contact-item">
        <i class="fa-brands fa-instagram"></i>
        <span>' . esc_html($instagram ?: '@hashiwaacademy') . '</span>
    </div>';

        if ($telepon) {
            echo '<div class="contact-item">
            <i class="fa-solid fa-phone"></i>
            <span>' . esc_html($telepon) . '</span>
        </div>';
        }

        echo '<div class="contact-item">
        <i class="fa-solid fa-location-dot"></i>
        <span>' . esc_html($city) . ', ' . ucfirst($user_country) . '</span>
    </div>';

        echo '</div>'; // contact-info
        echo '</div>'; // wrapper
    }
}
