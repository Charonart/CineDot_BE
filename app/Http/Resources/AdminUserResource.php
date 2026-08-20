<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tier = $this->userTier();

        $userRoles = $this->relationLoaded('userRoles') ? $this->userRoles : $this->userRoles()->with(['role', 'cinema', 'province'])->get();
        $primaryRole = $this->role?->name ?? (is_string($this->role) ? $this->role : 'customer');

        return [
            'id'               => $this->user_id,
            'user_id'          => $this->user_id,
            'username'         => $this->username,
            'email'            => $this->email,
            'fullname'         => $this->fullname,
            'avatar'           => $this->avatar,
            'role'             => $primaryRole,
            'roles'            => $userRoles->map(fn($ur) => $ur->role?->name)->filter()->values()->toArray(),
            'user_roles'       => $userRoles->map(function ($ur) {
                $scopeName = 'Toàn hệ thống';
                if ($ur->scope_type === 'cinema' && $ur->cinema) {
                    $scopeName = $ur->cinema->cinema_name;
                } elseif ($ur->scope_type === 'region' && $ur->province) {
                    $scopeName = $ur->province->province_name;
                }
                return [
                    'id'         => $ur->id,
                    'role_id'    => $ur->role_id,
                    'role_name'  => $ur->role?->name,
                    'scope_type' => $ur->scope_type,
                    'scope_id'   => $ur->scope_id,
                    'scope_name' => $scopeName,
                ];
            }),
            'phone'            => $this->phone,
            'gender'           => $this->gender,
            'birthday'         => $this->birthday?->format('Y-m-d'),
            'point'            => (int) ($this->total_points ?? 0),
            'total_points'     => (int) ($this->total_points ?? 0),
            'is_active'        => (bool) ($this->is_active ?? true),
            'user_tier'        => $tier?->tier ?? 'Bronze',
            'discount_percent' => (float) ($tier?->discount_percent ?? 0),
            'province'         => $this->whenLoaded('province', fn() => $this->province?->province_name),
            'province_id'      => $this->province_id,
            'email_verified'   => !is_null($this->email_verified_at),
            'last_login'       => $this->last_login?->toISOString(),
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
