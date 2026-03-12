<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Loan;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportsController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Get teller performance report
     */
    public function getTellerReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'teller_id' => 'nullable|integer|exists:users,id',
                'unit_id' => 'nullable|integer|exists:units,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

            // Base query for teller transactions
            $query = DB::table('transactions as t')
                ->join('users as teller', 't.processed_by', '=', 'teller.id')
                ->join('users as customer', 't.user_id', '=', 'customer.id')
                ->join('customer_profiles as cp', 'customer.id', '=', 'cp.user_id')
                ->leftJoin('units as u', 'teller.unit_id', '=', 'u.id')
                ->where('t.processed_at', '>=', $startDate)
                ->where('t.processed_at', '<=', $endDate)
                ->whereNotNull('t.processed_by')
                ->whereIn('t.type', ['deposit', 'withdrawal', 'loan_payment']);

            if ($request->teller_id) {
                $query->where('t.processed_by', $request->teller_id);
            }

            if ($request->unit_id) {
                $query->where('teller.unit_id', $request->unit_id);
            }

            // Get detailed transactions
            $transactions = $query->select([
                't.id',
                't.type',
                't.amount',
                't.processed_at',
                'teller.id as teller_id',
                'teller.name as teller_name',
                'customer.email as customer_email',
                'cp.full_name as customer_name',
                'u.name as unit_name'
            ])->get();

            // Calculate summary by teller
            $tellerSummary = $transactions->groupBy('teller_id')->map(function ($tellerTransactions) {
                $teller = $tellerTransactions->first();
                
                return [
                    'teller_id' => $teller->teller_id,
                    'teller_name' => $teller->teller_name,
                    'unit_name' => $teller->unit_name,
                    'total_transactions' => $tellerTransactions->count(),
                    'total_amount' => $tellerTransactions->sum('amount'),
                    'deposits' => [
                        'count' => $tellerTransactions->where('type', 'deposit')->count(),
                        'amount' => $tellerTransactions->where('type', 'deposit')->sum('amount')
                    ],
                    'withdrawals' => [
                        'count' => $tellerTransactions->where('type', 'withdrawal')->count(),
                        'amount' => $tellerTransactions->where('type', 'withdrawal')->sum('amount')
                    ],
                    'loan_payments' => [
                        'count' => $tellerTransactions->where('type', 'loan_payment')->count(),
                        'amount' => $tellerTransactions->where('type', 'loan_payment')->sum('amount')
                    ]
                ];
            })->values();

            // Overall summary
            $overallSummary = [
                'total_tellers' => $tellerSummary->count(),
                'total_transactions' => $transactions->count(),
                'total_amount' => $transactions->sum('amount'),
                'average_per_teller' => $tellerSummary->count() > 0 ? $transactions->sum('amount') / $tellerSummary->count() : 0,
                'by_type' => [
                    'deposits' => [
                        'count' => $transactions->where('type', 'deposit')->count(),
                        'amount' => $transactions->where('type', 'deposit')->sum('amount')
                    ],
                    'withdrawals' => [
                        'count' => $transactions->where('type', 'withdrawal')->count(),
                        'amount' => $transactions->where('type', 'withdrawal')->sum('amount')
                    ],
                    'loan_payments' => [
                        'count' => $transactions->where('type', 'loan_payment')->count(),
                        'amount' => $transactions->where('type', 'loan_payment')->sum('amount')
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString()
                    ],
                    'overall_summary' => $overallSummary,
                    'teller_summary' => $tellerSummary,
                    'detailed_transactions' => $transactions->take(100) // Limit for performance
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('teller_report_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate teller report'
            ], 500);
        }
    }

    /**
     * Get marketing report
     */
    public function getMarketingReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'report_type' => 'nullable|in:acquisition,engagement,retention'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

            // Customer acquisition metrics
            $newCustomers = User::whereBetween('created_at', [$startDate, $endDate])
                ->where('role', 'customer')
                ->count();

            $totalCustomers = User::where('role', 'customer')->count();

            // Account opening metrics
            $newAccounts = Account::whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Transaction engagement metrics
            $activeUsers = DB::table('transactions')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->distinct('user_id')
                ->count();

            $totalTransactions = Transaction::whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $transactionVolume = Transaction::whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');

            // Product adoption metrics
            $productAdoption = [
                'loans' => Loan::whereBetween('created_at', [$startDate, $endDate])->count(),
                'deposits' => DB::table('transactions')
                    ->where('type', 'deposit')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->distinct('user_id')
                    ->count(),
                'digital_payments' => DB::table('transactions')
                    ->whereIn('type', ['qr_payment', 'bill_payment', 'ewallet_topup'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->distinct('user_id')
                    ->count()
            ];

            // Customer segmentation
            $customerSegments = DB::table('users as u')
                ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->leftJoin('accounts as a', 'u.id', '=', 'a.user_id')
                ->where('u.role', 'customer')
                ->select([
                    DB::raw('CASE 
                        WHEN a.balance >= 100000000 THEN "Premium"
                        WHEN a.balance >= 50000000 THEN "Gold"
                        WHEN a.balance >= 10000000 THEN "Silver"
                        ELSE "Basic"
                    END as segment'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('AVG(a.balance) as avg_balance')
                ])
                ->groupBy('segment')
                ->get();

            // Channel usage
            $channelUsage = DB::table('transactions')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select([
                    DB::raw('CASE 
                        WHEN processed_by IS NOT NULL THEN "Teller"
                        WHEN type IN ("qr_payment", "bill_payment") THEN "Mobile"
                        ELSE "Online"
                    END as channel'),
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(amount) as transaction_volume')
                ])
                ->groupBy('channel')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString()
                    ],
                    'acquisition_metrics' => [
                        'new_customers' => $newCustomers,
                        'total_customers' => $totalCustomers,
                        'growth_rate' => $totalCustomers > 0 ? ($newCustomers / $totalCustomers) * 100 : 0,
                        'new_accounts' => $newAccounts
                    ],
                    'engagement_metrics' => [
                        'active_users' => $activeUsers,
                        'total_transactions' => $totalTransactions,
                        'transaction_volume' => $transactionVolume,
                        'avg_transactions_per_user' => $activeUsers > 0 ? $totalTransactions / $activeUsers : 0
                    ],
                    'product_adoption' => $productAdoption,
                    'customer_segments' => $customerSegments,
                    'channel_usage' => $channelUsage
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('marketing_report_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate marketing report'
            ], 500);
        }
    }

    /**
     * Get product performance report
     */
    public function getProductPerformanceReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'product_type' => 'nullable|in:loan,deposit,digital'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

            // Loan products performance
            $loanPerformance = DB::table('loans as l')
                ->join('loan_products as lp', 'l.loan_product_id', '=', 'lp.id')
                ->whereBetween('l.created_at', [$startDate, $endDate])
                ->select([
                    'lp.id',
                    'lp.name as product_name',
                    'lp.interest_rate',
                    DB::raw('COUNT(*) as total_loans'),
                    DB::raw('SUM(l.amount) as total_amount'),
                    DB::raw('AVG(l.amount) as avg_loan_amount'),
                    DB::raw('SUM(CASE WHEN l.status = "active" THEN 1 ELSE 0 END) as active_loans'),
                    DB::raw('SUM(CASE WHEN l.status = "completed" THEN 1 ELSE 0 END) as completed_loans'),
                    DB::raw('SUM(CASE WHEN l.status = "defaulted" THEN 1 ELSE 0 END) as defaulted_loans')
                ])
                ->groupBy('lp.id', 'lp.name', 'lp.interest_rate')
                ->get();

            // Deposit products performance
            $depositPerformance = DB::table('accounts as a')
                ->join('deposit_products as dp', 'a.deposit_product_id', '=', 'dp.id')
                ->whereBetween('a.created_at', [$startDate, $endDate])
                ->select([
                    'dp.id',
                    'dp.name as product_name',
                    'dp.interest_rate',
                    DB::raw('COUNT(*) as total_accounts'),
                    DB::raw('SUM(a.balance) as total_balance'),
                    DB::raw('AVG(a.balance) as avg_balance'),
                    DB::raw('SUM(CASE WHEN a.status = "active" THEN 1 ELSE 0 END) as active_accounts')
                ])
                ->groupBy('dp.id', 'dp.name', 'dp.interest_rate')
                ->get();

            // Digital products performance
            $digitalPerformance = DB::table('transactions as t')
                ->join('digital_products as dp', 't.digital_product_id', '=', 'dp.id')
                ->whereBetween('t.created_at', [$startDate, $endDate])
                ->select([
                    'dp.id',
                    'dp.name as product_name',
                    'dp.category',
                    DB::raw('COUNT(*) as total_transactions'),
                    DB::raw('SUM(t.amount) as total_volume'),
                    DB::raw('COUNT(DISTINCT t.user_id) as unique_users'),
                    DB::raw('AVG(t.amount) as avg_transaction_amount')
                ])
                ->groupBy('dp.id', 'dp.name', 'dp.category')
                ->get();

            // Overall product metrics
            $overallMetrics = [
                'loan_products' => [
                    'total_products' => DB::table('loan_products')->where('is_active', true)->count(),
                    'total_loans_issued' => $loanPerformance->sum('total_loans'),
                    'total_loan_amount' => $loanPerformance->sum('total_amount'),
                    'default_rate' => $loanPerformance->sum('total_loans') > 0 
                        ? ($loanPerformance->sum('defaulted_loans') / $loanPerformance->sum('total_loans')) * 100 
                        : 0
                ],
                'deposit_products' => [
                    'total_products' => DB::table('deposit_products')->where('is_active', true)->count(),
                    'total_accounts' => $depositPerformance->sum('total_accounts'),
                    'total_deposits' => $depositPerformance->sum('total_balance'),
                    'avg_deposit_size' => $depositPerformance->sum('total_accounts') > 0 
                        ? $depositPerformance->sum('total_balance') / $depositPerformance->sum('total_accounts') 
                        : 0
                ],
                'digital_products' => [
                    'total_products' => DB::table('digital_products')->where('is_active', true)->count(),
                    'total_transactions' => $digitalPerformance->sum('total_transactions'),
                    'total_volume' => $digitalPerformance->sum('total_volume'),
                    'unique_users' => $digitalPerformance->sum('unique_users')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString()
                    ],
                    'overall_metrics' => $overallMetrics,
                    'loan_performance' => $loanPerformance,
                    'deposit_performance' => $depositPerformance,
                    'digital_performance' => $digitalPerformance
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('product_performance_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate product performance report'
            ], 500);
        }
    }

    /**
     * Get dormant customer report
     */
    public function getDormantCustomerReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'dormancy_days' => 'nullable|integer|min:30|max:365',
                'include_zero_balance' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dormancyDays = $request->input('dormancy_days', 90); // Default 90 days
            $includeZeroBalance = $request->input('include_zero_balance', false);
            $cutoffDate = Carbon::now()->subDays($dormancyDays);

            // Find dormant customers
            $query = DB::table('users as u')
                ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->leftJoin('accounts as a', 'u.id', '=', 'a.user_id')
                ->leftJoin('transactions as t', function($join) use ($cutoffDate) {
                    $join->on('u.id', '=', 't.user_id')
                         ->where('t.created_at', '>', $cutoffDate);
                })
                ->where('u.role', 'customer')
                ->where('u.status', 'active')
                ->whereNull('t.id') // No transactions in the period
                ->select([
                    'u.id',
                    'u.email',
                    'u.created_at as registration_date',
                    'cp.full_name',
                    'cp.phone_number',
                    'a.balance',
                    'a.account_number',
                    DB::raw('(SELECT MAX(created_at) FROM transactions WHERE user_id = u.id) as last_transaction_date'),
                    DB::raw('DATEDIFF(NOW(), (SELECT MAX(created_at) FROM transactions WHERE user_id = u.id)) as days_since_last_transaction')
                ]);

            if (!$includeZeroBalance) {
                $query->where('a.balance', '>', 0);
            }

            $dormantCustomers = $query->get();

            // Categorize by dormancy level
            $categorized = [
                'recently_dormant' => $dormantCustomers->filter(function($customer) {
                    return $customer->days_since_last_transaction >= 30 && $customer->days_since_last_transaction < 90;
                }),
                'moderately_dormant' => $dormantCustomers->filter(function($customer) {
                    return $customer->days_since_last_transaction >= 90 && $customer->days_since_last_transaction < 180;
                }),
                'highly_dormant' => $dormantCustomers->filter(function($customer) {
                    return $customer->days_since_last_transaction >= 180;
                })
            ];

            // Calculate total balances
            $totalDormantBalance = $dormantCustomers->sum('balance');
            $averageDormantBalance = $dormantCustomers->count() > 0 ? $totalDormantBalance / $dormantCustomers->count() : 0;

            // Summary statistics
            $summary = [
                'total_dormant_customers' => $dormantCustomers->count(),
                'total_dormant_balance' => $totalDormantBalance,
                'average_dormant_balance' => $averageDormantBalance,
                'dormancy_threshold_days' => $dormancyDays,
                'by_category' => [
                    'recently_dormant' => [
                        'count' => $categorized['recently_dormant']->count(),
                        'total_balance' => $categorized['recently_dormant']->sum('balance')
                    ],
                    'moderately_dormant' => [
                        'count' => $categorized['moderately_dormant']->count(),
                        'total_balance' => $categorized['moderately_dormant']->sum('balance')
                    ],
                    'highly_dormant' => [
                        'count' => $categorized['highly_dormant']->count(),
                        'total_balance' => $categorized['highly_dormant']->sum('balance')
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'dormant_customers' => $dormantCustomers->take(100), // Limit for performance
                    'categorized_customers' => [
                        'recently_dormant' => $categorized['recently_dormant']->take(50),
                        'moderately_dormant' => $categorized['moderately_dormant']->take(50),
                        'highly_dormant' => $categorized['highly_dormant']->take(50)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('dormant_customer_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate dormant customer report'
            ], 500);
        }
    }

    /**
     * Get user activity report
     */
    public function getUserActivityReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'activity_type' => 'nullable|in:login,transaction,all'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $activityType = $request->input('activity_type', 'all');

            // Login activity
            $loginActivity = DB::table('user_sessions as us')
                ->join('users as u', 'us.user_id', '=', 'u.id')
                ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->whereBetween('us.created_at', [$startDate, $endDate])
                ->where('u.role', 'customer')
                ->select([
                    'u.id as user_id',
                    'u.email',
                    'cp.full_name',
                    DB::raw('COUNT(*) as login_count'),
                    DB::raw('MAX(us.created_at) as last_login'),
                    DB::raw('AVG(TIMESTAMPDIFF(MINUTE, us.created_at, us.ended_at)) as avg_session_duration')
                ])
                ->groupBy('u.id', 'u.email', 'cp.full_name')
                ->get();

            // Transaction activity
            $transactionActivity = DB::table('transactions as t')
                ->join('users as u', 't.user_id', '=', 'u.id')
                ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->whereBetween('t.created_at', [$startDate, $endDate])
                ->where('u.role', 'customer')
                ->select([
                    'u.id as user_id',
                    'u.email',
                    'cp.full_name',
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(t.amount) as total_transaction_amount'),
                    DB::raw('AVG(t.amount) as avg_transaction_amount'),
                    DB::raw('MAX(t.created_at) as last_transaction'),
                    DB::raw('GROUP_CONCAT(DISTINCT t.type) as transaction_types')
                ])
                ->groupBy('u.id', 'u.email', 'cp.full_name')
                ->get();

            // Combined activity analysis
            $combinedActivity = collect();
            
            // Merge login and transaction data
            $allUsers = $loginActivity->pluck('user_id')->merge($transactionActivity->pluck('user_id'))->unique();
            
            foreach ($allUsers as $userId) {
                $loginData = $loginActivity->firstWhere('user_id', $userId);
                $transactionData = $transactionActivity->firstWhere('user_id', $userId);
                
                $combinedActivity->push([
                    'user_id' => $userId,
                    'email' => $loginData->email ?? $transactionData->email,
                    'full_name' => $loginData->full_name ?? $transactionData->full_name,
                    'login_count' => $loginData->login_count ?? 0,
                    'transaction_count' => $transactionData->transaction_count ?? 0,
                    'total_transaction_amount' => $transactionData->total_transaction_amount ?? 0,
                    'last_login' => $loginData->last_login ?? null,
                    'last_transaction' => $transactionData->last_transaction ?? null,
                    'avg_session_duration' => $loginData->avg_session_duration ?? 0,
                    'activity_score' => $this->calculateActivityScore(
                        $loginData->login_count ?? 0,
                        $transactionData->transaction_count ?? 0,
                        $transactionData->total_transaction_amount ?? 0
                    )
                ]);
            }

            // Activity patterns by day
            $dailyActivity = DB::table('transactions')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select([
                    DB::raw('DATE(created_at) as activity_date'),
                    DB::raw('COUNT(DISTINCT user_id) as active_users'),
                    DB::raw('COUNT(*) as total_transactions'),
                    DB::raw('SUM(amount) as total_volume')
                ])
                ->groupBy('activity_date')
                ->orderBy('activity_date')
                ->get();

            // Activity by hour (for peak time analysis)
            $hourlyActivity = DB::table('transactions')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select([
                    DB::raw('HOUR(created_at) as hour'),
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('COUNT(DISTINCT user_id) as unique_users')
                ])
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            // Summary statistics
            $summary = [
                'total_active_users' => $combinedActivity->count(),
                'total_logins' => $loginActivity->sum('login_count'),
                'total_transactions' => $transactionActivity->sum('transaction_count'),
                'total_transaction_volume' => $transactionActivity->sum('total_transaction_amount'),
                'avg_logins_per_user' => $combinedActivity->count() > 0 ? $loginActivity->sum('login_count') / $combinedActivity->count() : 0,
                'avg_transactions_per_user' => $combinedActivity->count() > 0 ? $transactionActivity->sum('transaction_count') / $combinedActivity->count() : 0,
                'most_active_day' => $dailyActivity->sortByDesc('active_users')->first(),
                'peak_hour' => $hourlyActivity->sortByDesc('transaction_count')->first()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString()
                    ],
                    'summary' => $summary,
                    'user_activity' => $combinedActivity->sortByDesc('activity_score')->take(100),
                    'daily_patterns' => $dailyActivity,
                    'hourly_patterns' => $hourlyActivity,
                    'login_activity' => $loginActivity->take(50),
                    'transaction_activity' => $transactionActivity->take(50)
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('user_activity_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate user activity report'
            ], 500);
        }
    }

    /**
     * Get daily reconciliation report
     */
    public function getDailyReconciliationReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'report_date' => 'required|date',
                'unit_id' => 'nullable|integer|exists:units,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reportDate = Carbon::parse($request->report_date);
            $startOfDay = $reportDate->copy()->startOfDay();
            $endOfDay = $reportDate->copy()->endOfDay();

            // Opening balances (from previous day)
            $previousDay = $reportDate->copy()->subDay()->endOfDay();
            $openingBalance = DB::table('accounts')
                ->where('created_at', '<=', $previousDay)
                ->sum('balance');

            // Daily transactions summary
            $transactionSummary = DB::table('transactions')
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->select([
                    'type',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as completed_amount'),
                    DB::raw('SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as pending_amount'),
                    DB::raw('SUM(CASE WHEN status = "failed" THEN amount ELSE 0 END) as failed_amount')
                ])
                ->groupBy('type')
                ->get();

            // Cash movements
            $cashMovements = [
                'deposits' => $transactionSummary->where('type', 'deposit')->first(),
                'withdrawals' => $transactionSummary->where('type', 'withdrawal')->first(),
                'transfers_in' => $transactionSummary->where('type', 'transfer_in')->first(),
                'transfers_out' => $transactionSummary->where('type', 'transfer_out')->first()
            ];

            // Calculate net cash movement
            $totalDeposits = $cashMovements['deposits']->completed_amount ?? 0;
            $totalWithdrawals = $cashMovements['withdrawals']->completed_amount ?? 0;
            $netCashMovement = $totalDeposits - $totalWithdrawals;

            // Closing balance
            $closingBalance = DB::table('accounts')
                ->where('created_at', '<=', $endOfDay)
                ->sum('balance');

            // Teller-wise reconciliation
            $tellerReconciliation = DB::table('transactions as t')
                ->join('users as teller', 't.processed_by', '=', 'teller.id')
                ->whereBetween('t.processed_at', [$startOfDay, $endOfDay])
                ->whereNotNull('t.processed_by')
                ->select([
                    'teller.id as teller_id',
                    'teller.name as teller_name',
                    DB::raw('SUM(CASE WHEN t.type = "deposit" THEN t.amount ELSE 0 END) as total_deposits'),
                    DB::raw('SUM(CASE WHEN t.type = "withdrawal" THEN t.amount ELSE 0 END) as total_withdrawals'),
                    DB::raw('COUNT(CASE WHEN t.type = "deposit" THEN 1 END) as deposit_count'),
                    DB::raw('COUNT(CASE WHEN t.type = "withdrawal" THEN 1 END) as withdrawal_count'),
                    DB::raw('SUM(CASE WHEN t.type = "deposit" THEN t.amount ELSE 0 END) - SUM(CASE WHEN t.type = "withdrawal" THEN t.amount ELSE 0 END) as net_amount')
                ])
                ->groupBy('teller.id', 'teller.name')
                ->get();

            // Failed transactions analysis
            $failedTransactions = DB::table('transactions')
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->where('status', 'failed')
                ->select([
                    'type',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(amount) as total_amount')
                ])
                ->groupBy('type')
                ->get();

            // Reconciliation summary
            $reconciliationSummary = [
                'report_date' => $reportDate->toDateString(),
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'net_change' => $closingBalance - $openingBalance,
                'calculated_closing' => $openingBalance + $netCashMovement,
                'variance' => $closingBalance - ($openingBalance + $netCashMovement),
                'total_transactions' => $transactionSummary->sum('count'),
                'total_volume' => $transactionSummary->sum('completed_amount'),
                'failed_transaction_count' => $failedTransactions->sum('count'),
                'failed_transaction_amount' => $failedTransactions->sum('total_amount')
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'reconciliation_summary' => $reconciliationSummary,
                    'transaction_summary' => $transactionSummary,
                    'cash_movements' => $cashMovements,
                    'teller_reconciliation' => $tellerReconciliation,
                    'failed_transactions' => $failedTransactions,
                    'variance_analysis' => [
                        'is_balanced' => abs($reconciliationSummary['variance']) < 1, // Within 1 unit tolerance
                        'variance_amount' => $reconciliationSummary['variance'],
                        'variance_percentage' => $openingBalance > 0 ? ($reconciliationSummary['variance'] / $openingBalance) * 100 : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('reconciliation_report_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate reconciliation report'
            ], 500);
        }
    }

    /**
     * Get audit log report
     */
    public function getAuditLog(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'user_id' => 'nullable|integer|exists:users,id',
                'action' => 'nullable|string',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $query = DB::table('audit_logs as al')
                ->leftJoin('users as u', 'al.user_id', '=', 'u.id')
                ->leftJoin('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->whereBetween('al.created_at', [$startDate, $endDate])
                ->select([
                    'al.*',
                    'u.email as user_email',
                    'cp.full_name as user_name'
                ])
                ->orderBy('al.created_at', 'desc');

            if ($request->user_id) {
                $query->where('al.user_id', $request->user_id);
            }

            if ($request->action) {
                $query->where('al.action', 'like', '%' . $request->action . '%');
            }

            $total = $query->count();
            $auditLogs = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'user_id' => $log->user_id,
                        'user_email' => $log->user_email,
                        'user_name' => $log->user_name,
                        'action' => $log->action,
                        'description' => $log->description,
                        'ip_address' => $log->ip_address,
                        'user_agent' => $log->user_agent,
                        'additional_data' => json_decode($log->additional_data ?? '{}', true),
                        'created_at' => $log->created_at,
                        'risk_level' => $this->assessAuditRisk($log->action)
                    ];
                });

            // Summary statistics
            $actionSummary = DB::table('audit_logs')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select([
                    'action',
                    DB::raw('COUNT(*) as count')
                ])
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->get();

            $userSummary = DB::table('audit_logs as al')
                ->join('users as u', 'al.user_id', '=', 'u.id')
                ->whereBetween('al.created_at', [$startDate, $endDate])
                ->select([
                    'u.id',
                    'u.email',
                    DB::raw('COUNT(*) as activity_count')
                ])
                ->groupBy('u.id', 'u.email')
                ->orderBy('activity_count', 'desc')
                ->take(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString()
                    ],
                    'audit_logs' => $auditLogs,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit)
                    ],
                    'summary' => [
                        'total_entries' => $total,
                        'unique_users' => DB::table('audit_logs')
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->distinct('user_id')
                            ->count(),
                        'unique_actions' => $actionSummary->count()
                    ],
                    'action_summary' => $actionSummary,
                    'top_users' => $userSummary
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('audit_log_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch audit logs'
            ], 500);
        }
    }

    /**
     * Get system logs
     */
    public function getSystemLogs(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'level' => 'nullable|in:emergency,alert,critical,error,warning,notice,info,debug',
                'component' => 'nullable|string',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $query = DB::table('system_logs')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc');

            if ($request->level) {
                $query->where('level', $request->level);
            }

            if ($request->component) {
                $query->where('component', 'like', '%' . $request->component . '%');
            }

            $total = $query->count();
            $systemLogs = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();

            // Log level summary
            $levelSummary = DB::table('system_logs')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select([
                    'level',
                    DB::raw('COUNT(*) as count')
                ])
                ->groupBy('level')
                ->get();

            // Component summary
            $componentSummary = DB::table('system_logs')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select([
                    'component',
                    DB::raw('COUNT(*) as count')
                ])
                ->groupBy('component')
                ->orderBy('count', 'desc')
                ->take(10)
                ->get();

            // Error trends (hourly)
            $errorTrends = DB::table('system_logs')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
                ->select([
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour'),
                    DB::raw('COUNT(*) as error_count')
                ])
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString()
                    ],
                    'system_logs' => $systemLogs,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit)
                    ],
                    'summary' => [
                        'total_logs' => $total,
                        'error_count' => $levelSummary->whereIn('level', ['error', 'critical', 'alert', 'emergency'])->sum('count'),
                        'warning_count' => $levelSummary->where('level', 'warning')->first()->count ?? 0,
                        'info_count' => $levelSummary->where('level', 'info')->first()->count ?? 0
                    ],
                    'level_summary' => $levelSummary,
                    'component_summary' => $componentSummary,
                    'error_trends' => $errorTrends
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('system_log_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system logs'
            ], 500);
        }
    }

    /**
     * Calculate activity score for user activity analysis
     */
    private function calculateActivityScore($loginCount, $transactionCount, $transactionAmount)
    {
        // Weighted scoring: logins (20%), transaction count (40%), transaction amount (40%)
        $loginScore = min($loginCount * 2, 20); // Max 20 points for logins
        $transactionScore = min($transactionCount * 1, 40); // Max 40 points for transaction count
        $amountScore = min($transactionAmount / 1000000, 40); // Max 40 points for amount (1M = 40 points)
        
        return $loginScore + $transactionScore + $amountScore;
    }

    /**
     * Assess risk level for audit log entries
     */
    private function assessAuditRisk($action)
    {
        $highRiskActions = [
            'admin_login', 'password_reset', 'account_closure', 'large_transaction',
            'failed_login_multiple', 'privilege_escalation', 'data_export'
        ];
        
        $mediumRiskActions = [
            'password_change', 'profile_update', 'transaction_reversal',
            'account_status_change', 'limit_change'
        ];
        
        if (in_array($action, $highRiskActions)) {
            return 'high';
        } elseif (in_array($action, $mediumRiskActions)) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}