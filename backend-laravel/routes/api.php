<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\TransactionController;
use App\Http\Controllers\User\LoanController as UserLoanController;
use App\Http\Controllers\User\AccountController;
use App\Http\Controllers\User\DepositController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\BillPaymentController;
use App\Http\Controllers\User\DigitalProductController;
use App\Http\Controllers\User\CardController;
use App\Http\Controllers\User\TicketController;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\LoanController as AdminLoanController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\CardRequestController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TellerController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\User\ScheduledTransferController;
use App\Http\Controllers\User\ExternalTransferController;
use App\Http\Controllers\User\EWalletController;
use App\Http\Controllers\User\GoalSavingsController;
use App\Http\Controllers\User\LoyaltyController;
use App\Http\Controllers\User\AnnouncementController;
use App\Http\Controllers\User\SecureMessageController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\TransactionReversalController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/register/request-otp', [RegisterController::class, 'requestOtp']);
    Route::post('/register/verify-otp', [RegisterController::class, 'verifyOtp']);
    Route::post('/forgot-password/request', [RegisterController::class, 'forgotPasswordRequest']);
    Route::post('/forgot-password/reset', [RegisterController::class, 'forgotPasswordReset']);
});

// Public utility routes
Route::prefix('utility')->group(function () {
    Route::get('/faq', [UtilityController::class, 'getFaq']);
    Route::get('/system-status', [UtilityController::class, 'getSystemStatus']);
    Route::get('/public-config', [UtilityController::class, 'getPublicConfig']);
    Route::post('/loan-calculator', [UtilityController::class, 'loanCalculator']);
    Route::get('/currency-rates', [UtilityController::class, 'getCurrencyRates']);
});

