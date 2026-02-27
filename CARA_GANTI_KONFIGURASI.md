# 🔧 Cara Ganti Konfigurasi

Panduan cepat untuk mengubah konfigurasi aplikasi.

---

## 📱 Frontend Configuration

### Lokasi File

```
cgi-bin/frontend/src/config/
├── index.js                    ← Jangan edit! (auto-switch)
├── config.development.js       ← Edit ini untuk development
└── config.production.js        ← Edit ini untuk production
```

### Cara Mengubah API URL

#### Development (Local)
Edit: `cgi-bin/frontend/src/config/config.development.js`

```javascript
api: {
  baseUrl: "http://a2ubankdigital.my.id.test/app"  // ← Ganti sesuai Laravel Herd
}
```

#### Production (cPanel)
Edit: `cgi-bin/frontend/src/config/config.production.js`

```javascript
api: {
  baseUrl: "https://domain-kamu.com/app"  // ← Ganti dengan domain production
}
```

**PENTING:** 
- Tidak pakai trailing slash (/)
- Format: `https://domain.com/app` ✅
- Bukan: `https://domain.com/app/` ❌

### Cara Mengubah Branding

Edit file `config.development.js` atau `config.production.js`:

```javascript
brand: {
  name: "Nama Bank Kamu",           // ← Ganti nama
  logo: "/logo-kamu.png",           // ← Ganti logo
  logoWhite: "/logo-white.png",     // ← Ganti logo putih
}
```

### Cara Mengubah Warna Tema

Edit file `config.development.js` atau `config.production.js`:

```javascript
theme: {
  colors: {
    BPN_BLUE: "#00AEEF",    // ← Warna primary
    BPN_YELLOW: "#FBBF24",  // ← Warna warning
    BPN_RED: "#DC2626",     // ← Warna error
  }
}
```

---

## 🔌 Backend Configuration

### Lokasi File

```
root/
├── .env                    ← File aktif (jangan edit langsung!)
├── .env.development        ← Edit ini untuk development
└── .env.production         ← Edit ini untuk production
```

### Cara Mengubah Database

#### Development (Local)
Edit: `.env.development`

```env
DB_HOST="localhost"
DB_USER="root"
DB_PASS=""
DB_NAME="czsczczx"
```

#### Production (cPanel)
Edit: `.env.production`

```env
DB_HOST="localhost"
DB_USER="cpaneluser_dbuser"      # ← User database cPanel
DB_PASS="password_database"       # ← Password database
DB_NAME="cpaneluser_dbname"       # ← Nama database
```

### Cara Mengubah CORS (Allowed Origins)

#### Development
Edit: `.env.development`

```env
ALLOWED_ORIGINS="http://localhost:5173,http://localhost:5174,http://a2ubankdigital.my.id.test"
```

#### Production
Edit: `.env.production`

```env
ALLOWED_ORIGINS="https://domain-kamu.com"
```

**PENTING:**
- Pisahkan dengan koma (,) jika lebih dari 1
- Tidak pakai trailing slash (/)
- Harus sama dengan domain frontend

### Cara Mengubah Email Configuration

Edit: `.env.development` atau `.env.production`

```env
MAIL_HOST="mail.domain-kamu.com"
MAIL_PORT=465
MAIL_USERNAME="support@domain-kamu.com"
MAIL_PASSWORD="password_email"
MAIL_ENCRYPTION="ssl"
MAIL_FROM_ADDRESS="support@domain-kamu.com"
MAIL_FROM_NAME="Nama Bank Kamu"
```

### Cara Mengubah Digiflazz API (Pulsa/PPOB)

Edit: `.env.production`

```env
DIGIFLAZZ_USERNAME="username_kamu"
DIGIFLAZZ_API_KEY="api_key_kamu"
DIGIFLAZZ_PRODUCTION_KEY="production_key_kamu"
DIGIFLAZZ_WEBHOOK_SECRET="webhook_secret_kamu"
```

### Cara Mengubah Midtrans (Payment Gateway)

Edit: `.env.production`

```env
MIDTRANS_SERVER_KEY="Mid-server-PRODUCTION_KEY"
MIDTRANS_CLIENT_KEY="Mid-client-PRODUCTION_KEY"
MIDTRANS_MERCHANT_ID="MERCHANT_ID"
```

---

## 🔄 Workflow Ganti Konfigurasi

### Skenario 1: Ganti Domain Production

1. Edit `cgi-bin/frontend/src/config/config.production.js`:
   ```javascript
   baseUrl: "https://domain-baru.com/app"
   ```

2. Edit `.env.production`:
   ```env
   ALLOWED_ORIGINS="https://domain-baru.com"
   ```

3. Build ulang frontend:
   ```bash
   cd cgi-bin/frontend
   npm run build
   ```

4. Upload ke server:
   - Upload isi `dist/` ke root domain
   - Copy `.env.production` jadi `.env` di server

### Skenario 2: Ganti Database Production

1. Edit `.env.production`:
   ```env
   DB_HOST="localhost"
   DB_USER="user_baru"
   DB_PASS="password_baru"
   DB_NAME="database_baru"
   ```

2. Upload `.env.production` ke server dan rename jadi `.env`

3. Test koneksi: `https://domain.com/app/test_db_connection.php`

### Skenario 3: Ganti API Key (Digiflazz/Midtrans)

1. Edit `.env.production`:
   ```env
   DIGIFLAZZ_USERNAME="username_baru"
   DIGIFLAZZ_API_KEY="api_key_baru"
   ```

2. Upload `.env.production` ke server dan rename jadi `.env`

3. Tidak perlu build ulang frontend

---

## ⚠️ PENTING!

### Jangan Edit File Ini:
- ❌ `cgi-bin/frontend/src/config/index.js` (auto-switch)
- ❌ `.env` (file aktif, akan di-overwrite)

### Edit File Ini:
- ✅ `config.development.js` (untuk development)
- ✅ `config.production.js` (untuk production)
- ✅ `.env.development` (untuk development)
- ✅ `.env.production` (untuk production)

### Setelah Edit:
- Frontend: Build ulang (`npm run build`)
- Backend: Upload `.env` baru ke server
- Clear cache browser (Ctrl+Shift+R)
- Clear cache Cloudflare (jika pakai)

---

## 📝 Checklist Ganti Konfigurasi

### Frontend
- [ ] Edit `config.production.js` (baseUrl)
- [ ] Build ulang (`npm run build`)
- [ ] Upload isi `dist/` ke server

### Backend
- [ ] Edit `.env.production` (database, CORS, API keys)
- [ ] Copy jadi `.env` di server
- [ ] Test koneksi database

### Verifikasi
- [ ] Buka domain di browser
- [ ] Cek console (F12) tidak ada error
- [ ] Test login
- [ ] Test fitur utama

---

## 🆘 Troubleshooting

### Frontend tidak connect ke backend
→ Cek `config.production.js` baseUrl sudah benar

### Error CORS
→ Cek `.env` di server, `ALLOWED_ORIGINS` harus sama dengan domain frontend

### Database connection failed
→ Cek `.env` di server, kredensial database sudah benar

### Perubahan tidak muncul
→ Clear cache browser (Ctrl+Shift+R) dan Cloudflare

---

**Selamat mengkonfigurasi! 🎉**
