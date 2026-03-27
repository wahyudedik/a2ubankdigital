<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| AJAX Routes (web routes that return JSON)
| These handle interactive flows: inquiry/execute, search, file upload, etc.
| All routes use web middleware (session auth, CSRF via X-XSRF-TOKEN header)
|--------------------------------------------------------------------------
*/

// Public auth routes (register, forgot password)
Route::prefix('auth')->group(function () {
    Route::post('/register/request-otp', [App\Http\Controllers\Auth\RegisterController::class, 'requestOtp']);
    Route::post('/register/verify-otp', [App\Http\Controllers\Auth\RegisterController::class, 'verifyOtp']);
    Route::post('/forgot-password/request', [App\Http\Controllers\Auth\RegisterController::class, 'forgotPasswordRequest']);
    Route::post('/forgot-password/reset', [App\Http\Controllers\Auth\RegisterController::class, 'forgotPasswordReset']);
});

// Public utility
Route::prefix('utility')->group(function () {
    Route::get('/faq', [App\Http\Controllers\UtilityController::class, 'getFaq']);
    Route::get('/public-config', [App\Http\Controllers\UtilityController::class, 'getPublicConfig']);
    Route::post('/loan-calculator', [App\Http\Controllers\UtilityController::class, 'loanCalculator']);
    Route::get('/payment-methods', [App\Http\Controllers\Admin\SystemConfigController::class, 'getPaymentMethods']);
    Route::get('/investment-products', [App\Http\Controllers\UtilityServicesController::class, 'getInvestmentProducts']);
    Route::get('/market-data', [App\Http\Controllers\UtilityServicesController::class, 'getMarketData']);
    Route::get('/nearest-units', [App\Http\Controllers\UtilityServicesController::class, 'getNearestUnits']);
});

