# 🔒 Rencana Perbaikan: Unit-Based Access Control

## Masalah

Saat ini **semua staf** (kecuali Super Admin) bisa melihat data nasabah dari **semua unit/cabang**. Client meminta agar data hanya bisa diakses oleh staf yang sesuai dengan unit/cabang mereka. Hanya **Super Admin** yang boleh melihat semua data.

## Temuan Analisis

### Struktur Unit
```
KANTOR_PUSAT (HQ-001)
├── Cabang Jakarta (JAK-001) ← Kepala Cabang
│   ├── Unit Layanan Jakarta 1 (JAK-002) ← Kepala Unit, Teller, Marketing, CS
│   └── Unit Layanan Jakarta 2 (JAK-003) ← Kepala Unit, Teller, Marketing, CS
├── Cabang Surabaya (SBY-001) ← Kepala Cabang
│   ├── Unit Layanan Surabaya 1 (SBY-002)
│   └── Unit Layanan Surabaya 2 (SBY-003)
└── Cabang Bandung (BDG-001) ← Kepala Cabang
    ├── Unit Layanan Bandung 1 (BDG-002)
    └── Unit Layanan Bandung 2 (BDG-003)
```

### Role Hierarchy
| ID | Role | Akses Unit |
|----|------|-----------|
| 1 | Super Admin | **SEMUA** (bypass filter) |
| 2 | Kepala Cabang | Cabang + semua sub-unit di cabangnya |
| 3 | Kepala Unit | Hanya unitnya saja |
| 4 | Marketing | Hanya unitnya saja |
| 5 | Teller | Hanya unitnya saja |
| 6 | Customer Service | Hanya unitnya saja |
| 7 | Analis Kredit | Hanya unitnya saja |
| 8 | Debt Collector | Hanya unitnya saja |
| 9 | Customer | Hanya data sendiri |

### Relasi Data ke Unit
- **User** (staf): `user.unit_id` → unit tempat staf bekerja
- **CustomerProfile**: `customerProfile.unit_id` → unit nasabah terdaftar
- **Account**: `account.user_id` → `user.customerProfile.unit_id`
- **Loan**: `loan.user_id` → `user.customerProfile.unit_id`
- **Transaction**: `transaction.from_account_id/to_account_id` → `account.user_id` → `user.customerProfile.unit_id`

### Controller yang SUDAH Benar (ada filtering)
| Controller | Method | Status |
|-----------|--------|--------|
| `CustomerController` | `index()` | ✅ Sudah filter by `getAccessibleUnitIds()` |
| `CustomerController` | `store()` | ✅ Sudah cek unit accessibility |

### Controller yang BELUM Ada Filtering (SELESAI DIPERBAIKI)
| Controller | Method | Status |
|-----------|--------|--------|
| `AdminPageController` | `dashboard()` | ✅ Fixed |
| `AdminPageController` | `customers()` | ✅ Fixed |
| `AdminPageController` | `customerDetail()` | ✅ Fixed |
| `AdminPageController` | `loanApplications()` | ✅ Fixed |
| `AdminPageController` | `loanApplicationDetail()` | ✅ Fixed |
| `AdminPageController` | `loanAccounts()` | ✅ Fixed |
| `AdminPageController` | `staff()` | ✅ Fixed |
| `AdminPageController` | `cardRequests()` | ✅ Fixed |
| `AdminPageController` | `topupRequests()` | ✅ Fixed |
| `AdminPageController` | `withdrawalRequests()` | ✅ Fixed |
| `AdminPageController` | `transactions()` | ✅ Fixed |
| `AdminPageController` | `depositsAccounts()` | ✅ Fixed |
| `CustomerController` | `show()` | ✅ Fixed |
| `CustomerController` | `updateStatus()` | ✅ Fixed |
| `CustomerController` | `processAccountClosure()` | ✅ Fixed |
| `CustomerController` | `processCreditLimitRequest()` | ✅ Fixed |
| `CustomerController` | `getPendingAccountClosures()` | ✅ Fixed |
| `CustomerController` | `getPendingCreditLimitRequests()` | ✅ Fixed |
| `CustomerController` | `updateKycDocuments()` | ✅ Fixed |
| `CustomerController` | `reviewKyc()` | ✅ Fixed |

### Masalah Yang Sudah Diperbaiki
- **UserSeeder**: ✅ Semua staff users sudah punya `unit_id`
- **`getAccessibleUnitIds()`**: ✅ Sudah di-refactor ke `UnitAccessTrait` (reusable)

---

## Rencana Perbaikan

### T1: Buat `UnitAccessTrait`
Buat trait reusable di `app/Traits/UnitAccessTrait.php` yang berisi:
- `getAccessibleUnitIds($user)` - ambil semua unit ID yang bisa diakses user
- `collectChildUnitIds($parentUnitId, &$unitIds)` - rekursif ambil child units
- `scopeUnitAccess($query, $user)` - scope query builder untuk filter by unit

**File baru:**
- `app/Traits/UnitAccessTrait.php`

