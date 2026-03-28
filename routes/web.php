<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Inertia\AuthPageController;
use App\Http\Controllers\Inertia\UserPageController;
use App\Http\Controllers\Inertia\AdminPageController;
use App\Http\Controllers\Inertia\ActionController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthPageController::class, 'landing']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthPageController::class, 'loginPage'])->name('login');
    Route::post('/login', [AuthPageController::class, 'login']);
    Route::get('/register', [AuthPageController::class, 'registerPage']);
    Route::get('/forgot-password', [AuthPageController::class, 'forgotPasswordPage']);
    Route::get('/reset-password', [AuthPageController::class, 'resetPasswordPage']);
});

Route::post('/logout', [AuthPageController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Customer Routes (role: Nasabah = 9)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/dashboard', [UserPageController::class, 'dashboard']);
    Route::get('/history', [UserPageController::class, 'history']);
    Route::get('/payment', [UserPageController::class, 'payment']);
    Route::get('/bills', [UserPageController::class, 'billPayment']);
    Route::get('/profile', [UserPageController::class, 'profile']);
    Route::get('/profile/info', [UserPageController::class, 'profileInfo']);
    Route::post('/profile/info', [ActionController::class, 'updateProfile']);
    Route::get('/profile/change-pin', [UserPageController::class, 'changePin']);
    Route::post('/profile/change-pin', [ActionController::class, 'changePin']);
    Route::get('/profile/change-password', [UserPageController::class, 'changePassword']);
    Route::post('/profile/change-password', [ActionController::class, 'changePassword']);
    Route::get('/profile/beneficiaries', [UserPageController::class, 'beneficiaries']);
    Route::post('/profile/beneficiaries', [ActionController::class, 'addBeneficiary']);
    Route::delete('/profile/beneficiaries/{id}', [ActionController::class, 'deleteBeneficiary']);
    Route::get('/profile/cards', [UserPageController::class, 'cards']);
    Route::get('/profile/withdrawal-accounts', [UserPageController::class, 'withdrawalAccounts']);
    Route::post('/profile/withdrawal-accounts', [ActionController::class, 'addWithdrawalAccount']);
    Route::delete('/profile/withdrawal-accounts/{id}', [ActionController::class, 'deleteWithdrawalAccount']);
    Route::get('/notifications', [UserPageController::class, 'notifications']);
    Route::post('/notifications/mark-all-read', [ActionController::class, 'markAllNotificationsRead']);
    Route::get('/transfer', [UserPageController::class, 'transfer']);
    Route::post('/transfer/inquiry', [ActionController::class, 'transferInquiry']);
    Route::post('/transfer/execute', [ActionController::class, 'internalTransfer']);
    Route::get('/loan-products', [UserPageController::class, 'loanProducts']);
    Route::get('/loan-application/{productId}', [UserPageController::class, 'loanApplication']);
    Route::get('/my-loans', [UserPageController::class, 'myLoans']);
    Route::get('/my-loans/{loanId}', [UserPageController::class, 'myLoanDetail']);
    Route::get('/deposits', [UserPageController::class, 'deposits']);
    Route::get('/deposits/open', [UserPageController::class, 'openDeposit']);
    Route::get('/deposits/{depositId}', [UserPageController::class, 'depositDetail']);
    Route::get('/topup', [UserPageController::class, 'topup']);
    Route::get('/withdrawal', [UserPageController::class, 'withdrawal']);
    Route::get('/investments', [UserPageController::class, 'investments']);
});

