# Semua Jenis Notifikasi di Sistem A2U Bank Digital

## 1. Notifikasi Kartu (Card)
- **Permintaan Kartu Baru** - Ketika user mengajukan kartu baru
  - Notifikasi ke: Admin staff (role 1, 2, 3)
  - File: `app/Http/Controllers/User/CardController.php`
  
- **Permintaan Kartu Disetujui** - Ketika admin menyetujui permintaan kartu
  - Notifikasi ke: User
  - File: `app/Http/Controllers/Admin/CardRequestController.php`
  
- **Permintaan Kartu Ditolak** - Ketika admin menolak permintaan kartu
  - Notifikasi ke: User
  - File: `app/Http/Controllers/Admin/CardRequestController.php`

## 2. Notifikasi Transfer
- **Transfer Internal Berhasil** - Ketika transfer internal selesai
  - Notifikasi ke: User (pengirim)
  - File: `app/Http/Controllers/User/TransactionController.php`
  
- **Transfer Eksternal Berhasil** - Ketika transfer eksternal selesai
  - Notifikasi ke: User (pengirim)
  - File: `app/Http/Controllers/User/ExternalTransferController.php`
  
- **Transfer Terjadwal Dibuat** - Ketika user membuat transfer terjadwal
  - Notifikasi ke: User
  - File: `app/Http/Controllers/User/ScheduledTransferController.php`
  
- **Transfer Terjadwal Dibatalkan** - Ketika transfer terjadwal dibatalkan
  - Notifikasi ke: User
  - File: `app/Http/Controllers/User/ScheduledTransferController.php`
  
- **Transfer Terjadwal Dieksekusi** - Ketika transfer terjadwal dijalankan
  - Notifikasi ke: User
  - File: `app/Console/Commands/ProcessScheduledTransfers.php`

## 3. Notifikasi Penarikan (Withdrawal)
- **Permintaan Penarikan Baru** - Ketika user mengajukan penarikan
  - Notifikasi ke: Admin staff (role 1, 2, 3, 5)
  - File: `app/Http/Controllers/User/WithdrawalController.php`
  
- **Withdrawal Approved** - Ketika admin menyetujui penarikan
  - Notifikasi ke: User
  - File: `app/Http/Controllers/Admin/WithdrawalRequestController.php`
  
- **Withdrawal Rejected** - Ketika admin menolak penarikan
  - Notifikasi ke: User
  - File: `app/Http/Controllers/Admin/WithdrawalRequestController.php`
  
- **Withdrawal Completed** - Ketika penarikan selesai diproses
  - Notifikasi ke: User
  - File: `app/Http/Controllers/Admin/WithdrawalRequestController.php`

## 4. Notifikasi Top-up
- **Top-up E-Wallet Berhasil** - Ketika top-up e-wallet selesai
  - Notifikasi ke: User
  - File: `app/Http/Controllers/User/EWalletController.php`

## 5. Notifikasi Pembayaran
- **Pembayaran Berhasil** - Ketika pembayaran tagihan selesai
  - Notifikasi ke: User
  - File: `app/Http/Controllers/User/BillPaymentController.php`
  
- **Pembayaran QR Berhasil** - Ketika pembayaran QR selesai (pengirim)
  - Notifikasi ke: User (pengirim)
  - File: `app/Http/Controllers/User/QrPaymentController.php`
  
- **Pembayaran QR Diterima** - Ketika pembayaran QR diterima (penerima)
  - Notifikasi ke: User (penerima)
  - File: `app/Http/Controllers/User/QrPaymentController.php`

## 6. Notifikasi Pinjaman (Loan)
- **Angsuran Jatuh Tempo** - Ketika ada angsuran yang jatuh tempo
  - Notifikasi ke: User
  - File: `app/Console/Commands/CheckOverdueInstallments.php`
  
- **Pembayaran Angsuran Diterima** - Ketika pembayaran angsuran diterima
  - Notifikasi ke: User
  - File: `app/Http/Controllers/Admin/TellerController.php`

## 7. Notifikasi Tabungan
- **Tabungan Rencana Berhasil Dibuat** - Ketika user membuat goal savings
  - Notifikasi ke: User
  - File: `app/Http/Controllers/User/GoalSavingsController.php`
  
- **Target Tabungan Rencana Tercapai** - Ketika target tabungan tercapai
  - Notifikasi ke: User
  - File: `app/Http/Controllers/User/GoalSavingsController.php`

## 8. Notifikasi Standing Instruction
- **Standing Instruction Dibuat** - Ketika user membuat standing instruction
  - Notifikasi ke: User
  - File: `app/Http/Controllers/User/ScheduledTransferController.php`
  
