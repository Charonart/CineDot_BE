<?php

namespace App\Services;

use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Illuminate\Support\Facades\Redis;

class SeatService
{
    public function getScheduleSeats(int $showtimeId): array
    {
        $showtime = Showtime::with(['movie', 'room.cinema'])->findOrFail($showtimeId);
        $room = $showtime->room;

        // Parse room seat_matrix layout map
        $matrixMap = [];
        if ($room && is_array($room->seat_matrix)) {
            foreach ($room->seat_matrix as $mSeat) {
                if (is_array($mSeat)) {
                    $row = $mSeat['row_name'] ?? '';
                    $num = (string)($mSeat['seat_number'] ?? '');
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

        if ($seats->isEmpty() && $room && is_array($room->seat_matrix)) {
            $newSeats = [];
            // To prevent duplicates if the matrix has both string and object forms, use seat codes as keys
            $addedCodes = [];
            foreach ($room->seat_matrix as $mSeat) {
                if (!is_array($mSeat)) continue;
                $row = $mSeat['row_name'] ?? '';
                $num = (string)($mSeat['seat_number'] ?? '');
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

                $seatType = match (strtoupper($mSeat['type'] ?? $mSeat['seat_type'] ?? 'STD')) {
                    'VIP' => 'vip',
                    'COUPLE' => 'couple',
                    default => 'standard',
                };

                $newSeats[] = [
                    'showtime_id' => $showtimeId,
                    'seat_type'   => $seatType,
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
            $surcharge = $ss->seatType ? (float) $ss->seatType->surcharge_amount : 0.0;
            $finalPrice = (int) round($basePrice + $surcharge);

            $canvas = $matrixMap[$seatCode] ?? $matrixMap[(string)$ss->showtime_seat_id] ?? ['cx' => 0, 'cy' => 0, 'angle' => 0];

            $seatList[] = [
                'showtime_seat_id' => $ss->showtime_seat_id,
                'seat_code'        => $seatCode,
                'row_name'         => $ss->row_name,
                'seat_number'      => (string) $ss->seat_number,
                'seat_type'        => $ss->seat_type,
                'surcharge'        => (int) round($surcharge),
                'final_price'      => $finalPrice,
                'status'           => $status,
                'canvas'           => $canvas,
            ];
        }

        return [
            'showtime' => [
                'showtime_id'    => $showtime->showtime_id,
                'movie_title'    => $showtime->movie ? $showtime->movie->title : '',
                'room_name'      => $showtime->room ? $showtime->room->room_name : '',
                'cinema_name'    => ($showtime->room && $showtime->room->cinema) ? $showtime->room->cinema->cinema_name : '',
                'base_price'     => (int) round($basePrice),
                'showtime_start' => \Carbon\Carbon::parse($showtime->showtime_start)->toIso8601String(),
            ],
            'seats' => $seatList,
        ];
    }
}
