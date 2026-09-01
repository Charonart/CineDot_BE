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
            'id'               => $this->showtime_id,
            'showDate'         => $this->showtime_start ? $this->showtime_start->format('Y-m-d') : null,
            'startTime'        => $this->showtime_start ? $this->showtime_start->format('H:i') : null,
            'endTime'          => $this->showtime_end ? $this->showtime_end->format('H:i') : null,
            'room_id'          => $this->room_id,
            'screen'           => $this->whenLoaded('room', fn() => $this->room->room_name),
            'format'           => $this->whenLoaded('room', fn() => $this->room->room_type),
            'screen_type'      => $this->whenLoaded('room', fn() => $this->room->screen_type),
            'sound_technology' => $this->whenLoaded('room', fn() => $this->room->sound_technology),
            'screen_config'    => $this->whenLoaded('room', fn() => $this->room->effective_screen_config),
            'features'         => $this->whenLoaded('room', fn() => $this->room->features),
            'price'            => $this->base_price,
            'availableSeats'   => $this->available_seats ?? 0,
        ];
    }
}
