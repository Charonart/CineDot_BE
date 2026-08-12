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
            'code'               => $this->code,
            'discountType'      => $this->discount_type,
            'discountValue'     => $this->discount_value,
            'minOrderValue'    => $this->min_order_value,
            'maxDiscountValue' => $this->max_discount_value,
            'validFrom'         => $this->valid_from ? $this->valid_from->toIso8601String() : null,
            'usageLimit'        => $this->system_limit,
            'system_limit'      => $this->system_limit,
            'limit_per_user'    => $this->limit_per_user,
            'used_count'        => $this->used_count,
            'isActive'          => (bool) $this->is_active,
            'createdAt'         => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updatedAt'         => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
