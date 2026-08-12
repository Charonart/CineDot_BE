<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->user_id,
            'user_id'      => $this->user_id,
            'username'     => $this->username,
            'email'        => $this->email,
            'fullname'     => $this->fullname,
            'avatar'       => $this->avatar,
            'birthday'     => $this->birthday?->format('Y-m-d'),
            'gender'       => $this->gender,
            'role_id'      => $this->role_id,
            'role'         => $this->whenLoaded('role', fn() => $this->role->name, $this->role?->name),
            'province'     => $this->whenLoaded('province', fn() => $this->province->province_name),
            'phone'        => $this->phone,
            'total_points' => (int) ($this->total_points ?? 0),
            'user_tier'    => $this->userTier()?->tier ?? 'Bronze',
        ];
    }
}

