# Pages Data Requirements for Inertia SSR Conversion

All 54 pages analyzed. Each entry documents API calls, expected data, form submissions, URL params, and query params.

---

## 1. AdminAuditLogPage

- **API Calls (GET):** `admin_get_audit_log.php?page={page}&action={filter}`
- **Data from server:** `{ data: AuditLog[], pagination: { current_page, total_pages } }`
  - AuditLog: `{ id, created_at, full_name, action, details, transaction_code, ip_address }`
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** `page` (pagination), `action` (filter by action type)

---

## 2. AdminBuildPage

- **API Calls (POST):** `callApi('/admin_trigger_build.php', 'POST')`
- **Data from server:** `{ status, output, message }`
- **Form Actions:** POST trigger build (no form data)
- **URL Params:** None
- **Query Params:** None

---

## 3. AdminDashboardPage

- **API Calls (GET):** `admin_get_dashboard_summary.php`
- **Data from server:** `{ data: { kpi: {}, tasks: { pendingTopups, pendingWithdrawals, pendingLoans, pendingLoanDisbursements, pendingWithdrawalDisbursements }, recentActivities: Activity[] } }`
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** None
- **Note:** Also renders `<CustomerGrowthChart />` which fetches its own data internally

---

## 4. AdminDepositsListPage

- **API Calls (GET):** `admin_get_customer_deposits.php?search={search}&status={status}`
- **Data from server:** `{ data: { deposits: Deposit[], summary: { totalActiveBalance, totalDeposits, maturingThisMonth } } }`
  - Deposit: `{ id, customer_name, account_number, product_name, balance, interest_earned, maturity_date, is_near_maturity }`
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** `search` (text), `status` (active|near_maturity|matured)

---

## 5. AdminLoansListPage

- **API Calls (GET):** `admin_get_all_loans.php?search={search}&status={status}&page={page}`
- **Data from server:** `{ data: Loan[], summary: { totalActiveLoans, activeLoansCount, overdueLoansCount }, pagination: { current_page, total_pages, total_records } }`
  - Loan: `{ id, customer_name, product_name, loan_amount, outstanding_principal, next_due_date, overdue_installments_count }`
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** `search`, `status` (disbursed|overdue|completed), `page`

---

## 6. AdminNotificationsPage

- **API Calls (GET):** `notifications_get_list.php`
- **API Calls (PUT):** `user_mark_notification_read.php` → `{ notification_id: 'all' }`
- **Data from server:** `{ data: Notification[] }` — `{ id, title, message, is_read, created_at }`
- **Form Actions:** PUT mark all as read
- **URL Params:** None
- **Query Params:** None

---

## 7. AdminTellerDepositPage

- **API Calls (POST):** `transfer_internal_inquiry.php` → `{ destination_account_number }`
- **API Calls (POST):** `admin_teller_deposit.php` → `{ account_number, amount }`
- **Data from server (inquiry):** `{ data: { recipient_name, account_number } }`
- **Data from server (deposit):** `{ data: { transaction_id, initial_balance, final_balance } }`
- **Form Actions:** POST inquiry, POST deposit
- **URL Params:** None
- **Query Params:** None
- **Note:** Opens print receipt via `window.open(/admin/print-receipt/{transaction_id})`

---

## 8. AdminTellerLoanPaymentPage

- **API Calls (GET):** `admin_search_installments.php?q={searchTerm}`
- **API Calls (POST):** `admin_teller_pay_installment.php` → `{ installment_id, cash_amount }`
- **Data from server (search):** `{ data: Installment[] }` — `{ installment_id, customer_name, product_name, installment_number, due_date, amount_due, penalty_amount }`
- **Data from server (pay):** `{ data: { transaction_id } }`
- **Form Actions:** POST pay installment
- **URL Params:** None
- **Query Params:** `q` (search term, min 3 chars)

---

## 9. AdminTopUpRequestsPage

