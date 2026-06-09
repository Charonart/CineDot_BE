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
            'id'          => $this->cinema_id,
            'name'        => $this->cinema_name,
            'address'     => $this->cinema_address,
            'province'    => $this->whenLoaded('province', fn() => $this->province?->province_name),
            'phone'       => $this->phone,
            'email'       => $this->email,
            'description' => $this->whenNotNull($this->description),
            'isActive'    => $this->is_active,
            'rooms'       => $this->whenLoaded('rooms', function () {
                return $this->rooms->map(fn($r) => [
                    'id'         => $r->room_id,
                    'name'       => $r->room_name,
                    'type'       => $r->room_type,
                    'totalSeats' => $r->total_seats,
                    'isActive'   => $r->is_active,
                ])->values();
            }),
        ];
    }
}
