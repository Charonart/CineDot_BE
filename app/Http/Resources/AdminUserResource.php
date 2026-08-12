<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->user_id,
            'user_id'        => $this->user_id,
            'username'       => $this->username,
            'email'          => $this->email,
            'fullname'       => $this->fullname,
            'avatar'         => $this->avatar,
            'role'           => $this->role?->name ?? (is_string($this->role) ? $this->role : 'customer'),
            'role_id'        => $this->role_id,
            'phone'          => $this->phone,
            'gender'         => $this->gender,
            'point'          => (int) ($this->total_points ?? 0),
            'total_points'   => (int) ($this->total_points ?? 0),
            'user_tier'      => $this->userTier()?->tier ?? 'Bronze',
            'province'       => $this->whenLoaded('province', fn() => $this->province?->province_name),
            'email_verified' => !is_null($this->email_verified_at),
            'last_login'     => $this->last_login?->toISOString(),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
