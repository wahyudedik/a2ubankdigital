# 🐛 DAFTAR ISSUE YANG DITEMUKAN - A2U Bank Digital
**Tanggal:** 30 Maret 2026  
**Hasil Audit:** Frontend ↔ Backend ↔ Database

---

## 🔴 CRITICAL ISSUES (Harus Diperbaiki Segera)

### ISSUE #1: Scheduled Transfers - UI Tidak Ada
**Severity:** CRITICAL  
**Impact:** Cron job jalan tapi user tidak bisa manage jadwal

**Detail:**
- ✅ Command `ProcessScheduledTransfers.php` sudah ada & jalan setiap hari
- ✅ Tabel `scheduled_transfers` sudah ada
- ❌ Tidak ada UI untuk customer buat/edit/hapus jadwal
- ❌ Tidak ada routes untuk CRUD scheduled transfers

**Solusi:**
```php
// 1. Buat controller
app/Http/Controllers/User/ScheduledTransferController.php

// 2. Buat routes
Route::get('/scheduled-transfers', [UserPageController::class, 'scheduledTransfers']);
Route::post('/scheduled-transfers', [ActionController::class, 'createScheduledTransfer']);
Route::put('/scheduled-transfers/{id}', [ActionController::class, 'updateScheduledTransfer']);
Route::delete('/scheduled-transfers/{id}', [ActionController::class, 'deleteScheduledTransfer']);

// 3. Buat frontend
resources/js/Pages/ScheduledTransfersPage.jsx
```

---

### ISSUE #2: Standing Instructions - UI Tidak Ada
**Severity:** CRITICAL  
**Impact:** Cron job jalan tapi user tidak bisa manage standing instruction

**Detail:**
- ✅ Command `ProcessStandingInstructions.php` sudah ada & jalan setiap hari
- ✅ Tabel `standing_instructions` sudah ada
- ❌ Tidak ada UI untuk customer buat/edit/hapus standing instruction
- ❌ Tidak ada routes untuk CRUD standing instructions

**Solusi:**
```php
// 1. Buat controller
app/Http/Controllers/User/StandingInstructionController.php

// 2. Buat routes
Route::get('/standing-instructions', [UserPageController::class, 'standingInstructions']);
Route::post('/standing-instructions', [ActionController::class, 'createStandingInstruction']);

// 3. Buat frontend
resources/js/Pages/StandingInstructionsPage.jsx
```

---

### ISSUE #3: Support Tickets - Routes Tidak Ada
**Severity:** CRITICAL  
**Impact:** Customer tidak bisa buat tiket support

**Detail:**
- ✅ Controller `User\TicketController.php` sudah ada
- ✅ Controller `Admin\TicketController.php` sudah ada
- ✅ Tabel `support_tickets` & `support_ticket_replies` sudah ada
- ❌ Tidak ada routes di `web.php` atau `ajax.php`
- ❌ Tidak ada halaman frontend

**Solusi:**
```php
// Tambah routes di routes/web.php
Route::get('/tickets', [UserPageController::class, 'tickets']);
Route::post('/tickets', [ActionController::class, 'createTicket']);
Route::get('/tickets/{id}', [UserPageController::class, 'ticketDetail']);

// Tambah routes di routes/ajax.php
Route::post('/user/tickets', [User\TicketController::class, 'store']);
Route::post('/user/tickets/{id}/reply', [User\TicketController::class, 'reply']);

// Buat frontend
resources/js/Pages/TicketsPage.jsx
resources/js/Pages/TicketDetailPage.jsx
```

---

## 🟡 HIGH PRIORITY ISSUES

### ISSUE #4: External Transfer - Tidak Diimplementasi
**Severity:** HIGH  
**Impact:** Customer tidak bisa transfer ke bank lain

**Detail:**
- ✅ Controller `User\ExternalTransferController.php` sudah ada
- ✅ Tabel `external_banks` sudah ada
- ❌ Tidak ada routes
- ❌ Tidak ada halaman frontend
- ❌ Tidak ada data seeder untuk external_banks

**Solusi:**
```php
// 1. Buat seeder
database/seeders/ExternalBankSeeder.php

// 2. Tambah routes
Route::get('/external-transfer', [UserPageController::class, 'externalTransfer']);
Route::post('/external-transfer/inquiry', [ActionController::class, 'externalTransferInquiry']);
Route::post('/external-transfer/execute', [ActionController::class, 'externalTransferExecute']);

// 3. Buat frontend
resources/js/Pages/ExternalTransferPage.jsx
```

---

### ISSUE #5: FAQ & Announcements - Frontend Tidak Ada
**Severity:** HIGH  
**Impact:** Customer tidak bisa lihat FAQ & pengumuman

**Detail:**
- ✅ Controller `Admin\FaqController.php` sudah ada
- ✅ Controller `Admin\AnnouncementController.php` sudah ada
- ✅ Controller `User\AnnouncementController.php` sudah ada
- ✅ Tabel `faqs` & `announcements` sudah ada
- ❌ Tidak ada routes
- ❌ Tidak ada halaman frontend
- ❌ Tidak ada data seeder

