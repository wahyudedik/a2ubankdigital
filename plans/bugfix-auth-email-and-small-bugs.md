# 🐛 Bugfix: Bug Receh Auth System

## Tanggal: 2026-08-04 | Status: 🔧 Rencana Perbaikan

---

## 📌 Catatan Penting

**Auth flow saat ini:**
- Registrasi → Verifikasi pakai **LINK email** (bukan OTP code)
- Forgot Password → Verifikasi pakai **OTP code**
- Forgot PIN → Verifikasi pakai **OTP code**

Queue email sudah berfungsi dengan `php artisan queue:work`.

---

## 🟡 Bug Sedang

### Bug 2: Exception Message Bocor ke User

**Masalah:**
Beberapa controller menampilkan `$e->getMessage()` langsung ke response, yang bisa membocorkan detail error internal ke user.

**Lokasi:**
1. `RegisterController::verifyOtp()` line 386: `'Gagal memverifikasi OTP: ' . $e->getMessage()`
2. `RegisterController::forgotPasswordReset()` line 476: `'Gagal mereset password: ' . $e->getMessage()`
3. `SecurityController::forgotPinReset()` line 249: `'Gagal mereset PIN: ' . $e->getMessage()`

**Fix:**
Ganti dengan pesan error generik, log error di backend.

### Bug 3: Email Failure Tidak Dicek di Beberapa Tempat

**Masalah:**
`EmailService::send()` return value tidak dicek di beberapa controller, sehingga user selalu dapat pesan "OTP terkirim" meskipun email gagal dikirim.

**Lokasi:**
1. `RegisterController::forgotPasswordRequest()` line 416
2. `SecurityController::forgotPin()` line 121-131
3. `SecurityController::forgotPinRequestOtp()` line 171-181

**Fix:**
Setelah fix Bug 1 (synchronous), ini otomatis teratasi karena email dikirim langsung dan bisa dicek return value-nya.

---

## 🟢 Bug Ringan (UX)

### Bug 4: ForgotPasswordPage "Kirim Ulang Kode" UX

**Masalah:**
Tombol "Kirim ulang kode" di ForgotPasswordPage.jsx (line 87-89) mengembalikan user ke step 1, sehingga harus mengetik email lagi.

**Fix:**
Tambah handler `handleRequestResendOtp` yang langsung mengirim ulang OTP tanpa kembali ke step 1.

---

## ✅ Yang Sudah Tidak Ada Bug

| Kode | Item | Status |
|------|------|--------|
| E3 | Kolom `is_used` di `user_otps` | ✅ Ada (migration line 16) |
| E3 | Kolom `purpose` di `user_otps` | ✅ Ada (migration `2026_04_26_042059`) |
| - | `verifyByLink()` cek `is_used`, `purpose`, `expires_at` | ✅ Benar |
| - | `requestOtp()` generate token dengan `purpose=EMAIL_VERIFICATION` | ✅ Benar |
| - | `ForgotPasswordPage` endpoint mapping | ✅ Benar |
| - | `ForgotPinPage` route di ajax.php | ✅ Ada |
| - | `ResetPasswordPage` route di ajax.php | ✅ Ada |
| - | B1: ResetPasswordPage field mismatch | ✅ Fixed |
| - | B3: verifyOtp purpose filter | ✅ Fixed |
| - | B4: email send result check | ✅ Fixed |

---

## 📋 Todo List Implementasi

### T1: Fix Email Delivery - Synchronous Sending
Ubah `EmailService::send()` dari queue-based ke synchronous.

**File:** `app/Services/EmailService.php`
```
Method send() → langsung Mail::send() tanpa dispatch queue
Hapus fallback logic yang tidak perlu
Log error jika gagal
```

### T2: Fix Exception Message Leaks
Ganti `$e->getMessage()` dengan pesan generik di 3 lokasi.

**File:**
- `app/Http/Controllers/Auth/RegisterController.php` (line 386, 476)
- `app/Http/Controllers/User/SecurityController.php` (line 249)

### T3: Check Email Return Value di forgotPasswordRequest & forgotPin
Tambah pengecekan return value `emailService->send()`.

**File:**
- `app/Http/Controllers/Auth/RegisterController.php` (line 416)
- `app/Http/Controllers/User/SecurityController.php` (line 121)

### T4: Fix ForgotPasswordPage "Kirim Ulang" UX
Tambah handler resend OTP tanpa kembali ke step 1.

**File:** `resources/js/Pages/ForgotPasswordPage.jsx`

### T5: Final Testing
- [ ] Test registrasi → email verifikasi terkirim
- [ ] Test klik link verifikasi → akun aktif
- [ ] Test resend email → email baru terkirim
- [ ] Test forgot password → email OTP terkirim
- [ ] Test reset password dengan OTP
- [ ] Test forgot PIN → email OTP terkirim
- [ ] Test reset PIN dengan OTP
- [ ] Frontend build berhasil

---

## Arsitektur Flow Perbaikan

```
BEFORE (Broken):
EmailService::send() → SendEmailJob::dispatch() → Queue Table → ❌ No Worker
                    → returns true (fake success)

AFTER (Fixed):
EmailService::send() → Mail::send() → SMTP → ✅ Email Sent
                    → returns true/false (real result)
```
