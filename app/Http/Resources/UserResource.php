<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentTier = $this->userTier();
        $nextTier = $this->nextUserTier();
        $points = (int) ($this->total_points ?? 0);
        $pointsNeeded = $nextTier ? max(0, $nextTier->min_points - $points) : 0;

        $userRoles = $this->relationLoaded('userRoles') ? $this->userRoles : $this->userRoles()->with('role')->get();
        $primaryRole = $this->role?->name ?? (is_string($this->role) ? $this->role : 'customer');

        return [
            'id'           => $this->user_id,
            'user_id'      => $this->user_id,
            'username'     => $this->username,
            'email'        => $this->email,
            'fullname'     => $this->fullname,
            'avatar'       => $this->avatar,
            'birthday'     => $this->birthday?->format('Y-m-d'),
            'gender'       => $this->gender,
            'role'         => $primaryRole,
            'roles'        => $userRoles->map(fn($ur) => $ur->role?->name)->filter()->values()->toArray(),
            'province'     => $this->whenLoaded('province', fn() => $this->province->province_name),
            'phone'        => $this->phone,
            'total_points' => $points,
            'user_tier'    => $currentTier?->tier ?? 'Bronze',
            'tier_info'    => [
                'current_tier'         => $currentTier?->tier ?? 'Bronze',
                'current_points'       => $points,
                'discount_percent'     => (float) ($currentTier?->discount_percent ?? 0),
                'next_tier'            => $nextTier?->tier,
                'points_needed'        => $pointsNeeded,
                'next_tier_min_points' => $nextTier?->min_points ?? ($points > 0 ? $points : 500),
            ],
        ];
    }
}