/*
|--------------------------------------------------------------------------
| Admin Routes (all staff roles: 1-8)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin,admin,manager,marketing,teller,cs,analyst,debt_collector'])->prefix('admin')->group(function () {
    // Dashboard - semua staff
    Route::get('/dashboard', [AdminPageController::class, 'dashboard']);
    Route::get('/notifications', [AdminPageController::class, 'notifications']);

    // Nasabah - semua staff bisa lihat
    Route::get('/customers', [AdminPageController::class, 'customers']);
    Route::get('/customers/{customerId}', [AdminPageController::class, 'customerDetail'])->where('customerId', '[0-9]+');

    // Teller operations - teller & cs
    Route::middleware('role:super_admin,admin,manager,teller,cs')->group(function () {
        Route::get('/teller-deposit', [AdminPageController::class, 'tellerDeposit']);
        Route::get('/teller-loan-payment', [AdminPageController::class, 'tellerLoanPayment']);
        Route::get('/print-receipt/{transactionId}', [AdminPageController::class, 'printReceipt']);
    });

    // Permintaan - teller, cs, manager ke atas
    Route::middleware('role:super_admin,admin,manager,teller,cs')->group(function () {
        Route::get('/topup-requests', [AdminPageController::class, 'topupRequests']);
        Route::get('/withdrawal-requests', [AdminPageController::class, 'withdrawalRequests']);
        Route::get('/card-requests', [AdminPageController::class, 'cardRequests']);
    });

    // Transaksi - semua staff bisa lihat
    Route::get('/transactions', [AdminPageController::class, 'transactions']);

    // Pinjaman - manager ke atas + analis kredit
    Route::middleware('role:super_admin,admin,manager,analyst')->group(function () {
        Route::get('/loan-applications', [AdminPageController::class, 'loanApplications']);
        Route::get('/loan-applications/{loanId}', [AdminPageController::class, 'loanApplicationDetail']);
        Route::put('/loans/{id}/status', [ActionController::class, 'updateLoanStatus']);
        Route::post('/loans/{id}/disburse', [ActionController::class, 'disburseLoan']);
        Route::get('/loan-accounts', [AdminPageController::class, 'loanAccounts']);
    });

    // Produk & Deposito - manager ke atas
    Route::middleware('role:super_admin,admin,manager')->group(function () {
        Route::get('/loan-products', [AdminPageController::class, 'loanProducts']);
        Route::post('/loan-products', [ActionController::class, 'storeLoanProduct']);
        Route::put('/loan-products/{id}', [ActionController::class, 'updateLoanProduct']);
        Route::delete('/loan-products/{id}', [ActionController::class, 'deleteLoanProduct']);
        Route::get('/deposit-products', [AdminPageController::class, 'depositProducts']);
        Route::post('/deposit-products', [ActionController::class, 'storeDepositProduct']);
        Route::put('/deposit-products/{id}', [ActionController::class, 'updateDepositProduct']);
        Route::get('/deposit-accounts', [AdminPageController::class, 'depositsAccounts']);
    });

    // Tambah/edit nasabah - teller, cs, manager ke atas
    Route::middleware('role:super_admin,admin,manager,teller,cs')->group(function () {
        Route::get('/customers/add', [AdminPageController::class, 'customerAdd']);
        Route::post('/customers', [ActionController::class, 'storeCustomer']);
        Route::get('/customers/edit/{customerId}', [AdminPageController::class, 'customerEdit']);
        Route::put('/customers/{id}', [ActionController::class, 'updateCustomer']);
        Route::put('/customers/{id}/status', [ActionController::class, 'updateCustomerStatus']);
    });

    // Struktur organisasi - admin ke atas
    Route::middleware('role:super_admin,admin')->group(function () {
        Route::get('/units', [AdminPageController::class, 'units']);
        Route::post('/units', [ActionController::class, 'storeUnit']);
        Route::put('/units/{id}', [ActionController::class, 'updateUnit']);
        Route::delete('/units/{id}', [ActionController::class, 'deleteUnit']);
        Route::get('/staff', [AdminPageController::class, 'staff']);
        Route::post('/staff', [ActionController::class, 'storeStaff']);
        Route::get('/staff/{staffId}/edit', [AdminPageController::class, 'staffEdit']);
        Route::put('/staff/{id}', [ActionController::class, 'updateStaff']);
        Route::put('/staff/{id}/status', [ActionController::class, 'updateStaffStatus']);
        Route::post('/staff/{id}/reset-password', [ActionController::class, 'resetStaffPassword']);
    });

    // Laporan - manager ke atas + marketing
    Route::middleware('role:super_admin,admin,manager,marketing')->group(function () {
        Route::get('/reports', [AdminPageController::class, 'reports']);
    });

    // Pengaturan & Audit - super admin & admin saja
    Route::middleware('role:super_admin,admin')->group(function () {
        Route::get('/settings', [AdminPageController::class, 'settings']);
        Route::get('/audit-log', [AdminPageController::class, 'auditLog']);
        Route::get('/build', [AdminPageController::class, 'build']);
    });
});

/*
|--------------------------------------------------------------------------
| Catch-all
|--------------------------------------------------------------------------
*/
Route::fallback(fn () => redirect('/'));
