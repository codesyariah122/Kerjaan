# Produk by Kategori ACF - Elementor Widget

**Versi:** 1.2.0  
**Author:** Puji Ermanto (pujiermanto@gmail.com)  
**Website:** https://pujiermanto-blog.vercel.app/blog

---

## Deskripsi

Plugin ini menambahkan widget Elementor untuk menampilkan produk WooCommerce berdasarkan kategori produk, jumlah produk yang ingin ditampilkan, urutan tampil berdasarkan tanggal, serta filter produk berdasarkan role user yang sedang login (publik, user, mitra, admin).

---

## Fitur

- Pilih kategori produk WooCommerce (atau semua kategori)  
- Tentukan jumlah produk yang ingin ditampilkan  
- Urutkan produk berdasarkan tanggal: ascending / descending  
- Filter produk berdasarkan role user menggunakan custom field `_visible_for_role`  
- Support role: publik (produk tanpa role), user, mitra, dan admin (lihat semua)  

---

## Instalasi

1. Download atau salin folder plugin `produk-by-kategori-acf` ke folder `/wp-content/plugins/`  
2. Pastikan ada folder `/widgets/` dengan file `produk-by-kategori-acf-widget.php` di dalamnya  
3. Aktifkan plugin melalui menu **Plugins** di dashboard WordPress  
4. Pastikan Elementor sudah aktif di website Anda  
5. Buka halaman yang ingin diedit dengan Elementor  
6. Cari widget bernama **Produk by Kategori ACF** di bawah kategori **General**  
7. Tarik dan letakkan widget ke halaman  
8. Atur opsi widget seperti kategori, jumlah produk, dan urutan sesuai kebutuhan  
9. Simpan halaman dan lihat hasilnya

---

## Persyaratan

- WordPress minimal versi 5.0  
- Elementor minimal versi 3.0  
- WooCommerce sudah aktif dan terdapat produk serta kategori produk  
- Produk memiliki custom field `_visible_for_role` jika ingin fitur role-based filtering berjalan  

---

## Cara Kerja Role Filtering

- **Publik / Belum login:** hanya produk yang tidak memiliki `_visible_for_role` atau nilai kosong  
- **User:** produk dengan nilai `_visible_for_role` = `user` atau publik  
- **Mitra:** produk dengan nilai `_visible_for_role` = `mitra`, `user`, atau publik  
- **Administrator:** dapat melihat semua produk tanpa filter  

---

## Contoh Penggunaan Shortcode (opsional)

Widget ini menggunakan internal shortcode `[produk_by_kategori_acf]` yang dapat Anda gunakan jika ingin menampilkan produk secara manual di dalam konten/post.

---

## Support

Jika menemukan bug atau ingin request fitur, silakan hubungi author di:  
**Email:** pujiermanto@gmail.com

---

## Lisensi

GPL v2 atau lebih baru  