- **API Calls (GET):** `admin_get_topup_requests.php?status={status}`
- **API Calls (POST):** `admin_process_topup_request.php` → `{ request_id, action, admin_notes }`
- **Data from server:** `{ data: TopUpRequest[] }` — `{ id, customer_name, amount, payment_method, created_at, processed_at, proof_image_url, ... }`
- **Form Actions:** POST approve/reject with optional rejection reason
- **URL Params:** None
- **Query Params:** `status` (PENDING|APPROVED|REJECTED)

---

## 10. AdminUnitsPage

- **API Calls (GET):** `admin_get_units.php`
- **Data from server:** `{ data: Branch[] }` — `{ id, unit_name, is_active, units: Unit[] }`
  - Unit: `{ id, unit_name, unit_type, is_active, parent_id }`
- **Form Actions:** Save handled via `UnitModal` component (likely POST to create/update unit)
- **URL Params:** None
- **Query Params:** None

---

## 11. AdminWithdrawalRequestsPage

- **API Calls (GET):** `admin_get_withdrawal_requests.php?status={status}`
- **API Calls (POST):** `admin_process_withdrawal_request.php` → `{ request_id, action }`
- **API Calls (POST):** `admin_disburse_withdrawal.php` → `{ request_id }`
- **Data from server:** `{ data: WithdrawalRequest[] }` — `{ id, customer_name, amount, bank_name, account_number, account_name, created_at, processed_at }`
- **Form Actions:** POST approve/reject, POST disburse
- **URL Params:** None
- **Query Params:** `status` (PENDING|APPROVED|COMPLETED|REJECTED)

---

## 12. BeneficiaryListPage

- **API Calls (GET):** `beneficiaries_get_list.php`
- **API Calls (POST):** `transfer_internal_inquiry.php` → `{ destination_account_number }`
- **API Calls (POST):** `beneficiaries_add.php` → `{ account_number, nickname }`
- **API Calls (POST):** `beneficiaries_delete.php` → `{ id }`
- **Data from server (list):** `{ data: Beneficiary[] }` — `{ id, nickname, beneficiary_name, beneficiary_account_number }`
- **Data from server (inquiry):** `{ data: { recipient_name } }`
- **Form Actions:** POST add beneficiary, POST delete beneficiary
- **URL Params:** None
- **Query Params:** None

---

## 13. BillPaymentPage

- **API Calls (GET):** `utility_get_billers.php`
- **API Calls (POST):** `bill_payment_inquiry.php` → `{ product_code, customer_no }`
- **API Calls (POST):** `bill_payment_execute.php` → `{ pin, buyer_sku_code, customer_no, amount, fee, total, description }`
- **Data from server (billers):** `{ data: Product[] }` — `{ buyer_sku_code, product_name, category, type, price }`
- **Data from server (inquiry):** `{ data: { product_name, customer_name, customer_no, selling_price, admin_fee, total_amount } }`
- **Form Actions:** POST inquiry, POST execute payment
- **URL Params:** None
- **Query Params:** None

---

## 14. CardRequestsPage

- **API Calls (GET):** `admin_get_card_requests.php`
- **API Calls (POST):** `admin_process_card_request.php` → `{ card_id, action: 'APPROVE' }`
- **Data from server:** `{ data: CardRequest[] }` — `{ id, customer_name, account_number, requested_at, status }`
- **Form Actions:** POST approve card
- **URL Params:** None
- **Query Params:** None

---

## 15. CardsPage

- **API Calls (GET):** `user_get_cards.php`, `user_get_all_accounts.php`
- **API Calls (POST):** `user_request_card.php` → `{ account_id }`
- **API Calls (POST):** `user_update_card_status.php` → `{ card_id, new_status }`
- **API Calls (POST):** `user_set_card_limit.php` → `{ card_id, daily_limit }`
- **Data from server (cards):** `{ data: Card[] }` — `{ id, status, daily_limit, ... (DebitCard props) }`
- **Data from server (accounts):** `{ data: { TABUNGAN: Account[] } }` — `{ id, account_number, balance }`
- **Form Actions:** POST request card, POST update status, POST set limit
- **URL Params:** None
- **Query Params:** None

