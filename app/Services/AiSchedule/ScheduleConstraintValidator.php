<?php

namespace App\Services\AiSchedule;

use Carbon\Carbon;

class ScheduleConstraintValidator
{
    /**
     * Kiểm tra toàn bộ danh sách suất chiếu nháp (Draft Showtimes)
     * Trả về: ['is_valid' => bool, 'conflicts' => array, 'warnings' => array]
     */
    public static function validateDraftBatch(
        array $draftShowtimes,
        array $existingShowtimes = [],
        int $bufferMinutes = 15,
        int $staggeringGapMinutes = 15,
        string $openingTime = '08:30',
        string $closingTime = '23:30'
    ): array {
        $conflicts = [];
        $warnings = [];

        $allShowtimes = array_merge($existingShowtimes, $draftShowtimes);

        // 1. Kiểm tra từng suất với khung giờ mở/đóng cửa
        foreach ($draftShowtimes as $idx => $st) {
            $tempId = $st['temp_id'] ?? "draft_{$idx}";
            $start = Carbon::parse($st['showtime_start']);
            $end = Carbon::parse($st['showtime_end']);
            $movieTitle = $st['movie_title'] ?? 'Phim';
            $roomName = $st['room_name'] ?? "Phòng {$st['room_id']}";

            $dateStr = $start->format('Y-m-d');
            $openLimit = Carbon::parse("{$dateStr} {$openingTime}:00");
            $closeLimit = Carbon::parse("{$dateStr} {$closingTime}:00");

            if ($start->lessThan($openLimit)) {
                $warnings[] = [
                    'type'        => 'early_opening',
                    'temp_id'     => $tempId,
                    'message'     => "Suất chiếu '{$movieTitle}' tại {$roomName} bắt đầu lúc {$start->format('H:i')}, sớm hơn giờ mở cửa ({$openingTime}).",
                ];
            }

            if ($start->greaterThan($closeLimit)) {
                $warnings[] = [
                    'type'        => 'late_closing',
                    'temp_id'     => $tempId,
                    'message'     => "Suất chiếu '{$movieTitle}' tại {$roomName} bắt đầu lúc {$start->format('H:i')}, muộn hơn giờ đóng cửa ({$closingTime}).",
                ];
            }
        }

        // 2. Kiểm tra xung đột cùng phòng (Collision Detection)
        for ($i = 0; $i < count($draftShowtimes); $i++) {
            $st1 = $draftShowtimes[$i];
            $roomId1 = (int) $st1['room_id'];
            $start1 = Carbon::parse($st1['showtime_start']);
            $end1 = Carbon::parse($st1['showtime_end']);
            $buf1 = (int) ($st1['buffer_minutes'] ?? $bufferMinutes);
            $tempId1 = $st1['temp_id'] ?? "draft_{$i}";

            // Check against existing showtimes
            foreach ($existingShowtimes as $ex) {
                if ((int) $ex['room_id'] === $roomId1) {
                    $exStart = Carbon::parse($ex['showtime_start']);
                    $exEnd = Carbon::parse($ex['showtime_end']);
                    $exBuf = (int) ($ex['buffer_minutes'] ?? $bufferMinutes);

                    if (self::hasTimeOverlap($start1, $end1, $buf1, $exStart, $exEnd, $exBuf)) {
                        $conflicts[] = [
                            'temp_id'        => $tempId1,
                            'room_id'        => $roomId1,
                            'movie_title'    => $st1['movie_title'] ?? 'Phim',
                            'conflict_with'  => $ex['movie_title'] ?? 'Suất chiếu đã có',
                            'conflict_start' => $ex['showtime_start'],
                            'conflict_end'   => $ex['showtime_end'],
                            'message'        => "Xung đột phòng {$roomId1}: Trùng với suất chiếu '{$ex['movie_title']}' ({$exStart->format('H:i')} - {$exEnd->format('H:i')}).",
                        ];
                    }
                }
            }

            // Check against other draft showtimes
            for ($j = $i + 1; $j < count($draftShowtimes); $j++) {
                $st2 = $draftShowtimes[$j];
                $roomId2 = (int) $st2['room_id'];
                if ($roomId1 === $roomId2) {
                    $start2 = Carbon::parse($st2['showtime_start']);
                    $end2 = Carbon::parse($st2['showtime_end']);
                    $buf2 = (int) ($st2['buffer_minutes'] ?? $bufferMinutes);
                    $tempId2 = $st2['temp_id'] ?? "draft_{$j}";

                    if (self::hasTimeOverlap($start1, $end1, $buf1, $start2, $end2, $buf2)) {
                        $conflicts[] = [
                            'temp_id'        => $tempId1,
                            'room_id'        => $roomId1,
                            'movie_title'    => $st1['movie_title'] ?? 'Phim 1',
                            'conflict_with'  => $st2['movie_title'] ?? 'Phim 2',
                            'conflict_start' => $st2['showtime_start'],
                            'conflict_end'   => $st2['showtime_end'],
                            'message'        => "Xung đột phòng {$roomId1}: Suất '{$st1['movie_title']}' ({$start1->format('H:i')}) trùng giờ với suất '{$st2['movie_title']}' ({$start2->format('H:i')}).",
                        ];
                    }
                }
            }
        }

        // 3. Kiểm tra Giãn cách sảnh (Hall Staggering Gap)
        $staggeringWarnings = self::checkStaggeringGaps($allShowtimes, $staggeringGapMinutes);
        $warnings = array_merge($warnings, $staggeringWarnings);

        return [
            'is_valid'   => empty($conflicts),
            'conflicts'  => $conflicts,
            'warnings'   => $warnings,
        ];
    }

