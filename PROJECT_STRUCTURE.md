# Project Structure - A2U Bank Digital

## 📂 Struktur Folder Lengkap

```
a2ubankdigital.my.id/                    # Root Project
│
├── 📁 app/                              # BACKEND (PHP) - Production
│   ├── 📁 cache/                        # Backend cache
│   ├── 📁 crons/                        # Scheduled jobs
│   ├── 📁 helpers/                      # Helper functions
│   │   └── email_helper.php
│   ├── 📁 templates/                    # Email templates
│   ├── 📁 uploads/                      # Backend uploads
│   ├── 📁 utils/                        # Utility functions
│   ├── 📁 vendor/                       # Composer dependencies
│   ├── 📁 webhooks/                     # Webhook handlers
│   │
│   ├── 📄 config.php                    # Main config (reads .env from root)
│   ├── 📄 composer.json                 # PHP dependencies
│   ├── 📄 composer.lock
│   │
│   ├── 📄 auth_*.php                    # Authentication endpoints (8 files)
│   ├── 📄 admin_*.php                   # Admin endpoints (90+ files)
│   ├── 📄 user_*.php                    # Customer endpoints (50+ files)
│   ├── 📄 utility_*.php                 # Utility endpoints (15+ files)
│   ├── 📄 transfer_*.php                # Transfer endpoints
│   ├── 📄 loan_*.php                    # Loan endpoints
│   ├── 📄 deposit_*.php                 # Deposit endpoints
│   ├── 📄 bill_payment_*.php            # Bill payment endpoints
│   └── 📄 ... (190+ total PHP files)
│
├── 📁 cgi-bin/                          # Development Files
│   └── 📁 frontend/                     # FRONTEND Source (React)
│       ├── 📁 public/                   # Public assets
│       ├── 📁 src/                      # Source code
│       │   ├── 📁 assets/               # Images, fonts
│       │   ├── 📁 components/           # React components
│       │   │   ├── 📁 admin/            # Admin components
│       │   │   ├── 📁 customer/         # Customer components
│       │   │   ├── 📁 layout/           # Layout components
│       │   │   ├── 📁 modals/           # Modal components
│       │   │   ├── 📁 ui/               # UI components
│       │   │   └── 📁 utils/            # Utility components
│       │   │
│       │   ├── 📁 config/               # Configuration
│       │   │   └── index.js             # API base URL config
│       │   │
│       │   ├── 📁 contexts/             # React contexts
│       │   │   ├── ModalContext.jsx
│       │   │   └── NotificationContext.jsx
│       │   │
│       │   ├── 📁 hooks/                # Custom hooks
│       │   │   └── useApi.js
│       │   │
│       │   ├── 📁 pages/                # Page components (50+ files)
│       │   │   ├── LoginPage.jsx
│       │   │   ├── RegisterPage.jsx
│       │   │   ├── DashboardPage.jsx
│       │   │   ├── AdminDashboardPage.jsx
│       │   │   └── ... (50+ pages)
│       │   │
│       │   ├── 📄 App.jsx               # Main app component
│       │   ├── 📄 main.jsx              # Entry point
│       │   └── 📄 index.css             # Global styles
│       │
│       ├── 📄 .env.example              # Environment template
│       ├── 📄 package.json              # Node dependencies
│       ├── 📄 vite.config.js            # Vite configuration
│       ├── 📄 tailwind.config.js        # Tailwind CSS config
│       ├── 📄 postcss.config.js         # PostCSS config
│       └── 📄 eslint.config.js          # ESLint config
│
├── 📁 assets/                           # FRONTEND Build Output (Production)
│   ├── index-CXnhFa7O.js               # Bundled JavaScript
│   ├── index-CUGHFXP8.css              # Bundled CSS
│   └── workbox-*.js                    # Service worker
│
├── 📁 uploads/                          # User Uploaded Files
│   ├── 📁 documents/                    # KYC documents (KTP, selfie)
│   ├── 📁 proofs/                       # Payment proofs
│   ├── a2u-icon.png
│   ├── a2u-logo.png
│   └── ... (brand assets)
│
├── 📁 cache/                            # Application Cache
│   └── .gitkeep
│
├── 📁 .git/                             # Git repository
│
├── 📄 .env                              # Environment Variables (Backend)
├── 📄 .env.example                      # Environment template
├── 📄 .gitignore                        # Git ignore rules
├── 📄 .htaccess                         # Apache configuration
│
├── 📄 index.html                        # Frontend Entry (Production)
├── 📄 manifest.webmanifest              # PWA manifest
├── 📄 sw.js                             # Service worker
│
├── 📄 README.md                         # Main documentation
├── 📄 SETUP.md                          # Setup guide
├── 📄 API_DOCUMENTATION.md              # API documentation
├── 📄 DEPLOYMENT.md                     # Deployment guide
├── 📄 CHANGELOG.md                      # Version history
├── 📄 PROJECT_STRUCTURE.md              # This file
│
└── 📄 a2uj2723_au2.sql                  # Database dump

```