---

## 16. ChangePasswordPage

- **API Calls (POST):** `user_security_update_password.php` → `{ current_password, new_password }`
- **Data from server:** `{ status, message }`
- **Form Actions:** POST change password
- **URL Params:** None
- **Query Params:** None

---

## 17. ChangePinPage

- **API Calls (POST):** `user_security_update_pin.php` → `{ old_pin, new_pin, confirm_pin }`
- **Data from server:** `{ status, message }`
- **Form Actions:** POST change PIN
- **URL Params:** None
- **Query Params:** None

---

## 18. CustomerAddPage

- **API Calls (GET):** `admin_get_units.php` (for unit dropdown)
- **API Calls (POST):** `admin_add_customer.php` → `{ full_name, email, phone_number, nik, mother_maiden_name, pob, dob, gender, address_ktp, unit_id }`
- **Data from server (units):** `{ data: Branch[] }` (same as AdminUnitsPage)
- **Form Actions:** POST add customer
- **URL Params:** None
- **Query Params:** None

---

## 19. CustomerDetailPage

- **API Calls (GET):** `admin_get_customer_detail.php?id={customerId}`
- **API Calls (PUT):** `admin_update_customer_status.php` → `{ customer_id, new_status }`
- **API Calls (POST):** `admin_force_pay_installment.php` → `{ installment_id }`
- **Data from server:** `{ data: CustomerDetail }` — full customer object with:
  - `{ id, full_name, bank_id, email, phone_number, nik, mother_maiden_name, pob, dob, gender, address_ktp, status, branch_name, unit_name, ktp_image_path, selfie_image_path, accounts: Account[], loans: Loan[] }`
  - Account: `{ id, account_number, balance, account_type, status, deposit_product_name, interest_earned, maturity_date }`
  - Loan: `{ id, product_name, loan_amount, tenor, tenor_unit, status, installments: Installment[] }`
  - Installment: `{ id, installment_number, due_date, amount_due, penalty_amount, status }`
- **Form Actions:** PUT update status, POST force pay installment
- **URL Params:** `customerId` (from useParams)
- **Query Params:** None

---

## 20. CustomerEditPage

- **API Calls (GET):** `admin_get_customer_detail.php?id={customerId}`, `admin_get_units.php`
- **API Calls (PUT):** `admin_edit_customer.php` → `{ id, full_name, email, phone_number, status, nik, mother_maiden_name, pob, dob, gender, address_ktp, unit_id }`
- **Data from server:** Same as CustomerDetailPage + units list
- **Form Actions:** PUT edit customer
- **URL Params:** `customerId` (from useParams)
- **Query Params:** None

---

## 21. CustomerListPage

- **API Calls (GET):** `admin_get_customers.php?page={page}&limit=10&search={search}`
- **Data from server:** `{ data: Customer[], pagination: { current_page, total_pages } }`
  - Customer: `{ id, bank_id, full_name, email, status, created_at }`
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** `page`, `limit`, `search`

---

## 22. DashboardPage (User)

- **API Calls (GET):** `dashboard_summary.php`
- **Data from server:** `{ data: { balance, account_number, recent_transactions: Transaction[], weekly_summary: { labels, pemasukan, pengeluaran } } }`
  - Transaction: `{ transaction_code, transaction_type, flow, description, amount, created_at }`
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** None

---

## 23. DepositDetailPage

- **API Calls (GET):** `user_get_deposit_detail.php?id={depositId}`
- **API Calls (POST):** `deposit_account_disburse.php` → `{ deposit_id }`
- **Data from server:** `{ data: { product_name, account_number, status, principal, interest_rate_pa, placement_date, maturity_date, interest_earned } }`
- **Form Actions:** POST disburse deposit
- **URL Params:** `depositId` (from useParams)
- **Query Params:** None

---

## 24. DepositProductsPage (Admin)

