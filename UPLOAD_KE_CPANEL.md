# 📤 Panduan Upload ke cPanel

Panduan step-by-step untuk upload project ke cPanel.

---

## 🎯 Struktur di Server

```
/home/a2uj2723/public_html/coba.a2ubankdigital.my.id/
│
├── frontend/
│   └── dist/              ← Frontend build (yang diakses user)
│       ├── index.html
│       ├── assets/
│       └── ...
│
└── backend/               ← Backend PHP
    ├── app/               ← API endpoints
    ├── uploads/
    ├── cache/
    └── .env
```

---

## 📋 Langkah Upload

### 1. Update Document Root di cPanel

1. Login cPanel → **Domains** → **Manage Domain**
2. Cari domain: `coba.a2ubankdigital.my.id`
3. Klik **Manage**
4. Update **Document Root** jadi:
   ```
   /home/a2uj2723/public_html/coba.a2ubankdigital.my.id/frontend/dist
   ```
5. Klik **Update**

**Hasil:** Domain akan mengarah ke `frontend/dist/` yang berisi `index.html`

---

### 2. Upload Frontend Build Baru

**File yang sudah di-build:** `frontend/dist/`

**Upload ke server:**
1. cPanel → **File Manager**
2. Masuk ke: `/home/a2uj2723/public_html/coba.a2ubankdigital.my.id/frontend/`
3. **Hapus folder `dist/` lama** (jika ada)
4. **Upload folder `dist/` baru** dari local
5. Pastikan struktur:
   ```
   frontend/
   └── dist/
       ├── index.html
       ├── assets/
       │   ├── index-B6Oxo2jW.js
       │   └── index-C22KAV_1.css
       ├── manifest.webmanifest
       ├── sw.js
       └── *.png, *.svg
   ```

---

### 3. Setup Backend .env

**File local:** `backend/.env.production`

**Upload ke server:**
1. Buka `backend/.env.production` di local
2. Copy isinya
3. Di cPanel File Manager, buka: `backend/.env`
4. Paste dan **edit** sesuai server:

```env
APP_ENV=production

# Database Production - SESUAIKAN!
DB_HOST="localhost"
DB_USER="a2uj2723_dbuser"        # ← Ganti dengan user database cPanel
DB_PASS="PASSWORD_DATABASE"       # ← Ganti dengan password database
DB_NAME="a2uj2723_czsczczx"       # ← Ganti dengan nama database

# CORS - PENTING!
ALLOWED_ORIGINS="https://coba.a2ubankdigital.my.id"

# JWT (sama seperti development)
JWT_SECRET="e4c8f1c6b9f74c4e7a6d8f3a2b1c9e0f5d7a8c9b0e1f2a3d4c5b6a7e8f9c0d1"
JWT_ISSUER="a2ubankdigital.my.id"
JWT_AUDIENCE="a2ubankdigital.my.id"

# Email (sama seperti development)
MAIL_HOST="mail.a2ubankdigital.my.id"
MAIL_PORT=465
MAIL_USERNAME="support@a2ubankdigital.my.id"
MAIL_PASSWORD="support@a2ubankdigital.my.id"
MAIL_ENCRYPTION="ssl"
MAIL_FROM_ADDRESS="support@a2ubankdigital.my.id"
MAIL_FROM_NAME="A2U Bank Digital"

# VAPID (sama seperti development)
VAPID_PUBLIC_KEY="BAa5p4tdGbiu03u1qNzTrEWewtf8CD3iWMzyvuSLF_j9KvdBAWl3dFMALpPY2SEWR44IfOXoc3UuaHAee1Nsi0Q"
VAPID_PRIVATE_KEY="VTXdyl5kF-lREOOWd2orvMF3Hfn2isen8VIhqcOUuAE"

# Digiflazz (jika sudah punya akun production)
DIGIFLAZZ_USERNAME="USERNAME_PRODUCTION"
DIGIFLAZZ_API_KEY="API_KEY_PRODUCTION"
DIGIFLAZZ_PRODUCTION_KEY="PRODUCTION_KEY"
DIGIFLAZZ_API_BASE_URL="https://api.digiflazz.com/v1"
DIGIFLAZZ_WEBHOOK_SECRET="WEBHOOK_SECRET"

# Midtrans (jika sudah punya akun production)
MIDTRANS_SERVER_KEY="Mid-server-PRODUCTION"
MIDTRANS_CLIENT_KEY="Mid-client-PRODUCTION"
MIDTRANS_MERCHANT_ID="MERCHANT_ID"
```

