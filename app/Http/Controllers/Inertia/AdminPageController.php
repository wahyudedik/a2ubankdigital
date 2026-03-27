<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\DepositProduct;
use App\Models\CustomerProfile;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\Card;
use App\Models\CardRequest;
use App\Models\LoanInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminPageController extends Controller
{
    public function dashboard()
    {
        $totalCustomerFunds = (float) (Account::where('status', 'ACTIVE')->sum('balance') ?? 0);
        $outstandingLoanPortfolio = (float) (Loan::whereIn('status', ['DISBURSED', 'ACTIVE'])->sum('loan_amount') ?? 0);
        $feeRevenueMonthly = (float) (Transaction::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status', 'SUCCESS')->sum('fee') ?? 0);
        $newCustomersMonthly = User::where('role_id', 9)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        $pendingTopups = DB::table('topup_requests')->where('status', 'PENDING')->count();
        $pendingWithdrawals = DB::table('withdrawal_requests')->where('status', 'PENDING')->count();
        $pendingLoans = Loan::where('status', 'SUBMITTED')->count();
        $pendingLoanDisbursements = Loan::where('status', 'APPROVED')->count();
        $pendingWithdrawalDisbursements = DB::table('withdrawal_requests')->where('status', 'APPROVED')->count();

        $recentActivities = DB::table('transactions as t')
            ->leftJoin('accounts as from_acc', 't.from_account_id', '=', 'from_acc.id')
            ->leftJoin('users as from_user', 'from_acc.user_id', '=', 'from_user.id')
            ->leftJoin('accounts as to_acc', 't.to_account_id', '=', 'to_acc.id')
            ->leftJoin('users as to_user', 'to_acc.user_id', '=', 'to_user.id')
            ->select(['t.id', 't.transaction_code', 't.transaction_type', 't.amount', 't.description', 't.status', 't.created_at', DB::raw('COALESCE(from_user.full_name, to_user.full_name, "System") as full_name')])
            ->where('t.status', 'SUCCESS')->orderBy('t.created_at', 'desc')->limit(10)->get();

        $customerGrowth = User::where('role_id', 9)->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as registration_date, COUNT(*) as new_customers')
            ->groupBy('registration_date')->orderBy('registration_date')->get();

        return Inertia::render('AdminDashboardPage', [
            'kpi' => ['fee_revenue_monthly' => $feeRevenueMonthly, 'total_customer_funds' => $totalCustomerFunds, 'outstanding_loan_portfolio' => $outstandingLoanPortfolio, 'new_customers_monthly' => $newCustomersMonthly],
            'tasks' => compact('pendingTopups', 'pendingWithdrawals', 'pendingLoans', 'pendingLoanDisbursements', 'pendingWithdrawalDisbursements'),
            'recentActivities' => $recentActivities,
            'customerGrowth' => $customerGrowth,
        ]);
    }

    public function customers(Request $request)
    {
        $page = $request->input('page', 1);
        $search = $request->input('search', '');
        $limit = 10;

        $query = User::where('role_id', 9);
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('bank_id', 'like', "%{$search}%");
            });
        }
        $total = $query->count();
        $customers = $query->select(['id', 'bank_id', 'full_name', 'email', 'phone_number', 'status', 'created_at'])
            ->orderBy('created_at', 'desc')->skip(($page - 1) * $limit)->take($limit)->get();

        return Inertia::render('CustomerListPage', [
            'customers' => $customers,
            'pagination' => ['current_page' => (int)$page, 'total_pages' => (int)ceil($total / $limit), 'total_records' => $total],
            'filters' => ['search' => $search],
        ]);
    }

    public function customerDetail($customerId)
    {
        $customer = User::with(['customerProfile', 'accounts.depositProduct'])->where('role_id', 9)->findOrFail($customerId);
        $loans = Loan::where('user_id', $customerId)->with(['loanProduct', 'installments'])->orderBy('created_at', 'desc')->get();
        $profile = $customer->customerProfile;

        return Inertia::render('CustomerDetailPage', [
            'customer' => [
                'id' => $customer->id, 'bank_id' => $customer->bank_id, 'full_name' => $customer->full_name,
                'email' => $customer->email, 'phone_number' => $customer->phone_number, 'status' => $customer->status,
                'nik' => $profile?->nik, 'mother_maiden_name' => $profile?->mother_maiden_name,
                'pob' => $profile?->pob, 'dob' => $profile?->dob, 'gender' => $profile?->gender,
                'address_ktp' => $profile?->address_ktp, 'ktp_image_path' => $profile?->ktp_image_path,
                'selfie_image_path' => $profile?->selfie_image_path,
                'branch_name' => $profile?->unit?->unit_name ?? '-', 'unit_name' => $profile?->unit?->unit_name ?? '-',
                'accounts' => $customer->accounts->map(fn($a) => [
                    'id' => $a->id, 'account_number' => $a->account_number, 'balance' => (float)$a->balance,
                    'account_type' => $a->account_type, 'status' => $a->status,
                    'deposit_product_name' => $a->depositProduct?->product_name,
                    'interest_earned' => 0, 'maturity_date' => $a->maturity_date,
                ]),
                'loans' => $loans->map(fn($l) => [
                    'id' => $l->id, 'product_name' => $l->loanProduct?->product_name, 'loan_amount' => (float)$l->loan_amount,
                    'tenor' => $l->tenor, 'tenor_unit' => $l->tenor_unit, 'status' => $l->status,
                    'installments' => $l->installments->map(fn($i) => [
                        'id' => $i->id, 'installment_number' => $i->installment_number, 'due_date' => $i->due_date,
                        'amount_due' => (float)$i->total_amount, 'penalty_amount' => 0, 'status' => $i->status,
                    ]),
                ]),
            ],
        ]);
    }

    public function customerAdd()
    {
        $units = DB::table('units')->where('status', 'ACTIVE')->get()->groupBy('unit_type');
        $branches = DB::table('units')->where('unit_type', 'KANTOR_CABANG')->where('status', 'ACTIVE')->get();
        return Inertia::render('CustomerAddPage', ['units' => $branches, 'allUnits' => DB::table('units')->where('status', 'ACTIVE')->get()]);
    }

    public function customerEdit($customerId)
    {
        $customer = User::with('customerProfile')->where('role_id', 9)->findOrFail($customerId);
        $units = DB::table('units')->where('status', 'ACTIVE')->get();
        $branches = DB::table('units')->where('unit_type', 'KANTOR_CABANG')->where('status', 'ACTIVE')->get();
        return Inertia::render('CustomerEditPage', ['customer' => $customer, 'units' => $branches, 'allUnits' => $units]);
    }

    public function loanProducts()
    {
        $products = LoanProduct::orderBy('created_at', 'desc')->get();
        return Inertia::render('LoanProductsPage', ['products' => $products]);
    }

    public function depositProducts()
    {
        $products = DepositProduct::orderBy('created_at', 'desc')->get();
        return Inertia::render('DepositProductsPage', ['products' => $products]);
    }

    public function loanApplications(Request $request)
    {
        $status = $request->input('status', 'SUBMITTED');
        $loans = Loan::with(['user', 'loanProduct'])->where('status', $status)->orderBy('created_at', 'desc')->get()
            ->map(fn($l) => [
                'id' => $l->id, 'customer_name' => $l->user?->full_name, 'product_name' => $l->loanProduct?->product_name,
                'loan_amount' => (float)$l->loan_amount, 'tenor' => $l->tenor, 'tenor_unit' => $l->tenor_unit,
                'application_date' => $l->created_at, 'status' => $l->status,
            ]);
        return Inertia::render('LoanApplicationsPage', ['loans' => $loans, 'filters' => ['status' => $status]]);
    }

    public function loanApplicationDetail($loanId)
    {
        $loan = Loan::with(['user', 'loanProduct', 'installments'])->findOrFail($loanId);
        return Inertia::render('LoanApplicationDetailPage', [
            'loan' => [
                'id' => $loan->id, 'customer_name' => $loan->user?->full_name, 'email' => $loan->user?->email,
                'phone_number' => $loan->user?->phone_number, 'product_name' => $loan->loanProduct?->product_name,
                'loan_amount' => (float)$loan->loan_amount, 'tenor' => $loan->tenor, 'tenor_unit' => $loan->tenor_unit,
                'status' => $loan->status, 'application_date' => $loan->created_at,
                'installments' => $loan->installments->map(fn($i) => [
                    'id' => $i->id, 'installment_number' => $i->installment_number, 'due_date' => $i->due_date,
                    'amount_due' => (float)$i->total_amount, 'principal_amount' => (float)$i->principal_amount,
                    'interest_amount' => (float)$i->interest_amount, 'penalty_amount' => 0, 'status' => $i->status,
                ]),
            ],
        ]);
    }

    public function loanAccounts(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $page = $request->input('page', 1);
        $limit = 15;

        $query = Loan::with(['user', 'loanProduct'])->whereIn('status', ['DISBURSED', 'ACTIVE', 'COMPLETED', 'OVERDUE']);
        if ($search) $query->whereHas('user', fn($q) => $q->where('full_name', 'like', "%{$search}%"));
        if ($status) $query->where('status', strtoupper($status));

        $total = $query->count();
        $loans = $query->orderBy('created_at', 'desc')->skip(($page - 1) * $limit)->take($limit)->get()
            ->map(fn($l) => [
                'id' => $l->id, 'customer_name' => $l->user?->full_name, 'product_name' => $l->loanProduct?->product_name,
                'loan_amount' => (float)$l->loan_amount, 'status' => $l->status,
                'outstanding_principal' => (float)($l->installments()->where('status', 'PENDING')->sum('principal_amount') ?? 0),
            ]);

        $activeLoansCount = Loan::whereIn('status', ['DISBURSED', 'ACTIVE'])->count();
        $totalActiveLoans = (float)(Loan::whereIn('status', ['DISBURSED', 'ACTIVE'])->sum('loan_amount') ?? 0);

        return Inertia::render('AdminLoansListPage', [
            'loans' => $loans,
            'summary' => ['totalActiveLoans' => $totalActiveLoans, 'activeLoansCount' => $activeLoansCount, 'overdueLoansCount' => 0],
            'pagination' => ['current_page' => (int)$page, 'total_pages' => (int)ceil($total / $limit), 'total_records' => $total],
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function units()
    {
        $branches = DB::table('units')->where('unit_type', 'KANTOR_CABANG')->where('status', 'ACTIVE')->get();
        $allUnits = DB::table('units')->where('status', 'ACTIVE')->get();
        $grouped = $branches->map(function($branch) use ($allUnits) {
            $branch->units = $allUnits->where('unit_type', '!=', 'KANTOR_CABANG')->filter(fn($u) => str_starts_with($u->unit_code, explode('-', $branch->unit_code)[0] . '-'))->values();
            return $branch;
        });
        return Inertia::render('AdminUnitsPage', ['branches' => $grouped]);
    }

    public function staff(Request $request)
    {
        $staffList = User::where('role_id', '!=', 9)->with('customerProfile')->get()
            ->map(fn($s) => [
                'id' => $s->id, 'full_name' => $s->full_name, 'email' => $s->email,
                'role_name' => DB::table('roles')->where('id', $s->role_id)->value('role_name') ?? 'Staf',
                'role_id' => $s->role_id, 'status' => $s->status, 'unit_id' => $s->customerProfile?->unit_id,
                'branch_name' => '-', 'unit_name' => '-', 'can_edit' => true,
            ]);
        $roles = DB::table('roles')->where('id', '!=', 9)->get();
        $branches = DB::table('units')->where('unit_type', 'KANTOR_CABANG')->where('status', 'ACTIVE')->get();
        $allUnits = DB::table('units')->where('status', 'ACTIVE')->get();
        $branchesWithUnits = $branches->map(function($b) use ($allUnits) {
            $b->units = $allUnits->where('unit_type', '!=', 'KANTOR_CABANG')->filter(fn($u) => str_starts_with($u->unit_code ?? '', explode('-', $b->unit_code ?? '')[0] . '-'))->values();
            return $b;
        });

        return Inertia::render('StaffListPage', ['staffList' => $staffList, 'roles' => $roles, 'units' => $branchesWithUnits]);
    }

    public function staffEdit($staffId)
    {
        $staff = User::findOrFail($staffId);
        $roles = DB::table('roles')->where('id', '!=', 9)->get();
        return Inertia::render('StaffEditPage', ['staff' => $staff, 'roles' => $roles]);
    }

    public function cardRequests()
    {
        $requests = CardRequest::with('user')->orderBy('created_at', 'desc')->get()
            ->map(fn($r) => [
                'id' => $r->id, 'customer_name' => $r->user?->full_name,
                'account_number' => $r->account_number ?? '-', 'requested_at' => $r->created_at, 'status' => $r->status,
            ]);
        return Inertia::render('CardRequestsPage', ['requests' => $requests]);
    }

    public function topupRequests(Request $request)
    {
        $status = $request->input('status', 'PENDING');
        $requests = DB::table('topup_requests as tr')
            ->join('users as u', 'tr.user_id', '=', 'u.id')
            ->select(['tr.*', 'u.full_name as customer_name'])
            ->where('tr.status', $status)->orderBy('tr.created_at', 'desc')->get();
        return Inertia::render('AdminTopUpRequestsPage', ['requests' => $requests, 'filters' => ['status' => $status]]);
    }

    public function withdrawalRequests(Request $request)
    {
        $status = $request->input('status', 'PENDING');
        $requests = DB::table('withdrawal_requests as wr')
            ->join('users as u', 'wr.user_id', '=', 'u.id')
            ->leftJoin('withdrawal_accounts as wa', 'wr.withdrawal_account_id', '=', 'wa.id')
            ->select(['wr.*', 'u.full_name as customer_name', 'wa.bank_name', 'wa.account_number as dest_account_number', 'wa.account_name'])
            ->where('wr.status', $status)->orderBy('wr.created_at', 'desc')->get();
        return Inertia::render('AdminWithdrawalRequestsPage', ['requests' => $requests, 'filters' => ['status' => $status]]);
    }

    public function transactions(Request $request)
    {
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

        if ($search) $query->where(function($q) use ($search) { $q->where('t.transaction_code', 'like', "%{$search}%")->orWhere('t.description', 'like', "%{$search}%"); });
        if ($type) $query->where('t.transaction_type', $type);

        $total = $query->count();
        $transactions = $query->orderBy('t.created_at', 'desc')->skip(($page - 1) * $limit)->take($limit)->get();

        return Inertia::render('TransactionListPage', [
            'transactions' => $transactions,
            'pagination' => ['current_page' => (int)$page, 'total_pages' => (int)ceil($total / $limit)],
            'filters' => ['search' => $search, 'type' => $type],
        ]);
    }

    public function reports() { return Inertia::render('ReportsPage'); }
    public function settings() { return Inertia::render('SettingsPage'); }
    public function auditLog() { return Inertia::render('AdminAuditLogPage'); }
    public function tellerDeposit() { return Inertia::render('AdminTellerDepositPage'); }
    public function tellerLoanPayment() { return Inertia::render('AdminTellerLoanPaymentPage'); }
    public function notifications() {
        $notifications = Notification::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return Inertia::render('AdminNotificationsPage', ['notifications' => $notifications]);
    }
    public function depositsAccounts(Request $request) {
        $deposits = Account::where('account_type', 'DEPOSITO')->with(['user', 'depositProduct'])->get()
            ->map(fn($a) => ['id' => $a->id, 'customer_name' => $a->user?->full_name, 'account_number' => $a->account_number, 'product_name' => $a->depositProduct?->product_name, 'balance' => (float)$a->balance, 'maturity_date' => $a->maturity_date, 'status' => $a->status]);
        return Inertia::render('AdminDepositsListPage', ['deposits' => $deposits]);
    }
    public function printReceipt($transactionId) { return Inertia::render('PrintableReceiptPage', ['routeParams' => ['transactionId' => $transactionId]]); }
    public function build() { return Inertia::render('AdminBuildPage'); }
}
