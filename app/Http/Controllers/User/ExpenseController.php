<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\ExpenseRecord;
use App\Models\MonthlyBudget;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function __construct(
        private ExpenseService $expenseService
    ) {}

    // ==========================================
    // Category Endpoints
    // ==========================================

    /**
     * Get all categories for the authenticated user.
     */
    public function getCategories(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Get or create default categories
        $this->expenseService->getOrCreateDefaultCategories($user);

        $categories = ExpenseCategory::where('user_id', $user->id)
            ->with('children')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * Create a new custom category.
     */
    public function createCategory(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:expense_categories,id',
        ]);

        // Check for duplicate name
        $exists = ExpenseCategory::where('user_id', $user->id)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Nama kategori sudah ada.',
            ]);
        }

        $category = ExpenseCategory::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? '📦',
            'color' => $validated['color'] ?? '#95A5A6',
            'is_default' => false,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dibuat.',
            'data' => $category,
        ]);
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $category = ExpenseCategory::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
        ]);

        // Check for duplicate name (excluding current)
        $exists = ExpenseCategory::where('user_id', $user->id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Nama kategori sudah ada.',
            ]);
        }

        $category->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $category,
        ]);
    }

    /**
     * Delete a custom category.
     */
    public function deleteCategory(int $id): JsonResponse
    {
        $user = Auth::user();

        $category = ExpenseCategory::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        // Prevent deleting default categories
        if ($category->is_default) {
            throw ValidationException::withMessages([
                'category' => 'Tidak dapat menghapus kategori default.',
            ]);
        }

        // Check if category has records
        $hasRecords = ExpenseRecord::where('category_id', $id)->exists();
        if ($hasRecords) {
            throw ValidationException::withMessages([
                'category' => 'Kategori masih memiliki catatan pengeluaran.',
            ]);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }

    // ==========================================
    // Expense Record Endpoints
    // ==========================================

    /**
     * Get all expense records for the authenticated user.
     */
    public function getRecords(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = ExpenseRecord::where('user_id', $user->id)
            ->with('category');

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('expense_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('expense_date', '<=', $request->end_date);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by auto-imported
        if ($request->has('is_auto_imported')) {
            $query->where('is_auto_imported', $request->boolean('is_auto_imported'));
        }

        // Search by description
        if ($request->has('search')) {
            $query->where('description', 'like', "%{$request->search}%");
        }

        // Sort
        $sortBy = $request->get('sort_by', 'expense_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $records = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $records,
        ]);
    }

    /**
     * Create a new manual expense record.
     */
    public function createRecord(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01|max:999999999999.99',
            'description' => 'nullable|string|max:500',
            'expense_date' => 'required|date|before_or_equal:today',
            'receipt' => 'nullable|image|max:2048',
        ]);

        // Verify category belongs to user
        $category = ExpenseCategory::where('user_id', $user->id)
            ->where('id', $validated['category_id'])
            ->firstOrFail();

        // Handle receipt upload
        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        $record = ExpenseRecord::create([
            'user_id' => $user->id,
            'transaction_id' => null,
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'expense_date' => $validated['expense_date'],
            'receipt_path' => $receiptPath,
            'is_auto_imported' => false,
        ]);

        $record->load('category');

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan pengeluaran berhasil dibuat.',
            'data' => $record,
        ]);
    }

    /**
     * Update an existing expense record.
     */
    public function updateRecord(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $record = ExpenseRecord::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01|max:999999999999.99',
            'description' => 'nullable|string|max:500',
            'expense_date' => 'required|date|before_or_equal:today',
            'receipt' => 'nullable|image|max:2048',
        ]);

        // Verify category belongs to user
        $category = ExpenseCategory::where('user_id', $user->id)
            ->where('id', $validated['category_id'])
            ->firstOrFail();

        // Handle receipt upload
        $receiptPath = $record->receipt_path;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        $record->update([
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'expense_date' => $validated['expense_date'],
            'receipt_path' => $receiptPath,
        ]);

        $record->load('category');

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan pengeluaran berhasil diperbarui.',
            'data' => $record,
        ]);
    }

    /**
     * Delete an expense record.
     */
    public function deleteRecord(int $id): JsonResponse
    {
        $user = Auth::user();

        $record = ExpenseRecord::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Catatan pengeluaran berhasil dihapus.',
        ]);
    }

    // ==========================================
    // Budget Endpoints
    // ==========================================

    /**
     * Get all budgets for the authenticated user.
     */
    public function getBudgets(Request $request): JsonResponse
    {
        $user = Auth::user();
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $budgets = MonthlyBudget::where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->with('category')
            ->get();

        // Add computed attributes
        $budgets->each(function ($budget) {
            $budget->total_spent = $budget->total_spent;
            $budget->remaining = $budget->remaining;
            $budget->usage_percentage = $budget->usage_percentage;
        });

        return response()->json([
            'status' => 'success',
            'data' => $budgets,
        ]);
    }

    /**
     * Set or update budget for a category.
     */
    public function setBudget(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'category_id' => 'required|exists:expense_categories,id',
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
            'budget_amount' => 'required|numeric|min:0|max:999999999999.99',
            'alert_threshold' => 'nullable|integer|min:1|max:100',
        ]);

        // Verify category belongs to user
        $category = ExpenseCategory::where('user_id', $user->id)
            ->where('id', $validated['category_id'])
            ->firstOrFail();

        $budget = MonthlyBudget::updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $validated['category_id'],
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'budget_amount' => $validated['budget_amount'],
                'alert_threshold' => $validated['alert_threshold'] ?? 80,
            ]
        );

        $budget->load('category');

        return response()->json([
            'status' => 'success',
            'message' => 'Budget berhasil disimpan.',
            'data' => $budget,
        ]);
    }

    /**
     * Delete a budget.
     */
    public function deleteBudget(int $id): JsonResponse
    {
        $user = Auth::user();

        $budget = MonthlyBudget::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $budget->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Budget berhasil dihapus.',
        ]);
    }

    // ==========================================
    // Analytics Endpoints
    // ==========================================

    /**
     * Get monthly summary.
     */
    public function getSummary(Request $request): JsonResponse
    {
        $user = Auth::user();
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $summary = $this->expenseService->getMonthlySummary($user, $year, $month);

        return response()->json([
            'status' => 'success',
            'data' => $summary,
        ]);
    }

    /**
     * Get expense trend.
     */
    public function getTrend(Request $request): JsonResponse
    {
        $user = Auth::user();
        $months = $request->get('months', 6);

        $trend = $this->expenseService->getExpenseTrend($user, $months);

        return response()->json([
            'status' => 'success',
            'data' => $trend,
        ]);
    }

    /**
     * Get insights and recommendations.
     */
    public function getInsights(): JsonResponse
    {
        $user = Auth::user();

        $insights = $this->expenseService->getInsights($user);

        return response()->json([
            'status' => 'success',
            'data' => $insights,
        ]);
    }
}
