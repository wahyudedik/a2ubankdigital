<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanProduct;
use App\Models\DepositProduct;
use App\Models\DigitalProduct;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    // ==================== LOAN PRODUCTS ====================

    /**
     * Get loan products
     */
    public function getLoanProducts(): JsonResponse
    {
        $products = LoanProduct::orderBy('product_name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Create loan product
     */
    public function createLoanProduct(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Unit Head and above can manage products
        if ($user->role_id > 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $request->validate([
            'product_name' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|min:0',
            'interest_rate_pa' => 'required|numeric|min:0|max:9999',
            'late_payment_fee' => 'required|numeric|min:0',
            'min_tenor' => 'required|integer|min:1',
            'max_tenor' => 'required|integer|min:1',
            'tenor_unit' => 'required|in:MINGGU,BULAN,TAHUN'
        ]);

        if ((float)$request->max_amount < (float)$request->min_amount) {
            return response()->json(['status' => 'error', 'message' => 'Plafon maksimum harus lebih besar atau sama dengan plafon minimum.'], 422);
        }
        if ((int)$request->max_tenor < (int)$request->min_tenor) {
            return response()->json(['status' => 'error', 'message' => 'Tenor maksimum harus lebih besar atau sama dengan tenor minimum.'], 422);
        }

        try {
            $data = $request->all();
            $data['product_code'] = 'LP-' . strtoupper(uniqid());
            $product = LoanProduct::create($data);

            // Log product creation
            $this->logService->logAudit('LOAN_PRODUCT_CREATED', 'loan_products', $product->id, [], $product->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Produk pinjaman baru berhasil ditambahkan.',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update loan product
     */
    public function updateLoanProduct(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        
        // Only Unit Head and above can manage products
        if ($user->role_id > 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $product = LoanProduct::findOrFail($id);

        $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'min_amount' => 'sometimes|numeric|min:0',
            'max_amount' => 'sometimes|numeric',
            'interest_rate_pa' => 'sometimes|numeric|min:0|max:9999',
            'late_payment_fee' => 'sometimes|numeric|min:0',
            'min_tenor' => 'sometimes|integer|min:1',
            'max_tenor' => 'sometimes|integer',
            'tenor_unit' => 'sometimes|in:MINGGU,BULAN,TAHUN'
        ]);

        $oldData = $product->toArray();
        $product->update($request->all());

        // Log product update
        $this->logService->logAudit('LOAN_PRODUCT_UPDATED', 'loan_products', $product->id, $oldData, $product->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Produk pinjaman berhasil diperbarui.',
            'data' => $product
        ]);
    }

    /**
     * Delete loan product
     */
    public function deleteLoanProduct($id): JsonResponse
    {
        $user = Auth::user();
        
        // Only Unit Head and above can manage products
        if ($user->role_id > 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $product = LoanProduct::findOrFail($id);

        // Check if product is being used
        if ($product->loans()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat menghapus produk yang sedang digunakan.'
            ], 400);
        }

        $oldData = $product->toArray();
        $product->delete();

        // Log product deletion
        $this->logService->logAudit('LOAN_PRODUCT_DELETED', 'loan_products', $id, $oldData, []);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk pinjaman berhasil dihapus.'
        ]);
    }

    // ==================== DEPOSIT PRODUCTS ====================

    /**
     * Get deposit products
     */
    public function getDepositProducts(): JsonResponse
    {
        $products = DepositProduct::orderBy('product_name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Create deposit product
     */
    public function createDepositProduct(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Unit Head and above can manage products
        if ($user->role_id > 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $request->validate([
            'product_name' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'interest_rate_pa' => 'required|numeric|min:0|max:100',
            'tenor_months' => 'required|integer|min:1'
        ]);

        try {
            $product = DepositProduct::create($request->all());

            // Log product creation
            $this->logService->logAudit('DEPOSIT_PRODUCT_CREATED', 'deposit_products', $product->id, [], $product->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Produk deposito baru berhasil ditambahkan.',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update deposit product
     */
    public function updateDepositProduct(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        
        // Only Unit Head and above can manage products
        if ($user->role_id > 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $product = DepositProduct::findOrFail($id);

        $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'min_amount' => 'sometimes|numeric|min:0',
            'interest_rate_pa' => 'sometimes|numeric|min:0|max:100',
            'tenor_months' => 'sometimes|integer|min:1'
        ]);

        $oldData = $product->toArray();
        $product->update($request->all());

        // Log product update
        $this->logService->logAudit('DEPOSIT_PRODUCT_UPDATED', 'deposit_products', $product->id, $oldData, $product->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Produk deposito berhasil diperbarui.',
            'data' => $product
        ]);
    }

    // ==================== DIGITAL PRODUCTS ====================

    /**
     * Get digital products
     */
    public function getDigitalProducts(): JsonResponse
    {
        $products = DigitalProduct::where('is_active', true)
            ->orderBy('category')
            ->orderBy('product_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Create digital product
     */
    public function createDigitalProduct(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Unit Head and above can manage products
        if ($user->role_id > 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $request->validate([
            'product_name' => 'required|string|max:255',
            'category' => 'required|in:PULSA,DATA,EWALLET,GAMES,VOUCHER',
            'provider' => 'required|string|max:100',
            'denomination' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $product = DigitalProduct::create($request->all());

            // Log product creation
            $this->logService->logAudit('DIGITAL_PRODUCT_CREATED', 'digital_products', $product->id, [], $product->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Produk digital baru berhasil ditambahkan.',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update digital product
     */
    public function updateDigitalProduct(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        
        // Only Unit Head and above can manage products
        if ($user->role_id > 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $product = DigitalProduct::findOrFail($id);

        $request->validate([
            'product_name' => 'sometimes|string|max:255',
            'category' => 'sometimes|in:PULSA,DATA,EWALLET,GAMES,VOUCHER',
            'provider' => 'sometimes|string|max:100',
            'denomination' => 'sometimes|numeric|min:0',
            'price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean'
        ]);

        $oldData = $product->toArray();
        $product->update($request->all());

        // Log product update
        $this->logService->logAudit('DIGITAL_PRODUCT_UPDATED', 'digital_products', $product->id, $oldData, $product->toArray());

        return response()->json([
            'status' => 'success',
            'message' => 'Produk digital berhasil diperbarui.',
            'data' => $product
        ]);
    }

    /**
     * Delete digital product
     */
    public function deleteDigitalProduct($id): JsonResponse
    {
        $user = Auth::user();
        
        // Only Unit Head and above can manage products
        if ($user->role_id > 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $product = DigitalProduct::findOrFail($id);

        $oldData = $product->toArray();
        $product->delete();

        // Log product deletion
        $this->logService->logAudit('DIGITAL_PRODUCT_DELETED', 'digital_products', $id, $oldData, []);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk digital berhasil dihapus.'
        ]);
    }
}