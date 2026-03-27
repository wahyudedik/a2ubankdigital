<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user notifications
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);
        $unreadOnly = $request->input('unread_only', false);

        $query = Notification::where('user_id', Auth::id());

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        $totalRecords = $query->count();
        $unreadCount = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        $notifications = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords,
                'unread_count' => (int)$unreadCount
            ]
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notifikasi tidak ditemukan.'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi ditandai sebagai sudah dibaca.'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Semua notifikasi ditandai sebagai sudah dibaca.'
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy($id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notifikasi tidak ditemukan.'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi berhasil dihapus.'
        ]);
    }

    /**
     * Get unread count
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => ['unread_count' => $count]
        ]);
    }
}
