# FEATURES.md — Peta Lengkap Fitur A2U Bank Digital

> Dokumen ini mencakup seluruh fitur yang tersedia di aplikasi, status implementasi, dan lokasi kode terkait.

---

## 📊 Status Implementasi

| Kategori | Total Fitur | Status |
|----------|-------------|--------|
| Autentikasi & Keamanan | 8 | ✅ Selesai |
| Perbankan Dasar | 6 | ✅ Selesai |
| Transfer & Pembayaran | 7 | ✅ Selesai |
| Produk Keuangan | 5 | ✅ Selesai |
| Kartu | 3 | ✅ Selesai |
| Personal Finance | 4 | ✅ Selesai |
| Komunikasi & Dukungan | 4 | ✅ Selesai |
| Admin / Back-Office | 15 | ✅ Selesai |
| Sistem & Infrastruktur | 5 | ✅ Selesai |
| **Total** | **57** | **✅** |

---

## 🔐 1. Autentikasi & Keamanan

| # | Fitur | Endpoint / Route | Controller | Status |
|---|-------|------------------|------------|--------|
| 1.1 | Login (Email + Password) | `POST /login` | [`AuthController`](app/Http/Controllers/Auth/AuthController.php) | ✅ |
| 1.2 | Registrasi dengan OTP | `POST /ajax/auth/register/*` | [`RegisterController`](app/Http/Controllers/Auth/RegisterController.php) | ✅ |
| 1.3 | Logout | `POST /logout` | [`AuthController`](app/Http/Controllers/Auth/AuthController.php) | ✅ |
| 1.4 | Forgot Password (via OTP) | `POST /ajax/auth/forgot-password/*` | [`RegisterController`](app/Http/Controllers/Auth/RegisterController.php) | ✅ |
| 1.5 | Forgot PIN (via OTP) | `POST /ajax/user/security/forgot-pin/*` | [`SecurityController`](app/Http/Controllers/User/SecurityController.php) | ✅ |
| 1.6 | Ubah Password | `POST /ajax/user/security/update-password` | [`SecurityController`](app/Http/Controllers/User/SecurityController.php) | ✅ |
| 1.7 | Ubah PIN Transaksi | `POST /ajax/user/security/update-pin` | [`SecurityController`](app/Http/Controllers/User/SecurityController.php) | ✅ |
| 1.8 | Reset Password (via token) | `GET /reset-password` | [`AuthPageController`](app/Http/Controllers/Inertia/AuthPageController.php) | ✅ |

### Middleware Keamanan
| Middleware | File | Fungsi |
|-----------|------|--------|
| `CheckRole` | [`CheckRole.php`](app/Http/Middleware/CheckRole.php) | Otorisasi berdasarkan role |
| `SanitizeInput` | [`SanitizeInput.php`](app/Http/Middleware/SanitizeInput.php) | Input sanitization |
| `SecurityHeaders` | [`SecurityHeaders.php`](app/Http/Middleware/SecurityHeaders.php) | HTTP security headers |
| `VerifyCsrfToken` | [`VerifyCsrfToken.php`](app/Http/Middleware/VerifyCsrfToken.php) | CSRF protection |

---

## 🏦 2. Perbankan Dasar

| # | Fitur | Endpoint / Route | Controller | Status |
|---|-------|------------------|------------|--------|
| 2.1 | Dashboard Nasabah | `GET /dashboard` | [`UserDashboardController`](app/Http/Controllers/User/DashboardController.php) | ✅ |
| 2.2 | Riwayat Transaksi | `GET /ajax/user/transactions` | [`TransactionController`](app/Http/Controllers/User/TransactionController.php) | ✅ |
| 2.3 | Detail Transaksi | `GET /ajax/user/transactions/{id}` | [`TransactionController`](app/Http/Controllers/User/TransactionController.php) | ✅ |
| 2.4 | Daftar Rekening | `GET /ajax/user/accounts` | [`AccountController`](app/Http/Controllers/User/AccountController.php) | ✅ |
| 2.5 | Profil Nasabah | `GET /ajax/user/profile` | [`ProfileController`](app/Http/Controllers/User/ProfileController.php) | ✅ |
| 2.6 | Ubah Profil + Foto | `PUT /ajax/user/profile` | [`ProfileController`](app/Http/Controllers/User/ProfileController.php) | ✅ |

