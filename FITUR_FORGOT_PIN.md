# Fitur Reset PIN Transaksi (Lupa PIN)

## Overview
Fitur ini memungkinkan user untuk mereset PIN transaksi mereka jika lupa, dengan verifikasi menggunakan password akun dan kode OTP yang dikirim ke email.

## Flow Proses

### Langkah 1: Verifikasi Identitas
1. User klik link "Lupa PIN? Reset di sini" di halaman Ubah PIN
2. User diarahkan ke halaman `/forgot-pin`
3. User memasukkan **password akun** untuk verifikasi identitas
4. Sistem memverifikasi password
5. Jika valid, sistem generate kode OTP 6 digit
6. OTP dikirim ke email user yang terdaftar
7. OTP berlaku selama **10 menit**

### Langkah 2: Reset PIN
1. User memasukkan **kode OTP** yang diterima di email
2. User memasukkan **PIN baru** (6 digit)
3. User memasukkan **konfirmasi PIN baru**
4. Sistem memverifikasi:
   - OTP valid dan belum expired
   - PIN baru dan konfirmasi cocok
5. Jika valid, PIN direset dan OTP ditandai sebagai used
6. User diarahkan kembali ke halaman Profile

## Files Created/Modified

### Frontend
- **Created**: `resources/js/Pages/ForgotPinPage.jsx` - Halaman reset PIN dengan 2 step

### Backend Routes
- **Modified**: `routes/web.php`
  - Added: `GET /forgot-pin` → `UserPageController@forgotPin`

- **Modified**: `routes/ajax.php`
  - Added: `POST /ajax/user/security/forgot-pin/request-otp` → `SecurityController@forgotPinRequestOtp`
  - Added: `POST /ajax/user/security/forgot-pin/reset` → `SecurityController@forgotPinReset`

### Backend Controllers
- **Modified**: `app/Http/Controllers/Inertia/UserPageController.php`
  - Added: `forgotPin()` method - Render ForgotPinPage

- **Modified**: `app/Http/Controllers/User/SecurityController.php`
  - Added: `forgotPinRequestOtp()` - Request OTP dengan verifikasi password
  - Added: `forgotPinReset()` - Reset PIN dengan OTP

## API Endpoints

### 1. Request OTP
**Endpoint**: `POST /ajax/user/security/forgot-pin/request-otp`

**Authentication**: Required (user must be logged in)

**Request Body**:
```json
{
    "password": "user_password"
}
```

**Response Success**:
```json
{
    "status": "success",
    "message": "Kode OTP telah dikirim ke email Anda."
}
```

**Response Error**:
```json
{
    "status": "error",
    "message": "Password yang Anda masukkan salah."
}
```

### 2. Reset PIN
**Endpoint**: `POST /ajax/user/security/forgot-pin/reset`

**Authentication**: Required (user must be logged in)

**Request Body**:
```json
{
    "otp": "123456",
    "new_pin": "654321",
    "confirm_pin": "654321"
}
```

**Response Success**:
```json
{
    "status": "success",
    "message": "PIN transaksi berhasil direset."
}
```

**Response Error**:
```json
{
    "status": "error",
    "message": "Kode OTP tidak valid."
}
```

atau

```json
{
    "status": "error",
    "message": "Kode OTP sudah kadaluarsa. Silakan minta kode baru."
}
```

## Database

### Table: user_otps
OTP disimpan di table `user_otps` dengan struktur:
```sql
- user_id: ID user
- otp_code: Kode OTP 6 digit
- purpose: 'PIN_RESET'
- expires_at: Waktu expired (10 menit dari created)
- is_used: Boolean flag (false → true setelah digunakan)
```

### Table: users
PIN disimpan di column `pin_hash` dengan hashing menggunakan `Hash::make()`.

## Security Features

### 1. Password Verification
- User harus memasukkan password akun untuk request OTP
- Mencegah orang lain mereset PIN jika device tidak terkunci

### 2. OTP Expiration
- OTP berlaku selama 10 menit
- Setelah expired, user harus request OTP baru

### 3. One-Time Use
- OTP hanya bisa digunakan sekali
- Setelah digunakan, flag `is_used` diset ke `true`

