# 📋 DAFTAR PERBAIKAN PRIORITAS - A2U Bank Digital

**Tanggal:** 30 Maret 2026  
**Total Item:** 17 perbaikan

---

## 🔴 PRIORITAS 1: CRITICAL (Harus Segera)

### 1. Scheduled Transfers - Tambah UI & Routes

**Problem:** Cron job sudah jalan tapi user tidak bisa manage jadwal transfer

**File yang perlu dibuat:**
```
✅ app/Http/Controllers/User/ScheduledTransferController.php
✅ resources/js/Pages/ScheduledTransfersPage.jsx
```

**Routes yang perlu ditambah di `routes/web.php`:**
```php
Route::get('/scheduled-transfers', [UserPageController::class, 'scheduledTransfers']);
```

**Routes yang perlu ditambah di `routes/ajax.php`:**
```php
Route::get('/user/scheduled-transfers', [User\ScheduledTransferController::class, 'index']);
Route::post('/user/scheduled-transfers', [User\ScheduledTransferController::class, 'store']);
Route::put('/user/scheduled-transfers/{id}', [User\ScheduledTransferController::class, 'update']);
Route::delete('/user/scheduled-transfers/{id}', [User\ScheduledTransferController::class, 'destroy']);
```

---

### 2. Standing Instructions - Tambah UI & Routes

**Problem:** Cron job sudah jalan tapi user tidak bisa manage standing instruction

**File yang perlu dibuat:**
```
✅ app/Http/Controllers/User/StandingInstructionController.php
✅ resources/js/Pages/StandingInstructionsPage.jsx
```

**Routes yang perlu ditambah di `routes/web.php`:**
```php
Route::get('/standing-instructions', [UserPageController::class, 'standingInstructions']);
```

**Routes yang perlu ditambah di `routes/ajax.php`:**
```php
Route::get('/user/standing-instructions', [User\StandingInstructionController::class, 'index']);
Route::post('/user/standing-instructions', [User\StandingInstructionController::class, 'store']);
Route::put('/user/standing-instructions/{id}', [User\StandingInstructionController::class, 'update']);
Route::delete('/user/standing-instructions/{id}', [User\StandingInstructionController::class, 'destroy']);
```

---

### 3. Support Tickets - Tambah Routes & UI

**Problem:** Controller sudah ada tapi tidak ada routes & UI

**File yang perlu dibuat:**
```
✅ resources/js/Pages/TicketsPage.jsx
✅ resources/js/Pages/TicketDetailPage.jsx
✅ resources/js/Pages/AdminTicketsPage.jsx
```

**Routes yang perlu ditambah di `routes/web.php`:**
```php
// Customer
Route::get('/tickets', [UserPageController::class, 'tickets']);
Route::get('/tickets/{id}', [UserPageController::class, 'ticketDetail']);

// Admin
Route::get('/admin/tickets', [AdminPageController::class, 'tickets']);
Route::get('/admin/tickets/{id}', [AdminPageController::class, 'ticketDetail']);
```

**Routes yang perlu ditambah di `routes/ajax.php`:**
```php
// Customer
Route::post('/user/tickets', [User\TicketController::class, 'store']);
Route::post('/user/tickets/{id}/reply', [User\TicketController::class, 'reply']);
Route::put('/user/tickets/{id}/close', [User\TicketController::class, 'close']);

// Admin
Route::get('/admin/tickets', [Admin\TicketController::class, 'index']);
Route::put('/admin/tickets/{id}/assign', [Admin\TicketController::class, 'assign']);
Route::put('/admin/tickets/{id}/status', [Admin\TicketController::class, 'updateStatus']);
```

**Method yang perlu ditambah di `UserPageController.php`:**
```php
public function tickets() {
    return Inertia::render('TicketsPage');
}

public function ticketDetail($id) {
    return Inertia::render('TicketDetailPage', ['ticketId' => $id]);
}
```

**Method yang perlu ditambah di `AdminPageController.php`:**
```php
public function tickets() {
    return Inertia::render('AdminTicketsPage');
}

public function ticketDetail($id) {
    return Inertia::render('AdminTicketDetailPage', ['ticketId' => $id]);
}
```

---

## 🟡 PRIORITAS 2: HIGH (Penting)

