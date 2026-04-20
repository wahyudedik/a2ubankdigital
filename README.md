# A2U Bank Digital

Platform perbankan digital berbasis web, dibangun dengan **Laravel 12 + Inertia.js + React**. Mendukung operasional nasabah (mobile banking) dan back-office staf bank dalam satu aplikasi monolith.

---

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | React 19, Inertia.js v3 |
| Styling | Tailwind CSS 3 |
| Database | MySQL 8 |
| Build Tool | Vite |
| Queue / Cache | Database driver |
| Push Notif | Web Push (VAPID) |

---

## Fitur Utama

### Nasabah (Customer)

| Fitur | Keterangan |
|-------|------------|
| **Dashboard** | Ringkasan saldo, transaksi terakhir, shortcut fitur |
| **Transfer Internal** | Transfer antar rekening sesama A2U Bank |
| **Transfer Eksternal** | Transfer ke bank lain via simulasi BI-FAST |
| **Transfer Terjadwal** | Jadwalkan transfer di tanggal tertentu |
| **Standing Instruction** | Transfer otomatis berulang (harian/mingguan/bulanan) |
| **Pembayaran Tagihan** | Bayar listrik, air, internet, BPJS, dll (via Digiflazz) |
| **Produk Digital** | Beli pulsa, paket data, voucher game |
| **Top-up E-Wallet** | Top-up GoPay, OVO, Dana, dll |
| **QR Payment** | Bayar via scan QR Code (QRIS) |
| **Deposito** | Buka & kelola deposito berjangka |
| **Investasi** | Produk investasi reksa dana |
| **Pinjaman** | Pengajuan & cicilan pinjaman |
| **Goal Savings** | Tabungan dengan target tujuan |
| **Kartu Debit/Kredit** | Request & manajemen kartu |
| **Penarikan Tunai** | Request penarikan ke rekening bank lain |
| **Program Loyalitas** | Poin reward dari transaksi |
| **Riwayat Transaksi** | Histori lengkap dengan filter & export |
| **Notifikasi** | Push notification & in-app notification |
| **Pesan Aman** | Secure messaging dengan enkripsi |
| **Tiket Dukungan** | Buat & pantau tiket CS |
| **Profil & Keamanan** | Ubah password, PIN, 2FA, aktivitas login |
| **Penutupan Rekening** | Pengajuan tutup rekening |

### Staf Bank (Back-Office)

| Role | Akses Utama |
|------|-------------|
| **Super Admin** | Akses penuh semua fitur, konfigurasi sistem |
| **Kepala Cabang** | Dashboard cabang, laporan, approval |
| **Kepala Unit** | Manajemen unit, monitoring staf |
| **Marketing** | Data nasabah, produk, kampanye |
| **Teller** | Transaksi tunai, top-up, verifikasi |
| **Customer Service** | Tiket, pesan langsung, manajemen akun nasabah |
| **Analis Kredit** | Review & approval pengajuan pinjaman |
| **Debt Collector** | Monitoring angsuran & penagihan |

**Fitur Admin:**
- Dashboard analitik & statistik real-time
- Manajemen nasabah & akun
- Approval kartu debit/kredit
- Manajemen produk (deposito, pinjaman, investasi)
- Pembalikan transaksi (reversal)
- Laporan keuangan & ekspor data
- Manajemen staf & role
- Konfigurasi sistem & pengumuman
- FAQ & konten informasi
- Log audit sistem

---

## Akun Testing

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@a2ubank.com | admin123 |
| Kepala Cabang | kacab@a2ubank.com | kacab123 |
| Kepala Unit | kaunit@a2ubank.com | kaunit123 |
| Marketing | marketing@a2ubank.com | marketing123 |
| Teller | teller@a2ubank.com | teller123 |
| Customer Service | cs@a2ubank.com | cs123 |
| Analis Kredit | analis@a2ubank.com | analis123 |
| Debt Collector | collector@a2ubank.com | collector123 |
| Nasabah 1 | customer1@example.com | customer123 |
| Nasabah 2 | customer2@example.com | customer123 |

> PIN nasabah default: `123456`

---

## Struktur Proyek

```
├── app/
│   ├── Console/Commands/       # Artisan commands (scheduler jobs)
│   ├── Http/Controllers/
│   │   ├── Admin/              # Logic back-office staf
│   │   ├── User/               # Logic fitur nasabah
│   │   └── Inertia/            # Controller render halaman Inertia
│   ├── Models/                 # Eloquent models
│   └── Services/               # Email, Notifikasi, Log service
├── resources/js/
│   ├── Pages/                  # Halaman React (Inertia)
│   ├── components/             # Komponen UI reusable
│   └── Layouts/                # Layout Customer & Admin
├── routes/
│   ├── web.php                 # Route halaman Inertia
│   └── ajax.php                # Route AJAX (form actions)
└── database/
    ├── migrations/             # Skema database
    └── seeders/                # Data awal
```

