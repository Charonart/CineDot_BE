<?php

namespace App\Services\AiSchedule;

use App\Models\AiScheduleConfig;
use App\Models\Cinema;
use App\Models\Movie;
use App\Models\PricingRule;
use App\Models\Room;
use App\Models\SeatType;
use App\Models\Showtime;
use App\Services\AiSchedule\AiProviderFactory;
use App\Services\AiSchedule\ScheduleConstraintValidator;
use Carbon\Carbon;
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
            // Fallback phụ thu theo chuẩn công nghệ phòng nếu chưa cấu hình riêng
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

        // Fallback về cấu hình custom của rạp
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
     * Sinh bản nháp lịch chiếu (Draft Showtimes) với đầy đủ Context và 3 Chế Độ
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
        array $overrideConfig = []
    ): array {
        $config = AiScheduleConfig::getEffectiveConfig($cinemaId);
        $cinema = Cinema::with('rooms')->findOrFail($cinemaId);

        $bufferMinutes = (int) ($overrideConfig['buffer_minutes'] ?? $config->default_buffer_minutes ?? 15);
        $staggeringGap = (int) ($overrideConfig['staggering_gap_minutes'] ?? $config->staggering_gap_minutes ?? 15);
        $openingTime = $overrideConfig['opening_time'] ?? $config->opening_time ?? '08:30';
        $closingTime = $overrideConfig['closing_time'] ?? $config->closing_time ?? '23:30';
        $defaultBasePrice = (float) ($overrideConfig['default_base_price'] ?? $config->default_base_price ?? 100000.0);

        $primeInfo = $this->getEffectivePrimeTime($config, $targetDate);

        // Lấy danh sách phòng
        $roomsQuery = Room::where('cinema_id', $cinemaId)->where('is_active', true);
        if (!empty($selectedRoomIds)) {
            $roomsQuery->whereIn('room_id', $selectedRoomIds);
        }
        $rooms = $roomsQuery->orderBy('total_seats', 'desc')->get();
        if ($rooms->isEmpty()) {
            throw new \Exception("Không tìm thấy phòng chiếu khả dụng tại rạp {$cinema->cinema_name}.");
        }

        // Lấy danh sách phim đang chiếu
        $moviesQuery = Movie::where('status', 'NOW_SHOWING')->orWhere('status', 'now_showing');
        if (!empty($selectedMovieIds)) {
            $moviesQuery->whereIn('movie_id', $selectedMovieIds);
        }
        $movies = $moviesQuery->with('genres')->orderBy('popularity', 'desc')->get();
        if ($movies->isEmpty()) {
            $movies = Movie::with('genres')->orderBy('popularity', 'desc')->limit(10)->get();
        }

        // 1. TRUY VẤN ĐỘNG 100% SUẤT CHIẾU HIỆN CÓ TỪ DATABASE KÈM SỐ VÉ ĐÃ BÁN
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
                'movie_id'       => $ex->movie_id,
                'movie_title'    => $ex->movie?->title ?? 'Phim',
                'showtime_start' => $ex->showtime_start->format('Y-m-d H:i:s'),
                'showtime_end'   => $ex->showtime_end->format('Y-m-d H:i:s'),
                'start_time'     => $ex->showtime_start->format('H:i'),
                'end_time'       => $ex->showtime_end->format('H:i'),
                'base_price'     => (float) $ex->base_price,
                'booked_seats'   => $bookedCount,
                'is_locked'      => $bookedCount > 0, // Đã có vé -> Khóa cứng 100%
                'buffer_minutes' => $bufferMinutes,
            ];
        }

        $draftShowtimes = [];
        $aiExplanation = '';

        if ($mode === 'prompt' && !empty($userPrompt)) {
            // ── CHẾ ĐỘ 1: GỌI AI COPILOT VỚI CONTEXT ĐỘNG 100% TỪ DB ──
            $llmResult = $this->solvePromptWithLlm(
                $config,
                $cinema,
                $rooms,
                $movies,
                $targetDate,
                $userPrompt,
                $existingShowtimes,
                $scheduleMode,
                $primeInfo,
                $bufferMinutes,
                $staggeringGap,
                $openingTime,
                $closingTime,
                $defaultBasePrice,
                $overrideConfig
            );

            $draftShowtimes = $llmResult['draft_showtimes'];
            $aiExplanation = $llmResult['explanation'] ?? 'AI Copilot đã lập lịch theo yêu cầu ngôn ngữ tự nhiên của bạn.';
        } else {
            // ── CHẾ ĐỘ 2: SINH THEO CHIẾN LƯỢC MẪU (Fast Solver Chống Xung Đột) ──
            $ruleResult = $this->solvePresetSchedule(
                $strategyId ?: 'prime_time_boost',
                $cinema,
                $rooms,
                $movies,
                $targetDate,
                $existingShowtimes,
                $scheduleMode,
                $primeInfo,
                $bufferMinutes,
                $staggeringGap,
                $openingTime,
                $closingTime,
                $defaultBasePrice
            );

            $draftShowtimes = $ruleResult['draft_showtimes'];
            $aiExplanation = $ruleResult['explanation'];
        }

        // Kiểm tra xung đột & cảnh báo
        $validationContext = ($scheduleMode === 'replace_all')
            ? array_filter($existingShowtimes, fn ($st) => $st['is_locked'])
            : $existingShowtimes;

        $validation = ScheduleConstraintValidator::validateDraftBatch(
            $draftShowtimes,
            $validationContext,
            $bufferMinutes,
            $staggeringGap,
            $openingTime,
            $closingTime
        );

        // Tính toán chỉ số tài chính & vận hành dự kiến
        $financialSummary = $this->calculateDraftFinancials($draftShowtimes, $rooms, $primeInfo, $targetDate);

        return [
            'summary' => array_merge($financialSummary, [
                'total_showtimes'          => count($draftShowtimes),
                'total_rooms_used'         => count(array_unique(array_column($draftShowtimes, 'room_id'))),
                'strategy_id'              => $strategyId,
                'mode'                     => $mode,
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
     * Thuật toán Solver Rule-Based xếp lịch tối ưu (Hỗ trợ Smart Fill Gaps & Phân tầng giá phòng)
     */
    private function solvePresetSchedule(
        string $strategyId,
        Cinema $cinema,
        $rooms,
        $movies,
        string $targetDate,
        array $existingShowtimes,
        string $scheduleMode,
        array $primeInfo,
        int $bufferMinutes,
        int $staggeringGap,
        string $openingTime,
        string $closingTime,
        float $defaultBasePrice
    ): array {
        $draftShowtimes = [];
        $primeStartStr = $primeInfo['time_from'] ?? '18:00';
        $primeEndStr = $primeInfo['time_to'] ?? '22:30';

        $familyMovies = $movies->filter(function ($m) {
            $genres = $m->genres->pluck('name')->map('strtolower')->toArray();
            return in_array('hoạt hình', $genres) || in_array('animation', $genres) || in_array('gia đình', $genres) || in_array('family', $genres);
        });

        $blockbusterMovies = $movies->sortByDesc('popularity')->values();

        $roomIndex = 0;
        foreach ($rooms as $room) {
            // Giá vé cơ sở tính theo phòng
            $roomBasePrice = $this->calculateRoomBasePrice($room, $defaultBasePrice);

            // Giờ bắt đầu của phòng: Dàn trải staggering gap
            $staggerOffset = ($roomIndex * $staggeringGap) % 60;
            $openCarbon = Carbon::parse("{$targetDate} {$openingTime}:00")->addMinutes($staggerOffset);
            $closeCarbon = Carbon::parse("{$targetDate} {$closingTime}:00");

            // Lấy danh sách suất chiếu của phòng này
            $roomExisting = array_filter($existingShowtimes, fn ($st) => (int) $st['room_id'] === (int) $room->room_id);
            usort($roomExisting, fn ($a, $b) => strcmp($a['showtime_start'], $b['showtime_start']));

            // Nếu là replace_all -> Chỉ giữ lại các suất đã bán vé (is_locked = true)
            if ($scheduleMode === 'replace_all') {
                $roomExisting = array_filter($roomExisting, fn ($st) => $st['is_locked']);
            }

            // Tìm các khoảng thời gian trống (Free Time Slots)
            $freeIntervals = [];
            $pointer = clone $openCarbon;

            foreach ($roomExisting as $ex) {
                $exStart = Carbon::parse($ex['showtime_start'])->subMinutes($bufferMinutes);
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

            // Xếp phim vào các khoảng thời gian trống
            foreach ($freeIntervals as $interval) {
                $slotPointer = clone $interval['start'];
                $slotEnd = clone $interval['end'];

                while ($slotPointer->lessThan($slotEnd)) {
                    $timeOfDayStr = $slotPointer->format('H:i');
                    $isPrimeTime = ($timeOfDayStr >= $primeStartStr && $timeOfDayStr <= $primeEndStr);

                    // Chọn phim phù hợp theo phòng & khung giờ
                    $selectedMovie = null;

                    if ($strategyId === 'family_weekend' && $slotPointer->hour < 16 && $familyMovies->isNotEmpty()) {
                        $selectedMovie = $familyMovies->random();
                    } elseif ($isPrimeTime || $strategyId === 'prime_time_boost' || $room->screen_type === 'imax_laser') {
                        // Phòng IMAX hoặc Giờ vàng ưu tiên bom tấn top đầu
                        $selectedMovie = $blockbusterMovies->first() ?: $movies->first();
                    } elseif ($strategyId === 'balanced_catalog') {
                        $selectedMovie = $movies->random();
                    } else {
                        $selectedMovie = $movies->random();
                    }

                    $duration = (int) ($selectedMovie->duration ?: 120);
                    $showtimeStart = clone $slotPointer;
                    $showtimeEnd = (clone $showtimeStart)->addMinutes($duration);

                    // Nếu suất chiếu vượt quá khoảng trống, dừng khoảng này
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

        $modeText = ($scheduleMode === 'smart_fill')
            ? 'Đã bảo toàn 100% các suất chiếu hiện có và chèn thêm suất mới vào các khoảng thời gian trống.'
            : (($scheduleMode === 'optimize')
                ? 'Đã tối ưu hóa lại các suất chưa có vé và giữ nguyên các suất đã bán vé.'
                : 'Đã tạo mới toàn bộ lịch chiếu theo chiến lược.');

        $explanation = "{$modeText} (Chiến lược: {$strategyId})";

        return [
            'draft_showtimes' => $draftShowtimes,
            'explanation'     => $explanation,
        ];
    }

    /**
     * Điều phối gọi LLM với ĐẦY ĐỦ CONTEXT ĐỘNG 100% TỪ DATABASE
     */
    private function solvePromptWithLlm(
        AiScheduleConfig $config,
        Cinema $cinema,
        $rooms,
        $movies,
        string $targetDate,
        string $userPrompt,
        array $existingShowtimes,
        string $scheduleMode,
        array $primeInfo,
        int $bufferMinutes,
        int $staggeringGap,
        string $openingTime,
        string $closingTime,
        float $defaultBasePrice,
        array $overrideConfig
    ): array {
        $driver = AiProviderFactory::createFromConfig($config, $overrideConfig);

        $roomsData = $rooms->map(fn ($r) => [
            'room_id'          => $r->room_id,
            'room_name'        => $r->room_name,
            'room_type'        => $r->room_type,
            'screen_type'      => $r->screen_type,
            'total_seats'      => $r->total_seats,
            'room_base_price'  => $this->calculateRoomBasePrice($r, $defaultBasePrice),
        ])->toArray();

        $moviesData = $movies->map(fn ($m) => [
            'movie_id'   => $m->movie_id,
            'title'      => $m->title,
            'duration'   => $m->duration ?: 120,
            'popularity' => $m->popularity,
            'genres'     => $m->genres->pluck('name')->toArray(),
        ])->toArray();

        // NẠP DANH SÁCH SUẤT CHIẾU HIỆN CÓ TỪ DATABASE VÀO PROMPT
        $existingShowtimesData = array_map(function ($st) {
            return [
                'showtime_id'  => $st['showtime_id'],
                'room_id'      => $st['room_id'],
                'room_name'    => $st['room_name'],
                'movie_id'     => $st['movie_id'],
                'movie_title'  => $st['movie_title'],
                'start_time'   => $st['start_time'],
                'end_time'     => $st['end_time'],
                'booked_seats' => $st['booked_seats'],
                'is_locked'    => $st['is_locked'],
            ];
        }, $existingShowtimes);

        $systemPrompt = <<<PROMPT
Bạn là Trợ lý AI Xếp Lịch Chiếu Rạp Phim chuyên nghiệp của hệ thống CineDot.
Nhiệm vụ của bạn là đọc yêu cầu của Admin, kết hợp danh sách Phim, Phòng chiếu, và CONTEXT CÁC SUẤT CHIẾU ĐANG CÓ THỰC TẾ TRONG DATABASE để tạo ra lịch chiếu tối ưu nhất cho ngày {$targetDate} tại rạp "{$cinema->cinema_name}".

=== THÔNG SỐ VẬN HÀNH RẠP ===
- Ngày chiếu: {$targetDate}
- Giờ mở cửa: {$openingTime}
- Giờ đóng cửa (suất cuối bắt đầu trước): {$closingTime}
- Thời gian dọn phòng/quảng cáo (Buffer): {$bufferMinutes} phút
- Giãn cách giờ bắt đầu giữa các phòng (Staggering Gap): $\ge$ {$staggeringGap} phút
- Khung Giờ Vàng (Pricing Rule): {$primeInfo['time_from']} - {$primeInfo['time_to']}
- Chế độ xếp lịch được chọn: {$scheduleMode}

=== DANH SÁCH PHÒNG CHIẾU & GIÁ CƠ SỞ THEO PHÒNG ===
{$this->toJsonPretty($roomsData)}

=== DANH SÁCH PHIM ĐANG CHIẾU ===
{$this->toJsonPretty($moviesData)}

=== DANH SÁCH SUẤT CHIẾU HIỆN CÓ TRONG DATABASE CỦA NGÀY {$targetDate} ===
{$this->toJsonPretty($existingShowtimesData)}

=== QUY TẮC RÀNG BUỘC THEO CHẾ ĐỘ "{$scheduleMode}" ===
1. Khi schedule_mode là "smart_fill": Các suất chiếu hiện có ở trên PHẢI ĐƯỢC GIỮ NGUYÊN 100%. Bạn CHỈ ĐƯỢC PHÉP xếp các suất chiếu mới vào các khoảng thời gian còn trống của từng phòng (tránh hoàn toàn các khoảng thời gian đã có suất chiếu + {$bufferMinutes} phút dọn phòng).
2. Khi schedule_mode là "optimize": Các suất có is_locked = true (đã bán vé) BẮT BUỘC GIỮ NGUYÊN. Bạn chỉ được phép điều chỉnh dời giờ các suất is_locked = false.
3. Khi schedule_mode là "replace_all": Bạn được phép xếp mới toàn bộ (ngoại trừ các suất có is_locked = true).
4. Tuyệt đối không để 2 suất chiếu trùng giờ nhau trong cùng một phòng chiếu.
5. Định dạng JSON trả về PHẢI chuẩn xác theo cấu trúc sau:
{
  "explanation": "Giải thích chi tiết chiến lược xếp lịch đã áp dụng",
  "showtimes": [
    {
      "movie_id": 1,
      "room_id": 10,
      "showtime_start": "{$targetDate} 09:00:00",
      "showtime_end": "{$targetDate} 11:00:00",
      "base_price": 100000
    }
  ]
}
PROMPT;

        $response = $driver->generateStructuredSchedule($systemPrompt, $userPrompt);

        $parsedShowtimes = $response['showtimes'] ?? [];
        $explanation = $response['explanation'] ?? 'AI đã xử lý yêu cầu xếp lịch thành công.';

        $draftShowtimes = [];
        $moviesById = $movies->keyBy('movie_id');
        $roomsById = $rooms->keyBy('room_id');

        foreach ($parsedShowtimes as $idx => $st) {
            $mId = (int) ($st['movie_id'] ?? 0);
            $rId = (int) ($st['room_id'] ?? 0);
            $movie = $moviesById->get($mId);
            $room = $roomsById->get($rId);

            if (!$movie || !$room) {
                continue;
            }

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

        return [
            'draft_showtimes' => $draftShowtimes,
            'explanation'     => $explanation,
        ];
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

            // Ước tính giá vé trung bình (bao gồm tỷ lệ ghế VIP 40% phụ thu 15k, và giờ vàng)
            $avgSeatSurcharge = 6000.0;
            $effectivePrimeAdd = $isPrime ? $primeModifier : 0.0;
            $avgTicketPrice = $basePrice + $avgSeatSurcharge + $effectivePrimeAdd;

            // Giả định tỷ lệ lấp đầy bình quân: 70% giờ vàng, 45% giờ thường
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
