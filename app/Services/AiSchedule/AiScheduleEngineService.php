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
     * Sinh / Tinh chỉnh bản nháp lịch chiếu (Hỗ trợ Multi-turn Copilot, Scope Protection & Rich Context)
     */
    public function generateDraft(
        int $cinemaId,
        string $targetDate,
        string $mode = 'preset',
        ?string $strategyId = 'prime_time_boost',
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

        // 2. Lấy danh sách phim kèm Rich Metadata (doanh số 7 ngày gần nhất, thể loại, độ tuổi)
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

        $draftShowtimes = [];
        $aiExplanation = '';
        $isRefinement = !empty($currentDraftShowtimes);

        if ($mode === 'prompt' && !empty($userPrompt)) {
            // ── CHẾ ĐỘ COPILOT PROMPT (Hỗ trợ Multi-turn & Refinement) ──
            $llmResult = $this->solvePromptWithLlm(
                $config,
                $cinema,
                $rooms,
                $movies,
                $targetDate,
                $userPrompt,
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
        } else {
            // ── CHẾ ĐỘ CHIẾN LƯỢC MẪU (Fast Heuristic Solver) ──
            $ruleResult = $this->solvePresetSchedule(
                $strategyId ?: 'prime_time_boost',
                $cinema,
                $rooms,
                $movies,
                $targetDate,
                $existingShowtimes,
                $currentDraftShowtimes,
                $scheduleMode,
                $primeInfo,
                $bufferMinutes,
                $staggeringGap,
                $openingTime,
                $closingTime,
                $defaultBasePrice,
                $selectedRoomIds
            );

            $draftShowtimes = $ruleResult['draft_showtimes'];
            $aiExplanation = $ruleResult['explanation'];
        }

        // Tự động chữa lành và căn chỉnh dòng thời gian (Auto-Heal & Collision Free)
        $anchorShowtimes = ($scheduleMode === 'replace_all' || $mode === 'prompt')
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
                'strategy_id'              => $strategyId,
                'mode'                     => $mode,
                'is_refinement'            => $isRefinement,
                'schedule_mode'            => $scheduleMode,
                'target_date'              => $targetDate,
                'cinema_id'                => $cinemaId,
                'cinema_name'              => $cinema->cinema_name,
                'prime_time_info'          => $primeInfo,
                'strategy_explanation'     => $aiExplanation,
            ]),
            'draft_showtimes'    => $draftShowtimes,
            'existing_showtimes' => $existingShowtimes,
            'validation'         => $validation,
        ];
    }

    /**
     * Thuật toán Solver Rule-Based xếp lịch tối ưu (Xoay tua phim công bằng & phân bổ theo khung giờ)
     */
    private function solvePresetSchedule(
        string $strategyId,
        Cinema $cinema,
        $rooms,
        $movies,
        string $targetDate,
        array $existingShowtimes,
        array $currentDraftShowtimes,
        string $scheduleMode,
        array $primeInfo,
        int $bufferMinutes,
        int $staggeringGap,
        string $openingTime,
        string $closingTime,
        float $defaultBasePrice,
        array $selectedRoomIds = []
    ): array {
        $draftShowtimes = [];
        $primeStartStr = $primeInfo['time_from'] ?? '18:00';
        $primeEndStr = $primeInfo['time_to'] ?? '22:30';

        // Phân nhóm phim theo thể loại & độ hot
        $familyMovies = $movies->filter(function ($m) {
            $genres = $m->genres->pluck('name')->map('strtolower')->toArray();
            return in_array('hoạt hình', $genres) || in_array('animation', $genres) || in_array('gia đình', $genres) || in_array('family', $genres);
        })->values();

        $horrorMovies = $movies->filter(function ($m) {
            $genres = $m->genres->pluck('name')->map('strtolower')->toArray();
            $age = strtoupper($m->age_rating ?? '');
            return in_array('kinh dị', $genres) || in_array('horror', $genres) || in_array('giật gân', $genres) || in_array('thriller', $genres) || in_array($age, ['T18', 'C18', '18+']);
        })->values();

        $blockbusterMovies = $movies->sortByDesc('popularity')->values();
        $allMoviesList = $movies->values();

        $movieRotationIndex = 0;
        $roomIndex = 0;

        foreach ($rooms as $room) {
            if (!empty($selectedRoomIds) && !in_array($room->room_id, $selectedRoomIds)) {
                continue;
            }

            $roomBasePrice = $this->calculateRoomBasePrice($room, $defaultBasePrice);
            $staggerOffset = ($roomIndex * $staggeringGap) % 60;
            $openCarbon = Carbon::parse("{$targetDate} {$openingTime}:00")->addMinutes($staggerOffset);
            $closeCarbon = Carbon::parse("{$targetDate} {$closingTime}:00");

            $roomExisting = array_filter($existingShowtimes, fn ($st) => (int) $st['room_id'] === (int) $room->room_id);
            usort($roomExisting, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));

            if ($scheduleMode === 'replace_all') {
                $roomExisting = array_filter($roomExisting, fn ($st) => !empty($st['is_locked']));
            } elseif ($scheduleMode === 'optimize') {
                // Optimize mode: keep locked showtimes and showtimes with booked tickets, replace empty showtimes
                $roomExisting = array_filter($roomExisting, fn ($st) => !empty($st['is_locked']) || (int) ($st['booked_seats'] ?? 0) > 0);
            }

            // Tìm các khoảng thời gian trống (Free Time Windows)
            $freeIntervals = [];
            $pointer = clone $openCarbon;

            foreach ($roomExisting as $ex) {
                $exStart = Carbon::parse($ex['showtime_start']);
                $exEnd = Carbon::parse($ex['showtime_end'])->addMinutes($bufferMinutes);

                if ($exStart->greaterThan($pointer)) {
                    $freeIntervals[] = ['start' => clone $pointer, 'end' => clone $exStart];
                }
                if ($exEnd->greaterThan($pointer)) {
                    $pointer = clone $exEnd;
                }
            }

            if ($closeCarbon->greaterThan($pointer)) {
                $freeIntervals[] = ['start' => clone $pointer, 'end' => clone $closeCarbon];
            }

            // Xếp phim vào từng khoảng trống
            foreach ($freeIntervals as $interval) {
                $slotPointer = clone $interval['start'];
                $slotEnd = clone $interval['end'];

                while ($slotPointer->lessThan($slotEnd)) {
                    $timeOfDayStr = $slotPointer->format('H:i');
                    $hour = $slotPointer->hour;
                    $isPrimeTime = ($timeOfDayStr >= $primeStartStr && $timeOfDayStr <= $primeEndStr);

                    $selectedMovie = null;

                    // 1. Phim sáng & trưa (08:30 - 15:30): Ưu tiên gia đình/hoạt hình nếu có
                    if ($hour < 16 && $familyMovies->isNotEmpty()) {
                        $selectedMovie = $familyMovies[$movieRotationIndex % $familyMovies->count()];
                    }
                    // 2. Khung Giờ Vàng hoặc Phòng IMAX: Ưu tiên bom tấn top đầu
                    elseif ($isPrimeTime || $strategyId === 'prime_time_boost' || in_array($room->screen_type, ['imax_laser', 'screenx'])) {
                        $selectedMovie = $blockbusterMovies->first() ?: $allMoviesList->first();
                    }
                    // 3. Suất đêm muộn (sau 21:30): Ưu tiên phim kinh dị / 18+
                    elseif ($hour >= 21 && $horrorMovies->isNotEmpty()) {
                        $selectedMovie = $horrorMovies[$movieRotationIndex % $horrorMovies->count()];
                    }
                    // 4. Xoay tua đều danh mục phim
                    else {
                        $selectedMovie = $allMoviesList[$movieRotationIndex % $allMoviesList->count()];
                    }

                    $movieRotationIndex++;

                    $duration = (int) ($selectedMovie->duration ?: 120);
                    $showtimeStart = clone $slotPointer;
                    $showtimeEnd = (clone $showtimeStart)->addMinutes($duration);

                    if ($showtimeEnd->greaterThan($slotEnd)) {
                        break;
                    }

                    $tempId = "draft_" . $room->room_id . "_" . $showtimeStart->format('Hi');
                    $draftShowtimes[] = [
                        'temp_id'        => $tempId,
                        'movie_id'       => $selectedMovie->movie_id,
                        'movie_title'    => $selectedMovie->title,
                        'movie_poster'   => $selectedMovie->poster_path,
                        'duration'       => $duration,
                        'room_id'        => $room->room_id,
                        'room_name'      => $room->room_name,
                        'room_type'      => $room->room_type,
                        'room_capacity'  => $room->total_seats,
                        'showtime_start' => $showtimeStart->format('Y-m-d H:i:s'),
                        'showtime_end'   => $showtimeEnd->format('Y-m-d H:i:s'),
                        'base_price'     => $roomBasePrice,
                        'buffer_minutes' => $bufferMinutes,
                        'is_prime_time'  => $isPrimeTime,
                    ];

                    $slotPointer = (clone $showtimeEnd)->addMinutes($bufferMinutes);
                }
            }

            $roomIndex++;
        }

        $draftCount = count($draftShowtimes);
        if ($draftCount > 0) {
            $explanation = "Đã xếp lịch thông minh với {$draftCount} suất chiếu (Xoay tua phim đều, ưu tiên hoạt hình buổi sáng, bom tấn giờ vàng và phim đêm).";
        } elseif (!empty($existingShowtimes) && $scheduleMode === 'smart_fill') {
            $explanation = "Ngày {$targetDate} đã kín lịch chiếu ở tất cả các phòng (" . count($existingShowtimes) . " suất hiện có). Nếu bạn muốn xếp lại lịch toàn bộ ngày, vui lòng chọn mục tiêu 'Tạo mới từ đầu (Ghi đè)'.";
        } else {
            $explanation = "Không tìm thấy khoảng thời gian trống khả dụng để xếp thêm suất chiếu theo bộ lọc hiện tại.";
        }

        return [
            'draft_showtimes' => $draftShowtimes,
            'explanation'     => $explanation,
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
     */
    private function handleDeleteShowtimes(
        array $sourceShowtimes,
        $movies,
        array $roomIdsFilter = [],
        $movieIdentifier = null,
        ?string $timeSlot = null,
        ?string $timeFrom = null,
        ?string $timeTo = null,
        bool $unbookedOnly = true
    ): array {
        $resolved = [];
        $deletedCount = 0;
        $protectedCount = 0;

        foreach ($sourceShowtimes as $st) {
            $rId = (int) $st['room_id'];
            $mId = (int) $st['movie_id'];
            $bookedSeats = (int) ($st['booked_seats'] ?? 0);
            $isLocked = !empty($st['is_locked']);

            $start = Carbon::parse($st['showtime_start']);
            $timeStr = $start->format('H:i');

            // Kiểm tra bộ lọc phòng
            $isRoomMatch = empty($roomIdsFilter) || in_array($rId, $roomIdsFilter);
            
            // Kiểm tra bộ lọc giờ
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

            // Kiểm tra bộ lọc phim
            $isMovieMatch = true;
            if ($movieIdentifier) {
                if (is_numeric($movieIdentifier) && $mId !== (int) $movieIdentifier) {
                    $isMovieMatch = false;
                } elseif (is_string($movieIdentifier) && mb_stripos($st['movie_title'] ?? '', $movieIdentifier) === false) {
                    $isMovieMatch = false;
                }
            }

            // Nếu khớp điều kiện xóa
            if ($isRoomMatch && $isTimeMatch && $isMovieMatch) {
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

            $sourceCount = count($sourceShowtimes);
            $roomsJson = $this->toJsonPretty($roomsData);
            $moviesJson = $this->toJsonPretty($moviesData);

            $systemPrompt = <<<PROMPT
Bạn là Trợ Lý AI Điều Hành Rạp Chiếu Phim (CineDot AI Schedule Operator Agent).
Nhiệm vụ của bạn là đọc yêu cầu của Admin và CHỌN HÀNH ĐỘNG (ACTION) phù hợp nhất để thực thi:

=== CÁC ACTION TOOLS KHẢ DỤNG ===
1. `COMPRESS_TIMELINE`: Kéo toàn bộ hoặc một số phòng chiếu sát nhau, khít giờ, xóa khoảng trống chờ thừa (Khớp với: 'sát nhau', 'liên tục', 'nối tiếp', 'rút ngắn thời gian chờ', 'khít giờ', 'nén giờ').
   Params:
   {
     "action": "COMPRESS_TIMELINE",
     "params": {
       "room_ids": [] // để trống nếu áp dụng toàn bộ phòng, hoặc mảng [1, 2] nếu chỉ định phòng
     },
     "explanation": "Đã sắp xếp toàn bộ các suất chiếu nối tiếp liên tục (sát nhau) theo đúng thời gian dọn phòng {$bufferMinutes} phút."
   }

2. `SHIFT_SHOWTIMES`: Dời thời gian chiếu tiến hoặc lùi X phút (Khớp với: 'dời 30 phút', 'lùi 15p', 'đẩy sớm 20p').
   Params:
   {
     "action": "SHIFT_SHOWTIMES",
     "params": {
       "shift_minutes": 30, // số phút (dương = lùi giờ, âm = đẩy sớm)
       "room_ids": [] // mảng phòng áp dụng (để trống nếu tất cả)
     },
     "explanation": "Đã dời giờ các suất chiếu theo yêu cầu."
   }

3. `SWAP_OR_ASSIGN_MOVIE`: Đổi phim hoặc gán phim vào phòng/khung giờ chỉ định (Khớp với: 'đổi Người Nhện sang phòng 1', 'chiếu phim kinh dị sau 21h').
   Params:
   {
     "action": "SWAP_OR_ASSIGN_MOVIE",
     "params": {
       "target_movie_id": 969681, // hoặc tên phim
       "room_ids": [1],
       "time_from": "20:00"
     },
     "explanation": "Đã đổi phim sang phòng chỉ định."
   }

4. `DELETE_SHOWTIMES`: Xóa bớt các suất chiếu thỏa mãn điều kiện theo phòng, phim, hoặc khung giờ (Khớp với: 'xóa suất sáng', 'xóa phim X', 'xóa phòng 1 sau 22h', 'bỏ bớt suất vắng khách', 'xóa bớt suất', 'giảm bớt lịch').
   Params:
   {
     "action": "DELETE_SHOWTIMES",
     "params": {
       "room_ids": [], // mảng phòng cần xóa (để trống nếu tất cả)
       "movie_id": null, // hoặc tên phim cần xóa
       "time_slot": "morning", // 'morning' | 'afternoon' | 'evening' | 'night' | null
       "time_from": null, // ví dụ '08:30'
       "time_to": null // ví dụ '12:00'
     },
     "explanation": "Đã xóa bớt các suất chiếu thỏa mãn điều kiện (bảo vệ an toàn các suất đã có vé đặt)."
   }

5. `GENERATE_FULL_DAY`: Tạo mới hoàn toàn lịch chiếu cho cả ngày từ 08:30 đến 23:30 theo chiến lược (Khớp với: 'tạo lịch mới từ đầu', 'xếp lịch tối ưu giờ vàng', 'tạo lại toàn bộ ngày').
   Params:
   {
     "action": "GENERATE_FULL_DAY",
     "params": {
       "strategy_id": "prime_time_boost" // prime_time_boost | max_showtimes | family_weekend | balanced_catalog
     },
     "explanation": "Đã lập lịch chiếu mới toàn diện theo chiến lược tối ưu."
   }

6. `CUSTOM_SCHEDULE_SPEC`: Chỉ khi Admin liệt kê danh sách cụ thể từng suất chiếu.
   Params:
   {
     "action": "CUSTOM_SCHEDULE_SPEC",
     "params": {
       "showtimes": [
         { "movie_id": 1, "room_id": 1, "showtime_start": "{$targetDate} 09:00:00", "showtime_end": "{$targetDate} 11:00:00" }
       ]
     },
     "explanation": "..."
   }

=== THÔNG SỐ VẬN HÀNH ===
- Rạp: {$cinema->cinema_name} | Ngày: {$targetDate} | Giờ mở/đóng: {$openingTime} - {$closingTime} | Buffer dọn phòng: {$bufferMinutes}p
- Số suất chiếu hiện có trên màn hình: {$sourceCount} suất

=== DANH SÁCH PHÒNG ===
{$roomsJson}

=== DANH SÁCH PHIM ===
{$moviesJson}

BẮT BUỘC TRẢ VỀ JSON THUẦN TÚY KHÔNG MARKDOWN DẠNG:
{
  "action": "COMPRESS_TIMELINE" | "SHIFT_SHOWTIMES" | "SWAP_OR_ASSIGN_MOVIE" | "DELETE_SHOWTIMES" | "GENERATE_FULL_DAY" | "CUSTOM_SCHEDULE_SPEC",
  "params": { ... },
  "explanation": "Giải thích hành động đã thực hiện cho Admin"
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

            // Nếu không có source showtimes mà user muốn nén/dời/xóa, tạo baseline trước
            if (empty($sourceShowtimes) && in_array($action, ['COMPRESS_TIMELINE', 'SHIFT_SHOWTIMES', 'SWAP_OR_ASSIGN_MOVIE', 'DELETE_SHOWTIMES'])) {
                $baselineResult = $this->solvePresetSchedule(
                    'prime_time_boost',
                    $cinema,
                    $rooms,
                    $movies,
                    $targetDate,
                    [],
                    [],
                    'replace_all',
                    $primeInfo,
                    $bufferMinutes,
                    $staggeringGap,
                    $openingTime,
                    $closingTime,
                    $defaultBasePrice,
                    $selectedRoomIds
                );
                $sourceShowtimes = $baselineResult['draft_showtimes'];
            }

            // DISPATCH TO DETERMINISTIC ACTION HANDLERS
            $draftShowtimes = [];

            switch ($action) {
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

                case 'DELETE_SHOWTIMES':
                case 'REMOVE_SHOWTIMES':
                    $roomFilter = (array) ($params['room_ids'] ?? []);
                    $movieIdent = $params['movie_id'] ?? ($params['movie_title'] ?? ($params['movie_name'] ?? null));
                    $timeSlot = $params['time_slot'] ?? null;
                    $timeFrom = $params['time_from'] ?? null;
                    $timeTo = $params['time_to'] ?? null;
                    $delResult = $this->handleDeleteShowtimes(
                        $sourceShowtimes,
                        $movies,
                        $roomFilter,
                        $movieIdent,
                        $timeSlot,
                        $timeFrom,
                        $timeTo,
                        true
                    );
                    $draftShowtimes = $delResult['draft_showtimes'];
                    $explanation = "Đã xóa bớt {$delResult['deleted_count']} suất chiếu theo yêu cầu.";
                    if ($delResult['protected_count'] > 0) {
                        $explanation .= " (Đã bảo vệ {$delResult['protected_count']} suất chiếu có khách đã đặt vé).";
                    }
                    break;

                case 'GENERATE_FULL_DAY':
                    $strategy = $params['strategy_id'] ?? 'prime_time_boost';
                    $fullResult = $this->solvePresetSchedule(
                        $strategy,
                        $cinema,
                        $rooms,
                        $movies,
                        $targetDate,
                        [],
                        [],
                        'replace_all',
                        $primeInfo,
                        $bufferMinutes,
                        $staggeringGap,
                        $openingTime,
                        $closingTime,
                        $defaultBasePrice,
                        $selectedRoomIds
                    );
                    $draftShowtimes = $fullResult['draft_showtimes'];
                    break;

                case 'CUSTOM_SCHEDULE_SPEC':
                default:
                    $rawList = $params['showtimes'] ?? ($response['showtimes'] ?? []);
                    if (!empty($rawList)) {
                        $moviesById = $movies->keyBy('movie_id');
                        $roomsById = $rooms->keyBy('room_id');

                        foreach ($rawList as $idx => $st) {
                            $mId = (int) ($st['movie_id'] ?? 0);
                            $rId = (int) ($st['room_id'] ?? 0);
                            $movie = $moviesById->get($mId);
                            $room = $roomsById->get($rId);
                            if (!$movie || !$room) continue;

                            $start = Carbon::parse($st['showtime_start']);
                            $duration = (int) ($movie->duration ?: 120);
                            $end = isset($st['showtime_end']) ? Carbon::parse($st['showtime_end']) : (clone $start)->addMinutes($duration);
                            $timeStr = $start->format('H:i');
                            $isPrime = ($timeStr >= ($primeInfo['time_from'] ?? '18:00') && $timeStr <= ($primeInfo['time_to'] ?? '22:30'));
                            $calculatedRoomBasePrice = $this->calculateRoomBasePrice($room, $defaultBasePrice);

                            $draftShowtimes[] = [
                                'temp_id'        => "draft_ai_{$rId}_" . $start->format('Hi') . "_{$idx}",
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
                    } else {
                        // Fallback nén lịch nếu có từ khóa 'sát' hoặc 'khít'
                        if (mb_stripos($userPrompt, 'sát') !== false || mb_stripos($userPrompt, 'khít') !== false || mb_stripos($userPrompt, 'nén') !== false) {
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
                                []
                            );
                        } else {
                            $draftShowtimes = $sourceShowtimes;
                        }
                    }
                    break;
            }

            if (empty($draftShowtimes) && !empty($sourceShowtimes)) {
                $draftShowtimes = $sourceShowtimes;
            }

            return [
                'draft_showtimes' => $draftShowtimes,
                'explanation'     => $explanation,
            ];
        } catch (\Throwable $e) {
            Log::warning("AI Copilot fallback triggered: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());

            // AUTO HEURISTIC FALLBACK
            if (mb_stripos($userPrompt, 'sát') !== false || mb_stripos($userPrompt, 'khít') !== false || mb_stripos($userPrompt, 'nén') !== false) {
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
                    []
                );
                $explanation = "Đã tự động nén toàn bộ các suất chiếu sát nhau theo 15 phút dọn phòng (Smart Solver Engine).";
            } else {
                $solverResult = $this->solvePresetSchedule(
                    'prime_time_boost',
                    $cinema,
                    $rooms,
                    $movies,
                    $targetDate,
                    $existingShowtimes,
                    $currentDraftShowtimes,
                    $scheduleMode,
                    $primeInfo,
                    $bufferMinutes,
                    $staggeringGap,
                    $openingTime,
                    $closingTime,
                    $defaultBasePrice,
                    $selectedRoomIds
                );
                $draftShowtimes = $solverResult['draft_showtimes'];
                $explanation = "AI Copilot đã áp dụng Bộ giải thuật thông minh (Smart Solver): " . $solverResult['explanation'] . " (Ghi chú: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . ")";
            }

            return [
                'draft_showtimes' => $draftShowtimes,
                'explanation'     => $explanation,
            ];
        }
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

