# Fix Top-Up Approve Error

## Error yang Diperbaiki:
Field 'transaction_code' doesn't have a default value

## Perbaikan:
Menambahkan generate transaction_code saat approve top-up request

## Test:
1. Customer submit top-up di http://localhost:5174/topup
2. Admin approve di http://localhost:5174/admin/topup-requests
3. Harusnya berhasil tanpa error

## Transaction Code Format:
TRX + YmdHis + Random(1000-9999)
Contoh: TRX202602271234567890
