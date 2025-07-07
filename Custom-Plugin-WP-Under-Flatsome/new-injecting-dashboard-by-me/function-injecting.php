<?php
add_action('admin_init', 'restrict_snippets_access_by_password');
function restrict_snippets_access_by_password()
{
    // Daftar halaman Snippets yang ingin dibatasi aksesnya
    $restricted_pages = [
        'snippets',
        'edit-snippet',
        'add-snippet',
        'import-code-snippets',
        'snippets-settings',
        'code-snippets-welcome',
        'code_snippets_upgrade'
    ];

    // Periksa apakah berada di halaman admin dan halaman termasuk dalam daftar terbatas
    if (is_admin() && isset($_GET['page']) && in_array($_GET['page'], $restricted_pages)) {
        // Periksa apakah password benar
        if (!isset($_GET['password']) || $_GET['password'] !== 'pujiganteng') {
            // Redirect jika password salah
            wp_redirect(admin_url('?restricted_access=1'));
            exit;
        }
    }
}
