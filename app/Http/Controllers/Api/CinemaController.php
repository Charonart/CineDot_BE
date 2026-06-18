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
        $province = $request->get('province', 'all');
        $cinemas = $this->cinemaService->getList($province);

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
            '2D' => ['adult' => 90000, 'student' => 70000, 'child' => 50000],
            '3D' => ['adult' => 120000, 'student' => 100000, 'child' => 80000],
            'IMAX' => ['adult' => 180000, 'student' => 150000, 'child' => 120000],
            '4DX' => ['adult' => 200000, 'student' => 180000, 'child' => 150000],
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
                'name' => 'IMAX',
                'description' => 'Trải nghiệm điện ảnh đỉnh cao với màn hình cong khổng lồ, phủ kín tầm nhìn và hệ thống âm thanh laser sống động, mang bạn vào trung tâm của bộ phim.',
                'imageUrl' => 'https://example.com/imax-banner.jpg',
            ],
            '4dx' => [
                'name' => '4DX',
                'description' => 'Thưởng thức điện ảnh đa giác quan với ghế chuyển động theo cảnh phim, kết hợp các hiệu ứng môi trường như gió, sương, nước, và mùi hương.',
                'imageUrl' => 'https://example.com/4dx-banner.jpg',
            ],
            'dolby-atmos' => [
                'name' => 'Dolby Atmos',
                'description' => 'Công nghệ âm thanh vòm đột phá, tạo ra không gian âm thanh 3D bao trùm từ mọi hướng, mang lại cảm giác chân thực đến từng chi tiết nhỏ nhất.',
                'imageUrl' => 'https://example.com/dolby-banner.jpg',
            ],
            'kids' => [
                'name' => 'Kids',
                'description' => 'Phòng chiếu được thiết kế đặc biệt cho trẻ em với màu sắc rực rỡ, ghế ngồi thoải mái và âm lượng phù hợp để bảo vệ thính giác bé.',
                'imageUrl' => 'https://example.com/kids-banner.jpg',
            ],
        ];

        if (!array_key_exists($type, $theaters)) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy định dạng rạp này'], 404);
        }

        return response()->json(['success' => true, 'data' => $theaters[$type]]);
    }
}
