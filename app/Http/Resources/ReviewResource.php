<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->review_id,
            'userId'    => $this->user_id,
            'username'  => $this->whenLoaded('user', fn() => $this->user->username),
            'avatar'    => $this->whenLoaded('user', fn() => $this->user->avatar),
            'rating'    => $this->rating,
            'comment'   => $this->comment,
            'createdAt' => $this->created_at,
        ];
    }
}