### 4. Audit Log
- Setiap reset PIN dicatat di audit log
- Action: `PIN_RESET`
- Target: `users` table
- Target ID: User ID

## UI/UX Features

### Info Box
Menampilkan informasi proses reset PIN di bagian atas halaman.

### Two-Step Process
1. **Step 1**: Verifikasi identitas dengan password
2. **Step 2**: Input OTP dan PIN baru

### Error Handling
- Password salah
- OTP tidak valid
- OTP expired
- PIN tidak cocok dengan konfirmasi

### Loading States
- Button disabled saat loading
- Text berubah: "Kirim Kode OTP" → "Mengirim OTP..."
- Text berubah: "Reset PIN" → "Memproses..."

### Resend OTP
Button "Kirim ulang OTP" untuk kembali ke step 1 dan request OTP baru.

## Email Template
Email OTP menggunakan template `otp` dengan data:
```php
[
    'full_name' => 'Nama User',
    'otp_code' => '123456',
    'preheader' => 'Kode verifikasi untuk reset PIN transaksi Anda.'
]
```

Subject: "Reset PIN Transaksi - Kode Verifikasi"

## Testing

### Test Case 1: Happy Path
1. Login sebagai user
2. Buka `/profile/change-pin`
3. Klik "Lupa PIN? Reset di sini"
4. Masukkan password yang benar
5. Klik "Kirim Kode OTP"
6. Cek email untuk kode OTP
7. Masukkan OTP, PIN baru, dan konfirmasi PIN
8. Klik "Reset PIN"
9. Seharusnya berhasil dan redirect ke `/profile`

### Test Case 2: Wrong Password
1. Masukkan password yang salah
2. Klik "Kirim Kode OTP"
3. Seharusnya muncul error: "Password yang Anda masukkan salah."

### Test Case 3: Invalid OTP
1. Request OTP dengan password benar
2. Masukkan OTP yang salah
3. Klik "Reset PIN"
4. Seharusnya muncul error: "Kode OTP tidak valid."

### Test Case 4: Expired OTP
1. Request OTP
2. Tunggu lebih dari 10 menit
3. Masukkan OTP
4. Klik "Reset PIN"
5. Seharusnya muncul error: "Kode OTP sudah kadaluarsa. Silakan minta kode baru."

### Test Case 5: PIN Mismatch
1. Request OTP dan masukkan OTP yang valid
2. Masukkan PIN baru: "123456"
3. Masukkan konfirmasi PIN: "654321" (berbeda)
4. Klik "Reset PIN"
5. Seharusnya muncul error: "PIN baru dan konfirmasi PIN tidak cocok."

### Test Case 6: Resend OTP
1. Request OTP
2. Klik "Kirim ulang OTP"
3. Seharusnya kembali ke step 1
4. Request OTP baru
5. OTP lama tidak bisa digunakan lagi

## Cara Menggunakan

### Untuk User:
1. **Buka halaman Ubah PIN**: `/profile/change-pin`
2. **Klik link**: "Lupa PIN? Reset di sini"
3. **Masukkan password akun** Anda
4. **Klik**: "Kirim Kode OTP"
5. **Cek email** Anda untuk kode OTP (cek folder spam jika tidak ada di inbox)
6. **Masukkan**:
   - Kode OTP (6 digit)
   - PIN baru (6 digit)
   - Konfirmasi PIN baru (6 digit)
7. **Klik**: "Reset PIN"
8. **Selesai** - PIN Anda telah direset

### Untuk Admin:
- Tidak ada aksi khusus yang diperlukan
- Admin bisa melihat audit log untuk tracking reset PIN
- Query audit log: `SELECT * FROM audit_logs WHERE action = 'PIN_RESET'`

## Notes

### Perbedaan dengan forgotPin() Existing
- **Existing `forgotPin()`**: Menggunakan email untuk verifikasi (public, no auth)
- **New `forgotPinRequestOtp()`**: Menggunakan password untuk verifikasi (authenticated)

Kedua method tetap ada untuk backward compatibility.

### Email Configuration
Pastikan email sudah dikonfigurasi dengan benar di `.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS="noreply@a2ubankdigital.my.id"
MAIL_FROM_NAME="${APP_NAME}"
```

## Status
✅ **SELESAI** - Fitur reset PIN sudah lengkap dan siap digunakan
