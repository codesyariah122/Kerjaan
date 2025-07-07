📦 Plugin Name: Flashsale Role Control
🎯 Deskripsi Singkat

Flashsale Role Control adalah plugin WordPress khusus WooCommerce yang memungkinkan kontrol penuh atas visibilitas produk dan manajemen stok saat flash sale berdasarkan peran pengguna seperti User dan Mitra. Plugin ini juga menangani reservasi stok secara otomatis dan pengembalian stok keranjang yang ditinggalkan (abandoned cart).
✅ Fitur Utama
🔐 Kontrol Produk Berdasarkan Role

    Menambahkan field Visible For Role di setiap produk WooCommerce.

    Produk bisa diatur hanya tampil untuk:

        Semua pengguna

        Hanya User

        Hanya Mitra

    Menyembunyikan produk dari user yang tidak sesuai rolenya.

    Blokir akses langsung ke halaman single product jika role tidak sesuai.

🛒 Flashsale Countdown dengan Manajemen Stok

    Produk dengan countdown aktif akan otomatis mengurangi stok saat dimasukkan ke cart.

    Jika user tidak menyelesaikan pembelian dalam 15 menit, stok akan dikembalikan otomatis.

⏰ Cron Abandoned Cart

    Pengecekan tiap 5 menit untuk cart yang tidak aktif.

    Mengembalikan stok yang direservasi dari cart yang tidak jadi dibayar.

👥 Role Management

    Menambahkan role kustom:

        mitra

        user

    Siap pakai untuk digunakan dalam kontrol produk dan akses.

💡 Manfaat Plugin

    ⚡️ Meningkatkan konversi flash sale dengan countdown & auto-reserve stock.

    🔒 Menjaga keamanan dan eksklusivitas produk sesuai dengan segmentasi user.

    ⏳ Menghindari kehilangan stok akibat abandoned cart.

    🛠 Sangat cocok untuk marketplace yang punya segmentasi pelanggan seperti Reseller, Mitra, atau Member.

```
flashsale-role-control/
│
├── flashsale-role-control.php       ; file utama plugin
├── includes/
    ├── class-user-role-manager.php  ; pembuatan dan pengaturan role
    ├── class-flashsale-stock.php    ; logika pengurangan & restore stok
    ├── class-role-product-filter.php; filter produk & akses berdasarkan role
    └── class-abandoned-cart-cron.php; jadwal cron untuk abandoned cart
```  

🧠 Cara Kerja Singkat

    Admin memilih role yang bisa melihat produk saat mengedit produk WooCommerce.

    User hanya bisa melihat produk sesuai dengan rolenya.

    Saat produk flashsale ditambahkan ke keranjang:

        Stok langsung dikurangi (reserve).

        Jika tidak dibayar dalam 15 menit → stok dikembalikan via cron.

    Akses langsung ke produk akan dibatasi berdasarkan role yang dipilih.

🧰 Cara Instalasi

    Upload folder flashsale-role-control/ ke direktori plugin WordPress (wp-content/plugins).

    Aktifkan plugin dari dashboard.

    Edit produk WooCommerce → sesuaikan opsi Visible For Role.

    Pastikan cron job WP berjalan untuk fitur restore stok.

🧪 Shortcode Opsional

Jika kamu memiliki shortcode [launch_products], pastikan sudah terintegrasi dengan build_meta_query() agar produk hanya muncul berdasarkan role.
📄 LICENSE

Tokoweb Licence
