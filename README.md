# A2U Bank Digital

Platform perbankan digital monolith dibangun dengan Laravel 12 + Inertia.js + React.

## Tech Stack

- Laravel 12 (PHP 8.2+)
- Inertia.js v3
- React 19
- Tailwind CSS 3
- MySQL 8
- Vite

## Struktur Proyek

```
├── app/Http/Controllers/Inertia/   # Controller halaman Inertia
├── app/Http/Controllers/Admin/     # Controller logic admin
├── app/Http/Controllers/User/      # Controller logic user
├── resources/js/Pages/             # 54 halaman React
├── resources/js/components/        # Komponen React
├── resources/js/Layouts/           # Layout (Customer & Admin)
├── routes/web.php                  # Route halaman Inertia + form actions
├── routes/ajax.php                 # Route AJAX (interactive flows)
└── routes/api.php                  # Kosong (tidak dipakai)
```

## Akun Testing

| ID | Role | Email | Password |
|----|------|-------|----------|
| 1 | Super Admin | admin@a2ubank.com | admin123 |
| 2 | Kepala Cabang | kacab@a2ubank.com | kacab123 |
| 3 | Kepala Unit | kaunit@a2ubank.com | kaunit123 |
| 4 | Marketing | marketing@a2ubank.com | marketing123 |
| 5 | Teller | teller@a2ubank.com | teller123 |
| 6 | Customer Service | cs@a2ubank.com | cs123 |
| 7 | Analis Kredit | analis@a2ubank.com | analis123 |
| 8 | Debt Collector | collector@a2ubank.com | collector123 |
| 9 | Nasabah | customer1@example.com | customer123 |

---

## Deploy ke VPS dengan aaPanel

### Prasyarat

- VPS dengan Ubuntu 20.04/22.04 (minimal 1GB RAM)
- Domain sudah pointing ke IP VPS
- aaPanel sudah terinstall

---

### Langkah 1: Install Software di aaPanel

Login ke aaPanel, masuk ke App Store, install:

- Nginx (versi terbaru)
- MySQL 8.0
- PHP 8.2 (atau 8.3)
- phpMyAdmin

Setelah PHP terinstall, masuk ke **App Store > PHP 8.2 > Settings > Install Extensions**, install:

- fileinfo
- opcache
- redis (opsional)
- exif

---

### Langkah 2: Buat Database

1. Di aaPanel, klik **Database > Add Database**
2. Isi:
   - Database name: `a2ubank`
   - Username: `a2ubank`
   - Password: (buat password kuat, catat)
3. Klik Submit

---

### Langkah 3: Buat Website

1. Di aaPanel, klik **Website > Add Site**
2. Isi:
   - Domain: `a2ubankdigital.my.id` (domain kamu)
   - Root Directory: `/www/wwwroot/a2ubankdigital.my.id`
   - PHP Version: PHP 8.2
   - Database: Tidak perlu (sudah dibuat manual)
3. Klik Submit

---

### Langkah 4: Upload Proyek

**Opsi A: Via Git (Recommended)**

SSH ke VPS:

```bash
cd /www/wwwroot/a2ubankdigital.my.id
rm -rf .* * 2>/dev/null
git clone <repository-url> .
```

**Opsi B: Via ZIP Upload**

1. Di lokal, compress proyek (tanpa `node_modules`, `vendor`, `.git`):
```bash
# Di Windows PowerShell
Compress-Archive -Path app,bootstrap,config,database,public,resources,routes,storage,.env.example,artisan,composer.json,composer.lock,package.json,package-lock.json,vite.config.js,tailwind.config.js,postcss.config.js -DestinationPath deploy.zip
```
2. Upload `deploy.zip` via aaPanel File Manager ke `/www/wwwroot/a2ubankdigital.my.id/`
3. Extract di sana

---

### Langkah 5: Install Dependencies

SSH ke VPS:

```bash
cd /www/wwwroot/a2ubankdigital.my.id

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js (jika belum ada)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install JS dependencies & build
npm install
npm run build
```

---

### Langkah 6: Konfigurasi Environment

```bash
cd /www/wwwroot/a2ubankdigital.my.id

# Buat .env dari template
cp .env.example .env

# Edit .env
nano .env
```

Ubah nilai-nilai ini di `.env`:

