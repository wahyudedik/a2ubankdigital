<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SecureMessage;
use App\Models\User;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
     * Get all messages for authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);
            $status = $request->input('status', 'all');

            $query = SecureMessage::with(['sender'])
                ->where(function($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('recipient_id', $user->id);
                })
                ->orderBy('sent_at', 'desc');

            if ($status !== 'all') {
                if ($status === 'unread') {
                    $query->where('recipient_id', $user->id)
                          ->whereNull('read_at');
                } elseif ($status === 'sent') {
                    $query->where('sender_id', $user->id);
                } elseif ($status === 'received') {
                    $query->where('recipient_id', $user->id);
                }
            }

            $total = $query->count();
            $messages = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($message) use ($user) {
                    $isReceived = $message->recipient_id === $user->id;
                    return [
                        'id' => $message->id,
                        'thread_id' => $message->thread_id,
                        'sender' => [
                            'id' => $message->sender ? $message->sender->id : null,
                            'name' => $message->sender ? ($message->sender->full_name ?? $message->sender->email) : 'Unknown',
                            'type' => $message->sender && $message->sender->role_id === 9 ? 'customer' : 'admin'
                        ],
                        'message' => $message->message,
                        'sent_at' => $message->sent_at?->toISOString(),
                        'is_received' => $isReceived,
                        'is_read' => $message->read_at !== null,
                        'read_at' => $message->read_at?->toISOString()
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'messages' => $messages,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit)
                    ],
                    'summary' => [
                        'total_messages' => $total,
                        'unread_count' => SecureMessage::where('recipient_id', $user->id)
                            ->whereNull('read_at')
                            ->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch messages'
            ], 500);
        }
    }

    /**
     * Send a new message to admin
     */
    public function send(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:2000',
                'subject' => 'nullable|string|max:200'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            // Find an admin to send message to (preferably customer service)
            $admin = User::whereIn('role_id', [6, 2, 1]) // CS, Admin, Super Admin
                ->where('status', 'ACTIVE')
                ->orderByRaw('FIELD(role_id, 6, 2, 1)') // Prioritize CS
                ->first();

            if (!$admin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No admin available to receive messages'
                ], 400);
            }

            DB::beginTransaction();

            // Generate thread ID
            $threadId = SecureMessage::generateThreadId($user->id, $admin->id);

            // Create message
            $message = SecureMessage::create([
                'thread_id' => $threadId,
                'sender_id' => $user->id,
                'recipient_id' => $admin->id,
                'message' => $request->message,
                'sent_at' => now()
            ]);

            // Send notification to admin
            $this->notificationService->send(
                $admin->id,
                'New Message from Customer',
                "You have received a new message from {$user->full_name}",
                'message',
                ['message_id' => $message->id]
            );

            DB::commit();

            // Log the action
            $this->logService->log(
                'customer_message_sent',
                "Message sent to admin",
                $user->id,
                ['message_id' => $message->id, 'admin_id' => $admin->id]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully',
                'data' => [
                    'message_id' => $message->id,
                    'thread_id' => $threadId,
                    'sent_at' => $message->sent_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('customer_message_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            
            $message = SecureMessage::where('id', $id)
                ->where('recipient_id', $user->id)
                ->first();

            if (!$message) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Message not found or not authorized'
                ], 404);
            }

            if (!$message->read_at) {
                $message->update(['read_at' => now()]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Message marked as read',
                'data' => [
                    'message_id' => $message->id,
                    'read_at' => $message->read_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark message as read'
            ], 500);
        }
    }

    /**
     * Get message thread
     */
    public function getThread(Request $request)
    {
        try {
            $user = Auth::user();
            $threadId = $request->input('thread_id');

            if (!$threadId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Thread ID is required'
                ], 422);
            }

            $messages = SecureMessage::with(['sender', 'recipient'])
                ->where('thread_id', $threadId)
                ->where(function($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('recipient_id', $user->id);
                })
                ->orderBy('sent_at', 'asc')
                ->get()
                ->map(function ($message) use ($user) {
                    return [
                        'id' => $message->id,
                        'sender' => [
                            'id' => $message->sender ? $message->sender->id : null,
                            'name' => $message->sender ? ($message->sender->full_name ?? $message->sender->email) : 'Unknown',
                            'type' => $message->sender && $message->sender->role_id === 9 ? 'customer' : 'admin'
                        ],
                        'message' => $message->message,
                        'sent_at' => $message->sent_at?->toISOString(),
                        'is_mine' => $message->sender_id === $user->id,
                        'is_read' => $message->read_at !== null,
                        'read_at' => $message->read_at?->toISOString()
                    ];
                });

            // Mark unread messages in this thread as read
            SecureMessage::where('thread_id', $threadId)
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'thread_id' => $threadId,
                    'messages' => $messages,
                    'total_messages' => $messages->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch thread messages'
            ], 500);
        }
    }
}