// Protected routes - Customer
Route::middleware(['auth:sanctum', 'role:customer'])->prefix('user')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/picture', [ProfileController::class, 'updatePicture']);
    
    // Accounts
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/accounts/{id}', [AccountController::class, 'show']);
    Route::get('/accounts/{id}/statement', [AccountController::class, 'statement']);
    
    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/transfer/internal/inquiry', [TransactionController::class, 'internalTransferInquiry']);
    Route::post('/transfer/internal/execute', [TransactionController::class, 'internalTransferExecute']);
    
    // Loans
    Route::get('/loans', [UserLoanController::class, 'index']);
    Route::get('/loans/{id}', [UserLoanController::class, 'show']);
    Route::post('/loans/apply', [UserLoanController::class, 'apply']);
    Route::post('/loans/{id}/pay-installment', [UserLoanController::class, 'payInstallment']);
    Route::get('/loan-products', [UserLoanController::class, 'products']);
    
    // Deposits
    Route::get('/deposits', [DepositController::class, 'index']);
    Route::get('/deposits/{id}', [DepositController::class, 'show']);
    Route::post('/deposits/create', [DepositController::class, 'create']);
    Route::post('/deposits/{id}/disburse', [DepositController::class, 'disburse']);
    Route::get('/deposit-products', [DepositController::class, 'products']);
    
    // Bill Payments
    Route::get('/bill-payment/billers', [BillPaymentController::class, 'getBillers']);
    Route::post('/bill-payment/inquiry', [BillPaymentController::class, 'inquiry']);
    Route::post('/bill-payment/execute', [BillPaymentController::class, 'execute']);
    Route::get('/bill-payment/history', [BillPaymentController::class, 'history']);
    
    // Digital Products
    Route::get('/digital-products', [DigitalProductController::class, 'index']);
    Route::post('/digital-products/purchase', [DigitalProductController::class, 'purchase']);
    Route::get('/digital-products/history', [DigitalProductController::class, 'history']);
    
    // Cards
    Route::get('/cards', [CardController::class, 'index']);
    Route::get('/cards/{id}', [CardController::class, 'show']);
    Route::post('/cards/request', [CardController::class, 'requestCard']);
    Route::put('/cards/{id}/limit', [CardController::class, 'setLimit']);
    Route::put('/cards/{id}/status', [CardController::class, 'updateStatus']);
    Route::get('/cards/requests/history', [CardController::class, 'requestHistory']);
    
    // Support Tickets
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::post('/tickets/{id}/reply', [TicketController::class, 'reply']);
    Route::put('/tickets/{id}/close', [TicketController::class, 'close']);
    
    // Scheduled Transfers
    Route::post('/scheduled-transfers', [ScheduledTransferController::class, 'scheduleTransfer']);
    Route::get('/scheduled-transfers', [ScheduledTransferController::class, 'getScheduledTransfers']);
    Route::delete('/scheduled-transfers/{id}', [ScheduledTransferController::class, 'cancelScheduledTransfer']);
    
    // Standing Instructions
    Route::post('/standing-instructions', [ScheduledTransferController::class, 'createStandingInstruction']);
    Route::get('/standing-instructions', [ScheduledTransferController::class, 'getStandingInstructions']);
    Route::put('/standing-instructions/{id}/status', [ScheduledTransferController::class, 'updateStandingInstructionStatus']);
    
    // External Transfers
    Route::post('/transfer/external/inquiry', [ExternalTransferController::class, 'inquiry']);
    Route::post('/transfer/external/execute', [ExternalTransferController::class, 'execute']);
    Route::get('/interbank-list', [ExternalTransferController::class, 'getInterbankList']);
    Route::get('/bank-branches', [ExternalTransferController::class, 'getBankBranches']);
    
    // E-Wallet Top-up
    Route::post('/ewallet/topup/inquiry', [EWalletController::class, 'topupInquiry']);
    Route::post('/ewallet/topup/execute', [EWalletController::class, 'topupExecute']);
    Route::get('/ewallet/providers', [EWalletController::class, 'getProviders']);
    
    // Goal Savings
    Route::post('/goal-savings/open', [GoalSavingsController::class, 'openGoalSavings']);
    Route::get('/goal-savings/detail', [GoalSavingsController::class, 'getGoalSavingsDetail']);
    Route::post('/goal-savings/deposit', [GoalSavingsController::class, 'manualDeposit']);
    
    // Loyalty Points
    Route::get('/loyalty/points', [LoyaltyController::class, 'getLoyaltyPoints']);
    Route::post('/loyalty/redeem', [LoyaltyController::class, 'redeemPoints']);
    Route::get('/loyalty/rewards', [LoyaltyController::class, 'getAvailableRewards']);
    
    // Announcements
    Route::get('/announcements', [AnnouncementController::class, 'getActiveAnnouncements']);
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);
    
    // Secure Messages
    Route::post('/secure-messages/send', [SecureMessageController::class, 'sendSecureMessage']);
    Route::get('/secure-messages/threads', [SecureMessageController::class, 'getMessageThreads']);
    Route::get('/secure-messages/thread', [SecureMessageController::class, 'getThreadMessages']);
    Route::get('/secure-messages/contacts', [SecureMessageController::class, 'getAvailableContacts']);
    
    // Logout
    Route::post('/logout', [LoginController::class, 'logout']);
});

// Notifications - accessible by all authenticated users
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user/notifications', [NotificationController::class, 'index']);
    Route::get('/user/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/user/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/user/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/user/notifications/{id}', [NotificationController::class, 'destroy']);
});

