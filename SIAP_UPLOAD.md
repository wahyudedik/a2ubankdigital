# ✅ SIAP UPLOAD KE CPANEL

## STATUS: SEMUA SUDAH BENAR ✅

### Yang Sudah Diperbaiki:
1. ✅ `.htaccess` di root - redirect ke `frontend/dist/`
2. ✅ `.htaccess` di `frontend/dist/` - path sudah benar
3. ✅ File test dihapus (`test_php_root.php`)
4. ✅ Frontend sudah di-build
5. ✅ Backend vendor ada
6. ✅ Config production sudah benar

---

## LANGKAH UPLOAD:

### 1. Build Frontend (WAJIB!)
```bash
cd frontend
npm run build
```

### 2. Upload ke cPanel
Upload ke `/public_html/coba.a2ubankdigital.my.id/`:
- ✅ `.htaccess` (root)
- ✅ `frontend/` (seluruh folder termasuk dist/)
- ✅ `backend/` (seluruh folder termasuk vendor/)

### 3. Setup .env
```bash
# Di cPanel, copy:
backend/.env.production → backend/.env
```

### 4. Set Permissions
```
backend/app/     → 755
backend/uploads/ → 755
backend/cache/   → 755
backend/.env     → 644
```

### 5. Test
- Frontend: https://coba.a2ubankdigital.my.id
- Backend: https://coba.a2ubankdigital.my.id/backend/app/test_db_connection.php

---

## STRUKTUR DI SERVER:
```
/public_html/coba.a2ubankdigital.my.id/
├── .htaccess                    ← Redirect ke frontend/dist/
├── frontend/
│   └── dist/
│       ├── .htaccess            ← React Router
│       ├── index.html
│       └── assets/
└── backend/
    ├── .env                     ← Copy dari .env.production
    ├── app/
    │   ├── vendor/              ← WAJIB UPLOAD!
    │   └── ...
    ├── uploads/
    └── cache/
```

SELESAI! Tinggal upload saja.