**Solusi:**
```php
// 1. Buat seeder
database/seeders/FaqSeeder.php
database/seeders/AnnouncementSeeder.php

// 2. Tambah routes
Route::get('/faq', [UserPageController::class, 'faq']);
Route::get('/announcements', [UserPageController::class, 'announcements']);

// Admin routes
Route::get('/admin/faq', [AdminPageController::class, 'faq']);
Route::post('/admin/faq', [ActionController::class, 'storeFaq']);
Route::get('/admin/announcements', [AdminPageController::class, 'announcements']);
Route::post('/admin/announcements', [ActionController::class, 'storeAnnouncement']);

// 3. Buat frontend
resources/js/Pages/FaqPage.jsx
resources/js/Pages/AnnouncementsPage.jsx
resources/js/Pages/AdminFaqPage.jsx
resources/js/Pages/AdminAnnouncementsPage.jsx
```

---

### ISSUE #6: Secure Messages - Tidak Diimplementasi
**Severity:** HIGH  
**Impact:** Tidak ada komunikasi aman bank-customer

**Detail:**
- ✅ Controller `Admin\DirectMessageController.php` sudah ada
- ✅ Tabel `secure_messages` sudah ada
- ❌ Tidak ada routes
- ❌ Tidak ada halaman frontend
- ❌ Tidak ada controller untuk customer side

**Solusi:**
```php
// 1. Buat controller customer
app/Http/Controllers/User/SecureMessageController.php

// 2. Tambah routes
Route::get('/messages', [UserPageController::class, 'messages']);
Route::post('/messages', [ActionController::class, 'sendMessage']);

// 3. Buat frontend
resources/js/Pages/SecureMessagesPage.jsx
resources/js/Pages/AdminMessagesPage.jsx
```

---

## 🟠 MEDIUM PRIORITY ISSUES

### ISSUE #7: Digital Products - Frontend Tidak Ada
**Severity:** MEDIUM  
**Impact:** Customer tidak bisa beli pulsa/paket data

**Detail:**
- ✅ Controller `User\DigitalProductController.php` sudah ada
- ✅ Controller `Admin\ProductController.php` sudah ada (CRUD digital products)
- ✅ Tabel `digital_products` sudah ada
- ✅ Routes sudah ada di `ajax.php`
- ❌ Tidak ada halaman frontend

**Solusi:**
```jsx
// Buat frontend
resources/js/Pages/DigitalProductsPage.jsx
resources/js/Pages/AdminDigitalProductsPage.jsx
```

---

### ISSUE #8: Bill Payment - Data Seeder Tidak Ada
**Severity:** MEDIUM  
**Impact:** Fitur bill payment tidak bisa digunakan

**Detail:**
- ✅ Controller `User\BillPaymentController.php` sudah ada
- ✅ Tabel `biller_products` sudah ada
- ✅ Frontend `BillPaymentPage.jsx` sudah ada
- ❌ Tidak ada data biller (PLN, PDAM, Telkom, dll)

**Solusi:**
```php
// Buat seeder
database/seeders/BillerProductSeeder.php

DB::table('biller_products')->insert([
    ['biller_code' => 'PLN', 'biller_name' => 'PLN Prepaid', 'category' => 'LISTRIK', 'is_active' => true],
    ['biller_code' => 'PLNPOSTPAID', 'biller_name' => 'PLN Postpaid', 'category' => 'LISTRIK', 'is_active' => true],
    ['biller_code' => 'PDAM', 'biller_name' => 'PDAM', 'category' => 'AIR', 'is_active' => true],
    ['biller_code' => 'TELKOM', 'biller_name' => 'Telkom', 'category' => 'TELEPON', 'is_active' => true],
    ['biller_code' => 'BPJS', 'biller_name' => 'BPJS Kesehatan', 'category' => 'ASURANSI', 'is_active' => true],
]);
```

---

### ISSUE #9: QR Payment - Implementasi Tidak Lengkap
**Severity:** MEDIUM  
**Impact:** Fitur QR payment tidak optimal

**Detail:**
- ✅ Controller `User\QrPaymentController.php` sudah ada (generate)
- ✅ Routes sudah ada: `/user/payment/qr-generate`
- ❌ Tidak ada halaman khusus QR Payment
- ❌ Tidak ada fitur scan QR
- ❌ Frontend hanya ada `PaymentPage.jsx` (generic)

**Solusi:**
```jsx
// Buat frontend lengkap
resources/js/Pages/QrPaymentPage.jsx  // Generate QR
resources/js/Pages/QrScannerPage.jsx  // Scan QR

// Tambah method di controller
public function scan(Request $request) { /* Scan & process QR */ }
```

---

### ISSUE #10: Loyalty Points - Tidak Diimplementasi
**Severity:** MEDIUM  
**Impact:** Tidak ada program loyalitas

**Detail:**
- ✅ Controller `User\LoyaltyController.php` sudah ada
- ✅ Tabel `loyalty_points_history` sudah ada
- ❌ Tidak ada routes
- ❌ Tidak ada halaman frontend

