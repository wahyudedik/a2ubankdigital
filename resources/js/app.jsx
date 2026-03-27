import '../css/app.css';
import React from 'react';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { ModalProvider } from '@/contexts/ModalContext.jsx';
import { NotificationProvider } from '@/contexts/NotificationContext.jsx';
import CustomerLayout from '@/Layouts/CustomerLayout.jsx';
import AdminLayoutWrapper from '@/Layouts/AdminLayoutWrapper.jsx';

// Pages that should NOT have any layout (public pages)
const noLayoutPages = [
    'LandingPage', 'LoginPage', 'RegisterPage', 'ForgotPasswordPage', 'ResetPasswordPage',
    'PrintableReceiptPage', 'AdminBuildPage'
];

// Pages that use Admin layout
const adminPages = [
    'AdminDashboardPage', 'CustomerListPage', 'CustomerDetailPage', 'CustomerEditPage',
    'CustomerAddPage', 'TransactionListPage', 'LoanProductsPage', 'DepositProductsPage',
    'AdminDepositsListPage', 'LoanApplicationsPage', 'LoanApplicationDetailPage',
    'AdminLoansListPage', 'AdminUnitsPage', 'SettingsPage', 'StaffListPage', 'StaffEditPage',
    'CardRequestsPage', 'ReportsPage', 'AdminNotificationsPage', 'AdminTopUpRequestsPage',
    'AdminWithdrawalRequestsPage', 'AdminAuditLogPage', 'AdminTellerDepositPage',
    'AdminTellerLoanPaymentPage'
];

createInertiaApp({
    title: (title) => title ? `${title} - A2U Bank Digital` : 'A2U Bank Digital',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        const page = pages[`./Pages/${name}.jsx`];

        // Auto-assign layout based on page name
        if (!noLayoutPages.includes(name)) {
            if (adminPages.includes(name)) {
                page.default.layout = (page) => <AdminLayoutWrapper>{page}</AdminLayoutWrapper>;
            } else {
                page.default.layout = (page) => <CustomerLayout>{page}</CustomerLayout>;
            }
        }

        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <ModalProvider>
                <NotificationProvider>
                    <App {...props} />
                </NotificationProvider>
            </ModalProvider>
        );
    },
});
