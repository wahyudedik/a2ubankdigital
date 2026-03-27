<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\User;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DebtCollectorController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get debt collector assignments
     */
    public function getAssignments(Request $request)
    {
        try {
            $collector = Auth::user();
            $status = $request->input('status', 'all'); // all, assigned, in_progress, completed, escalated
            $priority = $request->input('priority', 'all'); // all, low, medium, high, urgent
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);

            $query = DB::table('debt_collection_assignments as dca')
                ->join('loans as l', 'dca.loan_id', '=', 'l.id')
                ->join('users as u', 'l.user_id', '=', 'u.id')
                ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->where('dca.collector_id', $collector->id)
                ->select([
                    'dca.*',
                    'l.amount as loan_amount',
                    'l.outstanding_balance',
                    'l.overdue_days',
                    'u.email as customer_email',
                    'cp.full_name as customer_name',
                    'cp.phone_number as customer_phone',
                    'cp.address as customer_address'
                ]);

            if ($status !== 'all') {
                $query->where('dca.status', $status);
            }

            if ($priority !== 'all') {
                $query->where('dca.priority', $priority);
            }

            $total = $query->count();
            $assignments = $query
                ->orderBy('dca.priority_score', 'desc')
                ->orderBy('dca.assigned_at', 'desc')
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'loan_id' => $assignment->loan_id,
                        'customer' => [
                            'name' => $assignment->customer_name,
                            'email' => $assignment->customer_email,
                            'phone' => $assignment->customer_phone,
                            'address' => $assignment->customer_address
                        ],
                        'loan_details' => [
                            'amount' => $assignment->loan_amount,
                            'outstanding_balance' => $assignment->outstanding_balance,
                            'overdue_days' => $assignment->overdue_days
                        ],
                        'assignment_details' => [
                            'status' => $assignment->status,
                            'priority' => $assignment->priority,
                            'priority_score' => $assignment->priority_score,
                            'assigned_at' => $assignment->assigned_at,
                            'due_date' => $assignment->due_date,
                            'notes' => $assignment->notes,
                            'last_contact_date' => $assignment->last_contact_date,
                            'contact_attempts' => $assignment->contact_attempts,
                            'promised_payment_date' => $assignment->promised_payment_date,
                            'promised_amount' => $assignment->promised_amount
                        ]
                    ];
                });

            // Get summary statistics
            $summary = [
                'total_assignments' => $total,
                'by_status' => DB::table('debt_collection_assignments')
                    ->where('collector_id', $collector->id)
                    ->select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'by_priority' => DB::table('debt_collection_assignments')
                    ->where('collector_id', $collector->id)
                    ->select('priority', DB::raw('COUNT(*) as count'))
                    ->groupBy('priority')
                    ->pluck('count', 'priority'),
                'total_outstanding' => DB::table('debt_collection_assignments as dca')
                    ->join('loans as l', 'dca.loan_id', '=', 'l.id')
                    ->where('dca.collector_id', $collector->id)
                    ->whereIn('dca.status', ['assigned', 'in_progress'])
                    ->sum('l.outstanding_balance'),
                'overdue_cases' => DB::table('debt_collection_assignments as dca')
                    ->join('loans as l', 'dca.loan_id', '=', 'l.id')
                    ->where('dca.collector_id', $collector->id)
                    ->where('dca.due_date', '<', now())
                    ->whereIn('dca.status', ['assigned', 'in_progress'])
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'assignments' => $assignments,
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
            $this->logService->log('debt_collector_assignments_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignments'
            ], 500);
        }
    }

    /**
     * Submit visit report
     */
    public function submitVisitReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'assignment_id' => 'required|integer|exists:debt_collection_assignments,id',
                'visit_type' => 'required|in:phone_call,home_visit,office_visit,email_contact',
                'contact_result' => 'required|in:contacted,not_available,refused,disconnected,wrong_number',
                'customer_response' => 'nullable|in:cooperative,hostile,evasive,promised_payment,partial_payment,dispute',
                'notes' => 'required|string|max:1000',
                'promised_payment_date' => 'nullable|date|after:today',
                'promised_amount' => 'nullable|numeric|min:0',
                'next_action' => 'required|in:follow_up,escalate,legal_action,close_case,schedule_visit',
                'next_action_date' => 'nullable|date|after_or_equal:today',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|max:5120' // 5MB max per file
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $collector = Auth::user();
            
            // Verify assignment belongs to collector
            $assignment = DB::table('debt_collection_assignments')
                ->where('id', $request->assignment_id)
                ->where('collector_id', $collector->id)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found or not authorized'
                ], 404);
            }

            DB::beginTransaction();

            // Handle file attachments
            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('debt_collection/reports/' . date('Y/m'), 'public');
                    $attachmentPaths[] = $path;
                }
            }

            // Create visit report
            $reportId = DB::table('debt_collection_reports')->insertGetId([
                'assignment_id' => $request->assignment_id,
                'collector_id' => $collector->id,
                'visit_type' => $request->visit_type,
                'contact_result' => $request->contact_result,
                'customer_response' => $request->customer_response,
                'notes' => $request->notes,
                'promised_payment_date' => $request->promised_payment_date,
                'promised_amount' => $request->promised_amount,
                'next_action' => $request->next_action,
                'next_action_date' => $request->next_action_date,
                'attachments' => json_encode($attachmentPaths),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update assignment
            $updateData = [
                'last_contact_date' => now(),
                'contact_attempts' => DB::raw('contact_attempts + 1'),
                'updated_at' => now()
            ];

            if ($request->promised_payment_date) {
                $updateData['promised_payment_date'] = $request->promised_payment_date;
            }

            if ($request->promised_amount) {
                $updateData['promised_amount'] = $request->promised_amount;
            }

            // Update status based on next action
            switch ($request->next_action) {
                case 'escalate':
                    $updateData['status'] = 'escalated';
                    break;
                case 'legal_action':
                    $updateData['status'] = 'legal_action';
                    break;
                case 'close_case':
                    $updateData['status'] = 'completed';
                    break;
                default:
                    $updateData['status'] = 'in_progress';
            }

            DB::table('debt_collection_assignments')
                ->where('id', $request->assignment_id)
                ->update($updateData);

            // Create follow-up task if needed
            if ($request->next_action_date) {
                DB::table('debt_collection_tasks')->insert([
                    'assignment_id' => $request->assignment_id,
                    'collector_id' => $collector->id,
                    'task_type' => $request->next_action,
                    'scheduled_date' => $request->next_action_date,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            // Log the report submission
            $this->logService->log(
                'debt_collection_report_submitted',
                "Visit report submitted for assignment {$request->assignment_id}",
                $collector->id,
                [
                    'assignment_id' => $request->assignment_id,
                    'report_id' => $reportId,
                    'visit_type' => $request->visit_type,
                    'contact_result' => $request->contact_result,
                    'next_action' => $request->next_action
                ]
            );

            // Send notification to supervisor if escalated
            if ($request->next_action === 'escalate') {
                $this->notificationService->sendToRole(
                    'debt_collection_supervisor',
                    'Case Escalated',
                    "Debt collection case has been escalated by {$collector->name}",
                    'debt_collection',
                    ['assignment_id' => $request->assignment_id]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Visit report submitted successfully',
                'data' => [
                    'report_id' => $reportId,
                    'assignment_id' => $request->assignment_id,
                    'status' => $updateData['status'],
                    'next_action' => $request->next_action,
                    'next_action_date' => $request->next_action_date,
                    'submitted_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('debt_collection_report_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit visit report'
            ], 500);
        }
    }

    /**
     * Update assignment status
     */
    public function updateAssignment(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:assigned,in_progress,completed,escalated,legal_action,closed',
                'notes' => 'nullable|string|max:500',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'promised_payment_date' => 'nullable|date|after:today',
                'promised_amount' => 'nullable|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $collector = Auth::user();
            
            // Verify assignment belongs to collector
            $assignment = DB::table('debt_collection_assignments')
                ->where('id', $id)
                ->where('collector_id', $collector->id)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found or not authorized'
                ], 404);
            }

            $oldStatus = $assignment->status;
            
            // Update assignment
            $updateData = [
                'status' => $request->status,
                'updated_at' => now()
            ];

            if ($request->notes) {
                $updateData['notes'] = $request->notes;
            }

            if ($request->priority) {
                $updateData['priority'] = $request->priority;
                $updateData['priority_score'] = $this->calculatePriorityScore($request->priority);
            }

            if ($request->promised_payment_date) {
                $updateData['promised_payment_date'] = $request->promised_payment_date;
            }

            if ($request->promised_amount) {
                $updateData['promised_amount'] = $request->promised_amount;
            }

            if ($request->status === 'completed') {
                $updateData['completed_at'] = now();
            }

            DB::table('debt_collection_assignments')
                ->where('id', $id)
                ->update($updateData);

            // Log the status change
            $this->logService->log(
                'debt_collection_status_updated',
                "Assignment status changed from {$oldStatus} to {$request->status}",
                $collector->id,
                [
                    'assignment_id' => $id,
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'notes' => $request->notes
                ]
            );

            // Send notifications based on status
            if ($request->status === 'escalated') {
                $this->notificationService->sendToRole(
                    'debt_collection_supervisor',
                    'Case Escalated',
                    "Assignment #{$id} has been escalated",
                    'debt_collection'
                );
            } elseif ($request->status === 'completed') {
                $this->notificationService->sendToRole(
                    'debt_collection_manager',
                    'Case Completed',
                    "Assignment #{$id} has been completed by {$collector->name}",
                    'debt_collection'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Assignment updated successfully',
                'data' => [
                    'assignment_id' => $id,
                    'old_status' => $oldStatus,
                    'new_status' => $request->status,
                    'updated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('debt_collection_update_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment'
            ], 500);
        }
    }

    /**
     * Get assignment details
     */
    public function getAssignmentDetails($id)
    {
        try {
            $collector = Auth::user();
            
            $assignment = DB::table('debt_collection_assignments as dca')
                ->join('loans as l', 'dca.loan_id', '=', 'l.id')
                ->join('users as u', 'l.user_id', '=', 'u.id')
                ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                ->where('dca.id', $id)
                ->where('dca.collector_id', $collector->id)
                ->select([
                    'dca.*',
                    'l.amount as loan_amount',
                    'l.outstanding_balance',
                    'l.overdue_days',
                    'l.next_payment_date',
                    'l.monthly_payment',
                    'u.email as customer_email',
                    'cp.full_name as customer_name',
                    'cp.phone_number as customer_phone',
                    'cp.address as customer_address',
                    'cp.date_of_birth',
                    'cp.occupation'
                ])
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found'
                ], 404);
            }

            // Get visit reports
            $reports = DB::table('debt_collection_reports')
                ->where('assignment_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get payment history
            $paymentHistory = DB::table('transactions')
                ->where('loan_id', $assignment->loan_id)
                ->where('type', 'loan_payment')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'assignment' => $assignment,
                    'reports' => $reports,
                    'payment_history' => $paymentHistory
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignment details'
            ], 500);
        }
    }

    /**
     * Calculate priority score based on priority level
     */
    private function calculatePriorityScore($priority)
    {
        $scores = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'urgent' => 4
        ];

        return $scores[$priority] ?? 1;
    }
}