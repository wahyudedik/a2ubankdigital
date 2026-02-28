# ✅ CHECKLIST SEBELUM UPLOAD KE CPANEL

## 📋 STATUS PENGECEKAN

### ✅ STRUKTUR FOLDER - SUDAH BENAR
```
/public_html/coba.a2ubankdigital.my.id/
├── .htaccess                    ✅ SUDAH DIPERBAIKI
├── frontend/
│   └── dist/
│       ├── .htaccess            ✅ SUDAH DIPERBAIKI
│       ├── index.html           ✅ ADA
│       ├── assets/              ✅ ADA
│       └── ...
└── backend/
    ├── .env                     ⚠️ COPY DARI .env.production
    ├── app/
    │   ├── vendor/              ✅ ADA (WAJIB UPLOAD!)
    │   ├── config.php           ✅ SUDAH BENAR
    │   └── ...
    ├── uploads/                 ✅ ADA
    └── cache/                   ✅ ADA
```

---

## 🔧 YANG SUDAH DIPERBAIKI

### 1. ✅ `.htaccess` di Root
**SEBELUM** (SALAH):
```apache
RewriteRule . /index.html [L]  # ❌ index.html tidak ada di root!
```

**SESUDAH** (BENAR):
```apache
RewriteRule ^(.*)$ /frontend/dist/$1 [L]  # ✅ Redirect ke frontend/dist/
```

### 2. ✅ `.htaccess` di `frontend/dist/`
**Path sudah diperbaiki**:
```apache
RewriteRule ^(.*)$ /frontend/dist/index.html [L]  # ✅ Path lengkap
```

### 3. ✅ File Test Dihapus
- ❌ `test_php_root.php` - SUDAH DIHAPUS

---

## 📦 CARA UPLOAD KE CPANEL

### Step 1: Build Frontend (WAJIB!)
```bash
cd frontend
npm run build
```

### Step 2: Upload via File Manager cPanel
Upload semua file dan folder ini ke `/public_html/coba.a2ubankdigital.my.id/`:

```
✅ .htaccess (root)
✅ frontend/ (seluruh folder)
✅ backend/ (seluruh folder)
✅ File dokumentasi .md (opsional)
```

**PENTING**: 
- ✅ Upload folder `backend/app/vendor/` (WAJIB! Ukuran ~50MB)
- ✅ Jangan skip file hidden seperti `.htaccess` dan `.env`

### Step 3: Setup .env di Server
```bash
# Di cPanel File Manager, masuk ke folder backend/
# Copy file .env.production jadi .env
cp .env.production .env
```

Atau manual:
1. Buka `backend/.env.production`
2. Copy semua isinya
3. Buat file baru `backend/.env`
4. Paste isinya

### Step 4: Set Permissions (PENTING!)
Di cPanel File Manager, set permissions:
```
backend/app/          → 755
backend/uploads/      → 755
backend/cache/        → 755
backend/.env          → 644
frontend/dist/        → 755
```

### Step 5: Test!
1. **Test Frontend**: https://coba.a2ubankdigital.my.id
   - Harus muncul halaman login/home
   
2. **Test Backend**: https://coba.a2ubankdigital.my.id/backend/app/test_db_connection.php
   - Harus muncul "All tests passed!"

---

## 🎯 KONFIGURASI YANG SUDAH BENAR

### Frontend Config (`frontend/src/config/config.production.js`)
```javascript
baseUrl: "https://coba.a2ubankdigital.my.id/backend/app"  ✅
```

### Backend Config (`backend/.env.production`)
```env
DB_HOST="localhost"                                        ✅
DB_USER="a2uj2723_coba"                                   ✅
DB_NAME="a2uj2723_coba"                                   ✅
ALLOWED_ORIGINS="https://coba.a2ubankdigital.my.id"      ✅
```

### Routing
```
https://coba.a2ubankdigital.my.id/
  → frontend/dist/index.html                              ✅

https://coba.a2ubankdigital.my.id/backend/app/...
  → backend/app/...                                       ✅
```

---

## ⚠️ TROUBLESHOOTING

### Jika Error 500:
1. Cek PHP version di cPanel → Harus 8.2+
2. Cek file `backend/.env` ada dan benar
3. Cek folder `backend/app/vendor/` ada (50MB+)
4. Lihat error log di cPanel

### Jika Error 403 Forbidden:
1. Cek permissions folder (755)
2. Cek file `.htaccess` ada di root dan `frontend/dis