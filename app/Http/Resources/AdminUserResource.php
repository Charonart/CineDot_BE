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
            'username'       => $this->username,
            'email'          => $this->email,
            'fullname'       => $this->fullname,
            'avatar'         => $this->avatar,
            'role'           => $this->role,
            'phone'          => $this->phone,
            'gender'         => $this->gender,
            'point'          => $this->point,
            'province'       => $this->whenLoaded('province', fn() => $this->province?->province_name),
            'email_verified' => !is_null($this->email_verified_at),
            'last_login'     => $this->last_login?->toISOString(),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
