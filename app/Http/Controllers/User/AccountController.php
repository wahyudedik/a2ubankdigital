<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Request account closure
     */
    public function requestAccountClosure(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
                'closure_date' => 'required|date|after:today',
                'transfer_remaining_balance' => 'required|boolean',
                'transfer_account_number' => 'required_if:transfer_remaining_balance,true|string|max:20',
                'confirmation_password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = Auth::user();
            
            // Verify password
            if (!password_verify($request->confirmation_password, $user->password_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password confirmation'
                ], 400);
            }

            // Check if user has active loans
            $activeLoans = $user->loans()->where('status', 'ACTIVE')->count();
            if ($activeLoans > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot close account with active loans. Please settle all loans first.'
                ], 400);
            }

            // Check if user has pending transactions
            $pendingTransactions = $user->transactions()
                ->whereIn('status', ['PENDING'])
                ->count();
            
            if ($pendingTransactions > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot close account with pending transactions. Please wait for all transactions to complete.'
                ], 400);
            }

            DB::beginTransaction();

            // Create account closure request
            $closureRequest = DB::table('account_closure_requests')->insertGetId([
                'user_id' => $user->id,
                'reason' => $request->reason,
                'status' => 'pending',
                'requested_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log the request
            $this->logService->log(
                'account_closure_requested',
                'User requested account closure',
                $user->id,
                ['closure_request_id' => $closureRequest, 'reason' => $request->reason]
            );

            // Notify admin
            $this->notificationService->notifyAdmins(
                'Account Closure Request',
                "User {$user->name} has requested account closure. Reason: {$request->reason}",
                ['type' => 'account_closure', 'user_id' => $user->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Account closure request submitted successfully',
                'data' => [
                    'request_id' => $closureRequest,
                    'status' => 'pending',
                    'estimated_processing_time' => '3-5 business days'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->logService->log(
                'account_closure_request_failed',
                'Account closure request failed: ' . $e->getMessage(),
                Auth::id()
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit account closure request'
            ], 500);
        }
    }

    /**
     * Request credit limit increase
     */
    public function requestCreditLimitIncrease(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'requested_limit' => 'required|numeric|min:1000000|max:100000000',
                'monthly_income' => 'required|numeric|min:0',
                'employment_status' => 'required|string|in:employed,self_employed,retired,student',
                'purpose' => 'required|string|max:500',
                'supporting_documents' => 'array',
                'supporting_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = Auth::user();
            $currentLimit = $user->account->credit_limit ?? 0;

            // Check if requested limit is higher than current
            if ($request->requested_limit <= $currentLimit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Requested limit must be higher than current limit'
                ], 400);
            }

            // Check if user has pending credit limit requests
            $pendingRequests = DB::table('credit_limit_requests')
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->count();

            if ($pendingRequests > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a pending credit limit request'
                ], 400);
            }

            DB::beginTransaction();

            // Handle document uploads
            $documentPaths = [];
            if ($request->hasFile('supporting_documents')) {
                foreach ($request->file('supporting_documents') as $file) {
                    $path = $file->store('credit_limit_documents', 'public');
                    $documentPaths[] = $path;
                }
            }

            // Create credit limit request
            $limitRequest = DB::table('credit_limit_requests')->insertGetId([
                'user_id' => $user->id,
                'current_limit' => $currentLimit,
                'requested_limit' => $request->requested_limit,
                'monthly_income' => $request->monthly_income,
                'employment_status' => $request->employment_status,
                'purpose' => $request->purpose,
                'supporting_documents' => json_encode($documentPaths),
                'status' => 'pending',
                'requested_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log the request
            $this->logService->log(
                'credit_limit_increase_requested',
                'User requested credit limit increase',
                $user->id,
                [
                    'request_id' => $limitRequest,
                    'current_limit' => $currentLimit,
                    'requested_limit' => $request->requested_limit
                ]
            );

            // Notify admin
            $this->notificationService->notifyAdmins(
                'Credit Limit Increase Request',
                "User {$user->name} has requested credit limit increase from " . number_format($currentLimit) . " to " . number_format($request->requested_limit),
                ['type' => 'credit_limit_request', 'user_id' => $user->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Credit limit increase request submitted successfully',
                'data' => [
                    'request_id' => $limitRequest,
                    'current_limit' => $currentLimit,
                    'requested_limit' => $request->requested_limit,
                    'status' => 'pending',
                    'estimated_processing_time' => '5-7 business days'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->logService->log(
                'credit_limit_request_failed',
                'Credit limit request failed: ' . $e->getMessage(),
                Auth::id()
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit credit limit request'
            ], 500);
        }
    }

    /**
     * Get account closure requests
     */
    public function getAccountClosureRequests()
    {
        try {
            $user = Auth::user();
            
            $requests = DB::table('account_closure_requests')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve account closure requests'
            ], 500);
        }
    }

    /**
     * Get credit limit requests
     */
    public function getCreditLimitRequests()
    {
        try {
            $user = Auth::user();
            
            $requests = DB::table('credit_limit_requests')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve credit limit requests'
            ], 500);
        }
    }
}