// Mapping dari endpoint PHP lama ke Laravel API yang benar
export const endpointMapping = {
    // Auth endpoints
    'auth_login.php': 'auth/login',
    'auth_register_request_otp.php': 'auth/register/request-otp',
    'auth_register_verify_otp.php': 'auth/register/verify-otp',
    'auth_forgot_password_reset.php': 'auth/forgot-password/reset',

    // User endpoints
    'user_profile_get.php': 'user/profile',
    'user_profile_update.php': 'user/profile',
    'user_get_withdrawal_accounts.php': 'user/withdrawal-accounts',
    'user_add_withdrawal_account.php': 'user/withdrawal-accounts',
    'user_create_withdrawal_request.php': 'user/withdrawal-requests',
    'user_delete_withdrawal_account.php': 'user/withdrawal-accounts',
    'user_get_loans.php': 'user/loans',
    'user_get_loan_detail.php': 'user/loans',
    'user_get_deposit_detail.php': 'user/deposits',
    'user_get_transaction_history.php': 'user/transactions',
    'user_get_transaction_detail.php': 'user/transactions',
    'user_pay_installment.php': 'user/loans/pay-installment',
    'user_create_topup_request.php': 'user/topup-requests',
    'user_security_update_password.php': 'user/security/update-password',
    'user_payment_qr_generate.php': 'user/payment/qr-generate',
    'user_mark_notification_read.php': 'user/notifications/mark-read',
    'user_loan_products_get.php': 'user/loan-products',

    // Transfer endpoints
    'transfer_internal_inquiry.php': 'user/transfer/internal/inquiry',
    'transfer_internal_execute.php': 'user/transfer/internal/execute',

    // Admin endpoints
    'admin_get_dashboard_summary.php': 'admin/dashboard/summary',
    'admin_get_customer_growth_report.php': 'admin/reports/customer-growth',
    'admin_get_teller_report.php': 'admin/reports/teller',
    'admin_get_profit_loss_report.php': 'admin/reports/profit-loss',
    'admin_get_product_performance_report.php': 'admin/reports/product-performance',
    'admin_get_npl_report.php': 'admin/reports/npl',
    'admin_get_daily_report.php': 'admin/reports/daily',
    'admin_get_marketing_report.php': 'admin/reports/marketing',
    'admin_get_account_balance_report.php': 'admin/reports/account-balance',
    'admin_get_customers.php': 'admin/customers',
    'admin_get_customer_detail.php': 'admin/customers',
    'admin_get_customer_deposits.php': 'admin/deposits',
    'admin_get_all_loans.php': 'admin/loans',
    'admin_get_topup_requests.php': 'admin/processing/topup-requests',
    'admin_process_topup_request.php': 'admin/processing/process-topup',
    'admin_get_card_requests.php': 'admin/card-requests',
    'admin_process_card_request.php': 'admin/card-requests/process',
    'admin_get_withdrawal_requests.php': 'admin/withdrawal-requests',
    'admin_process_withdrawal_request.php': 'admin/withdrawal-requests/process',
    'admin_disburse_withdrawal.php': 'admin/withdrawal-requests/disburse',
    'admin_get_units.php': 'admin/units',
    'admin_add_unit.php': 'admin/units',
    'admin_update_unit.php': 'admin/units',
    'admin_create_staff_user.php': 'admin/staff',
    'admin_get_staff_list.php': 'admin/staff',
    'admin_get_staff_detail.php': 'admin/staff',
    'admin_update_staff_status.php': 'admin/staff/status',
    'admin_edit_staff.php': 'admin/staff',
    'admin_reset_staff_password.php': 'admin/staff/reset-password',
    'admin_update_staff_assignment.php': 'admin/staff/assignment',
    'admin_get_roles.php': 'admin/roles',
    'admin_get_transactions.php': 'admin/transactions',
    'admin_get_transaction_detail.php': 'admin/transactions',
    'admin_get_settings.php': 'admin/system/settings',
    'admin_config_update.php': 'admin/system/config/update',
    'admin_get_receipt_data.php': 'admin/receipts',
    'admin_get_audit_log.php': 'admin/reports/audit-logs',
    'admin_teller_deposit.php': 'admin/teller/deposit',
    'admin_search_installments.php': 'admin/teller/search-installments',
    'admin_teller_pay_installment.php': 'admin/teller/pay-installment',
    'admin_add_deposit_product.php': 'admin/deposit-products',
    'admin_update_deposit_product.php': 'admin/deposit-products',
    'admin_loan_products_get.php': 'admin/loan-products',
    'admin_loan_products_add.php': 'admin/loan-products',
    'admin_loan_products_edit.php': 'admin/loan-products',
    'admin_loan_products_delete.php': 'admin/loan-products',
    'admin_loan_application_get_detail.php': 'admin/loans',
    'admin_loan_application_update_status.php': 'admin/loans/status',
    'admin_loan_disburse.php': 'admin/loans/disburse',

    // Utility endpoints
    'utility_get_payment_methods.php': 'utility/payment-methods',
    'utility_get_nearest_units.php': 'utility/nearest-units',
    'notifications_get_list.php': 'user/notifications',
    'deposit_products_get_list.php': 'user/deposit-products',
    'deposit_account_create.php': 'user/deposits/create',

    // Loan endpoints
    'loan_products_get_list.php': 'user/loan-products',
    'loan_application_create.php': 'user/loans/apply',
    'loan_calculator.php': 'utility/loan-calculator',

    // Bill payment endpoints
    'bill_payment_get_billers.php': 'user/bill-payment/billers',
    'bill_payment_inquiry.php': 'user/bill-payment/inquiry',
    'bill_payment_execute.php': 'user/bill-payment/execute',

    // Digital product endpoints
    'digital_products_get_list.php': 'user/digital-products',
    'digital_product_purchase.php': 'user/digital-products/purchase',

    // Card endpoints
    'card_get_list.php': 'user/cards',
    'card_request_new.php': 'user/cards/request',
    'card_set_limit.php': 'user/cards/limit',

    // Account endpoints
    'account_get_list.php': 'user/accounts',
    'account_get_statement.php': 'user/accounts/statement',
    'account_get_balance.php': 'user/accounts/balance'
};

// Helper function untuk mengkonversi endpoint
export const convertEndpoint = (oldEndpoint) => {
    // Remove leading slash if exists
    const cleanEndpoint = oldEndpoint.startsWith('/') ? oldEndpoint.slice(1) : oldEndpoint;

    // Separate endpoint from query parameters
    const [endpointPath, queryString] = cleanEndpoint.split('?');

    // Check if it's in our mapping
    if (endpointMapping[endpointPath]) {
        const mappedEndpoint = endpointMapping[endpointPath];
        const finalEndpoint = queryString ? `${mappedEndpoint}?${queryString}` : mappedEndpoint;
        return finalEndpoint;
    }

    // If not found, return as is (might be already correct)
    return cleanEndpoint;
};