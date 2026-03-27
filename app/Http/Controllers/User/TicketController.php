<?php

namespace App\Http\Controllers\User;

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
     * Get user's tickets
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $status = $request->input('status');

        $query = Ticket::where('user_id', Auth::id())
            ->with(['messages' => function($q) {
                $q->latest()->limit(1);
            }]);

        if ($status) {
            $query->where('status', $status);
        }

        $totalRecords = $query->count();
        $tickets = $query
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
     * Get ticket details with messages
     */
    public function show($id): JsonResponse
    {
        $ticket = Ticket::where('user_id', Auth::id())
            ->with(['messages.sender'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $ticket
        ]);
    }

    /**
     * Create new ticket
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|in:GENERAL,TECHNICAL,COMPLAINT,SUGGESTION',
            'priority' => 'required|in:LOW,MEDIUM,HIGH',
            'message' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            // Create ticket
            $ticket = Ticket::create([
                'user_id' => Auth::id(),
                'ticket_number' => 'TKT-' . time() . '-' . rand(1000, 9999),
                'subject' => $request->subject,
                'category' => $request->category,
                'priority' => $request->priority,
                'status' => 'OPEN'
            ]);

            // Create first message
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_id' => Auth::id(),
                'message' => $request->message,
                'is_from_customer' => true
            ]);

            // Notify CS staff
            $this->notificationService->notifyStaffByRole(
                [6], // CS role
                'Tiket Baru',
                'Tiket baru dari ' . Auth::user()->full_name . ' dengan subjek: ' . $request->subject
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tiket berhasil dibuat.',
                'data' => $ticket->fresh(['messages'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat tiket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reply to ticket
     */
    public function reply(Request $request, $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $ticket = Ticket::where('user_id', Auth::id())
            ->findOrFail($id);

        if ($ticket->status === 'CLOSED') {
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
                'sender_id' => Auth::id(),
                'message' => $request->message,
                'is_from_customer' => true
            ]);

            // Update ticket status to open if it was waiting
            if ($ticket->status === 'WAITING_CUSTOMER') {
                $ticket->update(['status' => 'OPEN']);
            }

            // Notify CS staff
            $this->notificationService->notifyStaffByRole(
                [6], // CS role
                'Balasan Tiket',
                'Nasabah membalas tiket #' . $ticket->ticket_number
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
    public function close($id): JsonResponse
    {
        $ticket = Ticket::where('user_id', Auth::id())
            ->findOrFail($id);

        if ($ticket->status === 'CLOSED') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tiket sudah ditutup.'
            ], 400);
        }

        $ticket->update([
            'status' => 'CLOSED',
            'closed_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tiket berhasil ditutup.'
        ]);
    }
}