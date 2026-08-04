<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\Account;
use App\Models\Role;
use App\Models\Transaction;
use App\Services\LogService;
use App\Services\NotificationService;
use App\Traits\UnitAccessTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    use UnitAccessTrait;

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

        if ($page < 1 || $limit < 1 || $limit > 100) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter pagination tidak valid. Halaman minimal 1, limit antara 1 dan 100.'
            ], 422);
        }

        // Build query
        $query = User::with('customerProfile')
            ->where('role_id', 9); // Only customers

        // Data scoping - filter by accessible units
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        if ($accessibleUnitIds === null) {
            // Super Admin - no filter
        } elseif (empty($accessibleUnitIds)) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 0,
                    'total_records' => 0
                ]
            ]);
        } else {
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
        $user = Auth::user();

        // Unit access check
        if (!$this->canAccessCustomer($user, (int) $id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses ke data nasabah ini.'
            ], 403);
        }

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
            'nik' => 'required|string|digits:16',
            'mother_maiden_name' => 'required|string',
            'pob' => 'sometimes|string',
            'dob' => 'sometimes|date',
            'gender' => 'sometimes|in:MALE,FEMALE,L,P',
            'address_ktp' => 'sometimes|string',
            'phone_number' => 'required|string',
            'unit_id' => 'required|exists:units,id'
        ], [
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'email.unique' => 'Email sudah terdaftar.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'nik.required' => 'NIK wajib diisi.',
            'mother_maiden_name.required' => 'Nama ibu kandung wajib diisi.',
            'phone_number.required' => 'Nomor telepon wajib diisi.',
            'unit_id.required' => 'Unit penempatan wajib dipilih.',
        ]);

        // Check unit accessibility for non-super-admin
        $adminUser = Auth::user();
        $accessibleUnitIds = $this->getAccessibleUnitIds($adminUser);
        if ($accessibleUnitIds !== null) {
            if (!in_array($request->unit_id, $accessibleUnitIds)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke unit tersebut.'
                ], 403);
            }
        }

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
            // Generate unique bank_id (NIP format)
            $bankId = $this->generateBankId();

            // Create user with default password
            $user = User::create([
                'bank_id' => $bankId,
                'role_id' => 9, // Customer
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password_hash' => bcrypt('password123'),
                'phone_number' => $request->phone_number,
                'status' => 'ACTIVE'
            ]);

            // Convert gender format
            $gender = $request->gender;
            if ($gender === 'MALE') $gender = 'L';
            if ($gender === 'FEMALE') $gender = 'P';

            // Create customer profile
            CustomerProfile::create([
                'user_id' => $user->id,
                'unit_id' => $request->unit_id,
                'nik' => $request->nik,
                'mother_maiden_name' => $request->mother_maiden_name,
                'pob' => $request->pob,
                'dob' => $request->dob,
                'gender' => $gender,
                'address_ktp' => $request->address_ktp,
                'kyc_status' => 'PENDING'
            ]);

            // Create savings account with unique account number
            do {
                $accountNumber = '1100' . str_pad($user->id, 6, '0', STR_PAD_LEFT) . rand(100, 999);
            } while (Account::where('account_number', $accountNumber)->exists());

            Account::create([
                'user_id' => $user->id,
                'account_number' => $accountNumber,
                'account_type' => 'TABUNGAN',
                'balance' => 0,
                'status' => 'ACTIVE'
            ]);

            // Log audit
            $this->logService->logAudit('CUSTOMER_CREATED', 'users', $user->id, [], [
                'customer_name' => $user->full_name
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
        $user = Auth::user();

        // Unit access check
        if (!$this->canAccessCustomer($user, (int) $id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses ke data nasabah ini.'
            ], 403);
        }

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
        $user = Auth::user();

        // Unit access check
        if (!$this->canAccessCustomer($user, (int) $id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses ke data nasabah ini.'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:ACTIVE,BLOCKED,SUSPENDED'
        ]);

        $customer = User::where('role_id', 9)->findOrFail($id);

        $oldStatus = $customer->status;
        $customer->update(['status' => $request->status]);

        // Send notification to customer about status change
        if ($oldStatus !== $request->status) {
            $statusMessages = [
                'ACTIVE' => 'Akun Anda telah diaktifkan kembali. Anda dapat menggunakan layanan kami.',
                'BLOCKED' => 'Akun Anda telah diblokir. Silakan hubungi customer service untuk informasi lebih lanjut.',
                'SUSPENDED' => 'Akun Anda telah ditangguhkan sementara. Silakan hubungi customer service untuk informasi lebih lanjut.'
            ];

            $this->notificationService->notifyUser(
                $customer->id,
                'Status Akun Diperbarui',
                $statusMessages[$request->status] ?? 'Status akun Anda telah diperbarui.'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status nasabah berhasil diperbarui.',
            'data' => $customer
        ]);
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
                    'status' => 'error',
                    'message' => 'Account closure request not found or already processed'
                ], 404);
            }

            $customerUser = User::find($closureRequest->user_id);
            if (!$customerUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Unit access check
            if (!$this->canAccessCustomer($admin, $closureRequest->user_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke data nasabah ini.'
                ], 403);
            }

            $user = $customerUser;

            DB::beginTransaction();

            if ($request->action === 'approve') {
                // Process account closure
                $account = $user->account;

                if (!$account) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User account not found'
                    ], 404);
                }

                // Transfer remaining balance if requested
                if ($closureRequest->transfer_remaining_balance && $account->balance > 0) {
                    // Create transfer transaction
                    Transaction::create([
                        'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                        'from_account_id' => $account->id,
                        'transaction_type' => 'WITHDRAWAL',
                        'amount' => $account->balance,
                        'fee' => 0,
                        'description' => 'Account closure - balance transfer',
                        'status' => 'SUCCESS',
                        'reference_number' => 'AC' . time() . rand(1000, 9999)
                    ]);

                    // Update account balance
                    $account->update(['balance' => 0]);
                }

                // Close the account
                $account->update([
                    'status' => 'CLOSED',
                ]);

                // Deactivate user
                $user->update([
                    'status' => 'BLOCKED',
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
                'status' => 'success',
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
                'status' => 'error',
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
                    'status' => 'error',
                    'message' => 'Credit limit request not found or already processed'
                ], 404);
            }

            $customerUser = User::find($limitRequest->user_id);
            if (!$customerUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // Unit access check
            if (!$this->canAccessCustomer($admin, $limitRequest->user_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke data nasabah ini.'
                ], 403);
            }

            $user = $customerUser;

            DB::beginTransaction();

            if ($request->action === 'approve') {
                // Update user's credit limit
                $userAccount = $user->account;

                if (!$userAccount) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User account not found'
                    ], 404);
                }

                $userAccount->update([
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
                'status' => 'success',
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
                'status' => 'error',
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
            $user = Auth::user();
            $accessibleUnitIds = $this->getAccessibleUnitIds($user);

            $query = DB::table('account_closure_requests')
                ->join('users', 'account_closure_requests.user_id', '=', 'users.id')
                ->join('customer_profiles', 'users.id', '=', 'customer_profiles.user_id')
                ->select(
                    'account_closure_requests.*',
                    'users.full_name as user_name',
                    'users.email as user_email',
                    'users.phone_number as user_phone'
                )
                ->where('account_closure_requests.status', 'pending');

            // Unit filtering
            if ($accessibleUnitIds !== null) {
                if (empty($accessibleUnitIds)) {
                    return response()->json(['status' => 'success', 'data' => []]);
                }
                $query->whereIn('customer_profiles.unit_id', $accessibleUnitIds);
            }

            $requests = $query->orderBy('account_closure_requests.created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
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
            $user = Auth::user();
            $accessibleUnitIds = $this->getAccessibleUnitIds($user);

            $query = DB::table('credit_limit_requests')
                ->join('users', 'credit_limit_requests.user_id', '=', 'users.id')
                ->join('accounts', 'users.id', '=', 'accounts.user_id')
                ->join('customer_profiles', 'users.id', '=', 'customer_profiles.user_id')
                ->select(
                    'credit_limit_requests.*',
                    'users.full_name as user_name',
                    'users.email as user_email',
                    'accounts.balance as current_balance'
                )
                ->where('credit_limit_requests.status', 'pending');

            // Unit filtering
            if ($accessibleUnitIds !== null) {
                if (empty($accessibleUnitIds)) {
                    return response()->json(['status' => 'success', 'data' => []]);
                }
                $query->whereIn('customer_profiles.unit_id', $accessibleUnitIds);
            }

            $requests = $query->orderBy('credit_limit_requests.created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve credit limit requests'
            ], 500);
        }
    }

    /**
     * Generate unique bank ID for new customer
     */
    private function generateBankId(): string
    {
        do {
            // Format: YYYYMMDD + 4 random digits
            $bankId = date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

            // Check if already exists
            $exists = User::where('bank_id', $bankId)->exists();
        } while ($exists);

        return $bankId;
    }

    /**
     * Admin mengganti dokumen KYC nasabah (KTP & Selfie).
     */
    public function updateKycDocuments(Request $request, $id): JsonResponse
    {
        $request->validate([
            'ktp_image' => 'required_without:selfie_image|image|mimes:jpg,jpeg,png|max:2048',
            'selfie_image' => 'required_without:ktp_image|image|mimes:jpg,jpeg,png|max:2048',
            'kyc_status' => 'sometimes|in:VERIFIED,REJECTED,PENDING',
            'kyc_notes' => 'sometimes|nullable|string|max:500',
        ], [
            'ktp_image.required_without' => 'Minimal satu dokumen (KTP atau Selfie) harus diupload.',
            'selfie_image.required_without' => 'Minimal satu dokumen (KTP atau Selfie) harus diupload.',
            'ktp_image.max' => 'Ukuran foto KTP maksimal 2MB.',
            'selfie_image.max' => 'Ukuran foto selfie maksimal 2MB.',
            'kyc_status.in' => 'Status KYC tidak valid.',
        ]);

        $user = Auth::user();

        // Unit access check
        if (!$this->canAccessCustomer($user, (int) $id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses ke data nasabah ini.'
            ], 403);
        }

        $customer = User::where('role_id', 9)->with('customerProfile')->findOrFail($id);
        $profile = $customer->customerProfile;

        if (!$profile) {
            return response()->json(['status' => 'error', 'message' => 'Profil nasabah tidak ditemukan.'], 404);
        }

        // Validasi dimensi gambar
        if ($request->hasFile('ktp_image')) {
            $ktpTempPath = $request->file('ktp_image')->getRealPath();
            $imageInfo = @getimagesize($ktpTempPath);
            if ($imageInfo === false) {
                return response()->json(['status' => 'error', 'message' => 'File KTP bukan gambar yang valid atau corrupt.'], 422);
            }
            [$width, $height] = $imageInfo;
            if ($width < 600 || $height < 400) {
                return response()->json(['status' => 'error', 'message' => "Dimensi KTP terlalu kecil. Minimal 600x400 px, saat ini {$width}x{$height} px."], 422);
            }
        }

        if ($request->hasFile('selfie_image')) {
            $selfieTempPath = $request->file('selfie_image')->getRealPath();
            $imageInfo = @getimagesize($selfieTempPath);
            if ($imageInfo === false) {
                return response()->json(['status' => 'error', 'message' => 'File Selfie bukan gambar yang valid atau corrupt.'], 422);
            }
            [$width, $height] = $imageInfo;
            if ($width < 400 || $height < 400) {
                return response()->json(['status' => 'error', 'message' => "Dimensi Selfie terlalu kecil. Minimal 400x400 px, saat ini {$width}x{$height} px."], 422);
            }
        }

        DB::beginTransaction();
        try {
            $nikSanitized = preg_replace('/[^a-zA-Z0-9]/', '', $profile->nik);

            // Upload KTP baru jika ada
            if ($request->hasFile('ktp_image')) {
                if ($profile->ktp_image_path) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $profile->ktp_image_path));
                }
                $ktpPath = $request->file('ktp_image')->storeAs(
                    'documents',
                    $nikSanitized . '_ktp_image_' . time() . '.' . $request->file('ktp_image')->extension(),
                    'public'
                );
                $profile->ktp_image_path = '/storage/' . $ktpPath;
            }

            // Upload Selfie baru jika ada
            if ($request->hasFile('selfie_image')) {
                if ($profile->selfie_image_path) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $profile->selfie_image_path));
                }
                $selfiePath = $request->file('selfie_image')->storeAs(
                    'documents',
                    $nikSanitized . '_selfie_image_' . time() . '.' . $request->file('selfie_image')->extension(),
                    'public'
                );
                $profile->selfie_image_path = '/storage/' . $selfiePath;
            }

            // Update status KYC jika disediakan
            if ($request->filled('kyc_status')) {
                $profile->kyc_status = $request->kyc_status;
                if ($request->kyc_status === 'VERIFIED') {
                    $profile->kyc_verified_at = now();
                    $profile->kyc_verified_by = Auth::id();
                }
            }
            if ($request->has('kyc_notes')) {
                $profile->kyc_notes = $request->kyc_notes;
            }

            $profile->save();

            // Notifikasi ke nasabah
            $statusMessages = [
                'VERIFIED' => 'Dokumen KYC Anda telah diverifikasi. Terima kasih.',
                'REJECTED' => 'Dokumen KYC Anda ditolak. Alasan: ' . ($request->kyc_notes ?: 'Tidak memenuhi syarat.') . '. Silakan upload ulang.',
                'PENDING' => 'Dokumen KYC Anda sedang dalam proses review.',
            ];
            $this->notificationService->notifyUser(
                $customer->id,
                'Status Dokumen KYC',
                $statusMessages[$request->kyc_status] ?? 'Dokumen KYC Anda telah diperbarui oleh admin.'
            );

            // Log audit
            $this->logService->logAudit('KYC_DOCUMENTS_UPDATED_BY_ADMIN', 'customer_profiles', $customer->id, [], [
                'customer_name' => $customer->full_name,
                'kyc_status' => $profile->kyc_status,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen KYC nasabah berhasil diperbarui.',
                'data' => [
                    'kyc_status' => $profile->kyc_status,
                    'ktp_image_path' => $profile->ktp_image_path,
                    'selfie_image_path' => $profile->selfie_image_path,
                    'kyc_notes' => $profile->kyc_notes,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui dokumen KYC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin approve/reject dokumen KYC nasabah.
     */
    public function reviewKyc(Request $request, $id): JsonResponse
    {
        $request->validate([
            'kyc_status' => 'required|in:VERIFIED,REJECTED',
            'kyc_notes' => 'sometimes|nullable|string|max:500',
        ], [
            'kyc_status.required' => 'Status KYC wajib diisi.',
            'kyc_status.in' => 'Status KYC harus VERIFIED atau REJECTED.',
        ]);

        $user = Auth::user();

        // Unit access check
        if (!$this->canAccessCustomer($user, (int) $id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses ke data nasabah ini.'
            ], 403);
        }

        $customer = User::where('role_id', 9)->with('customerProfile')->findOrFail($id);
        $profile = $customer->customerProfile;

        if (!$profile) {
            return response()->json(['status' => 'error', 'message' => 'Profil nasabah tidak ditemukan.'], 404);
        }

        $profile->kyc_status = $request->kyc_status;
        $profile->kyc_notes = $request->kyc_notes;
        if ($request->kyc_status === 'VERIFIED') {
            $profile->kyc_verified_at = now();
            $profile->kyc_verified_by = Auth::id();
        } else {
            $profile->kyc_verified_at = null;
            $profile->kyc_verified_by = null;
        }
        $profile->save();

        // Notifikasi ke nasabah
        $statusMessages = [
            'VERIFIED' => 'Selamat! Dokumen KYC Anda telah disetujui. Akun Anda sudah terverifikasi penuh.',
            'REJECTED' => 'Dokumen KYC Anda ditolak. Alasan: ' . ($request->kyc_notes ?: 'Tidak memenuhi syarat.') . '. Silakan upload ulang dokumen yang benar.',
        ];
        $this->notificationService->notifyUser(
            $customer->id,
            'Verifikasi Dokumen KYC',
            $statusMessages[$request->kyc_status]
        );

        // Log audit
        $this->logService->logAudit('KYC_REVIEWED', 'customer_profiles', $customer->id, [], [
            'customer_name' => $customer->full_name,
            'kyc_status' => $request->kyc_status,
            'kyc_notes' => $request->kyc_notes,
            'reviewed_by' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status KYC nasabah berhasil diperbarui.',
            'data' => [
                'kyc_status' => $profile->kyc_status,
                'kyc_notes' => $profile->kyc_notes,
            ]
        ]);
    }
}