5. **Save**

---

### 4. Set Permissions

Di cPanel File Manager:

1. Klik kanan folder `backend/uploads/` → **Change Permissions** → `755`
2. Klik kanan folder `backend/cache/` → **Change Permissions** → `755`

---

### 5. Test Backend

Buka di browser:
```
https://coba.a2ubankdigital.my.id/backend/app/test_db_connection.php
```

**Jika berhasil:**
```
✅ Database connection successful!
Database: a2uj2723_czsczczx
```

**Jika error:**
- Cek credentials database di `backend/.env`
- Cek database sudah dibuat di cPanel
- Cek user sudah ditambahkan ke database

---

### 6. Test Frontend

Buka di browser:
```
https://coba.a2ubankdigital.my.id
```

**Jika berhasil:**
- ✅ Halaman login muncul
- ✅ Tidak ada error 404 di console
- ✅ Bisa login

**Jika error 404:**
- Cek Document Root sudah diupdate
- Cek folder `frontend/dist/` ada di server
- Cek file `index.html` ada di `frontend/dist/`

---

## 🔍 Troubleshooting

### Error: 404 Not Found (Backend)

**URL yang dicoba:** `https://coba.a2ubankdigital.my.id/app/...`

**Penyebab:** Backend ada di `/backend/app/` bukan `/app/`

**Solusi:**
1. Cek `frontend/src/config/config.production.js`:
   ```javascript
   baseUrl: "https://coba.a2ubankdigital.my.id/backend/app"
   ```
2. Build ulang: `npm run build`
3. Upload `dist/` baru ke server

---

### Error: CORS Policy

**Penyebab:** `ALLOWED_ORIGINS` di `backend/.env` tidak sesuai

**Solusi:**
```env
ALLOWED_ORIGINS="https://coba.a2ubankdigital.my.id"
```
Pastikan tidak ada trailing slash!

---

### Error: Database Connection Failed

**Penyebab:** Credentials database salah

**Solusi:**
1. cPanel → **MySQL Databases**
2. Cek nama database, user, password
3. Update `backend/.env`
4. Test: `https://coba.a2ubankdigital.my.id/backend/app/test_db_connection.php`

---

### Error: 500 Internal Server Error

**Penyebab:** Permissions folder salah

**Solusi:**
```bash
chmod 755 backend/uploads/
chmod 755 backend/cache/
```

---

## ✅ Checklist Upload

- [ ] Update Document Root ke `frontend/dist`
- [ ] Upload `frontend/dist/` baru ke server
- [ ] Update `backend/.env` dengan credentials production
- [ ] Set permissions `backend/uploads/` dan `backend/cache/` (755)
- [ ] Test backend: `/backend/app/test_db_connection.php`
- [ ] Test frontend: buka domain
- [ ] Test login
- [ ] Clear cache Cloudflare (jika pakai)

---

## 🎉 Selesai!

Jika semua checklist sudah ✅, aplikasi sudah live dan bisa diakses!

**URL:**
- Frontend: `https://coba.a2ubankdigital.my.id`
- Backend API: `https://coba.a2ubankdigital.my.id/backend/app`

---

**Butuh bantuan?** Cek error di browser console (F12) atau cPanel Error Log.