// Protected routes - Admin/Staff
Route::middleware(['auth:sanctum', 'role:super_admin,admin,manager,teller,cs'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    
    // Customers
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{id}', [CustomerController::class, 'update']);
    Route::put('/customers/{id}/status', [CustomerController::class, 'updateStatus']);
    
    // Loans
    Route::get('/loans', [AdminLoanController::class, 'index']);
    Route::get('/loans/{id}', [AdminLoanController::class, 'show']);
    Route::put('/loans/{id}/status', [AdminLoanController::class, 'updateStatus']);
    Route::post('/loans/{id}/disburse', [AdminLoanController::class, 'disburse']);
    Route::post('/loans/{id}/force-pay-installment', [AdminLoanController::class, 'forcePayInstallment']);
    
    // Reports
    Route::get('/reports/daily', [ReportController::class, 'daily']);
    Route::get('/reports/account-balance', [ReportController::class, 'accountBalance']);
    Route::get('/reports/npl', [ReportController::class, 'npl']);
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
    Route::get('/reports/customer-growth', [ReportController::class, 'customerGrowth']);
    
    // Card Requests
    Route::get('/card-requests', [CardRequestController::class, 'index']);
    Route::get('/card-requests/{id}', [CardRequestController::class, 'show']);
    Route::put('/card-requests/{id}/process', [CardRequestController::class, 'process']);
    
    // Withdrawal Requests
    Route::get('/withdrawal-requests', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'index']);
    Route::get('/withdrawal-requests/{id}', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'show']);
    Route::put('/withdrawal-requests/{id}/process', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'process']);
    Route::post('/withdrawal-requests/{id}/disburse', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'disburse']);
    
    // Support Tickets
    Route::get('/tickets', [AdminTicketController::class, 'index']);
    Route::get('/tickets/{id}', [AdminTicketController::class, 'show']);
    Route::put('/tickets/{id}/assign', [AdminTicketController::class, 'assign']);
    Route::post('/tickets/{id}/reply', [AdminTicketController::class, 'reply']);
    Route::put('/tickets/{id}/close', [AdminTicketController::class, 'close']);
    Route::get('/tickets/statistics', [AdminTicketController::class, 'statistics']);
    
    // Staff Management
    Route::get('/staff', [StaffController::class, 'index']);
    Route::get('/staff/{id}', [StaffController::class, 'show']);
    Route::post('/staff', [StaffController::class, 'store']);
    Route::put('/staff/{id}', [StaffController::class, 'update']);
    Route::put('/staff/{id}/status', [StaffController::class, 'updateStatus']);
    Route::put('/staff/{id}/assignment', [StaffController::class, 'updateAssignment']);
    Route::post('/staff/{id}/reset-password', [StaffController::class, 'resetPassword']);
    Route::get('/roles', [StaffController::class, 'getRoles']);
    
    // Teller Operations
    Route::post('/teller/deposit', [TellerController::class, 'deposit']);
    Route::post('/teller/withdrawal', [TellerController::class, 'withdrawal']);
    Route::post('/teller/pay-installment', [TellerController::class, 'payInstallment']);
    
    // Unit/Branch Management
    Route::get('/units', [UnitController::class, 'index']);
    Route::get('/units/{id}', [UnitController::class, 'show']);
    Route::post('/units', [UnitController::class, 'store']);
    Route::put('/units/{id}', [UnitController::class, 'update']);
    Route::delete('/units/{id}', [UnitController::class, 'destroy']);
    Route::get('/branches', [UnitController::class, 'getBranches']);
    Route::get('/branches/{id}/units', [UnitController::class, 'getUnitsByBranch']);
    
    // Product Management
    Route::get('/loan-products', [ProductController::class, 'getLoanProducts']);
    Route::post('/loan-products', [ProductController::class, 'createLoanProduct']);
    Route::put('/loan-products/{id}', [ProductController::class, 'updateLoanProduct']);
    Route::delete('/loan-products/{id}', [ProductController::class, 'deleteLoanProduct']);
    
    Route::get('/deposit-products', [ProductController::class, 'getDepositProducts']);
    Route::post('/deposit-products', [ProductController::class, 'createDepositProduct']);
    Route::put('/deposit-products/{id}', [ProductController::class, 'updateDepositProduct']);
    
    Route::get('/digital-products', [ProductController::class, 'getDigitalProducts']);
    Route::post('/digital-products', [ProductController::class, 'createDigitalProduct']);
    Route::put('/digital-products/{id}', [ProductController::class, 'updateDigitalProduct']);
    Route::delete('/digital-products/{id}', [ProductController::class, 'deleteDigitalProduct']);
    
    // Transaction Reversal
    Route::get('/transactions/reversible', [TransactionReversalController::class, 'getReversibleTransactions']);
    Route::post('/transactions/{id}/reverse', [TransactionReversalController::class, 'reverseTransaction']);
    
    // Announcements Management
    Route::get('/announcements', [AdminAnnouncementController::class, 'index']);
    Route::post('/announcements', [AdminAnnouncementController::class, 'createGlobalAnnouncement']);
    Route::put('/announcements/{id}', [AdminAnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [AdminAnnouncementController::class, 'destroy']);
    Route::get('/announcements/statistics', [AdminAnnouncementController::class, 'getStatistics']);
});

