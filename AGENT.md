# 🤖 AGENT.md - Panduan untuk AI Agent

> Dokumen ini membantu AI agent memahami, menganalisis, dan berkontribusi pada proyek A2U Bank Digital.

---

## 📋 Daftar Isi

1. [Ringkasan Proyek](#ringkasan-proyek)
2. [Arsitektur & Tech Stack](#arsitektur--tech-stack)
3. [Struktur Direktori](#struktur-direktori)
4. [Konvensi Coding](#konvensi-coding)
5. [Cara Kerja Aplikasi](#cara-kerja-aplikasi)
6. [Panduan Modifikasi](#panduan-modifikasi)
7. [Testing](#testing)
8. [Deployment](#deployment)
9. [Troubleshooting Umum](#troubleshooting-umum)
10. [File Penting untuk Referensi](#file-penting-untuk-referensi)

---

## Ringkasan Proyek

**A2U Bank Digital** adalah platform mobile banking modern yang dibangun dengan arsitektur **Laravel + React (Inertia.js)**. Aplikasi ini menyediakan layanan perbankan digital lengkap untuk nasabah (customer) dan staf bank (back-office).

### Key Characteristics:
- **Type**: Web-based Mobile Banking Platform
- **Architecture**: Monolith (Laravel Backend + React SPA Frontend)
- **Authentication**: Session-based (Sanctum)
- **State Management**: Inertia.js (server-driven)
- **CSS Framework**: Tailwind CSS
- **Database**: MySQL 8

---

## Arsitektur & Tech Stack

### Backend (Laravel 12)
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Admin & back-office controllers
│   │   ├── User/           # Customer-facing API controllers
│   │   ├── Inertia/        # Page controllers (return Inertia responses)
│   │   └── Auth/           # Authentication controllers
│   └── Middleware/
│       ├── CheckRole.php   # Role-based access control
│       └── ...
├── Models/                 # Eloquent models (15+)
├── Services/               # Business logic layer
│   ├── ExpenseService.php  # Budgeting logic
│   ├── EmailService.php    # Email notifications
│   └── ...
└── Enums/                  # PHP enums (TransactionType, etc.)
```

### Frontend (React 19 + Inertia.js)
```
resources/js/
├── Pages/                  # 60+ React page components
├── Layouts/                # Layout wrappers (AuthenticatedLayout, etc.)
├── components/
│   ├── admin/              # Admin-specific components
│   ├── ui/                 # Reusable UI components (Button, Input)
│   └── utils/              # Utility components (PushNotificationHandler)
├── hooks/                  # Custom React hooks (useApi, useNavigate)
├── contexts/               # React contexts (Modal, Notification)
├── config/                 # Frontend config
└── utils/                  # Utilities (csrf.js, endpointMapping.js)
```

### Routes
```
routes/
├── web.php                 # Inertia page routes (GET requests)
├── ajax.php                # API/AJAX endpoints (POST, PUT, DELETE)
└── api.php                 # Public API routes (if any)
```

---

## Struktur Direktori

### Frontend Pages Mapping:
| File | Purpose |
|------|---------|
| `DashboardPage.jsx` | Customer dashboard |
| `TransferPage.jsx` | Internal transfer form |
| `ExternalTransferPage.jsx` | External bank transfer |
| `HistoryPage.jsx` | Transaction history |
| `DepositsPage.jsx` | Deposit accounts list |
| `MyLoansPage.jsx` | Active loans list |
| `BudgetingPage.jsx` | Budgeting dashboard |
| `ExpenseListPage.jsx` | Expense records list |
| `BudgetSetupPage.jsx` | Monthly budget setup |
| `ExpenseAnalyticsPage.jsx` | Expense analytics & insights |
| `AdminDashboardPage.jsx` | Admin dashboard |
| `CustomerListPage.jsx` | Admin: customer management |

### Backend Controllers:
| Controller | Location | Purpose |
|------------|----------|---------|
| `TransactionController` | `User/` | Transfer & transaction APIs |
| `ExpenseController` | `User/` | Budgeting CRUD & analytics |
| `DepositController` | `User/` | Deposit management |
| `LoanController` | `User/` | Loan management |
| `AdminDashboardController` | `Admin/` | Admin dashboard data |
| `UserPageController` | `Inertia/` | Page rendering (Inertia) |

---

## Konvensi Coding

### PHP (Backend):
1. **Naming**: PascalCase untuk class, camelCase untuk method/variable
2. **Controllers**: Method singular (index, show, store, update, destroy)
3. **Models**: Singular (User, Account, Transaction)
4. **Migrations**: Snake_case (create_users_table)
5. **Relationships**: Use Laravel relationship methods (belongsTo, hasMany, etc.)
6. **Validation**: Use FormRequest or inline validation in controller
7. **Response**: Always return `JsonResponse` for API endpoints

### React (Frontend):
1. **Components**: PascalCase (DashboardPage, TransferForm)
2. **Files**: PascalCase with .jsx extension
3. **Props**: camelCase
4. **State**: useState hooks
5. **API Calls**: Use `useApi` hook or direct fetch with CSRF token
6. **Styling**: Tailwind CSS classes (utility-first)
7. **Charts**: Chart.js via react-chartjs-2

### API Endpoints:
```php
// GET    /user/resource          → Index (list)
// GET    /user/resource/{id}     → Show (detail)
// POST   /user/resource          → Store (create)
// PUT    /user/resource/{id}     → Update
// DELETE /user/resource/{id}     → Destroy

// POST   /user/resource/action   → Custom actions (e.g., /transfer/execute)
```

---

## Cara Kerja Aplikasi

### Request Flow:
```
Browser → Laravel Router → Middleware (auth, role) → Controller → Service → Model → Database
                                    ↓
                              Inertia.js → React Component (page render)
```

### Key Patterns:

1. **Inertia Page Rendering**:
```php
// routes/web.php
Route::get('/dashboard', [UserPageController::class, 'dashboard']);

// UserPageController.php
public function dashboard() {
    return Inertia::render('DashboardPage', [
        'stats' => $this->service->getStats(),
    ]);
}
```

2. **AJAX API Calls**:
```javascript
// Frontend
const response = await fetch('/ajax/user/expense/records', {
    method: 'GET',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCsrfToken(),
    },
});
```

3. **Role-Based Access**:
```php
// routes/ajax.php
Route::middleware(['role:customer'])->group(function () {
    // Only customer can access
});

Route::middleware(['role:admin,teller,cs'])->group(function () {
    // Admin, teller, CS can access
});
```

4. **Auto-Import (Budgeting)**:
```php
// TransactionController.php (after successful transfer)
$this->expenseService->autoImportTransaction($transaction);
```

---

## Panduan Modifikasi

### Menambahkan Fitur Baru:

#### 1. Database Migration:
```php
// database/migrations/YYYY_MM_DD_create_xxx_table.php
Schema::create('xxx', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('name');
    $table->timestamps();
});
```

#### 2. Model:
```php
// app/Models/Xxx.php
class Xxx extends Model {
    protected $fillable = ['name', 'user_id'];
    
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
```

#### 3. Service (Business Logic):
```php
// app/Services/XxxService.php
class XxxService {
    public function getSomething($userId) {
        return Xxx::where('user_id', $userId)->get();
    }
}
```

#### 4. Controller:
```php
// app/Http/Controllers/User/XxxController.php
class XxxController extends Controller {
    public function __construct(XxxService $xxxService) {
        $this->xxxService = $xxxService;
    }
    
    public function index(): JsonResponse {
        $data = $this->xxxService->getSomething(auth()->id());
        return response()->json(['data' => $data]);
    }
}
```

#### 5. Routes:
```php
// routes/web.php (Inertia page)
Route::get('/xxx', [UserPageController::class, 'xxx']);

// routes/ajax.php (API endpoint)
Route::middleware(['web', 'auth:web', 'role:customer'])->group(function () {
    Route::get('/user/xxx', [XxxController::class, 'index']);
    Route::post('/user/xxx', [XxxController::class, 'store']);
});
```

#### 6. React Page:
```jsx
// resources/js/Pages/XxxPage.jsx
import { useState, useEffect } from 'react';

const XxxPage = () => {
    const [data, setData] = useState([]);
    
    useEffect(() => {
        fetch('/ajax/user/xxx', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(res => setData(res.data));
    }, []);
    
    return (
        <div>
            {/* Page content */}
        </div>
    );
};

export default XxxPage;
```

#### 7. Seeder (Optional):
```php
// database/seeders/XxxSeeder.php
Xxx::create(['name' => 'Default Item']);
```

---

## Testing

### Unit Tests:
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=TransactionTest

# Run with coverage
php artisan test --coverage
```

### Frontend Testing:
```bash
# Build for production
npm run build

# Development mode
npm run dev
```

### Manual Testing:
1. Login as customer (customer@bank.com / password)
2. Test all CRUD operations
3. Check responsive design on mobile
4. Verify error handling

---

## Deployment

### Production Steps:
```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Seed data (if needed)
php artisan db:seed --class=XxxSeeder

# 5. Clear cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue worker
php artisan queue:restart
```

### Environment Variables:
```env
APP_NAME="A2U Bank Digital"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=a2ubankdigital
DB_USERNAME=root
DB_PASSWORD=secret
```

---

## Troubleshooting Umum

### 1. CSRF Token Mismatch:
```javascript
// Gunakan helper dari utils/csrf.js
import { getCsrfToken } from '../utils/csrf';

fetch('/ajax/endpoint', {
    headers: {
        'X-XSRF-TOKEN': getCsrfToken(),
    }
});
```

### 2. Intelephense Errors in Routes:
Routes/ajax.php mungkin menunjukkan error undefined method pada closure. Ini normal karena IDE tidak bisa resolve `auth()` dalam closure.忽略 saja.

### 3. Migration Errors:
```bash
# Reset database
php artisan migrate:fresh --seed

# Or rollback specific migration
php artisan migrate:rollback --step=1
```

### 4. Frontend Build Errors:
```bash
# Clear node_modules
rm -rf node_modules
npm install
npm run build
```

---

## File Penting untuk Referensi

### Dokumentasi:
| File | Purpose |
|------|---------|
| `FEATURES.md` | Daftar lengkap semua fitur (57+) |
| `ROADMAP.md` | Peta jalan pengembangan |
| `IMPLEMENTASI_PERSONAL_BUDGETING.md` | Implementasi fitur budgeting |
| `README.md` | Setup guide & akun testing |

### Code Reference:
| File | Purpose |
|------|---------|
| `app/Http/Controllers/User/ExpenseController.php` | Contoh CRUD controller lengkap |
| `app/Services/ExpenseService.php` | Contoh service layer |
| `resources/js/Pages/BudgetingPage.jsx` | Contoh React page dengan Chart.js |
| `routes/ajax.php` | Semua API endpoints |
| `routes/web.php` | Semua Inertia page routes |

### Configuration:
| File | Purpose |
|------|---------|
| `.env` | Environment variables |
| `config/auth.php` | Authentication config |
| `config/database.php` | Database config |
| `vite.config.js` | Frontend build config |
| `tailwind.config.js` | Tailwind CSS config |

---

## 🎯 Tips untuk AI Agent

1. **Selalu baca dokumentasi dulu** sebelum mengubah kode
2. **Ikuti konvensi yang sudah ada** (naming, structure, patterns)
3. **Test perubahan** sebelum submit
4. **Dokumentasikan** setiap fitur baru
5. **Jangan hapus** kode yang ada tanpa alasan jelas
6. **Gunakan Service Layer** untuk business logic kompleks
7. **Handle errors gracefully** dengan try-catch
8. **Return consistent response format** (JSON)
9. **Use relationship methods** di Model, jangan query manual
10. **Keep controllers thin**, logic di Service layer

---

*Last updated: August 2026*
*For AI agents: Please update this document when making significant changes to the project structure.*
