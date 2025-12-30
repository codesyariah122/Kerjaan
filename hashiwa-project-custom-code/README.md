# 🏫 Hashiwa Japanese Academy – WordPress Custom Code & Plugin

**Project:** Hashiwa Japanese Academy  
**Developer:** Puji Ermanto  
**Company:** [tokoweb.co](https://tokoweb.co)  
**Versi:** 1.0  
**Tanggal Rilis:** November 2025  
**Lisensi:** GPL-2.0+  

---

## 📘 Deskripsi Proyek

Proyek ini dikembangkan khusus untuk **Hashiwa Japanese Academy** oleh **tokoweb.co**, dengan pengembangan dan integrasi WordPress kustom oleh **Puji Ermanto**.  

Tujuannya adalah untuk memperluas fungsionalitas WordPress standar menggunakan kombinasi **Custom Code Snippet** dan **Custom Plugin** agar sesuai dengan kebutuhan operasional akademi bahasa Jepang modern, termasuk pengelolaan informasi, kontak, dan integrasi dengan sistem pembelajaran berbasis **Tutor LMS**.

---

## ⚙️ Komponen Utama

### 1️⃣ Custom Plugin: `Kontak Informasi`
Plugin ini menambahkan *Custom Post Type (CPT)* bernama **Kontak Informasi** ke dalam submenu **Tutor LMS**.  
Digunakan untuk menyimpan dan menampilkan data kontak lembaga seperti alamat, email, telepon, dan sosial media.

#### 🔧 Fitur:
- Tersedia di bawah menu **Tutor LMS**
- Input dinamis untuk **Alamat (bisa lebih dari satu)**
- Input untuk Email, Telepon, Instagram, Twitter (X), dan YouTube
- Menggunakan **Meta Box** dengan sistem penyimpanan WordPress Meta API
- Proteksi data dengan `nonce` dan `sanitize_text_field`

#### 📄 Lokasi File:
/wp-content/plugins/kontak-informasi/kontak-informasi.php

#### 📥 Instalasi:
1. Upload folder `kontak-informasi` ke direktori: 
/wp-content/plugins/
2. Aktifkan melalui menu **Plugins > Installed Plugins**

---

### 2️⃣ Custom Code Snippet
Selain plugin, proyek ini juga menggunakan **Custom Code Snippet** untuk memodifikasi fungsionalitas WordPress tanpa harus mengedit file inti tema.  
Seluruh kode disimpan dan dikelola melalui plugin seperti **Code Snippets** atau **WPCode**.

#### 🔧 Contoh Snippet yang Digunakan:
- Menambahkan menu admin kustom di sidebar.
- Integrasi tombol aksi untuk WhatsApp & marketplace.
- Custom JavaScript di footer (SweetAlert, Floating Button, dll).
- Modifikasi halaman “Order Received” WooCommerce agar lebih interaktif.
- Penyesuaian CSS/JS di Elementor Widget (misal: pengaturan tinggi Icon Box & animasi SVG).

#### 📄 Lokasi (melalui plugin Code Snippets):
Dashboard > Snippets > All Snippets  


---

## 🧠 Tujuan & Manfaat

| Aspek | Manfaat |
|-------|----------|
| **Kustomisasi LMS** | Mempermudah Hashiwa mengelola data kontak langsung dari panel Tutor LMS. |
| **Efisiensi Operasional** | Semua pengaturan terpusat di dashboard WordPress, tanpa edit kode manual. |
| **Kemudahan Pengelolaan** | Admin dapat menambah atau ubah data kontak secara dinamis tanpa bantuan developer. |
| **Integrasi Fleksibel** | Data kontak bisa dipanggil di front-end menggunakan PHP atau shortcode. |

---  

🧩 Kompatibilitas

| Komponen   | Versi Minimum      | Status |
| ---------- | ------------------ | ------ |
| WordPress  | 5.5                | ✅      |
| PHP        | 7.4                | ✅      |
| Tutor LMS  | 2.0+               | ✅      |
| Elementor  | 3.10+              | ✅      |
| Tema Utama | Flatsome / Blocksy | ✅      |


🧰 Tools & Teknologi

- WordPress CMS

- Tutor LMS Plugin

- Elementor Page Builder

- WooCommerce

- Custom PHP Snippets

- SweetAlert.js

- Font Awesome Icons

- JavaScript (DOM manipulation & event listeners)

🧑‍💻 Developer

Dikembangkan oleh:
Puji Ermanto
📧 Email: puji@tokoweb.co  

📜 Lisensi

Proyek ini dilisensikan di bawah GNU General Public License v2.0 (GPL-2.0+)

Artinya:

Bebas digunakan, dimodifikasi, dan didistribusikan.

Harus menyertakan lisensi yang sama saat redistribusi.

🏷️ Catatan Proyek

Semua kode ditulis manual tanpa builder tambahan selain Elementor.

Plugin dan snippet ini dikembangkan eksklusif untuk Hashiwa Japanese Academy oleh tokoweb.co.

Kode disusun dengan prinsip clean code, security nonce, dan WordPress best practices.