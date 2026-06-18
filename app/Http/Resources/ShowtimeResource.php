<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowtimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->schedule_id,
            'showDate'       => $this->whenNotNull($this->schedule_date ? $this->schedule_date->format('Y-m-d') : null),
            'startTime'      => $this->schedule_start,
            'endTime'        => $this->schedule_end,
            'screen'         => $this->whenLoaded('room', fn() => $this->room->room_name),
            'format'         => $this->whenLoaded('room', fn() => $this->room->room_type),
            'price'          => $this->base_price,
            'availableSeats' => $this->available_seats ?? 0,
        ];
    }
}
