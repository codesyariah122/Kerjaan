<?php
if (!defined('ABSPATH')) exit;

class WC_ERP_API_Docs
{
    public function __construct()
    {
        add_action('init', [$this, 'add_rewrite_endpoint']);
        add_action('template_redirect', [$this, 'render_docs_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_rewrite_endpoint()
    {
        add_rewrite_rule('^api-docs/?$', 'index.php?wc_erp_api_docs=1', 'top');
        add_rewrite_tag('%wc_erp_api_docs%', '1');
    }

    public function render_docs_page()
    {
        if (get_query_var('wc_erp_api_docs') !== '1') return;

        status_header(200);
        nocache_headers();

        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        echo '<title>WC ERP API Docs</title>';
        echo '<link rel="stylesheet" href="' . WC_ERP_API_DOCS_URL . 'assets/swagger-ui.css">';
        echo '<style>body { margin:0; background:#fafafa; }</style>';
        echo '</head><body>';
        echo '<div id="swagger-ui"></div>';
        echo '<script src="' . WC_ERP_API_DOCS_URL . 'assets/swagger-ui-bundle.js"></script>';
        echo '<script src="' . WC_ERP_API_DOCS_URL . 'assets/swagger-ui-standalone-preset.js"></script>';
        echo '<script src="' . WC_ERP_API_DOCS_URL . 'assets/docs.js"></script>';
        echo '</body></html>';
        exit;
    }

    public function enqueue_assets()
    {
        // tidak perlu enqueue di frontend biasa
    }
}
