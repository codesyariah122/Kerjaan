<?php
class FRC_Abandoned_Cart_Cron
{

    public function __construct()
    {
        add_filter('cron_schedules', [$this, 'add_five_minute_schedule']);
        add_action('init', [$this, 'schedule_cron']);
        add_action('frc_check_abandoned_carts', [$this, 'handle_abandoned_cart']);
    }

    public function add_five_minute_schedule($schedules)
    {
        $schedules['five_minutes'] = [
            'interval' => 300,
            'display'  => __('Every 5 Minutes')
        ];
        return $schedules;
    }

    public function schedule_cron()
    {
        if (!wp_next_scheduled('frc_check_abandoned_carts')) {
            wp_schedule_event(time(), 'five_minutes', 'frc_check_abandoned_carts');
        }
    }

    public function handle_abandoned_cart()
    {
        global $wpdb;

        $sessions = $wpdb->get_results("
            SELECT session_key, session_value 
            FROM {$wpdb->prefix}woocommerce_sessions
        ");

        foreach ($sessions as $session) {
            $data = maybe_unserialize($session->session_value);
            if (!isset($data['cart']) || !is_array($data['cart'])) continue;

            foreach ($data['cart'] as $key => $item) {
                if (isset($data['frc_reserved_' . $key]) && isset($data['frc_time_' . $key])) {
                    $product_id = $data['frc_reserved_' . $key]['id'];
                    $qty = $data['frc_reserved_' . $key]['qty'];
                    $time = $data['frc_time_' . $key];

                    if (time() - $time >= 900) {
                        $product = wc_get_product($product_id);
                        if ($product && $product->managing_stock()) {
                            wc_update_product_stock($product, $qty);
                        }

                        unset($data['cart'][$key]);
                        unset($data['frc_reserved_' . $key]);
                        unset($data['frc_time_' . $key]);

                        $wpdb->update(
                            "{$wpdb->prefix}woocommerce_sessions",
                            ['session_value' => maybe_serialize($data)],
                            ['session_key' => $session->session_key]
                        );
                    }
                }
            }
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('frc_check_abandoned_carts');
    }
}
