<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::with(['vouchers', 'banners'])->orderByDesc('created_at')->get();
        return response()->json([
            'success' => true,
            'data'    => $campaigns
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:150',
            'budget'     => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $campaign = Campaign::create([
            'name'       => $validated['name'],
            'budget'     => $validated['budget'] ?? 0,
            'start_date' => $validated['start_date'] ?? null,
            'end_date'   => $validated['end_date'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo chiến dịch khuyến mãi thành công.',
            'data'    => $campaign
        ], 201);
    }

    public function storeVoucher(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validate([
            'code'               => 'required|string|max:50|unique:vouchers,code',
            'voucher_type'       => 'nullable|string|in:ticket,combo,order,all',
            'discount_type'      => 'required|string|in:fixed_amount,percentage',
            'discount_value'     => 'required|numeric|min:0',
            'min_order_value'    => 'nullable|numeric|min:0',
            'max_discount_value' => 'nullable|numeric|min:0',
            'system_limit'       => 'nullable|integer|min:1',
            'limit_per_user'     => 'nullable|integer|min:1',
            'valid_from'         => 'nullable|date',
            'valid_until'        => 'nullable|date',
            'rules_engine'       => 'nullable|array',
        ]);

        $voucher = Voucher::create([
            'campaign_id'        => $campaign->campaign_id,
            'code'               => strtoupper($validated['code']),
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
            'rules_engine'       => $validated['rules_engine'] ?? ['applicable_tiers' => ['GOLD', 'PLATINUM']],
            'is_active'          => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo Voucher thành công trong Campaign.',
            'data'    => $voucher
        ], 201);
    }

    public function storeBanner(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validate([
            'title'     => 'required|string|max:150',
            'image_url' => 'required|string|max:255',
            'link_url'  => 'nullable|string|max:255',
        ]);

        $banner = Banner::create([
            'campaign_id' => $campaign->campaign_id,
            'title'       => $validated['title'],
            'image_url'   => $validated['image_url'],
            'link_url'    => $validated['link_url'] ?? null,
            'is_active'   => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tải ảnh Banner & map vào Campaign thành công.',
            'data'    => $banner
        ], 201);
    }

    public function roi($id)
    {
        $campaign = Campaign::with('vouchers')->findOrFail($id);

        $budget = (float) $campaign->budget ?: 500000000.0;
        $usedBudget = 125000000.0;
        $revenueGenerated = 1975000000.0;
        $roiPercentage = round((($revenueGenerated - $usedBudget) / $usedBudget) * 100, 2);

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
