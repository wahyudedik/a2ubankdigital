# Test Top-Up Feature

## ✅ Yang Sudah Diperbaiki:

1. **Frontend URL** - Diubah dari `https://bank.taskora.id/app/` ke `http://a2ubankdigital.my.id.test/app/`
2. **Validasi Form** - Ditambahkan validasi sebelum submit
3. **Error Handling** - Ditambahkan try-catch untuk handle network errors
4. **Upload Folder** - Folder `uploads/proofs/` sudah dibuat

## 🧪 Cara Test:

### 1. Login sebagai Customer

Buka: `http://localhost:5174/login`

Gunakan akun customer:
- Email: `sintalaela960@gmail.com`
- Email: `andrealditam@gmail.com`

### 2. Akses Halaman Top-Up

Setelah login, klik menu "Top-Up" atau akses:
```
http://localhost:5174/topup
```

### 3. Isi Form Top-Up

1. **Masukkan Jumlah**: Contoh: 100000
2. **Pilih Metode Pembayaran**: 
   - QRIS (jika tersedia)
   - Transfer Bank (pilih bank)
3. **Upload Bukti Pembayaran**: 
   - Pilih gambar (PNG/JPG/GIF)
   - Maksimal 2MB
4. **Klik "Kirim Konfirmasi"**

### 4. Cek di Admin Panel

Login sebagai Admin:
- Email: `admin@taskora.id`
- Email: `aauasiarecords@gmail.com`

Akses:
```
http://localhost:5174/admin/topup-requests
```

Harusnya muncul request baru dengan status "PENDING"

## 🔍 Debug Jika Masih Error:

### Cek Console Browser (F12)

Buka Developer Tools (F12) → Console tab
Lihat error message yang muncul

### Cek Network Tab

1. Buka Developer Tools (F12) → Network tab
2. Submit form top-up
3. Cari request ke `user_create_topup_request.php`
4. Klik request tersebut
5. Lihat:
   - **Headers**: Pastikan Authorization header ada
   - **Payload**: Pastikan data terkirim (amount, payment_method, proof)
   - **Response**: Lihat error message dari server

### Test Backend Langsung

```bash
# Test dengan curl (ganti dengan token yang valid)
curl -X POST http://a2ubankdigital.my.id.test/app/user_create_topup_request.php \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "amount=100000" \
  -F "payment_method=QRIS" \
  -F "proof=@path/to/image.jpg"
```

### Cek Database

Via phpMyAdmin atau Herd:
```sql
-- Cek topup requests
SELECT * FROM topup_requests ORDER BY id DESC LIMIT 5;

-- Cek user yang login
SELECT id, email, full_name FROM users WHERE email = 'sintalaela960@gmail.com';
```

### Cek Upload Folder Permissions

```bash
# Via PowerShell
Get-Acl uploads/proofs | Format-List
```

Folder harus writable.

## 📝 Expected Flow:

1. **Customer** submit top-up request
2. **Data** tersimpan ke database dengan status `PENDING`
3. **File** bukti pembayaran tersimpan di `uploads/proofs/`
4. **Notifikasi** dikirim ke admin/staff
5. **Admin** bisa lihat di `/admin/topup-requests`
6. **Admin** approve/reject request
7. **Customer** dapat notifikasi hasil

## ⚠️ Common Issues:

### Error: "Failed to fetch"
- Backend tidak running
- URL salah
- CORS issue

**Fix:**
- Pastikan Herd running
- Cek URL di config: `http://a2ubankdigital.my.id.test/app/`
- Cek CORS di `.env`: `ALLOWED_ORIGINS` include `http://localhost:5174`

### Error: "Direktori upload tidak bisa ditulisi"
- Folder permissions issue

**Fix:**
```bash
# Windows
icacls uploads /grant Everyone:F /T
```

### Error: "Bukti pembayaran wajib diunggah"
- File tidak terkirim
- File terlalu besar (>2MB)

**Fix:**
- Pastikan file dipilih
- Compress image jika terlalu besar

### Error: "Akses ditolak" (403)
- Token tidak valid
- User tidak authenticated

**Fix:**
- Logout dan login ulang
- Cek localStorage untuk authToken

## 🎯 Success Indicators:

✅ Form submitted tanpa error
✅ Alert "Berhasil" muncul
✅ Redirect ke dashboard
✅ Data muncul di database `topup_requests`
✅ File tersimpan di `uploads/proofs/`
✅ Admin bisa lihat request di admin panel

## 📞 Need Help?

Jika masih error, kirim screenshot:
1. Browser console error
2. Network tab (request & response)
3. Database content (topup_requests table)
