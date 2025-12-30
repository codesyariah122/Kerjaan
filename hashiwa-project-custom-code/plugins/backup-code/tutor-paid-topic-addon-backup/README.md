# Tutor Paid Topic Addon (Rupiah)
<img width="1920" height="2367" alt="2144a658-5638-4e85-9bcb-29118a24cba0" src="https://github.com/user-attachments/assets/93f2018b-7007-4ca0-a49f-901f7d604eb9" />
<img width="1920" height="1132" alt="Course-Builder-‹-Hashiwa-Japanese-Academy-—-WordPress" src="https://github.com/user-attachments/assets/2110b2e0-b8fd-4b43-b787-865992da2450" />


Addon kustom untuk **Tutor LMS React Course Builder** yang memungkinkan kamu menambahkan **harga per topik/bab (Topic Price)** secara dinamis menggunakan REST API, dan menyembunyikan seluruh sistem harga default (Regular/Sale Price) di tab *Basics*.
<img width="1919" height="906" alt="Screenshot 2025-11-17 220210" src="https://github.com/user-attachments/assets/d89a80a4-47d5-4311-85bd-a92bfd641f5c" />

---

## 🎯 Fitur Utama

✅ Menambahkan input harga di setiap **Topic** (Curriculum tab).  
✅ Menyimpan harga otomatis lewat **REST API (AJAX)**.  
✅ Menampilkan **harga per topik** di sebelah judul topic (UI builder).  
✅ Menyembunyikan seluruh **Regular Price & Sale Price** di tab *Basics*.  
✅ Mengubah **Pricing Model Course → Free**, agar seluruh flow harga dikontrol dari per-topic.  

---

## 🧩 Struktur File

```bash
tutor-paid-topic-addon/
├── tutor-paid-topic-addon.php # File utama plugin
├── tutor-paid-topic.js # Logika front-end untuk input & display harga per topic
└── README.md # Dokumentasi plugin ini
```  


---

## ⚙️ Instalasi

1. Masuk ke folder plugin WordPress:
   ```bash
   /wp-content/plugins/

2. Buat folder baru:
```bash
mkdir tutor-paid-topic-addon
```

3. Upload dua file berikut:

- tutor-paid-topic-addon.php

- tutor-paid-topic.js

4. Aktifkan plugin di WordPress Admin → Plugins → Tutor Paid Topic Addon (Rupiah).  

🚀 Cara Kerja
1️⃣ Menyimpan Harga Per Topic

Saat instruktur membuat atau mengedit topik di Tutor LMS → Curriculum, akan muncul field:  

```bash
Masukkan harga topik (Rp)
```  

Setiap kali tombol Save Topic (✔) diklik:

- Harga dikirim via REST API → disimpan ke tabel custom wp_tutor_topic_price.

- Data tersimpan berdasarkan judul topik (topic_title).  


2️⃣ Menampilkan Harga di Judul Topik

Begitu halaman Curriculum dimuat:

- Script otomatis memanggil endpoint /tutor-paid-topic/v1/get-price.

- Menambahkan badge harga di sebelah judul topik, contoh:  

```bash
Lesson 1: Video Intro    Rp 50.000
```  

🧠 Teknologi & Hook yang Digunakan  

| Komponen                     | Fungsi                                                       |
| ---------------------------- | ------------------------------------------------------------ |
| `register_activation_hook`   | Membuat tabel `wp_tutor_topic_price`                         |
| `rest_api_init`              | Menyediakan endpoint `save-price` dan `get-price`            |
| `admin_enqueue_scripts`      | Memuat file JS khusus di halaman Course Builder Tutor LMS    |
| `admin_print_footer_scripts` | Menyembunyikan Regular/Sale Price dan mengatur Pricing Model |


🛠️ REST API Endpoint
POST /wp-json/tutor-paid-topic/v1/save-price

Request Body  

```json
{
  "title": "Lesson 1: Intro",
  "price": 50000
}
```   

Response  
```json
{
  "success": true,
  "message": "Harga topik disimpan."
}
```  

GET /wp-json/tutor-paid-topic/v1/get-price?title=Lesson%201%3A%20Intro

Response 
```json
{
  "price": 50000
}
```  

💾 Database Structure  

| Field         | Type         | Description                |
| ------------- | ------------ | -------------------------- |
| `id`          | BIGINT(20)   | Auto increment             |
| `topic_title` | VARCHAR(255) | Judul topik unik           |
| `price`       | INT(11)      | Harga topik (dalam Rupiah) |
| `created_at`  | DATETIME     | Timestamp pembuatan        |

Tabel: wp_tutor_topic_price  

🧩 Kompatibilitas

- Tutor LMS: v3.0.0 ke atas (React Course Builder)

- WordPress: 6.0+

- PHP: 7.4+  


#### NOTES for testing 
```mysql
DELETE FROM wpsu_usermeta WHERE user_id = 18 AND meta_key = '_tutor_completed_lesson_id_9620';

