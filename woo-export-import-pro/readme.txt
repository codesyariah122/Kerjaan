Woo Export Import Pro
=====================

Plugin ini adalah versi Step 1: mendukung export/import WooCommerce Products, Orders, Users, Analytics.
Untuk dukungan XLSX penuh, jalankan `composer install` di folder plugin untuk memasang PhpSpreadsheet.

Installation:
1. Upload plugin folder `woo-export-import-pro` to `wp-content/plugins/`.
2. If you want .xlsx support: run `composer install --no-dev -o` inside the plugin directory to install dependencies (PhpSpreadsheet).
3. Activate plugin and go to Tools -> WC Export/Import Pro.

Commands (if you have SSH access):
cd wp-content/plugins/woo-export-import-pro
composer install --no-dev -o

If you cannot run composer on server: run composer install locally and upload the vendor/ folder into plugin root.

Notes:
- Always backup DB before import.
- For very large exports, consider reducing batch size or using a streaming library (Spout) to reduce memory usage.
