<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DigitalProductController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get available digital products
     */
    public function index(): JsonResponse
    {
        $products = [
            // Pulsa
            [
                'id' => 'PULSA_10K',
                'category' => 'PULSA',
                'provider' => 'TELKOMSEL',
                'name' => 'Pulsa Telkomsel 10.000',
                'price' => 11000,
                'nominal' => 10000,
                'is_active' => true
            ],
            [
                'id' => 'PULSA_20K',
                'category' => 'PULSA',
                'provider' => 'TELKOMSEL',
                'name' => 'Pulsa Telkomsel 20.000',
                'price' => 21000,
                'nominal' => 20000,
                'is_active' => true
            ],
            [
                'id' => 'PULSA_50K',
                'category' => 'PULSA',
                'provider' => 'TELKOMSEL',
                'name' => 'Pulsa Telkomsel 50.000',
                'price' => 51000,
                'nominal' => 50000,
                'is_active' => true
            ],
            
            // Paket Data
            [
                'id' => 'DATA_1GB',
                'category' => 'DATA',
                'provider' => 'TELKOMSEL',
                'name' => 'Paket Data 1GB (30 hari)',
                'price' => 25000,
                'nominal' => 1024,
                'is_active' => true
            ],
            [
                'id' => 'DATA_3GB',
                'category' => 'DATA',
                'provider' => 'TELKOMSEL',
                'name' => 'Paket Data 3GB (30 hari)',
                'price' => 50000,
                'nominal' => 3072,
                'is_active' => true
            ],
            
            // E-Wallet
            [
                'id' => 'GOPAY_50K',
                'category' => 'EWALLET',
                'provider' => 'GOPAY',
                'name' => 'GoPay 50.000',
                'price' => 51000,
                'nominal' => 50000,
                'is_active' => true
            ],
            [
                'id' => 'OVO_100K',
                'category' => 'EWALLET',
                'provider' => 'OVO',
                'name' => 'OVO 100.000',
                'price' => 101500,
                'nominal' => 100000,
                'is_active' => true
            ],
            
            // Game Voucher
            [
                'id' => 'MLBB_86DM',
                'category' => 'GAME',
                'provider' => 'MOBILE_LEGENDS',
                'name' => 'Mobile Legends 86 Diamond',
                'price' => 22000,
                'nominal' => 86,
                'is_active' => true
            ],
            [
                'id' => 'FF_70DM',
                'category' => 'GAME',
                'provider' => 'FREE_FIRE',
                'name' => 'Free Fire 70 Diamond',
                'price' => 10000,
                'nominal' => 70,
                'is_active' => true
            ]
        ];

        // Filter by category if requested
        $category = request()->input('category');
        if ($category) {
            $products = array_filter($products, function($product) use ($category) {
                return $product['category'] === strtoupper($category);
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => array_values($products)
        ]);
    }

    /**
     * Purchase digital product
     */
    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|string',
            'destination' => 'required|string' // phone number, game ID, etc.
        ]);

        // Get product info (in production, this would come from database)
        $product = $this->getProductById($request->product_id);
        
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.'
            ], 404);
        }

        if (!$product['is_active']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk sedang tidak tersedia.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Get user's account
            $account = Account::where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN')
                ->lockForUpdate()
                ->first();

            if (!$account || $account->balance < $product['price']) {
                throw new \Exception('Saldo tidak mencukupi untuk pembelian produk digital.');
            }

            // Create transaction
            $transaction = Transaction::create([
                'transaction_code' => 'DIG-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $account->id,
                'transaction_type' => 'DIGITAL_PRODUCT',
                'amount' => $product['price'],
                'fee' => 0,
                'description' => "Pembelian {$product['name']} - {$request->destination}",
                'status' => 'PROCESSING'
            ]);

            // Deduct balance
            $account->decrement('balance', $product['price']);

            // Simulate product delivery (in production, call actual API)
            $deliveryResult = $this->simulateProductDelivery($product, $request->destination);

            if ($deliveryResult['success']) {
                $transaction->update(['status' => 'SUCCESS']);
                
                // Log successful purchase
                $this->logService->logTransaction('DIGITAL_PRODUCT', $transaction->id, [
                    'product_id' => $request->product_id,
                    'destination' => $request->destination,
                    'price' => $product['price']
                ]);

                // Send notification
                $this->notificationService->notifyUser(
                    Auth::id(),
                    'Pembelian Berhasil',
                    "Pembelian {$product['name']} ke {$request->destination} berhasil diproses."
                );

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembelian produk digital berhasil.',
                    'data' => [
                        'transaction' => $transaction,
                        'serial_number' => $deliveryResult['serial_number'] ?? null
                    ]
                ]);
            } else {
                // Purchase failed, refund balance
                $account->increment('balance', $product['price']);
                $transaction->update(['status' => 'FAILED']);

                throw new \Exception($deliveryResult['message'] ?? 'Pembelian gagal diproses.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->logService->logError('Digital product purchase failed', $e, [
                'product_id' => $request->product_id,
                'destination' => $request->destination
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Pembelian gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get purchase history
     */
    public function history(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 15);

        $userAccountIds = Account::where('user_id', Auth::id())->pluck('id');

        $query = Transaction::whereIn('from_account_id', $userAccountIds)
            ->where('transaction_type', 'DIGITAL_PRODUCT');

        $totalRecords = $query->count();
        $purchases = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $purchases,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Get product by ID
     */
    private function getProductById(string $productId): ?array
    {
        $products = $this->index()->getData(true)['data'];
        
        foreach ($products as $product) {
            if ($product['id'] === $productId) {
                return $product;
            }
        }
        
        return null;
    }

    /**
     * Simulate product delivery (replace with actual API call)
     */
    private function simulateProductDelivery(array $product, string $destination): array
    {
        // Simulate 98% success rate
        $success = rand(1, 100) <= 98;

        if ($success) {
            $result = [
                'success' => true,
                'message' => 'Produk berhasil dikirim.'
            ];

            // Add serial number for certain product types
            if (in_array($product['category'], ['GAME', 'VOUCHER'])) {
                $result['serial_number'] = 'SN-' . time() . '-' . rand(10000, 99999);
            }

            return $result;
        } else {
            return [
                'success' => false,
                'message' => 'Pengiriman produk gagal. Silakan coba lagi.'
            ];
        }
    }
}