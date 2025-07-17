# Custom Slick Slider for WordPress + Elementor

Plugin ini adalah slider kustom berbasis **Slick Carousel** yang bisa digunakan langsung di **Elementor** sebagai widget.
![Screenshot 2025-07-08 005949](https://github.com/user-attachments/assets/fba4d767-0e01-4aba-963d-0fc1792c98c5)

---

## 🔧 Fitur

- Menggunakan [Slick.js](https://kenwheeler.github.io/slick/) (carousel ringan dan responsif)
- Terintegrasi dengan Elementor (drag-and-drop widget)
- Dukungan layout: konten dua kolom (kiri teks, kanan gambar)
- Dynamic Repeater untuk multi-slide
- Opsi:
  - Gambar latar
  - Judul, subjudul, deskripsi
  - Tombol dan URL kustom

---

## 📦 Instalasi

1. Download atau clone plugin ini.
2. Copy ke folder:

### /wp-content/plugins/custom-slick-slider/

3. Aktifkan dari **Dashboard > Plugins**.
4. Pastikan Elementor sudah aktif juga.

---

## 🧩 Cara Menggunakan (Elementor)

1. Buka halaman dengan Elementor.
2. Cari widget: **Custom Slick Slider**.
3. Drag ke halaman.
4. Tambahkan slides menggunakan kontrol repeater:
- Gambar Background
- Sub Title
- Title
- Deskripsi
- Teks tombol
- Link tombol

---

## 📁 Struktur Direktori

```
custom-slick-slider/
├── css/
│ ├── slick.css
│ ├── slick-theme.css
│ └── slick-custom.css
├── js/
│ ├── slick.min.js
│ └── slick-init.js
├── images/
│ ├── slider1.jpg
│ └── slider2.jpg
├── widgets/
│ └── class-custom-slick-slider-widget.php
├── templates/
│ └── slider-template.php (opsional)
├── custom-slick-slider.php
└── README.md
```


---

## 🔄 Inisialisasi Slick Slider (di `js/slick-init.js`)

```js
jQuery(document).ready(function($) {
  $('.animated-slider').slick({
    autoplay: true,
    autoplaySpeed: 6000,
    speed: 500,
    dots: true,
    arrows: false,
    fade: true,
    cssEase: 'linear'
  });
});
```
### 🎨 Styling Tambahan (css/slick-custom.css)

```
.slider-item {
  height: 600px;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding: 50px;
  color: white;
  position: relative;
}

.slider-content {
  max-width: 600px;
  background: rgba(0, 0, 0, 0.5);
  padding: 20px;
  border-radius: 12px;
}
```  

#### ✅ Requirement
**WordPress 5.0+**

- Elementor (Free atau Pro)

- jQuery (otomatis tersedia di WordPress)

#### 👨‍💻 Author
> Puji Ermanto a.k.a Vickerness aka Dunkelheit
📧 pujiermanto@gmail.com

### 📝 License
GPLv2 or later



