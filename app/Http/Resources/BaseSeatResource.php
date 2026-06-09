<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseSeatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'seatId'    => $this->seat_id,
            'row'       => $this->seat_row,
            'number'    => $this->seat_number,
            'type'      => $this->seat_type,
            'positionX' => $this->position_x,
            'positionY' => $this->position_y,
            'isActive'  => $this->is_active,
        ];
    }
}
