<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Get active announcements for users
     */
    public function getActiveAnnouncements(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $type = $request->input('type');

        $query = Announcement::active()
            ->current()
            ->with('creator:id,full_name');

        if ($type) {
            $query->byType($type);
        }

        $totalRecords = $query->count();
        $announcements = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        // Add additional info
        $announcements->each(function ($announcement) {
            $announcement->days_remaining = $announcement->days_remaining;
            $announcement->is_current = $announcement->is_current;
        });

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
     * Get announcement by ID
     */
    public function show($id): JsonResponse
    {
        $announcement = Announcement::active()
            ->current()
            ->with('creator:id,full_name')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $announcement
        ]);
    }
}