### 4. External Transfer - Implementasi Lengkap

**Problem:** Controller ada tapi tidak ada routes, UI, dan data bank

**File yang perlu dibuat:**
```
✅ resources/js/Pages/ExternalTransferPage.jsx
✅ database/seeders/ExternalBankSeeder.php
```

**Routes yang perlu ditambah di `routes/web.php`:**
```php
Route::get('/external-transfer', [UserPageController::class, 'externalTransfer']);
```

**Routes yang perlu ditambah di `routes/ajax.php`:**
```php
Route::get('/user/external-banks', [User\ExternalTransferController::class, 'getBanks']);
Route::post('/user/external-transfer/inquiry', [User\ExternalTransferController::class, 'inquiry']);
Route::post('/user/external-transfer/execute', [User\ExternalTransferController::class, 'execute']);
```

**Seeder yang perlu dibuat:**
```php
// database/seeders/ExternalBankSeeder.php
DB::table('external_banks')->insert([
    ['bank_code' => '002', 'bank_name' => 'BRI', 'is_active' => true],
    ['bank_code' => '008', 'bank_name' => 'Mandiri', 'is_active' => true],
    ['bank_code' => '009', 'bank_name' => 'BNI', 'is_active' => true],
    ['bank_code' => '013', 'bank_name' => 'Permata', 'is_active' => true],
    ['bank_code' => '014', 'bank_name' => 'BCA', 'is_active' => true],
]);
```

**Jangan lupa jalankan:**
```bash
php artisan db:seed --class=ExternalBankSeeder
```

---

### 5. FAQ & Announcements - Tambah Routes & UI

**Problem:** Controller ada tapi tidak ada routes & UI

**File yang perlu dibuat:**
```
✅ resources/js/Pages/FaqPage.jsx
✅ resources/js/Pages/AnnouncementsPage.jsx
✅ resources/js/Pages/AdminFaqPage.jsx
✅ resources/js/Pages/AdminAnnouncementsPage.jsx
✅ database/seeders/FaqSeeder.php
✅ database/seeders/AnnouncementSeeder.php
```

**Routes yang perlu ditambah di `routes/web.php`:**
```php
// Customer
Route::get('/faq', [UserPageController::class, 'faq']);
Route::get('/announcements', [UserPageController::class, 'announcements']);

// Admin
Route::get('/admin/faq', [AdminPageController::class, 'faq']);
Route::post('/admin/faq', [ActionController::class, 'storeFaq']);
Route::put('/admin/faq/{id}', [ActionController::class, 'updateFaq']);
Route::delete('/admin/faq/{id}', [ActionController::class, 'deleteFaq']);

Route::get('/admin/announcements', [AdminPageController::class, 'announcements']);
Route::post('/admin/announcements', [ActionController::class, 'storeAnnouncement']);
Route::put('/admin/announcements/{id}', [ActionController::class, 'updateAnnouncement']);
Route::delete('/admin/announcements/{id}', [ActionController::class, 'deleteAnnouncement']);
```

**Routes yang perlu ditambah di `routes/ajax.php`:**
```php
// Customer
Route::get('/user/faq', [User\FaqController::class, 'index']);
Route::get('/user/announcements', [User\AnnouncementController::class, 'index']);

// Admin
Route::get('/admin/faq', [Admin\FaqController::class, 'index']);
Route::post('/admin/faq', [Admin\FaqController::class, 'store']);
Route::put('/admin/faq/{id}', [Admin\FaqController::class, 'update']);
Route::delete('/admin/faq/{id}', [Admin\FaqController::class, 'destroy']);

Route::get('/admin/announcements', [Admin\AnnouncementController::class, 'index']);
Route::post('/admin/announcements', [Admin\AnnouncementController::class, 'store']);
Route::put('/admin/announcements/{id}', [Admin\AnnouncementController::class, 'update']);
Route::delete('/admin/announcements/{id}', [Admin\AnnouncementController::class, 'destroy']);
```

---

### 6. Secure Messages - Implementasi Lengkap

**Problem:** Controller admin ada, tapi tidak ada controller customer, routes, dan UI

**File yang perlu dibuat:**
```
✅ app/Http/Controllers/User/SecureMessageController.php
✅ resources/js/Pages/SecureMessagesPage.jsx
✅ resources/js/Pages/AdminMessagesPage.jsx
```

