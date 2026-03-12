<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnitController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Get units list
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Super Admin and Branch Head can access
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $query = Unit::with('parent');

        // Filter by type if specified
        if ($request->has('unit_type')) {
            $query->where('unit_type', $request->unit_type);
        }

        // Filter by parent if specified
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        $units = $query->orderBy('unit_type')
            ->orderBy('unit_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $units
        ]);
    }

    /**
     * Get unit details
     */
    public function show($id): JsonResponse
    {
        $unit = Unit::with(['parent', 'children'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $unit
        ]);
    }

    /**
     * Create new unit
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Super Admin and Branch Head can create units
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $request->validate([
            'unit_name' => 'required|string|max:255',
            'unit_type' => 'required|in:CABANG,UNIT',
            'parent_id' => 'required_if:unit_type,UNIT|exists:units,id',
            'address' => 'sometimes|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'is_active' => 'sometimes|boolean'
        ]);

        // Validate unit type rules
        if ($request->unit_type === 'UNIT' && empty($request->parent_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit harus berada di bawah sebuah Cabang.'
            ], 400);
        }

        try {
            $unitData = [
                'unit_name' => $request->unit_name,
                'unit_type' => $request->unit_type,
                'parent_id' => $request->unit_type === 'CABANG' ? null : $request->parent_id,
                'is_active' => $request->is_active ?? true
            ];

            // Add location data for branches
            if ($request->unit_type === 'CABANG') {
                $unitData['address'] = $request->address;
                $unitData['latitude'] = $request->latitude;
                $unitData['longitude'] = $request->longitude;
            }

            $unit = Unit::create($unitData);

            // Log unit creation
            $this->logService->logAudit('UNIT_CREATED', 'units', $unit->id, [], $unitData);

            return response()->json([
                'status' => 'success',
                'message' => 'Unit/Cabang baru berhasil ditambahkan.',
                'data' => $unit->fresh(['parent'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update unit
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        
        // Only Super Admin and Branch Head can update units
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $unit = Unit::findOrFail($id);

        $request->validate([
            'unit_name' => 'sometimes|string|max:255',
            'unit_type' => 'sometimes|in:CABANG,UNIT',
            'parent_id' => 'required_if:unit_type,UNIT|exists:units,id',
            'address' => 'sometimes|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'is_active' => 'sometimes|boolean'
        ]);

        $oldData = $unit->toArray();

        $updateData = $request->only([
            'unit_name', 'unit_type', 'parent_id', 'address', 
            'latitude', 'longitude', 'is_active'
        ]);

        // Handle unit type change
        if ($request->has('unit_type')) {
            if ($request->unit_type === 'CABANG') {
                $updateData['parent_id'] = null;
            } elseif ($request->unit_type === 'UNIT' && empty($request->parent_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit harus berada di bawah sebuah Cabang.'
                ], 400);
            }
        }

        $unit->update($updateData);

        // Log unit update
        $this->logService->logAudit('UNIT_UPDATED', 'units', $unit->id, $oldData, $unit->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Data unit berhasil diperbarui.',
            'data' => $unit->fresh(['parent'])
        ]);
    }

    /**
     * Delete unit
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        
        // Only Super Admin can delete units
        if ($user->role_id !== 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $unit = Unit::findOrFail($id);

        // Check if unit has children
        if ($unit->children()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus unit yang masih memiliki sub-unit.'
            ], 400);
        }

        // Check if unit has assigned staff
        if ($unit->users()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus unit yang masih memiliki staf.'
            ], 400);
        }

        $oldData = $unit->toArray();
        $unit->delete();

        // Log unit deletion
        $this->logService->logAudit('UNIT_DELETED', 'units', $id, $oldData, []);

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil dihapus.'
        ]);
    }

    /**
     * Get branches only
     */
    public function getBranches(): JsonResponse
    {
        $branches = Unit::where('unit_type', 'CABANG')
            ->where('is_active', true)
            ->orderBy('unit_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $branches
        ]);
    }

    /**
     * Get units by branch
     */
    public function getUnitsByBranch($branchId): JsonResponse
    {
        $units = Unit::where('parent_id', $branchId)
            ->where('is_active', true)
            ->orderBy('unit_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $units
        ]);
    }
}