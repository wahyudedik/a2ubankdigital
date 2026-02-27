# Setup Guide - A2U Bank Digital

## 📋 Prerequisites

Pastikan sistem Anda sudah terinstall:
- PHP 7.4 atau lebih tinggi
- MySQL/MariaDB 5.7+
- Node.js 18+ dan npm
- Composer
- Web Server (Nginx/Apache)

## 🔧 Installation Steps

### 1. Clone/Download Project

```bash
git clone <repository-url>
cd a2ubankdigital.my.id
```

### 2. Database Setup

```bash
# Login ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE a2uj2723_au2 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

# Import database
mysql -u root -p a2uj2723_au2 < a2uj2723_au2.sql

# Atau gunakan phpMyAdmin untuk import
```

### 3. Backend Configuration

Backend berada di folder `app/` dan menggunakan `.env` di root project.

```bash
# Copy environment file (di root project)
cp .env.example .env

# Edit .env dengan kredensial Anda
nano .env  # atau gunakan text editor lain
```

Update konfigurasi di `.env`:
```env
DB_HOST=localhost
DB_USER=your_user
DB_PASS=your_password
DB_NAME=a2uj2723_au2

JWT_SECRET=generate_random_32_char_string
ALLOWED_ORIGINS=http://localhost:5173,http://yourdomain.com
```

```bash
# Install PHP dependencies
cd app
composer install
cd ..
```

**Catatan:** File `app/config.php` akan membaca `.env` dari root project (bukan dari folder app).

### 4. Frontend Configuration

```bash
cd cgi-bin/frontend

# Install dependencies
npm install

# Update API endpoint
# Edit: src/config/index.js
```

Update `src/config/index.js`:
```javascript
api: {
  baseUrl: "http://localhost/app"  // Sesuaikan dengan URL backend Anda
}
```

### 5. Web Server Configuration

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name a2ubankdigital.my.id.test;
    root /path/to/a2ubankdigital.my.id;
    index index.html index.php;

    # Frontend (React)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API
    location /app {
        try_files $uri $uri/ /app/index.php?$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    # Uploads
    location /uploads {
        alias /path/to/a2ubankdigital.my.id/uploads;
    }
}
```

#### Apache Configuration (.htaccess sudah ada)

Pastikan mod_rewrite enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 6. File Permissions

```bash
# Set permissions untuk upload folders
chmod -R 755 uploads/
chmod -R 755 cache/
chmod -R 755 app/cache/

# Set ownership (sesuaikan dengan user web server)
chown -R www-data:www-data uploads/
chown -R www-data:www-data cache/
```

### 7. Development Mode

```bash
# Terminal 1: Run frontend dev server
cd cgi-bin/frontend
npm run dev
# Access: http://localhost:5173

# Terminal 2: Backend sudah berjalan via web server
# Access: http://localhost/app
```

### 8. Production Build

```bash
cd cgi-bin/frontend

# Build frontend
npm run build

# Files akan di-copy ke root project:
# - index.html
# - assets/
# - manifest.webmanifest
# - sw.js
```

## ✅ Verification

### Test Backend Connection

1. Buat file test di `app/test.php`:
```php
<?php
require_once 'config.php';
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Backend connected',
    'db' => $pdo ? 'connected' : 'failed'
]);
```

2. Akses: `http://localhost/app/test.php`

### Test Frontend

1. Akses: `http://localhost:5173` (dev) atau `http://localhost` (production)
2. Coba login dengan kredensial dari database
3. Cek browser console (F12) untuk errors

## 🔐 Default Credentials

Cek database untuk user yang sudah ada:
```sql
SELECT id, email, role_id FROM users WHERE status = 'ACTIVE' LIMIT 5;
```

Role IDs:
- 1: Super Admin
- 2-8: Staff roles
- 9: Customer/Nasabah

## 🐛 Troubleshooting

### Backend tidak bisa diakses
- Cek web server running: `sudo systemctl status nginx`
- Cek PHP-FPM running: `sudo systemctl status php8.1-fpm`
- Cek error log: `tail -f /var/log/nginx/error.log`

### Database connection failed
- Cek kredensial di `.env`
- Cek MySQL running: `sudo systemctl status mysql`
- Test koneksi: `mysql -u user -p database_name`

### CORS errors
- Tambahkan origin frontend ke `ALLOWED_ORIGINS` di `.env`
- Restart web server setelah update `.env`

### Frontend tidak bisa fetch API
- Cek `baseUrl` di `cgi-bin/frontend/src/config/index.js`
- Cek browser console untuk error details
- Cek network tab untuk request/response

## 📞 Support

Jika ada masalah, cek:
1. Browser console (F12)
2. Network tab untuk API calls
3. Backend error log: `app/error_log`
4. Web server error log

## 🚀 Next Steps

Setelah setup berhasil:
1. Update branding (logo, nama bank)
2. Configure email SMTP
3. Setup payment gateway (Midtrans)
4. Configure digital products (Digiflazz)
5. Test semua fitur
6. Deploy to production server