---

## Deploy Production ke VPS dengan aaPanel

### Prasyarat

- VPS Ubuntu 20.04 / 22.04 (minimal **2 GB RAM**, 1 vCPU)
- Domain sudah di-pointing ke IP VPS
- [aaPanel](https://www.aapanel.com) sudah terinstall

---

### Langkah 1 — Install Software di aaPanel

Login ke aaPanel → **App Store**, install:

- **Nginx** (versi terbaru)
- **MySQL 8.0**
- **PHP 8.2** (atau 8.3)
- **phpMyAdmin** (opsional, untuk manajemen DB via GUI)

Setelah PHP terinstall, masuk ke **App Store → PHP 8.2 → Settings → Install Extensions**, install ekstensi berikut:

- `fileinfo`
- `opcache`
- `exif`
- `redis` *(opsional, untuk cache lebih cepat)*

---

### Langkah 2 — Buat Database

1. aaPanel → **Database → Add Database**
2. Isi:
   - Database name: `a2ubank`
   - Username: `a2ubank`
   - Password: *(buat password kuat, simpan baik-baik)*
3. Klik **Submit**

---

### Langkah 3 — Buat Website

1. aaPanel → **Website → Add Site**
2. Isi:
   - Domain: `yourdomain.com`
   - Root Directory: `/www/wwwroot/yourdomain.com`
   - PHP Version: PHP 8.2
   - Database: Biarkan kosong (sudah dibuat manual)
3. Klik **Submit**

---

### Langkah 4 — Upload Proyek

**Opsi A: Via Git (Direkomendasikan)**

SSH ke VPS lalu clone repo:

```bash
cd /www/wwwroot/yourdomain.com
rm -rf .* * 2>/dev/null || true
git clone https://github.com/username/repo.git .
```

**Opsi B: Via ZIP Upload**

Compress proyek di lokal (tanpa `node_modules`, `vendor`, `.git`):

```powershell
# Windows PowerShell
Compress-Archive -Path app,bootstrap,config,database,public,resources,routes,storage,artisan,composer.json,composer.lock,package.json,package-lock.json,vite.config.js,tailwind.config.js,postcss.config.js,.env.example -DestinationPath deploy.zip
```

Upload `deploy.zip` via **aaPanel → File Manager** ke `/www/wwwroot/yourdomain.com/`, lalu extract di sana.

---

### Langkah 5 — Install Node.js

Node.js diperlukan untuk build aset frontend. Jalankan di SSH:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
node -v   # pastikan v20+
```

---

### Langkah 6 — Install Dependencies & Build

```bash
cd /www/wwwroot/yourdomain.com

# PHP dependencies
composer install --no-dev --optimize-autoloader

# JS dependencies & build frontend
npm install
npm run build
```

---

### Langkah 7 — Konfigurasi Environment

```bash
cd /www/wwwroot/yourdomain.com

cp .env.example .env
nano .env
```

Sesuaikan nilai berikut:

```env
APP_NAME="A2U Bank Digital"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=a2ubank
DB_USERNAME=a2ubank
DB_PASSWORD=password_database_kamu

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=emailkamu@gmail.com
MAIL_PASSWORD=app_password_gmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="A2U Bank Digital"
```

> Untuk Gmail, gunakan **App Password** (bukan password akun biasa). Aktifkan di Google Account → Security → 2-Step Verification → App Passwords.

Lalu generate key, migrasi, dan buat storage link:

```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan vapid:generate   # generate VAPID keys untuk push notification
```

---

### Langkah 8 — Set Permission

```bash
cd /www/wwwroot/yourdomain.com

chown -R www:www .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

---

### Langkah 9 — Konfigurasi Nginx

aaPanel → **Website → yourdomain.com → Settings → Config**

Ganti seluruh isi config Nginx dengan:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    root /www/wwwroot/yourdomain.com/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20M;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Vite build assets
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
        fastcgi_read_timeout 300;
    }

    # Block akses file sensitif
    location ~ /\. {
        deny all;
    }
    location ~ \.(env|log)$ {
        deny all;
    }

    access_log /www/wwwlogs/yourdomain.com.log;
    error_log /www/wwwlogs/yourdomain.com.error.log;
}
```

Klik **Save**, lalu **Reload Nginx**.

---

### Langkah 10 — Pasang SSL (HTTPS)

1. aaPanel → **Website → yourdomain.com → SSL**
2. Pilih tab **Let's Encrypt**
3. Centang domain (dan `www.` jika ada)
4. Klik **Apply**
5. Aktifkan toggle **Force HTTPS**

Setelah SSL aktif, tambahkan redirect di blok `server` port 80 pada Nginx config:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$host$request_uri;
}
```