---

## 💸 3. Transfer & Pembayaran

| # | Fitur | Endpoint / Route | Controller | Status |
|---|-------|------------------|------------|--------|
| 3.1 | Transfer Internal (Inquiry + Execute) | `POST /ajax/user/transfer/internal/*` | [`TransactionController`](app/Http/Controllers/User/TransactionController.php) | ✅ |
| 3.2 | Transfer Eksternal (BI-FAST simulasi) | `POST /ajax/user/external-transfer/*` | [`ExternalTransferController`](app/Http/Controllers/User/ExternalTransferController.php) | ✅ |
| 3.3 | Transfer Terjadwal | `CRUD /ajax/user/scheduled-transfers` | [`ScheduledTransferController`](app/Http/Controllers/User/ScheduledTransferController.php) | ✅ |
| 3.4 | Standing Instruction (auto-recurring) | `CRUD /ajax/user/standing-instructions` | [`StandingInstructionController`](app/Http/Controllers/User/StandingInstructionController.php) | ✅ |
| 3.5 | Pembayaran Tagihan (Digiflazz) | `POST /ajax/user/bill-payment/*` | [`BillPaymentController`](app/Http/Controllers/User/BillPaymentController.php) | ✅ |
| 3.6 | Top-up Saldo | `POST /ajax/user/topup-requests` | Inline di [`ajax.php`](routes/ajax.php) | ✅ |
| 3.7 | QR Payment (QRIS) | `POST /ajax/user/payment/qr-*` | [`QrPaymentController`](app/Http/Controllers/User/QrPaymentController.php) | ✅ |

---

## 📈 4. Produk Keuangan

| # | Fitur | Endpoint / Route | Controller | Status |
|---|-------|------------------|------------|--------|
| 4.1 | Deposito (Buka & Kelola) | `CRUD /ajax/user/deposits` | [`DepositController`](app/Http/Controllers/User/DepositController.php) | ✅ |
| 4.2 | Pinjaman (Pengajuan & Cicilan) | `CRUD /ajax/user/loans` | [`LoanController`](app/Http/Controllers/User/LoanController.php) | ✅ |
| 4.3 | Investasi (Reksa Dana) | `GET /ajax/user/investment/*` | [`InvestmentController`](app/Http/Controllers/User/InvestmentController.php) | ✅ |
| 4.4 | Goal Savings | `CRUD /ajax/user/goal-savings` | [`GoalSavingsController`](app/Http/Controllers/User/GoalSavingsController.php) | ✅ |
| 4.5 | Produk Digital (Pulsa, Data, Voucher) | `GET /ajax/user/digital-products` | [`DigitalProductController`](app/Http/Controllers/User/DigitalProductController.php) | ✅ |

---

## 💳 5. Kartu

| # | Fitur | Endpoint / Route | Controller | Status |
|---|-------|------------------|------------|--------|
| 5.1 | Daftar & Request Kartu | `GET/POST /ajax/user/cards` | [`CardController`](app/Http/Controllers/User/CardController.php) | ✅ |
| 5.2 | Set Limit & Status Kartu | `PUT /ajax/user/cards/{id}/*` | [`CardController`](app/Http/Controllers/User/CardController.php) | ✅ |
| 5.3 | Reveal Nomor Kartu (sensitive) | `POST /ajax/user/cards/{id}/reveal` | [`CardController`](app/Http/Controllers/User/CardController.php) | ✅ |

---

## 💰 6. Personal Finance (Budgeting)

| # | Fitur | Endpoint / Route | Controller | Status |
|---|-------|------------------|------------|--------|
| 6.1 | Dashboard Budgeting | `GET /budgeting` | [`ExpenseController`](app/Http/Controllers/User/ExpenseController.php) | ✅ |
| 6.2 | Catatan Pengeluaran (CRUD) | `CRUD /ajax/user/expense/records` | [`ExpenseController`](app/Http/Controllers/User/ExpenseController.php) | ✅ |
| 6.3 | Budget per Kategori | `CRUD /ajax/user/expense/budgets` | [`ExpenseController`](app/Http/Controllers/User/ExpenseController.php) | ✅ |
| 6.4 | Analisis & Insights | `GET /ajax/user/expense/summary\|trend\|insights` | [`ExpenseController`](app/Http/Controllers/User/ExpenseController.php) | ✅ |

