<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SecureMessage;
use App\Models\User;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecureMessageController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Send secure message
     */
    public function sendSecureMessage(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000'
        ]);

        $user = Auth::user();

        // Verify recipient exists and is not the sender
        if ($request->recipient_id == $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat mengirim pesan ke diri sendiri.'
            ], 400);
        }

        $recipient = User::findOrFail($request->recipient_id);

        try {
            $message = SecureMessage::sendMessage(
                $user->id,
                $request->recipient_id,
                $request->message
            );

            // Log secure message
            $this->logService->logAudit('SECURE_MESSAGE_SENT', 'secure_messages', $message->id, [], [
                'recipient_id' => $request->recipient_id,
                'message_length' => strlen($request->message)
            ]);

            // Notify recipient
            $this->notificationService->notifyUser(
                $request->recipient_id,
                'Pesan Aman Baru',
                'Anda menerima pesan aman baru dari ' . $user->full_name . '.'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Pesan berhasil dikirim.',
                'data' => $message->fresh(['sender', 'recipient'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim pesan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get message threads
     */
    public function getMessageThreads(Request $request): JsonResponse
    {
        $user = Auth::user();
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);

        // Get unique threads involving the user
        $threads = SecureMessage::select('thread_id')
            ->forUser($user->id)
            ->groupBy('thread_id')
            ->orderBy('sent_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $threadData = [];
        
        foreach ($threads as $thread) {
            // Get latest message in thread
            $latestMessage = SecureMessage::with(['sender', 'recipient'])
                ->inThread($thread->thread_id)
                ->orderBy('sent_at', 'desc')
                ->first();

            if ($latestMessage) {
                // Determine the other participant
                $otherParticipant = $latestMessage->sender_id === $user->id 
                    ? $latestMessage->recipient 
                    : $latestMessage->sender;

                // Count unread messages (messages sent to user after their last read)
                $unreadCount = SecureMessage::inThread($thread->thread_id)
                    ->where('recipient_id', $user->id)
                    ->where('sent_at', '>', $user->last_login_at ?? now()->subDays(30))
                    ->count();

                $threadData[] = [
                    'thread_id' => $thread->thread_id,
                    'other_participant' => $otherParticipant,
                    'latest_message' => $latestMessage,
                    'unread_count' => $unreadCount
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $threadData,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => count($threadData)
            ]
        ]);
    }

    /**
     * Get messages in a thread
     */
    public function getThreadMessages(Request $request): JsonResponse
    {
        $request->validate([
            'other_user_id' => 'required|exists:users,id'
        ]);

        $user = Auth::user();
        $otherUserId = $request->other_user_id;

        $messages = SecureMessage::getThreadMessages($user->id, $otherUserId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'thread_id' => SecureMessage::generateThreadId($user->id, $otherUserId),
                'messages' => $messages,
                'participants' => [
                    'current_user' => $user,
                    'other_user' => User::find($otherUserId)
                ]
            ]
        ]);
    }

    /**
     * Get available contacts (staff members for customers, customers for staff)
     */
    public function getAvailableContacts(): JsonResponse
    {
        $user = Auth::user();

        if ($user->isCustomer()) {
            // Customers can message staff members
            $contacts = User::staff()
                ->where('status', 'ACTIVE')
                ->select('id', 'full_name', 'role_id')
                ->with('role:id,role_name')
                ->orderBy('role_id')
                ->orderBy('full_name')
                ->get();
        } else {
            // Staff can message customers and other staff
            $contacts = User::where('status', 'ACTIVE')
                ->where('id', '!=', $user->id)
                ->select('id', 'full_name', 'role_id')
                ->with('role:id,role_name')
                ->orderBy('role_id')
                ->orderBy('full_name')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $contacts
        ]);
    }
}