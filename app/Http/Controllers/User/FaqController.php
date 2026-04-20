<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Get active FAQs for users
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->input('category');
        $search = $request->input('search');

        $query = Faq::where('is_active', true);

        if ($category) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $faqs = $query->orderBy('sort_order')->orderBy('created_at', 'desc')->get()
            ->map(fn($faq) => [
                'id'       => $faq->id,
                'question' => $faq->question,
                'answer'   => $faq->answer,
                'category' => $faq->category,
            ]);

        $categories = Faq::where('is_active', true)
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'faqs'       => $faqs,
                'categories' => $categories,
            ],
        ]);
    }
}
