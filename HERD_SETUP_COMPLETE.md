# ✅ Setup Complete - Laravel Herd

## 🎉 Proyek Sudah Siap Digunakan!

### 📊 Status Koneksi

✅ **Backend (PHP)**
- URL: `http://a2ubankdigital.my.id.test/app/`
- Database: `czsczczx` (Connected)
- MySQL: 8.4.2
- Tables: 43 tables
- Users: 6 active users

✅ **Frontend (React)**
- Dev Server: `http://localhost:5174/`
- API Config: `http://a2ubankdigital.my.id.test/app/`
- Status: Running

✅ **Database**
- Name: `czsczczx`
- Host: `localhost`
- User: `root`
- Status: Connected

---

## 🔐 Test Accounts

Gunakan salah satu akun ini untuk login:

### Admin Accounts (Role 1)
| Email | Name | Role |
|-------|------|------|
| admin@taskora.id | Super Administrator | Admin |
| aauasiarecords@gmail.com | Super Administrator | Admin |

### Staff Account (Role 8)
| Email | Name | Role |
|-------|------|------|
| novitaanisa635@gmail.com | Novita | Staff |

### Customer Accounts (Role 9)
| Email | Name | Role |
|-------|------|------|
| sintalaela960@gmail.com | akun | Customer |
| andrealditam@gmail.com | ANDRE ALDI UTAMAA | Customer |

**Note:** Password ada di database. Jika tidak tahu, gunakan fitur "Forgot Password" atau reset via database.

---

## 🚀 Cara Menggunakan

### 1. Akses Aplikasi

**Frontend (Development):**
```
http://localhost:5174
```

**Login Page:**
```
http://localhost:5174/login
```

**Admin Dashboard:**
```
http://localhost:5174/admin/dashboard
```

**Customer Dashboard:**
```
http://localhost:5174/dashboard
```

### 2. Test Login

1. Buka: `http://localhost:5174/login`
2. Masukkan email dari tabel di atas
3. Masukkan password (cek database atau reset)
4. Klik "Masuk"

**Redirect otomatis:**
- Admin/Staff → `/admin/dashboard`
- Customer → `/dashboard`

### 3. Reset Password (Jika Lupa)

**Via Database (phpMyAdmin):**
```sql
-- Update password untuk user tertentu
UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@taskora.id';
-- Password: password
```

**Via Forgot Password:**
1. Klik "Lupa password?" di halaman login
2. Masukkan email
3. Cek email untuk link reset (perlu SMTP configured)

---

## 🛠️ Development Workflow

### Start Development

```bash
# Frontend sudah running di terminal
# Access: http://localhost:5174

# Backend sudah running via Herd
# Access: http://a2ubankdigital.my.id.test/app/
```

### Stop Development

```bash
# Stop frontend: Ctrl+C di terminal
# Backend tetap running via Herd (tidak perlu stop)
```

### Restart Frontend

```bash
cd cgi-bin/frontend
npm run dev
```

### Build for Production

```bash
cd cgi-bin/frontend
npm run build

# Output akan di-copy ke root:
# - index.html
# - assets/
# - manifest.webmanifest
```

---

## 📁 URLs Penting

| Service | URL | Description |
|---------|-----|-------------|
| Frontend Dev | http://localhost:5174 | React dev server |
| Frontend Prod | http://a2ubankdigital.my.id.test | Production build |
| Backend API | http://a2ubankdigital.my.id.test/app | PHP backend |
| Test DB | http://a2ubankdigital.my.id.test/app/test_db_connection.php | Database test |
| phpMyAdmin | http://localhost/phpmyadmin | Database management |

---

## 🔧 Konfigurasi Files

### Backend (.env di root)
```env
DB_HOST="localhost"
DB_USER="root"
DB_PASS=""
DB_NAME="czsczczx"
```

### Frontend (cgi-bin/frontend/src/config/index.js)
```javascript
api: {
  baseUrl: "http://a2ubankdigital.my.id.test/app"
}
```

---

## 🧪 Testing

### Test Backend Connection
```bash
curl http://a2ubankdigital.my.id.test/app/test_db_connection.php
```

### Test Login API
```bash
curl -X POST http://a2ubankdigital.my.id.test/app/auth_login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@taskora.id","password":"your_password"}'
```

### Test Frontend
1. Open browser: http://localhost:5174
2. Check console (F12) for errors
3. Try login

---

## 📊 Database Info

### Access Database

**Via phpMyAdmin:**
```
http://localhost/phpmyadmin
Database: czsczczx
```

**Via Herd:**
```bash
herd db czsczczx
```

### Useful Queries

**Get all active users:**
```sql
SELECT id, email, full_name, role_id, status 
FROM users 
WHERE status = 'ACTIVE';
```

**Get user with password:**
```sql
SELECT id, email, password_hash 
FROM users 
WHERE email = 'admin@taskora.id';
```

**Reset password:**
```sql
UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@taskora.id';
-- Password: password
```

---

## 🐛 Troubleshooting

### Frontend tidak bisa fetch API

**Cek:**
1. Backend URL di `cgi-bin/frontend/src/config/index.js`
2. CORS di `.env`: `ALLOWED_ORIGINS` include `http://localhost:5174`
3. Browser console untuk error details

**Fix:**
```env
# Update .env
ALLOWED_ORIGINS="http://localhost:5174,http://localhost:5173,http://a2ubankdigital.my.id.test"
```

### Login gagal

**Cek:**
1. Email ada di database
2. Status user = 'ACTIVE'
3. Password benar (atau reset)
4. Browser console untuk error

### Database connection failed

**Cek:**
1. MySQL running via Herd
2. Database `czsczczx` exists
3. Credentials di `.env` benar

**Test:**
```bash
herd db czsczczx
```

---

## 🎯 Next Steps

Sekarang Anda bisa:

1. ✅ **Login ke aplikasi**
   - Admin: http://localhost:5174/admin/dashboard
   - Customer: http://localhost:5174/dashboard

2. ✅ **Test fitur-fitur:**
   - Transfer
   - Loan application
   - Deposit
   - Card request
   - Reports

3. ✅ **Customize:**
   - Branding (logo, colors)
   - Email templates
   - Payment gateway
   - Digital products

4. ✅ **Deploy:**
   - Build frontend: `npm run build`
   - Upload to production server
   - Follow DEPLOYMENT.md

---

## 📚 Documentation

- **README.md** - Project overview
- **QUICKSTART.md** - 5-minute setup
- **SETUP.md** - Detailed setup guide
- **PROJECT_STRUCTURE.md** - Folder structure
- **API_DOCUMENTATION.md** - API reference
- **DEPLOYMENT.md** - Production deployment
- **HERD_SETUP_COMPLETE.md** - This file

---

## 🎉 Success!

Proyek Anda sudah siap digunakan!

**Happy Coding! 🚀**
