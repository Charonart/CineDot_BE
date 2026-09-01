<?php

namespace App\Services;

class RoomFormatCatalog
{
    /**
     * Danh mục các công nghệ Trình chiếu & Màn hình
     */
    public static function getScreenTypes(): array
    {
        return [
            'standard_2d' => [
                'key' => 'standard_2d',
                'name' => '2D Digital Tiêu Chuẩn',
                'badge' => '2D Digital',
                'description' => 'Chuẩn chiếu kỹ thuật số cơ bản (DCP) với độ phân giải sắc nét 2K/4K trên màn hình phẳng tiêu chuẩn.',
                'suitable_for' => 'Mọi thể loại phim thông thường, tâm lý tình cảm, hài hước, gia đình.',
                'icon' => 'film',
                'default_config' => [
                    'shape' => 'flat',
                    'aspect_ratio' => '2.39:1',
                    'width' => 460,
                    'curve_depth' => 0,
                    'label' => 'MÀN HÌNH CHIẾU 2D',
                    'side_walls' => false,
                ],
            ],
            'standard_3d' => [
                'key' => 'standard_3d',
                'name' => '3D Digital Không Gian 3 Chiều',
                'badge' => '3D Digital',
                'description' => 'Trình chiếu 3D sử dụng kính phân cực cao cấp tạo độ sâu hình ảnh sống động và trung thực.',
                'suitable_for' => 'Phim hoạt hình 3D, siêu anh hùng, viễn tưởng không gian.',
                'icon' => 'glasses',
                'default_config' => [
                    'shape' => 'flat',
                    'aspect_ratio' => '2.39:1',
                    'width' => 460,
                    'curve_depth' => 0,
                    'label' => 'MÀN HÌNH CHIẾU 3D',
                    'side_walls' => false,
                ],
            ],
            'imax_laser' => [
                'key' => 'imax_laser',
                'name' => 'IMAX Laser Khổ Lớn',
                'badge' => 'IMAX Laser',
                'description' => 'Màn hình cong khổng lồ tỷ lệ mở rộng 1.90:1 bao trọn tầm nhìn với máy chiếu Laser 4K thế hệ mới đem lại độ tương phản tuyệt đối và màu sắc rực rỡ.',
                'suitable_for' => 'Bom tấn hành động hành tinh, Sci-Fi, thiên nhiên hùng vĩ quay bằng camera IMAX.',
                'icon' => 'sparkles',
                'banner_url' => 'https://cdn.cinedot.vn/theaters/imax-laser-banner.jpg',
                'default_config' => [
                    'shape' => 'curved',
                    'aspect_ratio' => '1.90:1',
                    'width' => 520,
                    'curve_depth' => 35,
                    'label' => 'MÀN HÌNH CONG IMAX LASER',
                    'side_walls' => false,
                ],
            ],
            'screenx' => [
                'key' => 'screenx',
                'name' => 'ScreenX Chiếu Mở Rộng 270°',
                'badge' => 'ScreenX 270°',
                'description' => 'Hệ thống đa máy chiếu mở rộng hình ảnh tràn sang 2 bên tường phòng chiếu, tạo góc nhìn toàn cảnh 270 độ cực kỳ choáng ngợp.',
                'suitable_for' => 'Phim đua xe tốc độ, kinh dị nghẹt thở, các đại cảnh chiến trường quy mô lớn.',
                'icon' => 'layout-grid',
                'banner_url' => 'https://cdn.cinedot.vn/theaters/screenx-banner.jpg',
                'default_config' => [
                    'shape' => 'three_sided',
                    'aspect_ratio' => '2.39:1',
                    'width' => 460,
                    'curve_depth' => 15,
                    'label' => 'MÀN HÌNH CHÍNH SCREENX',
                    'side_walls' => true,
                    'side_wall_angle' => 35,
                    'side_wall_length' => 220,
                    'left_wall_label' => 'MÀN HÌNH TƯỜNG TRÁI 270°',
                    'right_wall_label' => 'MÀN HÌNH TƯỜNG PHẢI 270°',
                ],
            ],
            'dolby_cinema' => [
                'key' => 'dolby_cinema',
                'name' => 'Dolby Cinema (Dolby Vision HDR)',
                'badge' => 'Dolby Cinema',
                'description' => 'Sự kết hợp đỉnh cao giữa máy chiếu kép Dolby Vision cho màu đen sâu tuyệt đối, chuẩn dải tương phản HDR rực rỡ và hệ thống âm thanh Dolby Atmos.',
                'suitable_for' => 'Phim duy mỹ thị giác, bối cảnh đêm, nghệ thuật ánh sáng và âm nhạc đẳng cấp.',
                'icon' => 'eye',
                'banner_url' => 'https://cdn.cinedot.vn/theaters/dolby-cinema-banner.jpg',
                'default_config' => [
                    'shape' => 'curved',
                    'aspect_ratio' => '2.39:1',
                    'width' => 480,
                    'curve_depth' => 25,
                    'label' => 'MÀN HÌNH DOLBY VISION',
                    'side_walls' => false,
                ],
            ],
            'onyx_led' => [
                'key' => 'onyx_led',
                'name' => 'Samsung Onyx Cinema LED 4K',
                'badge' => 'Onyx LED',
                'description' => 'Màn hình module LED tự phát sáng thay thế hoàn toàn máy chiếu truyền thống, hỗ trợ chuẩn khung hình cao HFR (60fps/120fps) và độ sáng gấp 10 lần.',
                'suitable_for' => 'Phim kỹ xảo tân tiến, chuyển động nhanh mượt mà, game show điện ảnh.',
                'icon' => 'tv',
                'banner_url' => 'https://cdn.cinedot.vn/theaters/onyx-led-banner.jpg',
                'default_config' => [
                    'shape' => 'led_wall',
                    'aspect_ratio' => '1.90:1',
                    'width' => 460,
                    'curve_depth' => 0,
                    'label' => 'MÀN HÌNH SAMSUNG ONYX 4K LED',
                    'side_walls' => false,
                ],
            ],
        ];
    }

