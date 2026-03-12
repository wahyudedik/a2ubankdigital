<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get all announcements for admin
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only admin roles can manage announcements
        if (!in_array($user->role_id, [1, 2, 3, 4])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $type = $request->input('type');
        $status = $request->input('status');

        $query = Announcement::with('creator:id,full_name');

        if ($type) {
            $query->byType($type);
        }

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($status === 'current') {
            $query->current();
        }

        $totalRecords = $query->count();
        $announcements = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $announcements,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Create global announcement
     */
    public function createGlobalAnnouncement(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only admin roles can create announcements
        if (!in_array($user->role_id, [1, 2, 3, 4])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:INFO,WARNING,PROMO',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $announcement = Announcement::create([
                'title' => $request->title,
                'content' => $request->content,
                'type' => $request->type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'created_by' => $user->id,
                'is_active' => $request->is_active ?? true,
                'created_at' => now()
            ]);

            // Log announcement creation
            $this->logService->logAudit('ANNOUNCEMENT_CREATED', 'announcements', $announcement->id, [], [
                'title' => $request->title,
                'type' => $request->type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date
            ]);

            // Notify all active users if announcement is active and current
            if ($announcement->is_active && $announcement->is_current) {
                $this->notificationService->notifyAllUsers(
                    $announcement->title,
                    'Pengumuman baru: ' . substr($announcement->content, 0, 100) . '...'
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman berhasil dibuat.',
                'data' => $announcement->fresh(['creator'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat pengumuman: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update announcement
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        
        // Only admin roles can update announcements
        if (!in_array($user->role_id, [1, 2, 3, 4])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'type' => 'sometimes|in:INFO,WARNING,PROMO',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_active' => 'sometimes|boolean'
        ]);

        $oldData = $announcement->toArray();
        $announcement->update($request->all());

        // Log announcement update
        $this->logService->logAudit('ANNOUNCEMENT_UPDATED', 'announcements', $announcement->id, $oldData, $announcement->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Pengumuman berhasil diperbarui.',
            'data' => $announcement->fresh(['creator'])
        ]);
    }

    /**
     * Delete announcement
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        
        // Only Super Admin and Branch Head can delete announcements
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $announcement = Announcement::findOrFail($id);
        $oldData = $announcement->toArray();
        
        $announcement->delete();

        // Log announcement deletion
        $this->logService->logAudit('ANNOUNCEMENT_DELETED', 'announcements', $id, $oldData, []);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengumuman berhasil dihapus.'
        ]);
    }

    /**
     * Get announcement statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total' => Announcement::count(),
            'active' => Announcement::active()->count(),
            'current' => Announcement::active()->current()->count(),
            'by_type' => [
                'INFO' => Announcement::byType('INFO')->count(),
                'WARNING' => Announcement::byType('WARNING')->count(),
                'PROMO' => Announcement::byType('PROMO')->count()
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}