// Account Management & Security Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Device approval routes
    Route::post('/auth/request-device-approval', [App\Http\Controllers\Auth\AuthController::class, 'requestDeviceApproval']);
    Route::post('/auth/approve-device', [App\Http\Controllers\Auth\AuthController::class, 'approveNewDevice']);
    
    // Security activity routes
    Route::prefix('user/security')->group(function () {
        Route::get('/login-history', [App\Http\Controllers\User\SecurityActivityController::class, 'getLoginHistory']);
        Route::get('/activity', [App\Http\Controllers\User\SecurityActivityController::class, 'getSecurityActivity']);
    });
});

// Admin Account Management Routes
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
    Route::prefix('admin/account-management')->group(function () {
        // Account closure management
        Route::get('/closure-requests', [App\Http\Controllers\Admin\AccountManagementController::class, 'getAccountClosureRequests']);
        Route::post('/process-closure', [App\Http\Controllers\Admin\AccountManagementController::class, 'processAccountClosure']);
        
        // Credit limit management
        Route::get('/credit-limit-requests', [App\Http\Controllers\Admin\AccountManagementController::class, 'getCreditLimitRequests']);
        Route::post('/process-credit-limit', [App\Http\Controllers\Admin\AccountManagementController::class, 'processCreditLimitRequest']);
    });
    
    // Direct messaging routes
    Route::prefix('admin/messages')->group(function () {
        Route::post('/send-direct', [App\Http\Controllers\Admin\DirectMessageController::class, 'sendDirectMessage']);
        Route::get('/sent', [App\Http\Controllers\Admin\DirectMessageController::class, 'getSentMessages']);
        Route::get('/thread', [App\Http\Controllers\Admin\DirectMessageController::class, 'getMessageThread']);
        Route::post('/mark-read', [App\Http\Controllers\Admin\DirectMessageController::class, 'markAsRead']);
    });
});
// Advanced Reports & Analytics Routes
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
    Route::prefix('admin/reports')->group(function () {
        // Teller reports
        Route::get('/teller', [App\Http\Controllers\Admin\ReportsController::class, 'getTellerReport']);
        
        // Marketing reports
        Route::get('/marketing', [App\Http\Controllers\Admin\ReportsController::class, 'getMarketingReport']);
        
        // Product performance reports
        Route::get('/product-performance', [App\Http\Controllers\Admin\ReportsController::class, 'getProductPerformanceReport']);
        
        // Dormant customer reports
        Route::get('/dormant-customers', [App\Http\Controllers\Admin\ReportsController::class, 'getDormantCustomerReport']);
        
        // User activity reports
        Route::get('/user-activity', [App\Http\Controllers\Admin\ReportsController::class, 'getUserActivityReport']);
        
        // Daily reconciliation reports
        Route::get('/daily-reconciliation', [App\Http\Controllers\Admin\ReportsController::class, 'getDailyReconciliationReport']);
        
        // Audit logs
        Route::get('/audit-logs', [App\Http\Controllers\Admin\ReportsController::class, 'getAuditLog']);
        
        // System logs
        Route::get('/system-logs', [App\Http\Controllers\Admin\ReportsController::class, 'getSystemLogs']);
    });
});
// System Administration Routes
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
    Route::prefix('admin/system')->group(function () {
        // System configuration
        Route::get('/settings', [App\Http\Controllers\Admin\SystemConfigController::class, 'getSettings']);
        Route::post('/config/update', [App\Http\Controllers\Admin\SystemConfigController::class, 'updateConfig']);
        
        // FAQ management
        Route::prefix('faq')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\FaqController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Admin\FaqController::class, 'store']);
            Route::get('/{id}', [App\Http\Controllers\Admin\FaqController::class, 'show']);
            Route::put('/{id}', [App\Http\Controllers\Admin\FaqController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Admin\FaqController::class, 'destroy']);
            Route::post('/sort-order', [App\Http\Controllers\Admin\FaqController::class, 'updateSortOrder']);
            Route::post('/{id}/toggle-status', [App\Http\Controllers\Admin\FaqController::class, 'toggleStatus']);
        });
    });
});

