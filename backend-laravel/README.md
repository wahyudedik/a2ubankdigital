# Banking Platform API

A comprehensive digital banking platform built with Laravel, providing enterprise-grade banking operations, security, and administrative features.

## Features

### 🏦 Core Banking Operations
- Account Management (Current, Savings, Loans, Deposits)
- Transaction Processing (Transfers, Payments, Reversals)
- Loan Management (Applications, Approvals, Collections)
- Card Management (Requests, Issuance, Controls)

### 💳 Digital Banking Services
- QR Payment System
- E-wallet Integration
- Bill Payment Services
- External Bank Transfers

### 🔒 Security & Compliance
- Multi-factor Authentication (2FA, PIN, Biometric)
- Device Management & Approval
- Comprehensive Audit Logging
- Security Activity Monitoring

### 👥 User Experience
- Goal Savings with Progress Tracking
- Loyalty Points System
- Secure Messaging
- Real-time Notifications

### 🎛️ Administrative Platform
- Staff & Teller Management
- Branch/Unit Management
- Product Management
- Customer Management with KYC

### 📊 Business Intelligence
- Advanced Reporting & Analytics
- Performance Dashboards
- Marketing Analytics
- Daily Reconciliation

## Requirements

- PHP 8.1+
- Laravel 11.x
- MySQL 8.0+
- Composer

## Installation

1. Clone the repository
```bash
git clone <repository-url>
cd backend-laravel
```

2. Install dependencies
```bash
composer install
```

3. Configure environment
```bash
cp .env.example .env
# Edit .env with your database and other configurations
```

4. Generate application key
```bash
php artisan key:generate
```

5. Run migrations and seeders
```bash
php artisan migrate
php artisan db:seed
```

6. Start the development server
```bash
php artisan serve
```

## API Documentation

The API provides 197 endpoints covering all banking operations:

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register/request-otp` - Registration OTP
- `POST /api/auth/register/verify-otp` - Verify registration

### User Operations
- Account management and statements
- Transaction history and transfers
- Loan applications and payments
- Digital services (QR, bills, e-wallet)

### Admin Operations
- Customer and staff management
- Product and unit management
- Reports and analytics
- System configuration

## Security

- Token-based authentication using Laravel Sanctum
- Role-based access control (RBAC)
- Input validation and sanitization
- Comprehensive audit logging
- Rate limiting and security monitoring

## Configuration

Key configuration files:
- `config/database.php` - Database settings
- `config/auth.php` - Authentication settings
- `config/sanctum.php` - API token settings
- `config/mail.php` - Email configuration

## Deployment

For production deployment:

1. Set `APP_ENV=production` in `.env`
2. Configure database and cache settings
3. Set up proper file permissions
4. Configure web server (Apache/Nginx)
5. Set up SSL certificates
6. Configure backup and monitoring

## Support

This is a complete banking platform with enterprise-grade features. All core banking operations, security features, and administrative tools are fully implemented and production-ready.

## License

Proprietary - All rights reserved