### Tabel Database Terkait
- `expense_categories` — Kategori pengeluaran (9 default + custom)
- `expense_records` — Catatan pengeluaran (manual + auto-import dari transaksi)
- `monthly_budgets` — Budget bulanan per kategori

---

## 📬 7. Komunikasi & Dukungan

| # | Fitur | Endpoint / Route | Controller | Status |
|---|-------|------------------|------------|--------|
| 7.1 | Notifikasi (Push + In-App) | `GET /ajax/user/notifications` | [`NotificationController`](app/Http/Controllers/User/NotificationController.php) | ✅ |
| 7.2 | Pesan Aman (Secure Messaging) | `CRUD /ajax/user/messages` | [`SecureMessageController`](app/Http/Controllers/User/SecureMessageController.php) | ✅ |
| 7.3 | Tiket Dukungan (CS) | `CRUD /ajax/user/tickets` | [`TicketController`](app/Http/Controllers/User/TicketController.php) | ✅ |
| 7.4 | FAQ & Pengumuman | `GET /ajax/user/faq\|announcements` | [`FaqController`](app/Http/Controllers/User/FaqController.php) | ✅ |

---

## 🎁 8. Fitur Tambahan Nasabah

| # | Fitur | Endpoint / Route | Controller | Status |
|---|-------|------------------|------------|--------|
| 8.1 | Penarikan Tunai | `CRUD /ajax/user/withdrawal-*` | [`WithdrawalController`](app/Http/Controllers/User/WithdrawalController.php) | ✅ |
| 8.2 | Program Loyalitas (Poin) | `GET/POST /ajax/user/loyalty/*` | [`LoyaltyController`](app/Http/Controllers/User/LoyaltyController.php) | ✅ |
| 8.3 | Rekening Penerima (Beneficiary) | `CRUD /ajax/user/beneficiaries` | Inline di [`ajax.php`](routes/ajax.php) | ✅ |
| 8.4 | Penutupan Rekening | `POST /ajax/user/account-closure/*` | [`AccountClosureController`](app/Http/Controllers/User/AccountClosureController.php) | ✅ |
| 8.5 | Top-up E-Wallet | Via halaman [`EWalletPage`](resources/js/Pages/EWalletPage.jsx) | — | ✅ |

---

## 🖥️ 9. Admin / Back-Office

### 9.1 Dashboard & Analitik
| Fitur | Route | Controller |
|-------|-------|------------|
| Dashboard Admin | `GET /admin/dashboard` | [`DashboardController`](app/Http/Controllers/Admin/DashboardController.php) |
| Laporan Keuangan | `GET /admin/reports` | [`ReportController`](app/Http/Controllers/Admin/ReportController.php) |
| Log Audit | `GET /admin/audit-log` | [`AdminApiController`](app/Http/Controllers/Api/AdminApiController.php) |

### 9.2 Manajemen Nasabah
| Fitur | Route | Controller |
|-------|-------|------------|
| Daftar Nasabah | `GET /admin/customers` | [`CustomerController`](app/Http/Controllers/Admin/CustomerController.php) |
| Tambah/Edit/Hapus Nasabah | CRUD `/admin/customers` | [`ActionController`](app/Http/Controllers/Inertia/ActionController.php) |
| Detail Nasabah | `GET /admin/customers/{id}` | [`CustomerController`](app/Http/Controllers/Admin/CustomerController.php) |
| Status Nasabah (aktif/blokir) | `PUT /admin/customers/{id}/status` | [`ActionController`](app/Http/Controllers/Inertia/ActionController.php) |

