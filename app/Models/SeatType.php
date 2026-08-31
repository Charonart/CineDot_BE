<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\FilterableAndSortable;

class SeatType extends Model
{
    use FilterableAndSortable;

    protected $table = 'seat_types';

    /**
     * Primary key is a string (e.g. 'standard', 'vip', 'couple', 'sweetbox', 'deluxe', 'bed')
     */
    protected $primaryKey = 'seat_type';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'seat_type',
        'type_name',
        'surcharge_amount',
        'color_code',
        'icon_name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'surcharge_amount' => 'decimal:2',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
    ];

    public function showtimeSeats()
    {
        return $this->hasMany(ShowtimeSeat::class, 'seat_type', 'seat_type');
    }

    /**
     * Resolve raw input or alias into a valid registered seat_type key in database
     */
    public static function resolveTypeKey(?string $rawInput): string
    {
        if (empty($rawInput)) {
            return 'standard';
        }

        $cleaned = strtolower(trim($rawInput));

        // Alias mapping
        $aliasMap = [
            'std'         => 'standard',
            'regular'     => 'standard',
            'thuong'      => 'standard',
            'double'      => 'couple',
            'doi'         => 'couple',
            'sweet_box'   => 'sweetbox',
            'sweet-box'   => 'sweetbox',
            'recline'     => 'deluxe',
            'giuong'      => 'bed',
            'giuong_nam'  => 'bed',
        ];

        if (isset($aliasMap[$cleaned])) {
            $cleaned = $aliasMap[$cleaned];
        }

        // Check if exists in cached/queried seat types
        static $cachedKeys = null;
        if ($cachedKeys === null) {
            try {
                $cachedKeys = \Illuminate\Support\Facades\Cache::remember('seat_types:keys', 3600, function () {
                    return static::pluck('seat_type')->map(fn($k) => strtolower($k))->flip()->toArray();
                });
            } catch (\Throwable $e) {
                $cachedKeys = [];
            }
        }

        if (isset($cachedKeys[$cleaned])) {
            return $cleaned;
        }

        // Fallback for vip / couple
        if (str_contains($cleaned, 'vip')) return 'vip';
        if (str_contains($cleaned, 'couple') || str_contains($cleaned, 'sweet')) return 'couple';
        if (str_contains($cleaned, 'bed')) return 'bed';
        if (str_contains($cleaned, 'deluxe')) return 'deluxe';

        return 'standard';
    }
}
