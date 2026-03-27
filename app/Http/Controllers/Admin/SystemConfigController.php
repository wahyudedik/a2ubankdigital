<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SystemConfigController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get system settings
     */
    public function getSettings(Request $request)
    {
        try {
            $configs = DB::table('system_configurations')->get();
            $data = [];
            foreach ($configs as $config) {
                $data[$config->config_key] = $config->config_value;
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            $this->logService->log('system_settings_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update system configuration
     */
    public function updateConfig(Request $request)
    {
        try {
            $admin = Auth::user();
            $updatedSettings = [];

            DB::beginTransaction();

            $allowedKeys = [
                'monthly_admin_fee', 
                'transfer_fee_external', 
                'payment_qris_image_url', 
                'payment_bank_accounts', 
                'APP_DOWNLOAD_LINK_IOS', 
                'APP_DOWNLOAD_LINK_ANDROID'
            ];

            foreach ($request->all() as $key => $value) {
                if (in_array($key, $allowedKeys)) {
                    DB::table('system_configurations')->updateOrInsert(
                        ['config_key' => $key],
                        [
                            'config_value' => is_array($value) ? json_encode($value) : (string)$value,
                            'updated_at' => now()
                        ]
                    );
                    $updatedSettings[] = $key;
                }
            }

            DB::commit();

            $this->logService->log(
                'system_config_updated',
                'System configuration updated',
                $admin ? $admin->id : 1,
                ['updated_settings' => $updatedSettings]
            );

            return response()->json([
                'success' => true,
                'message' => 'System configuration updated successfully',
                'data' => []
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('system_config_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update system configuration'
            ], 500);
        }
    }

    /**
     * Get public configuration (for frontend/mobile apps)
     */
    public function getPublicConfig(Request $request)
    {
        try {
            $configs = DB::table('system_configurations')->get();
            $data = [];
            foreach ($configs as $config) {
                $data[$config->config_key] = $config->config_value;
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch public configuration'
            ], 500);
        }
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods(Request $request)
    {
        try {
            $type = $request->input('type', 'all'); // all, digital, bank_transfer, ewallet

            // Get payment methods from database
            $query = DB::table('payment_methods')
                ->where('is_active', true)
                ->orderBy('sort_order');

            if ($type !== 'all') {
                $query->where('type', $type);
            }

            $paymentMethods = $query->get()->map(function ($method) {
                return [
                    'id' => $method->id,
                    'code' => $method->code,
                    'name' => $method->name,
                    'type' => $method->type,
                    'description' => $method->description,
                    'icon_url' => $method->icon_url,
                    'fee_type' => $method->fee_type, // fixed, percentage
                    'fee_amount' => $method->fee_amount,
                    'min_amount' => $method->min_amount,
                    'max_amount' => $method->max_amount,
                    'processing_time' => $method->processing_time,
                    'is_realtime' => (bool) $method->is_realtime,
                    'supported_banks' => json_decode($method->supported_banks ?? '[]', true),
                    'additional_info' => json_decode($method->additional_info ?? '{}', true)
                ];
            });

            // Group by type
            $groupedMethods = $paymentMethods->groupBy('type');

            // Get payment method statistics
            $stats = [
                'total_methods' => $paymentMethods->count(),
                'by_type' => $groupedMethods->map->count(),
                'realtime_methods' => $paymentMethods->where('is_realtime', true)->count(),
                'most_used' => $this->getMostUsedPaymentMethods()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_methods' => $paymentMethods,
                    'grouped_methods' => $groupedMethods,
                    'statistics' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment methods'
            ], 500);
        }
    }

    /**
     * Parse setting value based on type
     */
    private function parseSettingValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_decode($value, true);
            case 'array':
                return explode(',', $value);
            default:
                return $value;
        }
    }

    /**
     * Format setting value for storage
     */
    private function formatSettingValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            case 'array':
                return is_array($value) ? implode(',', $value) : $value;
            default:
                return (string) $value;
        }
    }

    /**
     * Validate setting value
     */
    private function validateSettingValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return ['valid' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false']), 'message' => 'Must be boolean'];
            case 'integer':
                return ['valid' => is_numeric($value), 'message' => 'Must be integer'];
            case 'float':
                return ['valid' => is_numeric($value), 'message' => 'Must be numeric'];
            case 'json':
                json_decode(json_encode($value));
                return ['valid' => json_last_error() === JSON_ERROR_NONE, 'message' => 'Must be valid JSON'];
            default:
                return ['valid' => true, 'message' => ''];
        }
    }

    /**
     * Handle special settings that require additional actions
     */
    private function handleSpecialSetting($key, $value)
    {
        switch ($key) {
            case 'maintenance_mode':
                if ($value) {
                    // Enable maintenance mode
                    Cache::put('maintenance_mode', true, 3600);
                } else {
                    Cache::forget('maintenance_mode');
                }
                break;
            case 'max_transaction_limit':
                Cache::forget('transaction_limits');
                break;
        }
    }

    /**
     * Check database connection status
     */
    private function checkDatabaseStatus()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'connected', 'message' => 'Database connection OK'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed'];
        }
    }

    /**
     * Check cache status
     */
    private function checkCacheStatus()
    {
        try {
            Cache::put('health_check', 'ok', 60);
            $result = Cache::get('health_check');
            return ['status' => $result === 'ok' ? 'working' : 'error', 'message' => 'Cache system OK'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Cache system failed'];
        }
    }

    /**
     * Check storage status
     */
    private function checkStorageStatus()
    {
        try {
            $testFile = 'health_check.txt';
            Storage::put($testFile, 'test');
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);
            return ['status' => $exists ? 'working' : 'error', 'message' => 'Storage system OK'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Storage system failed'];
        }
    }

    /**
     * Get most used payment methods
     */
    private function getMostUsedPaymentMethods()
    {
        return DB::table('transactions')
            ->select('payment_method', DB::raw('COUNT(*) as usage_count'))
            ->whereNotNull('payment_method')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('payment_method')
            ->orderBy('usage_count', 'desc')
            ->take(5)
            ->get();
    }
}