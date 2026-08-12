<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->province_id,
            'province_name' => $this->province_name,
            'name'          => $this->province_name,
        ];
    }
}
