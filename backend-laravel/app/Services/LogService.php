<?php

namespace App\Services;

use App\Models\SystemLog;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogService
{
    /**
     * Log system event to database
     *
     * @param string $level (info, warning, error, critical)
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logSystemEvent(string $level, string $message, array $context = []): void
    {
        try {
            SystemLog::create([
                'level' => strtoupper($level),
                'message' => $message,
                'context' => json_encode($context),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write to system_logs table', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log audit trail for user actions
     *
     * @param string $action
     * @param string $tableName
     * @param int|null $recordId
     * @param array $oldValues
     * @param array $newValues
     * @return void
     */
    public function logAudit(
        string $action,
        string $tableName,
        ?int $recordId = null,
        array $oldValues = [],
        array $newValues = []
    ): void {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'table_name' => $tableName,
                'record_id' => $recordId,
                'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                'new_values' => !empty($newValues) ? json_encode($newValues) : null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write to audit_logs table', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log user login
     *
     * @param int $userId
     * @param bool $success
     * @param string|null $failureReason
     * @return void
     */
    public function logLogin(int $userId, bool $success, ?string $failureReason = null): void
    {
        $this->logAudit(
            $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED',
            'users',
            $userId,
            [],
            [
                'success' => $success,
                'failure_reason' => $failureReason,
                'timestamp' => now()->toDateTimeString()
            ]
        );
    }

    /**
     * Log transaction
     *
     * @param string $transactionType
     * @param int $transactionId
     * @param array $details
     * @return void
     */
    public function logTransaction(string $transactionType, int $transactionId, array $details): void
    {
        $this->logAudit(
            'TRANSACTION_' . strtoupper($transactionType),
            'transactions',
            $transactionId,
            [],
            $details
        );
    }

    /**
     * Log error with context
     *
     * @param string $message
     * @param \Exception $exception
     * @param array $additionalContext
     * @return void
     */
    public function logError(string $message, \Exception $exception, array $additionalContext = []): void
    {
        $context = array_merge([
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ], $additionalContext);

        $this->logSystemEvent('error', $message, $context);
    }
}