- **API Calls (GET):** `admin_get_deposit_products.php`
- **API Calls (POST):** `admin_loan_products_delete.php` → `{ id }` (note: reuses loan products delete endpoint for deposit)
- **Data from server:** `{ data: DepositProduct[] }` — `{ id, product_name, interest_rate_pa, tenor_months, min_amount, is_active }`
- **Form Actions:** CRUD via `DepositProductModal` component, POST delete
- **URL Params:** None
- **Query Params:** None

---

## 25. DepositsPage (User)

- **API Calls (GET):** `user_get_deposits.php`
- **Data from server:** `{ data: Deposit[] }` — `{ id, product_name, balance, maturity_date, status }`
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** None

---

## 26. ForgotPasswordPage

- **API Calls (POST):** `auth_forgot_password_request.php` → `{ email }`
- **Data from server:** `{ status, message }`
- **Form Actions:** POST request password reset
- **URL Params:** None
- **Query Params:** None

---

## 27. HistoryPage

- **API Calls (GET):** `user_get_transaction_history.php?page={page}&limit=15&start_date={}&end_date={}&type={}`
- **API Calls (GET):** `user_get_transaction_detail.php?id={txId}`
- **Data from server (list):** `{ data: Transaction[], pagination: { has_more } }`
  - Transaction: `{ id, description, flow, amount, created_at }`
- **Data from server (detail):** `{ data: TransactionDetail }` (full transaction object)
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** `page`, `limit`, `start_date`, `end_date`, `type`

---

## 28. InvestmentPage

- **API Calls (GET):** `utility_get_market_data.php` (polled every 15s)
- **Data from server:** `{ data: { stocks: Stock[] }, last_updated }`
  - Stock: `{ name, code, price_new, change, change_percent, volume }`
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** None

---

## 29. LandingPage

- **API Calls (GET):** `utility_get_public_config.php`
- **Data from server:** `{ data: { app_download_link_ios, app_download_link_android } }`
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** None

---

## 30. LoanApplicationDetailPage (Admin)

- **API Calls (GET):** `admin_loan_application_get_detail.php?id={loanId}`
- **Data from server:** `{ data: { customer_name, email, phone_number, product_name, loan_amount, tenor, tenor_unit, status, application_date, approver_name, approval_date, disbursement_date, installments: Installment[] } }`
  - Installment: `{ id, installment_number, due_date, amount_due, principal_amount, interest_amount, penalty_amount, status }`
- **Form Actions:** None (read-only detail view)
- **URL Params:** `loanId` (from useParams)
- **Query Params:** None

---

## 31. LoanApplicationPage (User)

- **API Calls (GET):** `user_loan_products_get.php` (to find product by `productId`)
- **API Calls (POST):** `user_loan_application_create.php` → `{ loan_product_id, amount, tenor, purpose }`
- **Data from server (products):** `{ data: LoanProduct[] }` — `{ id, product_name, min_amount, max_amount, min_tenor, max_tenor, tenor_unit }`
- **Form Actions:** POST create loan application
- **URL Params:** `productId` (from useParams)
- **Query Params:** None

---

## 32. LoanApplicationsPage (Admin)

- **API Calls (GET):** `admin_loan_applications_get.php?status={SUBMITTED|APPROVED}`
- **API Calls (POST):** `admin_loan_application_update_status.php` → `{ loan_id, status }`
- **API Calls (POST):** `admin_loan_disburse.php` → `{ loan_id }`
- **Data from server:** `{ data: LoanApplication[] }` — `{ id, customer_name, product_name, loan_amount, tenor, tenor_unit, application_date, status }`
- **Form Actions:** POST approve/reject, POST disburse
- **URL Params:** None
- **Query Params:** `status` (SUBMITTED|APPROVED)

---

## 33. LoanProductsListPage (User)

- **API Calls (GET):** `user_loan_products_get.php`
- **Data from server:** `{ data: LoanProduct[] }` — `{ id, product_name, interest_rate_pa, min_amount, max_amount, min_tenor, max_tenor, tenor_unit }`
- **Form Actions:** None (navigates to LoanApplicationPage)
- **URL Params:** None
- **Query Params:** None

