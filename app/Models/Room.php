<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FilterableAndSortable;
use App\Services\RoomFormatCatalog;

class Room extends Model
{
    use HasFactory, FilterableAndSortable;

    protected $primaryKey = 'room_id';
    public $timestamps = true;

    protected $fillable = [
        'cinema_id',
        'room_name',
        'room_type',
        'screen_type',
        'sound_technology',
        'screen_config',
        'features',
        'surcharge_amount',
        'total_seats',
        'is_active',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'surcharge_amount' => 'decimal:2',
        'total_seats'      => 'integer',
        'screen_config'    => 'array',
        'features'         => 'array',
    ];

    protected $appends = [
        'seat_matrix',
    ];

    public function cinema()
    {
        return $this->belongsTo(Cinema::class, 'cinema_id', 'cinema_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'room_id', 'room_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class, 'room_id', 'room_id');
    }

    /**
     * Get effective canvas screen config (falls back to catalog defaults)
     */
    public function getEffectiveScreenConfigAttribute(): array
    {
        if (!empty($this->screen_config) && is_array($this->screen_config)) {
            return $this->screen_config;
        }

        return RoomFormatCatalog::getDefaultScreenConfig($this->screen_type ?? 'standard_2d');
    }

    /**
     * Scopes for filtering
     */
    public function scopeScreenType($query, $screenType)
    {
        if (!empty($screenType)) {
            return $query->where('screen_type', $screenType);
        }
        return $query;
    }

    public function scopeSoundTech($query, $soundTech)
    {
        if (!empty($soundTech)) {
            return $query->where('sound_technology', $soundTech);
        }
        return $query;
    }

    public function scopeCinema($query, $cinemaId)
    {
        if (!empty($cinemaId)) {
            return $query->where('cinema_id', $cinemaId);
        }
        return $query;
    }

    public function getSeatMatrixAttribute(): array
    {
        $seats = $this->relationLoaded('seats') ? $this->seats : $this->seats()->whereNull('deleted_at')->get();
        $matrix = [];
        foreach ($seats as $s) {
            $matrix[] = [
                'seat_id'     => (string) $s->seat_code,
                'row_name'    => (string) $s->row_name,
                'seat_number' => (int) $s->seat_number,
                'type'        => strtoupper($s->seat_type),
                'cx'          => (int) $s->coord_x,
                'cy'          => (int) $s->coord_y,
                'angle'       => (int) $s->angle,
                'is_active'   => (bool) $s->is_active,
            ];
        }
        return $matrix;
    }

    /**
     * Parse and format static seat layout and canvas screen according to Postman collection spec.
     */
    public function getFormattedLayoutAttribute(): array
    {
        $seats = $this->relationLoaded('seats') ? $this->seats : $this->seats()->whereNull('deleted_at')->get();
        $formattedSeats = [];

        foreach ($seats as $seat) {
            $formattedSeats[] = [
                'seat_id' => (string) $seat->seat_code,
                'type'    => strtoupper($seat->seat_type),
                'cx'      => (int) $seat->coord_x,
                'cy'      => (int) $seat->coord_y,
                'angle'   => (int) $seat->angle,
            ];
        }

        return [
            'room_id'          => $this->room_id,
            'room_name'        => $this->room_name,
            'room_type'        => $this->room_type,
            'screen_type'      => $this->screen_type,
            'sound_technology' => $this->sound_technology,
            'screen'           => $this->effective_screen_config,
            'total_seats'      => $this->total_seats ?: count($formattedSeats),
            'seats'            => $formattedSeats,
        ];
    }
}
