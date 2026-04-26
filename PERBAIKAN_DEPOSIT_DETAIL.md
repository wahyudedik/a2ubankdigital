# Perbaikan Detail Deposito - Data Tidak Ditampilkan

## Masalah
Halaman detail deposito menampilkan data yang salah:
- **Pokok Penempatan**: RpNaN
- **Suku Bunga**: undefined% per tahun
- **Tanggal Penempatan**: Invalid Date
- **Estimasi Bunga Diperoleh**: RpNaN

## Penyebab

### 1. Data Tidak Lengkap dari Backend
Controller `UserPageController::depositDetail()` hanya mengirim raw data `Account` model yang tidak memiliki field seperti:
- `principal` (pokok penempatan)
- `interest_rate_pa` (suku bunga)
- `placement_date` (tanggal penempatan)
- `interest_earned` (estimasi bunga)

### 2. Frontend Mengharapkan Field yang Tidak Ada
`DepositDetailPage.jsx` mencoba mengakses field yang tidak ada di data `Account`:
```javascript
formatCurrency(detail.principal)  // undefined → RpNaN
detail.interest_rate_pa           // undefined → "undefined% per tahun"
new Date(detail.placement_date)   // undefined → Invalid Date
```

## Solusi yang Dilakukan

### 1. Update Backend - UserPageController::depositDetail()
Menghitung dan memformat data deposito sebelum dikirim ke frontend:

```php
public function depositDetail($depositId)
{
    $account = Account::where('user_id', Auth::id())
        ->where('account_type', 'DEPOSITO')
        ->with('depositProduct')
        ->findOrFail($depositId);

    // Calculate interest
    $principal = $account->balance;
    $interestRate = $account->depositProduct?->interest_rate_pa ?? 0;
    $months = $account->depositProduct?->tenor_months ?? 0;
    $interestEarned = $principal * ($interestRate / 100) * ($months / 12);

    // Format deposit data for frontend
    $deposit = [
        'id' => $account->id,
        'account_number' => $account->account_number,
        'product_name' => $account->depositProduct?->product_name ?? 'N/A',
        'status' => $account->status,
        'principal' => $principal,
        'interest_rate_pa' => $interestRate,
        'placement_date' => $account->created_at,
        'maturity_date' => $account->maturity_date,
        'interest_earned' => $interestEarned,
        'total_amount' => $principal + $interestEarned,
    ];

    return Inertia::render('DepositDetailPage', ['deposit' => $deposit]);
}
```

**Perhitungan Bunga:**
```
Bunga = Pokok × (Suku Bunga / 100) × (Tenor Bulan / 12)
```

Contoh:
- Pokok: Rp 1.000.000
- Suku Bunga: 5% per tahun
- Tenor: 1 bulan
- Bunga = 1.000.000 × (5 / 100) × (1 / 12) = Rp 4.166,67

### 2. Update Frontend - DepositDetailPage.jsx

#### a. Tambah Field "Total Pencairan"
Menampilkan total yang akan diterima (pokok + bunga):
```javascript
<DetailItem label="Total Pencairan" value={formatCurrency(detail.total_amount || 0)} />
```

#### b. Perbaiki Format Currency
Mengubah dari 2 desimal menjadi 0 desimal (lebih sesuai untuk Rupiah):
```javascript
// Sebelum:
const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR', 
    minimumFractionDigits: 2 
}).format(amount);
// Output: Rp1.000.000,00

// Sesudah:
const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR', 
    minimumFractionDigits: 0 
}).format(amount);
// Output: Rp1.000.000
```

#### c. Tambah Fallback untuk Data Kosong
Menambahkan fallback `|| 0` atau `|| '-'` untuk mencegah NaN atau Invalid Date:
```javascript
<DetailItem label="Pokok Penempatan" value={formatCurrency(detail.principal || 0)} />
<DetailItem label="Suku Bunga" value={`${detail.interest_rate_pa || 0}% per tahun`} />
<DetailItem label="Tanggal Penempatan" value={detail.placement_date ? new Date(detail.placement_date).toLocaleDateString('id-ID') : '-'} />
```

#### d. Perbaiki Endpoint Pencairan
Mengubah dari endpoint lama ke endpoint yang benar:
```javascript
// Sebelum:
const result = await callApi('deposit_account_disburse.php', 'POST', { deposit_id: detail?.id });

// Sesudah:
const result = await callApi('/user/deposits/' + detail?.id + '/disburse', 'POST', {});
```

#### e. Perbaiki Redirect Setelah Pencairan
Mengubah dari `router.reload()` ke `router.visit('/deposits')`:
```javascript
// Sebelum:
router.reload();

// Sesudah:
router.visit('/deposits');
```

### 3. Rebuild Frontend
```bash
npm run build
```

## Struktur Data yang Dikirim ke Frontend

```javascript
{
    id: 57,
    account_number: "DEP17710163844448",
    product_name: "Deposito 1 Bulan",
    status: "ACTIVE",
    principal: 1000000,              // Pokok penempatan (balance)
    interest_rate_pa: 5.00,          // Suku bunga per tahun (dari depositProduct)
    placement_date: "2026-04-25",    // Tanggal penempatan (created_at)
    maturity_date: "2026-05-25",     // Tanggal jatuh tempo
    interest_earned: 4166.67,        // Estimasi bunga
    total_amount: 1004166.67         // Total pencairan (pokok + bunga)
}
```

## Cara Testing

1. **Hard refresh browser**: `Ctrl + Shift + F5`
2. **Login sebagai customer**
3. **Buka halaman Deposito** (`/deposits`)
4. **Klik salah satu deposito** untuk melihat detail
5. **Seharusnya menampilkan**:
   - ✅ Pokok Penempatan: Rp1.000.000 (bukan RpNaN)
   - ✅ Suku Bunga: 5% per tahun (bukan undefined%)
   - ✅ Tanggal Penempatan: 25/4/2026 (bukan Invalid Date)
   - ✅ Tanggal Jatuh Tempo: 25/5/2026
   - ✅ Estimasi Bunga Diperoleh: Rp4.167
   - ✅ Total Pencairan: Rp1.004.167

## Fitur Pencairan Deposito

### Kondisi untuk Cairkan Dana:
- Status deposito harus **ACTIVE**
- Tanggal sekarang harus **≥ tanggal jatuh tempo** (matured)

### Proses Pencairan:
1. User klik tombol "Cairkan Dana"
2. Muncul modal konfirmasi dengan detail:
   - Pokok: Rp1.000.000
   - Bunga: Rp4.167
   - Total: Rp1.004.167
3. Setelah konfirmasi:
   - Total ditransfer ke rekening TABUNGAN
   - Saldo deposito menjadi 0
   - Status deposito berubah jadi CLOSED
   - Redirect ke halaman `/deposits`

## Files Modified
- `app/Http/Controllers/Inertia/UserPageController.php` - Update depositDetail() method
- `resources/js/Pages/DepositDetailPage.jsx` - Perbaiki format dan tambah fallback

## Status
✅ **SELESAI** - Backend dan frontend sudah diperbaiki dan di-build
