<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->video_id,
            'name' => $this->video_name,
            'url'  => $this->video_url,
            'type' => $this->video_type,
        ];
    }
}
