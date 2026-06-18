# CineDot Backend Task List

## 1. Khởi tạo Project & Database
- [x] Thiết lập Models và Migrations cho các nhóm bảng User & Cinema (Users, Provinces, Cinemas, Rooms, Seats).
- [x] Thiết lập Models và Migrations cho các nhóm bảng Phim (Movies, Genres, Movie_genres, Persons, Credits, Videos, Reviews).
- [x] Thiết lập Models và Migrations cho nhóm bảng Lịch chiếu (Schedule, Schedule_Seats).
- [x] Thiết lập Models và Migrations cho nhóm bảng Đặt vé & Thanh toán (Bookings, Booking_seats, Payments).
- [x] Tạo Seeders (Provinces, Genres, Users, Mock Cinemas/Rooms/Seats/Movies).

## 2. API - Authentication & Users
- [x] `POST /api/auth/register` - Đăng ký
- [x] `POST /api/auth/login` - Đăng nhập
- [x] `POST /api/auth/logout` - Đăng xuất
- [x] `GET /api/users/profile` - Xem profile
- [x] `PUT /api/users/profile` - Cập nhật profile

## 3. API - Cinemas & Locations
- [x] `GET /api/provinces` - Lấy danh sách Tỉnh/Thành
- [x] `GET /api/cinemas` - Danh sách rạp
- [x] `GET /api/cinemas/:id` - Chi tiết rạp
- [x] `GET /api/cinemas/:id/rooms` - Lấy các phòng của rạp (Đã gộp vào chi tiết rạp)
- [x] `GET /api/rooms/:id/seats` - Sơ đồ ghế gốc của phòng

## 4. API - Movies & Metadata
- [x] `GET /api/genres` - Danh sách thể loại
- [x] `GET /api/movies` - Lấy danh sách phim (kèm filter/search)
- [x] `GET /api/movies/:id` - Chi tiết phim
- [x] `GET /api/movies/:id/videos` - Danh sách video/trailer
- [x] `GET /api/movies/:id/credits` - Danh sách đạo diễn, diễn viên
- [x] `GET /api/movies/:id/reviews` - Đánh giá của phim
- [x] `POST /api/movies/:id/reviews` - Thêm đánh giá
- [x] `GET /api/persons/:id` - Chi tiết người (diễn viên/đạo diễn)

## 5. API - Schedules (Lịch chiếu)
- [x] `GET /api/schedules` - Danh sách suất chiếu (filter theo phim, rạp, ngày)
- [x] `GET /api/schedules/:id` - Chi tiết suất chiếu
- [x] `GET /api/schedules/:id/seats` - Lấy sơ đồ ghế thực tế (có trạng thái ghế/giá vé) của suất chiếu.

## 6. API - Booking & Payments (Luồng Đặt vé)
- [x] `POST /api/bookings/hold-seats` - Giữ ghế (Schedule_Seats -> HELD)
- [x] `POST /api/bookings` - Tạo đơn Booking từ các ghế đã giữ (Gộp vào lúc giữ ghế luôn)
- [x] `GET /api/bookings/:id` - Xem chi tiết đơn hàng
- [x] `GET /api/users/bookings` - Xem lịch sử mua vé
- [x] `POST /api/payments` - Mock thanh toán thành công (Cập nhật Bookings, Schedule_Seats -> SOLD)

