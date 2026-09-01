<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetCinemasRequest;
use App\Http\Resources\CinemaResource;
use App\Services\CinemaService;

class CinemaController extends Controller
{
    public function __construct(private CinemaService $cinemaService)
    {
    }

    public function index(GetCinemasRequest $request)
    {
        $cinemas = $this->cinemaService->getList($request->all());

        return response()->json([
            'success' => true,
            'data'    => CinemaResource::collection($cinemas),
        ]);
    }

    public function showBySlug($slug)
    {
        $cinema = $this->cinemaService->getDetailBySlug($slug);

        return response()->json([
            'success' => true,
            'data'    => new CinemaResource($cinema),
        ]);
    }

    public function pricing()
    {
        $pricing = [
            '2d' => [
                'formatName'  => '2D Digital Tiêu Chuẩn',
                'formatBadge' => '2D',
                'categories'  => [
                    ['dayType' => 'Thứ 2, Thứ 4, Thứ 5', 'timeSlot' => 'Trước 17:00', 'standardPrice' => 75000, 'vipPrice' => 85000, 'sweetboxPrice' => 170000],
                    ['dayType' => 'Thứ 2, Thứ 4, Thứ 5', 'timeSlot' => 'Sau 17:00', 'standardPrice' => 85000, 'vipPrice' => 95000, 'sweetboxPrice' => 190000],
                    ['dayType' => 'Thứ 3 (Happy Day)', 'timeSlot' => 'Cả ngày (Đồng giá)', 'standardPrice' => 60000, 'vipPrice' => 70000, 'sweetboxPrice' => 150000],
                    ['dayType' => 'Thứ 6, Thứ 7, CN, Ngày Lễ', 'timeSlot' => 'Trước 17:00', 'standardPrice' => 90000, 'vipPrice' => 100000, 'sweetboxPrice' => 200000],
                    ['dayType' => 'Thứ 6, Thứ 7, CN, Ngày Lễ', 'timeSlot' => 'Sau 17:00', 'standardPrice' => 105000, 'vipPrice' => 115000, 'sweetboxPrice' => 230000],
                    ['dayType' => 'HSSV / Trẻ Em / U22', 'timeSlot' => 'Trước 17:00 (T2 - T6)', 'standardPrice' => 55000, 'vipPrice' => 65000, 'sweetboxPrice' => 140000],
                ],
            ],
            '3d' => [
                'formatName'  => '3D Experience Đột Phá',
                'formatBadge' => '3D',
                'categories'  => [
                    ['dayType' => 'Thứ 2 - Thứ 5', 'timeSlot' => 'Trước 17:00', 'standardPrice' => 95000, 'vipPrice' => 110000, 'sweetboxPrice' => 220000],
                    ['dayType' => 'Thứ 2 - Thứ 5', 'timeSlot' => 'Sau 17:00', 'standardPrice' => 110000, 'vipPrice' => 125000, 'sweetboxPrice' => 250000],
                    ['dayType' => 'Thứ 6, Thứ 7, CN, Ngày Lễ', 'timeSlot' => 'Trước 17:00', 'standardPrice' => 120000, 'vipPrice' => 135000, 'sweetboxPrice' => 270000],
                    ['dayType' => 'Thứ 6, Thứ 7, CN, Ngày Lễ', 'timeSlot' => 'Sau 17:00', 'standardPrice' => 135000, 'vipPrice' => 150000, 'sweetboxPrice' => 300000],
                ],
            ],
            'imax' => [
                'formatName'  => 'IMAX Laser Trải Nghiệm Cực Đại',
                'formatBadge' => 'IMAX',
                'categories'  => [
                    ['dayType' => 'Thứ 2 - Thứ 5', 'timeSlot' => 'Trước 17:00', 'standardPrice' => 140000, 'vipPrice' => 160000, 'sweetboxPrice' => 320000],
                    ['dayType' => 'Thứ 2 - Thứ 5', 'timeSlot' => 'Sau 17:00', 'standardPrice' => 160000, 'vipPrice' => 180000, 'sweetboxPrice' => 360000],
                    ['dayType' => 'Thứ 6, Thứ 7, CN, Ngày Lễ', 'timeSlot' => 'Trước 17:00', 'standardPrice' => 180000, 'vipPrice' => 200000, 'sweetboxPrice' => 400000],
                    ['dayType' => 'Thứ 6, Thứ 7, CN, Ngày Lễ', 'timeSlot' => 'Sau 17:00', 'standardPrice' => 210000, 'vipPrice' => 230000, 'sweetboxPrice' => 460000],
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data'    => $pricing,
        ]);
    }

    public function showtimes(\Illuminate\Http\Request $request, $slug)
    {
        $date = $request->get('date', now()->toDateString());
        $grouped = $this->cinemaService->getShowtimesBySlug($slug, $date);

        $results = $grouped->map(function ($item) {
            return [
                'movie' => new \App\Http\Resources\MovieResource($item['movie']),
                'times' => \App\Http\Resources\ShowtimeResource::collection($item['times']),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'date'    => $date,
                'results' => $results,
            ]
        ]);
    }

    public function specialTheaters($type)
    {
        $theaters = [
            'imax' => [
                'name' => 'IMAX Laser',
                'description' => 'Trải nghiệm điện ảnh đỉnh cao với màn hình cong khổng lồ tỷ lệ 1.90:1, máy chiếu Laser 4K kép và hệ thống âm thanh 12 kênh sống động.',
                'imageUrl' => 'https://cdn.cinedot.vn/theaters/imax-laser-banner.jpg',
                'screen_type' => 'imax_laser',
                'sound_technology' => 'imax_sound',
            ],
            'screenx' => [
                'name' => 'ScreenX 270°',
                'description' => 'Hệ thống chiếu đa diện mở rộng hình ảnh tràn sang 2 bên tường phòng chiếu, tạo góc nhìn toàn cảnh 270 độ cực kỳ choáng ngợp.',
                'imageUrl' => 'https://cdn.cinedot.vn/theaters/screenx-banner.jpg',
                'screen_type' => 'screenx',
                'sound_technology' => 'dolby_atmos',
            ],
            'dolby-cinema' => [
                'name' => 'Dolby Cinema',
                'description' => 'Sự kết hợp hoàn hảo giữa máy chiếu kép Dolby Vision HDR (màu đen sâu tuyệt đối) và âm thanh vòm không gian 3 chiều Dolby Atmos.',
                'imageUrl' => 'https://cdn.cinedot.vn/theaters/dolby-cinema-banner.jpg',
                'screen_type' => 'dolby_cinema',
                'sound_technology' => 'dolby_atmos',
            ],
            'dolby-atmos' => [
                'name' => 'Dolby Atmos',
                'description' => 'Công nghệ âm thanh vòm vật thể 3D đột phá với dàn loa trần và loa tường riêng biệt, định vị âm thanh chính xác trong không gian 3 chiều.',
                'imageUrl' => 'https://cdn.cinedot.vn/theaters/dolby-banner.jpg',
                'screen_type' => 'standard_2d',
                'sound_technology' => 'dolby_atmos',
            ],
            'onyx-led' => [
                'name' => 'Samsung Onyx Cinema LED 4K',
                'description' => 'Màn hình module LED tự phát sáng không cần máy chiếu, độ sáng gấp 10 lần, hỗ trợ tốc độ khung hình cao HFR 120fps siêu mượt.',
                'imageUrl' => 'https://cdn.cinedot.vn/theaters/onyx-led-banner.jpg',
                'screen_type' => 'onyx_led',
                'sound_technology' => 'dolby_atmos',
            ],
            '4dx' => [
                'name' => '4DX Motion Studio',
                'description' => 'Thưởng thức điện ảnh đa giác quan với ghế chuyển động theo cảnh phim, kết hợp các hiệu ứng môi trường như gió, sương, nước, và mùi hương.',
                'imageUrl' => 'https://cdn.cinedot.vn/theaters/4dx-banner.jpg',
                'screen_type' => 'standard_3d',
                'sound_technology' => 'dolby_atmos',
            ],
            'kids' => [
                'name' => 'Kids Cinema',
                'description' => 'Phòng chiếu được thiết kế đặc biệt cho trẻ em với màu sắc rực rỡ, ghế ngồi thoải mái và âm lượng phù hợp để bảo vệ thính giác bé.',
                'imageUrl' => 'https://cdn.cinedot.vn/theaters/kids-banner.jpg',
                'screen_type' => 'standard_2d',
                'sound_technology' => 'surround_71',
            ],
        ];

        $key = strtolower($type);
        if (!array_key_exists($key, $theaters)) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy định dạng rạp này'], 404);
        }

        return response()->json(['success' => true, 'data' => $theaters[$key]]);
    }
}
