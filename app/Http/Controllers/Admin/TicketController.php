<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all tickets
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $status = $request->input('status');
        $priority = $request->input('priority');
        $category = $request->input('category');

        $query = Ticket::with(['user', 'assignedTo', 'messages' => function($q) {
            $q->latest()->limit(1);
        }]);

        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($category) {
            $query->where('category', $category);
        }

        $totalRecords = $query->count();
        $tickets = $query
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $tickets,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Get ticket details with all messages
     */
    public function show($id): JsonResponse
    {
        $ticket = Ticket::with(['user', 'assignedTo', 'messages.sender'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $ticket
        ]);
    }

    /**
     * Assign ticket to staff
     */
    public function assign(Request $request, $id): JsonResponse
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $ticket = Ticket::findOrFail($id);

        $ticket->update([
            'status' => 'in_progress'
        ]);

        // Notify assigned staff
        $this->notificationService->notifyUser(
            $request->assigned_to,
            'Tiket Ditugaskan',
            'Anda ditugaskan untuk menangani tiket #' . $ticket->ticket_number
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Tiket berhasil ditugaskan.',
            'data' => $ticket->fresh(['assignedTo'])
        ]);
    }

    /**
     * Reply to ticket
     */
    public function reply(Request $request, $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $ticket = Ticket::findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat membalas tiket yang sudah ditutup.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Create message
            $message = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'message' => $request->message
            ]);

            // Update ticket status
            $ticket->update([
                'status' => 'in_progress'
            ]);

            // Notify customer
            $this->notificationService->notifyUser(
                $ticket->user_id,
                'Balasan Tiket',
                'Tim support telah membalas tiket #' . $ticket->ticket_number
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Balasan berhasil dikirim.',
                'data' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim balasan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close ticket
     */
    public function close(Request $request, $id): JsonResponse
    {
        $request->validate([
            'resolution' => 'sometimes|string'
        ]);

        $ticket = Ticket::findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tiket sudah ditutup.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $ticket->update([
                'status' => 'closed'
            ]);

            // Add resolution message if provided
            if ($request->resolution) {
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'message' => 'Tiket ditutup. Resolusi: ' . $request->resolution
                ]);
            }

            // Notify customer
            $this->notificationService->notifyUser(
                $ticket->user_id,
                'Tiket Ditutup',
                'Tiket #' . $ticket->ticket_number . ' telah ditutup oleh tim support.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tiket berhasil ditutup.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menutup tiket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ticket statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_tickets' => Ticket::count(),
            'open_tickets' => Ticket::where('status', 'open')->count(),
            'in_progress_tickets' => Ticket::where('status', 'in_progress')->count(),
            'closed_tickets' => Ticket::where('status', 'closed')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}