---

## 34. LoanProductsPage (Admin)

- **API Calls (GET):** `admin_loan_products_get.php`
- **API Calls (POST):** `admin_loan_products_delete.php` → `{ id }`
- **Data from server:** `{ data: LoanProduct[] }` — `{ id, product_name, interest_rate_pa, late_payment_fee, min_amount, max_amount, min_tenor, max_tenor, tenor_unit, is_active }`
- **Form Actions:** CRUD via `LoanProductModal` component, POST delete
- **URL Params:** None
- **Query Params:** None

---

## 35. LoginPage

- **API Calls (POST):** `fetch('/api/auth/login')` → `{ email, password }`
- **Data from server:** `{ status, user: { roleId, fullName, email, ... } }`
- **Form Actions:** POST login
- **URL Params:** None
- **Query Params:** None
- **Note:** Uses raw `fetch` (not `callApi`). Stores user in `localStorage`. Redirects based on `roleId` (9 = customer → `/dashboard`, else → `/admin/dashboard`)

---

## 36. MyLoanDetailPage

- **API Calls (GET):** `user_get_loan_detail.php?id={loanId}`
- **API Calls (POST):** `user_pay_installment.php` → `{ installment_id }`
- **Data from server:** `{ data: { product_name, loan_amount, tenor, tenor_unit, installments: Installment[] } }`
  - Installment: `{ id, installment_number, due_date, amount_due, penalty_amount, status }`
- **Form Actions:** POST pay installment
- **URL Params:** `loanId` (from useParams)
- **Query Params:** None

---

## 37. MyLoansPage

- **API Calls (GET):** `user_get_loans.php`
- **Data from server:** `{ data: Loan[] }` — `{ id, product_name, loan_amount, disbursement_date }`
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** None

---

## 38. NotificationsPage (User)

- **API Calls (GET):** `notifications_get_list.php`
- **API Calls (PUT):** `user_mark_notification_read.php` → `{ notification_id: 'all' }`
- **Data from server:** `{ data: Notification[] }` — `{ id, title, message, is_read, created_at }`
- **Form Actions:** PUT mark all as read
- **URL Params:** None
- **Query Params:** None

---

## 39. OpenDepositPage

- **API Calls (GET):** `deposit_products_get_list.php`
- **API Calls (POST):** `deposit_account_create.php` → `{ product_id, amount }`
- **Data from server (products):** `{ data: DepositProduct[] }` — `{ id, product_name, interest_rate_pa, tenor_months, min_amount }`
- **Form Actions:** POST open deposit
- **URL Params:** None
- **Query Params:** None

---

## 40. PaymentPage

- **API Calls (POST):** `user_payment_qr_generate.php` → `{ amount }` (MyQr tab)
- **Data from server (QR):** `{ data: { qr_base64 } }`
- **Form Actions:** POST generate QR
- **URL Params:** None
- **Query Params:** None
- **Note:** ScanQr tab uses `html5-qrcode` library to scan QR codes, then navigates to `/transfer` with `{ state: { qrData } }`

---

## 41. PrintableReceiptPage

- **API Calls (GET):** `admin_get_receipt_data.php?id={transactionId}`
- **Data from server:** `{ data: { transaction_code, created_at, staff_name, customer_name, customer_account_number, loan_id, description, amount, initial_balance, final_balance, transaction_type, unit_name, unit_address } }`
- **Form Actions:** None (print-only page)
- **URL Params:** `transactionId` (from useParams)
- **Query Params:** None
- **Note:** Auto-triggers `window.print()` after data loads

---

## 42. ProfileInfoPage

- **API Calls (GET):** `user_profile_get.php`
- **API Calls (POST):** `user_profile_update.php` → `{ address_domicile, occupation }`
- **Data from server:** `{ data: { full_name, email, phone_number, nik, mother_maiden_name, dob, address_domicile, occupation } }`
- **Form Actions:** POST update profile
- **URL Params:** None
- **Query Params:** None

