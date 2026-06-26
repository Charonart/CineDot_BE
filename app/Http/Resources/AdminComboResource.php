<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminComboResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->combo_id,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => $this->price,
            'imageUrl'   => $this->image_url,
            'isActive'    => (bool) $this->is_active,
            'createdAt'   => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updatedAt'   => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
