# 🔧 Fix: Vendor Folder Missing

Error 500 disebabkan karena folder `vendor` tidak ada di server.

---

## 🎯 Masalah

File `backend/app/config.php` mencari:
```php
require_once __DIR__ . '/vendor/autoload.php';
```

Tapi folder `backend/app/vendor/` tidak ada di server!

---

## ✅ Solusi

### Opsi 1: Upload Folder vendor (Recommended)

**Folder yang perlu diupload:**
```
backend/app/vendor/
```

**Cara Upload:**

1. **Compress folder vendor di local:**
   ```bash
   # Di folder: E:\PROJEKU\a2ubankdigital.my.id\backend\app
   # Compress folder vendor jadi vendor.zip
   ```

2. **Upload ke server:**
   - cPanel → File Manager
   - Masuk ke: `backend/app/`
   - Upload: `vendor.zip`
   - Extract: Klik kanan `vendor.zip` → Extract
   - Hapus: `vendor.zip` setelah extract

3. **Pastikan struktur:**
   ```
   backend/app/
   ├── vendor/
   │   ├── autoload.php
   │   ├── composer/
   │   └── vlucas/
   ├── config.php
   └── *.php
   ```

---

### Opsi 2: Install via SSH (Jika punya akses SSH)

```bash
cd /home/a2uj2723/public_html/coba.a2ubankdigital.my.id/backend/app
composer install
```

---

### Opsi 3: Update config.php (Sudah Dilakukan)

Saya sudah update `backend/app/config.php` untuk auto-detect vendor location:

```php
// Cek apakah vendor ada di app/ atau di parent directory
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} else {
    die('Error: Composer autoload not found. Please run "composer install"');
}
```

**Upload file ini ke server:**
- Local: `backend/app/config.php`
- Server: `backend/app/config.php` (replace)

---

## 📋 Checklist

- [ ] Compress folder `backend/app/vendor/` jadi `vendor.zip`
- [ ] Upload `vendor.zip` ke server (`backend/app/`)
- [ ] Extract `vendor.zip` di server
- [ ] Hapus `vendor.zip`
- [ ] Upload `backend/app/config.php` yang baru
- [ ] Test: `https://coba.a2ubankdigital.my.id/backend/app/test_db_connection.php`

---

## 🔍 Verify

Setelah upload, cek struktur di server:

```
backend/app/vendor/
├── autoload.php          ← File ini WAJIB ada
├── composer/
│   ├── autoload_real.php
│   └── ...
└── vlucas/
    └── phpdotenv/        ← Library untuk load .env
```

---

## 🎯 Test

Buka di browser:
```
https://coba.a2ubankdigital.my.id/backend/app/test_db_connection.php
```

**Jika berhasil:**
```
✅ Database connection successful!
Database: a2uj2723_coba
```

**Jika masih error:**
- Cek folder `vendor/` ada di `backend/app/`
- Cek file `vendor/autoload.php` ada
- Cek permissions: `backend/app/vendor/` → 755

---

## 📝 Kenapa vendor Tidak Terupload?

Kemungkinan:
1. File `.gitignore` exclude folder `vendor/`
2. Upload manual tidak include folder `vendor/`
3. Folder `vendor/` terlalu besar (50+ MB)

**Solusi:**
- Upload manual via cPanel (compress dulu)
- Atau install via SSH: `composer install`

---

**Setelah upload vendor, error 500 akan hilang!** 🚀
