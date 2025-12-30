<?php
/*
Plugin Name: Manual QRIS Payment Gateway
Description: Gateway QRIS dummy manual untuk testing Tutor Paid Topic Addon
Author: Puji Ermanto
Version: 1.0
*/

if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', function () {

    class WC_Gateway_Manual_QRIS extends WC_Payment_Gateway
    {

        public function __construct()
        {
            $this->id                 = 'manual_qris';
            $this->method_title       = 'Manual QRIS (Dummy)';
            $this->method_description = 'Simulasi pembayaran QRIS secara manual untuk testing.';
            $this->has_fields         = true;
            $this->icon               = plugin_dir_url(__FILE__) . 'qris-dummy.png'; // logo QRIS kecil
            $this->supports           = ['products'];

            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            add_action('woocommerce_thankyou_' . $this->id, [$this, 'thankyou_page']);
        }

        public function init_form_fields()
        {
            $this->form_fields = [
                'enabled' => [
                    'title'   => 'Enable/Disable',
                    'type'    => 'checkbox',
                    'label'   => 'Aktifkan Manual QRIS Payment',
                    'default' => 'yes'
                ],
                'title' => [
                    'title'       => 'Judul',
                    'type'        => 'text',
                    'default'     => 'QRIS Manual Payment (Dummy)',
                    'desc_tip'    => true
                ],
                'description' => [
                    'title'       => 'Deskripsi',
                    'type'        => 'textarea',
                    'default'     => 'Silakan scan QRIS di bawah untuk simulasi pembayaran.',
                ],
                'instructions' => [
                    'title'       => 'Instruksi di halaman Thank You',
                    'type'        => 'textarea',
                    'default'     => 'Terima kasih telah melakukan pembayaran via QRIS. Admin akan memverifikasi dan menyelesaikan pesanan Anda secara manual.',
                ],
            ];
        }

        public function payment_fields()
        {
            echo '<p>' . esc_html($this->description) . '</p>';
            echo '<div style="margin:10px 0;text-align:center">';
            echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'qris-dummy.png') . '" alt="QRIS Dummy" style="max-width:200px;border:1px solid #ccc;border-radius:10px;">';
            echo '</div>';
            echo '<p style="font-size:13px;color:#666;">(Ini hanya simulasi pembayaran, tidak terhubung ke sistem QRIS asli.)</p>';
        }

        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);
            $order->update_status('on-hold', 'Menunggu pembayaran manual QRIS.');
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            return [
                'result'   => 'success',
                'redirect' => $this->get_return_url($order)
            ];
        }

        public function thankyou_page()
        {
            if ($this->instructions) {
                echo wpautop(wptexturize($this->instructions));
            }
        }
    }

    // Daftarkan gateway ke WooCommerce
    add_filter('woocommerce_payment_gateways', function ($methods) {
        $methods[] = 'WC_Gateway_Manual_QRIS';
        return $methods;
    });
});