```env
APP_NAME="A2U Bank Digital"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://a2ubankdigital.my.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=a2ubank
DB_USERNAME=a2ubank
DB_PASSWORD=password_yang_kamu_buat

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=emailkamu@gmail.com
MAIL_PASSWORD=app_password_gmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@a2ubankdigital.my.id
MAIL_FROM_NAME="A2U Bank Digital"
```

Lalu generate key dan jalankan migrasi:

```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

---

### Langkah 7: Set Permission

```bash
cd /www/wwwroot/a2ubankdigital.my.id

# Set ownership
chown -R www:www .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

---

### Langkah 8: Konfigurasi Nginx

Di aaPanel, klik **Website > a2ubankdigital.my.id > Settings > Config**

Ganti seluruh isi config Nginx dengan:

```nginx
server {
    listen 80;
    server_name a2ubankdigital.my.id;
    
    # Redirect ke HTTPS (aktifkan setelah SSL dipasang)
    # return 301 https://$host$request_uri;
    
    root /www/wwwroot/a2ubankdigital.my.id/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20M;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Build assets (Vite)
    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-82.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
    }

    # Block hidden files
    location ~ /\. {
        deny all;
    }

    # Block sensitive files
    location ~ \.(env|log|md)$ {
        deny all;
    }

    access_log /www/wwwlogs/a2ubankdigital.my.id.log;
    error_log /www/wwwlogs/a2ubankdigital.my.id.error.log;
}
```

Klik Save, lalu Restart Nginx.

---

### Langkah 9: Pasang SSL (HTTPS)

Di aaPanel:

1. Klik **Website > a2ubankdigital.my.id > SSL**
2. Pilih **Let's Encrypt**
3. Centang domain
4. Klik Apply
5. Aktifkan **Force HTTPS**

Setelah SSL aktif, uncomment redirect di Nginx config:

```nginx
return 301 https://$host$request_uri;
```

---

### Langkah 10: Optimasi Production

SSH ke VPS:

```bash
cd /www/wwwroot/a2ubankdigital.my.id

# Cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

---

### Langkah 11: Setup Cron Job & Queue Worker

**Cron Job (Scheduler):**

Di aaPanel, klik **Cron > Add Cron Job**:

- Type: Shell Script
- Name: Laravel Scheduler
- Period: Every 1 minute
- Script:
```bash
cd /www/wwwroot/a2ubankdigital.my.id && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler menjalankan:
- `transfers:process-scheduled` - Proses transfer terjadwal (setiap hari 00:05)
- `transfers:process-standing` - Proses standing instruction (setiap hari 00:10)
- `loans:check-overdue` - Cek angsuran jatuh tempo (setiap hari 01:00)

**Queue Worker (untuk email & notifikasi):**

Di aaPanel, klik **Cron > Add Cron Job**:

- Type: Shell Script
- Name: Laravel Queue Worker
- Period: Every 1 minute
- Script:
```bash
cd /www/wwwroot/a2ubankdigital.my.id && php artisan queue:work database --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

Atau untuk production yang lebih stabil, install Supervisor:
```bash
sudo apt install supervisor

# Buat config
sudo nano /etc/supervisor/conf.d/a2ubank-worker.conf
```

Isi:
```ini
[program:a2ubank-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/a2ubankdigital.my.id/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/a2ubankdigital.my.id/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start a2ubank-worker:*
```

---

### Troubleshooting

**500 Internal Server Error:**
```bash
# Cek log
tail -f storage/logs/laravel.log

# Fix permission
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**Halaman blank / asset tidak muncul:**
```bash
# Pastikan build sudah jalan
npm run build

# Pastikan APP_URL benar di .env
# Pastikan Nginx root mengarah ke /public
```

**Login tidak bisa / session error:**
```bash
# Pastikan sessions table ada
php artisan migrate

# Clear cache
php artisan cache:clear
php artisan config:clear
```

**Upload file gagal:**
```bash
# Pastikan storage link ada
php artisan storage:link

# Pastikan permission benar
chmod -R 775 storage/app/public
```

---

### Update Proyek

Setiap kali ada update kode:

```bash
cd /www/wwwroot/a2ubankdigital.my.id

# Pull kode terbaru
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Jalankan migrasi
php artisan migrate --force

# Clear & rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart PHP
sudo systemctl restart php8.2-fpm
```

## License

Proprietary - All rights reserved
