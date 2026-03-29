<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentController extends Controller
{
    /**
     * Get investment products
     */
    public function getProducts(): JsonResponse
    {
        // Simulated investment products
        $products = [
            [
                'id' => 'RD001',
                'name' => 'Reksa Dana Pasar Uang',
                'type' => 'MONEY_MARKET',
                'risk_level' => 'LOW',
                'min_investment' => 100000,
                'expected_return' => 4.5,
                'description' => 'Investasi jangka pendek dengan risiko rendah',
                'manager' => 'PT Manulife Aset Manajemen Indonesia',
                'is_active' => true
            ],
            [
                'id' => 'RD002',
                'name' => 'Reksa Dana Pendapatan Tetap',
                'type' => 'FIXED_INCOME',
                'risk_level' => 'MEDIUM',
                'min_investment' => 100000,
                'expected_return' => 7.5,
                'description' => 'Investasi obligasi dengan return stabil',
                'manager' => 'PT Schroder Investment Management Indonesia',
                'is_active' => true
            ],
            [
                'id' => 'RD003',
                'name' => 'Reksa Dana Saham',
                'type' => 'EQUITY',
                'risk_level' => 'HIGH',
                'min_investment' => 100000,
                'expected_return' => 15.0,
                'description' => 'Investasi saham dengan potensi return tinggi',
                'manager' => 'PT Bahana TCW Investment Management',
                'is_active' => true
            ],
            [
                'id' => 'RD004',
                'name' => 'Reksa Dana Campuran',
                'type' => 'BALANCED',
                'risk_level' => 'MEDIUM',
                'min_investment' => 100000,
                'expected_return' => 10.0,
                'description' => 'Kombinasi saham dan obligasi',
                'manager' => 'PT Mandiri Manajemen Investasi',
                'is_active' => true
            ],
            [
                'id' => 'SBN001',
                'name' => 'Surat Berharga Negara (SBN)',
                'type' => 'GOVERNMENT_BOND',
                'risk_level' => 'LOW',
                'min_investment' => 1000000,
                'expected_return' => 6.5,
                'description' => 'Obligasi pemerintah dengan jaminan negara',
                'manager' => 'Kementerian Keuangan RI',
                'is_active' => true
            ],
            [
                'id' => 'GOLD001',
                'name' => 'Emas Digital',
                'type' => 'GOLD',
                'risk_level' => 'MEDIUM',
                'min_investment' => 10000,
                'expected_return' => 8.0,
                'description' => 'Investasi emas tanpa perlu penyimpanan fisik',
                'manager' => 'PT Pegadaian (Persero)',
                'is_active' => true
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Get user's investment portfolio
     */
    public function getPortfolio(): JsonResponse
    {
        // In production, this would fetch from database
        // For now, return empty portfolio
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_investment' => 0,
                'total_value' => 0,
                'total_return' => 0,
                'return_percentage' => 0,
                'investments' => []
            ]
        ]);
    }

    /**
     * Simulate investment purchase
     */
    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|string',
            'amount' => 'required|numeric|min:10000'
        ]);

        // In production, this would:
        // 1. Validate user balance
        // 2. Create investment record
        // 3. Deduct balance
        // 4. Send confirmation

        return response()->json([
            'status' => 'success',
            'message' => 'Investasi berhasil. Fitur ini masih dalam pengembangan.',
            'data' => [
                'product_id' => $request->product_id,
                'amount' => $request->amount,
                'purchase_date' => now()->toISOString()
            ]
        ]);
    }
}
