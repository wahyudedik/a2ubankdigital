# Implementasi Fitur Personal Budgeting

## 📋 Ringkasan

Fitur **Personal Budgeting** telah berhasil diimplementasikan ke dalam aplikasi A2U Bank Digital. Fitur ini memungkinkan nasabah untuk mencatat, mengelola, dan menganalisis pengeluaran pribadi mereka secara terintegrasi dengan transaksi perbankan yang sudah ada.

---

## 🗂️ File yang Dibuat/Diupdate

### File Baru

| File | Keterangan |
|------|------------|
| `database/migrations/2026_08_02_000001_create_expense_categories_table.php` | Migration untuk tabel kategori pengeluaran |
| `database/migrations/2026_08_02_000002_create_expense_records_table.php` | Migration untuk tabel catatan pengeluaran |
| `database/migrations/2026_08_02_000003_create_monthly_budgets_table.php` | Migration untuk tabel budget bulanan |
| `app/Models/ExpenseCategory.php` | Model untuk kategori pengeluaran |
| `app/Models/ExpenseRecord.php` | Model untuk catatan pengeluaran |
| `app/Models/MonthlyBudget.php` | Model untuk budget bulanan |
| `app/Services/ExpenseService.php` | Service untuk logic bisnis budgeting |
| `app/Http/Controllers/User/ExpenseController.php` | Controller untuk API budgeting |
| `database/seeders/ExpenseCategorySeeder.php` | Seeder untuk kategori default |
| `resources/js/Pages/BudgetingPage.jsx` | Halaman dashboard budgeting |
| `resources/js/Pages/ExpenseListPage.jsx` | Halaman daftar pengeluaran |
| `resources/js/Pages/BudgetSetupPage.jsx` | Halaman pengaturan budget |
| `resources/js/Pages/ExpenseAnalyticsPage.jsx` | Halaman analisis pengeluaran |
| `plans/feature-personal-budgeting.md` | Dokumen rancangan fitur |

### File yang Diupdate

| File | Perubahan |
|------|-----------|
| `routes/web.php` | Menambahkan 4 route Inertia untuk halaman budgeting |
| `routes/ajax.php` | Menambahkan 14 route AJAX untuk API budgeting |
| `app/Http/Controllers/Inertia/UserPageController.php` | Menambahkan 4 method untuk render halaman |
| `app/Http/Controllers/User/TransactionController.php` | Menambahkan auto-import expense record |
| `resources/js/Pages/DashboardPage.jsx` | Menambahkan link Budgeting di menu layanan |

---

## 🗄️ Database Schema

### Tabel `expense_categories`
```sql
- id (BIGINT, PK)
- user_id (BIGINT, FK -> users)
- name (VARCHAR 100)
- icon (VARCHAR 50) - emoji icon
- color (VARCHAR 7) - hex color code
- is_default (BOOLEAN) - kategori default sistem
- parent_id (BIGINT, FK -> expense_categories) - sub-kategori
- created_at, updated_at (TIMESTAMP)
```

### Tabel `expense_records`
```sql
- id (BIGINT, PK)
- user_id (BIGINT, FK -> users)
- transaction_id (BIGINT, FK -> transactions) - link ke transaksi
- category_id (BIGINT, FK -> expense_categories)
- amount (DECIMAL 15,2)
- description (TEXT)
- expense_date (DATE)
- receipt_path (VARCHAR 255) - foto struk
- is_auto_imported (BOOLEAN) - auto dari transaksi
- created_at, updated_at (TIMESTAMP)
```

### Tabel `monthly_budgets`
```sql
- id (BIGINT, PK)
- user_id (BIGINT, FK -> users)
- category_id (BIGINT, FK -> expense_categories)
- year (SMALLINT)
- month (TINYINT)
- budget_amount (DECIMAL 15,2)
- alert_threshold (TINYINT, default 80)
- created_at, updated_at (TIMESTAMP)
- UNIQUE KEY (user_id, category_id, year, month)
```

---

## 🔌 API Endpoints

### Kategori (`/ajax/user/expense/categories`)
| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/ajax/user/expense/categories` | Ambil semua kategori |
| POST | `/ajax/user/expense/categories` | Buat kategori baru |
| PUT | `/ajax/user/expense/categories/{id}` | Update kategori |
| DELETE | `/ajax/user/expense/categories/{id}` | Hapus kategori |

### Catatan Pengeluaran (`/ajax/user/expense/records`)
| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/ajax/user/expense/records` | Ambil semua catatan (dengan filter) |
| POST | `/ajax/user/expense/records` | Buat catatan baru |
| PUT | `/ajax/user/expense/records/{id}` | Update catatan |
| DELETE | `/ajax/user/expense/records/{id}` | Hapus catatan |

### Budget (`/ajax/user/expense/budgets`)
| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/ajax/user/expense/budgets` | Ambil budget bulanan |
| POST | `/ajax/user/expense/budgets` | Set/update budget |
| DELETE | `/ajax/user/expense/budgets/{id}` | Hapus budget |

### Analisis (`/ajax/user/expense/`)
| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/ajax/user/expense/summary` | Ringkasan bulanan |
| GET | `/ajax/user/expense/trend` | Tren 6 bulan terakhir |
| GET | `/ajax/user/expense/insights` | Insights & rekomendasi |

---

## 🖥️ Halaman Frontend

