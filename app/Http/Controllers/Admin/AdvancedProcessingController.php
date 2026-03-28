<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdvancedProcessingController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    public function getTopupRequests(Request $request)
    {
        try {
            $status = $request->input('status', 'all');
            
            $query = DB::table('topup_requests as tr')
                ->leftJoin('users as u', 'tr.user_id', '=', 'u.id')
                ->select([
                    'tr.*',
                    'u.full_name as customer_name'
                ])
                ->orderBy('tr.created_at', 'desc');

            if ($status !== 'all') {
                $query->where('tr.status', strtolower($status));
            }

            $requests = $query->get()->map(function ($req) {
                return [
                    'id' => $req->id,
                    'customer_name' => $req->customer_name ?? 'Unknown Customer',
                    'amount' => $req->amount,
                    'payment_method' => $req->payment_method,
                    'proof_of_payment_url' => $req->proof_of_payment_url,
                    'status' => $req->status,
                    'created_at' => $req->created_at,
                    'processed_at' => $req->processed_at
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process top-up request
     */
    public function processTopupRequest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'request_id' => 'required|integer|exists:topup_requests,id',
                'action' => 'required|in:approve,reject',
                'admin_notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            
            // Get the top-up request
            $topupRequest = DB::table('topup_requests')
                ->where('id', $request->request_id)
                ->where('status', 'pending')
                ->first();

            if (!$topupRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Top-up request not found or already processed'
                ], 404);
            }

            $user = User::find($topupRequest->user_id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            DB::beginTransaction();

            if ($request->action === 'approve') {
                // Get user's account
                $account = DB::table('accounts')
                    ->where('user_id', $user->id)
                    ->where('account_type', 'TABUNGAN')
                    ->first();

                if (!$account) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Customer account not found'
                    ], 404);
                }

                // Create credit transaction
                $txCode = 'TRX-' . time() . '-' . rand(100000, 999999);
                DB::table('transactions')->insert([
                    'transaction_code' => $txCode,
                    'to_account_id' => $account->id,
                    'transaction_type' => 'TOPUP',
                    'amount' => $topupRequest->amount,
                    'fee' => 0,
                    'description' => "Top-up via {$topupRequest->payment_method}",
                    'status' => 'SUCCESS',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Update account balance
                DB::table('accounts')
                    ->where('id', $account->id)
                    ->increment('balance', $topupRequest->amount);

                $status = 'approved';
                $message = 'Permintaan isi saldo berhasil disetujui.';

            } else {
                $status = 'rejected';
                $message = 'Permintaan isi saldo ditolak.';
            }

            // Update top-up request
            DB::table('topup_requests')
                ->where('id', $request->request_id)
                ->update([
                    'status' => $status,
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                    'rejection_reason' => $request->admin_notes,
                    'updated_at' => now()
                ]);

            DB::commit();

            // Log the action
            $this->logService->log(
                'topup_request_processed',
                "Top-up request {$status} for user {$user->email}",
                $admin->id,
                [
                    'request_id' => $request->request_id,
                    'user_id' => $user->id,
                    'action' => $request->action,
                    'amount' => $topupRequest->amount
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('topup_processing_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process credit limit request (already exists in AccountManagementController, but adding here for completeness)
     */
    public function processCreditLimitRequest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'request_id' => 'required|integer|exists:credit_limit_requests,id',
                'action' => 'required|in:approve,reject',
                'admin_notes' => 'nullable|string|max:500',
                'approved_limit' => 'required_if:action,approve|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            
            // Get the credit limit request
            $limitRequest = DB::table('credit_limit_requests')
                ->where('id', $request->request_id)
                ->where('status', 'pending')
                ->first();

            if (!$limitRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit limit request not found or already processed'
                ], 404);
            }

            $user = User::find($limitRequest->user_id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            DB::beginTransaction();

            if ($request->action === 'approve') {
                // Update user's credit limit
                $user->update([
                    'credit_limit' => $request->approved_limit
                ]);

                $status = 'approved';
                $message = 'Credit limit increase approved';
                
                // Send notification to user
                $this->notificationService->send(
                    $user->id,
                    'Credit Limit Approved',
                    "Your credit limit has been increased to " . number_format($request->approved_limit, 0, ',', '.'),
                    'account'
                );

            } else {
                $status = 'rejected';
                $message = 'Credit limit increase rejected';
                
                // Send notification to user
                $this->notificationService->send(
                    $user->id,
                    'Credit Limit Request Rejected',
                    'Your credit limit increase request has been rejected. ' . ($request->admin_notes ?? ''),
                    'account'
                );
            }

            // Update credit limit request
            DB::table('credit_limit_requests')
                ->where('id', $request->request_id)
                ->update([
                    'status' => $status,
                    'approved_limit' => $request->approved_limit ?? null,
                    'processed_by' => $admin->id,
                    'processed_at' => now(),
                    'admin_notes' => $request->admin_notes,
                    'updated_at' => now()
                ]);

            DB::commit();

            // Log the action
            $this->logService->log(
                'credit_limit_processed',
                "Credit limit {$status} for user {$user->email}",
                $admin->id,
                [
                    'user_id' => $user->id,
                    'request_id' => $request->request_id,
                    'action' => $request->action,
                    'approved_limit' => $request->approved_limit ?? null
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'request_id' => $request->request_id,
                    'status' => $status,
                    'approved_limit' => $request->approved_limit ?? null,
                    'processed_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('credit_limit_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process credit limit request'
            ], 500);
        }
    }

    /**
     * Review uploaded document
     */
    public function reviewUploadedDocument(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'document_id' => 'required|integer|exists:uploaded_files,id',
                'review_status' => 'required|in:approved,rejected,needs_revision',
                'review_notes' => 'nullable|string|max:1000',
                'document_type' => 'nullable|string|max:100',
                'expiry_date' => 'nullable|date|after:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = Auth::user();
            
            // Get the document
            $document = DB::table('uploaded_files')
                ->where('id', $request->document_id)
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            // Check if document exists in storage
            if (!Storage::disk('public')->exists($document->path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document file not found in storage'
                ], 404);
            }

            DB::beginTransaction();

            // Create document review record
            $reviewId = DB::table('document_reviews')->insertGetId([
                'document_id' => $request->document_id,
                'reviewer_id' => $admin->id,
                'review_status' => $request->review_status,
                'review_notes' => $request->review_notes,
                'document_type' => $request->document_type,
                'expiry_date' => $request->expiry_date,
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update document status
            DB::table('uploaded_files')
                ->where('id', $request->document_id)
                ->update([
                    'review_status' => $request->review_status,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'document_type' => $request->document_type,
                    'expiry_date' => $request->expiry_date,
                    'updated_at' => now()
                ]);

            // If document belongs to a user, send notification
            if ($document->uploaded_by) {
                $user = User::find($document->uploaded_by);
                if ($user) {
                    $statusMessage = [
                        'approved' => 'Your document has been approved.',
                        'rejected' => 'Your document has been rejected. Please upload a new document.',
                        'needs_revision' => 'Your document needs revision. Please check the notes and resubmit.'
                    ];

                    $this->notificationService->send(
                        $user->id,
                        'Document Review Completed',
                        $statusMessage[$request->review_status] . ' ' . ($request->review_notes ?? ''),
                        'document'
                    );
                }
            }

            // Update related processes based on document type and status
            $this->updateRelatedProcesses($document, $request->review_status, $request->document_type);

            DB::commit();

            // Log the review
            $this->logService->log(
                'document_reviewed',
                "Document reviewed: {$document->original_name}",
                $admin->id,
                [
                    'document_id' => $request->document_id,
                    'review_id' => $reviewId,
                    'review_status' => $request->review_status,
                    'document_type' => $request->document_type,
                    'uploaded_by' => $document->uploaded_by
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Document review completed successfully',
                'data' => [
                    'document_id' => $request->document_id,
                    'review_id' => $reviewId,
                    'review_status' => $request->review_status,
                    'document_type' => $request->document_type,
                    'reviewed_at' => now()->toISOString(),
                    'reviewer' => $admin->name ?? $admin->email
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('document_review_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to review document'
            ], 500);
        }
    }

    /**
     * Get pending documents for review
     */
    public function getPendingDocuments(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);
            $documentType = $request->input('document_type', 'all');
            $priority = $request->input('priority', 'all'); // all, high, medium, low

            $query = DB::table('uploaded_files as uf')
                ->leftJoin('users as u', 'uf.uploaded_by', '=', 'u.id')
                ->leftJoin('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->whereNull('uf.review_status')
                ->orWhere('uf.review_status', 'pending')
                ->select([
                    'uf.*',
                    'u.email as uploader_email',
                    'cp.full_name as uploader_name'
                ])
                ->orderBy('uf.created_at', 'desc');

            if ($documentType !== 'all') {
                $query->where('uf.type', $documentType);
            }

            // Priority based on document age and type
            if ($priority !== 'all') {
                switch ($priority) {
                    case 'high':
                        $query->where('uf.created_at', '<=', now()->subDays(1));
                        break;
                    case 'medium':
                        $query->whereBetween('uf.created_at', [now()->subDays(3), now()->subDays(1)]);
                        break;
                    case 'low':
                        $query->where('uf.created_at', '>=', now()->subDays(3));
                        break;
                }
            }

            $total = $query->count();
            $documents = $query
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($doc) {
                    $age = now()->diffInDays($doc->created_at);
                    $priority = $age > 3 ? 'high' : ($age > 1 ? 'medium' : 'low');

                    return [
                        'id' => $doc->id,
                        'original_name' => $doc->original_name,
                        'type' => $doc->type,
                        'category' => $doc->category,
                        'size' => $doc->size,
                        'mime_type' => $doc->mime_type,
                        'path' => $doc->path,
                        'url' => Storage::url($doc->path),
                        'uploader' => [
                            'name' => $doc->uploader_name,
                            'email' => $doc->uploader_email
                        ],
                        'uploaded_at' => $doc->created_at,
                        'age_days' => $age,
                        'priority' => $priority
                    ];
                });

            // Summary statistics
            $summary = [
                'total_pending' => $total,
                'by_type' => DB::table('uploaded_files')
                    ->whereNull('review_status')
                    ->orWhere('review_status', 'pending')
                    ->select('type', DB::raw('COUNT(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'high_priority' => $documents->where('priority', 'high')->count(),
                'overdue' => $documents->where('age_days', '>', 3)->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'documents' => $documents,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit)
                    ],
                    'summary' => $summary
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending documents'
            ], 500);
        }
    }

    /**
     * Update related processes based on document review
     */
    private function updateRelatedProcesses($document, $reviewStatus, $documentType)
    {
        // Update loan applications if document is related to loan
        if ($document->category === 'loan_application' && $reviewStatus === 'approved') {
            DB::table('loan_applications')
                ->where('user_id', $document->uploaded_by)
                ->where('status', 'document_review')
                ->update([
                    'status' => 'approved',
                    'updated_at' => now()
                ]);
        }

        // Update account opening requests
        if ($document->category === 'account_opening' && $reviewStatus === 'approved') {
            DB::table('account_opening_requests')
                ->where('user_id', $document->uploaded_by)
                ->where('status', 'document_review')
                ->update([
                    'status' => 'approved',
                    'updated_at' => now()
                ]);
        }

        // Update KYC status
        if (in_array($documentType, ['id_card', 'passport', 'driving_license']) && $reviewStatus === 'approved') {
            DB::table('customer_profiles')
                ->where('user_id', $document->uploaded_by)
                ->update([
                    'kyc_status' => 'verified',
                    'kyc_verified_at' => now(),
                    'updated_at' => now()
                ]);
        }
    }
}