# Quick Start Guide - A2U Bank Digital

Panduan cepat untuk menjalankan proyek dalam 5 menit.

## ⚡ Prerequisites

- ✅ PHP 7.4+ installed
- ✅ MySQL/MariaDB installed
- ✅ Node.js 18+ installed
- ✅ Composer installed
- ✅ Web server (Nginx/Apache/XAMPP) running

## 🚀 Quick Setup (5 Steps)

### 1️⃣ Database Setup (2 menit)

```bash
# Login ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE a2uj2723_au2;
EXIT;

# Import database
mysql -u root -p a2uj2723_au2 < a2uj2723_au2.sql
```

### 2️⃣ Backend Configuration (1 menit)

```bash
# Edit .env di root project
# Update database credentials:
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=a2uj2723_au2

# Install dependencies
cd app
composer install
cd ..
```

### 3️⃣ Frontend Setup (1 menit)

```bash
cd cgi-bin/frontend

# Install dependencies
npm install

# Update API URL di src/config/index.js
# Ubah baseUrl sesuai backend Anda
```

### 4️⃣ Run Development Server (30 detik)

```bash
# Masih di folder cgi-bin/frontend
npm run dev

# Frontend akan berjalan di: http://localhost:5173
```

### 5️⃣ Test Login (30 detik)

1. Buka browser: `http://localhost:5173/login`
2. Cek database untuk kredensial:
```sql
SELECT email, role_id FROM users WHERE status = 'ACTIVE' LIMIT 5;
```
3. Login dengan email dan password dari database

## ✅ Verification

### Test Backend
```bash
# Buka di browser atau curl:
curl http://localhost/app/utility_get_public_config.php

# Harusnya return JSON
```

### Test Frontend
```bash
# Buka browser:
http://localhost:5173

# Cek console (F12) untuk errors
```

## 🎯 Default URLs

| Service | URL | Description |
|---------|-----|-------------|
| Frontend Dev | http://localhost:5173 | React dev server |
| Backend API | http://localhost/app | PHP backend |
| Login Page | http://localhost:5173/login | Login page |
| Admin Dashboard | http://localhost:5173/admin/dashboard | Admin panel |

## 👤 Test Accounts

Cek database untuk user yang ada:
```sql
SELECT id, email, full_name, role_id, status FROM users WHERE status = 'ACTIVE';
```

**Role IDs:**
- `1-8`: Admin/Staff
- `9`: Customer/Nasabah

## 🐛 Common Issues

### Backend tidak bisa diakses
```bash
# Cek web server running
# XAMPP: Start Apache & MySQL
# Nginx: sudo systemctl status nginx
# Apache: sudo systemctl status apache2
```

### Database connection failed
```bash
# Cek kredensial di .env (di root project!)
# Pastikan MySQL running
# Test: mysql -u root -p
```

### Frontend tidak bisa fetch API
```bash
# Cek baseUrl di: cgi-bin/frontend/src/config/index.js
# Pastikan sesuai dengan URL backend Anda
# Default: http://localhost/app
```

### CORS Error
```bash
# Tambahkan origin frontend ke .env:
ALLOWED_ORIGINS=http://localhost:5173,http://localhost

# Restart web server
```

## 📚 Next Steps

Setelah berhasil running:

1. ✅ Baca [SETUP.md](SETUP.md) untuk konfigurasi lengkap
2. ✅ Baca [API_DOCUMENTATION.md](API_DOCUMENTATION.md) untuk API reference
3. ✅ Baca [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md) untuk memahami struktur
4. ✅ Customize branding (logo, nama bank)
5. ✅ Configure email SMTP
6. ✅ Setup payment gateway

## 🆘 Need Help?

1. Check browser console (F12)
2. Check backend error log: `app/error_log`
3. Check web server error log
4. Read full documentation: [SETUP.md](SETUP.md)

## 🎉 Success!

Jika semua berjalan lancar, Anda sekarang bisa:
- ✅ Login ke aplikasi
- ✅ Akses dashboard
- ✅ Test fitur-fitur banking
- ✅ Mulai development

Happy coding! 🚀
