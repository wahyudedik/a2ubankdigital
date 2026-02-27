# 📁 Penjelasan Struktur Folder

Penjelasan lengkap tentang struktur folder project dan kenapa dibuat seperti ini.

---

## ❓ Pertanyaan Umum

### 1. Kenapa ada folder `cgi-bin/frontend`?

**Jawaban:** Ini adalah folder SOURCE CODE React yang kamu edit saat development.

```
cgi-bin/frontend/
├── src/              ← Source code React (edit di sini)
├── public/           ← Static files
├── dist/             ← Hasil build (otomatis dibuat)
├── package.json      ← Dependencies
└── vite.config.js    ← Build config
```

**Fungsi:**
- Development: `npm run dev` → jalankan dev server
- Production: `npm run build` → compile ke folder `dist/`

---

### 2. Kenapa build ada di `cgi-bin/frontend/dist/`?

**Jawaban:** Karena Vite (build tool) secara default membuat folder `dist/` di dalam project frontend.

**Alur Build:**
```
npm run build
    ↓
Vite compile React code
    ↓
Output ke: cgi-bin/frontend/dist/
    ↓
Isi dist/ yang diupload ke server
```

---

### 3. Kenapa tidak langsung build ke root?

**Jawaban:** Untuk menjaga pemisahan yang jelas antara:
- Source code (cgi-bin/frontend/src/)
- Build output (cgi-bin/frontend/dist/)
- Backend (app/)

**Keuntungan:**
- ✅ Source code tidak tercampur dengan build
- ✅ Bisa rebuild kapan saja tanpa hapus source
- ✅ Struktur lebih rapi dan terorganisir
- ✅ Mudah di-gitignore (dist/ tidak perlu di-commit)

---

### 4. Folder `assets` di root untuk apa?

**Jawaban:** Folder `assets` di root KOSONG dan TIDAK DIPAKAI.

**Sudah dihapus!** ✅

Yang dipakai adalah:
- `cgi-bin/frontend/dist/assets/` → Hasil build (JS & CSS)
- `cgi-bin/frontend/public/` → Static files (gambar, icon)

---

### 5. Apakah menghapus folder `assets` berpengaruh?

**Jawaban:** TIDAK! Folder `assets` di root kosong dan tidak direferensi oleh code manapun.

**Yang dipakai:**
```
Frontend build:
cgi-bin/frontend/dist/assets/
├── index-*.css      ← CSS hasil compile
├── index-*.js       ← JavaScript hasil compile
└── workbox-*.js     ← Service worker

Static files:
cgi-bin/frontend/public/
├── *.png            ← Gambar
├── *.svg            ← Icon
└── manifest.json    ← PWA manifest
```

---

## 📂 Struktur Lengkap

### Development (Local)

```
a2ubankdigital.my.id/
│
├── 📁 cgi-bin/
│   └── 📁 frontend/              # Frontend React
│       ├── 📁 src/               # ← EDIT DI SINI (development)
│       │   ├── pages/
│       │   ├── components/
│       │   └── config/
│       ├── 📁 public/            # Static files
│       ├── 📁 dist/              # ← Build output (production)
│       │   ├── index.html
│       │   ├── assets/
│       │   └── *.png, *.svg
│       └── package.json
│
├── 📁 app/                       # Backend PHP
│   ├── admin_*.php
│   ├── user_*.php
│   └── config.php
│
├── 📁 uploads/                   # User uploads
├── 📁 cache/                     # Cache
└── .env                          # Backend config
```

### Production (cPanel)

```
/home/cpaneluser/public_html/domain.com/
│
├── index.html              ← dari dist/
├── manifest.webmanifest    ← dari dist/
├── sw.js                   ← dari dist/
├── *.png, *.svg            ← dari dist/
│
├── 📁 assets/              ← dari dist/assets/
│   ├── index-*.css
│   └── index-*.js
│
├── 📁 app/                 ← backend PHP
├── 📁 uploads/             ← user uploads
├── 📁 cache/               ← cache
└── .env                    ← backend config
```

**TIDAK ADA folder `cgi-bin` di production!**

---

## 🔄 Alur Kerja

### Development

```bash
# 1. Edit source code
cd cgi-bin/frontend/src/

# 2. Jalankan dev server
npm run dev

# 3. Test di browser
http://localhost:5173
```

**Folder yang dipakai:**
- `cgi-bin/frontend/src/` → source code
- `cgi-bin/frontend/public/` → static files

### Production

```bash
# 1. Build frontend
cd cgi-bin/frontend
npm run build

# 2. Hasil build ada di:
cgi-bin/frontend/dist/

# 3. Upload ke server:
# - Isi dist/ → root domain
# - Folder app/ → root domain
# - .env.production → .env
```

**Folder yang diupload:**
- `cgi-bin/frontend/dist/` → root domain
- `app/` → root domain
- `uploads/` → root domain
- `cache/` → root domain

---

## ⚠️ PENTING!

### Jangan Upload Folder Ini ke Server:

- ❌ `cgi-bin/` (source code, tidak perlu di server)
- ❌ `node_modules/` (dependencies, terlalu besar)
- ❌ `.git/` (version control, tidak perlu)
- ❌ `.vscode/` (IDE config, tidak perlu)

### Yang Diupload ke Server:

- ✅ Isi `cgi-bin/frontend/dist/` → root domain
- ✅ Folder `app/` → root domain
- ✅ Folder `uploads/` → root domain
- ✅ Folder `cache/` → root domain
- ✅ File `.env` → root domain

---

## 🎯 Kenapa Struktur Seperti Ini?

### 1. Pemisahan Source & Build

```
Source (development):
cgi-bin/frontend/src/     ← Edit di sini

Build (production):
cgi-bin/frontend/dist/    ← Upload ini ke server
```

**Keuntungan:**
- Source code tetap rapi
- Build bisa di-regenerate kapan saja
- Tidak tercampur dengan backend

### 2. Folder `cgi-bin` untuk Development

Nama `cgi-bin` adalah konvensi web server untuk executable scripts. Tapi di project ini, kita pakai untuk menyimpan source code frontend yang tidak perlu diupload ke server.

**Alternatif nama:**
- `frontend-src/`
- `client/`
- `react-app/`

Tapi kita pakai `cgi-bin/frontend/` karena sudah ada dari awal.

### 3. Build Output di `dist/`

Vite (build tool) secara default membuat folder `dist/` untuk output. Ini adalah standar industri:
- Vite → `dist/`
- Create React App → `build/`
- Next.js → `.next/`

---

## 🔧 Cara Mengubah Output Build

Jika kamu mau build langsung ke root (tidak recommended), edit `vite.config.js`:

```javascript
export default defineConfig({
  build: {
    outDir: '../../',  // Build ke root
  }
})
```

**Tapi TIDAK DISARANKAN karena:**
- ❌ File build tercampur dengan source code
- ❌ Susah dibedakan mana source mana build
- ❌ Rawan salah hapus file

**Lebih baik tetap pakai `dist/` dan upload manual.**

---

## ✅ Kesimpulan

1. **Source code:** `cgi-bin/frontend/src/` (edit di sini)
2. **Build output:** `cgi-bin/frontend/dist/` (upload ini)
3. **Backend:** `app/` (upload ini)
4. **Folder `assets` di root:** KOSONG, sudah dihapus ✅
5. **Tidak berpengaruh:** Menghapus `assets` tidak berpengaruh ke fitur apapun

**Struktur ini sudah optimal untuk development dan deployment!** 🚀

---

**Butuh bantuan?** Baca `MULAI_DISINI.md`
