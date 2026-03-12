<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FaqController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Get all FAQs
     */
    public function index(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);
            $category = $request->input('category', 'all');
            $status = $request->input('status', 'all');
            $search = $request->input('search');

            $query = Faq::query();

            if ($category !== 'all') {
                $query->where('category', $category);
            }

            if ($status !== 'all') {
                $query->where('is_active', $status === 'active');
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('question', 'like', "%{$search}%")
                      ->orWhere('answer', 'like', "%{$search}%")
                      ->orWhere('tags', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            $faqs = $query
                ->orderBy('sort_order')
                ->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($faq) {
                    return [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                        'category' => $faq->category,
                        'tags' => explode(',', $faq->tags ?? ''),
                        'sort_order' => $faq->sort_order,
                        'is_active' => $faq->is_active,
                        'view_count' => $faq->view_count,
                        'helpful_count' => $faq->helpful_count,
                        'created_at' => $faq->created_at->toISOString(),
                        'updated_at' => $faq->updated_at->toISOString()
                    ];
                });

            // Get categories
            $categories = Faq::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->pluck('category');

            return response()->json([
                'success' => true,
                'data' => [
                    'faqs' => $faqs,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit)
                    ],
                    'categories' => $categories,
                    'statistics' => [
                        'total_faqs' => $total,
                        'active_faqs' => Faq::where('is_active', true)->count(),
                        'most_viewed' => Faq::orderBy('view_count', 'desc')->take(5)->get(['id', 'question', 'view_count'])
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch FAQs'
            ], 500);
        }
    }

    /**
     * Add new FAQ
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'question' => 'required|string|max:500',
                'answer' => 'required|string|max:2000',
                'category' => 'required|string|max:100',
                'tags' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();

            // Check for duplicate question
            $existingFaq = Faq::where('question', $request->question)->first();
            if ($existingFaq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FAQ with this question already exists'
                ], 409);
            }

            // Get next sort order if not provided
            $sortOrder = $request->input('sort_order');
            if ($sortOrder === null) {
                $sortOrder = Faq::where('category', $request->category)->max('sort_order') + 1;
            }

            $faq = Faq::create([
                'question' => $request->question,
                'answer' => $request->answer,
                'category' => $request->category,
                'tags' => $request->tags,
                'sort_order' => $sortOrder,
                'is_active' => $request->input('is_active', true),
                'created_by' => $admin->id
            ]);

            // Log the action
            $this->logService->log(
                'faq_created',
                "FAQ created: {$request->question}",
                $admin->id,
                ['faq_id' => $faq->id, 'category' => $request->category]
            );

            return response()->json([
                'success' => true,
                'message' => 'FAQ created successfully',
                'data' => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'category' => $faq->category,
                    'tags' => explode(',', $faq->tags ?? ''),
                    'sort_order' => $faq->sort_order,
                    'is_active' => $faq->is_active,
                    'created_at' => $faq->created_at->toISOString()
                ]
            ], 201);

        } catch (\Exception $e) {
            $this->logService->log('faq_creation_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create FAQ'
            ], 500);
        }
    }

    /**
     * Update FAQ
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'question' => 'required|string|max:500',
                'answer' => 'required|string|max:2000',
                'category' => 'required|string|max:100',
                'tags' => 'nullable|string|max:255',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            $faq = Faq::find($id);

            if (!$faq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FAQ not found'
                ], 404);
            }

            // Check for duplicate question (excluding current FAQ)
            $existingFaq = Faq::where('question', $request->question)
                ->where('id', '!=', $id)
                ->first();

            if ($existingFaq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FAQ with this question already exists'
                ], 409);
            }

            // Store old values for logging
            $oldValues = [
                'question' => $faq->question,
                'answer' => $faq->answer,
                'category' => $faq->category,
                'is_active' => $faq->is_active
            ];

            // Update FAQ
            $faq->update([
                'question' => $request->question,
                'answer' => $request->answer,
                'category' => $request->category,
                'tags' => $request->tags,
                'sort_order' => $request->input('sort_order', $faq->sort_order),
                'is_active' => $request->input('is_active', $faq->is_active),
                'updated_by' => $admin->id
            ]);

            // Log the action
            $this->logService->log(
                'faq_updated',
                "FAQ updated: {$request->question}",
                $admin->id,
                [
                    'faq_id' => $faq->id,
                    'old_values' => $oldValues,
                    'new_values' => $request->only(['question', 'answer', 'category', 'is_active'])
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'FAQ updated successfully',
                'data' => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'category' => $faq->category,
                    'tags' => explode(',', $faq->tags ?? ''),
                    'sort_order' => $faq->sort_order,
                    'is_active' => $faq->is_active,
                    'updated_at' => $faq->updated_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('faq_update_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update FAQ'
            ], 500);
        }
    }

    /**
     * Delete FAQ
     */
    public function destroy($id)
    {
        try {
            $admin = Auth::user();
            $faq = Faq::find($id);

            if (!$faq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FAQ not found'
                ], 404);
            }

            // Store FAQ data for logging
            $faqData = [
                'id' => $faq->id,
                'question' => $faq->question,
                'category' => $faq->category
            ];

            $faq->delete();

            // Log the action
            $this->logService->log(
                'faq_deleted',
                "FAQ deleted: {$faqData['question']}",
                $admin->id,
                $faqData
            );

            return response()->json([
                'success' => true,
                'message' => 'FAQ deleted successfully'
            ]);

        } catch (\Exception $e) {
            $this->logService->log('faq_deletion_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete FAQ'
            ], 500);
        }
    }

    /**
     * Get FAQ by ID
     */
    public function show($id)
    {
        try {
            $faq = Faq::find($id);

            if (!$faq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FAQ not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'category' => $faq->category,
                    'tags' => explode(',', $faq->tags ?? ''),
                    'sort_order' => $faq->sort_order,
                    'is_active' => $faq->is_active,
                    'view_count' => $faq->view_count,
                    'helpful_count' => $faq->helpful_count,
                    'created_at' => $faq->created_at->toISOString(),
                    'updated_at' => $faq->updated_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch FAQ'
            ], 500);
        }
    }

    /**
     * Bulk update FAQ sort order
     */
    public function updateSortOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'faqs' => 'required|array',
                'faqs.*.id' => 'required|integer|exists:faqs,id',
                'faqs.*.sort_order' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();

            DB::beginTransaction();

            foreach ($request->faqs as $faqData) {
                Faq::where('id', $faqData['id'])
                    ->update([
                        'sort_order' => $faqData['sort_order'],
                        'updated_by' => $admin->id,
                        'updated_at' => now()
                    ]);
            }

            DB::commit();

            // Log the action
            $this->logService->log(
                'faq_sort_order_updated',
                'FAQ sort order updated',
                $admin->id,
                ['updated_faqs' => $request->faqs]
            );

            return response()->json([
                'success' => true,
                'message' => 'FAQ sort order updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('faq_sort_order_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update FAQ sort order'
            ], 500);
        }
    }

    /**
     * Toggle FAQ status
     */
    public function toggleStatus($id)
    {
        try {
            $admin = Auth::user();
            $faq = Faq::find($id);

            if (!$faq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FAQ not found'
                ], 404);
            }

            $newStatus = !$faq->is_active;
            $faq->update([
                'is_active' => $newStatus,
                'updated_by' => $admin->id
            ]);

            // Log the action
            $this->logService->log(
                'faq_status_toggled',
                "FAQ status changed to " . ($newStatus ? 'active' : 'inactive'),
                $admin->id,
                ['faq_id' => $faq->id, 'question' => $faq->question, 'new_status' => $newStatus]
            );

            return response()->json([
                'success' => true,
                'message' => 'FAQ status updated successfully',
                'data' => [
                    'id' => $faq->id,
                    'is_active' => $faq->is_active
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('faq_status_toggle_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update FAQ status'
            ], 500);
        }
    }
}