DELETE FROM wpsu_usermeta 
WHERE user_id = 18 
AND meta_key LIKE '%tutor_course_progress%';
```  

**For error quiz:**
```mysql
SELECT * FROM `wpsu_posts` WHERE post_type='tutor_quiz';

SELECT ID, post_title, post_type, post_parent 
FROM wpsu_posts 
WHERE post_type = 'topics'
  AND post_title LIKE '%Intro to Course and Acadia bab 2%'
ORDER BY ID DESC;
```

**Query debugging lainnya**
```mysql
SELECT meta_key, meta_value
FROM wpsu_postmeta
WHERE post_id = 1234;
```
```mysql

SELECT meta_key, meta_value
FROM wpsu_postmeta
WHERE post_id IN (16984, 16983, 16982, 16981, 16980, 16979, 16978, 16977);
```

```mysql
SELECT * FROM wpsu_postmeta WHERE post_id IN (SELECT ID FROM wpsu_posts WHERE post_type='tutor_order') ORDER BY post_id DESC;
```  

### Check Query Order
```mysql
SELECT * FROM wpsu_postmeta WHERE post_id = ( SELECT ID FROM wpsu_posts WHERE post_type='tutor_order' ORDER BY ID DESC LIMIT 1 ) LIMIT 0, 25;
```

```mysql
SELECT 
    p.ID AS order_id,
    pm_user.meta_value AS user_id,
    pm_course.meta_value AS course_id,
    pm_topic.meta_value AS topic_id,
    pm_total.meta_value AS total,
    p.post_date AS order_date
FROM wpsu_posts p
LEFT JOIN wpsu_postmeta pm_user 
    ON p.ID = pm_user.post_id AND pm_user.meta_key = '_tutor_order_user_id'
LEFT JOIN wpsu_postmeta pm_course 
    ON p.ID = pm_course.post_id AND pm_course.meta_key = '_tutor_order_course_id'
LEFT JOIN wpsu_postmeta pm_topic 
    ON p.ID = pm_topic.post_id AND pm_topic.meta_key = '_tutor_order_topic_id'
LEFT JOIN wpsu_postmeta pm_total 
    ON p.ID = pm_total.post_id AND pm_total.meta_key = '_tutor_order_total'
WHERE p.post_type = 'tutor_order'
ORDER BY p.ID DESC
LIMIT 50;
``` 

```mysql
SELECT p.ID AS order_id, pm_total.meta_value AS total, p.post_date AS order_date
FROM wpsu_posts p
LEFT JOIN wpsu_postmeta pm_user 
    ON p.ID = pm_user.post_id AND pm_user.meta_key = '_tutor_order_user_id'
LEFT JOIN wpsu_postmeta pm_topic 
    ON p.ID = pm_topic.post_id AND pm_topic.meta_key = '_tutor_order_topic_id'
LEFT JOIN wpsu_postmeta pm_total 
    ON p.ID = pm_total.post_id AND pm_total.meta_key = '_tutor_order_total'
WHERE p.post_type = 'tutor_order'
AND pm_user.meta_value = 18       -- ganti sesuai user_id
AND pm_topic.meta_value = 9616    -- ganti sesuai topic_id
ORDER BY p.ID DESC;
```  

```mysql
SELECT 
    o.ID AS order_id,
    u.meta_value AS user_id,
    c.post_title AS course_title,
    t.post_title AS topic_title,
    total.meta_value AS total,
    o.post_date AS order_date
