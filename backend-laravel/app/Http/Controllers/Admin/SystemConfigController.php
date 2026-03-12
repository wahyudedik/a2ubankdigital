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
            $category = $request->input('category', 'all');

            $query = DB::table('system_settings');

            if ($category !== 'all') {
                $query->where('category', $category);
            }

            $settings = $query->get()->groupBy('category');

            // Format settings for easier consumption
            $formattedSettings = [];
            foreach ($settings as $cat => $categorySettings) {
                $formattedSettings[$cat] = [];
                foreach ($categorySettings as $setting) {
                    $formattedSettings[$cat][$setting->key] = [
                        'value' => $this->parseSettingValue($setting->value, $setting->type),
                        'type' => $setting->type,
                        'description' => $setting->description,
                        'is_public' => (bool) $setting->is_public,
                        'updated_at' => $setting->updated_at
                    ];
                }
            }

            // Get system status
            $systemStatus = [
                'maintenance_mode' => $formattedSettings['system']['maintenance_mode']['value'] ?? false,
                'api_version' => config('app.version', '1.0.0'),
                'database_status' => $this->checkDatabaseStatus(),
                'cache_status' => $this->checkCacheStatus(),
                'storage_status' => $this->checkStorageStatus()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'settings' => $formattedSettings,
                    'system_status' => $systemStatus,
                    'categories' => $settings->keys()
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('system_settings_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system settings'
            ], 500);
        }
    }

    /**
     * Update system configuration
     */
    public function updateConfig(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'required',
                'settings.*.category' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            $updatedSettings = [];

            DB::beginTransaction();

            foreach ($request->settings as $settingData) {
                // Check if setting exists
                $existingSetting = DB::table('system_settings')
                    ->where('key', $settingData['key'])
                    ->where('category', $settingData['category'])
                    ->first();

                if (!$existingSetting) {
                    return response()->json([
                        'success' => false,
                        'message' => "Setting {$settingData['key']} not found"
                    ], 404);
                }

                // Validate setting value based on type
                $validationResult = $this->validateSettingValue(
                    $settingData['value'], 
                    $existingSetting->type
                );

                if (!$validationResult['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid value for {$settingData['key']}: {$validationResult['message']}"
                    ], 400);
                }

                // Update setting
                DB::table('system_settings')
                    ->where('key', $settingData['key'])
                    ->where('category', $settingData['category'])
                    ->update([
                        'value' => $this->formatSettingValue($settingData['value'], $existingSetting->type),
                        'updated_by' => $admin->id,
                        'updated_at' => now()
                    ]);

                $updatedSettings[] = [
                    'key' => $settingData['key'],
                    'category' => $settingData['category'],
                    'old_value' => $existingSetting->value,
                    'new_value' => $settingData['value']
                ];

                // Handle special settings
                $this->handleSpecialSetting($settingData['key'], $settingData['value']);
            }

            DB::commit();

            // Clear settings cache
            Cache::forget('system_settings');

            // Log configuration changes
            $this->logService->log(
                'system_config_updated',
                'System configuration updated',
                $admin->id,
                ['updated_settings' => $updatedSettings]
            );

            // Send notification for critical changes
            $criticalSettings = ['maintenance_mode', 'max_transaction_limit', 'interest_rates'];
            $hasCriticalChanges = collect($updatedSettings)->pluck('key')->intersect($criticalSettings)->isNotEmpty();

            if ($hasCriticalChanges) {
                $this->notificationService->sendToAdmins(
                    'Critical System Configuration Changed',
                    'Important system settings have been modified. Please review the changes.',
                    'system'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'System configuration updated successfully',
                'data' => [
                    'updated_settings' => $updatedSettings,
                    'updated_by' => $admin->name ?? $admin->email,
                    'updated_at' => now()->toISOString()
                ]
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
            // Get only public settings
            $publicSettings = DB::table('system_settings')
                ->where('is_public', true)
                ->get()
                ->groupBy('category');

            $formattedSettings = [];
            foreach ($publicSettings as $category => $settings) {
                $formattedSettings[$category] = [];
                foreach ($settings as $setting) {
                    $formattedSettings[$category][$setting->key] = $this->parseSettingValue($setting->value, $setting->type);
                }
            }

            // Add system information
            $systemInfo = [
                'app_name' => config('app.name'),
                'app_version' => config('app.version', '1.0.0'),
                'api_version' => 'v1',
                'maintenance_mode' => $formattedSettings['system']['maintenance_mode'] ?? false,
                'supported_languages' => ['id', 'en'],
                'timezone' => config('app.timezone'),
                'currency' => 'IDR'
            ];

            // Add feature flags
            $featureFlags = [
                'qr_payment_enabled' => $formattedSettings['features']['qr_payment_enabled'] ?? true,
                'biometric_login_enabled' => $formattedSettings['features']['biometric_login_enabled'] ?? true,
                'goal_savings_enabled' => $formattedSettings['features']['goal_savings_enabled'] ?? true,
                'loyalty_program_enabled' => $formattedSettings['features']['loyalty_program_enabled'] ?? true,
                'external_transfer_enabled' => $formattedSettings['features']['external_transfer_enabled'] ?? true
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'system_info' => $systemInfo,
                    'settings' => $formattedSettings,
                    'feature_flags' => $featureFlags,
                    'last_updated' => now()->toISOString()
                ]
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