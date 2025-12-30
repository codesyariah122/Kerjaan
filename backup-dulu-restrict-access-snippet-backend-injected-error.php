bisa gak restrict access ini tanpa reload page ??

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
    $allowed_password = '123';

    // Halaman Code Snippets yang dibatasi
    $restricted_pages = [
        'snippets',
        'edit-snippet',
        'add-snippet',
        'import-code-snippets',
        'snippets-settings',
        'code-snippets-welcome',
        'code_snippets_upgrade',
    ];

    // Cek jika sedang di halaman yang dibatasi
    if (is_admin() && isset($_GET['page']) && in_array($_GET['page'], $restricted_pages, true)) {

        // Muat SweetAlert2
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script(
                'sweetalert2',
                'https://cdn.jsdelivr.net/npm/sweetalert2@11',
                [],
                null,
                true
            );
        });

        $password = isset($_GET['password']) ? sanitize_text_field($_GET['password']) : '';

        if ($password !== $allowed_password) {
            add_action('admin_footer', function () use ($password) {
                $wrong_pw = $password !== '';
    ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {

                        <?php if ($wrong_pw) : ?>
                            Swal.fire({
                                icon: 'error',
                                title: 'Password Salah!',
                                text: 'Silakan coba lagi.',
                                confirmButtonText: 'Ulangi'
                            }).then(() => {
                                window.location.href = '<?php echo admin_url(); ?>';
                            });
                        <?php else : ?>
                            Swal.fire({
                                title: 'Masukkan Password',
                                html: `
									<div style="position:relative;margin-bottom:6px;">
										<input id="swal-input-password" type="password" class="swal2-input" placeholder="Password">
										<button type="button" id="toggle-password" style="position:absolute;top:8px;right:8px;background:transparent;border:none;cursor:pointer;">
											👁️
										</button>
									</div>
									<small id="password-hint" style="display:block;font-size:13px;color:#888;margin-top:-8px;">
										Hint: 3 digit angka favoritmu 😉
									</small>
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
                                    const baseURL = window.location.href.split('&password=')[0];
                                    window.location.href = baseURL + '&password=' + encodeURIComponent(result.value);
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
