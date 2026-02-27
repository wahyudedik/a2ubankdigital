# API Documentation - A2U Bank Digital

Base URL: `http://yourdomain.com/app`

## Authentication

Semua endpoint (kecuali public) memerlukan JWT token di header:
```
Authorization: Bearer <your_jwt_token>
```

## Response Format

### Success Response
```json
{
  "status": "success",
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error description"
}
```

---

## 🔐 Authentication Endpoints

### 1. Login
**POST** `/auth_login.php`

Request:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

Response:
```json
{
  "status": "success",
  "message": "Login berhasil",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "bankId": "BNK001",
    "roleId": 9,
    "fullName": "John Doe",
    "email": "user@example.com"
  }
}
```

### 2. Register - Request OTP
**POST** `/auth_register_request_otp.php`

Request:
```json
{
  "email": "newuser@example.com",
  "phone_number": "081234567890",
  "full_name": "New User"
}
```

### 3. Register - Verify OTP
**POST** `/auth_register_verify_otp.php`

Request:
```json
{
  "email": "newuser@example.com",
  "otp": "123456",
  "password": "securepassword",
  "nik": "1234567890123456",
  "mother_maiden_name": "Mother Name",
  "pob": "Jakarta",
  "dob": "1990-01-01",
  "gender": "L",
  "address_ktp": "Full Address"
}
```

### 4. Forgot Password - Request
**POST** `/auth_forgot_password_request.php`

Request:
```json
{
  "email": "user@example.com"
}
```

### 5. Forgot Password - Reset
**POST** `/auth_forgot_password_reset.php`

Request:
```json
{
  "token": "reset_token_from_email",
  "new_password": "newpassword123"
}
```

---

## 👤 Customer Endpoints

### Dashboard
**GET** `/dashboard_summary.php`

Response:
```json
{
  "status": "success",
  "data": {
    "total_balance": 1000000,
    "accounts": [...],
    "recent_transactions": [...],
    "notifications_count": 5
  }
}
```

### Get Transaction History
**GET** `/user_get_transaction_history.php?limit=20&offset=0`

### Get Account Detail
**GET** `/user_get_account_detail.php?account_id=123`

### Internal Transfer - Inquiry
**POST** `/transfer_internal_inquiry.php`

Request:
```json
{
  "from_account_id": 123,
  "to_account_number": "1100000085204",
  "amount": 50000
}
```

### Internal Transfer - Execute
**POST** `/transfer_internal_execute.php`

Request:
```json
{
  "from_account_id": 123,
  "to_account_number": "1100000085204",
  "amount": 50000,
  "notes": "Transfer notes"
}
```

### External Transfer - Inquiry
**POST** `/user_external_transfer_inquiry.php`

### External Transfer - Execute
**POST** `/user_external_transfer_execute.php`

### Create Loan Application
**POST** `/user_loan_application_create.php`

Request:
```json
{
  "loan_product_id": 16,
  "loan_amount": 2000000,
  "tenor": 12,
  "purpose": "Business capital"
}
```

### Get My Loans
**GET** `/user_get_loans.php`

### Get Loan Detail
**GET** `/user_get_loan_detail.php?loan_id=44`

### Pay Loan Installment
**POST** `/user_pay_installment.php`

Request:
```json
{
  "installment_id": 629,
  "from_account_id": 51
}
```

### Open Deposit Account
**POST** `/deposit_account_create.php`

Request:
```json
{
  "deposit_product_id": 1,
  "amount": 50000,
  "from_account_id": 51
}
```

### Get Deposits
**GET** `/user_get_deposits.php`

### Request Card
**POST** `/user_request_card.php`

Request:
```json
{
  "account_id": 50,
  "card_type": "DEBIT"
}
```

### Get Cards
**GET** `/user_get_cards.php`

### Create Top-up Request
**POST** `/user_create_topup_request.php`

Request:
```json
{
  "account_id": 51,
  "amount": 100000,
  "payment_method": "BANK_TRANSFER"
}
```

### Create Withdrawal Request
**POST** `/user_create_withdrawal_request.php`

Request:
```json
{
  "account_id": 51,
  "amount": 50000,
  "withdrawal_account_id": 1
}
```

### Update Profile
**POST** `/user_profile_update.php`

### Change Password
**POST** `/user_security_update_password.php`

### Change PIN
**POST** `/user_security_update_pin.php`

---

## 👨‍💼 Admin Endpoints

### Get Dashboard Summary
**GET** `/admin_get_dashboard_summary.php`

### Get Customers
**GET** `/admin_get_customers.php?search=&status=&limit=20&offset=0`

### Get Customer Detail
**GET** `/admin_get_customer_detail.php?customer_id=85`