### T2: Update `UserSeeder` - Assign Unit ke Staf
Assign `unit_id` ke semua staff users:
- Budi Santoso (Kepala Cabang, role=2) → `unit_id` = Cabang Jakarta (id=2)
- Siti Rahayu (Kepala Unit, role=3) → `unit_id` = Unit Layanan Jakarta 1 (id=3)
- Dian Permata (Marketing, role=4) → `unit_id` = Unit Layanan Jakarta 1 (id=3)
- Novita Anisa (Teller, role=5) → `unit_id` = Unit Layanan Jakarta 1 (id=3)
- Rina Wulandari (CS, role=6) → `unit_id` = Unit Layanan Jakarta 1 (id=3)
- Hendra Wijaya (Analis Kredit, role=7) → `unit_id` = Unit Layanan Jakarta 1 (id=3)
- Agus Firmansyah (Debt Collector, role=8) → `unit_id` = Unit Layanan Jakarta 1 (id=3)
- Super Admin (role=1) → `unit_id` = null (bypass semua filter)

**File modifikasi:**
- `database/seeders/UserSeeder.php`
- **SQL update**: `UPDATE users SET unit_id = X WHERE id = Y` untuk data production

### T3: Update `AdminPageController` - Filtering by Unit
Tambahkan filtering by unit di SEMUA method:
- `dashboard()` → Filter stats berdasarkan unit
- `customers()` → Filter nasabah by `customerProfile.unit_id`
- `customerDetail()` → Cek apakah nasabah aksesible
- `loanApplications()` → Filter loan by user's customerProfile.unit_id
- `loanApplicationDetail()` → Cek akses
- `loanAccounts()` → Filter by unit
- `staff()` → Filter staf by unit
- `cardRequests()` → Filter by customer unit
- `topupRequests()` → Filter by customer unit
- `withdrawalRequests()` → Filter by customer unit
- `transactions()` → Filter by account user's unit
- `depositsAccounts()` → Filter by account user's unit

**File modifikasi:**
- `app/Http/Controllers/Inertia/AdminPageController.php`

### T4: Update `CustomerController` - Unit Access Check
Tambahkan unit access check di method yang belum ada:
- `show()` → Cek apakah customer aksesible
- `updateStatus()` → Cek akses sebelum update
- `processAccountClosure()` → Cek akses
- `processCreditLimitRequest()` → Cek akses
- `getPendingAccountClosures()` → Filter by unit
- `getPendingCreditLimitRequests()` → Filter by unit

Refactor `getAccessibleUnitIds()` dari private method ke trait.

**File modifikasi:**
- `app/Http/Controllers/Admin/CustomerController.php`

### T5: Verifikasi Build
- `npm run build` untuk memastikan tidak ada error

---

## Pola Filtering by Unit

### Untuk Query Eloquent (relasi langsung ke User → CustomerProfile):
```php
// Contoh: Filter nasabah by unit
$query->whereHas('customerProfile', function($q) use ($accessibleUnitIds) {
    $q->whereIn('unit_id', $accessibleUnitIds);
});
```

### Untuk Query Loan/Transaksi (perlu join ke User → CustomerProfile):
```php
// Contoh: Filter loan by unit
$query->whereHas('user.customerProfile', function($q) use ($accessibleUnitIds) {
    $q->whereIn('unit_id', $accessibleUnitIds);
});
```

### Untuk Query DB Raw (transactions, topup_requests, dll):
```php
// Contoh: Filter transaksi by unit
$query->where(function($q) use ($accessibleUnitIds) {
    $q->whereHas('from_account.user.customerProfile', fn($cq) => $cq->whereIn('unit_id', $accessibleUnitIds))
      ->orWhereHas('to_account.user.customerProfile', fn($cq) => $cq->whereIn('unit_id', $accessibleUnitIds));
});
```

### Super Admin Bypass:
```php
if ($user->role_id !== Role::SUPER_ADMIN) {
    $accessibleUnitIds = $this->getAccessibleUnitIds($user);
    // Apply filter...
}
```

---

## Data Production Safety

⚠️ **TIDAK BOLEG MIGRATE FRESH**

Untuk data production, update unit_id staff via SQL:
```sql
-- Kepala Cabang Jakarta
UPDATE users SET unit_id = 2 WHERE id = 80;

-- Kepala Unit Jakarta 1
UPDATE users SET unit_id = 3 WHERE id = 81;

-- Marketing Jakarta 1
UPDATE users SET unit_id = 3 WHERE id = 82;

-- Teller Jakarta 1
UPDATE users SET unit_id = 3 WHERE id = 79;

-- CS Jakarta 1
UPDATE users SET unit_id = 3 WHERE id = 83;

-- Analis Kredit Jakarta 1
UPDATE users SET unit_id = 3 WHERE id = 84;

-- Debt Collector Jakarta 1
UPDATE users SET unit_id = 3 WHERE id = 76;
```

---

## Checklist

- [x] T1: Buat `UnitAccessTrait.php`
- [x] T2: Update `UserSeeder.php` + SQL update production
- [x] T3: Update `AdminPageController.php` - semua method
- [x] T4: Update `CustomerController.php` - method yang belum ada cek
- [x] T5: Refactor `CustomerController` gunakan trait
- [x] T6: Verifikasi build frontend
- [x] T7: Update dokumentasi jika perlu

**Status: ✅ SEMUA SELESAI**
