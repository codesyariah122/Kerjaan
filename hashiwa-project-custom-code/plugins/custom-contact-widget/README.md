# Plugin: Kontak Informasi – Integrasi Tutor LMS
<img width="1909" height="915" alt="Screenshot 2025-11-12 233431" src="https://github.com/user-attachments/assets/e7b0e163-2f69-48a3-b911-531fec7bd23b" />
<img width="1919" height="915" alt="Screenshot 2025-11-20 020143" src="https://github.com/user-attachments/assets/88592b77-fdd7-4ee6-9297-f3ecf91f7001" />

**Versi:** 1.2  
**Penulis:** Puji Ermanto  
**Deskripsi:**  
Plugin ini menambahkan *Custom Post Type* bernama **"Kontak Informasi"** ke dalam menu **Tutor LMS** di dashboard WordPress.  
Digunakan untuk menyimpan informasi kontak lembaga atau institusi, seperti alamat, email, telepon, dan media sosial.  
Terdapat fitur **alamat dinamis**, memungkinkan admin menambahkan lebih dari satu alamat dengan mudah.

---  

#### Khusus untuk page contact widget elementor
<img width="1913" height="961" alt="Screenshot 2025-11-19 135555" src="https://github.com/user-attachments/assets/b639de4d-9ebc-4b7c-8fc1-99edc4dd3850" />

## 🎯 Fitur Utama

✅ Menambahkan menu **Kontak Informasi** di bawah menu **Tutor LMS**  
✅ Input manual untuk:
- Alamat (bisa lebih dari satu)
- Email
- Nomor Telepon
- Instagram
- Twitter (X)
- YouTube  
✅ Mendukung penyimpanan otomatis menggunakan WordPress Meta API  
✅ Interface input yang rapi dan mudah digunakan (menggunakan HTML & JavaScript sederhana)

---

## 🧩 Struktur Meta Field

| Field | Deskripsi |
|-------|------------|
| `alamat[]` | Input dinamis untuk alamat (bisa lebih dari satu) |
| `email` | Email kontak utama |
| `telepon` | Nomor telepon |
| `instagram` | URL akun Instagram |
| `twitter` | URL akun Twitter/X |
| `youtube` | URL channel YouTube |

---

## 🪄 Cara Menggunakan
<img width="1918" height="908" alt="Screenshot 2025-11-12 233439" src="https://github.com/user-attachments/assets/21ce93de-38c7-47c2-9f84-337e404ad152" />


### 1️⃣ Instalasi
1. Salin file plugin ini ke folder:
```bash
/wp-content/plugins/kontak-informasi/
```  

2. Pastikan nama file utamanya misalnya:
kontak-informasi.php  

3. Aktifkan plugin melalui menu **Plugins > Installed Plugins** di dashboard WordPress.

---

### 2️⃣ Menambahkan Data Kontak
1. Setelah aktif, buka menu:  
Tutor LMS > Kontak Informasi  

2. Klik **Tambah Informasi**.
3. Isi data kontak sesuai kebutuhan.
4. Untuk menambahkan alamat lebih dari satu, klik tombol ➕ **Tambah Alamat**.
5. Simpan dengan menekan **Publish / Update**.

---

⚙️ Kompatibilitas

WordPress: 5.5 – 6.x

PHP: 7.4 atau lebih baru

Tutor LMS: v2.0+

Tema yang direkomendasikan: Flatsome, Astra, Blocksy

🧠 Catatan Tambahan

Semua data disimpan menggunakan fungsi update_post_meta().

Untuk keamanan, semua field dilindungi menggunakan wp_nonce_field() dan sanitize_text_field().

Field alamat disimpan sebagai array, memudahkan pengambilan data untuk front-end.

🧑‍💻 Kontributor

Ditulis dan dibuat oleh:
Puji Ermanto
📧 Email: pujiermanto@gmail.com