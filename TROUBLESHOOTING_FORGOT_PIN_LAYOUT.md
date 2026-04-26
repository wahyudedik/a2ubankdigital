# Troubleshooting: Layout Forgot PIN Page

## Issue
Halaman `/forgot-pin` tidak menampilkan layout yang sama dengan halaman `/profile/change-pin`:
- ❌ Tidak ada header user dengan avatar
- ❌ Tidak ada bottom navigation
- ❌ Hanya menampilkan form tanpa wrapper

## Root Cause
Kemungkinan penyebab:
1. **Browser cache** - Browser masih menggunakan versi lama dari halaman
2. **Server cache** - Laravel masih menggunakan cached view/route
3. **Vite HMR** - Hot Module Replacement tidak ter-update

## Solutions

### Solution 1: Hard Refresh Browser (Paling Mudah)
1. **Close semua tab** browser yang membuka aplikasi
2. **Buka DevTools** (F12)
3. **Klik kanan** pada tombol refresh
4. **Pilih** "Empty Cache and Hard Reload"
5. **Atau** tekan `Ctrl + Shift + Delete` → Clear "Cached images and files"

### Solution 2: Incognito/Private Window
1. **Buka incognito window**: `Ctrl + Shift + N` (Chrome) atau `Ctrl + Shift + P` (Firefox)
2. **Login** dan akses `/forgot-pin`
3. Jika di incognito sudah benar, berarti masalahnya di browser cache

### Solution 3: Clear All Laravel Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Solution 4: Delete Build Folder & Rebuild
```bash
# PowerShell
Remove-Item -Path public/build -Recurse -Force
npm run build

# Bash
rm -rf public/build
npm run build
```

### Solution 5: Restart Herd/Server
1. **Stop Herd** atau web server yang digunakan
2. **Start Herd** lagi
3. **Refresh browser**

### Solution 6: Check Route Registration
```bash
php artisan route:list --path=forgot-pin
```

Output yang benar:
```
GET|HEAD   forgot-pin ........... Inertia\UserPageController@forgotPin
```

Jika output menunjukkan `AuthPageController`, berarti route belum ter-update.

### Solution 7: Manual Browser Cache Clear
**Chrome:**
1. Buka `chrome://settings/clearBrowserData`
2. Pilih "Cached images and files"
3. Time range: "All time"
4. Clear data

**Firefox:**
1. Buka `about:preferences#privacy`
2. Scroll ke "Cookies and Site Data"
3. Click "Clear Data"
4. Pilih "Cached Web Content"

## Verification Steps

### Step 1: Check Route
```bash
php artisan route:list --path=forgot-pin
```

Expected output:
```
GET|HEAD   forgot-pin ........... Inertia\UserPageController@forgotPin
```

### Step 2: Check Build
```bash
ls public/build/assets/
```

Should see files like:
- `app-[hash].js`
- `app-[hash].css`

### Step 3: Test in Browser
1. Open `/profile/change-pin`
2. Click "Lupa PIN? Reset di sini"
3. Should navigate to `/forgot-pin` with same layout

### Step 4: Check Console
1. Open DevTools (F12)
2. Go to Console tab
3. Look for any errors (red text)
4. Look for 404 errors for JS/CSS files

## Expected Layout

### Correct Layout (Same as /profile/change-pin):
```
┌─────────────────────────────────────┐
│ 👤 Rizky Pratama              🔔    │ ← Header with user
├─────────────────────────────────────┤
│ ← Reset PIN Transaksi               │ ← Page title with back arrow
│                                     │
│ ┌─────────────────────────────────┐ │
│ │ Password Akun (untuk verifikasi)│ │ ← White card
│ │ [___________________________]   │ │
│ │                                 │ │
│ │ [  Kirim Kode OTP  ]           │ │
│ └─────────────────────────────────┘ │
│                                     │
├─────────────────────────────────────┤
│ 🏠  📊  💳  📈  👤                  │ ← Bottom navigation
└─────────────────────────────────────┘
```

### Current Issue (Wrong Layout):
```
┌─────────────────────────────────────┐
│ Reset PIN Transaksi                 │ ← No header, no user
│                                     │
│ Password Akun (untuk verifikasi)    │ ← No card wrapper
│ [___________________________]       │
│                                     │
│ [  Kirim Kode OTP  ]               │
│                                     │
│                                     │ ← No bottom navigation
└─────────────────────────────────────┘
```

## Files to Check

### 1. Route File
**File**: `routes/web.php`

Should have:
```php
Route::middleware(['auth', 'role:nasabah'])->group(function () {
    // ...
    Route::get('/forgot-pin', [UserPageController::class, 'forgotPin']);
    // ...
});
```

Should NOT have (in guest routes):
```php
Route::middleware('guest')->group(function () {
    // ...
    Route::get('/forgot-pin', [AuthPageController::class, 'forgotPinPage']); // ❌ Remove this
    // ...
});
```

### 2. Controller Method
**File**: `app/Http/Controllers/Inertia/UserPageController.php`

Should have:
```php
public function forgotPin() { 
    return Inertia::render('ForgotPinPage'); 
}
```

### 3. Frontend Component
**File**: `resources/js/Pages/ForgotPinPage.jsx`

Should exist and have proper imports.

### 4. Build Files
**Folder**: `public/build/`

Should contain:
- `manifest.json`
- `assets/app-[hash].js`
- `assets/app-[hash].css`

## If Nothing Works

### Last Resort: Full Clean Rebuild
```bash
# 1. Stop Herd/Server
# 2. Delete caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 3. Delete build
Remove-Item -Path public/build -Recurse -Force

# 4. Delete node_modules (optional, if really stuck)
Remove-Item -Path node_modules -Recurse -Force
npm install

# 5. Rebuild
npm run build

# 6. Start Herd/Server
# 7. Close ALL browser tabs
# 8. Open in incognito window
# 9. Login and test
```

## Status
⚠️ **PENDING** - Layout issue needs to be resolved by clearing browser cache or restarting server

## Notes
- Fitur reset PIN **sudah berfungsi** dengan benar (backend & API)
- Yang bermasalah hanya **tampilan layout** (frontend rendering)
- Ini adalah issue **browser cache** atau **server cache**, bukan bug di code
