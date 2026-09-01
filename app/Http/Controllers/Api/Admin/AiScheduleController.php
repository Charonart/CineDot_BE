<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiScheduleConfig;
use App\Models\Cinema;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\AiSchedule\AiProviderFactory;
use App\Services\AiSchedule\AiScheduleEngineService;
use App\Services\AiSchedule\ScheduleConstraintValidator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiScheduleController extends Controller
{
    protected AiScheduleEngineService $engineService;

    public function __construct(AiScheduleEngineService $engineService)
    {
        $this->engineService = $engineService;
    }

    /**
     * 1. Lấy cấu hình rạp & thông tin kết nối AI
     * GET /api/v1/admin/showtimes/ai/config
     */
    public function getConfig(Request $request)
    {
        $cinemaId = $request->has('cinema_id') ? (int) $request->cinema_id : null;
        $config = AiScheduleConfig::getEffectiveConfig($cinemaId);

        $primeInfo = $this->engineService->getEffectivePrimeTime($config);

        $cinemaName = null;
        if ($cinemaId) {
            $cinema = Cinema::find($cinemaId);
            $cinemaName = $cinema?->cinema_name;
        }

        $rawKey = $config->ai_api_key;
        $hasKey = !empty($rawKey);
        $maskedKey = null;
        if ($hasKey) {
            $len = strlen($rawKey);
            $maskedKey = $len > 8 ? substr($rawKey, 0, 4) . '••••••••' . substr($rawKey, -4) : '••••••••';
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                                 => $config->id,
                'cinema_id'                          => $config->cinema_id,
                'cinema_name'                        => $cinemaName,
                'opening_time'                       => $config->opening_time,
                'closing_time'                       => $config->closing_time,
                'default_buffer_minutes'             => (int) $config->default_buffer_minutes,
                'staggering_gap_minutes'             => (int) $config->staggering_gap_minutes,
                'sync_prime_time_from_pricing_rules' => (bool) $config->sync_prime_time_from_pricing_rules,
                'custom_prime_time_start'            => $config->custom_prime_time_start,
                'custom_prime_time_end'              => $config->custom_prime_time_end,
                'default_base_price'                 => (float) $config->default_base_price,
                'ai_provider'                        => $config->ai_provider,
                'ai_model_name'                      => $config->ai_model_name,
                'ai_base_url'                        => $config->ai_base_url,
                'ai_temperature'                     => (float) $config->ai_temperature,
                'ai_timeout_seconds'                 => (int) ($config->ai_timeout_seconds ?? 60),
                'has_custom_api_key'                 => $hasKey,
                'masked_api_key'                     => $maskedKey,
                'effective_prime_time'               => $primeInfo,
            ]
        ]);
    }

    /**
     * 2. Lưu / Cập nhật cấu hình rạp & thông tin kết nối AI
     * PUT /api/v1/admin/showtimes/ai/config
     */
    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'cinema_id'                          => 'nullable|exists:cinemas,cinema_id',
            'opening_time'                       => 'sometimes|string|max:5',
            'closing_time'                       => 'sometimes|string|max:5',
            'default_buffer_minutes'             => 'sometimes|integer|min:0|max:60',
            'staggering_gap_minutes'             => 'sometimes|integer|min:0|max:60',
            'sync_prime_time_from_pricing_rules' => 'sometimes|boolean',
            'custom_prime_time_start'            => 'nullable|string|max:5',
            'custom_prime_time_end'              => 'nullable|string|max:5',
            'default_base_price'                 => 'sometimes|numeric|min:0',
            'ai_provider'                        => 'sometimes|string|max:50',
            'ai_model_name'                      => 'sometimes|string|max:100',
            'ai_api_key'                         => 'nullable|string',
            'ai_base_url'                        => 'nullable|string|max:255',
            'ai_temperature'                     => 'sometimes|numeric|min:0|max:1',
            'ai_timeout_seconds'                 => 'sometimes|integer|min:5|max:600',
        ]);

        $cinemaId = $validated['cinema_id'] ?? null;

        $config = $cinemaId
            ? AiScheduleConfig::firstOrNew(['cinema_id' => $cinemaId])
            : AiScheduleConfig::firstOrNew(['cinema_id' => null]);

        // Mặc định provider là custom nếu không truyền
        if (!isset($validated['ai_provider']) && empty($config->ai_provider)) {
            $config->ai_provider = 'custom';
        }

        // Chỉ cập nhật api_key nếu user có nhập chuỗi mới (không phải masked)
        if (isset($validated['ai_api_key'])) {
            if (str_contains($validated['ai_api_key'], '••••')) {
                unset($validated['ai_api_key']);
            } elseif (trim($validated['ai_api_key']) === '') {
                $validated['ai_api_key'] = null;
            }
        }

        $config->fill($validated);
        $config->save();

        if (!empty($config->ai_api_key) && $cinemaId !== null) {
            $global = AiScheduleConfig::whereNull('cinema_id')->first();
            if (!$global || empty($global->ai_api_key)) {
                AiScheduleConfig::updateOrCreate(
                    ['cinema_id' => null],
                    [
                        'ai_provider'        => $config->ai_provider,
                        'ai_base_url'        => $config->ai_base_url,
                        'ai_model_name'      => $config->ai_model_name,
                        'ai_api_key'         => $config->ai_api_key,
                        'ai_timeout_seconds' => $config->ai_timeout_seconds,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cấu hình rạp và kết nối AI thành công.',
            'data'    => $config
        ]);
    }

    /**
     * 3. Kiểm tra kết nối API Key & Model tức thì
     * POST /api/v1/admin/showtimes/ai/test-connection
     */
    public function testConnection(Request $request)
    {
        @set_time_limit(0);
        $validated = $request->validate([
            'ai_provider'        => 'nullable|string|max:50',
            'ai_api_key'         => 'nullable|string',
            'ai_model_name'      => 'nullable|string',
            'ai_base_url'        => 'nullable|string',
            'ai_timeout_seconds' => 'nullable|integer|min:5|max:600',
        ]);

        $provider = $validated['ai_provider'] ?? 'custom';
        $apiKey = $validated['ai_api_key'] ?? null;
        $model = $validated['ai_model_name'] ?? null;
        $baseUrl = $validated['ai_base_url'] ?? null;
        $timeout = (int) ($validated['ai_timeout_seconds'] ?? 60);

        // Nếu user để trống key hoặc gửi masked key, lấy từ DB config hoặc server env
        if (empty($apiKey) || str_contains($apiKey, '••••')) {
            $config = AiScheduleConfig::first();
            $apiKey = $config?->ai_api_key;
        }

        try {
            $driver = AiProviderFactory::create($provider, $apiKey, $model, $baseUrl, 0.2, $timeout);
            $result = $driver->testConnection();

            return response()->json($result, $result['success'] ? 200 : 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kết nối: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * 4. Danh sách các chiến lược mẫu (Strategy Presets)
     * GET /api/v1/admin/showtimes/ai/strategies
     */
    public function getStrategies()
    {
        $strategies = [
            [
                'id'              => 'prime_time_boost',
                'name'            => 'Tối Ưu Khung Giờ Vàng (Khuyên dùng)',
                'description'     => 'Ưu tiên các phim bom tấn hot nhất vào phòng lớn và khung giờ vàng Pricing Rule (18:00 - 23:00).',
                'icon'            => 'Flame',
                'badge'           => 'Tối Đa Doanh Thu',
                'recommended_for' => 'Thứ 6, Thứ 7, Chủ Nhật và ngày lễ',
            ],
            [
                'id'              => 'max_showtimes',
                'name'            => 'Tối Đa Số Lượng Suất Chiếu',
                'description'     => 'Nén thời gian nghỉ và dàn trải liên tục giữa các phòng để chiếu được số ca tối đa.',
                'icon'            => 'Zap',
                'badge'           => 'Công Suất Cao',
                'recommended_for' => 'Ngày cao điểm tết hoặc kỳ nghỉ lễ',
            ],
            [
                'id'              => 'family_weekend',
                'name'            => 'Gia Đình & Thiếu Nhi',
                'description'     => 'Tập trung đẩy các phim hoạt hình, gia đình vào khung giờ sáng và chiều sớm trước 16h.',
                'icon'            => 'Sparkles',
                'badge'           => 'Gia Đình',
                'recommended_for' => 'Sáng cuối tuần và dịp nghỉ hè',
            ],
            [
                'id'              => 'balanced_catalog',
                'name'            => 'Phân Bổ Đồng Đều Danh Mục Phim',
                'description'     => 'Chia đều số suất chiếu cho tất cả các phim đang phát hành tại rạp.',
                'icon'            => 'Scale',
                'badge'           => 'Đa Dạng',
                'recommended_for' => 'Các ngày thường trong tuần (T2 - T5)',
            ],
        ];

        return response()->json([
            'success' => true,
            'data'    => $strategies
        ]);
    }

    /**
     * 5. Sinh bản nháp lịch chiếu (Draft Showtimes)
     * POST /api/v1/admin/showtimes/ai/generate-draft
     */
    public function generateDraft(Request $request)
    {
        @set_time_limit(0);
        $validated = $request->validate([
            'cinema_id'           => 'required|exists:cinemas,cinema_id',
            'target_date'         => 'required|date_format:Y-m-d',
            'mode'                => 'required|in:preset,prompt',
            'strategy_id'         => 'nullable|string',
            'prompt'              => 'nullable|string',
            'selected_movie_ids'  => 'nullable|array',
            'selected_movie_ids.*'=> 'integer|exists:movies,movie_id',
            'selected_room_ids'   => 'nullable|array',
            'selected_room_ids.*' => 'integer|exists:rooms,room_id',
            'schedule_mode'       => 'nullable|string|in:smart_fill,optimize,replace_all',
            'clean_existing_date' => 'nullable|boolean',
            'override_config'     => 'nullable|array',
        ]);

        try {
            $scheduleMode = $validated['schedule_mode'] ?? ($validated['clean_existing_date'] ? 'replace_all' : 'smart_fill');

            $result = $this->engineService->generateDraft(
                (int) $validated['cinema_id'],
                $validated['target_date'],
                $validated['mode'],
                $validated['strategy_id'] ?? 'prime_time_boost',
                $validated['prompt'] ?? null,
                $validated['selected_movie_ids'] ?? [],
                $validated['selected_room_ids'] ?? [],
                $scheduleMode,
                $validated['override_config'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => "AI đã sinh thành công " . count($result['draft_showtimes']) . " suất chiếu nháp.",
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi sinh lịch chiếu AI: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 6. Kiểm tra xung đột bản nháp sau khi Admin kéo thả chỉnh sửa
     * POST /api/v1/admin/showtimes/ai/validate-draft
     */
    public function validateDraft(Request $request)
    {
        $validated = $request->validate([
            'cinema_id'        => 'required|exists:cinemas,cinema_id',
            'target_date'      => 'required|date_format:Y-m-d',
            'draft_showtimes'  => 'required|array',
        ]);

        $config = AiScheduleConfig::getEffectiveConfig((int) $validated['cinema_id']);

        $validation = ScheduleConstraintValidator::validateDraftBatch(
            $validated['draft_showtimes'],
            [],
            (int) $config->default_buffer_minutes,
            (int) $config->staggering_gap_minutes,
            $config->opening_time,
            $config->closing_time
        );

        return response()->json([
            'success' => true,
            'data'    => $validation
        ]);
    }

    /**
     * 7. Lưu chính thức bản nháp vào Database
     * POST /api/v1/admin/showtimes/ai/apply-draft
     */
    public function applyDraft(Request $request)
    {
        $validated = $request->validate([
            'cinema_id'           => 'required|exists:cinemas,cinema_id',
            'target_date'         => 'required|date_format:Y-m-d',
            'draft_showtimes'     => 'required|array|min:1',
            'clean_existing_date' => 'nullable|boolean',
        ]);

        $cinemaId = (int) $validated['cinema_id'];
        $targetDate = $validated['target_date'];
        $draftShowtimes = $validated['draft_showtimes'];
        $cleanExisting = (bool) ($validated['clean_existing_date'] ?? false);

        $createdShowtimes = [];
        $totalSeatsInserted = 0;

        DB::transaction(function () use ($cinemaId, $targetDate, $draftShowtimes, $cleanExisting, &$createdShowtimes, &$totalSeatsInserted) {
            if ($cleanExisting) {
                // Chỉ xóa những suất chưa có vé đặt
                $oldShowtimes = Showtime::whereDate('showtime_start', $targetDate)
                    ->whereHas('room', fn ($q) => $q->where('cinema_id', $cinemaId))
                    ->get();

                foreach ($oldShowtimes as $old) {
                    $hasBooked = ShowtimeSeat::where('showtime_id', $old->showtime_id)->where('status', 'booked')->exists();
                    if (!$hasBooked) {
                        ShowtimeSeat::where('showtime_id', $old->showtime_id)->delete();
                        $old->delete();
                    }
                }
            }

            // Cache danh sách ghế vật lý theo phòng để insert nhanh
            $roomSeatsCache = [];

            foreach ($draftShowtimes as $draft) {
                $roomId = (int) $draft['room_id'];
                $movieId = (int) $draft['movie_id'];
                $start = Carbon::parse($draft['showtime_start']);
                $end = Carbon::parse($draft['showtime_end']);
                $basePrice = (float) ($draft['base_price'] ?? 100000.0);

                // Tạo Suất Chiếu
                $showtime = Showtime::create([
                    'room_id'        => $roomId,
                    'movie_id'       => $movieId,
                    'showtime_start' => $start,
                    'showtime_end'   => $end,
                    'base_price'     => $basePrice,
                ]);

                // Lấy ghế phòng
                if (!isset($roomSeatsCache[$roomId])) {
                    $roomSeatsCache[$roomId] = Seat::where('room_id', $roomId)->where('is_active', true)->get();
                }

                $seats = $roomSeatsCache[$roomId];
                $seatsToInsert = [];
                foreach ($seats as $s) {
                    $seatsToInsert[] = [
                        'showtime_id' => $showtime->showtime_id,
                        'seat_id'     => $s->seat_id,
                        'status'      => 'available',
                    ];
                }

                if (!empty($seatsToInsert)) {
                    ShowtimeSeat::insert($seatsToInsert);
                    $totalSeatsInserted += count($seatsToInsert);
                }

                $createdShowtimes[] = $showtime;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Đã áp dụng thành công " . count($createdShowtimes) . " suất chiếu và khởi tạo {$totalSeatsInserted} ghế.",
            'data'    => [
                'showtimes_count' => count($createdShowtimes),
                'seats_count'     => $totalSeatsInserted,
            ]
        ]);
    }
}
