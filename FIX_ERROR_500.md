# 🔧 Fix Error 500 - Backend

Panduan untuk memperbaiki error 500 Internal Server Error.

---

## ✅ Yang Sudah Diperbaiki

### 1. Double Slash di URL
- ❌ Sebelum: `/backend/app//auth_login.php`
- ✅ Sekarang: `/backend/app/auth_login.php`
- ✅ Fix: Update `useApi.js` untuk remove leading slash

### 2. Frontend Build Baru
- ✅ Build sukses
- ✅ File: `frontend/dist/assets/index-CP5c9O5h.js`
- ✅ Upload file ini ke server

---

## 🔍 Debug Error 500

Error 500 artinya ada masalah di backend PHP. Kemungkinan penyebabnya:

### 1. Database Connection Error

**Test:**
```
https://coba.a2ubankdigital.my.id/backend/app/test_db_connection.php
```

**Jika error:**
- Cek database sudah dibuat di cPanel
- Cek user sudah ditambahkan ke database
- Cek credentials di `backend/.env`

**Fix:**

1. **Buat Database di cPanel:**
   - cPanel → MySQL Databases
   - Create Database: `a2uj2723_coba` (atau nama lain)
   - Create User: `a2uj2723_dbuser`
   - Add User to Database (ALL PRIVILEGES)

2. **Import Database:**
   - cPanel → phpMyAdmin
   - Pilih database yang baru dibuat
   - Import → pilih `backend/create_database.sql`
   - Klik Go

3. **Update backend/.env di server:**
   ```env
   DB_HOST="localhost"
   DB_USER="a2uj2723_dbuser"      # ← Sesuai yang dibuat
   DB_PASS="password_database"     # ← Sesuai yang dibuat
   DB_NAME="a2uj2723_coba"         # ← Sesuai yang dibuat
   ```

---

### 2. Path config.php Error

**Penyebab:** File `config.php` tidak bisa load `.env`

**Fix:**

Cek file `backend/app/config.php` di server, pastikan path `.env` benar:

```php
// Path ke .env harus relatif dari app/
$envPath = __DIR__ . '/../.env';
```

**Struktur di server:**
```
backend/
├── .env              ← File .env di sini
└── app/
    ├── config.php    ← Load .env dari ../
    └── *.php
```

---

### 3. Permissions Error

**Penyebab:** PHP tidak bisa read/write file

**Fix:**

Di cPanel File Manager:

```bash
backend/          → 755
backend/.env      → 644
backend/app/      → 755
backend/uploads/  → 755
backend/cache/    → 755
```

---

### 4. PHP Error Log

**Cara cek error detail:**

1. cPanel → File Manager
2. Buka file: `backend/error_log` (jika ada)
3. Atau cPanel → Errors → Error Log
4. Lihat error terakhir

**Common errors:**

```
Fatal error: Call to undefined function...
→ Extension PHP tidak aktif

Warning: require_once(...): failed to open stream
→ Path file salah

PDO::__construct(): Access denied
→ Database credentials salah
```

---

## 📋 Checklist Fix Error 500

### Upload Frontend Baru
- [ ] Hapus `frontend/dist/` lama di server
- [ ] Upload `frontend/dist/` baru (yang sudah di-build)
- [ ] Pastikan file `index-CP5c9O5h.js` ada

### Setup Database
- [ ] Buat database di cPanel
- [ ] Buat user database
- [ ] Add user to database (ALL PRIVILEGES)
- [ ] Import `backend/create_database.sql`

### Update Backend .env
- [ ] Edit `backend/.env` di server
- [ ] Update DB_HOST, DB_USER, DB_PASS, DB_NAME
- [ ] Update ALLOWED_ORIGINS
- [ ] Save

### Set Permissions
- [ ] `backend/` → 755
- [ ] `backend/.env` → 644
- [ ] `backend/app/` → 755
- [ ] `backend/uploads/` → 755
- [ ] `backend/cache/` → 755

### Test
- [ ] Test database: `/backend/app/test_db_connection.php`
- [ ] Test frontend: buka domain
- [ ] Test login
- [ ] Cek console tidak ada error

---

## 🎯 Test Setelah Fix

### 1. Test Database Connection
```
https://coba.a2ubankdigital.my.id/backend/app/test_db_connection.php
```

**Expected:**
```
✅ Database connection successful!
Database: a2uj2723_coba
Tables: 43 tables found
```

### 2. Test Frontend
```
https://coba.a2ubankdigital.my.id
```

**Expected:**
- ✅ Halaman login muncul
- ✅ Tidak ada error 500 di console
- ✅ URL API tidak ada double slash

### 3. Test Login
```
Email: admin@taskora.id
Password: (cek di database)
```

**Expected:**
- ✅ Login berhasil
- ✅ Redirect ke dashboard
- ✅ Token tersimpan

---

## 🆘 Masih Error?

### Cek Error Log

1. cPanel → File Manager
2. Buka: `backend/error_log`
3. Lihat error terakhir
4. Copy error message

### Common Fixes

**Error: "Class 'PDO' not found"**
```
→ PHP PDO extension tidak aktif
→ Hubungi hosting untuk aktifkan PDO
```

**Error: "Access denied for user"**
```
→ Database credentials salah
→ Cek DB_USER dan DB_PASS di .env
```

**Error: "Unknown database"**
```
→ Database belum dibuat
→ Buat database di cPanel MySQL Databases
```

**Error: "No such file or directory"**
```
→ Path .env salah
→ Cek struktur folder di server
```

---

## 📝 Summary

1. ✅ **Upload frontend baru** (fix double slash)
2. ✅ **Buat database** di cPanel
3. ✅ **Import SQL** via phpMyAdmin
4. ✅ **Update .env** dengan credentials yang benar
5. ✅ **Set permissions** yang benar
6. ✅ **Test** database connection
7. ✅ **Test** login

**Setelah semua checklist ✅, error 500 akan hilang!** 🚀

---

**Butuh bantuan?** Screenshot error log dan kirim ke developer.