### 9.3 Operasi Teller
| Fitur | Route | Controller |
|-------|-------|------------|
| Setor Tunai | `POST /ajax/admin/teller/deposit` | [`TellerController`](app/Http/Controllers/Admin/TellerController.php) |
| Bayar Cicilan | `POST /ajax/admin/teller/pay-installment` | [`TellerController`](app/Http/Controllers/Admin/TellerController.php) |
| Inquiry Rekening | `POST /ajax/admin/teller/account-inquiry` | Inline di [`ajax.php`](routes/ajax.php) |
| Cetak Struk | `GET /admin/print-receipt/{id}` | [`AdminPageController`](app/Http/Controllers/Inertia/AdminPageController.php) |

### 9.4 Manajemen Permintaan
| Fitur | Route | Controller |
|-------|-------|------------|
| Approve/Reject Top-up | `POST /ajax/admin/topup-requests/process` | [`AdminApiController`](app/Http/Controllers/Api/AdminApiController.php) |
| Proses Withdrawal | `PUT /ajax/admin/withdrawal-requests/process` | [`AdminApiController`](app/Http/Controllers/Api/AdminApiController.php) |
| Proses Request Kartu | `PUT /ajax/admin/card-requests/{id}/process` | [`AdminApiController`](app/Http/Controllers/Api/AdminApiController.php) |

### 9.5 Manajemen Pinjaman
| Fitur | Route | Controller |
|-------|-------|------------|
| Daftar Pengajuan Pinjaman | `GET /admin/loan-applications` | [`LoanController`](app/Http/Controllers/Admin/LoanController.php) |
| Approve/Reject Pinjaman | `PUT /ajax/admin/loan-applications/status` | [`AdminApiController`](app/Http/Controllers/Api/AdminApiController.php) |
| Pencairan Pinjaman | `POST /ajax/admin/loan-applications/disburse` | [`AdminApiController`](app/Http/Controllers/Api/AdminApiController.php) |

### 9.6 Manajemen Produk
| Fitur | Route | Controller |
|-------|-------|------------|
| Produk Pinjaman | CRUD `/admin/loan-products` | [`ProductController`](app/Http/Controllers/Admin/ProductController.php) |
| Produk Deposito | CRUD `/admin/deposit-products` | [`ProductController`](app/Http/Controllers/Admin/ProductController.php) |

### 9.7 Manajemen Staf & Organisasi
| Fitur | Route | Controller |
|-------|-------|------------|
| Manajemen Unit/Cabang | CRUD `/admin/units` | [`UnitController`](app/Http/Controllers/Admin/UnitController.php) |
| Manajemen Staf | CRUD `/admin/staff` | [`StaffController`](app/Http/Controllers/Admin/StaffController.php) |
| Reset Password Staf | `POST /admin/staff/{id}/reset-password` | [`ActionController`](app/Http/Controllers/Inertia/ActionController.php) |

### 9.8 Pengaturan Sistem
| Fitur | Route | Controller |
|-------|-------|------------|
| Konfigurasi Sistem | `GET/PUT /ajax/admin/system/settings` | [`AdminApiController`](app/Http/Controllers/Api/AdminApiController.php) |
| FAQ Management | CRUD via [`FaqController`](app/Http/Controllers/Admin/FaqController.php) | ✅ |
| Pengumuman | CRUD via [`AnnouncementController`](app/Http/Controllers/Admin/AnnouncementController.php) | ✅ |

### 9.9 Debt Collection
| Fitur | Route | Controller |
|-------|-------|------------|
| Monitoring Angsuran | `GET /admin/debt-collection` | [`DebtCollectorController`](app/Http/Controllers/DebtCollectorController.php) |
| Laporan Kunjungan | Via [`AdminPageController`](app/Http/Controllers/Inertia/AdminPageController.php) | ✅ |

### 9.10 Unit-Based Access Control 🔒
| Fitur | File | Status |
|-------|------|--------|
| `UnitAccessTrait` (reusable filtering logic) | [`UnitAccessTrait`](app/Traits/UnitAccessTrait.php) | ✅ |
| Dashboard & Admin pages filtered by unit | [`AdminPageController`](app/Http/Controllers/Inertia/AdminPageController.php) | ✅ |
| Customer CRUD filtered by unit | [`CustomerController`](app/Http/Controllers/Admin/CustomerController.php) | ✅ |
| Staff seeder with unit_id assignment | [`UserSeeder`](database/seeders/UserSeeder.php) | ✅ |

