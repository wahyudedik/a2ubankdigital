<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification to users by role IDs
     *
     * @param array $roleIds
     * @param string $title
     * @param string $message
     * @return void
     */
    public function notifyStaffByRole(array $roleIds, string $title, string $message): void
    {
        if (empty($roleIds)) {
            return;
        }

        try {
            $staffIds = User::whereIn('role_id', $roleIds)
                ->where('status', 'ACTIVE')
                ->pluck('id');

            foreach ($staffIds as $staffId) {
                Notification::create([
                    'user_id' => $staffId,
                    'title' => $title,
                    'message' => $message,
                    'is_read' => false
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send notification by role', [
                'role_ids' => $roleIds,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification hierarchically from staff to supervisors
     *
     * @param int $originatorUserId
     * @param array|int $targetRoleId
     * @param string $title
     * @param string $message
     * @return void
     */
    public function notifyStaffHierarchically(int $originatorUserId, $targetRoleId, string $title, string $message): void
    {
        $notifiedUsers = [];
        $targetRoles = is_array($targetRoleId) ? $targetRoleId : [$targetRoleId];

        try {
            // 1. Get originator's unit
            $originator = User::find($originatorUserId);
            if (!$originator || !$originator->unit_id) {
                return;
            }

            // 2. Notify implementers in same unit
            $implementerIds = User::where('unit_id', $originator->unit_id)
                ->whereIn('role_id', $targetRoles)
                ->where('status', 'ACTIVE')
                ->pluck('id');

            foreach ($implementerIds as $id) {
                $notifiedUsers[$id] = true;
            }

            // 3. Notify supervisors
            $supervisors = $this->getSupervisorIds($originatorUserId);
            if ($supervisors['unit_head_id']) {
                $notifiedUsers[$supervisors['unit_head_id']] = true;
            }
            if ($supervisors['branch_head_id']) {
                $notifiedUsers[$supervisors['branch_head_id']] = true;
            }

            // 4. Always notify super admin (role_id = 1)
            $superAdminId = User::where('role_id', 1)->where('status', 'ACTIVE')->first()?->id;
            if ($superAdminId) {
                $notifiedUsers[$superAdminId] = true;
            }

            // 5. Send notifications
            foreach (array_keys($notifiedUsers) as $staffId) {
                Notification::create([
                    'user_id' => $staffId,
                    'title' => $title,
                    'message' => $message,
                    'is_read' => false
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send hierarchical notification', [
                'originator_user_id' => $originatorUserId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get supervisor IDs for a user
     *
     * @param int $userId
     * @return array
     */
    private function getSupervisorIds(int $userId): array
    {
        $user = User::with('customerProfile')->find($userId);
        
        if (!$user || !$user->unit_id) {
            return ['unit_head_id' => null, 'branch_head_id' => null];
        }

        // Get unit head (role_id = 3)
        $unitHead = User::where('unit_id', $user->unit_id)
            ->where('role_id', 3)
            ->where('status', 'ACTIVE')
            ->first();

        // Get branch head (role_id = 2) - assuming unit belongs to a branch
        $branchHead = User::where('role_id', 2)
            ->where('status', 'ACTIVE')
            ->first();

        return [
            'unit_head_id' => $unitHead?->id,
            'branch_head_id' => $branchHead?->id
        ];
    }

    /**
     * Send notification to specific user
     *
     * @param int $userId
     * @param string $title
     * @param string $message
     * @return void
     */
    public function notifyUser(int $userId, string $title, string $message): void
    {
        try {
            Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'is_read' => false
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send notification to user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification to all active users
     *
     * @param string $title
     * @param string $message
     * @return void
     */
    public function notifyAllUsers(string $title, string $message): void
    {
        try {
            $activeUserIds = User::where('status', 'ACTIVE')->pluck('id');

            foreach ($activeUserIds as $userId) {
                Notification::create([
                    'user_id' => $userId,
                    'title' => $title,
                    'message' => $message,
                    'is_read' => false
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send notification to all users', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            $notification = Notification::where('id', $notificationId)
                ->where('user_id', $userId)
                ->first();

            if ($notification) {
                $notification->update(['is_read' => true]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
