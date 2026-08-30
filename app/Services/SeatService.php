<?php

namespace App\Services;

use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\SeatType;
use Illuminate\Support\Facades\Redis;

class SeatService
{
    public function getScheduleSeats(int $showtimeId): array
    {
        $showtime = Showtime::with(['movie', 'room.cinema'])->findOrFail($showtimeId);
        $room = $showtime->room;

        // Parse layout_snaps or room seat_matrix
        $layoutMatrix = $showtime->layout_snaps;
        if (is_string($layoutMatrix)) {
            $layoutMatrix = json_decode($layoutMatrix, true);
        }
        if (empty($layoutMatrix) && $room && $room->seat_matrix) {
            $layoutMatrix = is_string($room->seat_matrix) ? json_decode($room->seat_matrix, true) : $room->seat_matrix;
            // Backfill layout_snaps for showtime
            if (!empty($layoutMatrix)) {
                $showtime->update(['layout_snaps' => $layoutMatrix]);
            }
        }

        // Parse room seat_matrix layout map
        $matrixMap = [];
        if (is_array($layoutMatrix)) {
            foreach ($layoutMatrix as $mSeat) {
                if (is_array($mSeat)) {
                    $row = $mSeat['row_name'] ?? $mSeat['row'] ?? '';
                    $num = (string)($mSeat['seat_number'] ?? $mSeat['number'] ?? '');
                    $code = $row . $num;
                    $sid = $mSeat['seat_id'] ?? $mSeat['id'] ?? $code;
                    
                    $canvasData = [
                        'cx'    => isset($mSeat['cx']) ? (int) $mSeat['cx'] : (isset($mSeat['position_x']) ? (int) $mSeat['position_x'] : 0),
                        'cy'    => isset($mSeat['cy']) ? (int) $mSeat['cy'] : (isset($mSeat['position_y']) ? (int) $mSeat['position_y'] : 0),
                        'angle' => isset($mSeat['angle']) ? (int) $mSeat['angle'] : 0,
                    ];

                    if ($sid) {
                        $matrixMap[(string)$sid] = $canvasData;
                    }
                    if ($code) {
                        $matrixMap[(string)$code] = $canvasData;
                    }
                }
            }
        }

        $seats = ShowtimeSeat::with('seatType')
            ->where('showtime_id', $showtimeId)
            ->get();

        if ($seats->isEmpty() && is_array($layoutMatrix)) {
            $newSeats = [];
            // To prevent duplicates if the matrix has both string and object forms, use seat codes as keys
            $addedCodes = [];
            foreach ($layoutMatrix as $mSeat) {
                if (!is_array($mSeat)) continue;
                $row = $mSeat['row_name'] ?? $mSeat['row'] ?? '';
                $num = (string)($mSeat['seat_number'] ?? $mSeat['number'] ?? '');
                $code = $row . $num;
                $sid = $mSeat['seat_id'] ?? $mSeat['id'] ?? $code;
                
                if (empty($sid)) continue;
                
                if (isset($addedCodes[$sid])) continue;
                $addedCodes[$sid] = true;

                // Extract row and number from ID if they are not explicitly set
                if (empty($row) && preg_match('/^([A-Za-z]+)(\d+)$/', $sid, $m)) {
                    $row = $m[1];
                    $num = $m[2];
                }

                if (empty($row) || empty($num)) continue;

                $rawType = (string) ($mSeat['seat_type'] ?? $mSeat['type'] ?? 'standard');
                $seatTypeKey = SeatType::resolveTypeKey($rawType);

                $newSeats[] = [
                    'showtime_id' => $showtimeId,
                    'seat_type'   => $seatTypeKey,
                    'row_name'    => strtoupper($row),
                    'seat_number' => $num,
                    'status'      => $mSeat['status'] ?? 'available',
                ];
            }
            if (!empty($newSeats)) {
                \App\Models\ShowtimeSeat::insert($newSeats);
                $seats = ShowtimeSeat::with('seatType')
                    ->where('showtime_id', $showtimeId)
                    ->get();
            }
        }

        $seats = $seats->sortBy(function ($ss) {
                return $ss->row_name . str_pad($ss->seat_number, 3, '0', STR_PAD_LEFT);
            })
            ->values();

        $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);
        $pendingSeatIds = \App\Models\BookingSeat::whereHas('booking', function ($query) use ($showtimeId, $ttlSeconds) {
            $query->where('showtime_id', $showtimeId)
                  ->where('booking_status', 'pending')
                  ->where('created_at', '>=', \Carbon\Carbon::now()->subSeconds($ttlSeconds));
        })->pluck('showtime_seat_id')->flip()->toArray();

        $basePrice = (float) $showtime->base_price;

