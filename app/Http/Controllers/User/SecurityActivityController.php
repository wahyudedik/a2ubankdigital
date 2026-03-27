<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SecurityActivityController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get user login history
     */
    public function getLoginHistory(Request $request)
    {
        try {
            $user = Auth::user();
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);
            $days = $request->input('days', 30); // Default last 30 days

            $query = DB::table('user_sessions')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays($days))
                ->orderBy('created_at', 'desc');

            $total = $query->count();
            $sessions = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'login_time' => $session->created_at,
                        'logout_time' => $session->ended_at,
                        'ip_address' => $session->ip_address,
                        'user_agent' => $session->user_agent,
                        'device_info' => $this->parseUserAgent($session->user_agent),
                        'location' => $session->location ?? 'Unknown',
                        'status' => $session->ended_at ? 'Ended' : 'Active',
                        'duration' => $session->ended_at 
                            ? $this->calculateDuration($session->created_at, $session->ended_at)
                            : $this->calculateDuration($session->created_at, now())
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'login_history' => $loginHistory,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('login_history_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch login history'
            ], 500);
        }
    }

    /**
     * Get user security activity
     */
    public function getSecurityActivity(Request $request)
    {
        try {
            $user = Auth::user();
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);
            $days = $request->input('days', 30);

            // Get security-related activities from audit logs
            $query = DB::table('audit_logs')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays($days))
                ->whereIn('action', [
                    'login_success',
                    'login_failed',
                    'password_changed',
                    'pin_changed',
                    '2fa_enabled',
                    '2fa_disabled',
                    'device_approved',
                    'session_terminated',
                    'security_question_updated'
                ])
                ->orderBy('created_at', 'desc');

            $total = $query->count();
            $activities = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'action' => $activity->action,
                        'description' => $this->getActivityDescription($activity->action),
                        'ip_address' => $activity->ip_address ?? 'Unknown',
                        'user_agent' => $activity->user_agent ?? 'Unknown',
                        'device_info' => $this->parseUserAgent($activity->user_agent ?? ''),
                        'location' => $activity->location ?? 'Unknown',
                        'timestamp' => $activity->created_at,
                        'risk_level' => $this->assessRiskLevel($activity->action, $activity->ip_address),
                        'additional_data' => json_decode($activity->additional_data ?? '{}', true)
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'activities' => $activities,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit)
                    ],
                    'summary' => [
                        'total_activities' => $total,
                        'high_risk_count' => $activities->where('risk_level', 'high')->count(),
                        'medium_risk_count' => $activities->where('risk_level', 'medium')->count(),
                        'low_risk_count' => $activities->where('risk_level', 'low')->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('security_activity_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch security activity'
            ], 500);
        }
    }

    /**
     * Parse user agent string to extract device information
     */
    private function parseUserAgent($userAgent)
    {
        if (empty($userAgent)) {
            return [
                'device' => 'Unknown',
                'browser' => 'Unknown',
                'os' => 'Unknown'
            ];
        }

        $device = 'Desktop';
        $browser = 'Unknown';
        $os = 'Unknown';

        // Detect mobile devices
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $device = 'Mobile';
        } elseif (preg_match('/Tablet|iPad/', $userAgent)) {
            $device = 'Tablet';
        }

        // Detect browser
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Chrome ' . $matches[1];
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Firefox ' . $matches[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Safari ' . $matches[1];
        } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent, $matches)) {
            $browser = 'Edge ' . $matches[1];
        }

        // Detect OS
        if (preg_match('/Windows NT ([0-9.]+)/', $userAgent, $matches)) {
            $os = 'Windows ' . $matches[1];
        } elseif (preg_match('/Mac OS X ([0-9_]+)/', $userAgent, $matches)) {
            $os = 'macOS ' . str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Android ([0-9.]+)/', $userAgent, $matches)) {
            $os = 'Android ' . $matches[1];
        } elseif (preg_match('/iPhone OS ([0-9_]+)/', $userAgent, $matches)) {
            $os = 'iOS ' . str_replace('_', '.', $matches[1]);
        }

        return [
            'device' => $device,
            'browser' => $browser,
            'os' => $os
        ];
    }

    /**
     * Calculate duration between two timestamps
     */
    private function calculateDuration($start, $end)
    {
        $startTime = \Carbon\Carbon::parse($start);
        $endTime = \Carbon\Carbon::parse($end);
        
        $diff = $startTime->diff($endTime);
        
        if ($diff->days > 0) {
            return $diff->days . ' days, ' . $diff->h . ' hours';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hours, ' . $diff->i . ' minutes';
        } else {
            return $diff->i . ' minutes';
        }
    }

    /**
     * Get human-readable description for security activities
     */
    private function getActivityDescription($action)
    {
        $descriptions = [
            'login_success' => 'Successful login',
            'login_failed' => 'Failed login attempt',
            'password_changed' => 'Password changed',
            'pin_changed' => 'PIN changed',
            '2fa_enabled' => 'Two-factor authentication enabled',
            '2fa_disabled' => 'Two-factor authentication disabled',
            'device_approved' => 'New device approved',
            'session_terminated' => 'Session terminated',
            'security_question_updated' => 'Security questions updated'
        ];

        return $descriptions[$action] ?? 'Unknown security activity';
    }

    /**
     * Assess risk level based on activity type and context
     */
    private function assessRiskLevel($action, $ipAddress)
    {
        // High risk activities
        $highRiskActions = ['login_failed', 'device_approved', '2fa_disabled'];
        
        // Medium risk activities
        $mediumRiskActions = ['password_changed', 'pin_changed', 'session_terminated'];
        
        // Low risk activities
        $lowRiskActions = ['login_success', '2fa_enabled', 'security_question_updated'];

        if (in_array($action, $highRiskActions)) {
            return 'high';
        } elseif (in_array($action, $mediumRiskActions)) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}