<?php
function enqueue_sweetalert_admin()
{
    wp_enqueue_script('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
}
add_action('admin_enqueue_scripts', 'enqueue_sweetalert_admin');

/**
 * Proteksi halaman Code Snippets dengan password + SweetAlert2
 */
add_action('admin_init', 'restrict_snippets_access_by_password');
function restrict_snippets_access_by_password()
{

    $allowed_password = 'pujiganteng';

    // Daftar slug halaman Code Snippets yang dibatasi
    $restricted_pages = [
        'snippets',
        'edit-snippet',
        'add-snippet',
        'import-code-snippets',
        'snippets-settings',
        'code-snippets-welcome',
        'code_snippets_upgrade',
    ];

    // Cek apakah sedang di salah satu halaman di atas
    if (is_admin() && isset($_GET['page']) && in_array($_GET['page'], $restricted_pages, true)) {

        // Muat SweetAlert2 (hanya sekali di admin) …
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script(
                'sweetalert2',
                'https://cdn.jsdelivr.net/npm/sweetalert2@11',
                [],
                null,
                true
            );
        });

        // Ambil password yang (mungkin) dikirim via URL
        $password = isset($_GET['password']) ? sanitize_text_field($_GET['password']) : '';

        // Kalau password belum ada / salah, tampilkan SweetAlert + handle logika
        if ($password !== $allowed_password) {
            add_action(
                'admin_footer',
                function () use ($password) {

                    $wrong_pw = $password !== '';

?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {

                        <?php if ($wrong_pw) : ?>
                            // Sudah kirim password tapi salah
                            Swal.fire({
                                icon: 'error',
                                title: 'Password Salah!',
                                text: 'Silakan coba lagi.',
                                confirmButtonText: 'Ulangi'
                            }).then(() => {
                                // Balik ke dashboard supaya tak loop
                                window.location.href = '<?php echo admin_url(); ?>';
                            });
                        <?php else : ?>
                            // Belum input password → minta input
                            Swal.fire({
                                title: 'Masukkan Password',
                                html: `
									<div style="position:relative;">
										<input id="swal-input-password" type="password" class="swal2-input" placeholder="Password">
										<button type="button" id="toggle-password" style="position:absolute;top:8px;right:8px;background:transparent;border:none;cursor:pointer;">
											👁️
										</button>
									</div>
								`,
                                focusConfirm: false,
                                showCancelButton: true,
                                confirmButtonText: 'Submit',
                                cancelButtonText: 'Batal',
                                preConfirm: () => {
                                    const pw = document.getElementById('swal-input-password').value;
                                    if (!pw) {
                                        Swal.showValidationMessage('Password tidak boleh kosong');
                                        return false;
                                    }
                                    return pw;
                                },
                                didOpen: () => {
                                    const btn = document.getElementById('toggle-password');
                                    const input = document.getElementById('swal-input-password');
                                    btn.addEventListener('click', () => {
                                        const type = input.type === 'password' ? 'text' : 'password';
                                        input.type = type;
                                        btn.textContent = type === 'password' ? '👁️' : '🙈';
                                    });
                                }
                            }).then((result) => {
                                if (result.isConfirmed && result.value) {
                                    // Tambah ?password=… ke URL lalu reload
                                    const baseURL = window.location.href.split('&password=')[0];
                                    window.location.href = baseURL + '&password=' + encodeURIComponent(result.value);
                                } else {
                                    // Kalau user batal → balik dashboard
                                    window.location.href = '<?php echo admin_url(); ?>';
                                }
                            });
                        <?php endif; ?>

                    });
                </script>
            <?php
                }
            );
        }
    }
}


function conditional_cptui_menu_display()
{
    $allowed_password = 'pujiganteng';
    $has_access = isset($_GET['password']) && $_GET['password'] === $allowed_password;

    // Sembunyikan menu CPT UI jika tidak ada password
    if (! $has_access) {
        remove_menu_page('toplevel_page_cptui_main_menu');
    }
}
add_action('admin_menu', 'conditional_cptui_menu_display', 999);

function block_direct_cptui_access()
{
    $allowed_password = 'pujiganteng';

    if (
        isset($_GET['page']) &&
        strpos($_GET['page'], 'cptui_') === 0
    ) {
        // Enqueue SweetAlert
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
        });

        $password = isset($_GET['password']) ? sanitize_text_field($_GET['password']) : '';
        if ($password !== $allowed_password) {
            add_action('admin_footer', function () use ($password) {
                $error = $password !== '' && $password !== 'pujiganteng';
            ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        <?php if ($error): ?>
                            Swal.fire({
                                icon: 'error',
                                title: 'Password Salah!',
                                text: 'Silakan coba lagi.',
                                confirmButtonText: 'Ulangi'
                            }).then(() => {
                                window.location.href = '<?php echo admin_url(); ?>';
                            });
                        <?php else: ?>
                            // Custom HTML input with toggle button
                            Swal.fire({
                                title: 'Masukkan Password',
                                html: `
                                <div style="position: relative;">
                                    <input id="swal-input-password" type="password" class="swal2-input" placeholder="Password">
                                    <button type="button" id="toggle-password" style="position:absolute;top:8px;right:8px;background:transparent;border:none;cursor:pointer;">
                                        👁️
                                    </button>
                                </div>
                            `,
                                focusConfirm: false,
                                showCancelButton: true,
                                confirmButtonText: 'Submit',
                                cancelButtonText: 'Batal',
                                preConfirm: () => {
                                    const pw = document.getElementById('swal-input-password').value;
                                    if (!pw) {
                                        Swal.showValidationMessage('Password tidak boleh kosong');
                                        return false;
                                    }
                                    return pw;
                                },
                                didOpen: () => {
                                    const toggleBtn = document.getElementById('toggle-password');
                                    const pwInput = document.getElementById('swal-input-password');
                                    toggleBtn.addEventListener('click', function() {
                                        const type = pwInput.type === 'password' ? 'text' : 'password';
                                        pwInput.type = type;
                                        toggleBtn.textContent = type === 'password' ? '👁️' : '🙈';
                                    });
                                }
                            }).then((result) => {
                                if (result.isConfirmed && result.value) {
                                    const currentURL = window.location.href.split('&password=')[0];
                                    window.location.href = currentURL + '&password=' + encodeURIComponent(result.value);
                                } else {
                                    window.location.href = '<?php echo admin_url(); ?>';
                                }
                            });
                        <?php endif; ?>
                    });
                </script>
<?php
            });
        }
    }
}
add_action('admin_init', 'block_direct_cptui_access');
