<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;

class SeatLayoutService
{
    /**
     * Khoảng cách giữa các ghế (đơn vị pixel/unit cho front-end)
     */
    private const SEAT_SPACING_X = 20;
    private const SEAT_SPACING_Y = 20;
    private const OFFSET_X = 10;
    private const OFFSET_Y = 20;

    /**
     * Phụ thu mặc định theo loại ghế (VNĐ)
     */
    private const SURCHARGES = [
        'standard' => 0,
        'vip'      => 30000,
        'couple'   => 50000,
    ];

    /**
     * Cấu hình ma trận ghế tĩnh cho từng loại phòng.
     *
     * - standard : 8 hàng (A→H) × 10 ghế = 80 ghế
     * - vip      : 10 hàng (A→J) × 12 ghế = 120 ghế
     * - couple   : 6 hàng (A→F) × 8 ghế  = 48 ghế
     */
    private const LAYOUTS = [
        'standard' => [
            'rows' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
            'cols' => 10,
        ],
        'vip' => [
            'rows' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
            'cols' => 12,
        ],
        'couple' => [
            'rows' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'cols' => 8,
        ],
    ];

    /**
     * Sinh toàn bộ sơ đồ ghế cho phòng theo room_type.
     *
     * @param Room   $room     Phòng vừa tạo
     * @param string $roomType Loại phòng: standard | vip | couple
     */
    public function generateLayout(Room $room, string $roomType): void
    {
        $layout = self::LAYOUTS[$roomType] ?? self::LAYOUTS['standard'];
        $rows   = $layout['rows'];
        $cols   = $layout['cols'];

        $seats = [];

        foreach ($rows as $rowIndex => $rowLabel) {
            $seatType = $this->determineSeatType($roomType, $rowLabel, $rows);

            for ($col = 1; $col <= $cols; $col++) {
                $seats[] = [
                    'room_id'     => $room->room_id,
                    'seat_row'    => $rowLabel,
                    'seat_number' => $col,
                    'seat_type'   => $seatType,
                    'position_x'  => ($col - 1) * self::SEAT_SPACING_X + self::OFFSET_X,
                    'position_y'  => $rowIndex * self::SEAT_SPACING_Y + self::OFFSET_Y,
                    'angle'       => 0,
                    'surcharge'   => self::SURCHARGES[$seatType],
                    'is_active'   => true,
                ];
            }
        }

        // Bulk insert tất cả ghế 1 lần
        Seat::insert($seats);

        // Cập nhật total_seats trên room
        $room->update(['total_seats' => count($seats)]);
    }

    /**
     * Xác định loại ghế dựa trên room_type, hàng hiện tại, và danh sách hàng.
     *
     * - Standard (8×10 = 80):  Tất cả ghế là 'standard'
     * - VIP (10×12 = 120):     Hàng D, E, F ở giữa là 'vip', còn lại 'standard'
     * - Couple (6×8 = 48):     Hàng cuối (F) là 'couple', còn lại 'standard'
     */
    private function determineSeatType(string $roomType, string $rowLabel, array $rows): string
    {
        return match ($roomType) {
            'vip'    => in_array($rowLabel, ['D', 'E', 'F']) ? 'vip' : 'standard',
            'couple' => $rowLabel === end($rows) ? 'couple' : 'standard',
            default  => 'standard',
        };
    }
}

