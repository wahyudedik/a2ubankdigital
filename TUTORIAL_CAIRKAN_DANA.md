# 🎥 TUTORIAL: Cara Mencairkan Dana (Memotong Saldo)

## ⚠️ PENTING: Anda Harus Klik 2 Tombol Berbeda!

### Tombol 1: ✓ Setujui (HIJAU) ← Sudah Anda Lakukan ✅
- **Lokasi**: Tab "Menunggu Persetujuan"
- **Fungsi**: Menyetujui permintaan
- **Hasil**: Status berubah `pending` → `approved`
- **Saldo**: TIDAK DIPOTONG ❌

### Tombol 2: 💵 Cairkan (BIRU) ← BELUM Anda Lakukan ❌
- **Lokasi**: Tab "Siap Dicairkan"
- **Fungsi**: Mencairkan dana
- **Hasil**: Status berubah `approved` → `completed`
- **Saldo**: DIPOTONG DI SINI ✅

---

## 📋 Langkah Demi Langkah (IKUTI PERSIS):

### LANGKAH 1: Login Admin
```
URL: http://a2ubankdigital.test/login
Email: admin@a2ubank.com
Password: admin123
```

### LANGKAH 2: Buka Halaman Penarikan Dana
```
Klik Menu: Permintaan → Penarikan Dana
Atau langsung ke: http://a2ubankdigital.test/admin/withdrawal-requests
```

### LANGKAH 3: Lihat Tab yang Ada
Anda akan melihat 4 tab:
1. **Menunggu Persetujuan** ← Tab pertama
2. **Siap Dicairkan** ← Tab kedua (PENTING!)
3. **Selesai** ← Tab ketiga
4. **Ditolak** ← Tab keempat

### LANGKAH 4: KLIK TAB "SIAP DICAIRKAN" (Tab ke-2)
```
┌─────────────────────┬──────────────────┬─────────┬─────────┐
│ Menunggu Persetujuan│ Siap Dicairkan ← │ Selesai │ Ditolak │
└─────────────────────┴──────────────────┴─────────┴─────────┘
                           👆 KLIK INI!
```

### LANGKAH 5: Lihat Tabel
Anda akan melihat tabel seperti ini:
```
┌──────────────┬────────────┬──────────────────┬──────────┬─────────────┐
│ Nasabah      │ Jumlah     │ Rekening Tujuan  │ Tanggal  │ Aksi        │
├──────────────┼────────────┼──────────────────┼──────────┼─────────────┤
│ Rizky Pratama│ Rp 100.000 │ BCA - 1234567890 │ 25/04/26 │ [💵 Cairkan]│
└──────────────┴────────────┴──────────────────┴──────────┴─────────────┘
                                                              👆 KLIK INI!
```

### LANGKAH 6: KLIK TOMBOL "💵 Cairkan" (BIRU)
- Tombol ini berwarna **BIRU**
- Ada icon dollar 💵
- Tulisan "Cairkan"

### LANGKAH 7: Konfirmasi
- Akan muncul popup konfirmasi
- Klik "Ya, Cairkan Dana"

### LANGKAH 8: Verifikasi
- Login sebagai customer (Rizky Pratama)
- Cek saldo di dashboard
- Saldo seharusnya: Rp 3.000.000 - Rp 100.000 = **Rp 2.900.000**

---

## 🔍 Cara Memastikan Anda di Tab yang Benar:

### ❌ SALAH - Tab "Menunggu Persetujuan":
- Tombol yang muncul: **✓ Setujui** (hijau) dan **✗ Tolak** (merah)
- Ini tab untuk APPROVE, bukan CAIRKAN

### ✅ BENAR - Tab "Siap Dicairkan":
- Tombol yang muncul: **💵 Cairkan** (biru)
- Ini tab untuk DISBURSE (memotong saldo)

---

## 🎯 Checklist:

Pastikan Anda sudah:
- [ ] Login sebagai admin
- [ ] Buka halaman `/admin/withdrawal-requests`
- [ ] **KLIK TAB "SIAP DICAIRKAN"** (tab ke-2)
- [ ] Lihat tombol **💵 Cairkan** (biru)
- [ ] **KLIK tombol "💵 Cairkan"**
- [ ] Konfirmasi popup
- [ ] Cek saldo customer

---

## 📸 Screenshot yang Harus Anda Lihat:

**Tab "Siap Dicairkan" harus terlihat seperti ini:**

```
╔═══════════════════════════════════════════════════════════╗
║  Permintaan Penarikan Dana                                ║
╠═══════════════════════════════════════════════════════════╣
║  [Menunggu Persetujuan] [Siap Dicairkan] [Selesai] [...]  ║
║                          👆 AKTIF                          ║
╠═══════════════════════════════════════════════════════════╣
║  Nasabah      │ Jumlah     │ Rekening    │ Aksi          ║
║  ────────────┼────────────┼─────────────┼───────────     ║
║  Rizky       │ Rp 100.000 │ BCA - 123   │ [💵 Cairkan]  ║
║                                             👆 BIRU        ║
╚═══════════════════════════════════════════════════════════╝
```

**JIKA TIDAK ADA TOMBOL "💵 Cairkan", berarti Anda di tab yang salah!**

---

## ❓ FAQ:

**Q: Saya sudah approve, kenapa saldo tidak terpotong?**
A: Karena APPROVE ≠ CAIRKAN. Anda harus klik tombol "Cairkan" di tab "Siap Dicairkan".

**Q: Di mana tombol "Cairkan"?**
A: Di tab "Siap Dicairkan" (tab ke-2), bukan di tab "Menunggu Persetujuan".

**Q: Tombol apa yang harus saya klik?**
A: Tombol **💵 Cairkan** (biru), bukan tombol ✓ Setujui (hijau).

**Q: Berapa kali saya harus klik?**
A: 2 kali:
   1. Klik ✓ Setujui di tab "Menunggu Persetujuan" (sudah ✅)
   2. Klik 💵 Cairkan di tab "Siap Dicairkan" (belum ❌)

---

## 🚨 KESIMPULAN:

**ANDA BELUM KLIK TOMBOL "CAIRKAN"!**

Silakan ikuti langkah-langkah di atas dengan teliti.
Jika masih bingung, screenshot halaman "Siap Dicairkan" dan kirim ke saya.

