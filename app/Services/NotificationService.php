<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class NotificationService
{
    private function sendWebPush(int $userId, string $title, string $message): void
    {
        try {
            $subscriptions = PushSubscription::where('user_id', $userId)->get();
            if ($subscriptions->isEmpty()) return;

            $vapidPublicKey = config('services.vapid.public_key');
            $vapidPrivateKey = config('services.vapid.private_key');
            if (!$vapidPublicKey || !$vapidPrivateKey) return;

            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('app.url', 'https://a2ubankdigital.my.id'),
                    'publicKey' => $vapidPublicKey,
                    'privateKey' => $vapidPrivateKey,
                ],
            ]);

            $payload = json_encode(['title' => $title, 'body' => $message, 'icon' => '/a2u-icon.png', 'url' => '/notifications']);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create(['endpoint' => $sub->endpoint, 'publicKey' => $sub->p256dh, 'authToken' => $sub->auth]),
                    $payload
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Web push failed: ' . $e->getMessage());
        }
    }

    public function notifyUser(int $userId, string $title, string $message): void
    {
        try {
            Notification::create(['user_id' => $userId, 'title' => $title, 'message' => $message, 'is_read' => false]);
            $this->sendWebPush($userId, $title, $message);
        } catch (\Exception $e) {
            Log::error('Notification failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    public function notifyStaffByRole(array $roleIds, string $title, string $message): void
    {
        if (empty($roleIds)) return;
        try {
            foreach (User::whereIn('role_id', $roleIds)->where('status', 'ACTIVE')->pluck('id') as $id) {
                Notification::create(['user_id' => $id, 'title' => $title, 'message' => $message, 'is_read' => false]);
            }
        } catch (\Exception $e) {
            Log::error('Staff notification failed', ['error' => $e->getMessage()]);
        }
    }

    public function notifyAllUsers(string $title, string $message): void
    {
        try {
            foreach (User::where('status', 'ACTIVE')->pluck('id') as $id) {
                Notification::create(['user_id' => $id, 'title' => $title, 'message' => $message, 'is_read' => false]);
            }
        } catch (\Exception $e) {
            Log::error('Broadcast notification failed', ['error' => $e->getMessage()]);
        }
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $n = Notification::where('id', $notificationId)->where('user_id', $userId)->first();
        if ($n) { $n->update(['is_read' => true]); return true; }
        return false;
    }

    // Alias methods for backward compatibility
    public function send(int $userId, string $title, string $message, $extra = null): void { $this->notifyUser($userId, $title, $message); }
    public function notify(int $userId, string $title, string $message, $extra = null): void { $this->notifyUser($userId, $title, $message); }
    public function notifyAdmins(string $title, string $message, $extra = null): void { $this->notifyStaffByRole([1, 2], $title, $message); }
    public function sendToRole(string $role, string $title, string $message, $extra = null): void
    {
        $map = ['debt_collection_supervisor' => [3], 'debt_collection_manager' => [2, 3], 'admin' => [1, 2]];
        $this->notifyStaffByRole($map[$role] ?? [1], $title, $message);
    }
    public function sendUrgentNotification($user, string $title, string $message, $extra = null): void
    {
        $this->notifyUser(is_object($user) ? $user->id : $user, '[URGENT] ' . $title, $message);
    }
}
