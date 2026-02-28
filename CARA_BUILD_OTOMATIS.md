# 🚀 Cara Build Frontend Otomatis dari Browser

## Fitur Baru: Auto Build via Web Interface

Sekarang kamu bisa build frontend langsung dari browser tanpa perlu SSH atau terminal!

---

## 📋 Cara Menggunakan

### 1. Login sebagai Admin/Staff
- Email: Admin atau Staff account
- Role ID harus BUKAN 9 (bukan customer)

### 2. Akses Menu Build
Di sidebar admin, klik menu **"Build Frontend"** (muncul untuk semua Admin/Staff)

Atau akses langsung:
```
https://coba.a2ubankdigital.my.id/admin/build
```

### 3. Klik Tombol "Start Build"
- Proses build akan dimulai
- Output build akan ditampilkan real-time
- Tunggu 10-30 detik sampai selesai

### 4. Selesai!
- Jika berhasil, akan muncul pesan "Build Berhasil!"
- Perubahan frontend langsung terlihat di production

---

## 🔧 Cara Kerja

### Backend Endpoint
File: `backend/app/admin_trigger_build.php`

Endpoint ini akan:
1. Verify JWT token (hanya Super Admin)
2. Execute command: `cd frontend && npm run build`
3. Return output build ke browser

### Frontend Page
File: `frontend/src/pages/AdminBuildPage.jsx`

Halaman ini menampilkan:
- Tombol trigger build
- Output build real-time
- Status success/error

### Route
```javascript
// App.jsx
<Route path="/admin/build" element={
    <ProtectedRoute adminOnly={true}>
        <AdminBuildPage />
    </ProtectedRoute>
} />
```

---

## 🔒 Security

### Hanya Admin/Staff
- Endpoint dilindungi JWT authentication
- Hanya user dengan `role_id != 9` (bukan customer) yang bisa akses
- Menu muncul untuk semua Admin/Staff

### Server Requirements
- PHP `exec()` function harus enabled
- Node.js dan npm harus terinstall di server
- Folder `frontend/` harus accessible

---

## ⚠️ Troubleshooting

### Error: "Forbidden - Admin/Staff only"
- Pastikan login sebagai Admin/Staff (bukan customer)
- Customer (role_id = 9) tidak bisa akses
- Cek JWT token masih valid

### Error: "Frontend folder not found"
- Pastikan folder `frontend/` ada di server
- Path: `/home/a2uj2723/public_html/coba.a2ubankdigital.my.id/frontend/`

### Error: "Build failed"
- Cek apakah npm terinstall: `which npm`
- Cek apakah `node_modules` ada di folder frontend
- Lihat output error untuk detail

### Build Timeout
- Jika build terlalu lama, mungkin server timeout
- Coba build manual via SSH sebagai alternatif

---

## 🎯 Kapan Menggunakan

### Gunakan Auto Build Jika:
- ✅ Perubahan kecil di frontend (fix bug, update text)
- ✅ Tidak ada akses SSH
- ✅ Butuh deploy cepat

### Gunakan Manual Build Jika:
- ❌ Perubahan besar (install package baru)
- ❌ Build error yang perlu debugging
- ❌ Butuh kontrol penuh atas build process

---

## 📝 Manual Build (Alternatif)

Jika auto build tidak work, build manual via SSH:

```bash
# SSH ke server
ssh a2uj2723@coba.a2ubankdigital.my.id

# Masuk ke folder frontend
cd /home/a2uj2723/public_html/coba.a2ubankdigital.my.id/frontend

# Build
npm run build

# Selesai!
```

---

## 🔄 Workflow Development → Production

### 1. Development (Local)
```bash
# Edit code di local
npm run dev

# Test di browser
http://localhost:5173
```

### 2. Commit & Push
```bash
git add .
git commit -m "Fix: update feature X"
git push origin main
```

### 3. Deploy ke Production
**Opsi A: Auto Build (via Browser)**
1. Login ke admin panel
2. Klik "Build Frontend"
3. Klik "Start Build"
4. Done!

**Opsi B: Manual Build (via SSH)**
1. SSH ke server
2. `cd frontend && npm run build`
3. Done!

---

## ✅ Checklist Setelah Build

- [ ] Test frontend: https://coba.a2ubankdigital.my.id
- [ ] Test login
- [ ] Test fitur yang diubah
- [ ] Clear browser cache jika perlu (Ctrl+Shift+R)

---

**SELAMAT! Sekarang kamu bisa build frontend langsung dari browser! 🎉**
