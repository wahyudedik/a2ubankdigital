<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountManagementController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Process account closure request
     */
    public function processAccountClosure(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'request_id' => 'required|integer|exists:account_closure_requests,id',
                'action' => 'required|in:approve,reject',
                'admin_notes' => 'nullable|string|max:500',
                'closure_reason' => 'required_if:action,approve|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            
            // Get the closure request
            $closureRequest = DB::table('account_closure_requests')
                ->where('id', $request->request_id)
                ->where('status', 'pending')
                ->first();

            if (!$closureRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Closure request not found or already processed'
                ], 404);
            }

            $user = User::find($closureRequest->user_id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            DB::beginTransaction();

            if ($request->action === 'approve') {
                // Check if user has outstanding balances or loans
                $accounts = Account::where('user_id', $user->id)->get();
                $hasOutstandingBalance = false;
                $totalBalance = 0;

                foreach ($accounts as $account) {
                    if ($account->balance > 0) {
                        $hasOutstandingBalance = true;
                        $totalBalance += $account->balance;
                    }
                }

                // Check for active loans
                $activeLoans = DB::table('loans')
                    ->where('user_id', $user->id)
                    ->where('status', 'ACTIVE')
                    ->count();

                if ($activeLoans > 0) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot close account with active loans'
                    ], 400);
                }

                // If there's outstanding balance, create final withdrawal
                if ($hasOutstandingBalance) {
                    foreach ($accounts as $account) {
                        if ($account->balance > 0) {
                            // Create final withdrawal transaction
                            Transaction::create([
                                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                                'from_account_id' => $account->id,
                                'transaction_type' => 'WITHDRAWAL',
                                'amount' => $account->balance,
                                'fee' => 0,
                                'description' => 'Final withdrawal - Account closure',
                                'status' => 'SUCCESS',
                                'reference_number' => 'CLOSURE_' . time() . '_' . $account->id
                            ]);

                            // Update account balance to zero
                            $account->update(['balance' => 0]);
                        }
                    }
                }

                // Close all accounts
                Account::where('user_id', $user->id)->update([
                    'status' => 'CLOSED',
                ]);

                // Deactivate user
                $user->update([
                    'status' => 'BLOCKED',
                ]);

                $status = 'approved';
                $message = 'Account closure approved and processed';
                
                // Send notification to user
                $this->notificationService->send(
                    $user->id,
                    'Account Closed',
                    'Your account closure request has been approved and processed.',
                    'account'
                );

            } else {
                $status = 'rejected';
                $message = 'Account closure request rejected';
                
                // Send notification to user
                $this->notificationService->send(
                    $user->id,
                    'Account Closure Rejected',
                    'Your account closure request has been rejected. ' . ($request->admin_notes ?? ''),
                    'account'
                );
            }

            // Update closure request
            DB::table('account_closure_requests')
                ->where('id', $request->request_id)
                ->update([
                    'status' => $status,
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                    'updated_at' => now()
                ]);

            DB::commit();

            // Log the action
            $this->logService->log(
                'account_closure_processed',
                "Account closure {$status} for user {$user->email}",
                $admin->id,
                [
                    'user_id' => $user->id,
                    'request_id' => $request->request_id,
                    'action' => $request->action,
                    'total_balance_returned' => $totalBalance ?? 0
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'request_id' => $request->request_id,
                    'status' => $status,
                    'processed_at' => now()->toISOString(),
                    'total_balance_returned' => $totalBalance ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('account_closure_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process account closure'
            ], 500);
        }
    }

    /**
     * Get account closure requests
     */
    public function getAccountClosureRequests(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);
            $status = $request->input('status', 'all');

            $query = DB::table('account_closure_requests as acr')
                ->join('users as u', 'acr.user_id', '=', 'u.id')
                ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->select([
                    'acr.*',
                    'u.email',
                    'cp.full_name',
                    'cp.phone_number'
                ])
                ->orderBy('acr.created_at', 'desc');

            if ($status !== 'all') {
                $query->where('acr.status', $status);
            }

            $total = $query->count();
            $requests = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'user_id' => $request->user_id,
                        'customer_name' => $request->full_name,
                        'email' => $request->email,
                        'phone_number' => $request->phone_number,
                        'reason' => $request->reason,
                        'status' => $request->status,
                        'requested_at' => $request->created_at,
                        'processed_at' => $request->processed_at,
                        'admin_notes' => $request->admin_notes
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'requests' => $requests,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch closure requests'
            ], 500);
        }
    }

    /**
     * Process credit limit increase request
     */
    public function processCreditLimitRequest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'request_id' => 'required|integer|exists:credit_limit_requests,id',
                'action' => 'required|in:approve,reject',
                'admin_notes' => 'nullable|string|max:500',
                'approved_limit' => 'required_if:action,approve|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            
            // Get the credit limit request
            $limitRequest = DB::table('credit_limit_requests')
                ->where('id', $request->request_id)
                ->where('status', 'pending')
                ->first();

            if (!$limitRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit limit request not found or already processed'
                ], 404);
            }

            $user = User::find($limitRequest->user_id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            DB::beginTransaction();

            if ($request->action === 'approve') {
                // Update user's credit limit
                $user->update([
                    'credit_limit' => $request->approved_limit
                ]);

                $status = 'approved';
                $message = 'Credit limit increase approved';
                
                // Send notification to user
                $this->notificationService->send(
                    $user->id,
                    'Credit Limit Approved',
                    "Your credit limit has been increased to " . number_format($request->approved_limit, 0, ',', '.'),
                    'account'
                );

            } else {
                $status = 'rejected';
                $message = 'Credit limit increase rejected';
                
                // Send notification to user
                $this->notificationService->send(
                    $user->id,
                    'Credit Limit Request Rejected',
                    'Your credit limit increase request has been rejected. ' . ($request->admin_notes ?? ''),
                    'account'
                );
            }

            // Update credit limit request
            DB::table('credit_limit_requests')
                ->where('id', $request->request_id)
                ->update([
                    'status' => $status,
                    'approved_limit' => $request->approved_limit ?? null,
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                    'admin_notes' => $request->admin_notes,
                    'updated_at' => now()
                ]);

            DB::commit();

            // Log the action
            $this->logService->log(
                'credit_limit_processed',
                "Credit limit {$status} for user {$user->email}",
                $admin->id,
                [
                    'user_id' => $user->id,
                    'request_id' => $request->request_id,
                    'action' => $request->action,
                    'approved_limit' => $request->approved_limit ?? null
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'request_id' => $request->request_id,
                    'status' => $status,
                    'approved_limit' => $request->approved_limit ?? null,
                    'processed_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('credit_limit_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process credit limit request'
            ], 500);
        }
    }

    /**
     * Get credit limit requests
     */
    public function getCreditLimitRequests(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);
            $status = $request->input('status', 'all');

            $query = DB::table('credit_limit_requests as clr')
                ->join('users as u', 'clr.user_id', '=', 'u.id')
                ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->select([
                    'clr.*',
                    'u.email',
                    'u.credit_limit as current_limit',
                    'cp.full_name',
                    'cp.phone_number'
                ])
                ->orderBy('clr.created_at', 'desc');

            if ($status !== 'all') {
                $query->where('clr.status', $status);
            }

            $total = $query->count();
            $requests = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'user_id' => $request->user_id,
                        'customer_name' => $request->full_name,
                        'email' => $request->email,
                        'phone_number' => $request->phone_number,
                        'current_limit' => $request->current_limit,
                        'requested_limit' => $request->requested_limit,
                        'approved_limit' => $request->approved_limit,
                        'reason' => $request->reason,
                        'status' => $request->status,
                        'requested_at' => $request->created_at,
                        'processed_at' => $request->processed_at,
                        'admin_notes' => $request->admin_notes
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'requests' => $requests,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch credit limit requests'
            ], 500);
        }
    }
}