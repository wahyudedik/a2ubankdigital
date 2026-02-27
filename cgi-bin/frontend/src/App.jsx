import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { ModalProvider } from './contexts/ModalContext.jsx';
import { NotificationProvider } from './contexts/NotificationContext.jsx';

// Layouts
import MainLayout from './components/layout/MainLayout.jsx';
import AdminLayout from './components/layout/AdminLayout.jsx';

// Halaman Utama (Publik)
import LoginPage from './pages/LoginPage.jsx';
import RegisterPage from './pages/RegisterPage.jsx';
import LandingPage from './pages/LandingPage.jsx';
import ForgotPasswordPage from './pages/ForgotPasswordPage.jsx';
import ResetPasswordPage from './pages/ResetPasswordPage.jsx'; 

// Halaman Nasabah (Dilindungi)
import DashboardPage from './pages/DashboardPage.jsx';
import HistoryPage from './pages/HistoryPage.jsx';
import PaymentPage from './pages/PaymentPage.jsx';
import TransferPage from './pages/TransferPage.jsx';
import LoanProductsListPage from './pages/LoanProductsListPage.jsx';
import LoanApplicationPage from './pages/LoanApplicationPage.jsx';
import MyLoansPage from './pages/MyLoansPage.jsx';
import MyLoanDetailPage from './pages/MyLoanDetailPage.jsx';
import ProfilePage from './pages/ProfilePage.jsx';
import ChangePinPage from './pages/ChangePinPage.jsx';
import BeneficiaryListPage from './pages/BeneficiaryListPage.jsx';
import CardsPage from './pages/CardsPage.jsx';
import NotificationsPage from './pages/NotificationsPage.jsx';
import ProfileInfoPage from './pages/ProfileInfoPage.jsx';
import ChangePasswordPage from './pages/ChangePasswordPage.jsx';
import DepositsPage from './pages/DepositsPage.jsx';
import OpenDepositPage from './pages/OpenDepositPage.jsx';
import DepositDetailPage from './pages/DepositDetailPage.jsx';
import TopUpPage from './pages/TopUpPage.jsx';
import WithdrawalPage from './pages/WithdrawalPage.jsx';
import WithdrawalAccountsPage from './pages/WithdrawalAccountsPage.jsx';
import BillPaymentPage from './pages/BillPaymentPage.jsx';
import InvestmentPage from './pages/InvestmentPage.jsx';

// Halaman Admin (Dilindungi)
import AdminDashboardPage from './pages/AdminDashboardPage.jsx';
import CustomerListPage from './pages/CustomerListPage.jsx';
import CustomerDetailPage from './pages/CustomerDetailPage.jsx';
import CustomerEditPage from './pages/CustomerEditPage.jsx';
import CustomerAddPage from './pages/CustomerAddPage.jsx';
import TransactionListPage from './pages/TransactionListPage.jsx';
import LoanProductsPage from './pages/LoanProductsPage.jsx';
import DepositProductsPage from './pages/DepositProductsPage.jsx';
import AdminDepositsListPage from './pages/AdminDepositsListPage.jsx';
import LoanApplicationsPage from './pages/LoanApplicationsPage.jsx';
import LoanApplicationDetailPage from './pages/LoanApplicationDetailPage.jsx';
import AdminLoansListPage from './pages/AdminLoansListPage.jsx';
import AdminUnitsPage from './pages/AdminUnitsPage.jsx';
import SettingsPage from './pages/SettingsPage.jsx';
import StaffListPage from './pages/StaffListPage.jsx';
import StaffEditPage from './pages/StaffEditPage.jsx';
import CardRequestsPage from './pages/CardRequestsPage.jsx';
import ReportsPage from './pages/ReportsPage.jsx';
import AdminNotificationsPage from './pages/AdminNotificationsPage.jsx';
import AdminTopUpRequestsPage from './pages/AdminTopUpRequestsPage.jsx';
import AdminWithdrawalRequestsPage from './pages/AdminWithdrawalRequestsPage.jsx';
import AdminAuditLogPage from './pages/AdminAuditLogPage.jsx';
import AdminTellerDepositPage from './pages/AdminTellerDepositPage.jsx';
import AdminTellerLoanPaymentPage from './pages/AdminTellerLoanPaymentPage.jsx';
import PrintableReceiptPage from './pages/PrintableReceiptPage.jsx'; // <-- 1. Impor halaman baru

// Komponen helper untuk memeriksa status otentikasi
const useAuth = () => {
    const token = localStorage.getItem('authToken');
    const userString = localStorage.getItem('authUser');
    if (token && userString) {
        try {
            return { isAuthenticated: true, user: JSON.parse(userString) };
        } catch (e) {
            return { isAuthenticated: false, user: null };
        }
    }
    return { isAuthenticated: false, user: null };
};

// Komponen "Penjaga Gerbang" untuk Rute Terlindungi
const ProtectedRoute = ({ children, adminOnly = false }) => {
    const { isAuthenticated, user } = useAuth();
    
    if (!isAuthenticated) {
        return <Navigate to="/login" replace />;
    }
    
    if (adminOnly && user.roleId === 9) {
        return <Navigate to="/dashboard" replace />;
    }

    if (!adminOnly && user.roleId !== 9) {
        return <Navigate to="/admin/dashboard" replace />;
    }

    return children;
};

// Komponen "Penjaga Gerbang" untuk Rute Publik
const PublicRoute = ({ children }) => {
    const { isAuthenticated, user } = useAuth();
    if (isAuthenticated) {
        return user.roleId === 9 ? <Navigate to="/dashboard" replace /> : <Navigate to="/admin/dashboard" replace />;
    }
    return children;
};

