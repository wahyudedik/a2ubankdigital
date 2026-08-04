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
use App\Models\Role;
use App\Traits\UnitAccessTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminPageController extends Controller
{
    use UnitAccessTrait;

    public function dashboard()
    {
        $user = Auth::user();
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        // Base query scopes for customer-related data
        $customerUserQuery = function ($query) use ($accessibleUnitIds) {
            if ($accessibleUnitIds !== null) {
                $query->whereHas('customerProfile', fn($q) => $q->whereIn('unit_id', $accessibleUnitIds));
            }
        };

        $accountQuery = function ($query) use ($accessibleUnitIds) {
            if ($accessibleUnitIds !== null) {
                $query->whereHas('user', function ($uq) use ($accessibleUnitIds) {
                    $uq->whereHas('customerProfile', fn($q) => $q->whereIn('unit_id', $accessibleUnitIds));
                });
            }
        };

        // Total customer funds
        $accountModel = Account::where('status', 'ACTIVE');
        if ($accessibleUnitIds !== null) {
            $accountModel->whereHas('user', function ($q) use ($accessibleUnitIds) {
                $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds));
            });
        }
        $totalCustomerFunds = (float) ($accountModel->sum('balance') ?? 0);

        // Outstanding loan portfolio
        $loanQuery = Loan::whereIn('status', ['DISBURSED', 'ACTIVE']);
        if ($accessibleUnitIds !== null) {
            $loanQuery->whereHas('user', function ($q) use ($accessibleUnitIds) {
                $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds));
            });
        }
        $outstandingLoanPortfolio = (float) ($loanQuery->sum('loan_amount') ?? 0);

        // Fee revenue monthly
        $feeQuery = Transaction::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'SUCCESS');
        if ($accessibleUnitIds !== null) {
            $feeQuery->where(function ($q) use ($accessibleUnitIds) {
                $q->whereHas('fromAccount.user', fn($uq) => $uq->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)))
                  ->orWhereHas('toAccount.user', fn($uq) => $uq->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)));
            });
        }
        $feeRevenueMonthly = (float) ($feeQuery->sum('fee') ?? 0);

        // New customers monthly
        $newCustomerQuery = User::where('role_id', 9)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
        if ($accessibleUnitIds !== null) {
            $newCustomerQuery->whereHas('customerProfile', fn($q) => $q->whereIn('unit_id', $accessibleUnitIds));
        }
        $newCustomersMonthly = $newCustomerQuery->count();

        // Pending tasks
        $pendingTopupsQuery = DB::table('topup_requests as tr')
            ->join('users as u', 'tr.user_id', '=', 'u.id');
        $pendingWithdrawalsQuery = DB::table('withdrawal_requests as wr')
            ->join('users as u', 'wr.user_id', '=', 'u.id');
        $pendingLoansQuery = Loan::where('status', 'SUBMITTED');
        $pendingLoanDisbursementsQuery = Loan::where('status', 'APPROVED');
        $pendingWithdrawalDisbursementsQuery = DB::table('withdrawal_requests as wr2')
            ->join('users as u2', 'wr2.user_id', '=', 'u2.id');

        if ($accessibleUnitIds !== null) {
            $pendingTopupsQuery->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->whereIn('cp.unit_id', $accessibleUnitIds);
            $pendingWithdrawalsQuery->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->whereIn('cp.unit_id', $accessibleUnitIds);
            $pendingLoansQuery->whereHas('user', fn($q) => $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)));
            $pendingLoanDisbursementsQuery->whereHas('user', fn($q) => $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)));
            $pendingWithdrawalDisbursementsQuery->join('customer_profiles as cp2', 'u2.id', '=', 'cp2.user_id')
                ->whereIn('cp2.unit_id', $accessibleUnitIds);
        }

        $pendingTopups = $pendingTopupsQuery->where('tr.status', 'pending')->count();
        $pendingWithdrawals = $pendingWithdrawalsQuery->where('wr.status', 'pending')->count();
        $pendingLoans = $pendingLoansQuery->count();
        $pendingLoanDisbursements = $pendingLoanDisbursementsQuery->count();
        $pendingWithdrawalDisbursements = $pendingWithdrawalDisbursementsQuery->where('wr2.status', 'approved')->count();

        // Recent activities
        $recentQuery = DB::table('transactions as t')
            ->leftJoin('accounts as from_acc', 't.from_account_id', '=', 'from_acc.id')
            ->leftJoin('users as from_user', 'from_acc.user_id', '=', 'from_user.id')
            ->leftJoin('accounts as to_acc', 't.to_account_id', '=', 'to_acc.id')
            ->leftJoin('users as to_user', 'to_acc.user_id', '=', 'to_user.id')
            ->select(['t.id', 't.transaction_code', 't.transaction_type', 't.amount', 't.description', 't.status', 't.created_at', DB::raw('COALESCE(from_user.full_name, to_user.full_name, "System") as full_name')])
            ->where('t.status', 'SUCCESS');

        if ($accessibleUnitIds !== null) {
            $recentQuery->where(function ($q) use ($accessibleUnitIds) {
                $q->whereExists(function ($subQ) use ($accessibleUnitIds) {
                    $subQ->select(DB::raw(1))
                        ->from('accounts as fa2')
                        ->join('customer_profiles as cp_from', 'fa2.user_id', '=', 'cp_from.user_id')
                        ->whereColumn('fa2.id', 't.from_account_id')
                        ->whereIn('cp_from.unit_id', $accessibleUnitIds);
                })->orWhereExists(function ($subQ) use ($accessibleUnitIds) {
                    $subQ->select(DB::raw(1))
                        ->from('accounts as ta2')
                        ->join('customer_profiles as cp_to', 'ta2.user_id', '=', 'cp_to.user_id')
                        ->whereColumn('ta2.id', 't.to_account_id')
                        ->whereIn('cp_to.unit_id', $accessibleUnitIds);
                });
            });
        }

        $recentActivities = $recentQuery->orderBy('t.created_at', 'desc')->limit(10)->get();

        // Customer growth
        $growthQuery = User::where('role_id', 9)->where('created_at', '>=', now()->subDays(30));
        if ($accessibleUnitIds !== null) {
            $growthQuery->whereHas('customerProfile', fn($q) => $q->whereIn('unit_id', $accessibleUnitIds));
        }
        $customerGrowth = $growthQuery
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
        $user = Auth::user();
        $page = $request->input('page', 1);
        $search = $request->input('search', '');
        $limit = 10;

        $query = User::where('role_id', 9);

        // Unit-based filtering
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);
        if ($accessibleUnitIds !== null) {
            $query->whereHas('customerProfile', fn($q) => $q->whereIn('unit_id', $accessibleUnitIds));
        } elseif ($accessibleUnitIds !== null && empty($accessibleUnitIds)) {
            $query->whereRaw('1 = 0');
        }

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
        $user = Auth::user();

        // Unit access check
        if (!$this->canAccessCustomer($user, $customerId)) {
            abort(403, 'Anda tidak memiliki akses ke data nasabah ini.');
        }

        $customer = User::with(['customerProfile.unit', 'accounts.depositProduct'])->where('role_id', 9)->findOrFail($customerId);
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
                'kyc_status' => $profile?->kyc_status ?? 'PENDING', 'kyc_notes' => $profile?->kyc_notes,
                'branch_name' => $profile?->unit?->unit_name ?? '-',
                'unit_name' => $profile?->unit?->unit_name ?? '-',
                'accounts' => $customer->accounts->map(fn($a) => [
                    'id' => $a->id, 'account_number' => $a->account_number, 'balance' => (float)$a->balance,
                    'account_type' => $a->account_type, 'status' => $a->status,
                    'deposit_product_name' => $a->depositProduct?->product_name,
                    'interest_earned' => $a->account_type === 'DEPOSITO' ? $this->calculateDepositInterest($a) : 0,
                    'maturity_date' => $a->maturity_date,
                ]),
                'loans' => $loans->map(fn($l) => [
                    'id' => $l->id, 'product_name' => $l->loanProduct?->product_name, 'loan_amount' => (float)$l->loan_amount,
                    'tenor' => $l->tenor, 'tenor_unit' => $l->tenor_unit, 'status' => $l->status,
                    'installments' => $l->installments->map(fn($i) => [
                        'id' => $i->id, 'installment_number' => $i->installment_number, 'due_date' => $i->due_date,
                        'amount_due' => (float)$i->total_amount,
                        'penalty_amount' => (float)$i->late_fee,
                        'status' => $i->status,
                    ]),
                ]),
            ],
        ]);
    }

    private function calculateDepositInterest($account)
    {
        if (!$account->depositProduct || !$account->created_at) {
            return 0;
        }

        $principal = (float)$account->balance;
        $annualRate = (float)$account->depositProduct->interest_rate_pa / 100;
        $daysElapsed = (int) \Carbon\Carbon::parse($account->created_at)->startOfDay()->diffInDays(now()->startOfDay(), true);

        // Simple interest calculation: Principal * Rate * (Days/365)
        $interest = $principal * $annualRate * ($daysElapsed / 365);

        return round($interest, 2);
    }

    public function customerAdd()
    {
        $user = Auth::user();
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        if ($accessibleUnitIds !== null) {
            $units = DB::table('units')->whereIn('id', $accessibleUnitIds)->where('status', 'ACTIVE')->get()->groupBy('unit_type');
            $branches = DB::table('units')->where('unit_type', 'KANTOR_CABANG')->whereIn('id', $accessibleUnitIds)->where('status', 'ACTIVE')->get();
            $allUnits = DB::table('units')->whereIn('id', $accessibleUnitIds)->where('status', 'ACTIVE')->get();
        } else {
            $units = DB::table('units')->where('status', 'ACTIVE')->get()->groupBy('unit_type');
            $branches = DB::table('units')->where('unit_type', 'KANTOR_CABANG')->where('status', 'ACTIVE')->get();
            $allUnits = DB::table('units')->where('status', 'ACTIVE')->get();
        }

        return Inertia::render('CustomerAddPage', ['units' => $branches, 'allUnits' => $allUnits]);
    }

    public function customerEdit($customerId)
    {
        $user = Auth::user();

        // Unit access check
        if (!$this->canAccessCustomer($user, $customerId)) {
            abort(403, 'Anda tidak memiliki akses ke data nasabah ini.');
        }

        $customer = User::with('customerProfile')->where('role_id', 9)->findOrFail($customerId);

        $accessibleUnitIds = $this->getAccessibleUnitIds($user);
        if ($accessibleUnitIds !== null) {
            $units = DB::table('units')->whereIn('id', $accessibleUnitIds)->where('status', 'ACTIVE')->get();
            $branches = DB::table('units')->where('unit_type', 'KANTOR_CABANG')->whereIn('id', $accessibleUnitIds)->where('status', 'ACTIVE')->get();
        } else {
            $units = DB::table('units')->where('status', 'ACTIVE')->get();
            $branches = DB::table('units')->where('unit_type', 'KANTOR_CABANG')->where('status', 'ACTIVE')->get();
        }

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
        $user = Auth::user();
        $status = $request->input('status', 'SUBMITTED');
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        $query = Loan::with(['user', 'loanProduct'])->where('status', $status);

        // Unit-based filtering
        if ($accessibleUnitIds !== null) {
            $query->whereHas('user', fn($q) => $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)));
        }

        $loans = $query->orderBy('created_at', 'desc')->get()
            ->map(fn($l) => [
                'id' => $l->id, 'customer_name' => $l->user?->full_name, 'product_name' => $l->loanProduct?->product_name,
                'loan_amount' => (float)$l->loan_amount, 'tenor' => $l->tenor, 'tenor_unit' => $l->tenor_unit,
                'application_date' => $l->created_at, 'status' => $l->status,
            ]);
        return Inertia::render('LoanApplicationsPage', ['loans' => $loans, 'filters' => ['status' => $status]]);
    }

    public function loanApplicationDetail($loanId)
    {
        $user = Auth::user();
        $loan = Loan::with(['user', 'loanProduct', 'installments'])->findOrFail($loanId);

        // Unit access check
        if (!$this->canAccessCustomer($user, $loan->user_id)) {
            abort(403, 'Anda tidak memiliki akses ke data pinjaman ini.');
        }

        return Inertia::render('LoanApplicationDetailPage', [
            'loan' => [
                'id' => $loan->id, 'customer_name' => $loan->user?->full_name, 'email' => $loan->user?->email,
                'phone_number' => $loan->user?->phone_number, 'product_name' => $loan->loanProduct?->product_name,
                'loan_amount' => (float)$loan->loan_amount, 'tenor' => $loan->tenor, 'tenor_unit' => $loan->tenor_unit,
                'monthly_installment' => (float)$loan->monthly_installment,
                'total_interest' => (float)$loan->total_interest,
                'total_repayment' => (float)$loan->total_repayment,
                'purpose' => $loan->purpose,
                'status' => $loan->status, 'application_date' => $loan->created_at,
                'rejection_reason' => $loan->rejection_reason,
                'installments' => $loan->installments->map(fn($i) => [
                    'id' => $i->id, 'installment_number' => $i->installment_number, 'due_date' => $i->due_date,
                    'amount_due' => (float)$i->total_amount, 'principal_amount' => (float)$i->principal_amount,
                    'interest_amount' => (float)$i->interest_amount,
                    'penalty_amount' => (float)$i->late_fee,
                    'status' => $i->status,
                ]),
            ],
        ]);
    }

    public function loanAccounts(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $page = $request->input('page', 1);
        $limit = 15;
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        $query = Loan::with(['user', 'loanProduct'])->whereIn('status', ['DISBURSED', 'ACTIVE', 'COMPLETED', 'CLOSED', 'DEFAULTED']);

        // Unit-based filtering
        if ($accessibleUnitIds !== null) {
            $query->whereHas('user', fn($q) => $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)));
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('full_name', 'like', "%{$search}%"))
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $statusLower = strtolower($status);
            if ($statusLower === 'overdue') {
                $query->whereHas('installments', fn($q) => $q->where('status', 'OVERDUE'));
            } elseif ($statusLower === 'completed') {
                $query->where(function($q) {
                    $q->whereIn('status', ['COMPLETED', 'CLOSED'])
                      ->orWhere(function($subQ) {
                          $subQ->whereIn('status', ['DISBURSED', 'ACTIVE'])
                               ->whereDoesntHave('installments', fn($instQ) => $instQ->whereIn('status', ['PENDING', 'OVERDUE']));
                      });
                });
            } elseif ($statusLower === 'disbursed' || $statusLower === 'active') {
                $query->whereIn('status', ['DISBURSED', 'ACTIVE'])
                      ->where(function($q) {
                          $q->whereDoesntHave('installments')
                            ->orWhere(function($sub) {
                                $sub->whereHas('installments', fn($instQ) => $instQ->where('status', 'PENDING'))
                                    ->whereDoesntHave('installments', fn($instQ) => $instQ->where('status', 'OVERDUE'));
                            });
                      });
            } else {
                $query->where('status', strtoupper($status));
            }
        }

        $total = $query->count();
        $loans = $query->orderBy('created_at', 'desc')->skip(($page - 1) * $limit)->take($limit)->get()
            ->map(fn($l) => [
                'id' => $l->id, 'customer_name' => $l->user?->full_name, 'product_name' => $l->loanProduct?->product_name,
                'loan_amount' => (float)$l->loan_amount, 'status' => $l->status,
                'outstanding_principal' => (float)($l->installments()->whereIn('status', ['PENDING', 'OVERDUE'])->sum('principal_amount') ?? 0),
                'next_due_date' => $l->installments()->whereIn('status', ['PENDING', 'OVERDUE'])->orderBy('due_date')->value('due_date'),
                'overdue_installments_count' => $l->installments()->where('status', 'OVERDUE')->count(),
            ]);

        // Summary queries with unit filtering
        $activeLoansQuery = Loan::whereIn('status', ['DISBURSED', 'ACTIVE'])
            ->whereHas('installments', fn($q) => $q->whereIn('status', ['PENDING', 'OVERDUE']));
        if ($accessibleUnitIds !== null) {
            $activeLoansQuery->whereHas('user', fn($q) => $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)));
        }
        $activeLoansCount = $activeLoansQuery->count();
        $totalActiveLoans = (float)($activeLoansQuery->sum('loan_amount') ?? 0);

        $overdueQuery = Loan::whereHas('installments', fn($q) => $q->where('status', 'OVERDUE'));
        if ($accessibleUnitIds !== null) {
            $overdueQuery->whereHas('user', fn($q) => $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)));
        }
        $overdueLoansCount = $overdueQuery->count();

        return Inertia::render('AdminLoansListPage', [
            'loans' => $loans,
            'summary' => ['totalActiveLoans' => $totalActiveLoans, 'activeLoansCount' => $activeLoansCount, 'overdueLoansCount' => $overdueLoansCount],
            'pagination' => ['current_page' => (int)$page, 'total_pages' => (int)ceil($total / $limit), 'total_records' => $total],
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function units()
    {
        $user = Auth::user();
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        if ($accessibleUnitIds !== null) {
            // Filter branches by accessible units
            $branches = DB::table('units')
                ->where('unit_type', 'KANTOR_CABANG')
                ->whereIn('id', $accessibleUnitIds)
                ->get();

            $grouped = $branches->map(function($branch) use ($accessibleUnitIds) {
                $branch->units = DB::table('units')
                    ->where('parent_id', $branch->id)
                    ->whereIn('id', $accessibleUnitIds)
                    ->get();
                return $branch;
            });
        } else {
            $branches = DB::table('units')
                ->where('unit_type', 'KANTOR_CABANG')
                ->get();

            $grouped = $branches->map(function($branch) {
                $branch->units = DB::table('units')
                    ->where('parent_id', $branch->id)
                    ->get();
                return $branch;
            });
        }

        return Inertia::render('AdminUnitsPage', ['branches' => $grouped]);
    }

    public function staff(Request $request)
    {
        $user = Auth::user();
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        $staffQuery = User::where('role_id', '!=', 9)->with('customerProfile');

        // Unit-based filtering for staff
        if ($accessibleUnitIds !== null) {
            $staffQuery->where(function ($q) use ($accessibleUnitIds) {
                // Staff whose unit_id is in accessible units
                $q->whereIn('unit_id', $accessibleUnitIds)
                  // Or staff whose customerProfile unit is accessible (for staff that have customer profiles)
                  ->orWhereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds));
            });
        }

        $staffList = $staffQuery->get()
            ->map(fn($s) => [
                'id' => $s->id, 'full_name' => $s->full_name, 'email' => $s->email,
                'role_name' => DB::table('roles')->where('id', $s->role_id)->value('role_name') ?? 'Staf',
                'role_id' => $s->role_id, 'status' => $s->status, 'unit_id' => $s->unit_id ?? $s->customerProfile?->unit_id,
                'branch_name' => '-', 'unit_name' => '-', 'can_edit' => true,
            ]);

        $roles = DB::table('roles')->where('id', '!=', 9)->get();

        if ($accessibleUnitIds !== null) {
            $branches = DB::table('units')->where('unit_type', 'KANTOR_CABANG')->whereIn('id', $accessibleUnitIds)->where('status', 'ACTIVE')->get();
            $allUnits = DB::table('units')->whereIn('id', $accessibleUnitIds)->where('status', 'ACTIVE')->get();
        } else {
            $branches = DB::table('units')->where('unit_type', 'KANTOR_CABANG')->where('status', 'ACTIVE')->get();
            $allUnits = DB::table('units')->where('status', 'ACTIVE')->get();
        }

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
        $user = Auth::user();
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        $query = CardRequest::with('user');

        // Unit-based filtering
        if ($accessibleUnitIds !== null) {
            $query->whereHas('user', fn($q) => $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)));
        }

        $requests = $query->orderBy('created_at', 'desc')->get()
            ->map(fn($r) => [
                'id' => $r->id, 'customer_name' => $r->user?->full_name,
                'account_number' => $r->account_number ?? '-', 'requested_at' => $r->created_at, 'status' => $r->status,
            ]);
        return Inertia::render('CardRequestsPage', ['requests' => $requests]);
    }

    public function topupRequests(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status', 'pending');
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        $query = DB::table('topup_requests as tr')
            ->join('users as u', 'tr.user_id', '=', 'u.id')
            ->select(['tr.*', 'u.full_name as customer_name'])
            ->where('tr.status', $status);

        // Unit-based filtering
        if ($accessibleUnitIds !== null) {
            $query->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->whereIn('cp.unit_id', $accessibleUnitIds);
        }

        $requests = $query->orderBy('tr.created_at', 'desc')->get();
        return Inertia::render('AdminTopUpRequestsPage', ['requests' => $requests, 'filters' => ['status' => $status]]);
    }

    public function withdrawalRequests(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status', 'pending');
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        $query = DB::table('withdrawal_requests as wr')
            ->join('users as u', 'wr.user_id', '=', 'u.id')
            ->leftJoin('withdrawal_accounts as wa', 'wr.withdrawal_account_id', '=', 'wa.id')
            ->select([
                'wr.id', 'wr.amount', 'wr.status', 'wr.created_at', 'wr.processed_at',
                'u.full_name as customer_name',
                'wa.bank_name',
                'wa.account_number',
                'wa.account_name'
            ])
            ->where('wr.status', $status);

        // Unit-based filtering
        if ($accessibleUnitIds !== null) {
            $query->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->whereIn('cp.unit_id', $accessibleUnitIds);
        }

        $requests = $query->orderBy('wr.created_at', 'desc')->get();

        return Inertia::render('AdminWithdrawalRequestsPage', ['requests' => $requests, 'filters' => ['status' => $status]]);
    }

    public function transactions(Request $request)
    {
        $user = Auth::user();
        $page = $request->input('page', 1);
        $search = $request->input('search', '');
        $type = $request->input('type', '');
        $limit = 15;
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        $query = DB::table('transactions as t')
            ->leftJoin('accounts as fa', 't.from_account_id', '=', 'fa.id')
            ->leftJoin('users as fu', 'fa.user_id', '=', 'fu.id')
            ->leftJoin('accounts as ta', 't.to_account_id', '=', 'ta.id')
            ->leftJoin('users as tu', 'ta.user_id', '=', 'tu.id')
            ->select(['t.*', DB::raw('fu.full_name as from_name'), DB::raw('tu.full_name as to_name')]);

        // Unit-based filtering
        if ($accessibleUnitIds !== null) {
            $query->leftJoin('customer_profiles as cpf', 'fu.id', '=', 'cpf.user_id')
                ->leftJoin('customer_profiles as cpt', 'tu.id', '=', 'cpt.user_id')
                ->where(function ($q) use ($accessibleUnitIds) {
                    $q->whereIn('cpf.unit_id', $accessibleUnitIds)
                      ->orWhereIn('cpt.unit_id', $accessibleUnitIds);
                });
        }

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
        $user = Auth::user();
        $search = $request->input('search', '');
        $status = $request->input('status', 'active');
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        $query = Account::where('account_type', 'DEPOSITO')
            ->with(['user', 'depositProduct']);

        // Unit-based filtering
        if ($accessibleUnitIds !== null) {
            $query->whereHas('user', fn($q) => $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)));
        }

        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('full_name', 'like', "%{$search}%");
                })->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($status === 'active') {
            $query->where('status', 'ACTIVE');
        } elseif ($status === 'matured') {
            $query->where('status', 'ACTIVE')
                  ->where('maturity_date', '<=', now());
        } elseif ($status === 'near_maturity') {
            $query->where('status', 'ACTIVE')
                  ->whereBetween('maturity_date', [now(), now()->addDays(30)]);
        }

        $deposits = $query->get()->map(function($a) {
            // Calculate interest earned
            $principal = $a->balance;
            $interestRate = $a->depositProduct?->interest_rate_pa ?? 0;
            $months = $a->depositProduct?->tenor_months ?? 0;
            $interestEarned = $principal * ($interestRate / 100) * ($months / 12);

            // Check if near maturity (within 30 days)
            $isNearMaturity = $a->maturity_date &&
                              $a->maturity_date->between(now(), now()->addDays(30));

            return [
                'id' => $a->id,
                'customer_name' => $a->user?->full_name,
                'account_number' => $a->account_number,
                'product_name' => $a->depositProduct?->product_name,
                'balance' => (float)$a->balance,
                'principal' => (float)$principal,
                'interest_earned' => (float)$interestEarned,
                'maturity_date' => $a->maturity_date,
                'status' => $a->status,
                'is_near_maturity' => $isNearMaturity
            ];
        });

        // Calculate summary statistics (with unit filtering)
        $allActiveDepositsQuery = Account::where('account_type', 'DEPOSITO')
            ->where('status', 'ACTIVE');
        if ($accessibleUnitIds !== null) {
            $allActiveDepositsQuery->whereHas('user', fn($q) => $q->whereHas('customerProfile', fn($cpq) => $cpq->whereIn('unit_id', $accessibleUnitIds)));
        }
        $allActiveDeposits = $allActiveDepositsQuery->get();

        $summary = [
            'totalActiveBalance' => $allActiveDeposits->sum('balance'),
            'totalDeposits' => $allActiveDeposits->count(),
            'maturingThisMonth' => $allActiveDeposits->filter(function($a) {
                return $a->maturity_date &&
                       $a->maturity_date->between(now()->startOfMonth(), now()->endOfMonth());
            })->count()
        ];

        return Inertia::render('AdminDepositsListPage', [
            'deposits' => $deposits,
            'summary' => $summary
        ]);
    }

    public function printReceipt($transactionId) { return Inertia::render('PrintableReceiptPage', ['routeParams' => ['transactionId' => $transactionId]]); }
    public function build() { return Inertia::render('AdminBuildPage'); }
}
