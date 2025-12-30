#### Tutor Paid Topic Addon V2

Version: 2.3
Author: Puji Ermanto
Description: Inject harga per topic langsung di Course Builder Tutor LMS 3.x+ (React SPA) dan otomatis buat WooCommerce Product. Plugin ini memungkinkan pembayaran per topik dalam kursus Tutor LMS, terintegrasi penuh dengan WooCommerce untuk pengelolaan produk dan pesanan.
Fitur Utama

    Harga Per Topic: Set harga unik untuk setiap topik dalam kursus Tutor LMS langsung dari Course Builder (React SPA).
    Auto WooCommerce Product: Otomatis buat produk WooCommerce untuk setiap topik yang dihargai, dengan relasi meta yang tepat.
    Frontend Lock System: Tampilkan harga range pada kartu kursus, dan kunci topik yang belum dibeli dengan tombol "Buy Topic".
    Order Status Integration: Sinkronisasi status pesanan WooCommerce (Completed, Pending, On Hold) dengan akses topik.
    Role & Enrollment Management: Auto-assign role tutor_student, enroll otomatis setelah pembayaran, dan sinkronisasi cache enrollment.
    Cinematic Lesson Player: Styling khusus untuk halaman lesson dengan player YouTube yang dioptimalkan.
    REST API Endpoints: Endpoint untuk mendapatkan harga topik, progress user, dan refresh enrollment cache.
    Admin JS Integration: Inject input harga dan badge harga langsung di interface React Tutor LMS.

Persyaratan Sistem

    WordPress: 5.0+
    Tutor LMS: 3.x+ (teruji pada 3.x dengan React SPA)
    WooCommerce: 5.0+
    PHP: 7.4+
    MySQL: 5.6+

Instalasi

    Download Plugin: Unduh file ZIP plugin ini.
    Upload ke WordPress: Masuk ke Dashboard WordPress > Plugins > Add New > Upload Plugin, lalu pilih file ZIP.
    Aktivasi: Aktifkan plugin "Tutor Paid Topic Addon V2".
    Konfigurasi WooCommerce: Pastikan WooCommerce terinstall dan dikonfigurasi. Plugin ini akan otomatis membuat produk hidden untuk setiap topik.
    Pengaturan Tutor LMS: Pastikan Course Builder Tutor LMS menggunakan React SPA (default di Tutor 3.x+).

Penggunaan
1. Set Harga Topic di Course Builder

    Buka Course Builder Tutor LMS untuk kursus tertentu.
    Pada setiap topik, input harga di field "Harga per topic (Rp)" yang muncul otomatis.
    Klik "Update" untuk menyimpan. Plugin akan membuat produk WooCommerce terkait.

2. Frontend User Experience

    Pada halaman kursus, harga topik ditampilkan sebagai range (min - max) atau harga tunggal.
    Topik terkunci dengan badge "🔒 Locked · Rp XXX" dan tombol "Buy Topic".
    Setelah pembelian, topik terbuka berdasarkan status pesanan WooCommerce.
    Lesson pertama di topik pertama selalu terbuka jika user enrolled.

3. Order Management

    Pesanan Completed: Topik dibuka, user enrolled otomatis.
    Pesanan Pending/On Hold: Topik tetap terkunci dengan badge status.
    Pesanan Cancelled/Refunded: Akses topik dicabut.

4. REST API Endpoints

    GET /wp-json/tpt/v1/get-topic-prices?course_id=XXX: Ambil harga semua topik dalam kursus.
    GET /wp-json/tpt/v1/user-progress?user_id=XXX: Ambil progress user (completed & purchased topics).
    POST /wp-json/tpt/v1/refresh-enroll-cache?user_id=XXX: Refresh cache enrollment Tutor LMS.

5. Admin Tools

    Gunakan endpoint REST untuk debug atau reset progress user.
    Monitor log error di wp-content/debug.log jika WP_DEBUG aktif.

Struktur File  
```
tutor-paid-topic-addon-v2/
├── tutor-paid-topic-addon-v2.php      # File utama plugin
├── assets/
│   ├── admin.js                       # JS untuk inject harga di Course Builder
│   ├── player.js                      # JS untuk player YouTube cinematic
│   └── style.css                      # CSS untuk styling lesson dan frontend
├── module/
│   └── frontend.php                   # Frontend hooks: harga kartu, lock system
└── service/
    ├── role-registration.php          # Role tutor_student & enrollment
    ├── tutor-enroll-safe.php          # (Deprecated, digantikan unlock.php)
    ├── tutor-enroll-unlock.php        # Override lock system Tutor LMS
    └── tutor-rest-refresh.php         # REST API untuk refresh & reset
```  
Changelog
Version 2.3 (Current)

    Patch: Auto-sync enrolled courses cache untuk Tutor LMS.
    Fix: Sinkronisasi status order WooCommerce dengan akses topik.
    Enhancement: Cinematic styling untuk lesson pages.
    REST API: Endpoint untuk user progress dan refresh cache.

Version 2.2

    Integrasi penuh dengan WooCommerce order status.
    Auto-enroll user setelah pembayaran completed.
    Frontend lock dengan overlay visual dan spinner loading.

Version 2.1

    Admin JS: Inject harga langsung di React SPA Tutor.
    WooCommerce product creation otomatis per topic.
    Filter harga pada course card.

Version 2.0

    Initial release dengan fitur dasar harga per topic dan WooCommerce integration.

Troubleshooting

    Harga tidak muncul di Course Builder: Pastikan Tutor LMS 3.x+ dan React SPA aktif. Cek console browser untuk error JS.
    Produk WooCommerce tidak dibuat: Periksa permission user dan WooCommerce setup. Lihat log error.
    Topik tidak terkunci: Pastikan user role tutor_student dan enrollment cache sinkron. Gunakan endpoint /refresh-enroll-cache.
    AJAX Error: Pastikan nonce valid dan user memiliki capability edit_posts.

Lisensi

Plugin ini menggunakan lisensi GPL v2 atau yang lebih baru. Gratis untuk digunakan dan dimodifikasi sesuai kebutuhan.
Dukungan

Untuk dukungan, hubungi developer di [email atau forum terkait]. Pastikan sertakan versi WordPress, Tutor LMS, dan WooCommerce saat melaporkan issue.


### Noted
#### Endpoint reset enrolled tutor
Endpoint:

POST https://hashiwa.tokoweb.live/wp-json/tpt/v1/reset-course-data

<img width="1920" height="2645" alt="77a7fe63-ac72-4b53-b446-693a037d92ad" src="https://github.com/user-attachments/assets/251eb69f-321f-4de6-8538-75af40f9fcb0" />
<img width="1920" height="3673" alt="checkout-pembelian-course-bab-ke-2" src="https://github.com/user-attachments/assets/12e41c5a-ff9a-4b63-af39-23529eab8fac" />
<img width="1920" height="3319" alt="payment-success-pembelian-course-bab-ke-2" src="https://github.com/user-attachments/assets/d1385f3f-e315-4097-a045-e9b92612f1b3" />
<img width="1918" height="963" alt="Screenshot 2025-11-28 092819" src="https://github.com/user-attachments/assets/c50e6132-5afd-40e9-8fd9-f0ce37bcbaa5" />
<img width="1915" height="962" alt="Screenshot 2025-11-29 011421" src="https://github.com/user-attachments/assets/84957767-44ae-4bf2-b07d-3ab3203c37ec" />
<img width="1916" height="957" alt="Screenshot 2025-11-29 001955" src="https://github.com/user-attachments/assets/a50f53f7-bebe-4ebe-98c7-f11bc1f70b23" />