### 1. BudgetingPage (`/budgeting`)
- Dashboard utama budgeting
- Ringkasan pengeluaran vs budget
- Progress bar per kategori
- Quick actions (catat pengeluaran, atur budget, analisis)
- Pie chart distribusi pengeluaran
- Bar chart tren 6 bulan
- Insights & rekomendasi

### 2. ExpenseListPage (`/expense/records`)
- Daftar semua catatan pengeluaran
- Filter berdasarkan kategori, tanggal, search
- CRUD catatan pengeluaran
- Pagination
- Badge untuk auto-imported records

### 3. BudgetSetupPage (`/expense/budgets`)
- Pengaturan budget per kategori
- Progress bar real-time
- Alert threshold indicator
- Tips budgeting

### 4. ExpenseAnalyticsPage (`/expense/analytics`)
- Analisis mendalam pengeluaran
- Doughnut chart distribusi
- Bar chart pengeluaran vs budget
- Line chart tren 6 bulan
- Detail table per kategori
- Insights & rekomendasi

---

## 🔄 Integrasi dengan Transaksi

### Auto-Import Logic
Setelah transaksi berhasil (transfer internal, pembayaran tagihan, dll), sistem akan otomatis:

1. Mengecek apakah transaksi sudah pernah di-import
2. Mengkategorikan transaksi berdasarkan deskripsi/merchant
3. Membuat `expense_record` baru dengan `is_auto_imported = true`
4. Menghitung total spending per kategori untuk budget checking

### Kategori Auto-Import
| Kategori | Keyword Matching |
|----------|------------------|
| Makanan & Minuman | makan, minum, restoran, cafe, food, groceries, Indomaret |
| Transportasi | bensin, parkir, tol, ojek, grab, gojek |
| Rumah Tangga | listrik, air, internet, sewa, PLN, PDAM |
| Hiburan | netflix, spotify, game, bioskop |
| Kesehatan | obat, dokter, rumah sakit |
| Pendidikan | kursus, sekolah, universitas, buku |
| Tabungan & Investasi | tabungan, investasi, saham, deposito |
| Lainnya | Default jika tidak ada keyword match |

---

## 🚀 Setup & Installation

### 1. Jalankan Migration
```bash
php artisan migrate
```

### 2. Jalankan Seeder (opsional, untuk data existing)
```bash
php artisan db:seed --class=ExpenseCategorySeeder
```

### 3. Build Frontend
```bash
npm run build
```

---

## 🧪 Testing

### Akun Testing
- Nasabah 1: `customer1@example.com` / `customer123`
- Nasabah 2: `customer2@example.com` / `customer123`
- PIN: `123456`

### Test Cases
1. **Auto-Import**: Lakukan transfer internal → cek apakah expense record otomatis terbuat
2. **Manual Entry**: Tambah catatan pengeluaran manual → cek muncul di daftar
3. **Budget Setting**: Set budget untuk kategori → cek progress bar berubah
4. **Analytics**: Lihat halaman analisis → cek chart dan insights muncul
5. **Alert**: Set budget rendah → lakukan transaksi → cek notifikasi muncul

---

## 📊 Kategori Default

| # | Kategori | Ikon | Warna |
|---|----------|------|-------|
| 1 | Makanan & Minuman | 🍔 | #FF6B6B |
| 2 | Transportasi | 🚗 | #4ECDC4 |
| 3 | Rumah Tangga | 🏠 | #45B7D1 |
| 4 | Belanja | 🛒 | #96CEB4 |
| 5 | Hiburan | 🎭 | #FFEAA7 |
| 6 | Kesehatan | 🏥 | #DDA0DD |
| 7 | Pendidikan | 📚 | #98D8C8 |
| 8 | Tabungan & Investasi | 💰 | #F7DC6F |
| 9 | Lainnya | 🎁 | #BB8FCE |

---

## 🔗 Navigasi

### Menu di Dashboard
Budgeting sudah ditambahkan ke menu "Layanan & Fitur" di DashboardPage dengan ikon Wallet.

### URL Halaman
| Halaman | URL |
|---------|-----|
| Dashboard Budgeting | `/budgeting` |
| Daftar Pengeluaran | `/expense/records` |
| Atur Budget | `/expense/budgets` |
| Analisis | `/expense/analytics` |

---

## 📝 Catatan Teknis

1. **Soft Delete**: Tabel `expense_categories` tidak menggunakan soft delete, tapi kategori default tidak bisa dihapus
2. **Rate Limiting**: Semua AJAX routes memiliki rate limit 120 request/menit
3. **CSRF Protection**: Semua routes terproteksi CSRF via cookie XSRF-TOKEN
4. **Error Handling**: Auto-import menggunakan try-catch agar tidak mengganggu transaksi utama
5. **Performance**: Query dioptimasi dengan index pada kolom yang sering di-query

---

## 🔮 Future Enhancements

1. **Export Laporan**: Export ke PDF/CSV
2. **Receipt Upload**: Upload foto struk pengeluaran
3. **Recurring Expenses**: Pengeluaran berulang otomatis
4. **Multi-Currency**: Support mata uang asing
5. **AI Categorization**: Machine learning untuk auto-categorize lebih akurat
6. **Bill Splitting**: Fitur split bill dengan teman
7. **Tax Report**: Laporan untuk klaim pajak