// Wrapper untuk Layout Admin
const AdminLayoutWrapper = () => {
    const { user } = useAuth();
    const handleLogout = () => {
        localStorage.removeItem('authToken');
        localStorage.removeItem('authUser');
        window.location.href = '/';
    };
    return <AdminLayout user={user} onLogout={handleLogout} />;
};

// Wrapper untuk Layout Nasabah
const CustomerLayoutWrapper = () => {
    const { user } = useAuth();
    const handleLogout = () => {
        localStorage.removeItem('authToken');
        localStorage.removeItem('authUser');
        window.location.href = '/';
    };
    return <MainLayout user={user} onLogout={handleLogout} />;
};


function App() {
  return (
    <ModalProvider>
      <NotificationProvider>
        <Routes>
          {/* Rute Publik */}
          <Route path="/" element={<PublicRoute><LandingPage /></PublicRoute>} />
          <Route path="/login" element={<PublicRoute><LoginPage /></PublicRoute>} />
          <Route path="/register" element={<PublicRoute><RegisterPage /></PublicRoute>} />
          <Route path="/forgot-password" element={<PublicRoute><ForgotPasswordPage /></PublicRoute>} />
          <Route path="/reset-password" element={<PublicRoute><ResetPasswordPage /></PublicRoute>} />

          {/* Rute Nasabah */}
          <Route element={<ProtectedRoute><CustomerLayoutWrapper /></ProtectedRoute>}>
              <Route path="/dashboard" element={<DashboardPage />} />
              <Route path="/history" element={<HistoryPage />} />
              <Route path="/payment" element={<PaymentPage />} />
              <Route path="/bills" element={<BillPaymentPage />} />
              <Route path="/profile" element={<ProfilePage />} />
              <Route path="/profile/info" element={<ProfileInfoPage />} />
              <Route path="/profile/change-pin" element={<ChangePinPage />} />
              <Route path="/profile/change-password" element={<ChangePasswordPage />} />
              <Route path="/profile/beneficiaries" element={<BeneficiaryListPage />} />
              <Route path="/profile/cards" element={<CardsPage />} />
              <Route path="/profile/withdrawal-accounts" element={<WithdrawalAccountsPage />} />
              <Route path="/notifications" element={<NotificationsPage />} />
              <Route path="/transfer" element={<TransferPage />} />
              <Route path="/loan-products" element={<LoanProductsListPage />} />
              <Route path="/loan-application/:productId" element={<LoanApplicationPage />} />
              <Route path="/my-loans" element={<MyLoansPage />} />
              <Route path="/my-loans/:loanId" element={<MyLoanDetailPage />} />
              <Route path="/deposits" element={<DepositsPage />} />
              <Route path="/deposits/open" element={<OpenDepositPage />} />
              <Route path="/deposits/:depositId" element={<DepositDetailPage />} />
              <Route path="/topup" element={<TopUpPage />} />
              <Route path="/withdrawal" element={<WithdrawalPage />} />
              <Route path="/investments" element={<InvestmentPage />} />
          </Route>

          {/* Rute Admin */}
          <Route path="/admin" element={<ProtectedRoute adminOnly={true}><AdminLayoutWrapper /></ProtectedRoute>}>
              <Route path="dashboard" element={<AdminDashboardPage />} />
              <Route path="customers" element={<CustomerListPage />} />
              <Route path="customers/add" element={<CustomerAddPage />} />
              <Route path="customers/:customerId" element={<CustomerDetailPage />} />
              <Route path="customers/edit/:customerId" element={<CustomerEditPage />} />
              <Route path="topup-requests" element={<AdminTopUpRequestsPage />} />
              <Route path="withdrawal-requests" element={<AdminWithdrawalRequestsPage />} />
              <Route path="transactions" element={<TransactionListPage />} />
              <Route path="loan-products" element={<LoanProductsPage />} />
              <Route path="deposit-products" element={<DepositProductsPage />} />
              <Route path="deposit-accounts" element={<AdminDepositsListPage />} />
              <Route path="loan-applications" element={<LoanApplicationsPage />} />
              <Route path="loan-applications/:loanId" element={<LoanApplicationDetailPage />} />
              <Route path="loan-accounts" element={<AdminLoansListPage />} />
              <Route path="units" element={<AdminUnitsPage />} />
              <Route path="card-requests" element={<CardRequestsPage />} />
              <Route path="staff" element={<StaffListPage />} />
              <Route path="staff/:staffId/edit" element={<StaffEditPage />} />
              <Route path="reports" element={<ReportsPage />} />
              <Route path="settings" element={<SettingsPage />} />
              <Route path="notifications" element={<AdminNotificationsPage />} />
              <Route path="audit-log" element={<AdminAuditLogPage />} />
              <Route path="teller-deposit" element={<AdminTellerDepositPage />} />
              <Route path="teller-loan-payment" element={<AdminTellerLoanPaymentPage />} />
          </Route>
          
          {/* Rute Khusus Cetak (di luar layout utama) */}
          <Route path="/admin/print-receipt/:transactionId" element={<ProtectedRoute adminOnly={true}><PrintableReceiptPage /></ProtectedRoute>} /> {/* <-- 2. Daftarkan rute baru */}

          {/* Fallback untuk rute yang tidak ditemukan */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </NotificationProvider>
    </ModalProvider>
  );
}

export default App;
