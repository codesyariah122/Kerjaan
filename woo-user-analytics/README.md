# WooCommerce User Analytics

Plugin ini menambahkan laporan analitik pengguna dan data order di WooCommerce Analytics dengan tampilan chart langsung di halaman admin WordPress.

---

## Fitur

- **User Analytics**: Menampilkan grafik jumlah user baru yang mendaftar setiap hari.
- **User Order Analytics**: Menampilkan grafik jumlah order yang diselesaikan (`completed`) per hari.
- Tracking waktu login terakhir user (last login) disimpan ke user meta.
- REST API endpoint untuk mengambil data user baru dan data order yang sudah selesai.

---

## Instalasi

1. Upload folder plugin ke direktori `/wp-content/plugins/`
2. Aktifkan plugin melalui menu **Plugins** di WordPress.
3. Plugin akan menambahkan dua submenu di menu **WooCommerce**:
   - **User Analytics**  
   - **User Order Analytics**

---

## Cara Penggunaan

- Masuk ke admin WordPress.
- Buka menu **WooCommerce > User Analytics** untuk melihat grafik user baru.
- Buka menu **WooCommerce > User Order Analytics** untuk melihat grafik order per hari.

---

## REST API Endpoints

Plugin ini juga menyediakan dua endpoint REST API:

| Endpoint                          | Method | Keterangan                        |
|----------------------------------|--------|---------------------------------|
| `/wp-json/wc-analytics/v1/user-analytics`       | GET    | Data user baru per tanggal       |
| `/wp-json/wc-analytics/v1/user-order-analytics` | GET    | Data jumlah order selesai per tanggal |

---

## Pengembangan

- Data user baru diambil dari tabel WordPress `wp_users` berdasarkan tanggal pendaftaran (`user_registered`).
- Data order diambil dari tabel `wp_posts` dengan tipe `shop_order` dan status `wc-completed`.
- Chart menggunakan [Chart.js](https://www.chartjs.org/) via CDN.
- Dapat dikembangkan untuk menampilkan data lain sesuai kebutuhan.

---

## Lisensi

Plugin ini bersifat open-source dan gratis untuk digunakan dan dimodifikasi.

---

## Author

Puji Ermanto (<pujiermanto@gmail.com>)  
[https://pujiermanto-portfolio.vercel.app](https://pujiermanto-portfolio.vercel.app)

---

Terima kasih sudah menggunakan plugin ini!