        $seatList = [];
        foreach ($seats as $ss) {
            $status = strtoupper($ss->status);
            if ($status === 'AVAILABLE') {
                $isHeldInDb = isset($pendingSeatIds[$ss->showtime_seat_id]);
                $isHeldInRedis = false;
                try {
                    $redisKey = "hold:showtime:{$showtimeId}:seat:{$ss->showtime_seat_id}";
                    $isHeldInRedis = (bool) Redis::exists($redisKey);
                } catch (\Exception $e) {
                    // Redis fallback
                }

                if ($isHeldInDb || $isHeldInRedis) {
                    $status = 'HOLDING';
                }
            }

            $seatCode = $ss->row_name . $ss->seat_number;
            $seatTypeModel = $ss->seatType;
            $surcharge = $seatTypeModel ? (float) $seatTypeModel->surcharge_amount : 0.0;
            $finalPrice = (int) round($basePrice + $surcharge);

            $canvas = $matrixMap[$seatCode] ?? $matrixMap[(string)$ss->showtime_seat_id] ?? ['cx' => 0, 'cy' => 0, 'angle' => 0];

            $seatList[] = [
                'showtime_seat_id' => $ss->showtime_seat_id,
                'seat_code'        => $seatCode,
                'row_name'         => $ss->row_name,
                'seat_number'      => (string) $ss->seat_number,
                'seat_type'        => $ss->seat_type,
                'type_name'        => $seatTypeModel ? $seatTypeModel->type_name : ucfirst($ss->seat_type),
                'color_code'       => $seatTypeModel ? $seatTypeModel->color_code : '#64748B',
                'icon_name'        => $seatTypeModel ? $seatTypeModel->icon_name : 'seat',
                'surcharge'        => (int) round($surcharge),
                'final_price'      => $finalPrice,
                'status'           => $status,
                'canvas'           => $canvas,
            ];
        }

        $allActiveSeatTypes = SeatType::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('surcharge_amount', 'asc')
            ->get();

        return [
            'showtime' => [
                'showtime_id'    => $showtime->showtime_id,
                'movie_title'    => $showtime->movie ? $showtime->movie->title : '',
                'room_name'      => $showtime->room ? $showtime->room->room_name : '',
                'cinema_name'    => ($showtime->room && $showtime->room->cinema) ? $showtime->room->cinema->cinema_name : '',
                'base_price'     => (int) round($basePrice),
                'showtime_start' => \Carbon\Carbon::parse($showtime->showtime_start)->toIso8601String(),
            ],
            'seats'      => $seatList,
            'seat_types' => $allActiveSeatTypes,
        ];
    }

    public function getRealtimeSeatStatus(int $id): array
    {
        $showtimeSeats = ShowtimeSeat::where('showtime_id', $id)->get();
        if ($showtimeSeats->isEmpty()) {
            $this->getScheduleSeats((int) $id);
            $showtimeSeats = ShowtimeSeat::where('showtime_id', $id)->get();
        }
        
        $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);

        $pendingSeatIds = \App\Models\BookingSeat::whereHas('booking', function ($query) use ($id, $ttlSeconds) {
            $query->where('showtime_id', $id)
                  ->where('booking_status', 'pending')
                  ->where('created_at', '>=', \Carbon\Carbon::now()->subSeconds($ttlSeconds));
        })->pluck('showtime_seat_id')->flip()->toArray();

        $seatsData = [];
        
        // Optimize Redis calls by checking multiple keys at once
        $redisKeys = [];
        foreach ($showtimeSeats as $seat) {
            $redisKeys[] = "hold:showtime:{$id}:seat:{$seat->showtime_seat_id}";
        }
        
        $heldInRedisKeys = [];
        try {
            // Using pipeline or mget could be faster, but let's stick to safe fallback if redis is down
            foreach ($redisKeys as $key) {
                if (Redis::exists($key)) {
                    $heldInRedisKeys[$key] = true;
                }
            }
        } catch (\Exception $e) {
            // Redis fallback
        }

        foreach ($showtimeSeats as $seat) {
            $status = strtolower($seat->status);
            
            if ($status === 'available') {
                $isHeldInDb = isset($pendingSeatIds[$seat->showtime_seat_id]);
                $redisKey = "hold:showtime:{$id}:seat:{$seat->showtime_seat_id}";
                $isHeldInRedis = isset($heldInRedisKeys[$redisKey]);

                if ($isHeldInDb || $isHeldInRedis) {
                    $status = 'holding';
                }
            }

            $seatsData[] = [
                'showtime_seat_id' => $seat->showtime_seat_id,
                'showtime_id'      => (int) $seat->showtime_id,
                'row_name'         => $seat->row_name,
                'seat_number'      => $seat->seat_number,
                'seat_code'        => $seat->row_name . $seat->seat_number,
                'seat_type'        => $seat->seat_type,
                'status'           => $status,
            ];
        }

        return $seatsData;
    }
}
