# 🎯 STRUKTUR PROJECT BARU - SUPER CLEAN

Project sudah direorganisasi menjadi struktur yang SUPER CLEAN dengan pemisahan total antara Frontend dan Backend.

---

## 📂 Struktur Baru

```
a2ubankdigital.my.id/
│
├── 📁 frontend/                    # FRONTEND ONLY
│   ├── 📁 src/                     # Source code React
│   │   ├── pages/
│   │   ├── components/
│   │   └── config/
│   ├── 📁 public/                  # Static files
│   ├── 📁 dist/                    # Build output
│   ├── 📁 node_modules/            # Dependencies
│   ├── package.json
│   ├── vite.config.js
│   └── *.png, *.svg                # Assets
│
├── 📁 backend/                     # BACKEND ONLY
│   ├── 📁 app/                     # PHP files (190+ endpoints)
│   ├── 📁 uploads/                 # User uploads
│   ├── 📁 cache/                   # Cache
│   ├── .env                        # Active config
│   ├── .env.development            # Dev config
│   ├── .env.production             # Prod config
│   ├── .env.example                # Template
│   ├── .htaccess                   # Apache config
│   └── create_database.sql         # Database schema
│
├── 📁 .git/                        # Version control
├── 📁 .vscode/                     # IDE config
│
└── 📄 Dokumentasi (*.md)
```

---

## ✨ Keuntungan Struktur Baru

### 1. Pemisahan Total
- ✅ Frontend 100% terpisah dari Backend
- ✅ Tidak ada file frontend di backend
- ✅ Tidak ada file backend di frontend
- ✅ Super clean dan mudah dipahami

### 2. Mudah Development
```bash
# Frontend
cd frontend
npm run dev

# Backend (Laravel Herd)
# Otomatis jalan di: http://a2ubankdigital.my.id.test
```

### 3. Mudah Deployment
```bash
# Build frontend
cd frontend
npm run build

# Upload ke server:
# - frontend/dist/ → root domain
# - backend/ → root domain (atau subfolder /api)
```

### 4. Mudah Maintenance
- ✅ Mau edit frontend? Masuk folder `frontend/`
- ✅ Mau edit backend? Masuk folder `backend/`
- ✅ Tidak bingung file mana yang mana
- ✅ Bisa dikerjakan oleh tim terpisah

### 5. Mudah Version Control
```bash
# Frontend developer
git pull
cd frontend
npm install
npm run dev

# Backend developer
git pull
cd backend
# Setup .env
# Jalankan Laravel Herd
```

---

## 🔄 Workflow Development

### Frontend Developer

```bash
# 1. Masuk folder frontend
cd frontend

# 2. Install dependencies (sekali aja)
npm install

# 3. Jalankan dev server
npm run dev

# 4. Edit code di:
frontend/src/

# 5. Test di browser:
http://localhost:5173
```

### Backend Developer

```bash
# 1. Masuk folder backend
cd backend

# 2. Setup .env
cp .env.development .env

# 3. Import database
# Import create_database.sql ke MySQL

# 4. Jalankan Laravel Herd
# Otomatis jalan di: http://a2ubankdigital.my.id.test

# 5. Edit code di:
backend/app/
```

---

## 🚀 Deployment ke Production

### Opsi 1: Frontend & Backend di Domain yang Sama

```
Server: /home/cpaneluser/public_html/domain.com/
│
├── index.html              ← dari frontend/dist/
├── assets/                 ← dari frontend/dist/assets/
├── *.png, *.svg            ← dari frontend/dist/
│
└── app/                    ← dari backend/app/
    ├── admin_*.php
    ├── user_*.php
    └── config.php
```

**Upload:**
1. Build frontend: `cd frontend && npm run build`
2. Upload `frontend/dist/*` → root domain
3. Upload `backend/app/` → root domain
4. Upload `backend/uploads/` → root domain
5. Upload `backend/cache/` → root domain
6. Upload `backend/.env.production` → `.env` di root

### Opsi 2: Frontend & Backend Terpisah (Recommended)

```
Frontend: https://domain.com
Backend:  https://api.domain.com (atau https://domain.com/api)
```

**Upload:**
1. Build frontend: `cd frontend && npm run build`
2. Upload `frontend/dist/*` → domain.com
3. Upload `backend/*` → api.domain.com (atau domain.com/api)

**Keuntungan:**
- ✅ Scaling lebih mudah
- ✅ Bisa pakai CDN untuk frontend
- ✅ Backend bisa di server terpisah
- ✅ Lebih aman (backend tidak exposed)

---

## 📝 Update Konfigurasi

### Frontend API URL

File: `frontend/src/config/config.production.js`

```javascript
// Opsi 1: Backend di /app
baseUrl: "https://domain.com/app"

// Opsi 2: Backend di subdomain
baseUrl: "https://api.domain.com"

// Opsi 3: Backend di /api
baseUrl: "https://domain.com/api"
```

### Backend CORS

File: `backend/.env.production`

```env
# Opsi 1: Frontend di domain utama
ALLOWED_ORIGINS="https://domain.com"

# Opsi 2: Frontend di subdomain
ALLOWED_ORIGINS="https://app.domain.com"

# Multiple origins
ALLOWED_ORIGINS="https://domain.com,https://app.domain.com"
```

---

## 🔧 Update Laravel Herd

Karena struktur berubah, update Laravel Herd:

### 1. Link Backend

```bash
cd backend
herd link a2ubankdigital
```

Backend akan jalan di: `http://a2ubankdigital.test`

### 2. Update Frontend Config

File: `frontend/src/config/config.development.js`

```javascript
api: {
  baseUrl: "http://a2ubankdigital.test/app"
}
```

---

## 📋 Checklist Migrasi

### Development

- [ ] `cd backend && herd link a2ubankdigital`
- [ ] Update `frontend/src/config/config.development.js`
- [ ] `cd frontend && npm install`
- [ ] `cd frontend && npm run dev`
- [ ] Test di browser

### Production

- [ ] Update `frontend/src/config/config.production.js`
- [ ] Update `backend/.env.production`
- [ ] `cd frontend && npm run build`
- [ ] Upload `frontend/dist/*` ke server
- [ ] Upload `backend/*` ke server
- [ ] Test di domain production

---

## 🎉 Kesimpulan

Struktur baru:
- ✅ Frontend 100% terpisah di folder `frontend/`
- ✅ Backend 100% terpisah di folder `backend/`
- ✅ Tidak ada file tercampur
- ✅ Super clean dan mudah maintenance
- ✅ Mudah dikerjakan oleh tim terpisah
- ✅ Siap untuk scaling

**Struktur ini adalah best practice untuk project modern!** 🚀

---

**Next:** Update Laravel Herd dan test development environment.