---

## 43. ProfilePage

- **API Calls:** None (reads from `localStorage.authUser`)
- **Logout:** `fetch('/api/user/logout', { method: 'POST' })`
- **Data from server:** None (client-side only)
- **Form Actions:** POST logout
- **URL Params:** None
- **Query Params:** None
- **Note:** Uses `NotificationContext` for push notification subscription

---

## 44. RegisterPage

- **API Calls (GET):** `utility_get_nearest_units.php?lat={lat}&lon={lon}`
- **API Calls (POST):** `fetch(baseUrl + '/auth/register/request-otp')` → FormData with `{ full_name, email, password, phone_number, nik, mother_maiden_name, pob, dob, gender, address_ktp, unit_id, ktp_image, selfie_image }`
- **API Calls (POST):** `auth_register_verify_otp.php` → `{ email, otp_code }`
- **Data from server (units):** `{ data: NearestUnit[] }` — `{ id, unit_name, type, distance }`
- **Form Actions:** POST request OTP (multipart/form-data), POST verify OTP
- **URL Params:** None
- **Query Params:** None
- **Note:** Uses geolocation API to get user coordinates. Step 1 uses raw `fetch` for file upload.

---

## 45. ReportsPage

- **API Calls:** None directly — delegates to child report components:
  - `ProfitLossReport` (receives `dateFilter`)
  - `DailyReport` (receives `dateFilter`)
  - `TellerReport`
  - `AcquisitionReport` (receives `dateFilter`)
  - `CustomerGrowthChart` (receives `dateFilter`)
  - `NplReport`
  - `ProductPerformanceReport`
  - `AccountBalanceReport`
- **Data from server:** Each child component fetches its own data
- **Form Actions:** None (date filter is local state)
- **URL Params:** None
- **Query Params:** None (date filter passed as props to children)

---

## 46. ResetPasswordPage

- **API Calls (POST):** `auth_forgot_password_reset.php` → `{ token, new_password }`
- **Data from server:** `{ status, message }`
- **Form Actions:** POST reset password
- **URL Params:** None
- **Query Params:** `token` (from URL query string via `usePage().url`)

---

## 47. SettingsPage

### ChangePasswordForm (sub-component):
- **API Calls (POST):** `user_security_update_password.php` → `{ current_password, new_password }`

### SystemSettingsForm (sub-component, Super Admin only):
- **API Calls (GET):** `admin_get_settings.php`
- **API Calls (POST):** `admin_config_update.php` → `{ monthly_admin_fee, transfer_fee_external, payment_qris_image_url, payment_bank_accounts (JSON string), APP_DOWNLOAD_LINK_IOS, APP_DOWNLOAD_LINK_ANDROID }`
- **Data from server:** `{ data: { monthly_admin_fee, transfer_fee_external, payment_qris_image_url, payment_bank_accounts, APP_DOWNLOAD_LINK_IOS, APP_DOWNLOAD_LINK_ANDROID } }`
- **Form Actions:** POST update settings
- **URL Params:** None
- **Query Params:** None

---

## 48. StaffEditPage

- **API Calls (GET):** `admin_get_staff_detail.php?id={staffId}`, `admin_get_roles.php`
- **API Calls (POST):** `admin_edit_staff.php` → `{ staff_id, full_name, email, role_id }`
- **API Calls (POST):** `admin_reset_staff_password.php` → `{ staff_id }`
- **Data from server (staff):** `{ data: { full_name, email, role_id, ... } }`
- **Data from server (roles):** `{ data: Role[] }` — `{ id, role_name }`
- **Data from server (reset):** `{ data: { temporary_password } }`
- **Form Actions:** POST edit staff, POST reset password
- **URL Params:** `staffId` (from useParams)
- **Query Params:** None

---

## 49. StaffListPage

