<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CinemaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->cinema_id,
            'cinema_id'      => $this->cinema_id,
            'slug'           => $this->slug,
            'cinema_name'    => $this->cinema_name,
            'name'           => $this->cinema_name,
            'cinema_address' => $this->cinema_address,
            'address'        => $this->cinema_address,
            'province'       => $this->whenLoaded('province', fn() => $this->province?->province_name),
            'phone'          => $this->phone,
            'email'          => $this->email,
            'description'    => $this->whenNotNull($this->description),
            'isActive'       => $this->is_active,
            'rooms'          => $this->whenLoaded('rooms', function () {
                return $this->rooms->map(fn($r) => [
                    'room_id'     => $r->room_id,
                    'room_name'   => $r->room_name,
                    'room_type'   => $r->room_type,
                    'total_seats' => $r->total_seats,
                    'is_active'   => $r->is_active,
                ])->values();
            }),
        ];
    }
}
