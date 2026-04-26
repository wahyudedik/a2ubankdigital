# Perbaikan Fitur Deposito - Method Not Allowed & JSON Response Error

## Masalah

### 1. Error 405 Method Not Allowed (Pertama)
```
The POST method is not supported for route deposits/create. Supported methods: GET, HEAD.
```

### 2. Response JSON Muncul di Halaman
Setelah klik "Buka Deposito", muncul response JSON mentah di halaman baru:
```json
{"status":"success","message":"Pembukaan deposito berhasil","data":{...}}
```

### 3. Error 405 Method Not Allowed (Kedua)
```
POST http://a2ubankdigital.test/ajax/ajax/user/deposits/create 405 (Method Not Allowed)
```
URL menjadi `/ajax/ajax/...` (duplikasi `/ajax`)

## Penyebab

### Masalah 1: Wrong Endpoint
Frontend form melakukan POST ke `/deposits/create` (web route), tetapi endpoint yang benar adalah `/ajax/user/deposits/create` (AJAX route).

### Masalah 2: Wrong Method
Menggunakan `router.post()` dari Inertia.js untuk memanggil AJAX endpoint. `router.post()` digunakan untuk Inertia navigation, bukan untuk AJAX calls yang mengembalikan JSON.

### Masalah 3: Duplikasi /ajax Prefix
`AppConfig.api.baseUrl` sudah diset ke `/ajax`, jadi ketika memanggil `/ajax/user/deposits/create`, menjadi `/ajax` + `/ajax/user/deposits/create` = `/ajax/ajax/user/deposits/create`

## Solusi yang Dilakukan

### 1. Ganti dari `router.post()` ke `useApi()` Hook
`router.post()` adalah untuk Inertia navigation (server-side rendering), sedangkan `useApi()` adalah untuk AJAX calls yang mengembalikan JSON.

### 2. Hapus Prefix `/ajax` dari Endpoint
Karena `AppConfig.api.baseUrl = "/ajax"`, maka endpoint yang dipanggil **tidak perlu** include `/ajax` lagi.

**Salah:**
```javascript
const result = await callApi('/ajax/user/deposits/create', 'POST', { ... });
// Akan menjadi: /ajax + /ajax/user/deposits/create = /ajax/ajax/user/deposits/create ❌
```

**Benar:**
```javascript
const result = await callApi('/user/deposits/create', 'POST', { ... });
// Akan menjadi: /ajax + /user/deposits/create = /ajax/user/deposits/create ✅
```

### 3. Final Code
```javascript
import useApi from '@/hooks/useApi';

const { loading, error, callApi, setError } = useApi();

const result = await callApi('/user/deposits/create', 'POST', { 
    product_id: selectedProductId, 
    amount 
});

if (result && result.status === 'success') {
    await modal.showAlert({ 
        title: "Berhasil", 
        message: "Deposito berhasil dibuka.", 
        type: "success" 
    });
    navigate('/deposits');
}
```

### 4. Rebuild Frontend
```bash
npm run build
```

## Perbedaan `router.post()` vs `useApi()`

### `router.post()` - Untuk Inertia Navigation
- Digunakan untuk submit form ke server yang mengembalikan Inertia response (HTML/React component)
- Server harus return `Inertia::render()` atau redirect
- Contoh: Login form, register form, update profile

### `useApi()` - Untuk AJAX Calls
- Digunakan untuk memanggil API endpoint yang mengembalikan JSON
- Server return `response()->json()`
- `AppConfig.api.baseUrl` otomatis ditambahkan di depan endpoint
- Contoh: Create deposit, transfer, payment, dll

## Cara Kerja useApi Hook

```javascript
// AppConfig.api.baseUrl = "/ajax"

// Ketika memanggil:
callApi('/user/deposits/create', 'POST', { ... })

// Akan menjadi:
fetch('/ajax/user/deposits/create', { method: 'POST', ... })
```

**PENTING**: Jangan include `/ajax` di endpoint karena sudah otomatis ditambahkan!

