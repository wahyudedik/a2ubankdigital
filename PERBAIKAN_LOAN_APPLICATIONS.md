# Perbaikan Loan Applications - Accept & Reject Error

## Masalah
Error 405 Method Not Allowed saat mencoba approve atau reject pengajuan pinjaman:
```
PUT http://a2ubankdigital.test/ajax/admin/loan-applications/status 405 (Method Not Allowed)
```

## Penyebab
Frontend memanggil endpoint `/ajax/admin/loan-applications/status` dengan `loan_id` di request body, tetapi route yang ada hanya mendukung `/ajax/admin/loans/{id}/status` dengan ID di URL.

## Solusi yang Dilakukan

### 1. Tambah Route Alternatif di `routes/ajax.php`
Menambahkan route baru yang menerima ID dari request body:
```php
// Loan Applications (alternative routes that accept ID in body)
Route::put('/loan-applications/status', [App\Http\Controllers\Admin\LoanController::class, 'updateStatus']);
Route::post('/loan-applications/disburse', [App\Http\Controllers\Admin\LoanController::class, 'disburse']);
```

### 2. Update `LoanController::updateStatus()` Method
Mengubah method signature untuk menerima ID dari URL parameter ATAU request body:
```php
public function updateStatus(Request $request, $id = null): JsonResponse
{
    // Support both URL parameter and request body
    $loanId = $id ?? $request->input('loan_id');
    
    if (!$loanId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Loan ID is required.'
        ], 400);
    }

    $loan = Loan::with('user')->findOrFail($loanId);
    // ... rest of the code
}
```

### 3. Update `LoanController::disburse()` Method
Mengubah method signature untuk menerima ID dari URL parameter ATAU request body:
```php
public function disburse(Request $request, $id = null): JsonResponse
{
    // Support both URL parameter and request body
    $loanId = $id ?? $request->input('loan_id');
    
    if (!$loanId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Loan ID is required.'
        ], 400);
    }

    $loan = Loan::with('user')->findOrFail($loanId);
    // ... rest of the code
}
```

## Routes yang Tersedia Sekarang

### Route dengan ID di URL (existing):
- `PUT /ajax/admin/loans/{id}/status` - Update status pinjaman
- `POST /ajax/admin/loans/{id}/disburse` - Cairkan pinjaman

### Route dengan ID di Body (new):
- `PUT /ajax/admin/loan-applications/status` - Update status pinjaman (loan_id di body)
- `POST /ajax/admin/loan-applications/disburse` - Cairkan pinjaman (loan_id di body)

## Cara Testing

1. **Buka halaman** `/admin/loan-applications`
2. **Pilih tab** "Pengajuan Baru"
3. **Klik tombol Approve** (ikon centang hijau) pada salah satu pengajuan
4. **Konfirmasi** - seharusnya berhasil tanpa error 405
5. **Pindah ke tab** "Siap Dicairkan"
6. **Klik tombol Cairkan Dana** (ikon dollar biru)
7. **Konfirmasi** - seharusnya berhasil mencairkan dana

## Status Pinjaman

### SUBMITTED (Pengajuan Baru)
- Pengajuan baru dari nasabah
- Admin bisa **APPROVE** atau **REJECT**
- Setelah approve → status berubah jadi **APPROVED**

### APPROVED (Siap Dicairkan)
- Pinjaman sudah disetujui, menunggu pencairan
- Admin bisa **CAIRKAN DANA** (disburse)
- Setelah dicairkan → status berubah jadi **DISBURSED**
- Dana akan masuk ke rekening nasabah
- Jadwal angsuran otomatis dibuat

### DISBURSED
- Dana sudah dicairkan ke nasabah
- Nasabah bisa mulai bayar angsuran

### REJECTED
- Pengajuan ditolak
- Nasabah akan menerima notifikasi dengan alasan penolakan

## Catatan Penting

- Hanya pinjaman dengan status **SUBMITTED** yang bisa di-approve/reject
- Hanya pinjaman dengan status **APPROVED** yang bisa dicairkan
- Pencairan dana akan:
  - Menambah saldo rekening nasabah
  - Membuat transaksi LOAN_DISBURSEMENT
  - Generate jadwal angsuran
  - Kirim notifikasi & email ke nasabah
  - Catat audit log

## Files Modified
- `routes/ajax.php` - Tambah route loan-applications
- `app/Http/Controllers/Admin/LoanController.php` - Update updateStatus() dan disburse() methods

## Status
✅ **SELESAI** - Routes sudah ditambahkan dan controller sudah diupdate
