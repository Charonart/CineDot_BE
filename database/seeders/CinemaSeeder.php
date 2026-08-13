<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CinemaSeeder extends Seeder
{
    public function run(): void
    {
        $cinemas = [
            // Hà Nội (province_id: 1)
            ['cinema_id' => 1, 'province_id' => 1, 'cinema_name' => 'CineDot Vincom Bà Triệu', 'slug' => 'cinedot-vincom-ba-trieu', 'cinema_address' => 'Tầng 6 Vincom Center, 191 Bà Triệu, Hai Bà Trưng, Hà Nội', 'phone' => '02439748888', 'email' => 'batrieu@cinedot.com', 'description' => 'Rạp phim hiện đại tại trung tâm Hà Nội với dàn âm thanh Dolby Atmos đỉnh cao.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 2, 'province_id' => 1, 'cinema_name' => 'CineDot Royal City', 'slug' => 'cinedot-royal-city', 'cinema_address' => 'Tầng B2 Royal City, 72A Nguyễn Trãi, Thanh Xuân, Hà Nội', 'phone' => '02462620123', 'email' => 'royalcity@cinedot.com', 'description' => 'Tổ hợp rạp chiếu phim quy mô lớn với màn hình IMAX Laser lớn nhất miền Bắc.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 3, 'province_id' => 1, 'cinema_name' => 'CineDot Times City', 'slug' => 'cinedot-times-city', 'cinema_address' => 'Tầng B1 Times City, 458 Minh Khai, Hai Bà Trưng, Hà Nội', 'phone' => '02439750123', 'email' => 'timescity@cinedot.com', 'description' => 'Rạp chiếu phim gia đình tiêu chuẩn quốc tế với phòng chiếu Gold Class sang trọng.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 4, 'province_id' => 1, 'cinema_name' => 'CineDot Liễu Giai', 'slug' => 'cinedot-lieu-giai', 'cinema_address' => 'Tầng 3 Vincom Metropolis, 29 Liễu Giai, Ba Đình, Hà Nội', 'phone' => '02438318888', 'email' => 'lieugiai@cinedot.com', 'description' => 'Phòng chiếu ScreenX 270 độ mang lại trải nghiệm không gian điện ảnh vô tận.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 5, 'province_id' => 1, 'cinema_name' => 'CineDot West Lake Lotte Mall', 'slug' => 'cinedot-west-lake', 'cinema_address' => 'Tầng 4 Lotte Mall, 272 Võ Chí Công, Tây Hồ, Hà Nội', 'phone' => '02437568888', 'email' => 'westlake@cinedot.com', 'description' => 'Khu phức hợp rạp cao cấp ven Hồ Tây với hệ thống ghế ngả da cao cấp.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 6, 'province_id' => 1, 'cinema_name' => 'CineDot Ocean Park', 'slug' => 'cinedot-ocean-park', 'cinema_address' => 'Tầng 4 Vincom Mega Mall Ocean Park, Gia Lâm, Hà Nội', 'phone' => '02438889999', 'email' => 'oceanpark@cinedot.com', 'description' => 'Rạp phim hiện đại tại đại đô thị phía Đông Hà Nội.', 'is_active' => true, 'created_at' => now()],

            // TP. Hồ Chí Minh (province_id: 2)
            ['cinema_id' => 7, 'province_id' => 2, 'cinema_name' => 'CineDot Landmark 81', 'slug' => 'cinedot-landmark-81', 'cinema_address' => 'Tầng B1 Landmark 81, 720A Điện Biên Phủ, Bình Thạnh, TP.HCM', 'phone' => '02839158888', 'email' => 'landmark81@cinedot.com', 'description' => 'Cụm rạp cao cấp tích hợp màn hình IMAX Laser lớn nhất Việt Nam.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 8, 'province_id' => 2, 'cinema_name' => 'CineDot Vincom Đồng Khởi', 'slug' => 'cinedot-dong-khoi', 'cinema_address' => 'Tầng 3 Vincom Center, 72 Lê Thánh Tôn, Bến Nghé, Quận 1, TP.HCM', 'phone' => '02838278888', 'email' => 'dongkhoi@cinedot.com', 'description' => 'Cụm rạp trung tâm Sài Gòn với trang thiết bị chuẩn quốc tế và ghế VIP Sweetbox.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 9, 'province_id' => 2, 'cinema_name' => 'CineDot Thảo Điền Pearl', 'slug' => 'cinedot-thao-dien', 'cinema_address' => 'Tầng 2 Thảo Điền Mall, 12 Quốc Hương, Thảo Điền, TP. Thủ Đức, TP.HCM', 'phone' => '02837448888', 'email' => 'thaodien@cinedot.com', 'description' => 'Rạp phim phong cách hiện đại phục vụ cộng đồng cư dân quốc tế.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 10, 'province_id' => 2, 'cinema_name' => 'CineDot Crescent Mall', 'slug' => 'cinedot-crescent-mall', 'cinema_address' => 'Tầng 5 Crescent Mall, 101 Tôn Dật Tiên, Tân Phú, Quận 7, TP.HCM', 'phone' => '02854138888', 'email' => 'crescentmall@cinedot.com', 'description' => 'Cụm rạp rộng rãi khu đô thị Phú Mỹ Hưng với phòng chiếu 4DX chuyển động đa chiều.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 11, 'province_id' => 2, 'cinema_name' => 'CineDot Sư Vạn Hạnh', 'slug' => 'cinedot-su-van-hanh', 'cinema_address' => 'Tầng 6 Vạn Hạnh Mall, 11 Sư Vạn Hạnh, Quận 10, TP.HCM', 'phone' => '02838628888', 'email' => 'suvanhanh@cinedot.com', 'description' => 'Địa điểm xem phim sôi động bậc nhất của giới trẻ Sài Thành.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 12, 'province_id' => 2, 'cinema_name' => 'CineDot Aeon Mall Tân Phú', 'slug' => 'cinedot-aeon-tan-phu', 'cinema_address' => 'Tầng 3 Aeon Mall Tân Phú Celadon, 30 Bờ Bao Tân Thắng, Tân Phú, TP.HCM', 'phone' => '02862888888', 'email' => 'aeontanphu@cinedot.com', 'description' => 'Rạp chiếu phim gia đình với màn hình Starium sắc nét vượt trội.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 13, 'province_id' => 2, 'cinema_name' => 'CineDot Aeon Mall Bình Tân', 'slug' => 'cinedot-aeon-binh-tan', 'cinema_address' => 'Tầng 3 Aeon Mall Bình Tân, Số 1 Đường 17A, Bình Trị Đông B, Bình Tân, TP.HCM', 'phone' => '02837658888', 'email' => 'aeonbinhtan@cinedot.com', 'description' => 'Cụm rạp phía Tây thành phố với hệ thống ghế ngồi êm ái tiện nghi.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 14, 'province_id' => 2, 'cinema_name' => 'CineDot Giga Mall Thủ Đức', 'slug' => 'cinedot-giga-mall', 'cinema_address' => 'Tầng 6 Giga Mall, 240-242 Phạm Văn Đồng, Hiệp Bình Chánh, TP. Thủ Đức, TP.HCM', 'phone' => '02871088888', 'email' => 'gigamall@cinedot.com', 'description' => 'Tổ hợp rạp chiếu phim hiện đại công nghệ cao.', 'is_active' => true, 'created_at' => now()],

            // Đà Nẵng (province_id: 3)
            ['cinema_id' => 15, 'province_id' => 3, 'cinema_name' => 'CineDot Vincom Ngô Quyền', 'slug' => 'cinedot-ngo-quyen-da-nang', 'cinema_address' => 'Tầng 4 Vincom Plaza, 910A Ngô Quyền, An Hải Bắc, Sơn Trà, Đà Nẵng', 'phone' => '02363996888', 'email' => 'ngoquyen@cinedot.com', 'description' => 'Rạp chiếu phim hàng đầu thành phố đáng sống với phòng chiếu Dolby Atmos.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 16, 'province_id' => 3, 'cinema_name' => 'CineDot Helio Center Đà Nẵng', 'slug' => 'cinedot-helio-da-nang', 'cinema_address' => 'Tòa nhà Helio Center, Đường 2/9, Hòa Cường Bắc, Hải Châu, Đà Nẵng', 'phone' => '02363630888', 'email' => 'helio@cinedot.com', 'description' => 'Tổ hợp vui chơi giải trí và rạp phim màn hình lớn chất lượng cao.', 'is_active' => true, 'created_at' => now()],

            // Hải Phòng (province_id: 4)
            ['cinema_id' => 17, 'province_id' => 4, 'cinema_name' => 'CineDot Vincom Imperia Hải Phòng', 'slug' => 'cinedot-imperia-hai-phong', 'cinema_address' => 'Tầng 4 Vincom Plaza Imperia, Thượng Lý, Hồng Bàng, Hải Phòng', 'phone' => '02253528888', 'email' => 'imperia@cinedot.com', 'description' => 'Cụm rạp phim sang trọng tại thành phố hoa phượng đỏ.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 18, 'province_id' => 4, 'cinema_name' => 'CineDot Aeon Mall Lê Chân', 'slug' => 'cinedot-aeon-le-chan', 'cinema_address' => 'Tầng 3 Aeon Mall Lê Chân, 10 Võ Nguyên Giáp, Kênh Dương, Lê Chân, Hải Phòng', 'phone' => '02253988888', 'email' => 'aeonlechan@cinedot.com', 'description' => 'Rạp chiếu phim quy mô lớn nhất thành phố Cảng.', 'is_active' => true, 'created_at' => now()],

            // Cần Thơ (province_id: 5)
            ['cinema_id' => 19, 'province_id' => 5, 'cinema_name' => 'CineDot Vincom Hùng Vương Cần Thơ', 'slug' => 'cinedot-hung-vuong-can-tho', 'cinema_address' => 'Tầng 5 Vincom Plaza, 2 Hùng Vương, Thới Bình, Ninh Kiều, Cần Thơ', 'phone' => '02923768888', 'email' => 'hungvuong@cinedot.com', 'description' => 'Cụm rạp hiện đại bậc nhất khu vực Đồng Bằng Sông Cửu Long.', 'is_active' => true, 'created_at' => now()],
            ['cinema_id' => 20, 'province_id' => 5, 'cinema_name' => 'CineDot Sense City Cần Thơ', 'slug' => 'cinedot-sense-city-can-tho', 'cinema_address' => 'Tầng 3 TTTM Sense City, 1 Đại lộ Hòa Bình, Tân An, Ninh Kiều, Cần Thơ', 'phone' => '02923818888', 'email' => 'sensecity@cinedot.com', 'description' => 'Điểm hẹn điện ảnh quen thuộc của khán giả Tây Đô.', 'is_active' => true, 'created_at' => now()],
        ];

        DB::table('cinemas')->insertOrIgnore($cinemas);
    }
}
