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
            'id'       => $this->banner_id,
            'title'    => $this->title,
            'imageUrl' => $this->image_url,
            'linkUrl'  => $this->link_url,
            'order'    => $this->order,
            'isActive' => $this->is_active,
        ];
    }
}
