# PPDB Elementor Widget
<img width="1919" height="958" alt="Screenshot 2025-09-16 234522" src="https://github.com/user-attachments/assets/ab09d5a4-4f61-4449-bd8f-db88370f3255" />
<img width="1910" height="959" alt="Screenshot 2025-09-16 234512" src="https://github.com/user-attachments/assets/19b5f5a5-1578-418f-8c62-e09b60555df4" />

**PPDB Elementor Widget** adalah plugin WordPress kustom untuk menambahkan widget Elementor dan shortcode yang menampilkan gambar dari **Option Page JetEngine** (misalnya konten PPDB Online).  

Plugin ini memudahkan menampilkan gambar PPDB baik melalui **Elementor** maupun dengan **shortcode**.

---

## 📦 Fitur

- Integrasi langsung dengan **Elementor Widget**.
- Menampilkan gambar berdasarkan key/field di **JetEngine Option Page**.
- Mendukung penggunaan **shortcode `[ppdb]`**.
- Filter gambar berdasarkan title (misalnya `PPDB-PG`, `PPDB-TKA`, dll).
- Struktur kode modular (widget terpisah di folder `widgets`).

---

## 📂 Struktur Plugin

```
ppdb-elementor-widget/
│
├── ppdb-elementor-widget.php # File utama plugin
├── widgets/
│ └── widget-ppdb.php # Elementor widget PPDB
```  


---

## ⚙️ Instalasi

1. Download atau clone repository ini.
2. Buat folder di `wp-content/plugins/` dengan nama `ppdb-elementor-widget`.
3. Letakkan semua file plugin ke dalam folder tersebut.
4. Aktifkan plugin dari menu **Plugins** di dashboard WordPress.
5. Pastikan Elementor dan JetEngine sudah terinstall & aktif.

---

## 🖥️ Penggunaan

### 1. Elementor Widget
- Buka **Elementor Editor**.
- Cari widget bernama **"PPDB Images"**.
- Tambahkan ke halaman.
- Atur **PPDB Title** sesuai key di Option Page JetEngine (`PPDB-PG`, `PPDB-TKA`, dst).
- Widget akan otomatis menampilkan gambar sesuai pengaturan.

### 2. Shortcode
Gunakan shortcode di post, page, atau template:

```php
[ppdb title="ppdb-pg"]
```  

- title (opsional): untuk memfilter gambar berdasarkan key yang sudah disimpan di Option Page.

Jika title kosong, semua gambar akan ditampilkan.  

🛠️ Konfigurasi Data (JetEngine Options Page)

Plugin membaca data dari Option Page JetEngine, contoh struktur:  

```
$options['konten-ppdb-online'] = [
  'PPDB-PG'  => '{"url":"https://example.com/image1.jpg"}',
  'PPDB-TKA' => '{"url":"https://example.com/image2.jpg"}',
];
```  

Atau dalam bentuk array: 

```
$options['konten-ppdb-online'] = [
  'PPDB-PG'  => ['url' => 'https://example.com/image1.jpg'],
  'PPDB-TKA' => ['url' => 'https://example.com/image2.jpg'],
];
```  

📸 Output HTML

Widget maupun shortcode menghasilkan markup berikut:  

```
<div class="ppdb-gallery">
  <div class="ppdb-item">
    <img src="https://example.com/image.jpg" alt="PPDB-PG" />
  </div>
  ...
</div>
```  
Tambahkan CSS sesuai kebutuhan untuk styling galeri.  

👨‍💻 Author

Puji Ermanto (AKA: Dadang Sugandi)
📧 Email: pujiermanto@gmail.com  



