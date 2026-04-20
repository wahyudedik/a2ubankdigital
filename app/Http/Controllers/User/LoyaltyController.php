<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPointsHistory;
use App\Models\User;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoyaltyController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get user's loyalty points
     */
    public function getLoyaltyPoints(Request $request): JsonResponse
    {
        $user = Auth::user()->load('customerProfile');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);

        if ($page < 1 || $limit < 1 || $limit > 100) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter pagination tidak valid. Halaman minimal 1, limit antara 1 dan 100.'
            ], 422);
        }

        // Get points history
        $query = LoyaltyPointsHistory::where('user_id', $user->id);
        
        $totalRecords = $query->count();
        $pointsHistory = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        // Calculate summary
        $totalEarned = LoyaltyPointsHistory::where('user_id', $user->id)
            ->earned()
            ->sum('points');

        $totalRedeemed = abs(LoyaltyPointsHistory::where('user_id', $user->id)
            ->redeemed()
            ->sum('points'));

        $currentBalance = $user->customerProfile?->loyalty_points ?? 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_balance' => $currentBalance,
                'total_earned' => $totalEarned,
                'total_redeemed' => $totalRedeemed,
                'points_history' => $pointsHistory,
                'pagination' => [
                    'current_page' => (int)$page,
                    'total_records' => (int)$totalRecords
                ]
            ]
        ]);
    }

    /**
     * Redeem loyalty points
     */
    public function redeemPoints(Request $request): JsonResponse
    {
        $request->validate([
            'points' => 'required|integer|min:100',
            'reward_type' => 'required|in:CASHBACK,DISCOUNT_VOUCHER,GIFT_VOUCHER',
            'reward_description' => 'sometimes|string|max:255'
        ]);

        $user = Auth::user()->load('customerProfile');
        $pointsToRedeem = $request->points;

        // Check if user has enough points
        $currentBalance = $user->customerProfile?->loyalty_points ?? 0;
        if ($currentBalance < $pointsToRedeem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Poin tidak mencukupi. Saldo poin Anda: ' . number_format($currentBalance, 0, ',', '.')
            ], 400);
        }

        // Define redemption rates and rewards
        $rewardConfig = $this->getRewardConfig();
        
        if (!isset($rewardConfig[$request->reward_type])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jenis reward tidak valid.'
            ], 400);
        }

        $reward = $rewardConfig[$request->reward_type];
        
        // Check minimum points requirement
        if ($pointsToRedeem < $reward['min_points']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minimum poin untuk reward ini adalah ' . number_format($reward['min_points'], 0, ',', '.')
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Calculate reward value
            $rewardValue = $this->calculateRewardValue($request->reward_type, $pointsToRedeem);
            
            // Deduct points from user balance
            if ($user->customerProfile) {
                $user->customerProfile->decrement('loyalty_points', $pointsToRedeem);
            }

            // Create redemption record
            $description = $request->reward_description ?? $this->getRedemptionDescription($request->reward_type, $rewardValue);
            
            $redemption = LoyaltyPointsHistory::createRedemption(
                $user->id,
                $pointsToRedeem,
                $description
            );

            // Log the redemption
            $this->logService->logAudit('LOYALTY_POINTS_REDEEMED', 'loyalty_points_history', $redemption->id, [], [
                'points_redeemed' => $pointsToRedeem,
                'reward_type' => $request->reward_type,
                'reward_value' => $rewardValue
            ]);

            // Generate reward code/voucher
            $rewardCode = $this->generateRewardCode($request->reward_type);

            // Notify user
            $this->notificationService->notifyUser(
                $user->id,
                'Poin Berhasil Ditukar',
                'Anda telah berhasil menukar ' . number_format($pointsToRedeem, 0, ',', '.') . ' poin dengan ' . $description . '. Kode: ' . $rewardCode
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Poin berhasil ditukar.',
                'data' => [
                    'redemption_id' => $redemption->id,
                    'points_redeemed' => $pointsToRedeem,
                    'reward_type' => $request->reward_type,
                    'reward_value' => $rewardValue,
                    'reward_code' => $rewardCode,
                    'description' => $description,
                    'remaining_balance' => $user->fresh()->load('customerProfile')->customerProfile?->loyalty_points ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menukar poin: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available rewards
     */
    public function getAvailableRewards(): JsonResponse
    {
        $user = Auth::user()->load('customerProfile');
        $rewardConfig = $this->getRewardConfig();
        
        $availableRewards = [];
        
        foreach ($rewardConfig as $type => $config) {
            $availableRewards[] = [
                'type' => $type,
                'name' => $config['name'],
                'description' => $config['description'],
                'min_points' => $config['min_points'],
                'rate' => $config['rate'],
                'is_available' => ($user->customerProfile?->loyalty_points ?? 0) >= $config['min_points']
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_balance' => $user->customerProfile?->loyalty_points ?? 0,
                'available_rewards' => $availableRewards
            ]
        ]);
    }

    /**
     * Get reward configuration
     */
    private function getRewardConfig(): array
    {
        return [
            'CASHBACK' => [
                'name' => 'Cashback',
                'description' => 'Cashback langsung ke rekening',
                'min_points' => 1000,
                'rate' => 1, // 1 point = 1 rupiah
            ],
            'DISCOUNT_VOUCHER' => [
                'name' => 'Voucher Diskon',
                'description' => 'Voucher diskon untuk transaksi',
                'min_points' => 500,
                'rate' => 1.2, // 1 point = 1.2 rupiah discount value
            ],
            'GIFT_VOUCHER' => [
                'name' => 'Voucher Hadiah',
                'description' => 'Voucher belanja merchant partner',
                'min_points' => 2000,
                'rate' => 0.8, // 1 point = 0.8 rupiah gift value
            ]
        ];
    }

    /**
     * Calculate reward value based on points and type
     */
    private function calculateRewardValue(string $rewardType, int $points): float
    {
        $config = $this->getRewardConfig();
        return $points * $config[$rewardType]['rate'];
    }

    /**
     * Generate redemption description
     */
    private function getRedemptionDescription(string $rewardType, float $value): string
    {
        switch ($rewardType) {
            case 'CASHBACK':
                return 'Cashback Rp ' . number_format($value, 0, ',', '.');
            case 'DISCOUNT_VOUCHER':
                return 'Voucher Diskon Rp ' . number_format($value, 0, ',', '.');
            case 'GIFT_VOUCHER':
                return 'Voucher Hadiah Rp ' . number_format($value, 0, ',', '.');
            default:
                return 'Reward tidak dikenal';
        }
    }

    /**
     * Generate reward code
     */
    private function generateRewardCode(string $rewardType): string
    {
        $prefix = [
            'CASHBACK' => 'CB',
            'DISCOUNT_VOUCHER' => 'DV',
            'GIFT_VOUCHER' => 'GV'
        ];

        return ($prefix[$rewardType] ?? 'RW') . date('Ymd') . rand(1000, 9999);
    }
}