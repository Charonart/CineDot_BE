<?php

namespace App\Services;

use App\Models\Seat;
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

        $seats = ShowtimeSeat::with(['seat.seatType'])
            ->where('showtime_id', $showtimeId)
            ->get();

        // Auto-generate showtime_seats from Room's active physical seats if empty
        if ($seats->isEmpty() && $room) {
            $physicalSeats = Seat::where('room_id', $room->room_id)
                ->where('is_active', true)
                ->get();

            if ($physicalSeats->isNotEmpty()) {
                $newSeats = $physicalSeats->map(function ($seat) use ($showtimeId) {
                    return [
                        'showtime_id' => $showtimeId,
                        'seat_id'     => $seat->seat_id,
                        'status'      => 'available',
                    ];
                })->toArray();

                ShowtimeSeat::insert($newSeats);
                $seats = ShowtimeSeat::with(['seat.seatType'])
                    ->where('showtime_id', $showtimeId)
                    ->get();
            }
        }

        $seats = $seats->sortBy(function ($ss) {
                $row = $ss->seat?->row_name ?? '';
                $num = $ss->seat?->seat_number ?? '';
                return $row . str_pad($num, 3, '0', STR_PAD_LEFT);
            })
            ->values();

        $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);
        $pendingSeatIds = \App\Models\BookingSeat::whereHas('booking', function ($query) use ($showtimeId, $ttlSeconds) {
            $query->where('showtime_id', $showtimeId)
                  ->where('booking_status', 'pending')
                  ->where('created_at', '>=', \Carbon\Carbon::now()->subSeconds($ttlSeconds));
        })->pluck('showtime_seat_id')->flip()->toArray();

        // Single batch Redis pipeline lookup for all seats
        $heldInRedisMap = [];
        try {
            $pipelineResults = Redis::pipeline(function ($pipe) use ($showtimeId, $seats) {
                foreach ($seats as $ss) {
                    $pipe->exists("hold:showtime:{$showtimeId}:seat:{$ss->showtime_seat_id}");
                }
            });

            foreach ($seats as $idx => $ss) {
                if (!empty($pipelineResults[$idx])) {
                    $heldInRedisMap[$ss->showtime_seat_id] = true;
                }
            }
        } catch (\Throwable $e) {
            // Redis fallback
        }

        $basePrice = (float) $showtime->base_price;

        $seatList = [];
        foreach ($seats as $ss) {
            $status = strtoupper($ss->status);
            if ($status === 'AVAILABLE') {
                $isHeldInDb = isset($pendingSeatIds[$ss->showtime_seat_id]);
                $isHeldInRedis = isset($heldInRedisMap[$ss->showtime_seat_id]);

                if ($isHeldInDb || $isHeldInRedis) {
                    $status = 'HOLDING';
                }
            }

            $physicalSeat = $ss->seat;
            $rowName = $physicalSeat ? $physicalSeat->row_name : '';
            $seatNum = $physicalSeat ? (string) $physicalSeat->seat_number : '';
            $seatCode = $rowName . $seatNum;
            $seatType = $physicalSeat ? $physicalSeat->seat_type : 'standard';
            $seatTypeModel = $physicalSeat ? $physicalSeat->seatType : null;
            $surcharge = $seatTypeModel ? (float) $seatTypeModel->surcharge_amount : 0.0;
            $finalPrice = (int) round($basePrice + $surcharge);

            $canvas = [
                'cx'    => $physicalSeat ? (int) $physicalSeat->coord_x : 0,
                'cy'    => $physicalSeat ? (int) $physicalSeat->coord_y : 0,
                'angle' => $physicalSeat ? (int) $physicalSeat->angle : 0,
            ];

            $seatList[] = [
                'showtime_seat_id' => $ss->showtime_seat_id,
                'seat_code'        => $seatCode,
                'row_name'         => $rowName,
                'seat_number'      => $seatNum,
                'seat_type'        => $seatType,
                'type_name'        => $seatTypeModel ? $seatTypeModel->type_name : ucfirst($seatType),
                'color_code'       => $seatTypeModel ? $seatTypeModel->color_code : '#64748B',
                'icon_name'        => $seatTypeModel ? $seatTypeModel->icon_name : 'seat',
                'surcharge'        => (int) round($surcharge),
                'final_price'      => $finalPrice,
                'status'           => $status,
                'canvas'           => $canvas,
            ];
        }

        $allActiveSeatTypes = \Illuminate\Support\Facades\Cache::remember('seat_types:active_list', 3600, function () {
            return SeatType::where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->orderBy('surcharge_amount', 'asc')
                ->get();
        });

        $screenConfig = $room ? $room->effective_screen_config : null;

        return [
            'showtime' => [
                'showtime_id'      => $showtime->showtime_id,
                'movie_title'      => $showtime->movie ? $showtime->movie->title : '',
                'room_name'        => $room ? $room->room_name : '',
                'room_type'        => $room ? $room->room_type : '',
                'screen_type'      => $room ? $room->screen_type : 'standard_2d',
                'sound_technology' => $room ? $room->sound_technology : 'surround_71',
                'screen_config'    => $screenConfig,
                'features'         => $room ? ($room->features ?? []) : [],
                'cinema_name'      => ($room && $room->cinema) ? $room->cinema->cinema_name : '',
                'base_price'       => (int) round($basePrice),
                'showtime_start'   => \Carbon\Carbon::parse($showtime->showtime_start)->toIso8601String(),
            ],
            'screen'     => $screenConfig,
            'seats'      => $seatList,
            'seat_types' => $allActiveSeatTypes,
        ];
    }
}
