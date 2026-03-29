# VAPID Keys Setup Guide

## Apa itu VAPID Keys?

VAPID (Voluntary Application Server Identification) adalah standar untuk mengidentifikasi application server saat mengirim push notifications. VAPID keys terdiri dari:
- **Public Key**: Dibagikan ke browser untuk subscribe push notifications
- **Private Key**: Disimpan di server untuk menandatangani push notifications

## Generate VAPID Keys Baru

### Cara 1: Menggunakan Command Artisan (Recommended)

```bash
php artisan vapid:generate
```

Output akan menampilkan:
```
✓ VAPID Keys Generated Successfully!

Add these to your .env file:

VITE_VAPID_PUBLIC_KEY=BAa5p4tdGbiu03u1qNzTrEWewtf8CD3iWMzyvuSLF_j9KvdBAWl3dFMALpPY2SEWR44IfOXoc3UuaHAee1Nsi0Q
VAPID_PUBLIC_KEY=BAa5p4tdGbiu03u1qNzTrEWewtf8CD3iWMzyvuSLF_j9KvdBAWl3dFMALpPY2SEWR44IfOXoc3UuaHAee1Nsi0Q
VAPID_PRIVATE_KEY=VTXdyl5kF-lREOOWd2orvMF3Hfn2isen8VIhqcOUuAE

Then run: php artisan config:cache
```

### Cara 2: Menggunakan Web-Push Library

Jika command di atas tidak bekerja, install web-push library:

```bash
composer require web-push-notification/web-push
```

Kemudian buat script untuk generate keys:

```php
<?php
require 'vendor/autoload.php';

use WebPush\WebPush;

$vapidKeys = WebPush::generateVapidKeys();

echo "VITE_VAPID_PUBLIC_KEY=" . $vapidKeys['publicKey'] . "\n";
echo "VAPID_PUBLIC_KEY=" . $vapidKeys['publicKey'] . "\n";
echo "VAPID_PRIVATE_KEY=" . $vapidKeys['privateKey'] . "\n";
```

## Update .env File

Setelah generate keys, update file `.env`:

```env
# Push Notification (VAPID Keys)
VITE_VAPID_PUBLIC_KEY=<your-public-key>
VAPID_PUBLIC_KEY=<your-public-key>
VAPID_PRIVATE_KEY=<your-private-key>
```

## Refresh Configuration

Setelah update `.env`, jalankan:

```bash
php artisan config:cache
```

Atau jika menggunakan Docker:

```bash
docker-compose exec app php artisan config:cache
```

## Verify VAPID Keys

Untuk memverifikasi VAPID keys sudah benar:

1. Buka browser console di halaman profile
2. Cek apakah ada error tentang VAPID keys
3. Coba subscribe ke push notifications
4. Jika berhasil, akan muncul notifikasi "Notifikasi Push Aktif"

## Troubleshooting

### Error: "Kunci notifikasi tidak terkonfigurasi"
- Pastikan `VITE_VAPID_PUBLIC_KEY` sudah di `.env`
- Jalankan `php artisan config:cache`
- Refresh browser (Ctrl+Shift+R untuk hard refresh)

### Error: "Gagal memproses langganan"
- Pastikan `VAPID_PUBLIC_KEY` dan `VAPID_PRIVATE_KEY` sudah di `.env`
- Pastikan keys valid (tidak ada typo)
- Cek database `push_subscriptions` table

### Push Notifications Tidak Diterima
- Pastikan user sudah subscribe ke push notifications
- Cek browser notification settings
- Cek service worker di browser DevTools
- Cek database untuk push subscriptions

## Security Notes

⚠️ **PENTING:**
- Jangan share VAPID private key ke siapapun
- Jangan commit `.env` file ke git
- Rotate VAPID keys secara berkala (setiap 6-12 bulan)
- Gunakan HTTPS di production (push notifications hanya bekerja di HTTPS)

## Production Deployment

Saat deploy ke VPS production:

1. Generate VAPID keys baru:
   ```bash
   php artisan vapid:generate
   ```

2. Update `.env` di production server dengan keys baru

3. Jalankan config cache:
   ```bash
   php artisan config:cache
   ```

4. Restart application:
   ```bash
   php artisan queue:restart
   ```

## Monitoring Push Subscriptions

Untuk melihat berapa banyak users yang subscribe:

```bash
php artisan tinker
```

Kemudian:

```php
DB::table('push_subscriptions')->count();
DB::table('push_subscriptions')->where('user_id', 1)->get();
```

## Cleanup Old Subscriptions

Untuk menghapus subscriptions yang sudah tidak valid:

```bash
php artisan tinker
```

Kemudian:

```php
// Hapus subscriptions yang lebih dari 30 hari lalu tidak diupdate
DB::table('push_subscriptions')
    ->where('updated_at', '<', now()->subDays(30))
    ->delete();
```

## References

- [Web Push Protocol](https://datatracker.ietf.org/doc/html/draft-thomson-webpush-protocol)
- [VAPID Specification](https://datatracker.ietf.org/doc/html/draft-thomson-webpush-vapid)
- [MDN Web Push API](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)
