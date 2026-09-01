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
     * Kiểm tra xem 2 khoảng thời gian trong cùng phòng có bị chồng lấn (kèm buffer time dọn phòng) không
     */
    public static function hasTimeOverlap(
        Carbon $start1,
        Carbon $end1,
        int $buf1,
        Carbon $start2,
        Carbon $end2,
        int $buf2
    ): bool {
        if ($start1->lessThanOrEqualTo($start2)) {
            $busyUntil1 = (clone $end1)->addMinutes($buf1);
            return $start2->lessThan($busyUntil1);
        } else {
            $busyUntil2 = (clone $end2)->addMinutes($buf2);
            return $start1->lessThan($busyUntil2);
        }
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
                    $diff = abs($start1->diffInMinutes(Carbon::parse($st2['showtime_start'])));

                    if ($diff < $minGapMinutes && $diff >= 0) {
                        $m1 = $st1['movie_title'] ?? 'Phim 1';
                        $m2 = $st2['movie_title'] ?? 'Phim 2';
                        $r1 = $st1['room_name'] ?? "Phòng {$st1['room_id']}";
                        $r2 = $st2['room_name'] ?? "Phòng {$st2['room_id']}";

                        $warnings[] = [
                            'type'    => 'staggering_congestion',
                            'message' => "Giãn cách sảnh: Suất '{$m1}' ({$r1}) và '{$m2}' ({$r2}) bắt đầu cách nhau chỉ {$diff} phút (khuyến nghị >= {$minGapMinutes} phút).",
                        ];
                    }
                }
            }
        }

        return $warnings;
    }

    /**
     * Tự động giải quyết và loại bỏ 100% xung đột thời gian (Auto-Heal & Snap Timeline)
     * - Tự động lùi giờ bắt đầu đúng theo buffer dọn phòng
     * - Luôn bảo vệ 100% các suất đã bán vé ($anchorShowtimes)
     * - Đảm bảo showtime_end = showtime_start + duration thực tế của phim
     */
    public static function autoResolveConflicts(
        array $draftShowtimes,
        array $anchorShowtimes = [],
        int $bufferMinutes = 15,
        string $openingTime = '08:30',
        string $closingTime = '23:30',
        string $targetDate = '',
        array $primeInfo = []
    ): array {
        if (empty($draftShowtimes)) {
            return [];
        }

        $dateStr = !empty($targetDate) ? $targetDate : Carbon::parse($draftShowtimes[0]['showtime_start'])->format('Y-m-d');
        $openCarbon = Carbon::parse("{$dateStr} {$openingTime}:00");
        $closeLimitCarbon = Carbon::parse("{$dateStr} {$closingTime}:00")->addMinutes(90); // Cho phép suất cuối kết thúc muộn hơn đóng cửa 90p

        $primeStartStr = $primeInfo['time_from'] ?? '18:00';
        $primeEndStr = $primeInfo['time_to'] ?? '22:30';

        // Phân nhóm theo Room
        $draftsByRoom = [];
        foreach ($draftShowtimes as $st) {
            $rId = (int) $st['room_id'];
            $draftsByRoom[$rId][] = $st;
        }

        $anchorsByRoom = [];
        foreach ($anchorShowtimes as $anc) {
            // Chỉ coi là Anchor cứng (bất khả xâm phạm) nếu suất đã có khách đặt vé hoặc bị khóa cứng
            if (!empty($anc['is_locked']) || (int) ($anc['booked_seats'] ?? 0) > 0) {
                $rId = (int) $anc['room_id'];
                $anchorsByRoom[$rId][] = $anc;
            }
        }

        $resolvedShowtimes = [];

        foreach ($draftsByRoom as $roomId => $roomDrafts) {
            // Sắp xếp các suất nháp và anchor theo giờ bắt đầu
            usort($roomDrafts, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));
            $roomAnchors = $anchorsByRoom[$roomId] ?? [];
            usort($roomAnchors, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));

            $roomPointer = clone $openCarbon;

            foreach ($roomDrafts as $idx => $st) {
                $duration = max(30, (int) ($st['duration'] ?? 120));
                $proposedStart = Carbon::parse($st['showtime_start']);

                // Giờ bắt đầu không được sớm hơn pointer hiện tại và không sớm hơn giờ mở cửa
                $actualStart = $proposedStart->greaterThan($roomPointer)
                    ? clone $proposedStart
                    : clone $roomPointer;

                if ($actualStart->lessThan($openCarbon)) {
                    $actualStart = clone $openCarbon;
                }

                $actualEnd = (clone $actualStart)->addMinutes($duration);

                // Kiểm tra va chạm với các suất Anchor trong phòng
                $collisionWithAnchor = true;
                $maxAnchorIterations = 10;
                $iterations = 0;

                while ($collisionWithAnchor && $iterations < $maxAnchorIterations) {
                    $iterations++;
                    $collisionWithAnchor = false;

                    foreach ($roomAnchors as $anchor) {
                        $anchorStart = Carbon::parse($anchor['showtime_start']);
                        $anchorEnd = Carbon::parse($anchor['showtime_end']);
                        $anchorBusyUntil = (clone $anchorEnd)->addMinutes((int) ($anchor['buffer_minutes'] ?? $bufferMinutes));

                        $proposedBusyUntil = (clone $actualEnd)->addMinutes($bufferMinutes);

                        // Nếu khoảng [actualStart, proposedBusyUntil] chạm vào [anchorStart, anchorBusyUntil]
                        if ($actualStart->lessThan($anchorBusyUntil) && $proposedBusyUntil->greaterThan($anchorStart)) {
                            $actualStart = clone $anchorBusyUntil;
                            $actualEnd = (clone $actualStart)->addMinutes($duration);
                            $collisionWithAnchor = true;
                            break;
                        }
                    }
                }

                // Nếu sau khi lùi giờ mà suất chiếu bắt đầu quá muộn so với giờ đóng cửa
                if ($actualStart->greaterThan($closeLimitCarbon)) {
                    continue; // Bỏ qua suất bị tràn giờ đóng cửa
                }

                $startStr = $actualStart->format('H:i');
                $isPrime = ($startStr >= $primeStartStr && $startStr <= $primeEndStr);

                $st['showtime_start'] = $actualStart->format('Y-m-d H:i:s');
                $st['showtime_end'] = $actualEnd->format('Y-m-d H:i:s');
                $st['duration'] = $duration;
                $st['buffer_minutes'] = $bufferMinutes;
                $st['is_prime_time'] = $isPrime;
                $st['temp_id'] = "draft_{$roomId}_" . $actualStart->format('Hi') . "_{$idx}";

                $resolvedShowtimes[] = $st;

                // Cập nhật pointer của phòng = Giờ kết thúc + thời gian dọn phòng (buffer)
                $roomPointer = (clone $actualEnd)->addMinutes($bufferMinutes);
            }
        }

        // Sắp xếp lại toàn bộ danh sách kết quả theo giờ bắt đầu
        usort($resolvedShowtimes, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));

        return $resolvedShowtimes;
    }
}

