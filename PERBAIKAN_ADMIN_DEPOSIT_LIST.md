# Perbaikan Admin Deposit List - Keuntungan RpNaN

## Masalah
Di halaman admin "Dasbor Deposito Nasabah" (`/admin/deposit-accounts`), kolom "Keuntungan" menampilkan **RpNaN**.

## Penyebab
Method `AdminPageController::depositsAccounts()` tidak menghitung dan mengirim data `interest_earned` (keuntungan) ke frontend.

## Solusi yang Dilakukan

### Update AdminPageController::depositsAccounts()

#### 1. Tambah Perhitungan Bunga
Menghitung bunga deposito untuk setiap record:
```php
$principal = $a->balance;
$interestRate = $a->depositProduct?->interest_rate_pa ?? 0;
$months = $a->depositProduct?->tenor_months ?? 0;
$interestEarned = $principal * ($interestRate / 100) * ($months / 12);
```

#### 2. Tambah Field interest_earned
Menambahkan field `interest_earned` ke data yang dikirim:
```php
return [
    'id' => $a->id,
    'customer_name' => $a->user?->full_name,
    'account_number' => $a->account_number,
    'product_name' => $a->depositProduct?->product_name,
    'balance' => (float)$a->balance,
    'principal' => (float)$principal,
    'interest_earned' => (float)$interestEarned,  // ✅ Ditambahkan
    'maturity_date' => $a->maturity_date,
    'status' => $a->status,
    'is_near_maturity' => $isNearMaturity
];
```

#### 3. Tambah Filter Pencarian
Menambahkan filter untuk search by nama nasabah atau nomor rekening:
```php
if ($search) {
    $query->where(function($q) use ($search) {
        $q->whereHas('user', function($userQuery) use ($search) {
            $userQuery->where('full_name', 'like', "%{$search}%");
        })->orWhere('account_number', 'like', "%{$search}%");
    });
}
```

#### 4. Tambah Filter Status
Menambahkan 3 filter status:
- **active**: Deposito yang masih aktif
- **matured**: Deposito yang sudah jatuh tempo (maturity_date <= sekarang)
- **near_maturity**: Deposito yang akan jatuh tempo dalam 30 hari

```php
if ($status === 'active') {
    $query->where('status', 'ACTIVE');
} elseif ($status === 'matured') {
    $query->where('status', 'ACTIVE')
          ->where('maturity_date', '<=', now());
} elseif ($status === 'near_maturity') {
    $query->where('status', 'ACTIVE')
          ->whereBetween('maturity_date', [now(), now()->addDays(30)]);
}
```

#### 5. Tambah Summary Statistics
Menghitung statistik untuk KPI cards:
```php
$summary = [
    'totalActiveBalance' => $allActiveDeposits->sum('balance'),
    'totalDeposits' => $allActiveDeposits->count(),
    'maturingThisMonth' => $allActiveDeposits->filter(function($a) {
        return $a->maturity_date && 
               $a->maturity_date->between(now()->startOfMonth(), now()->endOfMonth());
    })->count()
];
```

#### 6. Tambah Indikator Near Maturity
Menambahkan flag untuk deposito yang akan jatuh tempo dalam 30 hari:
```php
$isNearMaturity = $a->maturity_date && 
                  $a->maturity_date->between(now(), now()->addDays(30));
```

## Struktur Data yang Dikirim ke Frontend

```php
[
    'deposits' => [
        [
            'id' => 57,
            'customer_name' => 'Rizky Pratama',
            'account_number' => 'DEP17710163844448',
            'product_name' => 'Deposito 1 Bulan',
            'balance' => 1000000.0,
            'principal' => 1000000.0,
            'interest_earned' => 4166.67,      // ✅ Keuntungan
            'maturity_date' => '2026-05-25',
            'status' => 'ACTIVE',
            'is_near_maturity' => true
        ],
        // ...
    ],
    'summary' => [
        'totalActiveBalance' => 2000000.0,
        'totalDeposits' => 2,
        'maturingThisMonth' => 2
    ]
]
```

## Fitur yang Tersedia

### 1. KPI Cards
- **Total Dana Aktif**: Total saldo semua deposito aktif
- **Jumlah Deposito**: Total jumlah deposito aktif
- **Jatuh Tempo Bulan Ini**: Jumlah deposito yang jatuh tempo bulan ini

### 2. Filter Status
- **Aktif**: Semua deposito dengan status ACTIVE
- **Segera Jatuh Tempo**: Deposito yang akan jatuh tempo dalam 30 hari
- **Telah Jatuh Tempo**: Deposito yang sudah melewati tanggal jatuh tempo

### 3. Pencarian
- Cari berdasarkan nama nasabah
- Cari berdasarkan nomor rekening

### 4. Indikator Visual
- Baris dengan background kuning untuk deposito yang segera jatuh tempo
- Icon jam untuk deposito near maturity

## Cara Testing

1. **Login sebagai admin**
2. **Buka menu** "Manajemen Deposito" → "Daftar Deposito"
3. **URL**: `/admin/deposit-accounts`
4. **Seharusnya menampilkan**:
   - ✅ KPI Cards dengan data yang benar
   - ✅ Kolom "Keuntungan" menampilkan nilai Rupiah (bukan RpNaN)
   - ✅ Filter "Aktif", "Segera Jatuh Tempo", "Telah Jatuh Tempo" berfungsi
   - ✅ Search box berfungsi untuk cari nama atau nomor rekening
   - ✅ Baris kuning untuk deposito yang segera jatuh tempo

## Contoh Tampilan

### Tabel Deposito:
| Nasabah | No. Rekening | Produk | Pokok | Keuntungan | Jatuh Tempo |
|---------|--------------|--------|-------|------------|-------------|
| Rizky Pratama | DEP17710163844448 | Deposito 1 Bulan | Rp1.000.000 | **Rp4.167** | 25/5/2026 |
| Rizky Pratama | DEP17710184549073 | Deposito 1 Bulan | Rp1.000.000 | **Rp4.167** | 25/5/2026 |

### KPI Cards:
- **Total Dana Aktif**: Rp2.000.000
- **Jumlah Deposito**: 2
- **Jatuh Tempo Bulan Ini**: 2

## Files Modified
- `app/Http/Controllers/Inertia/AdminPageController.php` - Update depositsAccounts() method

## Status
✅ **SELESAI** - Backend sudah diperbaiki dengan perhitungan bunga, filter, dan summary statistics