- **API Calls (GET):** `admin_get_staff_list.php`, `admin_get_roles.php`, `admin_get_units.php`
- **API Calls (POST):** `admin_update_staff_status.php` → `{ staff_id, new_status }`
- **Data from server (staff):** `{ data: Staff[] }` — `{ id, full_name, email, role_name, branch_name, unit_name, status, can_edit }`
- **Data from server (roles):** `{ data: Role[] }` (filtered, excluding 'Nasabah')
- **Data from server (units):** `{ data: Branch[] }` (same as AdminUnitsPage)
- **Form Actions:** POST update status. Add staff via `StaffModal`, reassign via `StaffAssignmentModal`
- **URL Params:** None
- **Query Params:** None

---

## 50. TopUpPage

- **API Calls (GET):** `utility_get_payment_methods.php`
- **API Calls (POST):** `fetch(baseUrl + '/user/topup-requests')` → FormData with `{ amount, payment_method, proof (file) }`
- **Data from server (methods):** `{ data: { qris_image_url, bank_accounts: BankAccount[] } }`
  - BankAccount: `{ bank_name, account_number, account_name }`
- **Form Actions:** POST create top-up request (multipart/form-data via raw fetch)
- **URL Params:** None
- **Query Params:** None

---

## 51. TransactionListPage (Admin)

- **API Calls (GET):** `admin_get_transactions.php?page={page}&limit=15&search={search}&type={type}`
- **API Calls (GET):** `admin_get_transaction_detail.php?id={txId}`
- **Data from server (list):** `{ data: Transaction[], pagination: { current_page, total_pages } }`
  - Transaction: `{ id, created_at, transaction_type, from_name, to_name, amount, status }`
- **Data from server (detail):** `{ data: TransactionDetail }` (shown in `AdminTransactionDetailModal`)
- **Form Actions:** None
- **URL Params:** None
- **Query Params:** `page`, `limit`, `search`, `type`

---

## 52. TransferPage

- **API Calls (POST):** `transfer_internal_inquiry.php` → `{ destination_account_number }`
- **API Calls (POST):** `transfer_internal_execute.php` → `{ destination_account_number, amount, description, pin }`
- **Data from server (inquiry):** `{ data: { recipient_name } }`
- **Form Actions:** POST inquiry, POST execute transfer
- **URL Params:** None
- **Query Params:** None
- **Note:** Accepts `location.state.qrData` from PaymentPage QR scan: `{ acc, name, amt }`

---

## 53. WithdrawalAccountsPage

- **API Calls (GET):** `user_get_withdrawal_accounts.php`
- **API Calls (POST):** `user_add_withdrawal_account.php` → `{ bank_name, account_number, account_name }`
- **API Calls (POST):** `user_delete_withdrawal_account.php` → `{ id }` (NOT YET IMPLEMENTED in backend)
- **Data from server:** `{ data: WithdrawalAccount[] }` — `{ id, bank_name, account_number, account_name }`
- **Form Actions:** POST add account, POST delete account (disabled)
- **URL Params:** None
- **Query Params:** None

---

## 54. WithdrawalPage

- **API Calls (GET):** `user_get_withdrawal_accounts.php`
- **API Calls (POST):** `user_create_withdrawal_request.php` → `{ withdrawal_account_id, amount, pin }`
- **Data from server:** `{ data: WithdrawalAccount[] }` — `{ id, bank_name, account_number, account_name }`
- **Form Actions:** POST create withdrawal request
- **URL Params:** None
- **Query Params:** None

---

## Summary: Pages Using URL Params (useParams)

| Page | Param Name | Usage |
|------|-----------|-------|
| CustomerDetailPage | `customerId` | `admin_get_customer_detail.php?id={customerId}` |
| CustomerEditPage | `customerId` | `admin_get_customer_detail.php?id={customerId}` |
| DepositDetailPage | `depositId` | `user_get_deposit_detail.php?id={depositId}` |
| LoanApplicationDetailPage | `loanId` | `admin_loan_application_get_detail.php?id={loanId}` |
| LoanApplicationPage | `productId` | Finds product from `user_loan_products_get.php` |
| MyLoanDetailPage | `loanId` | `user_get_loan_detail.php?id={loanId}` |
| PrintableReceiptPage | `transactionId` | `admin_get_receipt_data.php?id={transactionId}` |
| StaffEditPage | `staffId` | `admin_get_staff_detail.php?id={staffId}` |