**Routes yang perlu ditambah di `routes/web.php`:**
```php
// Customer
Route::get('/messages', [UserPageController::class, 'messages']);

// Admin
Route::get('/admin/messages', [AdminPageController::class, 'messages']);
```

**Routes yang perlu ditambah di `routes/ajax.php`:**
```php
// Customer
Route::get('/user/messages', [User\SecureMessageController::class, 'index']);
Route::post('/user/messages', [User\SecureMessageController::class, 'send']);
Route::put('/user/messages/{id}/read', [User\SecureMessageController::class, 'markAsRead']);

// Admin
Route::get('/admin/messages', [Admin\DirectMessageController::class, 'index']);
Route::post('/admin/messages', [Admin\DirectMessageController::class, 'send']);
Route::get('/admin/messages/customer/{userId}', [Admin\DirectMessageController::class, 'getByCustomer']);
```

---

## 🟠 PRIORITAS 3: MEDIUM (Perlu Diperbaiki)

### 7. Digital Products - Tambah UI

**Problem:** Backend lengkap tapi tidak ada UI

**File yang perlu dibuat:**
```
✅ resources/js/Pages/DigitalProductsPage.jsx
✅ resources/js/Pages/AdminDigitalProductsPage.jsx
```

**Routes yang perlu ditambah di `routes/web.php`:**
```php
// Customer
Route::get('/digital-products', [UserPageController::class, 'digitalProducts']);

// Admin
Route::get('/admin/digital-products', [AdminPageController::class, 'digitalProducts']);
```

**Method yang perlu ditambah di `UserPageController.php`:**
```php
public function digitalProducts() {
    return Inertia::render('DigitalProductsPage');
}
```

**Method yang perlu ditambah di `AdminPageController.php`:**
```php
public function digitalProducts() {
    return Inertia::render('AdminDigitalProductsPage');
}
```

---

### 8. Bill Payment - Tambah Data Seeder

**Problem:** Fitur ada tapi tidak ada data biller

**File yang perlu dibuat:**
```
✅ database/seeders/BillerProductSeeder.php
```

