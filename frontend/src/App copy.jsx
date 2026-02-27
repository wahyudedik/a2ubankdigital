import React, { useEffect, useState } from 'react';
import { Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { ModalProvider } from './contexts/ModalContext';
import { NotificationProvider } from './contexts/NotificationContext';

// Layouts
import MainLayout from './components/layout/MainLayout';
import AdminLayout from './components/layout/AdminLayout';

// Public Pages
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';
import ResetPasswordPage from './pages/ResetPasswordPage';
import LandingPage from './pages/LandingPage';

// Customer Pages
import DashboardPage from './pages/DashboardPage';
import TransferPage from './pages/TransferPage';
import HistoryPage from './pages/HistoryPage';
import ProfilePage from './pages/ProfilePage';
import ProfileInfoPage from './pages/ProfileInfoPage';
import ChangePasswordPage from './pages/ChangePasswordPage';
import ChangePinPage from './pages/ChangePinPage';
import PaymentPage from './pages/PaymentPage';
import BillPaymentPage from './pages/BillPaymentPage';
import TopUpPage from './pages/TopUpPage';
import CardsPage from './pages/CardsPage';
import CardRequestsPage from './pages/CardRequestsPage';
import LoanProductsPage from './pages/LoanProductsPage';
import LoanApplicationPage from './pages/LoanApplicationPage';
import MyLoansPage from './pages/MyLoansPage';
import MyLoanDetailPage from './pages/MyLoanDetailPage';
import DepositsPage from './pages/DepositsPage';
import OpenDepositPage from './pages/OpenDepositPage';
import DepositDetailPage from './pages/DepositDetailPage';
import WithdrawalAccountsPage from './pages/WithdrawalAccountsPage';
import WithdrawalPage from './pages/WithdrawalPage';
import TransactionListPage from './pages/TransactionListPage';
import NotificationsPage from './pages/NotificationsPage';
import BeneficiaryListPage from './pages/BeneficiaryListPage';
import InvestmentPage from './pages/InvestmentPage'; 

// Admin Pages
import AdminDashboardPage from './pages/AdminDashboardPage';
import CustomerListPage from './pages/CustomerListPage';
import CustomerAddPage from './pages/CustomerAddPage';
import CustomerDetailPage from './pages/CustomerDetailPage';
import CustomerEditPage from './pages/CustomerEditPage';
import StaffListPage from './pages/StaffListPage';
import StaffEditPage from './pages/StaffEditPage';
import AdminUnitsPage from './pages/AdminUnitsPage';
import AdminLoansListPage from './pages/AdminLoansListPage';
import LoanApplicationDetailPage from './pages/LoanApplicationDetailPage';
import AdminDepositsListPage from './pages/AdminDepositsListPage';
import AdminTellerDepositPage from './pages/AdminTellerDepositPage';
import AdminTellerLoanPaymentPage from './pages/AdminTellerLoanPaymentPage';
import AdminTopUpRequestsPage from './pages/AdminTopUpRequestsPage';
import AdminWithdrawalRequestsPage from './pages/AdminWithdrawalRequestsPage';
import LoanProductsListPage from './pages/LoanProductsListPage';
import DepositProductsPage from './pages/DepositProductsPage';
import ReportsPage from './pages/ReportsPage';
import AdminAuditLogPage from './pages/AdminAuditLogPage';
import AdminNotificationsPage from './pages/AdminNotificationsPage';
import SettingsPage from './pages/SettingsPage';

// --- Komponen Pelindung Rute (Guard) ---
const ProtectedRoute = ({ children, isAdminRoute = false }) => {
  const token = localStorage.getItem('token');
  const userStr = localStorage.getItem('user');
  const location = useLocation();

  // 1. Cek Login
  if (!token || !userStr) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  const user = JSON.parse(userStr);
  const roleId = parseInt(user.roleId);

  // 2. Logika Proteksi
  if (isAdminRoute) {
    // Jika rute Admin, tapi user adalah Nasabah (9) -> Tendang ke Dashboard Nasabah
    if (roleId === 9) {
      return <Navigate to="/dashboard" replace />;
    }
  } else {
    // Jika rute Nasabah, tapi user adalah Staf (< 9) -> Tendang ke Dashboard Admin
    if (roleId !== 9) {
      return <Navigate to="/admin/dashboard" replace />;
    }
  }

  // Jika lolos semua cek, tampilkan halaman
  return children;
};

// --- Komponen Redirect Root ---
const RootRedirect = () => {
  const token = localStorage.getItem('token');
  const userStr = localStorage.getItem('user');

  if (token && userStr) {
    const user = JSON.parse(userStr);
    if (parseInt(user.roleId) === 9) {
      return <Navigate to="/dashboard" replace />;
    } else {
      return <Navigate to="/admin/dashboard" replace />;
    }
  }
  return <LandingPage />;
};

function App() {
  return (
    // <Router> DIHAPUS karena sudah ada di main.jsx
      <ModalProvider>
        <NotificationProvider>
          <Routes>
            {/* Rute Publik */}
            <Route path="/" element={<RootRedirect />} />
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />
            <Route path="/reset-password" element={<ResetPasswordPage />} />

            {/* === RUTE NASABAH (MainLayout) === */}
            <Route
              path="/*"
              element={
                <ProtectedRoute isAdminRoute={false}>
                  <MainLayout>
                    <Routes>
                      <Route path="dashboard" element={<DashboardPage />} />
                      <Route path="transfer" element={<TransferPage />} />
                      <Route path="history" element={<HistoryPage />} />
                      <Route path="scan" element={<TransactionListPage />} /> {/* QRIS */}
                      
                      <Route path="profile" element={<ProfilePage />} />
                      <Route path="profile/info" element={<ProfileInfoPage />} />
                      <Route path="profile/password" element={<ChangePasswordPage />} />
                      <Route path="profile/pin" element={<ChangePinPage />} />
                      <Route path="profile/beneficiaries" element={<BeneficiaryListPage />} />
                      <Route path="notifications" element={<NotificationsPage />} />

                      <Route path="payment" element={<PaymentPage />} />
                      <Route path="payment/bill" element={<BillPaymentPage />} />
                      <Route path="topup" element={<TopUpPage />} />
                      
                      <Route path="cards" element={<CardsPage />} />
                      <Route path="cards/request" element={<CardRequestsPage />} />
                      
                      <Route path="loans" element={<MyLoansPage />} />
                      <Route path="loans/products" element={<LoanProductsPage />} />
                      <Route path="loans/apply" element={<LoanApplicationPage />} />
                      <Route path="loans/:id" element={<MyLoanDetailPage />} />
                      
                      <Route path="deposits" element={<DepositsPage />} />
                      <Route path="deposits/open" element={<OpenDepositPage />} />
                      <Route path="deposits/:id" element={<DepositDetailPage />} />
                      
                      <Route path="withdraw" element={<WithdrawalAccountsPage />} />
                      <Route path="withdraw/create" element={<WithdrawalPage />} />

                      <Route path="investments" element={<InvestmentPage />} />
                      
                      {/* Fallback untuk rute nasabah yang tidak ditemukan */}
                      <Route path="*" element={<Navigate to="/dashboard" replace />} />
                    </Routes>
                  </MainLayout>
                </ProtectedRoute>
              }
            />

            {/* === RUTE ADMIN (AdminLayout) === */}
            <Route
              path="/admin/*"
              element={
                <ProtectedRoute isAdminRoute={true}>
                  <AdminLayout>
                    <Routes>
                      <Route path="dashboard" element={<AdminDashboardPage />} />
                      
                      {/* User Management */}
                      <Route path="customers" element={<CustomerListPage />} />
                      <Route path="customers/add" element={<CustomerAddPage />} />
                      <Route path="customers/:id" element={<CustomerDetailPage />} />
                      <Route path="customers/:id/edit" element={<CustomerEditPage />} />
                      
                      <Route path="staff" element={<StaffListPage />} />
                      <Route path="staff/edit/:id" element={<StaffEditPage />} />
                      <Route path="units" element={<AdminUnitsPage />} />

                      {/* Loan Management */}
                      <Route path="loans" element={<AdminLoansListPage />} />
                      <Route path="loans/:id" element={<LoanApplicationDetailPage />} />
                      <Route path="loan-products" element={<LoanProductsListPage />} />

                      {/* Deposit Management */}
                      <Route path="deposits" element={<AdminDepositsListPage />} />
                      <Route path="deposit-products" element={<DepositProductsPage />} />

                      {/* Teller Operations */}
                      <Route path="teller/deposit" element={<AdminTellerDepositPage />} />
                      <Route path="teller/loan-payment" element={<AdminTellerLoanPaymentPage />} />
                      <Route path="topup-requests" element={<AdminTopUpRequestsPage />} />
                      <Route path="withdrawal-requests" element={<AdminWithdrawalRequestsPage />} />

                      {/* System & Reports */}
                      <Route path="reports" element={<ReportsPage />} />
                      <Route path="audit-log" element={<AdminAuditLogPage />} />
                      <Route path="notifications" element={<AdminNotificationsPage />} />
                      <Route path="settings" element={<SettingsPage />} />
                      
                      {/* Products Management */}
                      <Route path="digital-products" element={<AdminDashboardPage />} /> {/* Placeholder */}

                      {/* Fallback Admin */}
                      <Route path="*" element={<Navigate to="/admin/dashboard" replace />} />
                    </Routes>
                  </AdminLayout>
                </ProtectedRoute>
              }
            />
          </Routes>
        </NotificationProvider>
      </ModalProvider>
    // </Router>
  );
}

export default App;