**Aturan Akses:**
| Role | Akses Unit |
|------|-----------|
| Super Admin (role=1) | SEMUA unit (bypass filter) |
| Kepala Cabang (role=2) | Cabang + semua sub-unit |
| Roles 3-8 (Manager, Marketing, Teller, CS, Analis, DC) | Hanya unit sendiri |

---

## ⚙️ 10. Sistem & Infrastruktur

| # | Fitur | File | Status |
|---|-------|------|--------|
| 10.1 | Scheduler (Cron Jobs) | [`ProcessScheduledTransfers`](app/Console/Commands/ProcessScheduledTransfers.php), [`ProcessStandingInstructions`](app/Console/Commands/ProcessStandingInstructions.php), [`CheckOverdueInstallments`](app/Console/Commands/CheckOverdueInstallments.php) | ✅ |
| 10.2 | Queue Workers (Email + Notifikasi) | [`SendEmailJob`](app/Jobs/SendEmailJob.php) | ✅ |
| 10.3 | Push Notification (VAPID) | [`NotificationService`](app/Services/NotificationService.php), [`GenerateVapidKeys`](app/Console/Commands/GenerateVapidKeys.php) | ✅ |
| 10.4 | Email Service (SMTP) | [`EmailService`](app/Services/EmailService.php) | ✅ |
| 10.5 | Audit Logging | [`LogService`](app/Services/LogService.php), [`AuditLog`](app/Models/AuditLog.php) | ✅ |
| 10.6 | Unit-Based Access Control | [`UnitAccessTrait`](app/Traits/UnitAccessTrait.php) | ✅ |

---

## 🗄️ Database Tables (58 Migrations)

### Core Tables
| Table | Model | Keterangan |
|-------|-------|------------|
| `users` | [`User`](app/Models/User.php) | Pengguna (nasabah + staf) |
| `roles` | [`Role`](app/Models/Role.php) | Role/permission |
| `accounts` | [`Account`](app/Models/Account.php) | Rekening bank |
| `transactions` | [`Transaction`](app/Models/Transaction.php) | Log transaksi |
| `customer_profiles` | [`CustomerProfile`](app/Models/CustomerProfile.php) | Profil nasabah |
| `units` | [`Unit`](app/Models/Unit.php) | Struktur organisasi |

### Produk Keuangan
| Table | Model |
|-------|-------|
| `loans` | [`Loan`](app/Models/Loan.php) |
| `loan_products` | [`LoanProduct`](app/Models/LoanProduct.php) |
| `loan_installments` | [`LoanInstallment`](app/Models/LoanInstallment.php) |
| `deposit_products` | [`DepositProduct`](app/Models/DepositProduct.php) |
| `goal_savings_details` | [`GoalSavingsDetail`](app/Models/GoalSavingsDetail.php) |
| `investment_products` | (inline) |

### Kartu & Penarikan
| Table | Model |
|-------|-------|
| `cards` | [`Card`](app/Models/Card.php) |
| `card_requests` | [`CardRequest`](app/Models/CardRequest.php) |
| `withdrawal_accounts` | [`WithdrawalAccount`](app/Models/WithdrawalAccount.php) |
| `withdrawal_requests` | [`WithdrawalRequest`](app/Models/WithdrawalRequest.php) |

### Personal Finance (Budgeting)
| Table | Model |
|-------|-------|
| `expense_categories` | [`ExpenseCategory`](app/Models/ExpenseCategory.php) |
| `expense_records` | [`ExpenseRecord`](app/Models/ExpenseRecord.php) |
| `monthly_budgets` | [`MonthlyBudget`](app/Models/MonthlyBudget.php) |