**Isi seeder:**
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillerProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('biller_products')->insert([
            [
                'biller_code' => 'PLN',
                'biller_name' => 'PLN Prepaid',
                'category' => 'LISTRIK',
                'admin_fee' => 2500,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'biller_code' => 'PLNPOSTPAID',
                'biller_name' => 'PLN Postpaid',
                'category' => 'LISTRIK',
                'admin_fee' => 2500,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'biller_code' => 'PDAM',
                'biller_name' => 'PDAM',
                'category' => 'AIR',
                'admin_fee' => 2000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'biller_code' => 'TELKOM',
                'biller_name' => 'Telkom',
                'category' => 'TELEPON',
                'admin_fee' => 2000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'biller_code' => 'BPJS',
                'biller_name' => 'BPJS Kesehatan',
                'category' => 'ASURANSI',
                'admin_fee' => 2500,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
```

**Jangan lupa jalankan:**
```bash
php artisan db:seed --class=BillerProductSeeder
```

---

### 9. QR Payment - Lengkapi Implementasi

**Problem:** Backend parsial, perlu tambah scan QR

**File yang perlu dibuat:**
```
✅ resources/js/Pages/QrPaymentPage.jsx
✅ resources/js/Pages/QrScannerPage.jsx
```

**Routes yang perlu ditambah di `routes/web.php`:**
```php
Route::get('/qr-payment', [UserPageController::class, 'qrPayment']);
Route::get('/qr-scanner', [UserPageController::class, 'qrScanner']);
```

**Routes yang perlu ditambah di `routes/ajax.php`:**
```php
Route::post('/user/payment/qr-scan', [User\QrPaymentController::class, 'scan']);
Route::post('/user/payment/qr-pay', [User\QrPaymentController::class, 'pay']);
```

**Method yang perlu ditambah di `User\QrPaymentController.php`:**
```php
public function scan(Request $request) {
    // Scan QR code & return payment info
}

public function pay(Request $request) {
    // Process QR payment
}
```

---

### 10. Loyalty Points - Implementasi Lengkap

**Problem:** Controller ada tapi tidak ada routes & UI

**File yang perlu dibuat:**
```
✅ resources/js/Pages/LoyaltyPointsPage.jsx
```

**Routes yang perlu ditambah di `routes/web.php`:**
```php
Route::get('/loyalty', [UserPageController::class, 'loyalty']);
```

**Routes yang perlu ditambah di `routes/ajax.php`:**
```php
Route::get('/user/loyalty/points', [User\LoyaltyController::class, 'getPoints']);
Route::get('/user/loyalty/history', [User\LoyaltyController::class, 'getHistory']);
Route::post('/user/loyalty/redeem', [User\LoyaltyController::class, 'redeem']);
```

---

### 11. Goal Savings - Implementasi Lengkap

**Problem:** Tidak ada implementasi sama sekali

**File yang perlu dibuat:**
```
✅ app/Http/Controllers/User/GoalSavingsController.php
✅ resources/js/Pages/GoalSavingsPage.jsx
```

**Routes yang perlu ditambah di `routes/web.php`:**
```php
Route::get('/goal-savings', [UserPageController::class, 'goalSavings']);
```

**Routes yang perlu ditambah di `routes/ajax.php`:**
```php
Route::get('/user/goal-savings', [User\GoalSavingsController::class, 'index']);
Route::post('/user/goal-savings', [User\GoalSavingsController::class, 'store']);
Route::put('/user/goal-savings/{id}', [User\GoalSavingsController::class, 'update']);
Route::delete('/user/goal-savings/{id}', [User\GoalSavingsController::class, 'destroy']);
Route::post('/user/goal-savings/{id}/deposit', [User\GoalSavingsController::class, 'deposit']);
```

---

## 🔵 PRIORITAS 4: LOW (Opsional)

### 12. Investment Products - Implementasi Lengkap

**File yang perlu dibuat:**
```
✅ app/Http/Controllers/User/InvestmentController.php
```

**File yang perlu diupdate:**
```
✅ resources/js/Pages/InvestmentPage.jsx (sudah ada tapi kosong)
```

---

### 13. Account Closure - Implementasi Lengkap

**File yang perlu dibuat:**
```
✅ app/Http/Controllers/User/AccountClosureController.php
✅ resources/js/Pages/AccountClosurePage.jsx
✅ resources/js/Pages/AdminAccountClosureRequestsPage.jsx
```

---

### 14. Debt Collection - Implementasi UI

**File yang perlu dibuat:**
```
✅ resources/js/Pages/DebtCollectionPage.jsx
✅ resources/js/Pages/CollectionReportPage.jsx
```

---

### 15. E-Wallet Integration - Implementasi Lengkap

**File yang perlu dibuat:**
```
✅ resources/js/Pages/EWalletPage.jsx
```

---

### 16. Marketing Features - Implementasi Dashboard

**File yang perlu dibuat:**
```
✅ resources/js/Pages/MarketingDashboardPage.jsx
```

---

### 17. Update DatabaseSeeder

**File yang perlu diupdate:**
```
✅ database/seeders/DatabaseSeeder.php
```

**Tambahkan:**
```php
public function run(): void
{
    $this->call([
        RoleSeeder::class,
        UserSeeder::class,
        // Tambahkan seeder baru:
        ExternalBankSeeder::class,
        BillerProductSeeder::class,
        FaqSeeder::class,
        AnnouncementSeeder::class,
    ]);
}
```

---

## 📊 RINGKASAN

| Prioritas | Jumlah | Estimasi Waktu |
|-----------|--------|----------------|
| 🔴 CRITICAL | 3 item | 1-2 minggu |
| 🟡 HIGH | 3 item | 2-3 minggu |
| 🟠 MEDIUM | 5 item | 3-4 minggu |
| 🔵 LOW | 6 item | 4-6 minggu |
| **TOTAL** | **17 item** | **6-8 minggu** |

---

## 🎯 REKOMENDASI EKSEKUSI

**Week 1-2:** Prioritas 1 (Item #1-3)  
**Week 3-4:** Prioritas 2 (Item #4-6)  
**Week 5-6:** Prioritas 3 (Item #7-11)  
**Week 7-8:** Prioritas 4 (Item #12-17) - Optional

---

**Dibuat oleh:** Kiro AI Assistant  
**Tanggal:** 30 Maret 2026
