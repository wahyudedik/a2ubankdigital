<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only staff can access
        if ($user->role_id == 9) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $search = $request->input('search', '');

        // Build query
        $query = User::with('customerProfile')
            ->where('role_id', 9); // Only customers

        // Data scoping - filter by accessible units
        if ($user->role_id !== 1) { // Not super admin
            $accessibleUnitIds = $this->getAccessibleUnitIds($user);
            
            if (empty($accessibleUnitIds)) {
                return response()->json([
                    'status' => 'success',
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'total_pages' => 0,
                        'total_records' => 0
                    ]
                ]);
            }

            $query->whereHas('customerProfile', function($q) use ($accessibleUnitIds) {
                $q->whereIn('unit_id', $accessibleUnitIds);
            });
        }

        // Search filter
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('bank_id', 'like', "%{$search}%");
            });
        }

        // Get total count
        $totalRecords = $query->count();
        $totalPages = ceil($totalRecords / $limit);

        // Get paginated data
        $customers = $query
            ->select(['id', 'bank_id', 'full_name', 'email', 'phone_number', 'status', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $customers,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => (int)$totalPages,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    public function show($id): JsonResponse
    {
        $customer = User::with(['customerProfile', 'accounts', 'loans'])
            ->where('role_id', 9)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $customer
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'nik' => 'required|string|size:16',
            'mother_maiden_name' => 'required|string',
            'pob' => 'sometimes|string',
            'dob' => 'sometimes|date',
            'gender' => 'sometimes|in:MALE,FEMALE',
            'address_ktp' => 'sometimes|string',
            'phone_number' => 'required|string',
            'unit_id' => 'required|exists:units,id'
        ]);

        // Check duplicate NIK
        $existingProfile = CustomerProfile::where('nik', $request->nik)->first();
        if ($existingProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'NIK sudah terdaftar.'
            ], 409);
        }

        DB::beginTransaction();
        try {
            // Create user with default password
            $user = User::create([
                'role_id' => 9, // Customer
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make('password123'),
                'phone_number' => $request->phone_number,
                'status' => 'ACTIVE',
                'unit_id' => $request->unit_id
            ]);

            // Create customer profile
            CustomerProfile::create([
                'user_id' => $user->id,
                'unit_id' => $request->unit_id,
                'nik' => $request->nik,
                'mother_maiden_name' => $request->mother_maiden_name,
                'pob' => $request->pob,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'address_ktp' => $request->address_ktp,
                'registration_method' => 'OFFLINE',
                'registered_by' => Auth::id(),
                'kyc_status' => 'APPROVED'
            ]);

            // Create savings account
            Account::create([
                'user_id' => $user->id,
                'account_type' => 'TABUNGAN',
                'balance' => 0,
                'status' => 'ACTIVE'
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Nasabah baru berhasil ditambahkan.',
                'data' => $user->fresh(['customerProfile', 'accounts'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan nasabah: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string',
            'address_domicile' => 'sometimes|string',
            'occupation' => 'sometimes|string'
        ]);

        $customer = User::where('role_id', 9)->findOrFail($id);

        DB::beginTransaction();
        try {
            $customer->update($request->only(['full_name', 'phone_number']));

            if ($customer->customerProfile) {
                $customer->customerProfile->update($request->only([
                    'address_domicile',
                    'occupation'
                ]));
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data nasabah berhasil diperbarui.',
                'data' => $customer->fresh(['customerProfile'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:ACTIVE,BLOCKED,SUSPENDED'
        ]);

        $customer = User::where('role_id', 9)->findOrFail($id);

        $customer->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status nasabah berhasil diperbarui.',
            'data' => $customer
        ]);
    }

    private function getAccessibleUnitIds($user): array
    {
        // Logic to get accessible unit IDs based on user's unit assignment
        // This would typically come from a staff_unit_assignments table
        // For now, return user's unit_id if exists
        return $user->unit_id ? [$user->unit_id] : [];
    }

    /**
     * Process account closure request
     */
    public function processAccountClosure(Request $request, $requestId)
    {
        try {
            $request->validate([
                'action' => 'required|string|in:approve,reject',
                'admin_notes' => 'nullable|string|max:1000',
                'rejection_reason' => 'required_if:action,reject|string|max:500'
            ]);

            $admin = Auth::user();
            
            // Get closure request
            $closureRequest = DB::table('account_closure_requests')
                ->where('id', $requestId)
                ->where('status', 'pending')
                ->first();

            if (!$closureRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account closure request not found or already processed'
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
                // Process account closure
                $account = $user->account;
                
                // Transfer remaining balance if requested
                if ($closureRequest->transfer_remaining_balance && $account->balance > 0) {
                    // Create transfer transaction
                    Transaction::create([
                        'user_id' => $user->id,
                        'account_id' => $account->id,
                        'type' => 'transfer_out',
                        'amount' => $account->balance,
                        'description' => 'Account closure - balance transfer',
                        'recipient_account' => $closureRequest->transfer_account_number,
                        'status' => 'completed',
                        'reference_number' => 'AC' . time() . rand(1000, 9999),
                        'processed_at' => now()
                    ]);

                    // Update account balance
                    $account->update(['balance' => 0]);
                }

                // Close the account
                $account->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'closed_by' => $admin->id
                ]);

                // Deactivate user
                $user->update([
                    'status' => 'inactive',
                    'account_closed_at' => now()
                ]);

                $status = 'approved';
                $message = 'Account closure approved and processed successfully';
                
                // Notify user
                $this->notificationService->notify(
                    $user->id,
                    'Account Closure Approved',
                    'Your account closure request has been approved and processed. Your account is now closed.',
                    ['type' => 'account_closure_approved']
                );

            } else {
                // Reject closure request
                $status = 'rejected';
                $message = 'Account closure request rejected';
                
                // Notify user
                $this->notificationService->notify(
                    $user->id,
                    'Account Closure Rejected',
                    "Your account closure request has been rejected. Reason: {$request->rejection_reason}",
                    ['type' => 'account_closure_rejected']
                );
            }

            // Update closure request
            DB::table('account_closure_requests')
                ->where('id', $requestId)
                ->update([
                    'status' => $status,
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                    'admin_notes' => $request->admin_notes,
                    'rejection_reason' => $request->rejection_reason,
                    'updated_at' => now()
                ]);

            // Log the action
            $this->logService->log(
                'account_closure_processed',
                "Account closure request {$status} by admin",
                $admin->id,
                [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'action' => $request->action,
                    'admin_notes' => $request->admin_notes
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'request_id' => $requestId,
                    'status' => $status,
                    'processed_at' => now(),
                    'processed_by' => $admin->name
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->logService->log(
                'account_closure_process_failed',
                'Account closure processing failed: ' . $e->getMessage(),
                Auth::id()
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to process account closure request'
            ], 500);
        }
    }

    /**
     * Process credit limit request
     */
    public function processCreditLimitRequest(Request $request, $requestId)
    {
        try {
            $request->validate([
                'action' => 'required|string|in:approve,reject',
                'approved_limit' => 'required_if:action,approve|numeric|min:0',
                'admin_notes' => 'nullable|string|max:1000',
                'rejection_reason' => 'required_if:action,reject|string|max:500'
            ]);

            $admin = Auth::user();
            
            // Get credit limit request
            $limitRequest = DB::table('credit_limit_requests')
                ->where('id', $requestId)
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
                $user->account->update([
                    'credit_limit' => $request->approved_limit
                ]);

                $status = 'approved';
                $message = 'Credit limit increase approved successfully';
                
                // Notify user
                $this->notificationService->notify(
                    $user->id,
                    'Credit Limit Approved',
                    "Your credit limit increase request has been approved. New limit: " . number_format($request->approved_limit),
                    ['type' => 'credit_limit_approved', 'new_limit' => $request->approved_limit]
                );

            } else {
                // Reject request
                $status = 'rejected';
                $message = 'Credit limit request rejected';
                
                // Notify user
                $this->notificationService->notify(
                    $user->id,
                    'Credit Limit Rejected',
                    "Your credit limit increase request has been rejected. Reason: {$request->rejection_reason}",
                    ['type' => 'credit_limit_rejected']
                );
            }

            // Update request
            DB::table('credit_limit_requests')
                ->where('id', $requestId)
                ->update([
                    'status' => $status,
                    'approved_limit' => $request->approved_limit,
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                    'admin_notes' => $request->admin_notes,
                    'rejection_reason' => $request->rejection_reason,
                    'updated_at' => now()
                ]);

            // Log the action
            $this->logService->log(
                'credit_limit_processed',
                "Credit limit request {$status} by admin",
                $admin->id,
                [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'action' => $request->action,
                    'approved_limit' => $request->approved_limit
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'request_id' => $requestId,
                    'status' => $status,
                    'approved_limit' => $request->approved_limit,
                    'processed_at' => now(),
                    'processed_by' => $admin->name
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->logService->log(
                'credit_limit_process_failed',
                'Credit limit processing failed: ' . $e->getMessage(),
                Auth::id()
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to process credit limit request'
            ], 500);
        }
    }

    /**
     * Get pending account closure requests
     */
    public function getPendingAccountClosures()
    {
        try {
            $requests = DB::table('account_closure_requests')
                ->join('users', 'account_closure_requests.user_id', '=', 'users.id')
                ->select(
                    'account_closure_requests.*',
                    'users.full_name as user_name',
                    'users.email as user_email',
                    'users.phone_number as user_phone'
                )
                ->where('account_closure_requests.status', 'pending')
                ->orderBy('account_closure_requests.created_at', 'desc')
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
     * Get pending credit limit requests
     */
    public function getPendingCreditLimitRequests()
    {
        try {
            $requests = DB::table('credit_limit_requests')
                ->join('users', 'credit_limit_requests.user_id', '=', 'users.id')
                ->join('accounts', 'users.id', '=', 'accounts.user_id')
                ->select(
                    'credit_limit_requests.*',
                    'users.full_name as user_name',
                    'users.email as user_email',
                    'accounts.balance as current_balance'
                )
                ->where('credit_limit_requests.status', 'pending')
                ->orderBy('credit_limit_requests.created_at', 'desc')
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