    /**
     * Danh mục các Công nghệ Âm thanh
     */
    public static function getSoundTechnologies(): array
    {
        return [
            'surround_71' => [
                'key' => 'surround_71',
                'name' => '7.1 Surround Sound',
                'badge' => '7.1 Surround',
                'description' => 'Hệ thống âm thanh vòm kỹ thuật số 8 kênh (7 loa vệ tinh + 1 subwoofer) chuẩn rạp chiếu quốc tế.',
                'suitable_for' => 'Mọi thể loại phim thông thường.',
                'icon' => 'volume-2',
            ],
            'dolby_atmos' => [
                'key' => 'dolby_atmos',
                'name' => 'Dolby Atmos 3D Audio',
                'badge' => 'Dolby Atmos',
                'description' => 'Hệ thống âm thanh vật thể 3D (object-based audio) với dàn loa trần và loa tường riêng biệt, tái tạo không gian âm thanh 3 chiều bao phủ toàn bộ khán phòng.',
                'suitable_for' => 'Bom tấn cháy nổ, nhạc kịch, kinh dị, hiệu ứng không gian chi tiết.',
                'icon' => 'speaker',
            ],
            'imax_sound' => [
                'key' => 'imax_sound',
                'name' => 'IMAX Custom Sound / DTS:X',
                'badge' => 'IMAX Sound',
                'description' => 'Hệ thống âm thanh đa kênh công suất cực lớn, dải tần số siêu trầm đánh sâu và phủ sóng đồng đều đến từng vị trí ngồi trong rạp.',
                'suitable_for' => 'Phim hành động quy mô lớn, viễn tưởng không gian hoành tráng.',
                'icon' => 'zap',
            ],
        ];
    }

    /**
     * Lấy default screen config dựa theo screen_type
     */
    public static function getDefaultScreenConfig(string $screenType): array
    {
        $types = self::getScreenTypes();
        return $types[$screenType]['default_config'] ?? $types['standard_2d']['default_config'];
    }

    /**
     * Danh mục 7 template phòng chiếu thực tế
     */
    public static function getRoomTemplates(): array
    {
        return [
            [
                'template_key' => 'imax_laser',
                'room_type' => 'IMAX Laser 3D',
                'screen_type' => 'imax_laser',
                'sound_technology' => 'imax_sound',
                'features' => ['laser_projection', 'curved_screen', '12ch_imax_sound', 'sweetbox_seats'],
            ],
            [
                'template_key' => 'dolby_cinema',
                'room_type' => 'Dolby Cinema',
                'screen_type' => 'dolby_cinema',
                'sound_technology' => 'dolby_atmos',
                'features' => ['dolby_vision_hdr', 'dolby_atmos', 'curved_screen', 'acoustic_walls'],
            ],
            [
                'template_key' => 'screenx',
                'room_type' => 'ScreenX 270°',
                'screen_type' => 'screenx',
                'sound_technology' => 'dolby_atmos',
                'features' => ['three_wall_screen', '270_degree_view', 'dolby_atmos'],
            ],
            [
                'template_key' => 'onyx_led',
                'room_type' => 'Samsung Onyx Cinema LED',
                'screen_type' => 'onyx_led',
                'sound_technology' => 'dolby_atmos',
                'features' => ['samsung_onyx_led', '4k_dci', 'hfr_120fps', 'jbl_audio'],
            ],
            [
                'template_key' => 'gold_class',
                'room_type' => 'Gold Class VIP Recliner',
                'screen_type' => 'standard_2d',
                'sound_technology' => 'dolby_atmos',
                'features' => ['recliner_leather_seats', 'in_seat_service', 'mini_table', 'dolby_atmos'],
            ],
            [
                'template_key' => 'standard_3d',
                'room_type' => 'Digital 3D Atmos',
                'screen_type' => 'standard_3d',
                'sound_technology' => 'dolby_atmos',
                'features' => ['polarized_3d', 'dolby_atmos', 'sweetbox_seats'],
            ],
            [
                'template_key' => 'standard_2d',
                'room_type' => 'Digital 2D Standard',
                'screen_type' => 'standard_2d',
                'sound_technology' => 'surround_71',
                'features' => ['2k_laser', '71_surround'],
            ],
        ];
    }
}