## 🔍 Penjelasan Struktur

### Backend (app/)
- **Lokasi**: Root project `/app/`
- **Teknologi**: PHP 8+ dengan Composer
- **Config**: Membaca `.env` dari root project
- **Endpoints**: 190+ file PHP untuk API
- **Dependencies**: Managed by Composer (vendor/)

### Frontend Development (cgi-bin/frontend/)
- **Lokasi**: `/cgi-bin/frontend/`
- **Teknologi**: React 19 + Vite + TailwindCSS
- **Source Code**: Semua file development ada di sini
- **Dev Server**: `npm run dev` → http://localhost:5173
- **Build Output**: Akan di-copy ke root project

### Frontend Production (root/)
- **Lokasi**: Root project
- **Files**: index.html, assets/, manifest.webmanifest, sw.js
- **Generated**: Hasil build dari `cgi-bin/frontend/`
- **Access**: Langsung via web server (http://domain.com)

### Uploads (uploads/)
- **documents/**: KYC documents (KTP, selfie)
- **proofs/**: Payment proof images
- **Brand assets**: Logo, icons

### Cache (cache/)
- Application cache files
- Temporary data

## 🔄 Workflow

### Development
```bash
# Backend: Sudah running via web server
# Access: http://localhost/app/

# Frontend: Run dev server
cd cgi-bin/frontend
npm run dev
# Access: http://localhost:5173
```

### Production Build
```bash
cd cgi-bin/frontend
npm run build

# Output akan di-copy ke root:
# - index.html
# - assets/
# - manifest.webmanifest
# - sw.js
```

### Deployment
```bash
# Upload semua files ke server
# Struktur di server sama dengan local

# Backend: app/
# Frontend: index.html + assets/
# Config: .env
# Uploads: uploads/
```

## 📝 File Penting

### Backend
- `app/config.php` - Main configuration
- `app/auth_login.php` - Login endpoint
- `app/composer.json` - PHP dependencies
- `.env` - Environment variables (di root!)

### Frontend
- `cgi-bin/frontend/src/App.jsx` - Main app
- `cgi-bin/frontend/src/config/index.js` - API config
- `cgi-bin/frontend/package.json` - Node dependencies
- `index.html` - Production entry point (di root!)

### Configuration
- `.env` - Backend environment (di root!)
- `.htaccess` - Apache rewrite rules
- `manifest.webmanifest` - PWA configuration

## ⚠️ Catatan Penting

1. **Backend .env di ROOT**, bukan di `app/`
2. **Frontend source di `cgi-bin/frontend/`**, bukan di root
3. **Frontend build output di ROOT** (index.html, assets/)
4. **Uploads folder** harus writable (chmod 775)
5. **Cache folder** harus writable (chmod 775)

## 🔗 Koneksi

```
Frontend (localhost:5173)
    ↓ API calls
Backend (localhost/app/)
    ↓ reads
.env (root)
    ↓ connects to
Database (MySQL)
```

## 📊 File Count

- Backend PHP files: 190+
- Frontend pages: 50+
- React components: 100+
- Total lines of code: 50,000+
