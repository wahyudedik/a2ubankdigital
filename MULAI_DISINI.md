# 🚀 MULAI DISINI

Selamat datang di A2U Bank Digital! Project sudah direorganisasi dengan struktur SUPER CLEAN.

---

## 🎯 STRUKTUR BARU

```
a2ubankdigital.my.id/
├── 📁 frontend/        # Frontend React (100% terpisah)
├── 📁 backend/         # Backend PHP (100% terpisah)
└── 📄 *.md             # Dokumentasi
```

**Baca:** [STRUKTUR_BARU.md](STRUKTUR_BARU.md) untuk penjelasan lengkap!

---

## 📚 Dokumentasi

### ⭐ PENTING - Baca Dulu!

0. **[STRUKTUR_BARU.md](STRUKTUR_BARU.md)** ⭐⭐⭐ WAJIB BACA!
   - Struktur project baru (super clean)
   - Pemisahan frontend & backend
   - Workflow development baru
   - Update Laravel Herd

### 🚀 Deploy ke Production

1. **[QUICK_DEPLOY.md](QUICK_DEPLOY.md)** ⭐ MULAI DISINI!
   - Cheat sheet deploy cepat
   - Checklist deployment
   - Troubleshooting cepat

2. **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)**
   - Panduan deployment lengkap
   - Step-by-step detail
   - Setup database

### 🔧 Konfigurasi

3. **[CARA_GANTI_KONFIGURASI.md](CARA_GANTI_KONFIGURASI.md)**
   - Cara ganti API URL
   - Cara ganti database
   - Cara ganti CORS
   - Cara ganti API keys

### 📁 Struktur Project

4. **[PROJECT_STRUCTURE_CLEAN.md](PROJECT_STRUCTURE_CLEAN.md)**
   - Struktur folder project
   - File-file penting
   - Alur kerja development

5. **[PENJELASAN_STRUKTUR.md](PENJELASAN_STRUKTUR.md)** ⭐ BACA INI!
   - Kenapa ada folder cgi-bin/frontend?
   - Kenapa build di dist/?
   - Folder mana yang diupload?
   - FAQ lengkap

---

## ⚡ Quick Start

### Development Lokal

```bash
# 1. Setup backend
cd backend
cp .env.development .env
# Import create_database.sql ke MySQL

# 2. Link Laravel Herd
herd link a2ubankdigital
# Backend: http://a2ubankdigital.test

# 3. Setup frontend
cd ../frontend
npm install
npm run dev
# Frontend: http://localhost:5173
```

### Deploy ke Production

```bash
# 1. Edit config production
# - frontend/src/config/config.production.js
# - backend/.env.production

# 2. Build frontend
cd frontend
npm run build

# 3. Upload ke cPanel (lihat QUICK_DEPLOY.md)
```

---

## 📁 File Konfigurasi Penting

### Frontend

| File | Fungsi | Edit? |
|------|--------|-------|
| `frontend/src/config/config.development.js` | Config development | ✅ |
| `frontend/src/config/config.production.js` | Config production | ✅ |
| `frontend/src/config/index.js` | Auto-switch | ❌ |

### Backend

| File | Fungsi | Edit? |
|------|--------|-------|
| `backend/.env.development` | Config development | ✅ |
| `backend/.env.production` | Config production | ✅ |
| `backend/.env` | Active config | ❌ |

---

## 🎯 Yang Perlu Diubah Sebelum Deploy

### 1. Frontend API URL

File: `frontend/src/config/config.production.js`

```javascript
api: {
  baseUrl: "https://DOMAIN-KAMU.com/app"  // ← Ganti ini!
}
```

### 2. Backend Database

File: `backend/.env.production`

```env
DB_USER="cpanel_user"      # ← Ganti ini!
DB_PASS="password"         # ← Ganti ini!
DB_NAME="cpanel_db"        # ← Ganti ini!
```

### 3. Backend CORS

File: `backend/.env.production`

```env
ALLOWED_ORIGINS="https://DOMAIN-KAMU.com"  # ← Ganti ini!
```

---

## 🔄 Workflow

```
Development:
1. cd frontend && npm run dev
2. cd backend (Laravel Herd otomatis jalan)
3. Test di localhost

Production:
1. Edit frontend/src/config/config.production.js
2. Edit backend/.env.production
3. cd frontend && npm run build
4. Upload frontend/dist/ + backend/ ke server
5. Test di domain
```

---

## 🆘 Troubleshooting

| Masalah | Solusi |
|---------|--------|
| CORS Error | Cek `ALLOWED_ORIGINS` di `.env` server |
| Database Error | Cek credentials di `.env` server |
| 404 Error | Cek `index.html` ada di root domain |
| 500 Error | Cek permissions `uploads/` & `cache/` (755) |
| Frontend tidak connect | Cek `baseUrl` di `config.production.js` |

---

## 📖 Dokumentasi Lainnya

- **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** - Dokumentasi API backend
- **[README.md](README.md)** - Overview project
- **[CHANGELOG.md](CHANGELOG.md)** - History perubahan

---

## ✅ Checklist Deploy Pertama Kali

- [ ] Baca `STRUKTUR_BARU.md` (WAJIB!)
- [ ] Baca `QUICK_DEPLOY.md`
- [ ] Edit `frontend/src/config/config.production.js`
- [ ] Edit `backend/.env.production`
- [ ] Build frontend (`cd frontend && npm run build`)
- [ ] Buat database di cPanel
- [ ] Import `backend/create_database.sql`
- [ ] Upload file ke server
- [ ] Set permissions (755)
- [ ] Test backend
- [ ] Test frontend
- [ ] Test login

---

## 🎉 Selamat!

Struktur project sudah rapi dan siap deploy!

**Mulai dari:** [QUICK_DEPLOY.md](QUICK_DEPLOY.md)

**Butuh bantuan?** Baca dokumentasi di atas atau hubungi developer.

---

**Happy Coding! 🚀**
