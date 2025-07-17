## 📦 Custom Category Slider Pro
<img width="1919" height="907" alt="Screenshot 2025-07-17 172111" src="https://github.com/user-attachments/assets/10c6ceb5-10d9-4d27-b1a4-be3e83258b7b" />

Deskripsi:
Plugin slider kategori yang bisa dikustomisasi dengan pengaturan admin, integrasi Elementor widget, dukungan AJAX, lazy loading gambar, dan latar belakang kategori.

Versi: 1.0
Author: Puji Ermanto<pujiermanto@gmail.com> | AKA Vickerness

### 🚀 Fitur Utama

    - Menampilkan kategori (default: product_cat dari WooCommerce) dalam bentuk slider.

    - Upload background per kategori dari halaman edit kategori.

    - Background global juga bisa diatur dari Elementor widget.

    - Integrasi dengan Elementor (dengan widget khusus).

    - Slick carousel + lazy loading gambar.

    - AJAX load ulang kategori secara dinamis.

    - FontAwesome dan style responsif.

    - Panel pengaturan di Settings > Category Slider.

### 📁 Struktur Plugin

```
custom-category-slider-pro/
├── elementor-category-slider-widget.php
├── custom-category-slider-pro.php
└── README.md
```  

### ⚙️ Penggunaan
1. Aktifkan Plugin

Install seperti biasa via WordPress dashboard atau upload manual.
2. Pengaturan Admin

Buka: Settings > Category Slider

    Section Title: Judul di atas slider

    Taxonomy: product_cat untuk WooCommerce, atau taxonomy lainnya

    Number of Categories: Jumlah maksimal kategori yang ditampilkan

3. Upload Background Image per Kategori (Opsional)

    Masuk ke Products > Categories

    Klik "Edit" di salah satu kategori

    Akan muncul field: Background Image

    Upload gambar latar khusus

4. Tambahkan via Elementor

    Drag & Drop widget: Category Slider dari Elementor

    Tersedia pengaturan untuk title, taxonomy, jumlah kategori, dan background global

### 🧱 Elementor Widget

File widget: elementor-category-slider-widget.php
Opsi di widget:

    Judul section

    Taxonomy

    Jumlah kategori

    Gambar latar (untuk background global di dalam <div class="category-img">)

### 🖼️ Gambar Kategori

    Diambil dari Thumbnail ID kategori (thumbnail_id term meta).

    Jika tidak ada, akan fallback ke placeholder WooCommerce.  

### 🧩 Shortcode (Opsional untuk Dev)

Jika kamu ingin memanggil slider dari PHP (misal di template), gunakan:

```
echo ccsp_render_category_slider([
    'title' => 'Judul Custom',
    'taxonomy' => 'product_cat',
    'limit' => 10,
    'bg_image_url' => 'https://link-ke-gambar.jpg' // opsional
]); 
```  

### 🔧 Developer Notes

    Slick carousel digunakan via CDN.

    CSS dan JS di-inject secara inline untuk mempermudah override cepat.

> AJAX handler tersedia untuk load ulang kategori jika dibutuhkan:

        action: ccsp_load_categories

        nonces: ccsp_nonce

### 🧪 Troubleshooting

    Gambar tidak muncul? Pastikan setiap kategori memiliki thumbnail ID.

    Hover zoom kurang besar? Atur di inline CSS bagian:

```
.category-slider .category-item:hover .category-img img {
    transform: scale(1.3);
}
```

### ✅ To-Do Selanjutnya

    Tambahkan cache untuk hasil query kategori.

    Tambahkan shortcode [category_slider].

    Tambah pilihan order (by name, count, dll).

### 📄 Lisensi

GPLv2 atau yang lebih baru.