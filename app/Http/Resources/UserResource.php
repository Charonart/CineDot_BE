<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->user_id,
            'username' => $this->username,
            'email'    => $this->email,
            'fullname' => $this->fullname,
            'avatar'   => $this->avatar,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'gender'   => $this->gender,
            'province' => $this->whenLoaded('province', fn() => $this->province->province_name),
            'phone'    => $this->phone,
            'point'    => $this->point,
        ];
    }
}
