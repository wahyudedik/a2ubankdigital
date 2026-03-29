<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SecureMessage;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DirectMessageController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Send direct message to user
     */
    public function sendDirectMessage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'subject' => 'required|string|max:200',
                'message' => 'required|string|max:2000',
                'priority' => 'nullable|in:low,normal,high,urgent',
                'message_type' => 'nullable|in:general,security,account,promotion,system'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            $user = User::find($request->user_id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if user account is active
            if ($user->status !== 'ACTIVE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send message to inactive user'
                ], 400);
            }

            DB::beginTransaction();

            // Create secure message
            $message = SecureMessage::create([
                'sender_id' => $admin->id,
                'recipient_id' => $user->id,
                'sender_type' => 'admin',
                'recipient_type' => 'user',
                'subject' => $request->subject,
                'message' => $request->message,
                'priority' => $request->priority ?? 'normal',
                'message_type' => $request->message_type ?? 'general',
                'status' => 'sent',
                'sent_at' => now()
            ]);

            // Send notification to user
            $this->notificationService->send(
                $user->id,
                'New Message from Admin',
                "You have received a new message: {$request->subject}",
                'message',
                [
                    'message_id' => $message->id,
                    'priority' => $request->priority ?? 'normal'
                ]
            );

            // If urgent priority, send additional notifications
            if ($request->priority === 'urgent') {
                $this->notificationService->sendUrgentNotification(
                    $user,
                    'Urgent Message',
                    $request->subject
                );
            }

            DB::commit();

            // Log the action
            $this->logService->log(
                'admin_direct_message_sent',
                "Direct message sent to user {$user->email}",
                $admin->id,
                [
                    'recipient_id' => $user->id,
                    'message_id' => $message->id,
                    'subject' => $request->subject,
                    'priority' => $request->priority ?? 'normal'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Direct message sent successfully',
                'data' => [
                    'message_id' => $message->id,
                    'recipient' => [
                        'id' => $user->id,
                        'name' => $user->customerProfile->full_name ?? $user->email,
                        'email' => $user->email
                    ],
                    'subject' => $request->subject,
                    'priority' => $request->priority ?? 'normal',
                    'sent_at' => $message->sent_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('admin_direct_message_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send direct message'
            ], 500);
        }
    }

    /**
     * Get sent messages by admin
     */
    public function getSentMessages(Request $request)
    {
        try {
            $admin = Auth::user();
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);
            $status = $request->input('status', 'all');
            $priority = $request->input('priority', 'all');

            $query = SecureMessage::with(['recipient.customerProfile'])
                ->where('sender_id', $admin->id)
                ->where('sender_type', 'admin')
                ->orderBy('created_at', 'desc');

            if ($status !== 'all') {
                $query->where('status', $status);
            }

            if ($priority !== 'all') {
                $query->where('priority', $priority);
            }

            $total = $query->count();
            $messages = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'recipient' => [
                            'id' => $message->recipient->id,
                            'name' => $message->recipient->customerProfile->full_name ?? $message->recipient->email,
                            'email' => $message->recipient->email
                        ],
                        'subject' => $message->subject,
                        'message' => $message->message,
                        'priority' => $message->priority,
                        'message_type' => $message->message_type,
                        'status' => $message->status,
                        'sent_at' => $message->sent_at?->toISOString(),
                        'read_at' => $message->read_at?->toISOString(),
                        'replied_at' => $message->replied_at?->toISOString()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $messages,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit)
                    ],
                    'summary' => [
                        'total_sent' => $total,
                        'unread_count' => SecureMessage::where('sender_id', $admin->id)
                            ->where('sender_type', 'admin')
                            ->whereNull('read_at')
                            ->count(),
                        'urgent_count' => SecureMessage::where('sender_id', $admin->id)
                            ->where('sender_type', 'admin')
                            ->where('priority', 'urgent')
                            ->whereNull('read_at')
                            ->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sent messages'
            ], 500);
        }
    }

    /**
     * Get message thread with user
     */
    public function getMessageThread(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            $userId = $request->user_id;

            // Get all messages between admin and user
            $messages = SecureMessage::where(function ($query) use ($admin, $userId) {
                    $query->where('sender_id', $admin->id)
                          ->where('recipient_id', $userId);
                })
                ->orWhere(function ($query) use ($admin, $userId) {
                    $query->where('sender_id', $userId)
                          ->where('recipient_id', $admin->id);
                })
                ->with(['sender.customerProfile', 'recipient.customerProfile'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->customerProfile->full_name ?? $message->sender->email,
                            'type' => $message->sender_type
                        ],
                        'recipient' => [
                            'id' => $message->recipient->id,
                            'name' => $message->recipient->customerProfile->full_name ?? $message->recipient->email,
                            'type' => $message->recipient_type
                        ],
                        'subject' => $message->subject,
                        'message' => $message->message,
                        'priority' => $message->priority,
                        'status' => $message->status,
                        'sent_at' => $message->sent_at?->toISOString(),
                        'read_at' => $message->read_at?->toISOString()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $messages,
                    'thread_info' => [
                        'total_messages' => $messages->count(),
                        'unread_from_user' => $messages->where('sender_type', 'user')
                            ->whereNull('read_at')
                            ->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch message thread'
            ], 500);
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'message_id' => 'required|integer|exists:secure_messages,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            
            $message = SecureMessage::where('id', $request->message_id)
                ->where('recipient_id', $admin->id)
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found or not authorized'
                ], 404);
            }

            if (!$message->read_at) {
                $message->update([
                    'read_at' => now(),
                    'status' => 'read'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Message marked as read',
                'data' => [
                    'message_id' => $message->id,
                    'read_at' => $message->read_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark message as read'
            ], 500);
        }
    }
}