<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class UtilityServicesController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Get investment products
     */
    public function getInvestmentProducts(Request $request)
    {
        try {
            $type = $request->input('type', 'all'); // all, mutual_fund, bonds, stocks
            $riskLevel = $request->input('risk_level', 'all'); // low, medium, high
            $minAmount = $request->input('min_amount', 0);

            $query = DB::table('investment_products')
                ->where('is_active', true);

            if ($type !== 'all') {
                $query->where('type', $type);
            }

            if ($riskLevel !== 'all') {
                $query->where('risk_level', $riskLevel);
            }

            if ($minAmount > 0) {
                $query->where('min_investment', '<=', $minAmount);
            }

            $products = $query->get()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'type' => $product->type,
                    'description' => $product->description,
                    'risk_level' => $product->risk_level,
                    'expected_return' => $product->expected_return,
                    'min_investment' => $product->min_investment,
                    'max_investment' => $product->max_investment,
                    'investment_period' => $product->investment_period,
                    'fees' => [
                        'management_fee' => $product->management_fee,
                        'performance_fee' => $product->performance_fee,
                        'redemption_fee' => $product->redemption_fee
                    ],
                    'performance_data' => json_decode($product->performance_data ?? '{}', true),
                    'fund_manager' => $product->fund_manager,
                    'inception_date' => $product->inception_date,
                    'nav_per_unit' => $product->nav_per_unit,
                    'total_assets' => $product->total_assets,
                    'currency' => $product->currency ?? 'IDR',
                    'is_sharia_compliant' => (bool) $product->is_sharia_compliant
                ];
            });

            // Get market summary
            $marketSummary = [
                'total_products' => $products->count(),
                'by_type' => $products->groupBy('type')->map->count(),
                'by_risk_level' => $products->groupBy('risk_level')->map->count(),
                'avg_expected_return' => $products->avg('expected_return'),
                'top_performers' => $products->sortByDesc('expected_return')->take(5)->values()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products,
                    'market_summary' => $marketSummary,
                    'last_updated' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch investment products'
            ], 500);
        }
    }

    /**
     * Get market data
     */
    public function getMarketData(Request $request)
    {
        try {
            $dataType = $request->input('type', 'all'); // all, forex, stocks, commodities, crypto
            $period = $request->input('period', '1d'); // 1d, 1w, 1m, 3m, 1y

            // Get market data from database or external API
            $marketData = [];

            // Currency exchange rates
            if ($dataType === 'all' || $dataType === 'forex') {
                $marketData['forex'] = [
                    'USD_IDR' => [
                        'rate' => 15750.50,
                        'change' => -25.30,
                        'change_percent' => -0.16,
                        'last_updated' => now()->toISOString()
                    ],
                    'EUR_IDR' => [
                        'rate' => 17125.75,
                        'change' => 45.20,
                        'change_percent' => 0.26,
                        'last_updated' => now()->toISOString()
                    ],
                    'JPY_IDR' => [
                        'rate' => 105.25,
                        'change' => -1.15,
                        'change_percent' => -1.08,
                        'last_updated' => now()->toISOString()
                    ]
                ];
            }

            // Stock indices
            if ($dataType === 'all' || $dataType === 'stocks') {
                $marketData['indices'] = [
                    'IHSG' => [
                        'value' => 7245.67,
                        'change' => 32.45,
                        'change_percent' => 0.45,
                        'volume' => 8750000000,
                        'last_updated' => now()->toISOString()
                    ],
                    'LQ45' => [
                        'value' => 1025.34,
                        'change' => -5.67,
                        'change_percent' => -0.55,
                        'volume' => 5250000000,
                        'last_updated' => now()->toISOString()
                    ]
                ];
            }

            // Commodities
            if ($dataType === 'all' || $dataType === 'commodities') {
                $marketData['commodities'] = [
                    'gold' => [
                        'price' => 1950.25,
                        'currency' => 'USD',
                        'unit' => 'oz',
                        'change' => 12.50,
                        'change_percent' => 0.64,
                        'last_updated' => now()->toISOString()
                    ],
                    'oil_brent' => [
                        'price' => 85.75,
                        'currency' => 'USD',
                        'unit' => 'barrel',
                        'change' => -1.25,
                        'change_percent' => -1.44,
                        'last_updated' => now()->toISOString()
                    ]
                ];
            }

            // Cryptocurrency (if enabled)
            if ($dataType === 'all' || $dataType === 'crypto') {
                $marketData['crypto'] = [
                    'bitcoin' => [
                        'price' => 43250.75,
                        'currency' => 'USD',
                        'change' => 1250.30,
                        'change_percent' => 2.98,
                        'market_cap' => 847500000000,
                        'last_updated' => now()->toISOString()
                    ],
                    'ethereum' => [
                        'price' => 2650.45,
                        'currency' => 'USD',
                        'change' => -85.20,
                        'change_percent' => -3.11,
                        'market_cap' => 318750000000,
                        'last_updated' => now()->toISOString()
                    ]
                ];
            }

            // Market news/alerts
            $marketNews = [
                [
                    'title' => 'Bank Indonesia maintains interest rate at 6.00%',
                    'summary' => 'Central bank keeps policy rate unchanged amid inflation concerns',
                    'impact' => 'neutral',
                    'timestamp' => now()->subHours(2)->toISOString()
                ],
                [
                    'title' => 'IDR strengthens against USD',
                    'summary' => 'Rupiah gains on positive economic indicators',
                    'impact' => 'positive',
                    'timestamp' => now()->subHours(4)->toISOString()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'market_data' => $marketData,
                    'market_news' => $marketNews,
                    'period' => $period,
                    'last_updated' => now()->toISOString(),
                    'disclaimer' => 'Market data is for informational purposes only and may be delayed'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch market data'
            ], 500);
        }
    }

    /**
     * Get nearest units/branches
     */
    public function getNearestUnits(Request $request)
    {
        try {
            // Accept both lat/lon (frontend) and latitude/longitude
            $latitude = $request->input('lat', $request->input('latitude'));
            $longitude = $request->input('lon', $request->input('longitude'));

            if (!$latitude || !$longitude) {
                // If no coordinates, return all active units sorted by name
                $units = Unit::where('status', 'ACTIVE')
                    ->orderBy('unit_name')
                    ->get()
                    ->map(function ($unit) {
                        return [
                            'id' => $unit->id,
                            'unit_name' => $unit->unit_name,
                            'unit_code' => $unit->unit_code,
                            'type' => $unit->unit_type,
                            'address' => $unit->address,
                            'phone' => $unit->phone,
                            'distance' => 0,
                        ];
                    });

                return response()->json([
                    'status' => 'success',
                    'data' => $units
                ]);
            }

            $radius = $request->input('radius', 100); // Default 100km

            // Calculate distance using Haversine formula
            $units = Unit::select([
                    '*',
                    DB::raw("
                        (6371 * acos(
                            LEAST(1, cos(radians({$latitude})) * 
                            cos(radians(latitude)) * 
                            cos(radians(longitude) - radians({$longitude})) + 
                            sin(radians({$latitude})) * 
                            sin(radians(latitude)))
                        )) AS distance
                    ")
                ])
                ->where('status', 'ACTIVE')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->take(20)
                ->get()
                ->map(function ($unit) {
                    return [
                        'id' => $unit->id,
                        'unit_name' => $unit->unit_name,
                        'unit_code' => $unit->unit_code,
                        'type' => $unit->unit_type,
                        'address' => $unit->address,
                        'phone' => $unit->phone,
                        'distance' => round($unit->distance, 2),
                    ];
                });

            // If no units found within radius, return all active units
            if ($units->isEmpty()) {
                $units = Unit::where('status', 'ACTIVE')
                    ->orderBy('unit_name')
                    ->get()
                    ->map(function ($unit) {
                        return [
                            'id' => $unit->id,
                            'unit_name' => $unit->unit_name,
                            'unit_code' => $unit->unit_code,
                            'type' => $unit->unit_type,
                            'address' => $unit->address,
                            'phone' => $unit->phone,
                            'distance' => 0,
                        ];
                    });
            }

            return response()->json([
                'status' => 'success',
                'data' => $units
            ]);

        } catch (\Exception $e) {
            // Fallback: return all active units if calculation fails
            $units = Unit::where('status', 'ACTIVE')
                ->orderBy('unit_name')
                ->get()
                ->map(function ($unit) {
                    return [
                        'id' => $unit->id,
                        'unit_name' => $unit->unit_name,
                        'unit_code' => $unit->unit_code,
                        'type' => $unit->unit_type,
                        'address' => $unit->address,
                        'phone' => $unit->phone,
                        'distance' => 0,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $units
            ]);
        }
    }

    /**
     * Upload file utility
     */
    public function uploadFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:10240', // Max 10MB
                'type' => 'required|in:document,image,avatar,proof',
                'category' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            $type = $request->type;
            $category = $request->input('category', 'general');

            // Validate file type based on upload type
            $allowedTypes = $this->getAllowedFileTypes($type);
            $fileExtension = strtolower($file->getClientOriginalExtension());

            if (!in_array($fileExtension, $allowedTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => "File type not allowed for {$type}. Allowed types: " . implode(', ', $allowedTypes)
                ], 400);
            }

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $fileExtension;
            $directory = "uploads/{$type}/{$category}/" . date('Y/m');

            // Store file
            $path = $file->storeAs($directory, $filename, 'public');

            // Save file record to database
            $fileRecord = DB::table('uploaded_files')->insertGetId([
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'path' => $path,
                'type' => $type,
                'category' => $category,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log the upload
            if (Auth::check()) {
                $this->logService->log(
                    'file_uploaded',
                    "File uploaded: {$file->getClientOriginalName()}",
                    Auth::id(),
                    [
                        'file_id' => $fileRecord,
                        'type' => $type,
                        'size' => $file->getSize(),
                        'path' => $path
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'id' => $fileRecord,
                    'original_name' => $file->getClientOriginalName(),
                    'filename' => $filename,
                    'path' => $path,
                    'url' => Storage::url($path),
                    'type' => $type,
                    'category' => $category,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_at' => now()->toISOString()
                ]
            ], 201);

        } catch (\Exception $e) {
            if (Auth::check()) {
                $this->logService->log('file_upload_error', $e->getMessage(), Auth::id());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file'
            ], 500);
        }
    }

    /**
     * Get beneficiaries
     */
    public function getBeneficiaries(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type', 'all'); // all, internal, external, ewallet

            $query = DB::table('beneficiaries')
                ->where('user_id', $user->id)
                ->where('is_active', true);

            if ($type !== 'all') {
                $query->where('type', $type);
            }

            $beneficiaries = $query
                ->orderBy('is_favorite', 'desc')
                ->orderBy('name')
                ->get()
                ->map(function ($beneficiary) {
                    return [
                        'id' => $beneficiary->id,
                        'name' => $beneficiary->name,
                        'type' => $beneficiary->type,
                        'account_number' => $beneficiary->account_number,
                        'bank_code' => $beneficiary->bank_code,
                        'bank_name' => $beneficiary->bank_name,
                        'is_favorite' => (bool) $beneficiary->is_favorite,
                        'nickname' => $beneficiary->nickname,
                        'last_used' => $beneficiary->last_used,
                        'created_at' => $beneficiary->created_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'beneficiaries' => $beneficiaries,
                    'summary' => [
                        'total' => $beneficiaries->count(),
                        'favorites' => $beneficiaries->where('is_favorite', true)->count(),
                        'by_type' => $beneficiaries->groupBy('type')->map->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch beneficiaries'
            ], 500);
        }
    }

    /**
     * Add beneficiary
     */
    public function addBeneficiary(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'type' => 'required|in:internal,external,ewallet',
                'account_number' => 'required|string|max:50',
                'bank_code' => 'required_if:type,external|string|max:10',
                'bank_name' => 'required_if:type,external|string|max:100',
                'nickname' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            // Check for duplicate
            $existing = DB::table('beneficiaries')
                ->where('user_id', $user->id)
                ->where('account_number', $request->account_number)
                ->where('type', $request->type)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Beneficiary already exists'
                ], 409);
            }

            $beneficiaryId = DB::table('beneficiaries')->insertGetId([
                'user_id' => $user->id,
                'name' => $request->name,
                'type' => $request->type,
                'account_number' => $request->account_number,
                'bank_code' => $request->bank_code,
                'bank_name' => $request->bank_name,
                'nickname' => $request->nickname,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Beneficiary added successfully',
                'data' => [
                    'id' => $beneficiaryId,
                    'name' => $request->name,
                    'type' => $request->type,
                    'account_number' => $request->account_number,
                    'bank_name' => $request->bank_name
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add beneficiary'
            ], 500);
        }
    }

    /**
     * Delete beneficiary
     */
    public function deleteBeneficiary($id)
    {
        try {
            $user = Auth::user();

            $beneficiary = DB::table('beneficiaries')
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$beneficiary) {
                return response()->json([
                    'success' => false,
                    'message' => 'Beneficiary not found'
                ], 404);
            }

            DB::table('beneficiaries')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Beneficiary deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete beneficiary'
            ], 500);
        }
    }

    /**
     * Get allowed file types for upload type
     */
    private function getAllowedFileTypes($type)
    {
        $allowedTypes = [
            'document' => ['pdf', 'doc', 'docx', 'txt'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'avatar' => ['jpg', 'jpeg', 'png'],
            'proof' => ['jpg', 'jpeg', 'png', 'pdf']
        ];

        return $allowedTypes[$type] ?? [];
    }
}