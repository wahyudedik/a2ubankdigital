<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\DepositProduct;
use App\Models\Notification;
use App\Models\Card;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserPageController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $account = $user->accounts()->where('account_type', 'TABUNGAN')->where('status', 'ACTIVE')->first();

        if (!$account) {
            return Inertia::render('DashboardPage', ['dashboardData' => ['balance' => 0, 'account_number' => '-', 'recent_transactions' => [], 'weekly_summary' => ['labels' => [], 'pemasukan' => [], 'pengeluaran' => []]]]);
        }

        $transactions = DB::table('transactions as t')
            ->leftJoin('accounts as from_acc', 't.from_account_id', '=', 'from_acc.id')
            ->leftJoin('users as from_user', 'from_acc.user_id', '=', 'from_user.id')
            ->select('t.transaction_code', 't.transaction_type', 't.amount',
                DB::raw("CASE WHEN t.to_account_id = " . intval($account->id) . " AND t.transaction_type = 'TRANSFER_INTERNAL' THEN CONCAT('Transfer dari ', from_user.full_name) ELSE t.description END as description"),
                't.created_at', DB::raw("IF(t.to_account_id = " . intval($account->id) . ", 'KREDIT', 'DEBIT') as flow"))
            ->where(fn($q) => $q->where('t.from_account_id', $account->id)->orWhere('t.to_account_id', $account->id))
            ->orderBy('t.created_at', 'desc')->limit(5)->get();

        // Weekly summary
        $weeklyData = DB::table('transactions')
            ->select(DB::raw('DATE(created_at) as d'), DB::raw("SUM(CASE WHEN to_account_id = " . intval($account->id) . " THEN amount ELSE 0 END) as inc"), DB::raw("SUM(CASE WHEN from_account_id = " . intval($account->id) . " THEN amount ELSE 0 END) as exp"))
            ->where(fn($q) => $q->where('from_account_id', $account->id)->orWhere('to_account_id', $account->id))
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())->where('status', 'SUCCESS')
            ->groupBy('d')->orderBy('d')->get()->keyBy('d');

        $labels = []; $pemasukan = []; $pengeluaran = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('D');
            $pemasukan[] = (float)($weeklyData[$date]->inc ?? 0);
            $pengeluaran[] = (float)($weeklyData[$date]->exp ?? 0);
        }

        return Inertia::render('DashboardPage', [
            'dashboardData' => [
                'balance' => (float)$account->balance, 'account_number' => $account->account_number,
                'recent_transactions' => $transactions,
                'weekly_summary' => compact('labels', 'pemasukan', 'pengeluaran'),
            ],
        ]);
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $account = $user->accounts()->where('account_type', 'TABUNGAN')->first();
        $page = $request->input('page', 1);
        $limit = 15;

        $query = DB::table('transactions as t')
            ->where(fn($q) => $q->where('t.from_account_id', $account?->id)->orWhere('t.to_account_id', $account?->id))
            ->select('t.*', DB::raw("IF(t.to_account_id = " . intval($account?->id ?? 0) . ", 'KREDIT', 'DEBIT') as flow"));

        if ($request->type) $query->where('t.transaction_type', $request->type);
        if ($request->start_date) $query->whereDate('t.created_at', '>=', $request->start_date);
        if ($request->end_date) $query->whereDate('t.created_at', '<=', $request->end_date);

        $total = $query->count();
        $transactions = $query->orderBy('t.created_at', 'desc')->skip(($page - 1) * $limit)->take($limit)->get();

        return Inertia::render('HistoryPage', [
            'transactions' => $transactions,
            'pagination' => ['current_page' => (int)$page, 'total_pages' => (int)ceil($total / $limit), 'has_more' => $page < ceil($total / $limit)],
            'filters' => $request->only(['type', 'start_date', 'end_date']),
        ]);
    }

    public function profile()
    {
        return Inertia::render('ProfilePage');
    }

    public function profileInfo()
    {
        $user = Auth::user()->load('customerProfile');
        return Inertia::render('ProfileInfoPage', [
            'profile' => [
                'full_name' => $user->full_name, 'email' => $user->email, 'phone_number' => $user->phone_number,
                'nik' => $user->customerProfile?->nik, 'mother_maiden_name' => $user->customerProfile?->mother_maiden_name,
                'dob' => $user->customerProfile?->dob, 'address_domicile' => $user->customerProfile?->address_domicile,
                'occupation' => $user->customerProfile?->occupation,
            ],
        ]);
    }

    public function myLoans()
    {
        $loans = Loan::where('user_id', Auth::id())->with('loanProduct')->orderBy('created_at', 'desc')->get()
            ->map(fn($l) => ['id' => $l->id, 'product_name' => $l->loanProduct?->product_name, 'loan_amount' => (float)$l->loan_amount, 'status' => $l->status, 'disbursement_date' => $l->disbursed_at]);
        return Inertia::render('MyLoansPage', ['loans' => $loans]);
    }

    public function myLoanDetail($loanId)
    {
        $loan = Loan::where('user_id', Auth::id())->with(['loanProduct', 'installments'])->findOrFail($loanId);
        return Inertia::render('MyLoanDetailPage', [
            'loan' => [
                'id' => $loan->id, 'product_name' => $loan->loanProduct?->product_name, 
                'loan_amount' => (float)$loan->loan_amount,
                'monthly_installment' => (float)$loan->monthly_installment,
                'total_interest' => (float)$loan->total_interest,
                'total_repayment' => (float)$loan->total_repayment,
                'tenor' => $loan->tenor, 'tenor_unit' => $loan->tenor_unit, 'status' => $loan->status,
                'disbursed_at' => $loan->disbursed_at,
                'installments' => $loan->installments->map(fn($i) => [
                    'id' => $i->id, 'installment_number' => $i->installment_number, 'due_date' => $i->due_date,
                    'amount_due' => (float)$i->total_amount, 
                    'penalty_amount' => (float)$i->late_fee, 
                    'status' => $i->status,
                    'paid_at' => $i->paid_at,
                ]),
            ],
        ]);
    }

    public function loanProducts()
    {
        $products = LoanProduct::where('is_active', true)->get();
        return Inertia::render('LoanProductsListPage', ['products' => $products]);
    }

    public function loanApplication($productId)
    {
        $product = LoanProduct::findOrFail($productId);
        return Inertia::render('LoanApplicationPage', ['product' => $product]);
    }

    public function deposits()
    {
        $deposits = Account::where('user_id', Auth::id())->where('account_type', 'DEPOSITO')->with('depositProduct')->get()
            ->map(fn($a) => ['id' => $a->id, 'product_name' => $a->depositProduct?->product_name, 'balance' => (float)$a->balance, 'maturity_date' => $a->maturity_date, 'status' => $a->status]);
        return Inertia::render('DepositsPage', ['deposits' => $deposits]);
    }

    public function depositDetail($depositId)
    {
        $deposit = Account::where('user_id', Auth::id())->where('account_type', 'DEPOSITO')->with('depositProduct')->findOrFail($depositId);
        return Inertia::render('DepositDetailPage', ['deposit' => $deposit]);
    }

    public function openDeposit()
    {
        $products = DepositProduct::where('is_active', true)->get();
        return Inertia::render('OpenDepositPage', ['products' => $products]);
    }

    public function cards()
    {
        $cards = Card::where('user_id', Auth::id())->get();
        $accounts = Account::where('user_id', Auth::id())->where('account_type', 'TABUNGAN')->where('status', 'ACTIVE')->get();
        return Inertia::render('CardsPage', ['cards' => $cards, 'accounts' => $accounts]);
    }

    public function notifications()
    {
        $notifications = Notification::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return Inertia::render('NotificationsPage', ['notifications' => $notifications]);
    }

    public function transfer() { return Inertia::render('TransferPage'); }
    public function payment() { return Inertia::render('PaymentPage'); }
    public function billPayment() { return Inertia::render('BillPaymentPage'); }
    public function topup() { return Inertia::render('TopUpPage'); }
    public function withdrawal()
    {
        $accounts = DB::table('withdrawal_accounts')->where('user_id', Auth::id())->get();
        return Inertia::render('WithdrawalPage', ['withdrawalAccounts' => $accounts]);
    }
    public function withdrawalAccounts()
    {
        $accounts = DB::table('withdrawal_accounts')->where('user_id', Auth::id())->get();
        return Inertia::render('WithdrawalAccountsPage', ['accounts' => $accounts]);
    }
    public function beneficiaries()
    {
        $beneficiaries = DB::table('beneficiaries')->where('user_id', Auth::id())->get();
        return Inertia::render('BeneficiaryListPage', ['beneficiaries' => $beneficiaries]);
    }
    public function changePassword() { return Inertia::render('ChangePasswordPage'); }
    public function changePin() { return Inertia::render('ChangePinPage'); }
    public function investments() { return Inertia::render('InvestmentPage'); }
    public function scheduledTransfers() { return Inertia::render('ScheduledTransfersPage'); }
    public function standingInstructions() { return Inertia::render('StandingInstructionsPage'); }
    public function tickets() { return Inertia::render('TicketsPage'); }
    public function ticketDetail($id) { return Inertia::render('TicketDetailPage', ['ticketId' => $id]); }
    public function externalTransfer() { return Inertia::render('ExternalTransferPage'); }
    public function faq() { return Inertia::render('FaqPage'); }
    public function announcements() { return Inertia::render('AnnouncementsPage'); }
    public function secureMessages() { return Inertia::render('SecureMessagesPage'); }
    public function digitalProducts() { return Inertia::render('DigitalProductsPage'); }
    public function qrPayment() { return Inertia::render('QrPaymentPage'); }
    public function loyalty() { return Inertia::render('LoyaltyPointsPage'); }
    public function goalSavings() { return Inertia::render('GoalSavingsPage'); }
    public function accountClosure() { return Inertia::render('AccountClosurePage'); }
    public function ewallet() { return Inertia::render('EWalletPage'); }
}