// Authenticated user routes (for interactive flows)
Route::middleware(['auth:web'])->prefix('user')->group(function () {
    // Transfer inquiry/execute
    Route::post('/transfer/internal/inquiry', [App\Http\Controllers\User\TransactionController::class, 'internalTransferInquiry']);
    Route::post('/transfer/internal/execute', [App\Http\Controllers\User\TransactionController::class, 'internalTransferExecute']);
    // Bill payment
    Route::get('/bill-payment/billers', [App\Http\Controllers\User\BillPaymentController::class, 'getBillers']);
    Route::post('/bill-payment/inquiry', [App\Http\Controllers\User\BillPaymentController::class, 'inquiry']);
    Route::post('/bill-payment/execute', [App\Http\Controllers\User\BillPaymentController::class, 'execute']);
    // QR Payment
    Route::post('/payment/qr-generate', [App\Http\Controllers\User\QrPaymentController::class, 'generate']);
    // Top-up (file upload)
    Route::post('/topup-requests', function(Request $request) {
        $user = $request->user();
        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('uploads/proofs', 'public');
        }
        DB::table('topup_requests')->insert([
            'user_id' => $user->id, 'amount' => $request->amount,
            'payment_method' => $request->payment_method, 'proof_of_payment_url' => $proofPath ? '/storage/' . $proofPath : null,
            'status' => 'PENDING', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'message' => 'Permintaan isi saldo berhasil dikirim.']);
    });
    // Security
    Route::post('/security/update-password', [App\Http\Controllers\User\SecurityController::class, 'updatePassword']);
    Route::post('/security/update-pin', [App\Http\Controllers\User\SecurityController::class, 'updatePin']);
    // Notifications
    Route::get('/notifications', [App\Http\Controllers\User\NotificationController::class, 'index']);
    Route::put('/notifications/mark-all-read', [App\Http\Controllers\User\NotificationController::class, 'markAllAsRead']);
    // Withdrawal accounts
    Route::get('/withdrawal-accounts', [App\Http\Controllers\User\WithdrawalController::class, 'getAccounts']);
    Route::post('/withdrawal-accounts', [App\Http\Controllers\User\WithdrawalController::class, 'addAccount']);
    Route::delete('/withdrawal-accounts/{id}', function($id) {
        DB::table('withdrawal_accounts')->where('id', $id)->where('user_id', auth()->id())->delete();
        return response()->json(['status' => 'success', 'message' => 'Rekening penarikan berhasil dihapus.']);
    });
    // Withdrawal requests
    Route::post('/withdrawal-requests', [App\Http\Controllers\User\WithdrawalController::class, 'createRequest']);
    // Loans
    Route::get('/loans', [App\Http\Controllers\User\LoanController::class, 'index']);
    Route::get('/loans/{id}', [App\Http\Controllers\User\LoanController::class, 'show']);
    Route::post('/loans/apply', [App\Http\Controllers\User\LoanController::class, 'apply']);
    Route::post('/loans/{id}/pay-installment', [App\Http\Controllers\User\LoanController::class, 'payInstallment']);
    Route::get('/loan-products', [App\Http\Controllers\User\LoanController::class, 'products']);
    // Deposits
    Route::get('/deposits', [App\Http\Controllers\User\DepositController::class, 'index']);
    Route::get('/deposits/{id}', [App\Http\Controllers\User\DepositController::class, 'show']);
    Route::post('/deposits/create', [App\Http\Controllers\User\DepositController::class, 'create']);
    Route::post('/deposits/{id}/disburse', [App\Http\Controllers\User\DepositController::class, 'disburse']);
    Route::get('/deposit-products', [App\Http\Controllers\User\DepositController::class, 'products']);
    // Cards
    Route::get('/cards', [App\Http\Controllers\User\CardController::class, 'index']);
    Route::post('/cards/request', [App\Http\Controllers\User\CardController::class, 'requestCard']);
    Route::put('/cards/{id}/limit', [App\Http\Controllers\User\CardController::class, 'setLimit']);
    Route::put('/cards/{id}/status', [App\Http\Controllers\User\CardController::class, 'updateStatus']);
    // Accounts
    Route::get('/accounts', [App\Http\Controllers\User\AccountController::class, 'index']);
    // Profile
    Route::get('/profile', [App\Http\Controllers\User\ProfileController::class, 'show']);
    Route::put('/profile', [App\Http\Controllers\User\ProfileController::class, 'update']);
    // Transactions
    Route::get('/transactions', [App\Http\Controllers\User\TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [App\Http\Controllers\User\TransactionController::class, 'show']);
    // Digital products
    Route::get('/digital-products', [App\Http\Controllers\User\DigitalProductController::class, 'index']);
    Route::post('/digital-products/purchase', [App\Http\Controllers\User\DigitalProductController::class, 'purchase']);
    // Beneficiaries
    Route::get('/beneficiaries', function() {
        $beneficiaries = DB::table('beneficiaries')->where('user_id', auth()->id())->get();
        return response()->json(['status' => 'success', 'data' => $beneficiaries]);
    });
    Route::post('/beneficiaries', function(Request $request) {
        DB::table('beneficiaries')->insert(['user_id' => auth()->id(), 'nickname' => $request->nickname, 'beneficiary_account_number' => $request->account_number, 'beneficiary_name' => $request->beneficiary_name ?? $request->nickname, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['status' => 'success', 'message' => 'Penerima berhasil ditambahkan.']);
    });
    Route::delete('/beneficiaries/{id}', function($id) {
        DB::table('beneficiaries')->where('id', $id)->where('user_id', auth()->id())->delete();
        return response()->json(['status' => 'success', 'message' => 'Penerima berhasil dihapus.']);
    });
    // Dashboard summary (for pages that still fetch via AJAX)
    Route::get('/dashboard/summary', [App\Http\Controllers\User\DashboardController::class, 'summary']);
});

// Admin routes (for interactive flows)
Route::middleware(['auth:web'])->prefix('admin')->group(function () {
    // Teller operations
    Route::post('/teller/deposit', [App\Http\Controllers\Admin\TellerController::class, 'deposit']);
    Route::post('/teller/pay-installment', [App\Http\Controllers\Admin\TellerController::class, 'payInstallment']);
    Route::get('/teller/search-installments', function(Request $request) {
        $q = $request->input('q', '');
        if (strlen($q) < 3) return response()->json(['status' => 'success', 'data' => []]);
        $installments = DB::table('loan_installments as li')
            ->join('loans as l', 'li.loan_id', '=', 'l.id')
            ->join('users as u', 'l.user_id', '=', 'u.id')
            ->leftJoin('loan_products as lp', 'l.loan_product_id', '=', 'lp.id')
            ->where('li.status', 'PENDING')
            ->where(fn($query) => $query->where('u.full_name', 'like', "%{$q}%")->orWhere('u.bank_id', 'like', "%{$q}%"))
            ->select(['li.id as installment_id', 'u.full_name as customer_name', 'lp.product_name', 'li.installment_number', 'li.due_date', 'li.total_amount as amount_due', DB::raw('0 as penalty_amount')])
            ->limit(20)->get();
        return response()->json(['status' => 'success', 'data' => $installments]);
    });
    // Receipt data
    Route::get('/receipts/{id}', function($id) {
        $tx = DB::table('transactions as t')
            ->leftJoin('accounts as fa', 't.from_account_id', '=', 'fa.id')
            ->leftJoin('users as fu', 'fa.user_id', '=', 'fu.id')
            ->leftJoin('accounts as ta', 't.to_account_id', '=', 'ta.id')
            ->leftJoin('users as tu', 'ta.user_id', '=', 'tu.id')
            ->select(['t.*', 'fu.full_name as from_name', 'tu.full_name as to_name', 'fa.account_number as from_account', 'ta.account_number as to_account'])
            ->where('t.id', $id)->first();
        return response()->json(['status' => 'success', 'data' => $tx]);
    });
    // Process topup/withdrawal
    Route::post('/processing/process-topup', [App\Http\Controllers\Admin\AdvancedProcessingController::class, 'processTopupRequest']);
    Route::put('/withdrawal-requests/{id}/process', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'process']);
    Route::post('/withdrawal-requests/{id}/disburse', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'disburse']);
    // Card requests
    Route::put('/card-requests/{id}/process', [App\Http\Controllers\Admin\CardRequestController::class, 'process']);
    // System settings
    Route::get('/system/settings', [App\Http\Controllers\Admin\SystemConfigController::class, 'getSettings']);
    Route::post('/system/config/update', [App\Http\Controllers\Admin\SystemConfigController::class, 'updateConfig']);
    // Audit log
    Route::get('/reports/audit-logs', [App\Http\Controllers\Admin\ReportsController::class, 'getAuditLog']);
    // Reports (for child components)
    Route::get('/reports/customer-growth', [App\Http\Controllers\Admin\ReportController::class, 'customerGrowth']);
    Route::get('/reports/daily', [App\Http\Controllers\Admin\ReportController::class, 'daily']);
    Route::get('/reports/account-balance', [App\Http\Controllers\Admin\ReportController::class, 'accountBalance']);
    Route::get('/reports/npl', [App\Http\Controllers\Admin\ReportController::class, 'npl']);
    Route::get('/reports/profit-loss', [App\Http\Controllers\Admin\ReportController::class, 'profitLoss']);
    Route::get('/reports/teller', [App\Http\Controllers\Admin\ReportsController::class, 'getTellerReport']);
    Route::get('/reports/marketing', [App\Http\Controllers\Admin\ReportsController::class, 'getMarketingReport']);
    Route::get('/reports/product-performance', [App\Http\Controllers\Admin\ReportsController::class, 'getProductPerformanceReport']);
    // Staff
    Route::get('/staff', [App\Http\Controllers\Admin\StaffController::class, 'index']);
    Route::get('/staff/{id}', [App\Http\Controllers\Admin\StaffController::class, 'show']);
    Route::post('/staff', [App\Http\Controllers\Admin\StaffController::class, 'store']);
    Route::put('/staff/{id}', [App\Http\Controllers\Admin\StaffController::class, 'update']);
    Route::put('/staff/{id}/status', [App\Http\Controllers\Admin\StaffController::class, 'updateStatus']);
    Route::put('/staff/{id}/assignment', [App\Http\Controllers\Admin\StaffController::class, 'updateAssignment']);
    Route::post('/staff/{id}/reset-password', [App\Http\Controllers\Admin\StaffController::class, 'resetPassword']);
    Route::get('/roles', [App\Http\Controllers\Admin\StaffController::class, 'getRoles']);
    // Customers
    Route::get('/customers', [App\Http\Controllers\Admin\CustomerController::class, 'index']);
    Route::get('/customers/{id}', [App\Http\Controllers\Admin\CustomerController::class, 'show']);
    Route::post('/customers', [App\Http\Controllers\Admin\CustomerController::class, 'store']);
    Route::put('/customers/{id}', [App\Http\Controllers\Admin\CustomerController::class, 'update']);
    Route::put('/customers/{id}/status', [App\Http\Controllers\Admin\CustomerController::class, 'updateStatus']);
    // Loans
    Route::get('/loans', [App\Http\Controllers\Admin\LoanController::class, 'index']);
    Route::get('/loans/{id}', [App\Http\Controllers\Admin\LoanController::class, 'show']);
    Route::put('/loans/{id}/status', [App\Http\Controllers\Admin\LoanController::class, 'updateStatus']);
    Route::post('/loans/{id}/disburse', [App\Http\Controllers\Admin\LoanController::class, 'disburse']);
    Route::post('/loans/{id}/force-pay-installment', [App\Http\Controllers\Admin\LoanController::class, 'forcePayInstallment']);
    // Products
    Route::get('/loan-products', [App\Http\Controllers\Admin\ProductController::class, 'getLoanProducts']);
    Route::post('/loan-products', [App\Http\Controllers\Admin\ProductController::class, 'createLoanProduct']);
    Route::put('/loan-products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'updateLoanProduct']);
    Route::delete('/loan-products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'deleteLoanProduct']);
    Route::get('/deposit-products', [App\Http\Controllers\Admin\ProductController::class, 'getDepositProducts']);
    Route::post('/deposit-products', [App\Http\Controllers\Admin\ProductController::class, 'createDepositProduct']);
    Route::put('/deposit-products/{id}', [App\Http\Controllers\Admin\ProductController::class, 'updateDepositProduct']);
    // Units
    Route::get('/units', [App\Http\Controllers\Admin\UnitController::class, 'index']);
    Route::post('/units', [App\Http\Controllers\Admin\UnitController::class, 'store']);
    Route::put('/units/{id}', [App\Http\Controllers\Admin\UnitController::class, 'update']);
    Route::delete('/units/{id}', [App\Http\Controllers\Admin\UnitController::class, 'destroy']);
    Route::get('/branches', [App\Http\Controllers\Admin\UnitController::class, 'getBranches']);
    // Dashboard
    Route::get('/dashboard/summary', [App\Http\Controllers\Admin\DashboardController::class, 'summary']);
    // Transactions
    Route::get('/transactions', function(Request $request) {
        $page = $request->input('page', 1);
        $search = $request->input('search', '');
        $type = $request->input('type', '');
        $limit = 15;
        $query = DB::table('transactions as t')
            ->leftJoin('accounts as fa', 't.from_account_id', '=', 'fa.id')
            ->leftJoin('users as fu', 'fa.user_id', '=', 'fu.id')
            ->leftJoin('accounts as ta', 't.to_account_id', '=', 'ta.id')
            ->leftJoin('users as tu', 'ta.user_id', '=', 'tu.id')
            ->select(['t.*', DB::raw('fu.full_name as from_name'), DB::raw('tu.full_name as to_name')]);
        if ($search) $query->where(fn($q) => $q->where('t.transaction_code', 'like', "%{$search}%")->orWhere('t.description', 'like', "%{$search}%"));
        if ($type) $query->where('t.transaction_type', $type);
        $total = $query->count();
        $data = $query->orderBy('t.created_at', 'desc')->skip(($page - 1) * $limit)->take($limit)->get();
        return response()->json(['status' => 'success', 'data' => $data, 'pagination' => ['current_page' => (int)$page, 'total_pages' => (int)ceil($total / $limit)]]);
    });
    Route::get('/transactions/{id}', function($id) {
        $tx = DB::table('transactions as t')
            ->leftJoin('accounts as fa', 't.from_account_id', '=', 'fa.id')->leftJoin('users as fu', 'fa.user_id', '=', 'fu.id')
            ->leftJoin('accounts as ta', 't.to_account_id', '=', 'ta.id')->leftJoin('users as tu', 'ta.user_id', '=', 'tu.id')
            ->select(['t.*', 'fu.full_name as from_user_name', 'fa.account_number as from_account_number', 'tu.full_name as to_user_name', 'ta.account_number as to_account_number'])
            ->where('t.id', $id)->first();
        return response()->json(['status' => 'success', 'data' => $tx]);
    });
    // Deposits
    Route::get('/deposits', function(Request $request) {
        $deposits = App\Models\Account::where('account_type', 'DEPOSITO')->with(['user', 'depositProduct'])->get()
            ->map(fn($a) => ['id' => $a->id, 'customer_name' => $a->user?->full_name, 'account_number' => $a->account_number, 'product_name' => $a->depositProduct?->product_name, 'balance' => (float)$a->balance, 'maturity_date' => $a->maturity_date, 'status' => $a->status]);
        return response()->json(['status' => 'success', 'data' => ['deposits' => $deposits, 'summary' => ['totalActiveBalance' => $deposits->sum('balance'), 'totalDeposits' => $deposits->count()]]]);
    });
});
