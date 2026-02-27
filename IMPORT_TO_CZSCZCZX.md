# Import Data ke Database czsczczx

## 📊 Database Anda: `czsczczx`

File `.env` sudah dikonfigurasi untuk database `czsczczx`.

## 🔄 Import Data dari SQL Dump

### Cara 1: Via phpMyAdmin (Termudah)

1. Buka phpMyAdmin: `http://localhost/phpmyadmin`

2. Pilih database `czsczczx` di sidebar kiri

3. Klik tab **"Import"**

4. Klik **"Choose File"** dan pilih: `a2uj2723_au2.sql`

5. Scroll ke bawah, klik **"Go"**

6. Tunggu sampai selesai (mungkin 1-2 menit)

7. Seharusnya muncul pesan sukses

### Cara 2: Via SQL Query (Edit Database Name)

File SQL dump menggunakan nama database `a2uj2723_au2`, tapi kita perlu import ke `czsczczx`.

**Opsi A: Edit file SQL**
1. Buka file `a2uj2723_au2.sql` dengan text editor
2. Cari baris: `-- Database: \`a2uj2723_au2\``
3. Ganti dengan: `-- Database: \`czsczczx\``
4. Save
5. Import via phpMyAdmin

**Opsi B: Import langsung (phpMyAdmin akan ignore database name)**
1. Pilih database `czsczczx` di phpMyAdmin
2. Import file `a2uj2723_au2.sql`
3. phpMyAdmin akan import ke database yang dipilih

### Cara 3: Via Command Line (XAMPP)

```bash
# Masuk ke folder MySQL XAMPP
cd C:\xampp\mysql\bin

# Import ke database czsczczx
mysql.exe -u root czsczczx < E:\PROJEKU\a2ubankdigital.my.id\a2uj2723_au2.sql
```

### Cara 4: Via Command Line (Laragon)

```bash
# Buka Laragon Terminal

# Import
mysql -u root czsczczx < a2uj2723_au2.sql
```

## ✅ Verifikasi Import

Setelah import, cek apakah data sudah masuk:

### Via phpMyAdmin:
1. Pilih database `czsczczx`
2. Lihat daftar tabel (seharusnya ada 30+ tabel)
3. Klik tabel `users`
4. Lihat data (seharusnya ada beberapa user)

### Via Test Script:
Buka di browser:
```
http://localhost/app/test_db_connection.php
```

Harusnya menampilkan:
```
=== Testing Database Connection ===

1. Checking .env file...
   ✓ .env file found

2. Loading config.php...
   ✓ Config loaded successfully

3. Checking environment variables...
   DB_HOST: localhost
   DB_NAME: czsczczx  ✓
   DB_USER: root
   DB_PASS: ***

4. Testing database connection...
   ✓ Database connected successfully!
   MySQL Version: 10.x.x-MariaDB

5. Checking database tables...
   Found 30+ tables

6. Checking users table...
   Total users: 3
   Active users: 3

7. Sample active users:
   - ID: 84, Email: xxx@xxx.com
   - ID: 85, Email: xxx@xxx.com
   - ID: 88, Email: xxx@xxx.com

=== ✓ ALL TESTS PASSED ===
```

## 🔐 Test Login

Setelah data ter-import, cek user credentials:

### Via phpMyAdmin:
```sql
SELECT id, email, full_name, role_id, status 
FROM users 
WHERE status = 'ACTIVE' 
LIMIT 5;
```

### Via Frontend:
1. Buka: `http://localhost:5173/login`
2. Gunakan email dari database
3. Password: (cek di database atau reset via forgot password)

## ❌ Troubleshooting

### Error: "Table 'czsczczx.users' doesn't exist"
**Solusi:** Data belum di-import. Ulangi langkah import.

### Error: "Duplicate entry"
**Solusi:** Data sudah ada. Anda bisa:
- Skip error dan lanjutkan
- Atau drop semua tabel dulu, lalu import ulang

### Drop All Tables (Hati-hati!)
```sql
-- Via phpMyAdmin SQL tab
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS accounts, account_closure_requests, announcements, 
audit_logs, beneficiaries, biller_products, cards, collection_visit_reports,
customer_profiles, debt_collection_assignments, deposit_products, 
digital_products, external_banks, faqs, goal_savings_details, 
interest_accruals, investment_products, limit_increase_requests, loans, 
loan_installments, loan_products, login_history, loyalty_points_history,
notifications, password_resets, push_subscriptions, roles, 
scheduled_transfers, sessions, standing_instructions, support_tickets, 
ticket_messages, transactions, units, users, withdrawal_accounts, 
withdrawal_requests;
SET FOREIGN_KEY_CHECKS = 1;
```

## 📝 Konfigurasi Final

File `.env` Anda sekarang:
```env
DB_HOST="localhost"
DB_USER="root"
DB_PASS=""
DB_NAME="czsczczx"  ✓ Sesuai dengan database Anda
```

## 🎯 Checklist

- [x] Database `czsczczx` sudah ada
- [ ] Import file `a2uj2723_au2.sql` ke database `czsczczx`
- [ ] Test koneksi via `http://localhost/app/test_db_connection.php`
- [ ] Cek user credentials di database
- [ ] Test login di `http://localhost:5173/login`

## 🚀 Next Steps

Setelah import berhasil:
1. Test koneksi database
2. Test login frontend
3. Mulai development!
