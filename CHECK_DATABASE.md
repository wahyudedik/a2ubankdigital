# Cara Cek Koneksi Database

## 🔍 Masalah yang Ditemukan

Di file `.env` Anda, database name adalah:
```
DB_NAME="czsczczx"
```

Tapi file SQL dump Anda bernama:
```
a2uj2723_au2.sql
```

**Kemungkinan:**
1. Database `czsczczx` belum dibuat
2. Database `a2uj2723_au2` sudah ada tapi .env salah
3. Perlu import database dulu

## ✅ Langkah-langkah Pengecekan

### Opsi 1: Via phpMyAdmin (Paling Mudah)

1. Buka phpMyAdmin di browser:
   - XAMPP: `http://localhost/phpmyadmin`
   - Laragon: `http://localhost/phpmyadmin`

2. Cek database yang ada:
   - Lihat sidebar kiri
   - Apakah ada database `czsczczx`?
   - Apakah ada database `a2uj2723_au2`?

3. **Jika database belum ada:**
   - Klik "New" di sidebar
   - Nama database: `a2uj2723_au2`
   - Collation: `utf8mb4_general_ci`
   - Klik "Create"

4. **Import database:**
   - Pilih database `a2uj2723_au2`
   - Klik tab "Import"
   - Choose file: `a2uj2723_au2.sql`
   - Klik "Go"

5. **Update .env:**
   ```env
   DB_NAME="a2uj2723_au2"
   ```

### Opsi 2: Via Command Line (XAMPP)

```bash
# Masuk ke folder XAMPP MySQL
cd C:\xampp\mysql\bin

# Login ke MySQL
mysql.exe -u root -p

# Cek database yang ada
SHOW DATABASES;

# Jika database belum ada, buat:
CREATE DATABASE a2uj2723_au2 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

# Keluar
EXIT;

# Import database
mysql.exe -u root -p a2uj2723_au2 < E:\PROJEKU\a2ubankdigital.my.id\a2uj2723_au2.sql
```

### Opsi 3: Via Command Line (Laragon)

```bash
# Buka Laragon Terminal (klik kanan Laragon > Terminal)

# Login ke MySQL
mysql -u root -p

# Cek database yang ada
SHOW DATABASES;

# Jika database belum ada, buat:
CREATE DATABASE a2uj2723_au2 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

# Keluar
EXIT;

# Import database
mysql -u root -p a2uj2723_au2 < a2uj2723_au2.sql
```

## 🧪 Test Koneksi Database

Setelah database ready, test koneksi:

### Via Browser:
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
   DB_NAME: a2uj2723_au2
   DB_USER: root
   DB_PASS: ***

4. Testing database connection...
   ✓ PDO object exists
   ✓ Database connected successfully!
   MySQL Version: 10.x.x-MariaDB

5. Checking database tables...
   Found 30+ tables

6. Checking users table...
   Total users: X
   Active users: X

7. Sample active users:
   - ID: 1, Email: admin@example.com, ...

=== ✓ ALL TESTS PASSED ===
```

## ❌ Jika Masih Error

### Error: "Access denied for user 'root'@'localhost'"

**Solusi:**
1. Cek password MySQL root Anda
2. Update di `.env`:
   ```env
   DB_PASS="your_mysql_password"
   ```

### Error: "Unknown database 'czsczczx'"

**Solusi:**
1. Database belum dibuat
2. Ikuti langkah import database di atas
3. Update `.env`:
   ```env
   DB_NAME="a2uj2723_au2"
   ```

### Error: "SQLSTATE[HY000] [2002] No connection could be made"

**Solusi:**
1. MySQL belum running
2. Start MySQL:
   - XAMPP: Start MySQL di control panel
   - Laragon: Start All

## 📝 Konfigurasi .env yang Benar

Setelah database ready, `.env` Anda seharusnya:

```env
APP_ENV=development

DB_HOST="localhost"
DB_USER="root"
DB_PASS=""                    # Kosong jika tidak ada password
DB_NAME="a2uj2723_au2"        # Nama database yang benar

JWT_SECRET="e4c8f1c6b9f74c4e7a6d8f3a2b1c9e0f5d7a8c9b0e1f2a3d4c5b6a7e8f9c0d1"
JWT_ISSUER="a2ubankdigital.my.id"
JWT_AUDIENCE="a2ubankdigital.my.id"

ALLOWED_ORIGINS="http://localhost:5173,https://a2ubankdigital.my.id,exp://192.168.1.47:8081,http://localhost:8081"

# ... rest of config
```

## ✅ Checklist

- [ ] MySQL/MariaDB running
- [ ] Database `a2uj2723_au2` created
- [ ] Database imported from `a2uj2723_au2.sql`
- [ ] `.env` updated dengan DB_NAME yang benar
- [ ] Test via `http://localhost/app/test_db_connection.php`
- [ ] Semua test passed

## 🎯 Next Step

Setelah database connected:
1. Test login di frontend: `http://localhost:5173/login`
2. Cek user credentials di database
3. Mulai development!
