# Produk by Kategori ACF – Elementor Widget
<img width="1910" height="913" alt="Screenshot 2025-07-29 135859" src="https://github.com/user-attachments/assets/22910e36-bfd9-4fea-8827-4fcb73bf622a" />

**Versi:** 1.2.0  
**Author:** Puji Ermanto | AKA Maman Salajami | AKA Deden Inyuuus | AKA TATANG
**Email:** pujiermanto@gmail.com  
**Portfolio:** [https://pujiermanto-portfolio.vercel.app](https://pujiermanto-portfolio.vercel.app)

## Deskripsi

Widget Elementor kustom yang menampilkan produk WooCommerce berdasarkan:

- Kategori produk (manual atau otomatis dari judul post saat ini)
- Jumlah produk ditampilkan
- Urutan produk (ASC / DESC)
- Role user (misalnya: mitra, customer, dll)
- ACF field `_visible_for_role` untuk menyaring produk berdasarkan role
- Tombol "View All Products" menuju halaman kategori

## Fitur Utama

✅ Select kategori produk manual atau otomatis dari judul post  
✅ Filter berdasarkan role user menggunakan ACF  
✅ Kontrol jumlah dan urutan produk  
✅ Teks tombol & gaya dapat dikustomisasi  
✅ Hindari duplikat produk antar instance widget  
✅ Kompatibel dengan Elementor dan WooCommerce

---

## Instalasi

### Opsi 1 – Manual Upload Folder

1. Ekstrak folder plugin ini.
2. Upload seluruh folder ke dalam direktori:  
   `/wp-content/plugins/`.
3. Aktifkan plugin dari **Dashboard → Plugins**.
4. Pastikan Elementor dan WooCommerce sudah aktif.
5. Tambahkan widget "Produk by Kategori ACF" di dalam editor Elementor.
6. Pilih kategori atau gunakan opsi "Gunakan Judul Post Saat Ini".

### Opsi 2 – Upload File .zip

1. Compress folder plugin menjadi file `.zip`.
2. Di Dashboard WordPress, buka **Plugins → Add New → Upload Plugin**.
3. Pilih file ZIP dan klik **Install Now**.
4. Aktifkan plugin setelah selesai.
5. Pastikan Elementor dan WooCommerce sudah aktif.
6. Gunakan widget "Produk by Kategori ACF" di dalam editor Elementor.



---

## Penggunaan

Widget ini dapat digunakan dalam:

- Halaman koleksi produk (seperti "Zeya Series", "Kamila Series", dll).
- Loop Post Elementor dengan judul post sebagai acuan kategori.
- Role-based produk view: hanya produk dengan `_visible_for_role` cocok yang tampil.

### Tips

- Untuk otomatisasi kategori dari post, pilih:  
  **"Gunakan Judul Post Saat Ini (Post Title)"** di pengaturan kategori.
- Untuk membatasi produk ke role tertentu, gunakan ACF key:  
  `_visible_for_role` dengan nilai seperti: `user`, `mitra`, dll.

---

## Screenshot

![Screenshot Widget](assets/screenshot-1.png)  
_Tampilan widget di Elementor dengan pilihan kategori_

---

## Changelog

### 1.2.0 – Juli 2025

- 🔄 Tambahan opsi "Gunakan Judul Post Saat Ini"
- 🔐 Filter berdasarkan role user via ACF
- 🛠️ Hindari duplikat produk antar widget
- 🎨 Kontrol styling tombol dan heading

---

## Kebutuhan

- PHP 7.4+
- WordPress 5.8+
- Elementor 3.0+
- WooCommerce 5.0+
- ACF (Advanced Custom Fields) jika menggunakan filter `_visible_for_role`

---

## Credits

Puji Ermanto  
[Portfolio Developer](https://pujiermanto-portfolio.vercel.app)  