### Komunikasi & Sistem
| Table | Model |
|-------|-------|
| `notifications` | [`Notification`](app/Models/Notification.php) |
| `push_subscriptions` | [`PushSubscription`](app/Models/PushSubscription.php) |
| `secure_messages` | [`SecureMessage`](app/Models/SecureMessage.php) |
| `support_tickets` | [`Ticket`](app/Models/Ticket.php) |
| `support_ticket_replies` | [`TicketMessage`](app/Models/TicketMessage.php) |
| `announcements` | [`Announcement`](app/Models/Announcement.php) |
| `faqs` | [`Faq`](app/Models/Faq.php) |
| `audit_logs` | [`AuditLog`](app/Models/AuditLog.php) |
| `system_logs` | [`SystemLog`](app/Models/SystemLog.php) |
| `system_configurations` | (inline) |

### Pendukung Lainnya
| Table | Keterangan |
|-------|------------|
| `beneficiaries` | Rekening penerima |
| `biller_products` | Produk tagihan |
| `digital_products` | Produk digital |
| `external_banks` | Bank eksternal |
| `loyalty_points_history` | Histori poin loyalitas |
| `scheduled_transfers` | Transfer terjadwal |
| `standing_instructions` | Instruksi berulang |
| `topup_requests` | Permintaan isi saldo |
| `user_otps` | OTP codes |
| `user_sessions` | Sesi login |
| `uploaded_documents` | Dokumen upload |
| `login_history` | Histori login |
| `password_resets` | Token reset password |
| `interest_accruals` | Akumulasi bunga deposito |
| `limit_increase_requests` | Permintaan kenaikan limit |
| `collection_visit_reports` | Laporan kunjungan debt collector |
| `debt_collection_assignments` | Penugasan debt collector |
| `personal_access_tokens` | API tokens (Sanctum) |

---

## 📱 Frontend Pages (72 Halaman)

