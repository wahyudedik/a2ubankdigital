# 🚀 Deploy Ulang - Solusi Lengkap

Panduan deploy ulang dari awal untuk fix semua error.

---

## 📋 Persiapan di Local

### 1. Build Frontend Baru

```bash
cd frontend
npm run build
```

**Output:** `frontend/dist/`

### 2. Siapkan File Backend

**File yang perlu diupload:**
```
backend/
├── .env.production     ← Edit dulu!
├── app/
│   ├── vendor/         ← WAJIB ada!
│   ├── config.php
│   └── *.php (semua file)
├── uploads/
├── cache/
└── create_database.sql
```

### 3. Edit .env.production

```env
APP_ENV=production

# Database Production
DB_HOST="localhost"
DB_USER="a2uj2723_coba"
DB_PASS="]fliv{e3wTklew@O"
DB_NAME="a2uj2723_coba"

# CORS
ALLOWED_ORIGINS="https://coba.a2ubankdigital.my.id"

# JWT (sama seperti development)
JWT_SECRET="e4c8f1c6b9f74c4e7a6d8f3a2b1c9e0f5d7a8c9b0e1f2a3d4c5b6a7e8f9c0d1"
JWT_ISSUER="a2ubankdigital.my.id"
JWT_AUDIENCE="a2ubankdigital.my.id"

# Email
MAIL_HOST="mail.a2ubankdigital.my.id"
MAIL_PORT=465
MAIL_USERNAME="support@a2ubankdigital.my.id"
MAIL_PASSWORD="support@a2ubankdigital.my.id"
MAIL_ENCRYPTION="ssl"
MAIL_FROM_ADDRESS="support@a2ubankdigital.my.id"
MAIL_FROM_NAME="A2U Bank Digital"

# VAPID
VAPID_PUBLIC_KEY="BAa5p4tdGbiu03u1qNzTrEWewtf8CD3iWMzyvuSLF_j9KvdBAWl3dFMALpPY2SEWR44IfOXoc3UuaHAee1Nsi0Q"
VAPID_PRIVATE_KEY="VTXdyl5kF-lREOOWd2orvMF3Hfn2isen8VIhqcOUuAE"

# Digiflazz (opsional)
DIGIFLAZZ_USERNAME="SESUAIKAN"
DIGIFLAZZ_API_KEY="SESUAIKAN"
DIGIFLAZZ_PRODUCTION_KEY="SESUAIKAN"
DIGIFLAZZ_API_BASE_URL="https://api.digiflazz.com/v1"
DIGIFLAZZ_WEBHOOK_SECRET="SESUAIKAN"

# Midtrans (opsional)
MIDTRANS_SERVER_KEY="SB-Mid-server-MmJqryAihWtDB-5wpnJfV7XG"
MIDTRANS_CLIENT_KEY="SB-Mid-client-lhrDHVLRBkAXD1-G"
MIDTRANS_MERCHANT_ID="G028783534"
```

---

## 🗑️ Bersihkan Server

### 1. Hapus Semua File Lama

Di cPanel File Manager, hapus:
```
coba.a2ubankdigital.my.id/
├── backend/     ← HAPUS
├── frontend/    ← HAPUS
└── vendor/      ← HAPUS (jika ada di root)
```

**JANGAN HAPUS:**
- `.git/` (jika ada)
- `.vscode/` (jika ada)
- `*.md` (dokumentasi)

---

## 📤 Upload File Baru

### 1. Upload Backend

**Compress dulu di local:**
```
backend/ → backend.zip
```

**Upload ke server:**
1. cPanel → File Manager
2. Upload `backend.zip` ke root
3. Extract `backend.zip`
4. Hapus `backend.zip`

**Rename .env:**
```
backend/.env.production → backend/.env
```

### 2. Upload Frontend

**Compress dulu di local:**
```
frontend/dist/ → frontend-dist.zip
```

**Upload ke server:**
1. Upload `frontend-dist.zip` ke root
2. Extract `frontend-dist.zip`
3. Rename folder: `dist/` → `frontend/`
4. Buat folder: `frontend/dist/`
5. Pindahkan semua isi `frontend/` ke `frontend/dist/`
6. Hapus `frontend-dist.zip`

**Struktur akhir:**
```
coba.a2ubankdigital.my.id/
├── backend/
│   ├── .env
│   ├── app/
│   │   ├── vendor/
│   │   └── *.php
│   ├── uploads/
│   └── cache/
└── frontend/
    └── dist/
        ├── index.html
        ├── assets/
        └── ...
```

---

## ⚙️ Konfigurasi Server

### 1. Set Permissions

```
backend/          → 755
backend/.env      → 644
backend/app/      → 755
backend/uploads/  → 755
backend/cache/    → 755
frontend/dist/    → 755
```

### 2. Update Document Root

cPanel → Domains → Manage Domain:
```
Document Root: /home/a2uj2723/public_html/coba.a2ubankdigital.my.id/frontend/dist
```

### 3. Buat Folder Kosong

Jika belum ada:
```
backend/uploads/documents/  → 755
backend/uploads/proofs/     → 755
backend/cache/              → 755
```

---

## 🗄️ Setup Database

### 1. Buat Database (jika belum)

cPanel → MySQL Databases:
```
Database: a2uj2723_coba
User:     a2uj2723_coba
Password: ]fliv{e3wTklew@O
```

Add user to database: ALL PRIVILEGES

### 2. Import SQL

cPanel → phpMyAdmin:
1. Pilih database `a2uj2723_coba`
2. Import → `backend/create_database.sql`
3. Klik Go

---

## ✅ Test

### 1. Test Backend

```
https://coba.a2ubankdigital.my.id/backend/app/test_db_connection.php
```

**Expected:**
```
✅ Database connection successful!
Database: a2uj2723_coba
Tables: 43 tables
```

### 2. Test Frontend

```
https://coba.a2ubankdigital.my.id
```

**Expected:**
- ✅ Halaman login muncul
- ✅ Tidak ada error 500
- ✅ Tidak ada double slash di URL

### 3. Test Login

```
Email: admin@taskora.id
Password: (cek di database)
```

---

## 🔧 Troubleshooting

### Masih Error 500?

1. **Cek vendor folder:**
   ```
   backend/app/vendor/autoload.php harus ada!
   ```

2. **Cek .env:**
   ```
   backend/.env harus ada dan readable (644)
   ```

3. **Cek error log:**
   ```
   cPanel → Errors → Error Log
   ```

4. **Cek PHP version:**
   ```
   cPanel → MultiPHP Manager
   Pastikan PHP 7.4 atau 8.x
   ```

### Frontend tidak muncul?

1. **Cek Document Root:**
   ```
   Harus: frontend/dist
   ```

2. **Cek file index.html:**
   ```
   frontend/dist/index.html harus ada
   ```

---

## 📝 Checklist

- [ ] Build frontend (`npm run build`)
- [ ] Edit `backend/.env.production`
- [ ] Compress `backend/` jadi `backend.zip`
- [ ] Compress `frontend/dist/` jadi `frontend-dist.zip`
- [ ] Hapus file lama di server
- [ ] Upload `backend.zip` dan extract
- [ ] Upload `frontend-dist.zip` dan extract
- [ ] Rename `.env.production` jadi `.env`
- [ ] Set permissions (755/644)
- [ ] Update Document Root
- [ ] Buat database (jika belum)
- [ ] Import SQL
- [ ] Test backend
- [ ] Test frontend
- [ ] Test login

---

**Ikuti checklist ini step-by-step, error 500 akan hilang!** 🚀
