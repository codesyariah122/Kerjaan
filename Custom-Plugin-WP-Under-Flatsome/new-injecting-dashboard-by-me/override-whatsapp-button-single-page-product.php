<?php
function override_wa_button_script()
{
    if (is_product()) {
        // Ambil nomor WA dari post type wa_order dengan post ID 22007
        $wa_post_id = 22007;
        $wa_number = get_post_meta($wa_post_id, '_wa_order_number', true);

        // Jika nomor kosong, bisa fallback ke nomor default
        if (empty($wa_number)) {
            $wa_number = '6281312824853'; // nomor default
        }

        // Escape untuk aman di JS
        $wa_number_esc = esc_js($wa_number);
?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const waNumber = "<?php echo $wa_number_esc; ?>";
                const buttons = document.querySelectorAll('a.button.primary');

                buttons.forEach(function(btn) {
                    const span = btn.querySelector('span');
                    if (span && span.textContent.trim().toLowerCase() === 'konsultasikan sekarang') {
                        const productTitle = document.querySelector('.product_title');
                        if (productTitle) {
                            const titleText = productTitle.textContent.trim();
                            const encodedMessage = encodeURIComponent(
                                `👋 Hallo Admin Kotabaru Parahyangan,\n\nSaya tertarik dengan properti berikut:\n🏠 *${titleText}*\n\nMohon info lebih lanjut ya.`
                            );
                            const newUrl = `https://wa.me/${waNumber}?text=${encodedMessage}`;


                            btn.removeAttribute('href');
                            btn.style.cursor = 'pointer';

                            btn.addEventListener('click', function(e) {
                                e.preventDefault();
                                window.open(newUrl, '_blank');
                            });
                        }
                    }
                });
            });
        </script>
<?php
    }
}
add_action('wp_footer', 'override_wa_button_script');
