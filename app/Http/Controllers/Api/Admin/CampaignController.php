<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Voucher;
use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CampaignController extends Controller
{
    /**
     * Display a listing of marketing campaigns.
     */
    public function index(Request $request)
    {
        $limit = (int) $request->get('limit', 15);
        $query = Campaign::with(['vouchers', 'banners']);

        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        $campaigns = $query->orderByDesc('created_at')->paginate($limit);

        // Compute summary metrics for each campaign item
        $items = collect($campaigns->items())->map(function ($camp) {
            $voucherIds = $camp->vouchers->pluck('voucher_id')->filter();
            $usedBudget = 0.0;
            $revenueGenerated = 0.0;

            if ($voucherIds->isNotEmpty()) {
                $usedBudget = (float) Booking::whereIn('voucher_id', $voucherIds)
                    ->whereIn('booking_status', ['completed', 'paid'])
                    ->sum('discount_amount');

                $revenueGenerated = (float) Booking::whereIn('voucher_id', $voucherIds)
                    ->whereIn('booking_status', ['completed', 'paid'])
                    ->sum('final_amount');
            }

            $budget = (float) ($camp->budget ?? 0);
            $roiPercentage = $usedBudget > 0 
                ? round((($revenueGenerated - $usedBudget) / $usedBudget) * 100, 2)
                : 0.0;

            return [
                'id'                => $camp->campaign_id,
                'name'              => $camp->name,
                'budget'            => $budget,
                'usedBudget'        => $usedBudget,
                'revenueGenerated'  => $revenueGenerated,
                'roiPercentage'     => $roiPercentage,
                'startDate'         => $camp->start_date ? Carbon::parse($camp->start_date)->format('Y-m-d') : null,
                'endDate'           => $camp->end_date ? Carbon::parse($camp->end_date)->format('Y-m-d') : null,
                'isActive'          => (bool) $camp->is_active,
                'vouchersCount'     => $camp->vouchers->count(),
                'bannersCount'      => $camp->banners->count(),
                'createdAt'         => $camp->created_at ? $camp->created_at->toIso8601String() : null,
                'updatedAt'         => $camp->updated_at ? $camp->updated_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'page'         => $campaigns->currentPage(),
                'results'      => $items,
                'totalPages'   => $campaigns->lastPage(),
                'totalResults' => $campaigns->total(),
            ]
        ]);
    }

    /**
     * Overview KPI statistics for Marketing Campaigns Hub
     */
    public function stats()
    {
        $totalCampaigns = Campaign::count();
        $activeCampaigns = Campaign::where('is_active', true)->count();
        $totalBudget = (float) Campaign::sum('budget');

        $voucherIds = Voucher::whereNotNull('campaign_id')->pluck('voucher_id')->filter();
        $totalRevenueGenerated = 0.0;
        $totalDiscountGiven = 0.0;

        if ($voucherIds->isNotEmpty()) {
            $totalRevenueGenerated = (float) Booking::whereIn('voucher_id', $voucherIds)
                ->whereIn('booking_status', ['completed', 'paid'])
                ->sum('final_amount');

            $totalDiscountGiven = (float) Booking::whereIn('voucher_id', $voucherIds)
                ->whereIn('booking_status', ['completed', 'paid'])
                ->sum('discount_amount');
        }

        $overallRoi = $totalDiscountGiven > 0
            ? round((($totalRevenueGenerated - $totalDiscountGiven) / $totalDiscountGiven) * 100, 2)
            : 0.0;

        return response()->json([
            'success' => true,
            'data'    => [
                'total_campaigns'         => $totalCampaigns,
                'active_campaigns'        => $activeCampaigns,
                'total_budget'            => $totalBudget,
                'total_revenue_generated' => $totalRevenueGenerated,
                'total_discount_given'    => $totalDiscountGiven,
                'overall_roi_percentage'  => $overallRoi,
            ]
        ]);
    }

    /**
     * Store a newly created marketing campaign.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:150',
            'budget'     => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'is_active'  => 'nullable|boolean',
        ]);

        $campaign = Campaign::create([
            'name'       => $validated['name'],
            'budget'     => $validated['budget'] ?? 0,
            'start_date' => $validated['start_date'] ?? null,
            'end_date'   => $validated['end_date'] ?? null,
            'is_active'  => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo chiến dịch tiếp thị thành công.',
            'data'    => $campaign
        ], 201);
    }

    /**
     * Display the specified campaign with associated vouchers and banners.
     */
    public function show(string $id)
    {
        $campaign = Campaign::with(['vouchers', 'banners'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $campaign
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, string $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validate([
            'name'       => 'sometimes|required|string|max:150',
            'budget'     => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'is_active'  => 'nullable|boolean',
        ]);

        $campaign->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin chiến dịch thành công.',
            'data'    => $campaign->fresh()->load(['vouchers', 'banners'])
        ]);
    }

    /**
     * Toggle active state.
     */
    public function toggleStatus(string $id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->is_active = !$campaign->is_active;
        $campaign->save();

        return response()->json([
            'success' => true,
            'message' => $campaign->is_active ? 'Đã kích hoạt chiến dịch.' : 'Đã tạm ngưng chiến dịch.',
            'data'    => $campaign
        ]);
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(string $id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa chiến dịch thành công.'
        ]);
    }

    /**
     * Store a voucher directly attached to this campaign.
     */
    public function storeVoucher(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validate([
            'code'               => 'required|string|max:50|unique:vouchers,code',
            'title'              => 'nullable|string|max:150',
            'description'        => 'nullable|string',
            'voucher_type'       => 'nullable|string|in:ticket,combo,order,all',
            'discount_type'      => 'required|string|in:fixed_amount,percentage',
            'discount_value'     => 'required|numeric|min:0',
            'min_order_value'    => 'nullable|numeric|min:0',
            'max_discount_value' => 'nullable|numeric|min:0',
            'system_limit'       => 'nullable|integer|min:1',
            'limit_per_user'     => 'nullable|integer|min:1',
            'valid_from'         => 'nullable|date',
            'valid_until'        => 'nullable|date',
        ]);

        $voucher = Voucher::create([
            'campaign_id'        => $campaign->campaign_id,
            'code'               => strtoupper($validated['code']),
            'title'              => $validated['title'] ?? null,
            'description'        => $validated['description'] ?? null,
            'voucher_type'       => $validated['voucher_type'] ?? 'all',
            'discount_type'      => $validated['discount_type'],
            'discount_value'     => $validated['discount_value'],
            'min_order_value'    => $validated['min_order_value'] ?? 0,
            'max_discount_value' => $validated['max_discount_value'] ?? null,
            'system_limit'       => $validated['system_limit'] ?? 1000,
            'limit_per_user'     => $validated['limit_per_user'] ?? 1,
            'used_count'         => 0,
            'valid_from'         => $validated['valid_from'] ?? now(),
            'valid_until'        => $validated['valid_until'] ?? now()->addMonth(),
            'is_active'          => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo Voucher thành công trong Chiến dịch.',
            'data'    => $voucher
        ], 201);
    }

    /**
     * Store a banner directly attached to this campaign.
     */
    public function storeBanner(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validate([
            'title'     => 'required|string|max:150',
            'image_url' => 'required|string|max:255',
            'link_url'  => 'nullable|string|max:255',
            'order'     => 'nullable|integer',
        ]);

        $banner = Banner::create([
            'campaign_id' => $campaign->campaign_id,
            'title'       => $validated['title'],
            'image_url'   => $validated['image_url'],
            'link_url'    => $validated['link_url'] ?? null,
            'order'       => $validated['order'] ?? 0,
            'is_active'   => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm Banner vào Chiến dịch thành công.',
            'data'    => $banner
        ], 201);
    }

    /**
     * Calculate ROI for this specific campaign.
     */
    public function roi($id)
    {
        $campaign = Campaign::with('vouchers')->findOrFail($id);

        $budget = (float) ($campaign->budget ?? 0);
        $voucherIds = $campaign->vouchers->pluck('voucher_id')->filter();

        if ($voucherIds->isNotEmpty()) {
            $usedBudget = (float) Booking::whereIn('voucher_id', $voucherIds)
                ->whereIn('booking_status', ['completed', 'paid'])
                ->sum('discount_amount');

            $revenueGenerated = (float) Booking::whereIn('voucher_id', $voucherIds)
                ->whereIn('booking_status', ['completed', 'paid'])
                ->sum('final_amount');
        } else {
            $usedBudget = 0.0;
            $revenueGenerated = 0.0;
        }

        $roiPercentage = $usedBudget > 0 
            ? round((($revenueGenerated - $usedBudget) / $usedBudget) * 100, 2)
            : 0.0;

        return response()->json([
            'success' => true,
            'data'    => [
                'campaign_id'       => $campaign->campaign_id,
                'campaign_name'     => $campaign->name,
                'budget'            => $budget,
                'used_budget'       => $usedBudget,
                'revenue_generated' => $revenueGenerated,
                'roi_percentage'    => $roiPercentage,
            ]
        ]);
    }
}