- **Status Standing Instruction Diperbarui** - Ketika status berubah
  - Notifikasi ke: User
  - File: `app/Http/Controllers/User/ScheduledTransferController.php`

## 9. Notifikasi Produk Digital
- **Pembelian Berhasil** - Ketika pembelian produk digital selesai
  - Notifikasi ke: User
  - File: `app/Http/Controllers/User/DigitalProductController.php`

## 10. Notifikasi Loyalitas
- **Poin Berhasil Ditukar** - Ketika user menukar poin loyalitas
  - Notifikasi ke: User
  - File: `app/Http/Controllers/User/LoyaltyController.php`

## 11. Notifikasi Pesan Aman
- **Pesan Aman Baru** - Ketika user menerima pesan aman
  - Notifikasi ke: User (penerima)
  - File: `app/Http/Controllers/User/SecureMessageController.php`

## 12. Notifikasi Tiket Support
- **Tiket Baru** - Ketika user membuat tiket support
  - Notifikasi ke: CS staff (role 6)
  - File: `app/Http/Controllers/User/TicketController.php`
  
- **Balasan Tiket** - Ketika ada balasan tiket
  - Notifikasi ke: CS staff (role 6) atau User
  - File: `app/Http/Controllers/User/TicketController.php` dan `app/Http/Controllers/Admin/TicketController.php`
  
- **Tiket Ditugaskan** - Ketika tiket ditugaskan ke staff
  - Notifikasi ke: Staff yang ditugaskan
  - File: `app/Http/Controllers/Admin/TicketController.php`
  
- **Tiket Ditutup** - Ketika tiket ditutup
  - Notifikasi ke: User
  - File: `app/Http/Controllers/Admin/TicketController.php`

## 13. Notifikasi Transaksi Reversal
- **Transaksi Di-reverse** - Ketika transaksi di-reverse oleh admin
  - Notifikasi ke: User (pengirim dan penerima)
  - File: `app/Http/Controllers/Admin/TransactionReversalController.php`

## 14. Notifikasi Setoran Tunai (Teller)
- **Setoran Tunai Berhasil** - Ketika setoran tunai selesai
  - Notifikasi ke: User
  - File: `app/Http/Controllers/Admin/TellerController.php`
  
- **Penarikan Tunai Berhasil** - Ketika penarikan tunai selesai
  - Notifikasi ke: User
  - File: `app/Http/Controllers/Admin/TellerController.php`

## 15. Notifikasi Staff
- **Status Akun Diperbarui** - Ketika status staff diubah (blocked/suspended)
  - Notifikasi ke: Staff
  - File: `app/Http/Controllers/Admin/StaffController.php`
  
- **Password Direset** - Ketika password staff direset
  - Notifikasi ke: Staff
  - File: `app/Http/Controllers/Admin/StaffController.php`

## Total Jenis Notifikasi: 30+

## Struktur Database Notifikasi

```sql
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Cara Menggunakan NotificationService

```php
// Notify single user
$this->notificationService->notifyUser(
    $userId,
    'Judul Notifikasi',
    'Pesan notifikasi'
);

// Notify staff by role
$this->notificationService->notifyStaffByRole(
    [1, 2, 3], // Role IDs
    'Judul Notifikasi',
    'Pesan notifikasi'
);

// Notify all users
$this->notificationService->notifyAll(
    'Judul Notifikasi',
    'Pesan notifikasi'
);

// Notify with urgent flag
$this->notificationService->sendUrgentNotification(
    $userId,
    'Judul Notifikasi',
    'Pesan notifikasi'
);
```

## Fitur Push Notification

Semua notifikasi juga dikirim sebagai push notification ke browser user jika:
1. User telah subscribe ke push notifications
2. Browser mendukung Web Push API
3. VAPID keys sudah dikonfigurasi di `.env`

## Generate VAPID Keys Baru

Untuk generate VAPID keys baru di VPS:

```bash
php artisan vapid:generate
```

Command ini akan menampilkan VAPID keys baru yang harus ditambahkan ke `.env`:

```
VITE_VAPID_PUBLIC_KEY=...
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
```

Setelah itu, jalankan:

```bash
php artisan config:cache
```

## Status Implementasi

✅ Semua 30+ jenis notifikasi sudah terimplementasi
✅ NotificationService sudah berfungsi dengan baik
✅ Push notification sudah terintegrasi
✅ Database notifications sudah tersedia
✅ Frontend notification page sudah berfungsi
✅ VAPID keys sudah dikonfigurasi
✅ Command untuk generate VAPID keys sudah tersedia
