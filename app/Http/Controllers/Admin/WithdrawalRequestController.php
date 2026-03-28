<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Models\User;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WithdrawalRequestController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get withdrawal requests
     */
    public function index(Request $request)
    {
        try {
            $status = $request->input('status', 'all'); // all, pending, approved, rejected, completed
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);

            // Query with joins to get customer and account information
            $query = DB::table('withdrawal_requests as wr')
                ->join('users as u', 'wr.user_id', '=', 'u.id')
                ->leftJoin('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->leftJoin('withdrawal_accounts as wa', 'wr.withdrawal_account_id', '=', 'wa.id')
                ->select([
                    'wr.*',
                    'u.email as customer_email',
                    'u.full_name as customer_name',
                    'wa.bank_name',
                    'wa.account_number',
                    'wa.account_name'
                ])
                ->orderBy('wr.created_at', 'desc');

            if ($status !== 'all') {
                $query->where('wr.status', strtolower($status));
            }

            $total = $query->count();
            $requests = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'customer_name' => $request->customer_name ?? $request->customer_email ?? 'Unknown Customer',
                        'amount' => $request->amount,
                        'bank_name' => $request->bank_name ?? 'Unknown Bank',
                        'account_number' => $request->account_number ?? 'Unknown Account',
                        'account_name' => $request->account_name ?? 'Unknown Account Name',
                        'status' => $request->status,
                        'created_at' => $request->created_at,
                        'processed_at' => $request->processed_at
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $requests,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Exception encountered: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific withdrawal request
     */
    public function show($id)
    {
        try {
            $request = DB::table('withdrawal_requests as wr')
                ->join('users as u', 'wr.user_id', '=', 'u.id')
                ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->join('withdrawal_accounts as wa', 'wr.withdrawal_account_id', '=', 'wa.id')
                ->select([
                    'wr.*',
                    'u.email as customer_email',
                    'u.full_name as customer_name',
                    'u.phone_number as customer_phone',
                    'wa.bank_name',
                    'wa.account_number',
                    'wa.account_name'
                ])
                ->where('wr.id', $id)
                ->first();

            if (!$request) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Withdrawal request not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $request->id,
                    'customer_name' => $request->customer_name,
                    'customer_email' => $request->customer_email,
                    'customer_phone' => $request->customer_phone,
                    'amount' => $request->amount,
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->account_number,
                    'account_name' => $request->account_name,
                    'purpose' => $request->purpose,
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                    'processed_at' => $request->processed_at,
                    'processed_by' => $request->processed_by,
                    'admin_notes' => $request->admin_notes,
                    'fee_amount' => $request->fee_amount,
                    'net_amount' => $request->amount - ($request->fee_amount ?? 0)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch withdrawal request'
            ], 500);
        }
    }

    /**
     * Process withdrawal request (approve/reject)
     */
    public function process(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:APPROVE,REJECT',
                'admin_notes' => 'nullable|string|max:500',
                'fee_amount' => 'nullable|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            
            // Get the withdrawal request
            $withdrawalRequest = WithdrawalRequest::where('id', $id)
                ->where('status', 'pending')
                ->first();

            if (!$withdrawalRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Withdrawal request not found or already processed'
                ], 404);
            }

            $user = User::find($withdrawalRequest->user_id);
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found'
                ], 404);
            }

            DB::beginTransaction();

            if ($request->action === 'APPROVE') {
                $feeAmount = $request->input('fee_amount', 0);
                $netAmount = $withdrawalRequest->amount - $feeAmount;

                if ($netAmount <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Net amount must be greater than zero'
                    ], 400);
                }

                $status = 'approved';
                $message = 'Withdrawal request approved';
                
                // Send notification to customer
                $this->notificationService->notifyUser(
                    $user->id,
                    'Withdrawal Approved',
                    "Your withdrawal request of " . number_format($withdrawalRequest->amount, 0, ',', '.') . " has been approved and will be processed soon."
                );

            } else {
                $status = 'rejected';
                $message = 'Withdrawal request rejected';
                
                // Send notification to customer
                $this->notificationService->notifyUser(
                    $user->id,
                    'Withdrawal Rejected',
                    'Your withdrawal request has been rejected. ' . ($request->admin_notes ?? '')
                );
            }

            // Update withdrawal request
            $withdrawalRequest->update([
                'status' => $status,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            DB::commit();

            // Log the action
            $this->logService->log(
                'withdrawal_request_processed',
                "Withdrawal request {$status} for user {$user->email}",
                $admin->id,
                [
                    'request_id' => $id,
                    'user_id' => $user->id,
                    'action' => $request->action,
                    'amount' => $withdrawalRequest->amount,
                    'fee_amount' => $request->input('fee_amount', 0)
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'request_id' => $id,
                    'status' => $status,
                    'processed_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('withdrawal_processing_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process withdrawal request'
            ], 500);
        }
    }

    /**
     * Disburse withdrawal (complete the withdrawal)
     */
    public function disburse(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'transaction_reference' => 'nullable|string|max:100',
                'admin_notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            
            // Get the withdrawal request
            $withdrawalRequest = WithdrawalRequest::where('id', $id)
                ->where('status', 'approved')
                ->first();

            if (!$withdrawalRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Withdrawal request not found or not approved'
                ], 404);
            }

            $user = User::find($withdrawalRequest->user_id);
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found'
                ], 404);
            }

            DB::beginTransaction();

            // Get user's account
            $account = DB::table('accounts')
                ->where('user_id', $user->id)
                ->where('account_type', 'TABUNGAN')
                ->first();

            if (!$account) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer account not found'
                ], 404);
            }

            $netAmount = $withdrawalRequest->amount;

            // Check if account has sufficient balance
            if ($account->balance < $netAmount) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient account balance'
                ], 400);
            }

            // Create debit transaction
            $txCode = 'TRX-' . time() . '-' . rand(100000, 999999);
            $transactionId = DB::table('transactions')->insertGetId([
                'transaction_code' => $txCode,
                'from_account_id' => $account->id,
                'transaction_type' => 'WITHDRAWAL',
                'amount' => $netAmount,
                'fee' => 0,
                'description' => "Withdrawal disbursement - Request #{$id}",
                'status' => 'SUCCESS',
                'reference_number' => $request->transaction_reference ?? 'WD-' . time(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update account balance
            DB::table('accounts')
                ->where('id', $account->id)
                ->decrement('balance', $netAmount);

            // Update withdrawal request
            $withdrawalRequest->update([
                'status' => 'completed',
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            DB::commit();

            // Send notification to customer
            $this->notificationService->notifyUser(
                $user->id,
                'Withdrawal Completed',
                "Your withdrawal of " . number_format($netAmount, 0, ',', '.') . " has been processed and transferred to your registered account."
            );

            // Log the action
            $this->logService->log(
                'withdrawal_disbursed',
                "Withdrawal disbursed for user {$user->email}",
                $admin->id,
                [
                    'request_id' => $id,
                    'user_id' => $user->id,
                    'amount' => $netAmount,
                    'transaction_id' => $transactionId
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Withdrawal has been successfully disbursed',
                'data' => [
                    'request_id' => $id,
                    'transaction_id' => $transactionId,
                    'net_amount' => $netAmount,
                    'disbursed_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('withdrawal_disbursement_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to disburse withdrawal'
            ], 500);
        }
    }
}