// Public Utility Routes
Route::prefix('utility')->group(function () {
    // Public configuration
    Route::get('/config', [App\Http\Controllers\Admin\SystemConfigController::class, 'getPublicConfig']);
    Route::get('/payment-methods', [App\Http\Controllers\Admin\SystemConfigController::class, 'getPaymentMethods']);
    
    // Investment and market data
    Route::get('/investment-products', [App\Http\Controllers\UtilityServicesController::class, 'getInvestmentProducts']);
    Route::get('/market-data', [App\Http\Controllers\UtilityServicesController::class, 'getMarketData']);
    Route::get('/nearest-units', [App\Http\Controllers\UtilityServicesController::class, 'getNearestUnits']);
});

// Authenticated Utility Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // File upload
    Route::post('/utility/upload', [App\Http\Controllers\UtilityServicesController::class, 'uploadFile']);
    
    // Beneficiary management
    Route::prefix('user/beneficiaries')->group(function () {
        Route::get('/', [App\Http\Controllers\UtilityServicesController::class, 'getBeneficiaries']);
        Route::post('/', [App\Http\Controllers\UtilityServicesController::class, 'addBeneficiary']);
        Route::delete('/{id}', [App\Http\Controllers\UtilityServicesController::class, 'deleteBeneficiary']);
    });
});
// Specialized Features Routes

// Debt Collection Routes (for debt collectors)
Route::middleware(['auth:sanctum', 'role:debt_collector,admin,super_admin'])->group(function () {
    Route::prefix('debt-collection')->group(function () {
        Route::get('/assignments', [App\Http\Controllers\DebtCollectorController::class, 'getAssignments']);
        Route::post('/visit-report', [App\Http\Controllers\DebtCollectorController::class, 'submitVisitReport']);
        Route::put('/assignment/{id}', [App\Http\Controllers\DebtCollectorController::class, 'updateAssignment']);
        Route::get('/assignment/{id}/details', [App\Http\Controllers\DebtCollectorController::class, 'getAssignmentDetails']);
    });
});

// Marketing & Promotions Routes (for marketing staff and admins)
Route::middleware(['auth:sanctum', 'role:marketing,admin,super_admin'])->group(function () {
    Route::prefix('admin/marketing')->group(function () {
        // Customer segmentation
        Route::get('/customer-segments', [App\Http\Controllers\Admin\MarketingController::class, 'getCustomerSegments']);
        
        // Campaign management
        Route::post('/send-promotion', [App\Http\Controllers\Admin\MarketingController::class, 'sendPromotion']);
        Route::get('/campaign-performance', [App\Http\Controllers\Admin\MarketingController::class, 'getCampaignPerformance']);
    });
});

// Advanced Processing Routes (for admin staff)
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
    Route::prefix('admin/processing')->group(function () {
        // Top-up request processing
        Route::get('/topup-requests', [App\Http\Controllers\Admin\AdvancedProcessingController::class, 'getTopupRequests']);
        Route::post('/process-topup', [App\Http\Controllers\Admin\AdvancedProcessingController::class, 'processTopupRequest']);
        
        // Credit limit processing
        Route::post('/process-credit-limit', [App\Http\Controllers\Admin\AdvancedProcessingController::class, 'processCreditLimitRequest']);
        
        // Document review
        Route::get('/pending-documents', [App\Http\Controllers\Admin\AdvancedProcessingController::class, 'getPendingDocuments']);
        Route::post('/review-document', [App\Http\Controllers\Admin\AdvancedProcessingController::class, 'reviewUploadedDocument']);
    });
});