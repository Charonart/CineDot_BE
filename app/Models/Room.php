<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\FilterableAndSortable;

class Room extends Model
{
    use HasFactory, FilterableAndSortable;

    protected $primaryKey = 'room_id';
    public $timestamps = false;

    protected $fillable = [
        'cinema_id',
        'room_name',
        'room_type',
        'seat_matrix',
        'total_seats',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'total_seats' => 'integer',
        'seat_matrix' => 'array',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'room_id', 'room_id');
    }

    /**
     * Parse and format static seat layout according to Postman collection spec.
     */
    public function getFormattedLayoutAttribute(): array
    {
        $matrix = $this->seat_matrix ?? [];
        $formattedSeats = [];

        if (is_array($matrix)) {
            foreach ($matrix as $seat) {
                if (is_array($seat)) {
                    $seatId = $seat['seat_id'] ?? (($seat['row_name'] ?? '') . ($seat['seat_number'] ?? ''));
                    $formattedSeats[] = [
                        'seat_id' => (string) $seatId,
                        'type'    => $seat['type'] ?? $seat['seat_type'] ?? 'STANDARD',
                        'cx'      => isset($seat['cx']) ? (int) $seat['cx'] : (isset($seat['position_x']) ? (int) $seat['position_x'] : 0),
                        'cy'      => isset($seat['cy']) ? (int) $seat['cy'] : (isset($seat['position_y']) ? (int) $seat['position_y'] : 0),
                        'angle'   => isset($seat['angle']) ? (int) $seat['angle'] : 0,
                    ];
                }
            }
        }

        return [
            'room_id'     => $this->room_id,
            'room_name'   => $this->room_name,
            'total_seats' => $this->total_seats ?? count($formattedSeats),
            'seats'       => $formattedSeats,
        ];
    }
}

