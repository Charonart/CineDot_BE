<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->banner_id,
            'campaignId'   => $this->campaign_id,
            'campaignName' => $this->campaign ? $this->campaign->name : null,
            'title'        => $this->title,
            'imageUrl'     => $this->image_url,
            'linkUrl'      => $this->link_url,
            'order'        => (int) ($this->order ?? 0),
            'isActive'     => (bool) $this->is_active,
            'createdAt'    => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updatedAt'    => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