    /**
     * Kiểm tra xem 2 khoảng thời gian có bị chồng lấn (kèm buffer time) không
     */
    public static function hasTimeOverlap(
        Carbon $start1,
        Carbon $end1,
        int $buf1,
        Carbon $start2,
        Carbon $end2,
        int $buf2
    ): bool {
        $effStart1 = (clone $start1)->subMinutes($buf1);
        $effEnd1 = (clone $end1)->addMinutes($buf1);

        return ($start2->lessThan($effEnd1) && $end2->greaterThan($effStart1));
    }

    /**
     * Cảnh báo giãn cách sảnh nếu 2 phòng khác nhau bắt đầu quá sát nhau (dưới $gapMinutes)
     */
    public static function checkStaggeringGaps(array $showtimes, int $minGapMinutes = 15): array
    {
        $warnings = [];
        $count = count($showtimes);

        for ($i = 0; $i < $count - 1; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $st1 = $showtimes[$i];
                $st2 = $showtimes[$j];

                if ((int) $st1['room_id'] !== (int) $st2['room_id']) {
                    $start1 = Carbon::parse($st1['showtime_start']);
                    $start2 = Carbon::parse($st2['showtime_end'] ?? $st2['showtime_start']);
                    $diff = abs($start1->diffInMinutes(Carbon::parse($st2['showtime_start'])));

                    if ($diff < $minGapMinutes && $diff >= 0) {
                        $m1 = $st1['movie_title'] ?? 'Phim 1';
                        $m2 = $st2['movie_title'] ?? 'Phim 2';
                        $r1 = $st1['room_name'] ?? "Phòng {$st1['room_id']}";
                        $r2 = $st2['room_name'] ?? "Phòng {$st2['room_id']}";

                        $warnings[] = [
                            'type'    => 'staggering_congestion',
                            'message' => "Giãn cách sảnh: Suất '{$m1}' ({$r1}) và '{$m2}' ({$r2}) bắt đầu cách nhau chỉ {$diff} phút (khuyến nghị $\ge$ {$minGapMinutes} phút).",
                        ];
                    }
                }
            }
        }

        return $warnings;
    }
}
