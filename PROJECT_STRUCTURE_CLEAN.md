# 📁 Struktur Project A2U Bank Digital

Dokumentasi struktur project yang sudah dirapikan untuk kemudahan deployment.

---

## 🗂️ Struktur Folder

```
a2ubankdigital.my.id/
│
├── 📁 app/                          # Backend PHP (190+ files)
│   ├── admin_*.php                  # Admin endpoints
│   ├── user_*.php                   # User endpoints
│   ├── auth_*.php                   # Authentication
│   ├── utility_*.php                # Utilities
│   └── config.php                   # Backend config
│
├── 📁 cgi-bin/
│   └── 📁 frontend/                 # Frontend React
│       ├── 📁 src/                  # Source code
│       │   ├── 📁 config/           # ⭐ Configuration files
│       │   │   ├── index.js         # Auto-switch (jangan edit!)
│       │   │   ├── config.development.js   # ⚙️ Config development
│       │   │   └── config.production.js    # ⚙️ Config production
│       │   ├── 📁 pages/            # React pages
│       │   ├── 📁 components/       # React components
│       │   └── 📁 utils/            # Utilities
│       ├── 📁 dist/                 # ⭐ Build output (untuk upload)
│       ├── 📁 public/               # Static assets
│       ├── package.json             # Dependencies
│       └── vite.config.js           # Vite config
│
├── 📁 uploads/                      # User uploads
│   ├── 📁 documents/                # KTP, Selfie
│   └── 📁 proofs/                   # Bukti transfer
│
├── 📁 cache/                        # Cache files
│
├── 📄 .env                          # ⭐ Active config (jangan edit!)
├── 📄 .env.development              # ⚙️ Config development
├── 📄 .env.production               # ⚙️ Config production
├── 📄 .env.example                  # Template
│
├── 📄 create_database.sql           # Database schema
│
├── 📄 DEPLOYMENT_GUIDE.md           # 📖 Panduan deployment
├── 📄 CARA_GANTI_KONFIGURASI.md     # 📖 Panduan ganti config
└── 📄 PROJECT_STRUCTURE_CLEAN.md    # 📖 File ini
```

---

## 🎯 File Penting untuk Konfigurasi

### Frontend Configuration

| File | Fungsi | Edit? |
|------|--------|-------|
| `cgi-bin/frontend/src/config/index.js` | Auto-switch config | ❌ Jangan! |
| `cgi-bin/frontend/src/config/config.development.js` | Config development | ✅ Ya |
| `cgi-bin/frontend/src/config/config.production.js` | Config production | ✅ Ya |

**Yang perlu diubah:**
- API baseUrl (backend URL)
- Branding (nama, logo)
- Theme colors

### Backend Configuration

| File | Fungsi | Edit? |
|------|--------|-------|
| `.env` | Active config | ❌ Jangan! |
| `.env.development` | Config development | ✅ Ya |
| `.env.production` | Config production | ✅ Ya |

**Yang perlu diubah:**
- Database credentials
- CORS (ALLOWED_ORIGINS)
- Email configuration
- API keys (Digiflazz, Midtrans)

---

## 🚀 Workflow Development

### 1. Development Lokal

```bash
# Backend (Laravel Herd)
# Otomatis jalan di: http://a2ubankdigital.my.id.test

# Frontend
cd cgi-bin/frontend
npm run dev
# Jalan di: http://localhost:5173
```

**Config yang dipakai:**
- Frontend: `config.development.js`
- Backend: `.env.development`

### 2. Build untuk Production

```bash
cd cgi-bin/frontend
npm run build
```

**Output:** `cgi-bin/frontend/dist/`

**Config yang dipakai:**
- Frontend: `config.production.js`
- Backend: `.env.production`

### 3. Upload ke cPanel

**Upload file ini:**
- ✅ Isi folder `dist/` → root domain
- ✅ Folder `app/` → root domain
- ✅ Folder `uploads/` → root domain
- ✅ Folder `cache/` → root domain
- ✅ File `.env.production` → rename jadi `.env` di server

**Struktur di server:**
```
/home/cpaneluser/public_html/domain.com/
├── index.html           ← dari dist/
├── assets/              ← dari dist/assets/
├── app/                 ← backend PHP
├── uploads/             ← user uploads
├── cache/               ← cache
└── .env                 ← dari .env.production
```

---

## 📝 Cara Ganti Konfigurasi

### Ganti API URL

**Development:**
```javascript
// File: cgi-bin/frontend/src/config/config.development.js
api: {
  baseUrl: "http://a2ubankdigital.my.id.test/app"
}
```

**Production:**
```javascript
// File: cgi-bin/frontend/src/config/config.production.js
api: {
  baseUrl: "https://domain-kamu.com/app"
}
```

### Ganti Database

**Development:**
```env
# File: .env.development
DB_HOST="localhost"
DB_USER="root"
DB_PASS=""
DB_NAME="czsczczx"
```

**Production:**
```env
# File: .env.production
DB_HOST="localhost"
DB_USER="cpaneluser_dbuser"
DB_PASS="password_database"
DB_NAME="cpaneluser_dbname"
```

### Ganti CORS

**Development:**
```env
# File: .env.development
ALLOWED_ORIGINS="http://localhost:5173,http://a2ubankdigital.my.id.test"
```

**Production:**
```env
# File: .env.production
ALLOWED_ORIGINS="https://domain-kamu.com"
```

---

## ✅ Keuntungan Struktur Ini

### 1. Pemisahan Environment
- ✅ Development dan production terpisah
- ✅ Tidak perlu edit config bolak-balik
- ✅ Auto-switch berdasarkan environment

### 2. Mudah Deploy
- ✅ Tinggal build dan upload
- ✅ Tidak perlu edit file di server
- ✅ Semua config sudah siap

### 3. Mudah Maintenance
- ✅ Config terpusat di 2 file
- ✅ Dokumentasi lengkap
- ✅ Tidak ada file yang perlu dihapus

### 4. Aman
- ✅ `.env.production` tidak ter-commit ke git
- ✅ Credentials production terpisah
- ✅ Tidak ada hardcoded config

---

## 🔄 Alur Kerja

### Development
1. Edit code di `cgi-bin/frontend/src/`
2. Jalankan `npm run dev`
3. Test di `http://localhost:5173`
4. Backend otomatis connect ke Laravel Herd

### Production
1. Edit `config.production.js` (jika perlu)
2. Edit `.env.production` (jika perlu)
3. Build: `npm run build`
4. Upload isi `dist/` + `app/` + `.env` ke server
5. Test di domain production

---

## 📚 Dokumentasi Lengkap

- **Deployment:** Baca `DEPLOYMENT_GUIDE.md`
- **Ganti Config:** Baca `CARA_GANTI_KONFIGURASI.md`
- **API Docs:** Baca `API_DOCUMENTATION.md`

---

## 🆘 Troubleshooting

### Frontend tidak connect ke backend
→ Cek `config.production.js` baseUrl

### Error CORS
→ Cek `.env` di server, `ALLOWED_ORIGINS`

### Database connection failed
→ Cek `.env` di server, credentials database

### Build error
→ Cek `npm install` sudah jalan

---

**Happy Coding! 🚀**