### Nasabah (45 halaman)
| Halaman | File | Route |
|---------|------|-------|
| Landing Page | [`LandingPage.jsx`](resources/js/Pages/LandingPage.jsx) | `/` |
| Login | [`LoginPage.jsx`](resources/js/Pages/LoginPage.jsx) | `/login` |
| Register | [`RegisterPage.jsx`](resources/js/Pages/RegisterPage.jsx) | `/register` |
| Dashboard | [`DashboardPage.jsx`](resources/js/Pages/DashboardPage.jsx) | `/dashboard` |
| Riwayat Transaksi | [`HistoryPage.jsx`](resources/js/Pages/HistoryPage.jsx) | `/history` |
| Transfer | [`TransferPage.jsx`](resources/js/Pages/TransferPage.jsx) | `/transfer` |
| Transfer Eksternal | [`ExternalTransferPage.jsx`](resources/js/Pages/ExternalTransferPage.jsx) | `/external-transfer` |
| Transfer Terjadwal | [`ScheduledTransfersPage.jsx`](resources/js/Pages/ScheduledTransfersPage.jsx) | `/scheduled-transfers` |
| Standing Instruction | [`StandingInstructionsPage.jsx`](resources/js/Pages/StandingInstructionsPage.jsx) | `/standing-instructions` |
| Pembayaran Tagihan | [`BillPaymentPage.jsx`](resources/js/Pages/BillPaymentPage.jsx) | `/bills` |
| Produk Digital | [`DigitalProductsPage.jsx`](resources/js/Pages/DigitalProductsPage.jsx) | `/digital-products` |
| QR Payment | [`QrPaymentPage.jsx`](resources/js/Pages/QrPaymentPage.jsx) | `/qr-payment` |
| Top-up | [`TopUpPage.jsx`](resources/js/Pages/TopUpPage.jsx) | `/topup` |
| Penarikan | [`WithdrawalPage.jsx`](resources/js/Pages/WithdrawalPage.jsx) | `/withdrawal` |
| Deposito | [`DepositsPage.jsx`](resources/js/Pages/DepositsPage.jsx) | `/deposits` |
| Detail Deposito | [`DepositDetailPage.jsx`](resources/js/Pages/DepositDetailPage.jsx) | `/deposits/{id}` |
| Buka Deposito | [`OpenDepositPage.jsx`](resources/js/Pages/OpenDepositPage.jsx) | `/deposits/open` |
| Pinjaman Saya | [`MyLoansPage.jsx`](resources/js/Pages/MyLoansPage.jsx) | `/my-loans` |
| Detail Pinjaman | [`MyLoanDetailPage.jsx`](resources/js/Pages/MyLoanDetailPage.jsx) | `/my-loans/{id}` |
| Produk Pinjaman | [`LoanProductsPage.jsx`](resources/js/Pages/LoanProductsPage.jsx) | `/loan-products` |
| Pengajuan Pinjaman | [`LoanApplicationPage.jsx`](resources/js/Pages/LoanApplicationPage.jsx) | `/loan-application/{id}` |
| Investasi | [`InvestmentPage.jsx`](resources/js/Pages/InvestmentPage.jsx) | `/investments` |
| Goal Savings | [`GoalSavingsPage.jsx`](resources/js/Pages/GoalSavingsPage.jsx) | `/goal-savings` |
| Kartu | [`CardsPage.jsx`](resources/js/Pages/CardsPage.jsx) | `/profile/cards` |
| Request Kartu | [`CardRequestsPage.jsx`](resources/js/Pages/CardRequestsPage.jsx) | — |
| E-Wallet | [`EWalletPage.jsx`](resources/js/Pages/EWalletPage.jsx) | `/ewallet` |
| Loyalitas | [`LoyaltyPointsPage.jsx`](resources/js/Pages/LoyaltyPointsPage.jsx) | `/loyalty` |
| Profil | [`ProfilePage.jsx`](resources/js/Pages/ProfilePage.jsx) | `/profile` |
| Info Profil | [`ProfileInfoPage.jsx`](resources/js/Pages/ProfileInfoPage.jsx) | `/profile/info` |
| Ubah PIN | [`ChangePinPage.jsx`](resources/js/Pages/ChangePinPage.jsx) | `/profile/change-pin` |
| Lupa PIN | [`ForgotPinPage.jsx`](resources/js/Pages/ForgotPinPage.jsx) | `/profile/forgot-pin` |
| Ubah Password | [`ChangePasswordPage.jsx`](resources/js/Pages/ChangePasswordPage.jsx) | `/profile/change-password` |
| Penerima | [`BeneficiaryListPage.jsx`](resources/js/Pages/BeneficiaryListPage.jsx) | `/profile/beneficiaries` |
| Rekening Penarikan | [`WithdrawalAccountsPage.jsx`](resources/js/Pages/WithdrawalAccountsPage.jsx) | `/profile/withdrawal-accounts` |
| Notifikasi | [`NotificationsPage.jsx`](resources/js/Pages/NotificationsPage.jsx) | `/notifications` |
| Pesan Aman | [`SecureMessagesPage.jsx`](resources/js/Pages/SecureMessagesPage.jsx) | `/messages` |
| Tiket | [`TicketsPage.jsx`](resources/js/Pages/TicketsPage.jsx) | `/tickets` |
| Detail Tiket | [`TicketDetailPage.jsx`](resources/js/Pages/TicketDetailPage.jsx) | `/tickets/{id}` |
| FAQ | [`FaqPage.jsx`](resources/js/Pages/FaqPage.jsx) | `/faq` |
| Pengumuman | [`AnnouncementsPage.jsx`](resources/js/Pages/AnnouncementsPage.jsx) | `/announcements` |
| Penutupan Rekening | [`AccountClosurePage.jsx`](resources/js/Pages/AccountClosurePage.jsx) | `/account-closure` |
| **Budgeting** | [`BudgetingPage.jsx`](resources/js/Pages/BudgetingPage.jsx) | `/budgeting` |
| **Catatan Pengeluaran** | [`ExpenseListPage.jsx`](resources/js/Pages/ExpenseListPage.jsx) | `/expense/records` |
| **Atur Budget** | [`BudgetSetupPage.jsx`](resources/js/Pages/BudgetSetupPage.jsx) | `/expense/budgets` |
| **Analisis Pengeluaran** | [`ExpenseAnalyticsPage.jsx`](resources/js/Pages/ExpenseAnalyticsPage.jsx) | `/expense/analytics` |

