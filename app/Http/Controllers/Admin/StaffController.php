<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get staff list
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Super Admin, Branch Head, Unit Head can access
        if (!in_array($user->role_id, [1, 2, 3])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);

        $query = User::with(['role', 'unit'])
            ->where('role_id', '!=', 9); // Exclude customers

        // Data scoping based on role
        if ($user->role_id !== 1) { // Not super admin
            $query->where('role_id', '>', $user->role_id);
        }

        $totalRecords = $query->count();
        $staff = $query
            ->orderBy('role_id')
            ->orderBy('full_name')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        // Add can_edit flag
        $staff->each(function ($staffMember) use ($user) {
            $canEdit = false;
            
            if ($user->role_id === 1 && $staffMember->role_id > 1) {
                $canEdit = true;
            } elseif ($user->role_id === 2 && $staffMember->role_id > 2) {
                $canEdit = true;
            } elseif ($user->role_id === 3 && $staffMember->role_id > 3) {
                $canEdit = true;
            }
            
            $staffMember->can_edit = $canEdit;
        });

        return response()->json([
            'status' => 'success',
            'data' => $staff,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Get staff details
     */
    public function show($id): JsonResponse
    {
        $staff = User::with(['role', 'unit'])
            ->where('role_id', '!=', 9)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $staff
        ]);
    }

    /**
     * Create new staff
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'unit_id' => 'sometimes|exists:units,id',
            'phone_number' => 'sometimes|string'
        ]);

        $user = Auth::user();

        // Check if user can create staff with this role
        if ($user->role_id !== 1 && $request->role_id <= $user->role_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat membuat staf dengan role yang sama atau lebih tinggi.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $staff = User::create([
                'bank_id' => date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'role_id' => $request->role_id,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password_hash' => Hash::make('password123'), // Default password
                'phone_number' => $request->phone_number ?? '',
                'status' => 'ACTIVE'
            ]);

            // Log staff creation
            $this->logService->logAudit('STAFF_CREATED', 'users', $staff->id, [], [
                'created_by' => $user->id,
                'role_id' => $request->role_id
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Staf berhasil dibuat.',
                'data' => $staff->fresh(['role', 'unit'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat staf: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update staff
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role_id' => 'sometimes|exists:roles,id',
            'unit_id' => 'sometimes|exists:units,id',
            'phone_number' => 'sometimes|string'
        ]);

        $staff = User::where('role_id', '!=', 9)->findOrFail($id);
        $user = Auth::user();

        // Check permissions
        if ($user->role_id !== 1 && $staff->role_id <= $user->role_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat mengedit staf dengan role yang sama atau lebih tinggi.'
            ], 403);
        }

        $oldData = $staff->toArray();

        $staff->update($request->only([
            'full_name', 'email', 'role_id', 'unit_id', 'phone_number'
        ]));

        // Log staff update
        $this->logService->logAudit('STAFF_UPDATED', 'users', $staff->id, $oldData, $staff->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Data staf berhasil diperbarui.',
            'data' => $staff->fresh(['role', 'unit'])
        ]);
    }

    /**
     * Update staff status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:ACTIVE,BLOCKED,SUSPENDED'
        ]);

        $staff = User::where('role_id', '!=', 9)->findOrFail($id);
        $user = Auth::user();

        // Check permissions
        if ($user->role_id !== 1 && $staff->role_id <= $user->role_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat mengubah status staf dengan role yang sama atau lebih tinggi.'
            ], 403);
        }

        $oldStatus = $staff->status;
        $staff->update(['status' => $request->status]);

        // Log status change
        $this->logService->logAudit('STAFF_STATUS_CHANGED', 'users', $staff->id, 
            ['status' => $oldStatus], 
            ['status' => $request->status]
        );

        // Notify staff if status changed to blocked/suspended
        if (in_array($request->status, ['BLOCKED', 'SUSPENDED'])) {
            $this->notificationService->notifyUser(
                $staff->id,
                'Status Akun Diperbarui',
                'Status akun Anda telah diubah menjadi ' . $request->status . '.'
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status staf berhasil diperbarui.'
        ]);
    }

    /**
     * Reset staff password
     */
    public function resetPassword($id): JsonResponse
    {
        $staff = User::where('role_id', '!=', 9)->findOrFail($id);
        $user = Auth::user();

        // Check permissions
        if ($user->role_id !== 1 && $staff->role_id <= $user->role_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat mereset password staf dengan role yang sama atau lebih tinggi.'
            ], 403);
        }

        $newPassword = 'password123';
        $staff->update([
            'password_hash' => Hash::make($newPassword)
        ]);

        // Log password reset
        $this->logService->logAudit('STAFF_PASSWORD_RESET', 'users', $staff->id);

        // Notify staff
        $this->notificationService->notifyUser(
            $staff->id,
            'Password Direset',
            'Password Anda telah direset oleh admin. Password baru: ' . $newPassword
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Password staf berhasil direset.',
            'data' => ['new_password' => $newPassword]
        ]);
    }

    /**
     * Get roles
     */
    public function getRoles(): JsonResponse
    {
        $user = Auth::user();
        
        $query = Role::query();
        
        // Filter roles based on user's role
        if ($user->role_id !== 1) {
            $query->where('id', '>', $user->role_id);
        }
        
        $roles = $query->orderBy('id')->get();

        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }

    /**
     * Update staff assignment
     */
    public function updateAssignment(Request $request, $id): JsonResponse
    {
        $request->validate([
            'unit_id' => 'required|exists:units,id'
        ]);

        $staff = User::where('role_id', '!=', 9)->findOrFail($id);
        $user = Auth::user();

        // Check permissions
        if ($user->role_id !== 1 && $staff->role_id <= $user->role_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat mengubah penugasan staf dengan role yang sama atau lebih tinggi.'
            ], 403);
        }

        $oldUnitId = $staff->unit_id;
        $staff->update(['unit_id' => $request->unit_id]);

        // Log assignment change
        $this->logService->logAudit('STAFF_ASSIGNMENT_CHANGED', 'users', $staff->id, 
            ['unit_id' => $oldUnitId], 
            ['unit_id' => $request->unit_id]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Penugasan staf berhasil diperbarui.'
        ]);
    }
}