FROM wpsu_posts o
LEFT JOIN wpsu_postmeta u ON o.ID = u.post_id AND u.meta_key = '_tutor_order_user_id'
LEFT JOIN wpsu_postmeta c_id ON o.ID = c_id.post_id AND c_id.meta_key = '_tutor_order_course_id'
LEFT JOIN wpsu_posts c ON c.ID = c_id.meta_value
LEFT JOIN wpsu_postmeta t_id ON o.ID = t_id.post_id AND t_id.meta_key = '_tutor_order_topic_id'
LEFT JOIN wpsu_posts t ON t.ID = t_id.meta_value
LEFT JOIN wpsu_postmeta total ON o.ID = total.post_id AND total.meta_key = '_tutor_order_total'
WHERE o.post_type = 'tutor_order'
AND u.meta_value = 18          -- user_id
AND t.ID = 9616                -- topic_id
ORDER BY o.ID DESC;
```  

```mysql
SELECT p.ID AS order_id
FROM wpsu_posts p
INNER JOIN wpsu_postmeta pm_course 
        ON p.ID = pm_course.post_id AND pm_course.meta_key = '_tutor_order_course_id'
INNER JOIN wpsu_postmeta pm_topic 
        ON p.ID = pm_topic.post_id AND pm_topic.meta_key = '_tutor_order_topic_id'
INNER JOIN wpsu_postmeta pm_user 
        ON p.ID = pm_user.post_id AND pm_user.meta_key = '_tutor_order_user_id'
WHERE p.post_type = 'tutor_order'
  AND p.post_status = 'completed'
  AND pm_course.meta_value = 9569
  AND pm_topic.meta_value = 9616
  AND pm_user.meta_value = 18
LIMIT 1;
```  

```mysql
SELECT pm_topic.meta_value AS topic_id, p.ID AS order_id
FROM wpsu_posts p
INNER JOIN wpsu_postmeta pm_course 
        ON p.ID = pm_course.post_id AND pm_course.meta_key = '_tutor_order_course_id'
INNER JOIN wpsu_postmeta pm_topic 
        ON p.ID = pm_topic.post_id AND pm_topic.meta_key = '_tutor_order_topic_id'
INNER JOIN wpsu_postmeta pm_user 
        ON p.ID = pm_user.post_id AND pm_user.meta_key = '_tutor_order_user_id'
WHERE p.post_type = 'tutor_order'
  AND p.post_status = 'completed'
  AND pm_course.meta_value = 9569
  AND pm_user.meta_value = 18;
```  

### check order true / false
```mysql
SELECT EXISTS (
    SELECT 1
    FROM wpsu_posts p
    INNER JOIN wpsu_postmeta pm_course 
        ON p.ID = pm_course.post_id AND pm_course.meta_key = '_tutor_order_course_id'
    INNER JOIN wpsu_postmeta pm_topic 
        ON p.ID = pm_topic.post_id AND pm_topic.meta_key = '_tutor_order_topic_id'
    INNER JOIN wpsu_postmeta pm_user 
        ON p.ID = pm_user.post_id AND pm_user.meta_key = '_tutor_order_user_id'
    WHERE p.post_type = 'tutor_order'
      AND p.post_status = 'completed'
      AND pm_course.meta_value = 9569
      AND pm_topic.meta_value = 9616
      AND pm_user.meta_value = 18
) AS has_access;
```  

### ambil semua order unik per topic untuk user tertentu 
```mysql
SELECT pm_topic.meta_value AS topic_id, MAX(p.ID) AS latest_order_id
FROM wpsu_posts p
INNER JOIN wpsu_postmeta pm_course 
    ON p.ID = pm_course.post_id AND pm_course.meta_key = '_tutor_order_course_id'
INNER JOIN wpsu_postmeta pm_topic 
    ON p.ID = pm_topic.post_id AND pm_topic.meta_key = '_tutor_order_topic_id'
INNER JOIN wpsu_postmeta pm_user 
    ON p.ID = pm_user.post_id AND pm_user.meta_key = '_tutor_order_user_id'
WHERE p.post_type = 'tutor_order'
  AND p.post_status = 'completed'
  AND pm_course.meta_value = 9569
  AND pm_user.meta_value = 18
