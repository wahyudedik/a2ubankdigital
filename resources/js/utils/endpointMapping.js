// Mapping dari endpoint PHP lama ke Laravel API yang benar
export const endpointMapping = {
    // Auth endpoints
    'auth_login.php': 'auth/login',
    'auth_register_request_otp.php': 'auth/register/request-otp',
    'auth_register_verify_otp.php': 'auth/register/verify-otp',
    'auth_forgot_password_reset.php': 'auth/forgot-password/reset',
    'auth_forgot_password_request.php': 'auth/forgot-password/request',

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
    'user_mark_notification_read.php': 'user/notifications/mark-all-read',
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
    'admin_add_customer.php': 'admin/customers',
    'admin_edit_customer.php': 'admin/customers',
    'admin_update_customer_status.php': 'admin/customers/status',
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

    // Force pay installment
    'admin_force_pay_installment.php': 'admin/loans/force-pay-installment',

    // Utility endpoints
    'utility_get_payment_methods.php': 'utility/payment-methods',
    'utility_get_nearest_units.php': 'utility/nearest-units',
    'notifications_get_list.php': 'user/notifications',
    'deposit_products_get_list.php': 'user/deposit-products',
    'deposit_account_create.php': 'user/deposits/create',
    'deposit_account_disburse.php': 'user/deposits/disburse',

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
    'user_request_card.php': 'user/cards/request',
    'card_set_limit.php': 'user/cards/limit',
    'user_set_card_limit.php': 'user/cards/limit',
    'user_update_card_status.php': 'user/cards/status',

    // Account endpoints
    'account_get_list.php': 'user/accounts',
    'account_get_statement.php': 'user/accounts/statement',
    'account_get_balance.php': 'user/accounts/balance',

    // Public config
    'utility_get_public_config.php': 'utility/public-config',
    'utility_get_billers.php': 'user/bill-payment/billers',
    'utility_get_market_data.php': 'utility/market-data',

    // Beneficiaries
    'beneficiaries_get_list.php': 'user/beneficiaries',
    'beneficiaries_add.php': 'user/beneficiaries',
    'beneficiaries_delete.php': 'user/beneficiaries',

    // Admin build
    'admin_trigger_build.php': 'admin/trigger-build',

    // Push notification
    'push_notification_subscribe.php': 'user/push-notification/subscribe',

    // Dashboard
    'dashboard_summary.php': 'user/dashboard/summary',
};

// Helper function untuk mengkonversi endpoint
export const convertEndpoint = (oldEndpoint, method = 'GET', body = null) => {
    const cleanEndpoint = oldEndpoint.startsWith('/') ? oldEndpoint.slice(1) : oldEndpoint;
    const [endpointPath, queryString] = cleanEndpoint.split('?');

    if (endpointMapping[endpointPath]) {
        let mappedEndpoint = endpointMapping[endpointPath];

        // Parse query string to get id parameter
        const params = new URLSearchParams(queryString || '');
        const idFromQuery = params.get('id');

        // Handle endpoints that need ID in URL path
        const detailEndpoints = {
            'admin_get_customer_detail.php': 'admin/customers',
            'admin_get_staff_detail.php': 'admin/staff',
            'admin_loan_application_get_detail.php': 'admin/loans',
            'user_get_loan_detail.php': 'user/loans',
            'user_get_deposit_detail.php': 'user/deposits',
            'user_get_transaction_detail.php': 'user/transactions',
            'admin_get_transaction_detail.php': 'admin/transactions',
            'admin_get_receipt_data.php': 'admin/receipts',
        };

        if (detailEndpoints[endpointPath] && idFromQuery) {
            mappedEndpoint = `${detailEndpoints[endpointPath]}/${idFromQuery}`;
            params.delete('id');
            const remainingParams = params.toString();
            return remainingParams ? `${mappedEndpoint}?${remainingParams}` : mappedEndpoint;
        }

        // Handle body-based ID for PUT/POST
        if (body) {
            const bodyId = body.id || body.loan_id || body.request_id || body.staff_id || body.card_id || body.deposit_id || body.installment_id;

            if (endpointPath === 'admin_edit_customer.php' && method === 'PUT') {
                mappedEndpoint = `admin/customers/${body.id}`;
            } else if (endpointPath === 'admin_update_customer_status.php') {
                mappedEndpoint = `admin/customers/${body.customer_id || body.id}/status`;
            } else if (endpointPath === 'admin_update_staff_status.php') {
                mappedEndpoint = `admin/staff/${body.staff_id || body.id}/status`;
            } else if (endpointPath === 'admin_loan_application_update_status.php') {
                mappedEndpoint = `admin/loans/${body.loan_id || body.id}/status`;
            } else if (endpointPath === 'admin_loan_disburse.php') {
                mappedEndpoint = `admin/loans/${body.loan_id || body.id}/disburse`;
            } else if (endpointPath === 'admin_process_card_request.php') {
                mappedEndpoint = `admin/card-requests/${body.card_id || body.id}/process`;
            } else if (endpointPath === 'admin_process_withdrawal_request.php') {
                mappedEndpoint = `admin/withdrawal-requests/${body.request_id || body.id}/process`;
            } else if (endpointPath === 'admin_disburse_withdrawal.php') {
                mappedEndpoint = `admin/withdrawal-requests/${body.request_id || body.id}/disburse`;
            } else if (endpointPath === 'admin_process_topup_request.php') {
                mappedEndpoint = `admin/processing/process-topup`;
            } else if (endpointPath === 'admin_reset_staff_password.php') {
                mappedEndpoint = `admin/staff/${body.staff_id || body.id}/reset-password`;
            } else if (endpointPath === 'admin_update_staff_assignment.php') {
                mappedEndpoint = `admin/staff/${body.staff_id || body.id}/assignment`;
            } else if (endpointPath === 'admin_edit_staff.php') {
                mappedEndpoint = `admin/staff/${body.staff_id || body.id}`;
            } else if (endpointPath === 'admin_update_deposit_product.php' && body.id) {
                mappedEndpoint = `admin/deposit-products/${body.id}`;
            } else if (endpointPath === 'admin_loan_products_edit.php' && body.id) {
                mappedEndpoint = `admin/loan-products/${body.id}`;
            } else if (endpointPath === 'admin_loan_products_delete.php' && body.id) {
                mappedEndpoint = `admin/loan-products/${body.id}`;
            } else if (endpointPath === 'admin_force_pay_installment.php') {
                mappedEndpoint = `admin/loans/${body.loan_id || body.installment_id}/force-pay-installment`;
            } else if (endpointPath === 'user_pay_installment.php') {
                mappedEndpoint = `user/loans/${body.loan_id || body.installment_id}/pay-installment`;
            } else if (endpointPath === 'user_set_card_limit.php' || endpointPath === 'card_set_limit.php') {
                mappedEndpoint = `user/cards/${body.card_id || body.id}/limit`;
            } else if (endpointPath === 'user_update_card_status.php') {
                mappedEndpoint = `user/cards/${body.card_id || body.id}/status`;
            } else if (endpointPath === 'deposit_account_disburse.php') {
                mappedEndpoint = `user/deposits/${body.deposit_id || body.id}/disburse`;
            } else if (endpointPath === 'beneficiaries_delete.php' && body.id) {
                mappedEndpoint = `user/beneficiaries/${body.id}`;
            }
        }

        const finalEndpoint = queryString ? `${mappedEndpoint}?${queryString}` : mappedEndpoint;
        return finalEndpoint;
    }

    return cleanEndpoint;
};
