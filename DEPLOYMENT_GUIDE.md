# 📦 Panduan Deployment ke cPanel

Panduan lengkap untuk deploy A2U Bank Digital ke hosting cPanel.

---

## 📋 Persiapan Sebelum Deploy

### 1. Update Konfigurasi Production

#### A. Frontend Configuration
Edit file: `cgi-bin/frontend/src/config/config.production.js`

```javascript
api: {
  baseUrl: "https://DOMAIN-KAMU.com/app"  // ← Ganti dengan domain production
}
```

#### B. Backend Configuration  
Edit file: `.env.production`

```env
# Database Production
DB_HOST="localhost"
DB_USER="cpanel_dbuser"        # ← Ganti dengan user database cPanel
DB_PASS="password_database"    # ← Ganti dengan password database
DB_NAME="cpanel_dbname"        # ← Ganti dengan nama database

# CORS
ALLOWED_ORIGINS="https://DOMAIN-KAMU.com"  # ← Ganti dengan domain production

# Digiflazz (jika sudah punya akun production)
DIGIFLAZZ_USERNAME="username_production"
DIGIFLAZZ_API_KEY="api_key_production"
DIGIFLAZZ_PRODUCTION_KEY="production_key"

# Midtrans (jika sudah punya akun production)
MIDTRANS_SERVER_KEY="Mid-server-PRODUCTION"
MIDTRANS_CLIENT_KEY="Mid-client-PRODUCTION"
MIDTRANS_MERCHANT_ID="MERCHANT_ID"
```

### 2. Build Frontend

```bash
cd cgi-bin/frontend
npm run build
```

Hasil build akan ada di folder `cgi-bin/frontend/dist/`

---

## 🚀 Upload ke cPanel

### Struktur Folder di cPanel

```
/home/cpaneluser/public_html/domain.com/
├── index.html              ← dari dist/
├── manifest.webmanifest    ← dari dist/
├── sw.js                   ← dari dist/
├── workbox-*.js            ← dari dist/
├── assets/                 ← dari dist/assets/
│   ├── index-*.css
│   └── index-*.js
├── *.png, *.svg            ← semua gambar dari dist/
├── app/                    ← backend PHP (dari root/app/)
├── uploads/                ← dari root/uploads/
├── cache/                  ← dari root/cache/
└── .env                    ← copy dari .env.production
```

### Langkah Upload

1. **Login ke cPanel** → File Manager
2. **Masuk ke folder domain** (misal: `public_html/domain.com/`)
3. **Upload file-file ini:**

#### Frontend (dari `cgi-bin/frontend/dist/`)
- ✅ `index.html`
- ✅ `manifest.webmanifest`
- ✅ `sw.js`
- ✅ `workbox-*.js`
- ✅ Folder `assets/` (semua isi)
- ✅ Semua file gambar (*.png, *.svg)

#### Backend (dari root project)
- ✅ Folder `app/` (semua isi - 190+ file PHP)
- ✅ Folder `uploads/` (buat folder kosong jika belum ada)
- ✅ Folder `cache/` (buat folder kosong jika belum ada)
- ✅ File `.env` (copy dari `.env.production` dan rename jadi `.env`)

4. **Set Permissions** (klik kanan folder → Change Permissions):
   - `uploads/` → 755 atau 777
   - `cache/` → 755 atau 777

---

## 🗄️ Setup Database di cPanel

### 1. Buat Database

1. cPanel → MySQL Databases
2. Buat database baru (misal: `cpaneluser_a2ubank`)
3. Buat user database (misal: `cpaneluser_dbuser`)
4. Tambahkan user ke database dengan ALL PRIVILEGES

### 2. Import Database

1. cPanel → phpMyAdmin
2. Pilih database yang baru dibuat
3. Import → Pilih file `create_database.sql` dari project
4. Klik Go

### 3. Update .env di Server

Edit file `.env` di server (via File Manager → Edit):

```env
DB_HOST="localhost"
DB_USER="cpaneluser_dbuser"      # ← sesuai yang dibuat tadi
DB_PASS="password_database"       # ← sesuai yang dibuat tadi
DB_NAME="cpaneluser_a2ubank"      # ← sesuai yang dibuat tadi
```

---

## ✅ Verifikasi Deployment

### 1. Test Backend

Buka: `https://domain-kamu.com/app/test_db_connection.php`

Jika berhasil, akan muncul:
```
✅ Database connection successful!
Database: cpaneluser_a2ubank
```

### 2. Test Frontend

Buka: `https://domain-kamu.com`

Jika berhasil:
- Halaman login muncul
- Tidak ada error CORS di console browser
- Bisa login dengan akun yang ada

---

## 🔧 Troubleshooting

### Error: CORS Policy

**Penyebab:** `ALLOWED_ORIGINS` di `.env` tidak sesuai

**Solusi:**
```env
ALLOWED_ORIGINS="https://domain-kamu.com"
```
Pastikan tidak ada trailing slash!

### Error: Database Connection Failed

**Penyebab:** Kredensial database salah

**Solusi:**
1. Cek di cPanel → MySQL Databases
2. Pastikan user sudah ditambahkan ke database
3. Update `.env` dengan kredensial yang benar

### Error: 500 Internal Server Error

**Penyebab:** Permissions folder salah

**Solusi:**
```bash
chmod 755 uploads/
chmod 755 cache/
```

### Error: File Not Found (404)

**Penyebab:** File tidak terupload dengan benar

**Solusi:**
1. Pastikan `index.html` ada di root domain
2. Pastikan folder `app/` ada di root domain
3. Pastikan folder `assets/` ada di root domain

---

## 🔄 Update Aplikasi

Jika ada perubahan code:

### Update Frontend
```bash
cd cgi-bin/frontend
npm run build
```
Upload ulang isi folder `dist/` ke server

### Update Backend
Upload file PHP yang berubah ke folder `app/` di server

### Update Database
Jika ada perubahan struktur database, jalankan query SQL via phpMyAdmin

---

## 📝 Checklist Deployment

- [ ] Edit `config.production.js` (baseUrl)
- [ ] Edit `.env.production` (database, CORS, API keys)
- [ ] Build frontend (`npm run build`)
- [ ] Buat database di cPanel
- [ ] Import `create_database.sql`
- [ ] Upload frontend (dari `dist/`)
- [ ] Upload backend (folder `app/`)
- [ ] Upload `.env` (copy dari `.env.production`)
- [ ] Buat folder `uploads/` dan `cache/`
- [ ] Set permissions 755/777
- [ ] Test backend (`/app/test_db_connection.php`)
- [ ] Test frontend (buka domain)
- [ ] Test login
- [ ] Clear cache Cloudflare (jika pakai)

---

## 🆘 Butuh Bantuan?

Jika masih ada masalah, cek:
1. Browser Console (F12) untuk error JavaScript
2. cPanel Error Log untuk error PHP
3. File `.env` di server sudah benar
4. Permissions folder sudah benar

---

**Selamat! Aplikasi sudah live! 🎉**