## Summary: Pages Using Raw fetch (not callApi)

| Page | Endpoint | Reason |
|------|----------|--------|
| LoginPage | `/api/auth/login` | Auth login |
| ProfilePage | `/api/user/logout` | Auth logout |
| RegisterPage | `{baseUrl}/auth/register/request-otp` | File upload (FormData) |
| TopUpPage | `{baseUrl}/user/topup-requests` | File upload (FormData) |

## Summary: Unique API Endpoints Referenced

### Auth
- `auth_forgot_password_request.php`
- `auth_forgot_password_reset.php`
- `auth_register_verify_otp.php`
- `/api/auth/login` (raw fetch)
- `{baseUrl}/auth/register/request-otp` (raw fetch)

### User/Customer
- `dashboard_summary.php`
- `user_profile_get.php`
- `user_profile_update.php`
- `user_security_update_password.php`
- `user_security_update_pin.php`
- `user_get_cards.php`
- `user_get_all_accounts.php`
- `user_request_card.php`
- `user_update_card_status.php`
- `user_set_card_limit.php`
- `user_get_deposits.php`
- `user_get_deposit_detail.php`
- `user_get_loans.php`
- `user_get_loan_detail.php`
- `user_pay_installment.php`
- `user_loan_products_get.php`
- `user_loan_application_create.php`
- `user_get_withdrawal_accounts.php`
- `user_add_withdrawal_account.php`
- `user_create_withdrawal_request.php`
- `user_get_transaction_history.php`
- `user_get_transaction_detail.php`
- `user_payment_qr_generate.php`
- `user_mark_notification_read.php`
- `notifications_get_list.php`
- `beneficiaries_get_list.php`
- `beneficiaries_add.php`
- `beneficiaries_delete.php`
- `transfer_internal_inquiry.php`
- `transfer_internal_execute.php`
- `deposit_products_get_list.php`
- `deposit_account_create.php`
- `deposit_account_disburse.php`
- `bill_payment_inquiry.php`
- `bill_payment_execute.php`

### Admin
- `admin_get_dashboard_summary.php`
- `admin_get_customers.php`
- `admin_get_customer_detail.php`
- `admin_add_customer.php`
- `admin_edit_customer.php`
- `admin_update_customer_status.php`
- `admin_force_pay_installment.php`
- `admin_get_staff_list.php`
- `admin_get_staff_detail.php`
- `admin_edit_staff.php`
- `admin_reset_staff_password.php`
- `admin_update_staff_status.php`
- `admin_get_roles.php`
- `admin_get_units.php`
- `admin_get_audit_log.php`
- `admin_get_settings.php`
- `admin_config_update.php`
- `admin_get_topup_requests.php`
- `admin_process_topup_request.php`
- `admin_get_withdrawal_requests.php`
- `admin_process_withdrawal_request.php`
- `admin_disburse_withdrawal.php`
- `admin_loan_applications_get.php`
- `admin_loan_application_get_detail.php`
- `admin_loan_application_update_status.php`
- `admin_loan_disburse.php`
- `admin_loan_products_get.php`
- `admin_loan_products_delete.php`
- `admin_get_deposit_products.php`
- `admin_get_customer_deposits.php`
- `admin_get_all_loans.php`
- `admin_get_card_requests.php`
- `admin_process_card_request.php`
- `admin_get_transactions.php`
- `admin_get_transaction_detail.php`
- `admin_get_receipt_data.php`
- `admin_teller_deposit.php`
- `admin_teller_pay_installment.php`
- `admin_search_installments.php`
- `admin_trigger_build.php`

### Utility
- `utility_get_payment_methods.php`
- `utility_get_billers.php`
- `utility_get_market_data.php`
- `utility_get_nearest_units.php`
- `utility_get_public_config.php`