---

### Langkah 11 — Optimasi Production

```bash
cd /www/wwwroot/yourdomain.com

php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize
```

---

### Langkah 12 — Setup Cron Job (Scheduler)

aaPanel → **Cron → Add Cron Job**:

| Field | Value |
|-------|-------|
| Type | Shell Script |
| Name | Laravel Scheduler |
| Period | Every 1 Minute |
| Script | `cd /www/wwwroot/yourdomain.com && php artisan schedule:run >> /dev/null 2>&1` |

Scheduler menjalankan job otomatis:

| Job | Jadwal | Fungsi |
|-----|--------|--------|
| `transfers:process-scheduled` | Setiap hari 00:05 | Proses transfer terjadwal |
| `transfers:process-standing` | Setiap hari 00:10 | Proses standing instruction |
| `loans:check-overdue` | Setiap hari 01:00 | Cek angsuran jatuh tempo |

---

### Langkah 13 — Setup Queue Worker

Queue digunakan untuk pengiriman email dan push notification secara async.

**Opsi A: Supervisor (Direkomendasikan untuk production)**

```bash
sudo apt install -y supervisor

sudo nano /etc/supervisor/conf.d/a2ubank-worker.conf
```

Isi file config:

```ini
[program:a2ubank-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /www/wwwroot/yourdomain.com/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=2
redirect_stderr=true
stdout_logfile=/www/wwwroot/yourdomain.com/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start a2ubank-worker:*

# Cek status
sudo supervisorctl status
```

**Opsi B: Cron (Alternatif sederhana)**

Tambahkan cron job kedua di aaPanel:

| Field | Value |
|-------|-------|
| Name | Laravel Queue Worker |
| Period | Every 1 Minute |
| Script | `cd /www/wwwroot/yourdomain.com && php artisan queue:work database --stop-when-empty --max-time=55 >> /dev/null 2>&1` |

---

### Langkah 14 — Verifikasi Deployment

Cek semua komponen berjalan normal:

```bash
cd /www/wwwroot/yourdomain.com

# Cek status aplikasi
php artisan about

# Cek koneksi database
php artisan db:show

# Cek queue
php artisan queue:monitor database

# Cek log error
tail -f storage/logs/laravel.log
```

Buka browser, akses `https://yourdomain.com` — pastikan halaman login muncul.

---

## Update Proyek

Setiap kali ada update kode:

```bash
cd /www/wwwroot/yourdomain.com

# Pull kode terbaru
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Jalankan migrasi baru (jika ada)
php artisan migrate --force

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue worker
sudo supervisorctl restart a2ubank-worker:*
```

---

## Troubleshooting

**500 Internal Server Error**
```bash
tail -f storage/logs/laravel.log
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**Halaman blank / aset tidak muncul**
```bash
# Pastikan build sudah dijalankan
npm run build

# Pastikan APP_URL benar di .env dan Nginx root mengarah ke /public
php artisan config:clear
```

**Login gagal / session error**
```bash
php artisan migrate          # pastikan tabel sessions ada
php artisan cache:clear
php artisan config:clear
```

**Upload file gagal**
```bash
php artisan storage:link
chmod -R 775 storage/app/public
```

**Push notification tidak berjalan**
```bash
# Generate ulang VAPID keys
php artisan vapid:generate

# Pastikan VAPID_PUBLIC_KEY dan VAPID_PRIVATE_KEY sudah diisi di .env
# Jalankan ulang config cache
php artisan config:cache
```

**Queue tidak memproses job**
```bash
sudo supervisorctl status a2ubank-worker:*
sudo supervisorctl restart a2ubank-worker:*
tail -f storage/logs/worker.log
```

---

## Perintah Artisan Penting

```bash
# Generate VAPID keys untuk push notification
php artisan vapid:generate

# Update gambar QRIS
php artisan qris:update

# Jalankan scheduler manual (testing)
php artisan schedule:run

# Proses queue manual
php artisan queue:work database --once

# Clear semua cache
php artisan optimize:clear
```

---

## License

Proprietary — All rights reserved.
