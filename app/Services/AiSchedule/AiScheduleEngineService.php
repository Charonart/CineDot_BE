<?php

namespace App\Services\AiSchedule;

use App\Models\AiScheduleConfig;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\PricingRule;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\AiSchedule\AiProviderFactory;
use App\Services\AiSchedule\ScheduleConstraintValidator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiScheduleEngineService
{
    /**
     * Tính toán giá vé cơ sở của phòng dựa theo Cinema base price + Room surcharge amount
     */
    public function calculateRoomBasePrice(Room $room, float $cinemaDefaultBasePrice): float
    {
        $roomSurcharge = (float) ($room->surcharge_amount ?? 0);

        if ($roomSurcharge <= 0) {
            $catalogSurcharges = [
                'imax_laser'   => 60000.0,
                'screenx'      => 40000.0,
                'dolby_cinema' => 50000.0,
                'onyx_led'     => 50000.0,
                'standard_3d'  => 30000.0,
                'standard_2d'  => 0.0,
            ];
            $roomSurcharge = $catalogSurcharges[$room->screen_type] ?? 0.0;
        }

        return $cinemaDefaultBasePrice + $roomSurcharge;
    }

    /**
     * Lấy thông tin Khung Giờ Vàng hiệu lực (tự động đồng bộ từ PricingRule)
     */
    public function getEffectivePrimeTime(AiScheduleConfig $config, ?string $targetDate = null): array
    {
        $date = $targetDate ? Carbon::parse($targetDate) : Carbon::today();
        $dayOfWeek = $date->format('l');

        if ($config->sync_prime_time_from_pricing_rules) {
            $primeRule = PricingRule::where('is_active', true)
                ->where('rule_category', 'prime_time_surcharge')
                ->first();

            if ($primeRule && !empty($primeRule->conditions)) {
                $conds = is_string($primeRule->conditions) ? json_decode($primeRule->conditions, true) : $primeRule->conditions;
                $timeFrom = $conds['time_from'] ?? '18:00';
                $timeTo = $conds['time_to'] ?? '23:00';
                $days = $conds['days'] ?? ['Friday', 'Saturday', 'Sunday'];

                $isApplicable = in_array($dayOfWeek, $days) || in_array('all', $days);

                return [
                    'source'           => 'pricing_rule',
                    'rule_id'          => $primeRule->pricing_rule_id,
                    'rule_name'        => $primeRule->name,
                    'time_from'        => $timeFrom,
                    'time_to'          => $timeTo,
                    'applicable_days'  => $days,
                    'is_today_prime'   => $isApplicable,
                    'modifier_type'    => $primeRule->modifier_type,
                    'modifier_value'   => (float) $primeRule->modifier_value,
                    'display_text'     => "{$primeRule->name} ({$timeFrom} - {$timeTo})" . ($isApplicable ? " [Áp dụng hôm nay]" : " [Không áp dụng ngày {$dayOfWeek}]"),
                ];
            }
        }

        $timeFrom = $config->custom_prime_time_start ?: '18:00';
        $timeTo = $config->custom_prime_time_end ?: '22:30';

        return [
            'source'           => 'custom_config',
            'rule_id'          => null,
            'rule_name'        => 'Khung giờ vàng tùy chỉnh',
            'time_from'        => $timeFrom,
            'time_to'          => $timeTo,
            'applicable_days'  => ['Everyday'],
            'is_today_prime'   => true,
            'modifier_type'    => 'fixed_amount',
            'modifier_value'   => 0.0,
            'display_text'     => "Khung giờ vàng tùy chỉnh ({$timeFrom} - {$timeTo})",
        ];
    }

    /**
     * Sinh / Tinh chỉnh bản nháp lịch chiếu (100% Pure LLM-driven theo ý định người dùng)
     */
    public function generateDraft(
        int $cinemaId,
        string $targetDate,
        string $mode = 'prompt',
        ?string $strategyId = null,
        ?string $userPrompt = null,
        array $selectedMovieIds = [],
        array $selectedRoomIds = [],
        string $scheduleMode = 'smart_fill', // 'smart_fill' | 'optimize' | 'replace_all'
        array $overrideConfig = [],
        array $currentDraftShowtimes = [],
        array $chatHistory = [],
        ?array $timeRange = null
    ): array {
        $config = AiScheduleConfig::getEffectiveConfig($cinemaId);
        $cinema = Cinema::with('rooms')->findOrFail($cinemaId);

        $bufferMinutes = (int) ($overrideConfig['buffer_minutes'] ?? $config->default_buffer_minutes ?? 15);
        $staggeringGap = (int) ($overrideConfig['staggering_gap_minutes'] ?? $config->staggering_gap_minutes ?? 15);
        $openingTime = $overrideConfig['opening_time'] ?? $config->opening_time ?? '08:30';
        $closingTime = $overrideConfig['closing_time'] ?? $config->closing_time ?? '23:30';
        $defaultBasePrice = (float) ($overrideConfig['default_base_price'] ?? $config->default_base_price ?? 100000.0);

        if (!empty($timeRange['start'])) {
            $openingTime = $timeRange['start'];
        }
        if (!empty($timeRange['end'])) {
            $closingTime = $timeRange['end'];
        }

        $primeInfo = $this->getEffectivePrimeTime($config, $targetDate);

        // 1. Lấy danh sách phòng theo Scope
        $roomsQuery = Room::where('cinema_id', $cinemaId)->where('is_active', true);
        if (!empty($selectedRoomIds)) {
            $roomsQuery->whereIn('room_id', $selectedRoomIds);
        }
        $rooms = $roomsQuery->orderBy('total_seats', 'desc')->get();
        if ($rooms->isEmpty()) {
            throw new \Exception("Không tìm thấy phòng chiếu khả dụng theo bộ lọc tại rạp {$cinema->cinema_name}.");
        }

        // 2. Lấy danh sách phim kèm Rich Metadata (thể loại, thời lượng, độ tuổi, doanh số)
        $moviesQuery = Movie::query();
        if (!empty($selectedMovieIds)) {
            $moviesQuery->whereIn('movie_id', $selectedMovieIds);
        } else {
            $moviesQuery->where(function ($q) {
                $q->where('status', 'NOW_SHOWING')
                  ->orWhere('status', 'now_showing')
                  ->orWhere('status', 'UPCOMING')
                  ->orWhere('status', 'upcoming');
            });
        }
        $movies = $moviesQuery->with(['genres'])->orderBy('popularity', 'desc')->get();
        if ($movies->isEmpty()) {
            $movies = Movie::with('genres')->orderBy('popularity', 'desc')->limit(12)->get();
        }

        // Truy vấn số vé đã bán trong 7 ngày qua của từng phim để làm context
        $sevenDaysAgo = Carbon::parse($targetDate)->subDays(7);
        $salesStats = ShowtimeSeat::where('status', 'booked')
            ->whereHas('showtime', function ($q) use ($sevenDaysAgo, $targetDate) {
                $q->whereBetween('showtime_start', [$sevenDaysAgo->startOfDay(), Carbon::parse($targetDate)->endOfDay()]);
            })
            ->join('showtimes', 'showtime_seats.showtime_id', '=', 'showtimes.showtime_id')
            ->select('showtimes.movie_id', DB::raw('count(*) as total_tickets_sold'))
            ->groupBy('showtimes.movie_id')
            ->pluck('total_tickets_sold', 'showtimes.movie_id')
            ->toArray();

        foreach ($movies as $movie) {
            $movie->recent_tickets_sold = $salesStats[$movie->movie_id] ?? 0;
        }

        // 3. TRUY VẤN SUẤT CHIẾU HIỆN CÓ CỦA NGÀY TRONG DATABASE
        $existingDb = Showtime::with(['movie', 'room', 'showtimeSeats'])
            ->whereDate('showtime_start', $targetDate)
            ->whereHas('room', function ($q) use ($cinemaId) {
                $q->where('cinema_id', $cinemaId);
            })
            ->orderBy('showtime_start', 'asc')
            ->get();

        $existingShowtimes = [];
        foreach ($existingDb as $ex) {
            $bookedCount = $ex->showtimeSeats ? $ex->showtimeSeats->where('status', 'booked')->count() : 0;
            $existingShowtimes[] = [
                'showtime_id'    => $ex->showtime_id,
                'room_id'        => $ex->room_id,
                'room_name'      => $ex->room?->room_name ?? "Phòng {$ex->room_id}",
                'room_type'      => $ex->room?->room_type ?? '2D Standard',
                'screen_type'    => $ex->room?->screen_type ?? 'standard_2d',
                'movie_id'       => $ex->movie_id,
                'movie_title'    => $ex->movie?->title ?? 'Phim',
                'showtime_start' => $ex->showtime_start->format('Y-m-d H:i:s'),
                'showtime_end'   => $ex->showtime_end->format('Y-m-d H:i:s'),
                'start_time'     => $ex->showtime_start->format('H:i'),
                'end_time'       => $ex->showtime_end->format('H:i'),
                'base_price'     => (float) $ex->base_price,
                'booked_seats'   => $bookedCount,
                'is_locked'      => $bookedCount > 0,
                'buffer_minutes' => $bufferMinutes,
            ];
        }

        $isRefinement = !empty($currentDraftShowtimes);
        $effectivePrompt = !empty($userPrompt)
            ? $userPrompt
            : "Hãy phân tích danh sách phim (thể loại, thời lượng, độ hot) và các phòng chiếu để lập lịch chiếu toàn diện, tối ưu cho ngày {$targetDate}.";

        // ── CHẾ ĐỘ PURE LLM COPILOT (100% AI Suy luận theo ý Admin & Context) ──
        $llmResult = $this->solvePromptWithLlm(
            $config,
            $cinema,
            $rooms,
            $movies,
            $targetDate,
            $effectivePrompt,
            $existingShowtimes,
            $currentDraftShowtimes,
            $chatHistory,
            $scheduleMode,
            $primeInfo,
            $bufferMinutes,
            $staggeringGap,
            $openingTime,
            $closingTime,
            $defaultBasePrice,
            $selectedRoomIds,
            $selectedMovieIds,
            $overrideConfig
        );

        $draftShowtimes = $llmResult['draft_showtimes'];
        $aiExplanation = $llmResult['explanation'] ?? 'AI Copilot đã cập nhật lịch chiếu theo yêu cầu của bạn.';
        $aiThinkingSteps = $llmResult['thinking_steps'] ?? [];

        // Tự động chữa lành và căn chỉnh dòng thời gian tránh xung đột (Collision Free)
        $anchorShowtimes = ($scheduleMode === 'replace_all')
            ? array_values(array_filter($existingShowtimes, fn ($st) => !empty($st['is_locked']) || (int) ($st['booked_seats'] ?? 0) > 0))
            : $existingShowtimes;

        $draftShowtimes = ScheduleConstraintValidator::autoResolveConflicts(
            $draftShowtimes,
            $anchorShowtimes,
            $bufferMinutes,
            $openingTime,
            $closingTime,
            $targetDate,
            $primeInfo
        );

        // Kiểm tra xung đột & cảnh báo
        $validation = ScheduleConstraintValidator::validateDraftBatch(
            $draftShowtimes,
            $anchorShowtimes,
            $bufferMinutes,
            $staggeringGap,
            $openingTime,
            $closingTime
        );

        $financialSummary = $this->calculateDraftFinancials($draftShowtimes, $rooms, $primeInfo, $targetDate);

        return [
            'summary' => array_merge($financialSummary, [
                'total_showtimes'          => count($draftShowtimes),
                'total_rooms_used'         => count(array_unique(array_column($draftShowtimes, 'room_id'))),
                'strategy_id'              => null,
                'mode'                     => 'prompt',
                'is_refinement'            => $isRefinement,
                'schedule_mode'            => $scheduleMode,
                'target_date'              => $targetDate,
                'cinema_id'                => $cinemaId,
                'cinema_name'              => $cinema->cinema_name,
                'prime_time_info'          => $primeInfo,
                'strategy_explanation'     => $aiExplanation,
                'thinking_steps'           => $aiThinkingSteps,
            ]),
            'draft_showtimes'    => $draftShowtimes,
            'existing_showtimes' => $existingShowtimes,
            'validation'         => $validation,
        ];
    }

    /**
     * Chuyển đổi danh sách suất chiếu hiện có từ DB sang định dạng bản nháp
     */
    private function convertExistingToDraft(
        array $existingShowtimes,
        $movies,
        $rooms,
        int $bufferMinutes,
        array $primeInfo,
        float $defaultBasePrice
    ): array {
        $moviesById = $movies->keyBy('movie_id');
        $roomsById = $rooms->keyBy('room_id');
        $drafts = [];

        foreach ($existingShowtimes as $idx => $ex) {
            $mId = (int) $ex['movie_id'];
            $rId = (int) $ex['room_id'];
            $movie = $moviesById->get($mId);
            $room = $roomsById->get($rId);

            $start = Carbon::parse($ex['showtime_start']);
            $duration = (int) ($movie?->duration ?: 120);
            $end = isset($ex['showtime_end']) ? Carbon::parse($ex['showtime_end']) : (clone $start)->addMinutes($duration);

            $timeStr = $start->format('H:i');
            $isPrime = ($timeStr >= ($primeInfo['time_from'] ?? '18:00') && $timeStr <= ($primeInfo['time_to'] ?? '22:30'));
            $calculatedRoomBasePrice = $room ? $this->calculateRoomBasePrice($room, $defaultBasePrice) : 100000;

            $drafts[] = [
                'temp_id'        => "draft_db_{$rId}_" . $start->format('Hi') . "_{$idx}",
                'movie_id'       => $mId,
                'movie_title'    => $ex['movie_title'] ?? ($movie?->title ?? 'Phim'),
                'movie_poster'   => $movie?->poster_path ?? '',
                'duration'       => $duration,
                'room_id'        => $rId,
                'room_name'      => $ex['room_name'] ?? ($room?->room_name ?? "Phòng {$rId}"),
                'room_type'      => $room?->room_type ?? '2D Standard',
                'room_capacity'  => $room?->total_seats ?? 100,
                'showtime_start' => $start->format('Y-m-d H:i:s'),
                'showtime_end'   => $end->format('Y-m-d H:i:s'),
                'base_price'     => (float) ($ex['base_price'] ?? $calculatedRoomBasePrice),
                'buffer_minutes' => $bufferMinutes,
                'is_prime_time'  => $isPrime,
                'is_locked'      => !empty($ex['is_locked']),
                'booked_seats'   => (int) ($ex['booked_seats'] ?? 0),
            ];
        }

        return $drafts;
    }

    /**
     * AGENT ACTION TOOL 1: Nén dòng thời gian / Xếp sát khít nhau (COMPRESS_TIMELINE)
     */
    private function handleCompressTimeline(
        array $sourceShowtimes,
        $rooms,
        $movies,
        int $bufferMinutes,
        int $staggeringGap,
        string $openingTime,
        string $closingTime,
        string $targetDate,
        array $primeInfo,
        float $defaultBasePrice,
        array $roomIdsFilter = []
    ): array {
        if (empty($sourceShowtimes)) {
            return [];
        }

        $draftsByRoom = [];
        foreach ($sourceShowtimes as $st) {
            $rId = (int) $st['room_id'];
            $draftsByRoom[$rId][] = $st;
        }

        $moviesById = $movies->keyBy('movie_id');
        $roomsById = $rooms->keyBy('room_id');
        $resolved = [];
        $roomIndex = 0;

        foreach ($draftsByRoom as $roomId => $roomDrafts) {
            if (!empty($roomIdsFilter) && !in_array($roomId, $roomIdsFilter)) {
                $resolved = array_merge($resolved, $roomDrafts);
                continue;
            }

            usort($roomDrafts, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));
            $room = $roomsById->get($roomId);
            $roomBasePrice = $room ? $this->calculateRoomBasePrice($room, $defaultBasePrice) : $defaultBasePrice;

            $staggerOffset = ($roomIndex * $staggeringGap) % 60;
            $roomPointer = Carbon::parse("{$targetDate} {$openingTime}:00")->addMinutes($staggerOffset);
            $closeCarbon = Carbon::parse("{$targetDate} {$closingTime}:00")->addMinutes(90);

            foreach ($roomDrafts as $idx => $st) {
                $mId = (int) $st['movie_id'];
                $movie = $moviesById->get($mId);
                $duration = (int) ($st['duration'] ?? ($movie?->duration ?: 120));

                $actualStart = clone $roomPointer;
                $actualEnd = (clone $actualStart)->addMinutes($duration);

                if ($actualStart->greaterThan($closeCarbon)) {
                    continue;
                }

                $timeStr = $actualStart->format('H:i');
                $isPrime = ($timeStr >= ($primeInfo['time_from'] ?? '18:00') && $timeStr <= ($primeInfo['time_to'] ?? '22:30'));

                $st['showtime_start'] = $actualStart->format('Y-m-d H:i:s');
                $st['showtime_end'] = $actualEnd->format('Y-m-d H:i:s');
                $st['duration'] = $duration;
                $st['buffer_minutes'] = $bufferMinutes;
                $st['is_prime_time'] = $isPrime;
                $st['temp_id'] = "draft_cmp_{$roomId}_" . $actualStart->format('Hi') . "_{$idx}";

                $resolved[] = $st;

                // Suất tiếp theo bắt đầu ngay sau giờ kết thúc + buffer dọn phòng
                $roomPointer = (clone $actualEnd)->addMinutes($bufferMinutes);
            }

            $roomIndex++;
        }

        usort($resolved, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));
        return $resolved;
    }

    /**
     * AGENT ACTION TOOL 2: Dời giờ / Tiến hoặc lùi thời gian (SHIFT_SHOWTIMES)
     */
    private function handleShiftShowtimes(
        array $sourceShowtimes,
        int $shiftMinutes,
        array $primeInfo,
        array $roomIdsFilter = [],
        array $movieIdsFilter = []
    ): array {
        $resolved = [];

        foreach ($sourceShowtimes as $idx => $st) {
            $rId = (int) $st['room_id'];
            $mId = (int) $st['movie_id'];

            $isRoomMatch = empty($roomIdsFilter) || in_array($rId, $roomIdsFilter);
            $isMovieMatch = empty($movieIdsFilter) || in_array($mId, $movieIdsFilter);

            if ($isRoomMatch && $isMovieMatch) {
                $start = Carbon::parse($st['showtime_start'])->addMinutes($shiftMinutes);
                $end = Carbon::parse($st['showtime_end'])->addMinutes($shiftMinutes);
                $timeStr = $start->format('H:i');
                $isPrime = ($timeStr >= ($primeInfo['time_from'] ?? '18:00') && $timeStr <= ($primeInfo['time_to'] ?? '22:30'));

                $st['showtime_start'] = $start->format('Y-m-d H:i:s');
                $st['showtime_end'] = $end->format('Y-m-d H:i:s');
                $st['is_prime_time'] = $isPrime;
                $st['temp_id'] = "draft_sft_{$rId}_" . $start->format('Hi') . "_{$idx}";
            }

            $resolved[] = $st;
        }

        usort($resolved, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));
        return $resolved;
    }

    /**
     * AGENT ACTION TOOL 3: Đổi phim hoặc gán phim vào phòng/khung giờ (SWAP_OR_ASSIGN_MOVIE)
     */
    private function handleSwapOrAssignMovie(
        array $sourceShowtimes,
        $targetMovieIdOrName,
        $movies,
        $rooms,
        array $primeInfo,
        array $roomIdsFilter = [],
        $sourceMovieIdOrName = null,
        ?string $timeFrom = null,
        ?string $timeTo = null
    ): array {
        $targetMovie = null;
        if (is_numeric($targetMovieIdOrName)) {
            $targetMovie = $movies->firstWhere('movie_id', (int) $targetMovieIdOrName);
        }
        if (!$targetMovie && is_string($targetMovieIdOrName)) {
            $targetMovie = $movies->first(function ($m) use ($targetMovieIdOrName) {
                return mb_stripos($m->title, $targetMovieIdOrName) !== false;
            });
        }
        if (!$targetMovie) {
            $targetMovie = $movies->first();
        }

        $resolved = [];
        foreach ($sourceShowtimes as $idx => $st) {
            $rId = (int) $st['room_id'];
            $mId = (int) $st['movie_id'];
            $start = Carbon::parse($st['showtime_start']);
            $timeStr = $start->format('H:i');

            $isRoomMatch = empty($roomIdsFilter) || in_array($rId, $roomIdsFilter);
            $isTimeMatch = true;
            if ($timeFrom && $timeStr < $timeFrom) $isTimeMatch = false;
            if ($timeTo && $timeStr > $timeTo) $isTimeMatch = false;

            $isSourceMatch = true;
            if ($sourceMovieIdOrName) {
                if (is_numeric($sourceMovieIdOrName) && $mId !== (int) $sourceMovieIdOrName) {
                    $isSourceMatch = false;
                } elseif (is_string($sourceMovieIdOrName) && mb_stripos($st['movie_title'] ?? '', $sourceMovieIdOrName) === false) {
                    $isSourceMatch = false;
                }
            }

            if ($isRoomMatch && $isTimeMatch && $isSourceMatch && $targetMovie) {
                $duration = (int) ($targetMovie->duration ?: 120);
                $end = (clone $start)->addMinutes($duration);

                $st['movie_id'] = $targetMovie->movie_id;
                $st['movie_title'] = $targetMovie->title;
                $st['movie_poster'] = $targetMovie->poster_path;
                $st['duration'] = $duration;
                $st['showtime_end'] = $end->format('Y-m-d H:i:s');
                $st['temp_id'] = "draft_swp_{$rId}_" . $start->format('Hi') . "_{$idx}";
            }

            $resolved[] = $st;
        }

        usort($resolved, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));
        return $resolved;
    }

    /**
     * AGENT ACTION TOOL 4: Xóa bớt suất chiếu chưa bán vé (DELETE_SHOWTIMES)
     * Hỗ trợ xóa chính xác theo target_temp_ids, xóa theo số lượng limit, hoặc lọc theo phòng/giờ/phim.
     */
    private function handleDeleteShowtimes(
        array $sourceShowtimes,
        $movies,
        array $targetTempIds = [],
        array $roomIdsFilter = [],
        $movieIdentifier = null,
        ?string $timeSlot = null,
        ?string $timeFrom = null,
        ?string $timeTo = null,
        ?int $limit = null,
        bool $deleteAll = false,
        bool $unbookedOnly = true
    ): array {
        $resolved = [];
        $deletedCount = 0;
        $protectedCount = 0;

        $hasSpecificFilter = !empty($targetTempIds)
            || !empty($roomIdsFilter)
            || !empty($movieIdentifier)
            || !empty($timeSlot)
            || !empty($timeFrom)
            || !empty($timeTo);

        // BẢO VỆ AN TOÀN: Nếu không có bất kỳ bộ lọc cụ thể nào và KHÔNG có cờ delete_all = true và không có limit
        // Tuyệt đối không xóa bừa bãi toàn bộ lịch!
        if (!$hasSpecificFilter && !$deleteAll && empty($limit)) {
            return [
                'draft_showtimes' => $sourceShowtimes,
                'deleted_count'   => 0,
                'protected_count' => 0,
                'warning'         => 'Không có tiêu chí xóa cụ thể hoặc suất chiếu chỉ định. Hệ thống giữ nguyên lịch chiếu.',
            ];
        }

        foreach ($sourceShowtimes as $st) {
            $rId = (int) $st['room_id'];
            $mId = (int) $st['movie_id'];
            $tempId = $st['temp_id'] ?? '';
            $bookedSeats = (int) ($st['booked_seats'] ?? 0);
            $isLocked = !empty($st['is_locked']);

            $start = Carbon::parse($st['showtime_start']);
            $timeStr = $start->format('H:i');

            // Kiểm tra nếu đã đủ số lượng limit xóa
            if ($limit !== null && $limit > 0 && $deletedCount >= $limit) {
                $resolved[] = $st;
                continue;
            }

            // 1. Kiểm tra lọc theo ID suất cụ thể
            $isIdMatch = true;
            if (!empty($targetTempIds)) {
                $isIdMatch = in_array($tempId, $targetTempIds);
            }

            // 2. Kiểm tra bộ lọc phòng
            $isRoomMatch = empty($roomIdsFilter) || in_array($rId, $roomIdsFilter);

            // 3. Kiểm tra bộ lọc giờ
            $isTimeMatch = true;
            if ($timeFrom && $timeStr < $timeFrom) $isTimeMatch = false;
            if ($timeTo && $timeStr > $timeTo) $isTimeMatch = false;

            if ($timeSlot) {
                $slotLower = mb_strtolower($timeSlot);
                if (str_contains($slotLower, 'sáng') || str_contains($slotLower, 'morning')) {
                    if ($timeStr >= '12:00') $isTimeMatch = false;
                } elseif (str_contains($slotLower, 'chiều') || str_contains($slotLower, 'afternoon')) {
                    if ($timeStr < '12:00' || $timeStr >= '18:00') $isTimeMatch = false;
                } elseif (str_contains($slotLower, 'tối') || str_contains($slotLower, 'evening')) {
                    if ($timeStr < '18:00' || $timeStr >= '22:00') $isTimeMatch = false;
                } elseif (str_contains($slotLower, 'đêm') || str_contains($slotLower, 'night') || str_contains($slotLower, 'khuya')) {
                    if ($timeStr < '22:00') $isTimeMatch = false;
                }
            }

            // 4. Kiểm tra bộ lọc phim
            $isMovieMatch = true;
            if ($movieIdentifier) {
                if (is_numeric($movieIdentifier) && $mId !== (int) $movieIdentifier) {
                    $isMovieMatch = false;
                } elseif (is_string($movieIdentifier) && mb_stripos($st['movie_title'] ?? '', $movieIdentifier) === false) {
                    $isMovieMatch = false;
                }
            }

            $shouldDelete = $deleteAll || (!empty($targetTempIds) ? $isIdMatch : ($isIdMatch && $isRoomMatch && $isTimeMatch && $isMovieMatch));

            if ($shouldDelete) {
                if ($unbookedOnly && ($bookedSeats > 0 || $isLocked)) {
                    // ĐÃ CÓ VÉ BÁN HOẶC BỊ KHÓA -> BẢO VỆ TUYỆT ĐỐI KHÔNG XÓA
                    $protectedCount++;
                    $resolved[] = $st;
                } else {
                    // XÓA SUẤT NÀY KHỎI BẢN NHÁP
                    $deletedCount++;
                }
            } else {
                $resolved[] = $st;
            }
        }

        return [
            'draft_showtimes' => $resolved,
            'deleted_count'   => $deletedCount,
            'protected_count' => $protectedCount,
        ];
    }

    /**
     * AGENT ACTION TOOL 5: Cập nhật 1 suất chiếu cụ thể (UPDATE_SHOWTIME)
     */
    private function handleUpdateShowtime(
        array $sourceShowtimes,
        string $targetTempId,
        $movies,
        $rooms,
        array $primeInfo,
        string $targetDate,
        ?string $newStartTime = null,
        $newMovieIdOrTitle = null,
        ?int $newRoomId = null,
        float $defaultBasePrice = 100000.0
    ): array {
        $moviesById = $movies->keyBy('movie_id');
        $roomsById = $rooms->keyBy('room_id');
        $resolved = [];
        $updated = false;

        foreach ($sourceShowtimes as $st) {
            $tempId = $st['temp_id'] ?? '';
            if ($tempId === $targetTempId || (!$updated && empty($targetTempId))) {
                if ($newRoomId && $roomsById->has($newRoomId)) {
                    $st['room_id'] = $newRoomId;
                    $roomObj = $roomsById->get($newRoomId);
                    $st['room_name'] = $roomObj->room_name;
                    $st['room_type'] = $roomObj->room_type;
                    $st['room_capacity'] = $roomObj->total_seats;
                    $st['base_price'] = $this->calculateRoomBasePrice($roomObj, $defaultBasePrice);
                }

                if ($newMovieIdOrTitle) {
                    $targetMovie = null;
                    if (is_numeric($newMovieIdOrTitle)) {
                        $targetMovie = $moviesById->get((int) $newMovieIdOrTitle);
                    }
                    if (!$targetMovie && is_string($newMovieIdOrTitle)) {
                        $targetMovie = $movies->first(fn ($m) => mb_stripos($m->title, $newMovieIdOrTitle) !== false);
                    }
                    if ($targetMovie) {
                        $st['movie_id'] = $targetMovie->movie_id;
                        $st['movie_title'] = $targetMovie->title;
                        $st['movie_poster'] = $targetMovie->poster_path;
                        $st['duration'] = (int) ($targetMovie->duration ?: 120);
                    }
                }

                if ($newStartTime) {
                    $startStr = strlen($newStartTime) <= 5 ? "{$targetDate} {$newStartTime}:00" : $newStartTime;
                    $newStartCarbon = Carbon::parse($startStr);
                    $duration = (int) ($st['duration'] ?? 120);
                    $newEndCarbon = (clone $newStartCarbon)->addMinutes($duration);

                    $timeStr = $newStartCarbon->format('H:i');
                    $isPrime = ($timeStr >= ($primeInfo['time_from'] ?? '18:00') && $timeStr <= ($primeInfo['time_to'] ?? '22:30'));

                    $st['showtime_start'] = $newStartCarbon->format('Y-m-d H:i:s');
                    $st['showtime_end'] = $newEndCarbon->format('Y-m-d H:i:s');
                    $st['is_prime_time'] = $isPrime;
                }

                $updated = true;
            }
            $resolved[] = $st;
        }

        usort($resolved, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));
        return $resolved;
    }

    /**
     * AGENT ACTION TOOL 6: Thêm 1 suất chiếu mới vào phòng/giờ chỉ định (ADD_SHOWTIME)
     */
    private function handleAddShowtime(
        array $sourceShowtimes,
        $movieIdOrTitle,
        $roomIdOrName,
        string $startTime,
        $movies,
        $rooms,
        array $primeInfo,
        string $targetDate,
        int $bufferMinutes,
        float $defaultBasePrice
    ): array {
        $moviesById = $movies->keyBy('movie_id');
        $roomsById = $rooms->keyBy('room_id');

        // Tìm phim
        $targetMovie = null;
        if (is_numeric($movieIdOrTitle)) {
            $targetMovie = $moviesById->get((int) $movieIdOrTitle);
        }
        if (!$targetMovie && is_string($movieIdOrTitle)) {
            $targetMovie = $movies->first(fn ($m) => mb_stripos($m->title, $movieIdOrTitle) !== false);
        }
        if (!$targetMovie) {
            $targetMovie = $movies->first();
        }

        // Tìm phòng
        $targetRoom = null;
        if (is_numeric($roomIdOrName)) {
            $targetRoom = $roomsById->get((int) $roomIdOrName);
        }
        if (!$targetRoom && is_string($roomIdOrName)) {
            $targetRoom = $rooms->first(fn ($r) => mb_stripos($r->room_name, $roomIdOrName) !== false);
        }
        if (!$targetRoom) {
            $targetRoom = $rooms->first();
        }

        if (!$targetMovie || !$targetRoom) {
            return $sourceShowtimes;
        }

        $startStr = strlen($startTime) <= 5 ? "{$targetDate} {$startTime}:00" : $startTime;
        $startCarbon = Carbon::parse($startStr);
        $duration = (int) ($targetMovie->duration ?: 120);
        $endCarbon = (clone $startCarbon)->addMinutes($duration);

        $timeStr = $startCarbon->format('H:i');
        $isPrime = ($timeStr >= ($primeInfo['time_from'] ?? '18:00') && $timeStr <= ($primeInfo['time_to'] ?? '22:30'));
        $roomBasePrice = $this->calculateRoomBasePrice($targetRoom, $defaultBasePrice);

        $newShowtime = [
            'temp_id'        => "draft_add_{$targetRoom->room_id}_" . $startCarbon->format('Hi') . "_" . time(),
            'movie_id'       => $targetMovie->movie_id,
            'movie_title'    => $targetMovie->title,
            'movie_poster'   => $targetMovie->poster_path,
            'duration'       => $duration,
            'room_id'        => $targetRoom->room_id,
            'room_name'      => $targetRoom->room_name,
            'room_type'      => $targetRoom->room_type,
            'room_capacity'  => $targetRoom->total_seats,
            'showtime_start' => $startCarbon->format('Y-m-d H:i:s'),
            'showtime_end'   => $endCarbon->format('Y-m-d H:i:s'),
            'base_price'     => $roomBasePrice,
            'buffer_minutes' => $bufferMinutes,
            'is_prime_time'  => $isPrime,
            'booked_seats'   => 0,
            'is_locked'      => false,
        ];

        $sourceShowtimes[] = $newShowtime;
        usort($sourceShowtimes, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));
        return $sourceShowtimes;
    }

    /**
     * Điều phối AI Agent Intent Router & Action Dispatcher
     */
    private function solvePromptWithLlm(
        AiScheduleConfig $config,
        Cinema $cinema,
        $rooms,
        $movies,
        string $targetDate,
        string $userPrompt,
        array $existingShowtimes,
        array $currentDraftShowtimes,
        array $chatHistory,
        string $scheduleMode,
        array $primeInfo,
        int $bufferMinutes,
        int $staggeringGap,
        string $openingTime,
        string $closingTime,
        float $defaultBasePrice,
        array $selectedRoomIds,
        array $selectedMovieIds,
        array $overrideConfig
    ): array {
        // Nguồn suất chiếu ban đầu: Bản nháp hiện tại HOẶC Suất chiếu có sẵn trong DB
        $sourceShowtimes = !empty($currentDraftShowtimes)
            ? $currentDraftShowtimes
            : (!empty($existingShowtimes) ? $this->convertExistingToDraft($existingShowtimes, $movies, $rooms, $bufferMinutes, $primeInfo, $defaultBasePrice) : []);

        try {
            $driver = AiProviderFactory::createFromConfig($config, $overrideConfig);

            $roomsData = $rooms->map(fn ($r) => [
                'room_id'          => $r->room_id,
                'room_name'        => $r->room_name,
                'room_type'        => $r->room_type,
                'screen_type'      => $r->screen_type,
                'is_premium'       => in_array($r->screen_type, ['imax_laser', 'screenx', 'dolby_cinema']),
                'total_seats'      => $r->total_seats,
            ])->toArray();

            $moviesData = $movies->map(fn ($m) => [
                'movie_id'            => $m->movie_id,
                'title'               => $m->title,
                'duration_minutes'    => $m->duration ?: 120,
                'popularity_score'    => $m->popularity,
                'genres'              => $m->genres->pluck('name')->toArray(),
            ])->toArray();

            // Rich Snapshot Context: Danh sách chi tiết các suất chiếu hiện có trên màn hình
            $sourceShowtimesCompact = array_map(function ($st) {
                return [
                    'temp_id'      => $st['temp_id'] ?? ("draft_" . ($st['showtime_id'] ?? ($st['room_id'] . '_' . str_replace(':', '', substr($st['showtime_start'], 11, 5))))),
                    'room_id'      => (int) $st['room_id'],
                    'room_name'    => $st['room_name'] ?? ('Phòng ' . $st['room_id']),
                    'movie_id'     => (int) $st['movie_id'],
                    'movie_title'  => $st['movie_title'] ?? 'Phim',
                    'start_time'   => substr($st['showtime_start'], 11, 5), // HH:mm
                    'end_time'     => substr($st['showtime_end'] ?? '', 11, 5),   // HH:mm
                    'duration'     => (int) ($st['duration'] ?? 120),
                    'booked_seats' => (int) ($st['booked_seats'] ?? 0),
                    'is_locked'    => !empty($st['is_locked']) || (int) ($st['booked_seats'] ?? 0) > 0,
                    'is_prime'     => !empty($st['is_prime_time']),
                ];
            }, $sourceShowtimes);

            $sourceCount = count($sourceShowtimes);
            $roomsJson = $this->toJsonPretty($roomsData);
            $moviesJson = $this->toJsonPretty($moviesData);
            $showtimesJson = $this->toJsonPretty($sourceShowtimesCompact);

            $systemPrompt = <<<PROMPT
Bạn là Trợ Lý AI Điều Hành Rạp Chiếu Phim Cao Cấp (CineDot AI Schedule Operator Agent).
Nhiệm vụ của bạn là hiểu sâu sắc yêu cầu của Admin, phân tích danh sách suất chiếu hiện có và CHỌN HÀNH ĐỘNG (ACTION) chính xác để thực thi.

=== THÔNG TIN VẬN HÀNH HIỆN TẠI ===
- Rạp: {$cinema->cinema_name} | Ngày chiếu: {$targetDate}
- Khung giờ hoạt động: {$openingTime} - {$closingTime} | Thời gian dọn phòng (buffer): {$bufferMinutes} phút
- Khung giờ vàng (Prime Time): {$primeInfo['display_text']}
- Tổng số suất chiếu đang hiển thị: {$sourceCount} suất

=== DANH SÁCH PHÒNG CHIẾU ===
{$roomsJson}

=== DANH SÁCH PHIM ĐANG CÓ TẠI RẠP ===
{$moviesJson}

=== DANH SÁCH SUẤT CHIẾU HIỆN CÓ TRÊN TIMELINE (SNAPSHOT CONTEXT) ===
{$showtimesJson}

=== CÁC ACTION TOOLS KHẢ DỤNG ===

1. `CUSTOM_SCHEDULE_SPEC`: AI tự do thiết kế và lập toàn bộ kế hoạch lịch chiếu cho một hoặc nhiều phòng (Dùng khi tạo mới lịch từ đầu, xếp lịch cả ngày, hoặc Admin có yêu cầu xếp lịch tùy biến toàn diện).
   * Bạn hãy tính toán giờ bắt đầu và kết thúc từng suất theo thời lượng phim (duration), buffer dọn phòng ({$bufferMinutes} phút), giờ mở cửa ({$openingTime}) đến đóng cửa ({$closingTime}), và ƯU TIÊN TUYỆT ĐỐI THEO Ý ĐỊNH CỦA ADMIN trong prompt (ví dụ thể loại, phòng chiếu, khung giờ).
   Params:
   {
     "action": "CUSTOM_SCHEDULE_SPEC",
     "params": {
       "showtimes": [
         {
           "movie_id": 1, // ID hoặc Tên phim
           "room_id": 1, // ID hoặc Tên phòng
           "showtime_start": "{$targetDate} 09:00:00", // YYYY-MM-DD HH:mm:ss hoặc "09:00"
           "showtime_end": "{$targetDate} 11:00:00" // (tùy chọn)
         }
       ]
     },
     "explanation": "Đã lập lịch chiếu chi tiết dựa theo yêu cầu của Admin."
   }

2. `DELETE_SHOWTIMES`: Xóa 1 hoặc nhiều suất chiếu theo yêu cầu.
   * Chú ý quan trọng:
     - Nếu Admin bảo "xóa 1 lịch chiếu...", "xóa suất chiếu lúc 9:00 phòng 1", hãy chọn đúng `target_temp_ids: ["temp_id_cần_xóa"]` từ danh sách snapshot, hoặc đặt `limit: 1`.
     - Tuyệt đối không xóa toàn bộ trừ khi Admin ghi rõ "xóa tất cả", "xóa toàn bộ lịch", "clear hết".
   Params:
   {
     "action": "DELETE_SHOWTIMES",
     "params": {
       "target_temp_ids": ["draft_1_0900_0"], // Mảng temp_id của suất chiếu cụ thể cần xóa
       "limit": 1, // Số lượng suất tối đa cần xóa
       "room_ids": [], // Mảng phòng cần xóa (nếu lọc theo phòng)
       "movie_id": null, // ID hoặc tên phim cần xóa
       "time_slot": null, // 'morning' | 'afternoon' | 'evening' | 'night'
       "time_from": null, // 'HH:mm'
       "time_to": null, // 'HH:mm'
       "delete_all": false // Chỉ true khi Admin yêu cầu xóa toàn bộ lịch chiếu trong ngày
     },
     "explanation": "Đã xóa suất chiếu theo yêu cầu của Admin (bảo vệ các suất đã có vé bán)."
   }

3. `UPDATE_SHOWTIME`: Cập nhật / dời / đổi phim cho 1 suất chiếu cụ thể.
   Params:
   {
     "action": "UPDATE_SHOWTIME",
     "params": {
       "target_temp_id": "draft_1_0900_0", // temp_id của suất cần sửa
       "new_start_time": "09:30", // giờ bắt đầu mới (HH:mm)
       "new_movie_id": null, // ID hoặc tên phim mới (nếu đổi phim)
       "new_room_id": null // ID phòng mới (nếu đổi phòng)
     },
     "explanation": "Đã dời giờ suất chiếu sang 09:30."
   }

4. `ADD_SHOWTIME`: Chèn thêm 1 suất chiếu mới vào phòng và giờ chỉ định.
   Params:
   {
     "action": "ADD_SHOWTIME",
     "params": {
       "movie_id": 969681, // ID hoặc tên phim
       "room_id": 1, // ID phòng chiếu
       "start_time": "20:00" // Giờ bắt đầu (HH:mm)
     },
     "explanation": "Đã thêm 1 suất chiếu mới vào phòng 1 lúc 20:00."
   }

5. `COMPRESS_TIMELINE`: Kéo toàn bộ hoặc một số phòng chiếu sát nhau liên tục theo đúng buffer dọn phòng {$bufferMinutes} phút.
   Params:
   {
     "action": "COMPRESS_TIMELINE",
     "params": {
       "room_ids": [] // để trống nếu áp dụng toàn bộ phòng, hoặc [1, 2]
     },
     "explanation": "Đã sắp xếp các suất chiếu sát khít nhau liên tục."
   }

6. `SHIFT_SHOWTIMES`: Dời thời gian chiếu tiến hoặc lùi X phút hàng loạt.
   Params:
   {
     "action": "SHIFT_SHOWTIMES",
     "params": {
       "shift_minutes": 30, // số phút (dương = lùi, âm = đẩy sớm)
       "room_ids": [],
       "movie_ids": []
     },
     "explanation": "Đã dời các suất chiếu 30 phút."
   }

7. `SWAP_OR_ASSIGN_MOVIE`: Đổi phim hàng loạt hoặc gán phim vào phòng/khung giờ.
   Params:
   {
     "action": "SWAP_OR_ASSIGN_MOVIE",
     "params": {
       "target_movie_id": 969681,
       "source_movie_id": null,
       "room_ids": [1],
       "time_from": "18:00",
       "time_to": "22:30"
     },
     "explanation": "Đã đổi phim trong khung giờ chỉ định."
   }

BẮT BUỘC TRẢ VỀ JSON THUẦN TÚY KHÔNG MARKDOWN DẠNG:
{
  "action": "CUSTOM_SCHEDULE_SPEC" | "DELETE_SHOWTIMES" | "UPDATE_SHOWTIME" | "ADD_SHOWTIME" | "COMPRESS_TIMELINE" | "SHIFT_SHOWTIMES" | "SWAP_OR_ASSIGN_MOVIE",
  "params": { ... },
  "explanation": "Giải thích chi tiết hành động đã thực hiện cho Admin",
  "thinking_steps": [
    { "title": "Bước phân tích 1", "detail": "Chi tiết", "status": "completed" }
  ]
}
PROMPT;

            $fullUserPrompt = $userPrompt;
            if (!empty($chatHistory)) {
                $historyStr = "";
                foreach (array_slice($chatHistory, -4) as $msg) {
                    $roleName = $msg['role'] === 'user' ? 'Admin' : 'AI Copilot';
                    $historyStr .= "{$roleName}: {$msg['content']}\n";
                }
                $fullUserPrompt = "Lịch sử tương tác:\n{$historyStr}\n\nYêu cầu hiện tại của Admin: {$userPrompt}";
            }

            $response = $driver->generateStructuredSchedule($systemPrompt, $fullUserPrompt);
            $action = strtoupper($response['action'] ?? '');
            $params = $response['params'] ?? [];
            $explanation = $response['explanation'] ?? 'AI Copilot đã cập nhật lịch chiếu thành công.';
            $thinkingSteps = $response['thinking_steps'] ?? [];

            // DISPATCH TO DETERMINISTIC ACTION HANDLERS
            $draftShowtimes = [];

            switch ($action) {
                case 'DELETE_SHOWTIMES':
                case 'REMOVE_SHOWTIMES':
                    $targetTempIds = (array) ($params['target_temp_ids'] ?? ($params['temp_ids'] ?? []));
                    if (!empty($params['target_temp_id'])) {
                        $targetTempIds[] = (string) $params['target_temp_id'];
                    }
                    if (!empty($params['temp_id'])) {
                        $targetTempIds[] = (string) $params['temp_id'];
                    }

                    $roomFilter = (array) ($params['room_ids'] ?? []);
                    $movieIdent = $params['movie_id'] ?? ($params['movie_title'] ?? ($params['movie_name'] ?? null));
                    $timeSlot = $params['time_slot'] ?? null;
                    $timeFrom = $params['time_from'] ?? null;
                    $timeTo = $params['time_to'] ?? null;
                    $limit = isset($params['limit']) ? (int) $params['limit'] : null;
                    $deleteAll = !empty($params['delete_all']);

                    $delResult = $this->handleDeleteShowtimes(
                        $sourceShowtimes,
                        $movies,
                        $targetTempIds,
                        $roomFilter,
                        $movieIdent,
                        $timeSlot,
                        $timeFrom,
                        $timeTo,
                        $limit,
                        $deleteAll,
                        true
                    );
                    $draftShowtimes = $delResult['draft_showtimes'];
                    if (!empty($delResult['warning']) && $delResult['deleted_count'] === 0) {
                        $explanation = $delResult['warning'];
                    } else {
                        $explanation = "Đã xóa {$delResult['deleted_count']} suất chiếu theo yêu cầu.";
                        if ($delResult['protected_count'] > 0) {
                            $explanation .= " (Đã bảo vệ {$delResult['protected_count']} suất chiếu có khách đã đặt vé).";
                        }
                    }
                    break;

                case 'UPDATE_SHOWTIME':
                    $targetTempId = (string) ($params['target_temp_id'] ?? ($params['temp_id'] ?? ''));
                    $newStartTime = $params['new_start_time'] ?? ($params['start_time'] ?? null);
                    $newMovieId = $params['new_movie_id'] ?? ($params['movie_id'] ?? null);
                    $newRoomId = isset($params['new_room_id']) ? (int) $params['new_room_id'] : null;

                    $draftShowtimes = $this->handleUpdateShowtime(
                        $sourceShowtimes,
                        $targetTempId,
                        $movies,
                        $rooms,
                        $primeInfo,
                        $targetDate,
                        $newStartTime,
                        $newMovieId,
                        $newRoomId,
                        $defaultBasePrice
                    );
                    break;

                case 'ADD_SHOWTIME':
                    $movieId = $params['movie_id'] ?? ($params['movie_title'] ?? null);
                    $roomId = $params['room_id'] ?? ($params['room_name'] ?? null);
                    $startTime = $params['start_time'] ?? '19:00';

                    $draftShowtimes = $this->handleAddShowtime(
                        $sourceShowtimes,
                        $movieId,
                        $roomId,
                        $startTime,
                        $movies,
                        $rooms,
                        $primeInfo,
                        $targetDate,
                        $bufferMinutes,
                        $defaultBasePrice
                    );
                    break;

                case 'COMPRESS_TIMELINE':
                    $roomFilter = (array) ($params['room_ids'] ?? []);
                    $draftShowtimes = $this->handleCompressTimeline(
                        $sourceShowtimes,
                        $rooms,
                        $movies,
                        $bufferMinutes,
                        $staggeringGap,
                        $openingTime,
                        $closingTime,
                        $targetDate,
                        $primeInfo,
                        $defaultBasePrice,
                        $roomFilter
                    );
                    break;

                case 'SHIFT_SHOWTIMES':
                    $shift = (int) ($params['shift_minutes'] ?? 30);
                    $roomFilter = (array) ($params['room_ids'] ?? []);
                    $movieFilter = (array) ($params['movie_ids'] ?? []);
                    $draftShowtimes = $this->handleShiftShowtimes(
                        $sourceShowtimes,
                        $shift,
                        $primeInfo,
                        $roomFilter,
                        $movieFilter
                    );
                    break;

                case 'SWAP_OR_ASSIGN_MOVIE':
                    $targetMovie = $params['target_movie_id'] ?? ($params['movie_id'] ?? null);
                    $sourceMovie = $params['source_movie_id'] ?? null;
                    $roomFilter = (array) ($params['room_ids'] ?? []);
                    $timeFrom = $params['time_from'] ?? null;
                    $timeTo = $params['time_to'] ?? null;
                    $draftShowtimes = $this->handleSwapOrAssignMovie(
                        $sourceShowtimes,
                        $targetMovie,
                        $movies,
                        $rooms,
                        $primeInfo,
                        $roomFilter,
                        $sourceMovie,
                        $timeFrom,
                        $timeTo
                    );
                    break;

                case 'CUSTOM_SCHEDULE_SPEC':
                case 'GENERATE_FULL_DAY':
                default:
                    $rawList = $params['showtimes'] ?? ($response['showtimes'] ?? []);
                    if (!empty($rawList)) {
                        $draftShowtimes = $this->parseCustomShowtimesSpec(
                            $rawList,
                            $movies,
                            $rooms,
                            $targetDate,
                            $primeInfo,
                            $bufferMinutes,
                            $defaultBasePrice
                        );
                    } else {
                        $draftShowtimes = $sourceShowtimes;
                    }
                    break;
            }

            if ($action !== 'DELETE_SHOWTIMES' && $action !== 'REMOVE_SHOWTIMES' && empty($draftShowtimes) && !empty($sourceShowtimes)) {
                $draftShowtimes = $sourceShowtimes;
            }

            return [
                'draft_showtimes' => $draftShowtimes,
                'explanation'     => $explanation,
                'thinking_steps'  => $thinkingSteps,
            ];
        } catch (\Throwable $e) {
            Log::error("AI Copilot Provider Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            throw new \Exception("AI Provider không thể xử lý yêu cầu: " . $e->getMessage() . ". Vui lòng kiểm tra lại cấu hình API Key, Model AI hoặc đường truyền mạng.");
        }
    }

    /**
     * Chuyển đổi và chuẩn hóa mảng suất chiếu sinh trực tiếp từ LLM (CUSTOM_SCHEDULE_SPEC)
     */
    private function parseCustomShowtimesSpec(
        array $rawList,
        $movies,
        $rooms,
        string $targetDate,
        array $primeInfo,
        int $bufferMinutes,
        float $defaultBasePrice
    ): array {
        $moviesById = $movies->keyBy('movie_id');
        $roomsById = $rooms->keyBy('room_id');
        $draftShowtimes = [];

        foreach ($rawList as $idx => $st) {
            $mIdentifier = $st['movie_id'] ?? ($st['movie_title'] ?? ($st['title'] ?? null));
            $rIdentifier = $st['room_id'] ?? ($st['room_name'] ?? null);

            $movie = null;
            if (is_numeric($mIdentifier) && $moviesById->has((int) $mIdentifier)) {
                $movie = $moviesById->get((int) $mIdentifier);
            }
            if (!$movie && is_string($mIdentifier)) {
                $movie = $movies->first(fn ($m) => mb_stripos($m->title, (string) $mIdentifier) !== false);
            }
            if (!$movie) {
                $movie = $movies->first();
            }

            $room = null;
            if (is_numeric($rIdentifier) && $roomsById->has((int) $rIdentifier)) {
                $room = $roomsById->get((int) $rIdentifier);
            }
            if (!$room && is_string($rIdentifier)) {
                $room = $rooms->first(fn ($r) => mb_stripos($r->room_name, (string) $rIdentifier) !== false);
            }
            if (!$room) {
                $room = $rooms->first();
            }

            if (!$movie || !$room) continue;

            $startRaw = $st['showtime_start'] ?? ($st['start_time'] ?? ($st['start'] ?? '09:00'));
            $startStr = strlen($startRaw) <= 5 ? "{$targetDate} {$startRaw}:00" : $startRaw;
            $start = Carbon::parse($startStr);

            $duration = (int) ($movie->duration ?: 120);

            if (!empty($st['showtime_end'])) {
                $endRaw = $st['showtime_end'];
                $endStr = strlen($endRaw) <= 5 ? "{$targetDate} {$endRaw}:00" : $endRaw;
                $end = Carbon::parse($endStr);
            } else {
                $end = (clone $start)->addMinutes($duration);
            }

            $timeStr = $start->format('H:i');
            $isPrime = ($timeStr >= ($primeInfo['time_from'] ?? '18:00') && $timeStr <= ($primeInfo['time_to'] ?? '22:30'));
            $calculatedRoomBasePrice = $this->calculateRoomBasePrice($room, $defaultBasePrice);

            $draftShowtimes[] = [
                'temp_id'        => "draft_ai_{$room->room_id}_" . $start->format('Hi') . "_{$idx}",
                'movie_id'       => $movie->movie_id,
                'movie_title'    => $movie->title,
                'movie_poster'   => $movie->poster_path,
                'duration'       => $duration,
                'room_id'        => $room->room_id,
                'room_name'      => $room->room_name,
                'room_type'      => $room->room_type,
                'room_capacity'  => $room->total_seats,
                'showtime_start' => $start->format('Y-m-d H:i:s'),
                'showtime_end'   => $end->format('Y-m-d H:i:s'),
                'base_price'     => (float) ($st['base_price'] ?? $calculatedRoomBasePrice),
                'buffer_minutes' => $bufferMinutes,
                'is_prime_time'  => $isPrime,
            ];
        }

        usort($draftShowtimes, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));
        return $draftShowtimes;
    }

    /**
     * Tính toán dự báo tài chính và độ phủ khung giờ vàng cho bản nháp
     */
    private function calculateDraftFinancials(array $draftShowtimes, $rooms, array $primeInfo, string $targetDate): array
    {
        $totalSeatsCapacity = 0;
        $primeTimeShowtimesCount = 0;
        $totalEstimatedRevenue = 0.0;

        $roomsById = $rooms->keyBy('room_id');
        $primeModifier = ($primeInfo['is_today_prime'] ?? false) ? (float) ($primeInfo['modifier_value'] ?? 0) : 0.0;

        foreach ($draftShowtimes as $st) {
            $roomId = $st['room_id'];
            $room = $roomsById->get($roomId);
            $capacity = $room ? (int) $room->total_seats : 100;
            $totalSeatsCapacity += $capacity;

            $basePrice = (float) ($st['base_price'] ?? 100000.0);
            $isPrime = $st['is_prime_time'] ?? false;
            if ($isPrime) {
                $primeTimeShowtimesCount++;
            }

            $avgSeatSurcharge = 6000.0;
            $effectivePrimeAdd = $isPrime ? $primeModifier : 0.0;
            $avgTicketPrice = $basePrice + $avgSeatSurcharge + $effectivePrimeAdd;

            $assumedOccupancy = $isPrime ? 0.70 : 0.45;
            $showtimeRevenue = $capacity * $assumedOccupancy * $avgTicketPrice;
            $totalEstimatedRevenue += $showtimeRevenue;
        }

        $totalCount = count($draftShowtimes);
        $primeCoveragePercent = $totalCount > 0 ? round(($primeTimeShowtimesCount / $totalCount) * 100, 1) : 0;

        return [
            'estimated_total_capacity'     => $totalSeatsCapacity,
            'estimated_expected_revenue'   => (int) round($totalEstimatedRevenue),
            'prime_time_showtimes_count'   => $primeTimeShowtimesCount,
            'prime_time_coverage_percent'  => $primeCoveragePercent,
        ];
    }

    private function toJsonPretty(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

