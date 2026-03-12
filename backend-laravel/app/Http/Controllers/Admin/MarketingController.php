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
use Illuminate\Support\Facades\Mail;

class MarketingController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get customer segments
     */
    public function getCustomerSegments(Request $request)
    {
        try {
            $segmentType = $request->input('segment_type', 'all'); // all, balance, activity, product, demographic
            $includeStats = $request->input('include_stats', true);

            $segments = [];

            // Balance-based segments
            if ($segmentType === 'all' || $segmentType === 'balance') {
                $balanceSegments = DB::table('users as u')
                    ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                    ->join('accounts as a', 'u.id', '=', 'a.user_id')
                    ->where('u.role', 'customer')
                    ->where('u.status', 'active')
                    ->select([
                        DB::raw('CASE 
                            WHEN a.balance >= 1000000000 THEN "VIP"
                            WHEN a.balance >= 500000000 THEN "Premium"
                            WHEN a.balance >= 100000000 THEN "Gold"
                            WHEN a.balance >= 50000000 THEN "Silver"
                            ELSE "Basic"
                        END as segment'),
                        DB::raw('COUNT(*) as customer_count'),
                        DB::raw('AVG(a.balance) as avg_balance'),
                        DB::raw('SUM(a.balance) as total_balance'),
                        DB::raw('MIN(a.balance) as min_balance'),
                        DB::raw('MAX(a.balance) as max_balance')
                    ])
                    ->groupBy('segment')
                    ->get();

                $segments['balance_segments'] = $balanceSegments->map(function ($segment) {
                    return [
                        'segment_name' => $segment->segment,
                        'customer_count' => $segment->customer_count,
                        'avg_balance' => $segment->avg_balance,
                        'total_balance' => $segment->total_balance,
                        'min_balance' => $segment->min_balance,
                        'max_balance' => $segment->max_balance,
                        'percentage' => 0 // Will be calculated later
                    ];
                });
            }

            // Activity-based segments
            if ($segmentType === 'all' || $segmentType === 'activity') {
                $activitySegments = DB::table('users as u')
                    ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                    ->leftJoin('transactions as t', function($join) {
                        $join->on('u.id', '=', 't.user_id')
                             ->where('t.created_at', '>=', now()->subDays(30));
                    })
                    ->where('u.role', 'customer')
                    ->where('u.status', 'active')
                    ->select([
                        'u.id',
                        'cp.full_name',
                        'u.email',
                        DB::raw('COUNT(t.id) as transaction_count'),
                        DB::raw('COALESCE(SUM(t.amount), 0) as transaction_volume'),
                        DB::raw('CASE 
                            WHEN COUNT(t.id) >= 20 THEN "Highly Active"
                            WHEN COUNT(t.id) >= 10 THEN "Active"
                            WHEN COUNT(t.id) >= 5 THEN "Moderately Active"
                            WHEN COUNT(t.id) >= 1 THEN "Low Activity"
                            ELSE "Inactive"
                        END as activity_segment')
                    ])
                    ->groupBy('u.id', 'cp.full_name', 'u.email')
                    ->get()
                    ->groupBy('activity_segment')
                    ->map(function ($customers, $segment) {
                        return [
                            'segment_name' => $segment,
                            'customer_count' => $customers->count(),
                            'avg_transactions' => $customers->avg('transaction_count'),
                            'avg_volume' => $customers->avg('transaction_volume'),
                            'total_volume' => $customers->sum('transaction_volume'),
                            'customers' => $customers->take(10) // Sample customers
                        ];
                    });

                $segments['activity_segments'] = $activitySegments;
            }

            // Product usage segments
            if ($segmentType === 'all' || $segmentType === 'product') {
                $productSegments = DB::table('users as u')
                    ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                    ->leftJoin('loans as l', 'u.id', '=', 'l.user_id')
                    ->leftJoin('accounts as a', function($join) {
                        $join->on('u.id', '=', 'a.user_id')
                             ->where('a.type', 'savings');
                    })
                    ->where('u.role', 'customer')
                    ->select([
                        'u.id',
                        'cp.full_name',
                        'u.email',
                        DB::raw('CASE WHEN l.id IS NOT NULL THEN 1 ELSE 0 END as has_loan'),
                        DB::raw('CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END as has_savings'),
                        DB::raw('CASE 
                            WHEN l.id IS NOT NULL AND a.id IS NOT NULL THEN "Multi-Product"
                            WHEN l.id IS NOT NULL THEN "Loan Only"
                            WHEN a.id IS NOT NULL THEN "Savings Only"
                            ELSE "No Products"
                        END as product_segment')
                    ])
                    ->get()
                    ->groupBy('product_segment')
                    ->map(function ($customers, $segment) {
                        return [
                            'segment_name' => $segment,
                            'customer_count' => $customers->count(),
                            'percentage' => 0, // Will be calculated later
                            'customers' => $customers->take(10)
                        ];
                    });

                $segments['product_segments'] = $productSegments;
            }

            // Demographic segments
            if ($segmentType === 'all' || $segmentType === 'demographic') {
                $demographicSegments = DB::table('users as u')
                    ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
                    ->where('u.role', 'customer')
                    ->select([
                        DB::raw('CASE 
                            WHEN TIMESTAMPDIFF(YEAR, cp.date_of_birth, CURDATE()) < 25 THEN "Gen Z (18-24)"
                            WHEN TIMESTAMPDIFF(YEAR, cp.date_of_birth, CURDATE()) < 35 THEN "Millennial (25-34)"
                            WHEN TIMESTAMPDIFF(YEAR, cp.date_of_birth, CURDATE()) < 45 THEN "Gen X (35-44)"
                            WHEN TIMESTAMPDIFF(YEAR, cp.date_of_birth, CURDATE()) < 55 THEN "Boomer (45-54)"
                            ELSE "Senior (55+)"
                        END as age_segment'),
                        'cp.gender',
                        'cp.occupation',
                        DB::raw('COUNT(*) as customer_count')
                    ])
                    ->whereNotNull('cp.date_of_birth')
                    ->groupBy('age_segment', 'cp.gender', 'cp.occupation')
                    ->get()
                    ->groupBy('age_segment');

                $segments['demographic_segments'] = $demographicSegments;
            }

            // Calculate percentages
            $totalCustomers = User::where('role', 'customer')->where('status', 'active')->count();
            
            foreach ($segments as $segmentType => $segmentData) {
                if (is_array($segmentData) || is_object($segmentData)) {
                    foreach ($segmentData as $key => $segment) {
                        if (isset($segment['customer_count'])) {
                            $segments[$segmentType][$key]['percentage'] = $totalCustomers > 0 
                                ? round(($segment['customer_count'] / $totalCustomers) * 100, 2) 
                                : 0;
                        }
                    }
                }
            }

            // Marketing insights
            $insights = [
                'total_customers' => $totalCustomers,
                'segment_distribution' => $segments,
                'recommendations' => $this->generateMarketingRecommendations($segments),
                'campaign_opportunities' => $this->identifyCampaignOpportunities($segments)
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'segments' => $segments,
                    'insights' => $insights,
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->log('customer_segments_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer segments'
            ], 500);
        }
    }

    /**
     * Send promotional campaign
     */
    public function sendPromotion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'campaign_name' => 'required|string|max:200',
                'campaign_type' => 'required|in:email,sms,push_notification,in_app',
                'target_segments' => 'required|array',
                'target_segments.*' => 'string',
                'subject' => 'required|string|max:200',
                'message' => 'required|string|max:2000',
                'call_to_action' => 'nullable|string|max:100',
                'action_url' => 'nullable|url',
                'schedule_type' => 'required|in:immediate,scheduled',
                'scheduled_at' => 'required_if:schedule_type,scheduled|nullable|date|after:now',
                'personalization' => 'nullable|boolean',
                'track_engagement' => 'nullable|boolean'
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

            // Create campaign record
            $campaignId = DB::table('marketing_campaigns')->insertGetId([
                'name' => $request->campaign_name,
                'type' => $request->campaign_type,
                'target_segments' => json_encode($request->target_segments),
                'subject' => $request->subject,
                'message' => $request->message,
                'call_to_action' => $request->call_to_action,
                'action_url' => $request->action_url,
                'schedule_type' => $request->schedule_type,
                'scheduled_at' => $request->scheduled_at,
                'personalization_enabled' => $request->input('personalization', false),
                'track_engagement' => $request->input('track_engagement', true),
                'status' => $request->schedule_type === 'immediate' ? 'sending' : 'scheduled',
                'created_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Get target customers based on segments
            $targetCustomers = $this->getCustomersBySegments($request->target_segments);

            if ($targetCustomers->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No customers found for the selected segments'
                ], 400);
            }

            // Create campaign recipients
            $recipients = [];
            foreach ($targetCustomers as $customer) {
                $recipients[] = [
                    'campaign_id' => $campaignId,
                    'customer_id' => $customer->id,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            DB::table('campaign_recipients')->insert($recipients);

            // Send immediately or schedule
            if ($request->schedule_type === 'immediate') {
                $this->executeCampaign($campaignId, $request->all());
            }

            DB::commit();

            // Log campaign creation
            $this->logService->log(
                'marketing_campaign_created',
                "Marketing campaign '{$request->campaign_name}' created",
                $admin->id,
                [
                    'campaign_id' => $campaignId,
                    'type' => $request->campaign_type,
                    'target_count' => $targetCustomers->count(),
                    'schedule_type' => $request->schedule_type
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $request->schedule_type === 'immediate' 
                    ? 'Campaign sent successfully' 
                    : 'Campaign scheduled successfully',
                'data' => [
                    'campaign_id' => $campaignId,
                    'target_count' => $targetCustomers->count(),
                    'status' => $request->schedule_type === 'immediate' ? 'sending' : 'scheduled',
                    'scheduled_at' => $request->scheduled_at,
                    'created_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('marketing_campaign_error', $e->getMessage(), Auth::id());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send promotion'
            ], 500);
        }
    }

    /**
     * Get campaign performance
     */
    public function getCampaignPerformance(Request $request)
    {
        try {
            $campaignId = $request->input('campaign_id');
            $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
            $endDate = $request->input('end_date', now()->toDateString());

            $query = DB::table('marketing_campaigns as mc')
                ->leftJoin('campaign_recipients as cr', 'mc.id', '=', 'cr.campaign_id')
                ->whereBetween('mc.created_at', [$startDate, $endDate]);

            if ($campaignId) {
                $query->where('mc.id', $campaignId);
            }

            $campaigns = $query->select([
                'mc.*',
                DB::raw('COUNT(cr.id) as total_recipients'),
                DB::raw('SUM(CASE WHEN cr.status = "sent" THEN 1 ELSE 0 END) as sent_count'),
                DB::raw('SUM(CASE WHEN cr.status = "delivered" THEN 1 ELSE 0 END) as delivered_count'),
                DB::raw('SUM(CASE WHEN cr.status = "opened" THEN 1 ELSE 0 END) as opened_count'),
                DB::raw('SUM(CASE WHEN cr.status = "clicked" THEN 1 ELSE 0 END) as clicked_count'),
                DB::raw('SUM(CASE WHEN cr.status = "converted" THEN 1 ELSE 0 END) as converted_count')
            ])
            ->groupBy('mc.id')
            ->get()
            ->map(function ($campaign) {
                $deliveryRate = $campaign->total_recipients > 0 
                    ? ($campaign->delivered_count / $campaign->total_recipients) * 100 
                    : 0;
                
                $openRate = $campaign->delivered_count > 0 
                    ? ($campaign->opened_count / $campaign->delivered_count) * 100 
                    : 0;
                
                $clickRate = $campaign->opened_count > 0 
                    ? ($campaign->clicked_count / $campaign->opened_count) * 100 
                    : 0;
                
                $conversionRate = $campaign->clicked_count > 0 
                    ? ($campaign->converted_count / $campaign->clicked_count) * 100 
                    : 0;

                return [
                    'campaign_id' => $campaign->id,
                    'name' => $campaign->name,
                    'type' => $campaign->type,
                    'status' => $campaign->status,
                    'created_at' => $campaign->created_at,
                    'metrics' => [
                        'total_recipients' => $campaign->total_recipients,
                        'sent_count' => $campaign->sent_count,
                        'delivered_count' => $campaign->delivered_count,
                        'opened_count' => $campaign->opened_count,
                        'clicked_count' => $campaign->clicked_count,
                        'converted_count' => $campaign->converted_count,
                        'delivery_rate' => round($deliveryRate, 2),
                        'open_rate' => round($openRate, 2),
                        'click_rate' => round($clickRate, 2),
                        'conversion_rate' => round($conversionRate, 2)
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'campaigns' => $campaigns,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campaign performance'
            ], 500);
        }
    }

    /**
     * Get customers by segments
     */
    private function getCustomersBySegments($segments)
    {
        $query = DB::table('users as u')
            ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
            ->join('accounts as a', 'u.id', '=', 'a.user_id')
            ->where('u.role', 'customer')
            ->where('u.status', 'active');

        // Apply segment filters
        $query->where(function ($q) use ($segments) {
            foreach ($segments as $segment) {
                switch ($segment) {
                    case 'VIP':
                        $q->orWhere('a.balance', '>=', 1000000000);
                        break;
                    case 'Premium':
                        $q->orWhere(function ($sq) {
                            $sq->where('a.balance', '>=', 500000000)
                               ->where('a.balance', '<', 1000000000);
                        });
                        break;
                    case 'Gold':
                        $q->orWhere(function ($sq) {
                            $sq->where('a.balance', '>=', 100000000)
                               ->where('a.balance', '<', 500000000);
                        });
                        break;
                    case 'Silver':
                        $q->orWhere(function ($sq) {
                            $sq->where('a.balance', '>=', 50000000)
                               ->where('a.balance', '<', 100000000);
                        });
                        break;
                    case 'Basic':
                        $q->orWhere('a.balance', '<', 50000000);
                        break;
                }
            }
        });

        return $query->select(['u.id', 'u.email', 'cp.full_name', 'cp.phone_number'])
                    ->distinct()
                    ->get();
    }

    /**
     * Execute campaign (send messages)
     */
    private function executeCampaign($campaignId, $campaignData)
    {
        // Get campaign recipients
        $recipients = DB::table('campaign_recipients as cr')
            ->join('users as u', 'cr.customer_id', '=', 'u.id')
            ->join('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
            ->where('cr.campaign_id', $campaignId)
            ->where('cr.status', 'pending')
            ->select(['cr.id', 'u.id as user_id', 'u.email', 'cp.full_name', 'cp.phone_number'])
            ->get();

        foreach ($recipients as $recipient) {
            try {
                // Personalize message if enabled
                $message = $campaignData['message'];
                if ($campaignData['personalization'] ?? false) {
                    $message = str_replace('{name}', $recipient->full_name, $message);
                }

                // Send based on campaign type
                switch ($campaignData['campaign_type']) {
                    case 'email':
                        // Send email (implement your email service)
                        break;
                    case 'sms':
                        // Send SMS (implement your SMS service)
                        break;
                    case 'push_notification':
                        $this->notificationService->send(
                            $recipient->user_id,
                            $campaignData['subject'],
                            $message,
                            'marketing'
                        );
                        break;
                    case 'in_app':
                        $this->notificationService->send(
                            $recipient->user_id,
                            $campaignData['subject'],
                            $message,
                            'promotion'
                        );
                        break;
                }

                // Update recipient status
                DB::table('campaign_recipients')
                    ->where('id', $recipient->id)
                    ->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'updated_at' => now()
                    ]);

            } catch (\Exception $e) {
                // Log individual send failure
                DB::table('campaign_recipients')
                    ->where('id', $recipient->id)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'updated_at' => now()
                    ]);
            }
        }

        // Update campaign status
        DB::table('marketing_campaigns')
            ->where('id', $campaignId)
            ->update([
                'status' => 'completed',
                'sent_at' => now(),
                'updated_at' => now()
            ]);
    }

    /**
     * Generate marketing recommendations
     */
    private function generateMarketingRecommendations($segments)
    {
        $recommendations = [];

        // Analyze balance segments
        if (isset($segments['balance_segments'])) {
            $vipCount = $segments['balance_segments']->where('segment_name', 'VIP')->first()->customer_count ?? 0;
            $basicCount = $segments['balance_segments']->where('segment_name', 'Basic')->first()->customer_count ?? 0;

            if ($vipCount > 0) {
                $recommendations[] = [
                    'type' => 'upsell',
                    'message' => 'Target VIP customers with premium investment products',
                    'priority' => 'high'
                ];
            }

            if ($basicCount > $vipCount * 5) {
                $recommendations[] = [
                    'type' => 'engagement',
                    'message' => 'Focus on converting Basic customers to higher tiers',
                    'priority' => 'medium'
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Identify campaign opportunities
     */
    private function identifyCampaignOpportunities($segments)
    {
        $opportunities = [];

        // Product cross-sell opportunities
        if (isset($segments['product_segments'])) {
            $savingsOnly = $segments['product_segments']['Savings Only']['customer_count'] ?? 0;
            $loanOnly = $segments['product_segments']['Loan Only']['customer_count'] ?? 0;

            if ($savingsOnly > 0) {
                $opportunities[] = [
                    'type' => 'cross_sell',
                    'target_segment' => 'Savings Only',
                    'opportunity' => 'Loan products',
                    'potential_customers' => $savingsOnly
                ];
            }

            if ($loanOnly > 0) {
                $opportunities[] = [
                    'type' => 'cross_sell',
                    'target_segment' => 'Loan Only',
                    'opportunity' => 'Savings accounts',
                    'potential_customers' => $loanOnly
                ];
            }
        }

        return $opportunities;
    }
}