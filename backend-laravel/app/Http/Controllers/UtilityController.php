<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UtilityController extends Controller
{
    /**
     * Get FAQ list
     */
    public function getFaq(): JsonResponse
    {
        try {
            $faqs = DB::table('faqs')
                ->select('question', 'answer', 'category')
                ->where('is_active', 1)
                ->orderBy('category')
                ->orderBy('id')
                ->get();

            $groupedFaqs = [];
            foreach ($faqs as $faq) {
                $groupedFaqs[$faq->category][] = $faq;
            }

            return response()->json([
                'status' => 'success',
                'data' => $groupedFaqs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data FAQ.'
            ], 500);
        }
    }

    /**
     * Get system status
     */
    public function getSystemStatus(): JsonResponse
    {
        $status = [
            'api_status' => 'online',
            'database_status' => 'connected',
            'maintenance_mode' => false,
            'last_updated' => now()->toDateTimeString(),
            'services' => [
                'authentication' => 'operational',
                'transactions' => 'operational',
                'notifications' => 'operational',
                'bill_payment' => 'operational',
                'digital_products' => 'operational'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $status
        ]);
    }

    /**
     * Get public configuration
     */
    public function getPublicConfig(): JsonResponse
    {
        $config = [
            'app_name' => config('app.name'),
            'app_version' => '1.0.0',
            'maintenance_mode' => false,
            'features' => [
                'registration' => true,
                'loan_application' => true,
                'deposit_account' => true,
                'bill_payment' => true,
                'digital_products' => true,
                'internal_transfer' => true
            ],
            'limits' => [
                'max_transfer_amount' => 50000000,
                'max_daily_transaction' => 100000000,
                'min_loan_amount' => 1000000,
                'max_loan_amount' => 100000000
            ],
            'contact' => [
                'phone' => '+62-21-1234567',
                'email' => 'support@a2ubankdigital.my.id',
                'whatsapp' => '+62-812-3456789'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $config
        ]);
    }

    /**
     * Loan calculator
     */
    public function loanCalculator(Request $request): JsonResponse
    {
        $request->validate([
            'loan_amount' => 'required|numeric|min:1000000|max:100000000',
            'interest_rate' => 'required|numeric|min:0.1|max:50',
            'tenor' => 'required|integer|min:1|max:60'
        ]);

        $loanAmount = $request->loan_amount;
        $annualRate = $request->interest_rate / 100;
        $monthlyRate = $annualRate / 12;
        $tenor = $request->tenor;

        // Calculate monthly installment using PMT formula
        $monthlyInstallment = ($loanAmount * $monthlyRate * pow(1 + $monthlyRate, $tenor)) / 
                             (pow(1 + $monthlyRate, $tenor) - 1);

        $totalRepayment = $monthlyInstallment * $tenor;
        $totalInterest = $totalRepayment - $loanAmount;

        // Generate installment schedule
        $schedule = [];
        $remainingPrincipal = $loanAmount;

        for ($i = 1; $i <= $tenor; $i++) {
            $interestAmount = $remainingPrincipal * $monthlyRate;
            $principalAmount = $monthlyInstallment - $interestAmount;
            $remainingPrincipal -= $principalAmount;

            $schedule[] = [
                'installment_number' => $i,
                'principal_amount' => round($principalAmount, 2),
                'interest_amount' => round($interestAmount, 2),
                'total_amount' => round($monthlyInstallment, 2),
                'remaining_balance' => round(max(0, $remainingPrincipal), 2)
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'loan_amount' => $loanAmount,
                'interest_rate_pa' => $request->interest_rate,
                'tenor_months' => $tenor,
                'monthly_installment' => round($monthlyInstallment, 2),
                'total_repayment' => round($totalRepayment, 2),
                'total_interest' => round($totalInterest, 2),
                'installment_schedule' => $schedule
            ]
        ]);
    }

    /**
     * Get currency rates
     */
    public function getCurrencyRates(): JsonResponse
    {
        // Static rates - in production, fetch from external API
        $rates = [
            [
                'currency' => 'USD',
                'currency_name' => 'US Dollar',
                'buy_rate' => 15750.00,
                'sell_rate' => 15850.00,
                'last_updated' => now()->toDateTimeString()
            ],
            [
                'currency' => 'EUR',
                'currency_name' => 'Euro',
                'buy_rate' => 17200.00,
                'sell_rate' => 17350.00,
                'last_updated' => now()->toDateTimeString()
            ],
            [
                'currency' => 'SGD',
                'currency_name' => 'Singapore Dollar',
                'buy_rate' => 11650.00,
                'sell_rate' => 11750.00,
                'last_updated' => now()->toDateTimeString()
            ],
            [
                'currency' => 'MYR',
                'currency_name' => 'Malaysian Ringgit',
                'buy_rate' => 3450.00,
                'sell_rate' => 3550.00,
                'last_updated' => now()->toDateTimeString()
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $rates
        ]);
    }
}