**Solusi:**
```php
// Tambah routes
Route::get('/loyalty', [UserPageController::class, 'loyalty']);
Route::get('/loyalty/history', [UserPageController::class, 'loyaltyHistory']);

// Buat frontend
resources/js/Pages/LoyaltyPointsPage.jsx
```

---

## 🔵 LOW PRIORITY ISSUES

### ISSUE #11: Goal Savings - Tidak Diimplementasi
**Severity:** LOW  
**Impact:** Fitur tabungan berencana tidak ada

**Detail:**
- ✅ Tabel `goal_savings_details` sudah ada
- ❌ Tidak ada controller
- ❌ Tidak ada routes
- ❌ Tidak ada halaman frontend

**Solusi:**
```php
// Implementasi lengkap
app/Http/Controllers/User/GoalSavingsController.php
resources/js/Pages/GoalSavingsPage.jsx
```

---

### ISSUE #12: Investment Products - Implementasi Kosong
**Severity:** LOW  
**Impact:** Fitur investasi tidak berfungsi

**Detail:**
- ✅ Tabel `investment_products` sudah ada
- ✅ Frontend `InvestmentPage.jsx` sudah ada (tapi kosong)
- ✅ Utility `UtilityServicesController::getInvestmentProducts` sudah ada
- ❌ Tidak ada controller untuk CRUD
- ❌ Tidak ada implementasi lengkap

**Solusi:**
```php
// Implementasi lengkap
app/Http/Controllers/User/InvestmentController.php
// Update InvestmentPage.jsx dengan implementasi lengkap
```

---

### ISSUE #13: Account Closure - Tidak Diimplementasi
**Severity:** LOW  
**Impact:** Customer tidak bisa request tutup rekening

**Detail:**
- ✅ Tabel `account_closure_requests` sudah ada
- ❌ Tidak ada controller
- ❌ Tidak ada routes
- ❌ Tidak ada halaman frontend

**Solusi:**
```php
// Implementasi lengkap
app/Http/Controllers/User/AccountClosureController.php
resources/js/Pages/AccountClosurePage.jsx
```

---

### ISSUE #14: Debt Collection - Tidak Diimplementasi
**Severity:** LOW  
**Impact:** Debt collector tidak punya tools

**Detail:**
- ✅ Controller `DebtCollectorController.php` sudah ada
- ✅ Tabel `debt_collection_assignments` & `collection_visit_reports` sudah ada
- ❌ Tidak ada routes
- ❌ Tidak ada halaman frontend

**Solusi:**
```php
// Implementasi lengkap untuk debt collector role
resources/js/Pages/DebtCollectionPage.jsx
resources/js/Pages/CollectionReportPage.jsx
```

---

### ISSUE #15: E-Wallet Integration - Tidak Diimplementasi
**Severity:** LOW  
**Impact:** Tidak ada integrasi e-wallet

**Detail:**
- ✅ Controller `User\EWalletController.php` sudah ada
- ❌ Tidak ada routes
- ❌ Tidak ada halaman frontend
- ❌ Tidak ada tabel khusus

**Solusi:**
```php
// Implementasi lengkap
resources/js/Pages/EWalletPage.jsx
// Tambah routes untuk e-wallet
```

---

### ISSUE #16: Marketing Features - Tidak Diimplementasi
**Severity:** LOW  
**Impact:** Marketing tidak punya dashboard

**Detail:**
- ✅ Controller `Admin\MarketingController.php` sudah ada
- ❌ Tidak ada routes
- ❌ Tidak ada halaman frontend

**Solusi:**
```php
// Implementasi lengkap
resources/js/Pages/MarketingDashboardPage.jsx
```

---

## 📊 RINGKASAN ISSUE

| Severity | Jumlah | Status |
|----------|--------|--------|
| 🔴 CRITICAL | 3 | Harus diperbaiki segera |
| 🟡 HIGH | 3 | Prioritas tinggi |
| 🟠 MEDIUM | 5 | Prioritas menengah |
| 🔵 LOW | 6 | Prioritas rendah |
| **TOTAL** | **17 issue** | |

---

## 🎯 REKOMENDASI PRIORITAS PERBAIKAN

### Sprint 1 (URGENT - 1-2 minggu):
1. ✅ Issue #1: Scheduled Transfers UI
2. ✅ Issue #2: Standing Instructions UI
3. ✅ Issue #3: Support Tickets Routes & UI

### Sprint 2 (HIGH - 2-3 minggu):
4. ✅ Issue #4: External Transfer
5. ✅ Issue #5: FAQ & Announcements
6. ✅ Issue #6: Secure Messages

### Sprint 3 (MEDIUM - 3-4 minggu):
7. ✅ Issue #7: Digital Products Frontend
8. ✅ Issue #8: Bill Payment Seeder
9. ✅ Issue #9: QR Payment Lengkap
10. ✅ Issue #10: Loyalty Points
11. ✅ Issue #11: Goal Savings

### Sprint 4 (LOW - Optional):
12. ✅ Issue #12-16: Fitur tambahan lainnya

---

**Dibuat oleh:** Kiro AI Assistant  
**Metode:** Comprehensive audit Frontend ↔ Backend ↔ Database  
**Tanggal:** 30 Maret 2026
