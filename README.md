# A2U Bank Digital - Banking System

Sistem perbankan digital lengkap dengan fitur tabungan, deposito, pinjaman, transfer, dan pembayaran.

## 📁 Struktur Proyek

```
a2ubankdigital.my.id/
├── app/                    # Backend API (PHP) - Production
│   ├── helpers/           # Helper functions
│   ├── templates/         # Email templates
│   ├── utils/             # Utility functions
│   ├── vendor/            # Composer dependencies
│   ├── webhooks/          # Webhook handlers
│   ├── crons/             # Cron jobs
│   ├── config.php         # Database & config
│   └── *.php              # 190+ API endpoints
│
├── cgi-bin/
│   └── frontend/          # Frontend Source Code (Development)
│       ├── src/
│       │   ├── components/  # React components
│       │   ├── pages/       # Page components
│       │   ├── contexts/    # React contexts
│       │   ├── hooks/       # Custom hooks
│       │   └── config/      # Frontend config
│       ├── package.json
│       └── vite.config.js
│
├── assets/                # Frontend build output (Production)
│   ├── index-*.js         # Bundled JavaScript
│   └── index-*.css        # Bundled CSS
│
├── uploads/               # User uploaded files
│   ├── documents/         # KYC documents
│   └── proofs/            # Payment proofs
│
├── cache/                 # Application cache
│
├── .env                   # Environment config (Backend)
├── .htaccess              # Apache rewrite rules
├── index.html             # Frontend entry point (Production)
├── manifest.webmanifest   # PWA manifest
└── sw.js                  # Service worker

```

## 🚀 Quick Start

```bash
# 1. Import database
mysql -u root -p a2uj2723_au2 < a2uj2723_au2.sql

# 2. Configure .env (di root project)
cp .env.example .env
# Edit database credentials

# 3. Install backend dependencies
cd app && composer install && cd ..

# 4. Install frontend dependencies & run dev server
cd cgi-bin/frontend
npm install
npm run dev
# Access: http://localhost:5173
```

📖 **Detailed guide:** [QUICKSTART.md](QUICKSTART.md) | [SETUP.md](SETUP.md)

## 🔧 Configuration

### Environment Variables (.env)

```env
# Database
DB_HOST=localhost
DB_USER=your_user
DB_PASS=your_password
DB_NAME=your_database

# JWT
JWT_SECRET=your_secret_key
JWT_ISSUER=your_domain
JWT_AUDIENCE=your_domain

# Email (SMTP)
MAIL_HOST=your_smtp_host
MAIL_PORT=465
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password

# CORS
ALLOWED_ORIGINS=http://localhost:5173,https://yourdomain.com

# Payment Gateway (Midtrans)
MIDTRANS_SERVER_KEY=your_key
MIDTRANS_CLIENT_KEY=your_key

# Digital Products (Digiflazz)
DIGIFLAZZ_USERNAME=your_username
DIGIFLAZZ_API_KEY=your_key
```

## 📱 Features

### Customer Features
- ✅ Registration & KYC
- ✅ Login with JWT authentication
- ✅ Dashboard & Account overview
- ✅ Internal & External transfers
- ✅ Bill payments
- ✅ Loan application & management
- ✅ Deposit accounts
- ✅ Card management
- ✅ Transaction history
- ✅ Notifications
- ✅ Profile management

### Admin Features
- ✅ Customer management
- ✅ Transaction monitoring
- ✅ Loan approval & disbursement
- ✅ Deposit management
- ✅ Card requests approval
- ✅ Staff management
- ✅ Reports & analytics
- ✅ Audit logs
- ✅ Teller operations

## 🔐 Security

- JWT-based authentication
- Password hashing with bcrypt
- CORS protection
- SQL injection prevention (PDO prepared statements)
- XSS protection
- CSRF protection

## 📊 Database

Database schema includes:
- users
- accounts (savings, loans, deposits)
- transactions
- loan_installments
- cards
- notifications
- audit_logs
- And more...

## 🧪 Testing

Test backend connection:
```
http://localhost/app/test_connection.php
```

Test simple endpoint:
```
http://localhost/app/test_simple.php
```

## 📝 API Documentation

### Authentication
- POST `/app/auth_login.php` - Login
- POST `/app/auth_register_request_otp.php` - Register (request OTP)
- POST `/app/auth_register_verify_otp.php` - Verify OTP
- POST `/app/auth_forgot_password_request.php` - Forgot password
- POST `/app/auth_forgot_password_reset.php` - Reset password

### Customer Endpoints
- GET `/app/dashboard_summary.php` - Dashboard data
- GET `/app/user_get_transaction_history.php` - Transaction history
- POST `/app/transfer_internal_execute.php` - Internal transfer
- POST `/app/user_loan_application_create.php` - Apply for loan
- And 100+ more endpoints...

### Admin Endpoints
- GET `/app/admin_get_customers.php` - Customer list
- GET `/app/admin_get_transactions.php` - All transactions
- POST `/app/admin_loan_disburse.php` - Disburse loan
- And 90+ more endpoints...

## 🛠️ Tech Stack

### Frontend
- React 19
- React Router v7
- Vite
- TailwindCSS
- Axios
- Chart.js
- Lucide Icons

### Backend
- PHP 8+
- MySQL/MariaDB
- Composer packages:
  - vlucas/phpdotenv
  - firebase/php-jwt
  - phpmailer/phpmailer
  - spomky-labs/otphp
  - minishlink/web-push

## 📄 License

Proprietary - A2U Bank Digital

## 👥 Support

For support, email: support@a2ubankdigital.my.id
