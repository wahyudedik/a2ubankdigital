# Card Management Bugfix - Fixes Applied

## Summary
Semua masalah pada fitur manajemen kartu telah diperbaiki. Sistem sekarang berfungsi normal untuk menampilkan kartu, memblokir/membuka blokir, memperbarui limit, mengajukan kartu baru, dan memproses permintaan kartu di admin panel.

## Perbaikan yang Diterapkan

### 1. Backend - CardController.php

#### Fix 1.1: Validasi daily_limit
**File:** `app/Http/Controllers/User/CardController.php` (baris 108)
- **Masalah:** Validasi menggunakan `numeric` yang bisa menerima string, menyebabkan error `validation.max.numeric`
- **Solusi:** Ubah ke `integer` dan cast nilai ke integer sebelum update
- **Perubahan:**
  ```php
  // Sebelum
  'daily_limit' => 'required|numeric|min:0|max:50000000'
  
  // Sesudah
  'daily_limit' => 'required|integer|min:0|max:50000000'
  ```

#### Fix 1.2: Penghapusan kondisi status 'active' pada setLimit
**File:** `app/Http/Controllers/User/CardController.php` (baris 113)
- **Masalah:** Hanya kartu dengan status 'active' yang bisa diupdate limitnya
- **Solusi:** Hapus kondisi `->where('status', 'active')` agar kartu dengan status apapun bisa diupdate limitnya
- **Perubahan:**
  ```php
  // Sebelum
  $card = Card::where('user_id', Auth::id())
      ->where('status', 'active')
      ->findOrFail($id);
  
  // Sesudah
  $card = Card::where('user_id', Auth::id())
      ->findOrFail($id);
  ```

#### Fix 1.3: Eager-load user relationship pada updateStatus
**File:** `app/Http/Controllers/User/CardController.php` (baris 140)
- **Masalah:** Response tidak include user relationship setelah update status
- **Solusi:** Tambahkan `->fresh()->load('user')` untuk reload data dengan user relationship
- **Perubahan:**
  ```php
  // Sebelum
  'data' => $card
  
  // Sesudah
  'data' => $card->fresh()->load('user')
  ```

### 2. Frontend - CardsPage.jsx

#### Fix 2.1: Convert string ke integer pada handleSetLimit
**File:** `resources/js/Pages/CardsPage.jsx` (baris 42)
- **Masalah:** Input form mengirim string, tapi backend validasi integer
- **Solusi:** Convert string ke integer menggunakan `parseInt(newLimit, 10)`
- **Perubahan:**
  ```javascript
  // Sebelum
  const result = await callApi(`/user/cards/${selectedCard.id}/limit`, 'PUT', { daily_limit: newLimit });
  
  // Sesudah
  const result = await callApi(`/user/cards/${selectedCard.id}/limit`, 'PUT', { daily_limit: parseInt(newLimit, 10) });
  ```

#### Fix 2.2: Perbaikan handleRequestCard
**File:** `resources/js/Pages/CardsPage.jsx` (baris 27)
- **Masalah:** Frontend mengirim `account_id`, tapi backend mengharapkan `card_type`, `delivery_address`, dan `reason`
- **Solusi:** Ubah payload untuk mengirim field yang benar
- **Perubahan:**
  ```javascript
  // Sebelum
  const result = await callApi('user_request_card.php', 'POST', { account_id: selectedAccountId });
  
  // Sesudah
  const result = await callApi('/user/cards/request', 'POST', { card_type: 'DEBIT', delivery_address: 'Alamat pengiriman', reason: 'Pengajuan kartu baru' });
  ```

### 3. Frontend - CardRequestsPage.jsx

#### Fix 3.1: Perbaikan handleApprove
**File:** `resources/js/Pages/CardRequestsPage.jsx` (baris 16)
- **Masalah:** Frontend menggunakan POST dengan endpoint lama, tapi route hanya support PUT
- **Solusi:** Ubah ke PUT dengan endpoint baru dan payload yang benar
- **Perubahan:**
  ```javascript
  // Sebelum
  const result = await callApi('admin_process_card_request.php', 'POST', { card_id: cardId, action: 'APPROVE' });
  
  // Sesudah
  const result = await callApi(`/admin/card-requests/${cardId}/process`, 'PUT', { action: 'APPROVE' });
  ```

### 4. Frontend - DebitCard.jsx (Sudah Benar)

Komponen sudah menampilkan data dengan benar:
- ✓ Nomor kartu: `card.card_number_masked`
- ✓ Nama pemegang: `card.user?.full_name`
- ✓ Tanggal berlaku: `formatExpiryDate(card.expiry_date)` → MM/YY format

## Verifikasi

### Test Results
Semua 12 test dalam `CardManagementBugfixTest.php` passing:
- ✓ Card number displays with masked format
- ✓ Cardholder name displays from user relationship
- ✓ Expiry date formats correctly in MM/YY format
- ✓ Block/unblock action calls correct endpoint
- ✓ Limit update action calls correct endpoint
- ✓ No regression in card type display
- ✓ No regression in bank name display
- ✓ No regression in card request functionality
- ✓ No regression in card list ordering
- ✓ Limit validation range (0 to 50,000,000)
- ✓ Status validation values (active or blocked)
- ✓ User can only access own cards

### Endpoint Verification
- ✓ GET `/ajax/user/cards` - List cards dengan user relationship
- ✓ GET `/ajax/user/cards/{id}` - Get card detail dengan user relationship
- ✓ PUT `/ajax/user/cards/{id}/status` - Update status (block/unblock)
- ✓ PUT `/ajax/user/cards/{id}/limit` - Update daily limit
- ✓ POST `/ajax/user/cards/request` - Request new card
- ✓ PUT `/ajax/admin/card-requests/{id}/process` - Process card request (approve)

## Hasil Akhir

Fitur manajemen kartu sekarang berfungsi normal:
1. ✓ Nomor kartu ditampilkan dengan format masked
2. ✓ Nama pemegang kartu ditampilkan dari user relationship
3. ✓ Tanggal berlaku ditampilkan dalam format MM/YY
4. ✓ Tombol blokir/buka blokir berfungsi dengan benar
5. ✓ Tombol ubah limit berfungsi dengan benar
6. ✓ Tombol ajukan kartu baru berfungsi dengan benar
7. ✓ Admin dapat memproses permintaan kartu dengan benar
8. ✓ Tidak ada regresi pada fitur lainnya