GROUP BY pm_topic.meta_value;
```  

#### Delete unik topic untuk user tertentu
```mysql
DELETE p, pm
FROM wpsu_posts p
INNER JOIN wpsu_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'tutor_order'
  AND p.ID IN (
      SELECT ID FROM (
          SELECT p2.ID
          FROM wpsu_posts p2
          INNER JOIN wpsu_postmeta pm_course ON p2.ID = pm_course.post_id AND pm_course.meta_key = '_tutor_order_course_id' AND pm_course.meta_value = 9569
          INNER JOIN wpsu_postmeta pm_user ON p2.ID = pm_user.post_id AND pm_user.meta_key = '_tutor_order_user_id' AND pm_user.meta_value = 18
      ) AS sub
  );
```  

```mysql
DELETE p, pm
FROM wpsu_posts p
INNER JOIN wpsu_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'tutor_order'
  AND p.ID IN (
      SELECT ID FROM (
          SELECT p2.ID
          FROM wpsu_posts p2
          INNER JOIN wpsu_postmeta pm_course ON p2.ID = pm_course.post_id AND pm_course.meta_key = '_tutor_order_course_id' AND pm_course.meta_value = 9569
          INNER JOIN wpsu_postmeta pm_topic ON p2.ID = pm_topic.post_id AND pm_topic.meta_key = '_tutor_order_topic_id' AND pm_topic.meta_value = 9616
          INNER JOIN wpsu_postmeta pm_user ON p2.ID = pm_user.post_id AND pm_user.meta_key = '_tutor_order_user_id' AND pm_user.meta_value = 18
      ) AS sub
  );
```  

```mysql
SELECT p.ID AS order_id, pm_course.meta_value AS course_id, pm_topic.meta_value AS topic_id, pm_user.meta_value AS user_id
FROM wpsu_posts p
INNER JOIN wpsu_postmeta pm_course 
    ON p.ID = pm_course.post_id AND pm_course.meta_key = '_tutor_order_course_id'
INNER JOIN wpsu_postmeta pm_topic 
    ON p.ID = pm_topic.post_id AND pm_topic.meta_key = '_tutor_order_topic_id'
INNER JOIN wpsu_postmeta pm_user 
    ON p.ID = pm_user.post_id AND pm_user.meta_key = '_tutor_order_user_id'
WHERE p.post_type = 'tutor_order'
  AND p.post_status = 'completed'
  AND pm_course.meta_value = 9569
  AND pm_topic.meta_value = 9616
  AND pm_user.meta_value = 18;
```  

### Delete user access meta
```mysql
DELETE FROM wpsu_usermeta
WHERE user_id = 18 
  AND meta_key LIKE '_tutor_user_topic_access%';
```  

```mysql
-- 1. Hapus order dari tutor_order beserta postmeta-nya
DELETE p, pm
FROM wpsu_posts p
INNER JOIN wpsu_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'tutor_order'
  AND p.ID IN (
      SELECT ID FROM (
          SELECT p2.ID
          FROM wpsu_posts p2
          INNER JOIN wpsu_postmeta pm_course 
              ON p2.ID = pm_course.post_id 
              AND pm_course.meta_key = '_tutor_order_course_id' 
              AND pm_course.meta_value = 9569
          INNER JOIN wpsu_postmeta pm_topic 
              ON p2.ID = pm_topic.post_id 
              AND pm_topic.meta_key = '_tutor_order_topic_id' 
              AND pm_topic.meta_value = 9616
          INNER JOIN wpsu_postmeta pm_user 
              ON p2.ID = pm_user.post_id 
              AND pm_user.meta_key = '_tutor_order_user_id' 
              AND pm_user.meta_value = 18
      ) AS sub
  );

-- 2. Hapus semua meta access topic untuk user agar status menjadi "locked"
DELETE FROM wpsu_usermeta
WHERE user_id = 18
  AND meta_key LIKE '_tutor_user_topic_access%';

-- 3. (Opsional) Hapus enrollment course jika mau sepenuhnya reset
DELETE FROM wpsu_usermeta
WHERE user_id = 18
  AND meta_key LIKE '_tutor_enrolled_courses%';
```  

### Bersihkan enrollment course
```mysql
DELETE FROM wpsu_usermeta
WHERE user_id = 18
  AND meta_key LIKE '_tutor_enrolled_courses%';
```



👨‍💻 Author

Puji Ermanto
Senior Developer
✨ Tokoweb - Indonesia

📝 Versi

v1.5.0 — Tutor Paid Topic Addon (Rupiah)
Last updated: November 2025