### Admin (27 halaman)
| Halaman | File | Route |
|---------|------|-------|
| Dashboard Admin | [`AdminDashboardPage.jsx`](resources/js/Pages/AdminDashboardPage.jsx) | `/admin/dashboard` |
| Daftar Nasabah | [`CustomerListPage.jsx`](resources/js/Pages/CustomerListPage.jsx) | `/admin/customers` |
| Tambah Nasabah | [`CustomerAddPage.jsx`](resources/js/Pages/CustomerAddPage.jsx) | `/admin/customers/add` |
| Detail Nasabah | [`CustomerDetailPage.jsx`](resources/js/Pages/CustomerDetailPage.jsx) | `/admin/customers/{id}` |
| Edit Nasabah | [`CustomerEditPage.jsx`](resources/js/Pages/CustomerEditPage.jsx) | `/admin/customers/edit/{id}` |
| Daftar Staf | [`StaffListPage.jsx`](resources/js/Pages/StaffListPage.jsx) | `/admin/staff` |
| Edit Staf | [`StaffEditPage.jsx`](resources/js/Pages/StaffEditPage.jsx) | `/admin/staff/{id}/edit` |
| Unit/Cabang | [`AdminUnitsPage.jsx`](resources/js/Pages/AdminUnitsPage.jsx) | `/admin/units` |
| Pengajuan Pinjaman | [`LoanApplicationsPage.jsx`](resources/js/Pages/LoanApplicationsPage.jsx) | `/admin/loan-applications` |
| Detail Pengajuan | [`LoanApplicationDetailPage.jsx`](resources/js/Pages/LoanApplicationDetailPage.jsx) | `/admin/loan-applications/{id}` |
| Produk Pinjaman | [`LoanProductsListPage.jsx`](resources/js/Pages/LoanProductsListPage.jsx) | `/admin/loan-products` |
| Daftar Deposito | [`AdminDepositsListPage.jsx`](resources/js/Pages/AdminDepositsListPage.jsx) | `/admin/deposit-accounts` |
| Produk Deposito | [`DepositProductsPage.jsx`](resources/js/Pages/DepositProductsPage.jsx) | `/admin/deposit-products` |
| Setor Tunai (Teller) | [`AdminTellerDepositPage.jsx`](resources/js/Pages/AdminTellerDepositPage.jsx) | `/admin/teller-deposit` |
| Bayar Cicilan (Teller) | [`AdminTellerLoanPaymentPage.jsx`](resources/js/Pages/AdminTellerLoanPaymentPage.jsx) | `/admin/teller-loan-payment` |
| Struk | [`PrintableReceiptPage.jsx`](resources/js/Pages/PrintableReceiptPage.jsx) | `/admin/print-receipt/{id}` |
| Request Top-up | [`AdminTopUpRequestsPage.jsx`](resources/js/Pages/AdminTopUpRequestsPage.jsx) | `/admin/topup-requests` |
| Request Withdrawal | [`AdminWithdrawalRequestsPage.jsx`](resources/js/Pages/AdminWithdrawalRequestsPage.jsx) | `/admin/withdrawal-requests` |
| Request Kartu | [`AdminBuildPage.jsx`](resources/js/Pages/AdminBuildPage.jsx) | — |
| Daftar Pinjaman | [`AdminLoansListPage.jsx`](resources/js/Pages/AdminLoansListPage.jsx) | `/admin/loan-accounts` |
| Transaksi | [`TransactionListPage.jsx`](resources/js/Pages/TransactionListPage.jsx) | `/admin/transactions` |
| Laporan | [`ReportsPage.jsx`](resources/js/Pages/ReportsPage.jsx) | `/admin/reports` |
| Audit Log | [`AdminAuditLogPage.jsx`](resources/js/Pages/AdminAuditLogPage.jsx) | `/admin/audit-log` |
| Pengaturan | [`SettingsPage.jsx`](resources/js/Pages/SettingsPage.jsx) | `/admin/settings` |
| Notifikasi Admin | [`AdminNotificationsPage.jsx`](resources/js/Pages/AdminNotificationsPage.jsx) | `/admin/notifications` |
| Marketing Dashboard | [`MarketingDashboardPage.jsx`](resources/js/Pages/MarketingDashboardPage.jsx) | — |
| Debt Collection | [`DebtCollectionPage.jsx`](resources/js/Pages/DebtCollectionPage.jsx) | — |
