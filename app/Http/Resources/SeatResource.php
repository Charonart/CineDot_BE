<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'scheduleSeatId' => $this->schedule_seat_id,
            'seatId'         => $this->seat_id,
            'row'            => $this->whenLoaded('seat', fn() => $this->seat->seat_row),
            'number'         => $this->whenLoaded('seat', fn() => $this->seat->seat_number),
            'type'           => $this->whenLoaded('seat', fn() => $this->seat->seat_type),
            'positionX'      => $this->whenLoaded('seat', fn() => $this->seat->position_x),
            'positionY'      => $this->whenLoaded('seat', fn() => $this->seat->position_y),
            'status'         => $this->status, // available, booked, held, blocked
            'price'          => $this->price,
        ];
    }
}