### Add Customer (Offline Registration)
**POST** `/admin_add_customer.php`

Request:
```json
{
  "full_name": "Customer Name",
  "email": "customer@example.com",
  "phone_number": "081234567890",
  "nik": "1234567890123456",
  "mother_maiden_name": "Mother Name",
  "pob": "Jakarta",
  "dob": "1990-01-01",
  "gender": "L",
  "address_ktp": "Full Address",
  "unit_id": 17,
  "initial_deposit": 50000
}
```

### Edit Customer
**POST** `/admin_edit_customer.php`

### Update Customer Status
**POST** `/admin_update_customer_status.php`

Request:
```json
{
  "customer_id": 85,
  "new_status": "BLOCKED"
}
```

### Get All Transactions
**GET** `/admin_get_transactions.php?limit=50&offset=0`

### Get Transaction Detail
**GET** `/admin_get_transaction_detail.php?transaction_id=309`

### Get Loan Applications
**GET** `/admin_loan_applications_get.php?status=SUBMITTED`

### Get Loan Application Detail
**GET** `/admin_loan_application_get_detail.php?loan_id=44`

### Update Loan Application Status
**POST** `/admin_loan_application_update_status.php`

Request:
```json
{
  "loan_id": 44,
  "new_status": "APPROVED"
}
```

### Disburse Loan
**POST** `/admin_loan_disburse.php`

Request:
```json
{
  "loan_id": 44
}
```

### Get All Loans
**GET** `/admin_get_all_loans.php`

### Process Top-up Request
**POST** `/admin_process_topup_request.php`

Request:
```json
{
  "request_id": 10,
  "action": "APPROVE"
}
```

### Process Withdrawal Request
**POST** `/admin_process_withdrawal_request.php`

### Disburse Withdrawal
**POST** `/admin_disburse_withdrawal.php`

Request:
```json
{
  "withdrawal_request_id": 15
}
```

### Process Card Request
**POST** `/admin_process_card_request.php`

Request:
```json
{
  "card_id": 8,
  "action": "APPROVE"
}
```

### Get Staff List
**GET** `/admin_get_staff_list.php`

### Create Staff
**POST** `/admin_create_staff_user.php`

Request:
```json
{
  "full_name": "Staff Name",
  "email": "staff@example.com",
  "role_id": 3,
  "unit_id": 17
}
```

### Edit Staff
**POST** `/admin_edit_staff.php`

### Get Audit Log
**GET** `/admin_get_audit_log.php?limit=100&offset=0`

### Teller - Deposit
**POST** `/admin_teller_deposit.php`

Request:
```json
{
  "account_number": "1100000085204",
  "amount": 100000,
  "notes": "Cash deposit"
}
```

### Teller - Withdrawal
**POST** `/admin_teller_withdrawal.php`

### Teller - Pay Installment
**POST** `/admin_teller_pay_installment.php`

---

## 🛠️ Utility Endpoints

### Get Public Config
**GET** `/utility_get_public_config.php`

### Get FAQ
**GET** `/utility_get_faq.php`

### Get Bank Branches
**GET** `/utility_get_bank_branches.php`

### Get Nearest Units
**GET** `/utility_get_nearest_units.php?lat=-6.2&lng=106.8`

### Loan Calculator
**POST** `/utility_loan_calculator.php`

Request:
```json
{
  "loan_amount": 2000000,
  "interest_rate_pa": 10,
  "tenor": 12,
  "tenor_unit": "BULAN"
}
```

---

## 📊 Reports Endpoints

### Daily Report
**GET** `/admin_get_daily_report.php?date=2026-02-27`

### Profit & Loss Report
**GET** `/admin_get_profit_loss_report.php?start_date=2026-01-01&end_date=2026-12-31`

### Customer Growth Report
**GET** `/admin_get_customer_growth_report.php?period=monthly&year=2026`

### NPL Report
**GET** `/admin_get_npl_report.php`

### Teller Report
**GET** `/admin_get_teller_report.php?teller_id=1&date=2026-02-27`

---

## 🔔 Error Codes

- `400` - Bad Request (Invalid input)
- `401` - Unauthorized (Invalid/missing token)
- `403` - Forbidden (Insufficient permissions)
- `404` - Not Found
- `500` - Internal Server Error

## 📝 Notes

1. Semua tanggal menggunakan format: `YYYY-MM-DD`
2. Semua timestamp menggunakan format: `YYYY-MM-DD HH:MM:SS`
3. Amount dalam format decimal: `1000000.00`
4. JWT token expired dalam 24 jam
5. OTP expired dalam 5 menit
6. Password reset token expired dalam 1 jam