## Cara Kerja Fitur Deposito

### Pembukaan Deposito Baru
1. **User mengisi form**:
   - Pilih produk deposito (dengan tenor dan bunga tertentu)
   - Masukkan jumlah penempatan dana

2. **Validasi**:
   - Produk harus aktif
   - Jumlah harus antara minimum dan maximum produk
   - Saldo tabungan harus mencukupi

3. **Proses**:
   - Dana dipotong dari rekening TABUNGAN
   - Dibuat rekening DEPOSITO baru dengan nomor `DEP{timestamp}{random}`
   - Status: ACTIVE
   - Maturity date dihitung: sekarang + tenor bulan
   - Transaksi dicatat dengan tipe `PEMBUKAAN_DEPOSITO`

4. **Response**:
   - Modal alert "Berhasil"
   - Redirect ke halaman `/deposits`

### Pencairan Deposito
1. **User klik tombol cairkan** pada deposito yang aktif

2. **Perhitungan**:
   - Pokok = saldo deposito
   - Bunga = pokok × (interest_rate_pa / 100) × (tenor_months / 12)
   - Total = pokok + bunga

3. **Proses**:
   - Total (pokok + bunga) ditransfer ke rekening TABUNGAN
   - Saldo deposito menjadi 0
   - Status deposito berubah jadi CLOSED
   - Transaksi dicatat dengan tipe `PENCAIRAN_DEPOSITO`

## Endpoint yang Tersedia

### User Endpoints (AJAX):
Ketika menggunakan `useApi()`, **jangan** include `/ajax` prefix:

- `callApi('/user/deposits', 'GET')` → `/ajax/user/deposits`
- `callApi('/user/deposits/{id}', 'GET')` → `/ajax/user/deposits/{id}`
- `callApi('/user/deposits/create', 'POST')` → `/ajax/user/deposits/create` ✅
- `callApi('/user/deposits/{id}/disburse', 'POST')` → `/ajax/user/deposits/{id}/disburse`
- `callApi('/user/deposit-products', 'GET')` → `/ajax/user/deposit-products`

### Web Routes (untuk halaman):
- `GET /deposits` - Halaman list deposito
- `GET /deposits/open` - Halaman form buka deposito
- `GET /deposits/{depositId}` - Halaman detail deposito

## Cara Testing

1. **Hard refresh browser**: `Ctrl + Shift + F5`
2. **Login sebagai customer**
3. **Buka menu Deposito** atau navigasi ke `/deposits/open`
4. **Pilih produk deposito** (contoh: Deposito 1 Bulan 5.00% p.a)
5. **Masukkan jumlah** (contoh: 1000000)
6. **Klik "Buka Deposito"**
7. **Konfirmasi** - seharusnya muncul modal konfirmasi
8. **Klik "Ya, Lanjutkan"**
9. **Seharusnya muncul**:
   - Modal alert "Berhasil"
   - Redirect ke halaman `/deposits`
   - **TIDAK** muncul JSON response di halaman
   - **TIDAK** ada error 405
10. **Cek saldo tabungan** - seharusnya berkurang
11. **Cek list deposito** - deposito baru muncul dengan status ACTIVE

## Validasi

### Minimum Amount
Setiap produk deposito punya minimum amount. Jika user input kurang dari minimum, akan muncul error:
```
Jumlah penempatan harus antara Rp X dan Rp Y.
```

### Saldo Tidak Cukup
Jika saldo tabungan kurang dari jumlah yang ingin ditempatkan:
```
Saldo tabungan tidak mencukupi untuk penempatan deposito.
```

### Produk Tidak Aktif
Jika produk deposito sudah tidak aktif:
```
Produk deposito tidak valid atau tidak aktif.
```

## Files Modified
- `resources/js/Pages/OpenDepositPage.jsx` - Ganti dari `router.post()` ke `useApi()` hook dan hapus `/ajax` prefix

## Status
✅ **SELESAI** - Frontend sudah diupdate menggunakan useApi hook dengan endpoint yang benar dan di-build
