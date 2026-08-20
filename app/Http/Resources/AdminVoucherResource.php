<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminVoucherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->voucher_id,
            'campaignId'         => $this->campaign_id,
            'campaignName'       => $this->campaign ? $this->campaign->name : null,
            'code'               => $this->code,
            'title'              => $this->title,
            'description'        => $this->description,
            'voucherType'        => $this->voucher_type,
            'discountType'       => $this->discount_type,
            'discountValue'      => (float) $this->discount_value,
            'minOrderValue'      => (float) ($this->min_order_value ?? 0),
            'maxDiscountValue'   => $this->max_discount_value ? (float) $this->max_discount_value : null,
            'validFrom'          => $this->valid_from ? $this->valid_from->toIso8601String() : null,
            'validUntil'         => $this->valid_until ? $this->valid_until->toIso8601String() : null,
            'systemLimit'        => $this->system_limit,
            'usageLimit'         => $this->system_limit,
            'limitPerUser'       => $this->limit_per_user,
            'usedCount'          => (int) ($this->used_count ?? 0),
            'isActive'           => (bool) $this->is_active,
            'createdAt'          => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updatedAt'          => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
