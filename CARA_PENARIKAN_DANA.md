# 📘 Panduan Lengkap: Proses Penarikan Dana

## ⚠️ PENTING: 2 Langkah Terpisah

Penarikan dana memerlukan **2 LANGKAH** yang berbeda:

### ✅ Langkah 1: APPROVE (Menyetujui Permintaan)
- **Lokasi**: Tab "Menunggu Persetujuan"
- **Tombol**: ✓ Setujui (hijau)
- **Fungsi**: Mengubah status dari `pending` → `approved`
- **Saldo Customer**: **BELUM DIPOTONG** ❌

### ✅ Langkah 2: DISBURSE (Cairkan Dana)
- **Lokasi**: Tab "Siap Dicairkan"
- **Tombol**: 💵 Cairkan (biru)
- **Fungsi**: Mengubah status dari `approved` → `completed`
- **Saldo Customer**: **DIPOTONG DI SINI** ✅

---

## 📋 Langkah-Langkah Detail:

### 1️⃣ Login sebagai Admin/Teller
- Buka: `http://a2ubankdigital.test/login`
- Email: `admin@a2ubank.com` atau `teller@a2ubank.com`
- Password: `admin123` atau `teller123`

### 2️⃣ Buka Halaman Permintaan Penarikan Dana
- Menu: **Permintaan** → **Penarikan Dana**
- URL: `http://a2ubankdigital.test/admin/withdrawal-requests`

### 3️⃣ Tab "Menunggu Persetujuan"
- Lihat daftar permintaan dengan status `pending`
- Klik tombol **✓ Setujui** (hijau) untuk menyetujui
- Atau klik tombol **✗ Tolak** (merah) untuk menolak

**Hasil Langkah 3:**
- Status berubah: `pending` → `approved`
- Permintaan pindah ke tab "Siap Dicairkan"
- Customer menerima notifikasi "Penarikan Disetujui"
- **Saldo customer BELUM dipotong**

### 4️⃣ Tab "Siap Dicairkan" ⭐ **LANGKAH PENTING**
- Klik tab **"Siap Dicairkan"**
- Lihat daftar permintaan dengan status `approved`
- Klik tombol **💵 Cairkan** (biru)

**Hasil Langkah 4:**
- Status berubah: `approved` → `completed`
- **Saldo customer DIPOTONG** ✅
- Transaksi withdrawal dicatat
- Customer menerima notifikasi "Penarikan Selesai Diproses"
- Permintaan pindah ke tab "Selesai"

### 5️⃣ Verifikasi
- Login sebagai customer
- Cek saldo di dashboard
- Saldo seharusnya sudah berkurang

---

## 🔍 Troubleshooting

### ❌ Masalah: "Saldo tidak terpotong setelah approve"
**Penyebab**: Anda hanya melakukan Langkah 3 (APPROVE), belum melakukan Langkah 4 (DISBURSE)

**Solusi**: 
1. Buka tab "Siap Dicairkan"
2. Klik tombol **💵 Cairkan** (biru)

### ❌ Masalah: "Tab Siap Dicairkan kosong"
**Penyebab**: Belum ada permintaan yang di-approve

**Solusi**:
1. Pastikan sudah melakukan approve di tab "Menunggu Persetujuan"
2. Refresh halaman
3. Cek tab "Siap Dicairkan" lagi

### ❌ Masalah: "Error saat cairkan dana"
**Cek Log**:
```bash
Get-Content storage/logs/laravel.log -Tail 50
```

**Kemungkinan Penyebab**:
- Saldo customer tidak cukup
- Kartu customer diblokir
- Error database

---

## 📊 Status Flow

```
CUSTOMER REQUEST
      ↓
[PENDING] ← Tab "Menunggu Persetujuan"
      ↓
   APPROVE (✓ Setujui)
      ↓
[APPROVED] ← Tab "Siap Dicairkan" ⭐
      ↓
   DISBURSE (💵 Cairkan) ← SALDO DIPOTONG DI SINI
      ↓
[COMPLETED] ← Tab "Selesai"
```

---

## 🎯 Kesimpulan

**INGAT**: 
- **APPROVE** = Menyetujui permintaan (saldo BELUM dipotong)
- **DISBURSE** = Cairkan dana (saldo DIPOTONG)

Kedua langkah ini **HARUS** dilakukan untuk memotong saldo customer!

