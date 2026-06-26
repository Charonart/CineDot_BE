<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->review_id,
            'rating'    => $this->rating,
            'comment'   => $this->comment,
            'createdAt' => $this->created_at,
            'user' => [
                'id'       => $this->whenLoaded('user', fn() => $this->user->user_id),
                'username' => $this->whenLoaded('user', fn() => $this->user->username),
                'email'    => $this->whenLoaded('user', fn() => $this->user->email),
                'avatar'   => $this->whenLoaded('user', fn() => $this->user->avatar),
            ],
            'movie' => [
                'id'    => $this->whenLoaded('movie', fn() => $this->movie->id),
                'title' => $this->whenLoaded('movie', fn() => $this->movie->title),
                'slug'  => $this->whenLoaded('movie', fn() => $this->movie->slug),
            ],
        ];
    }
}
