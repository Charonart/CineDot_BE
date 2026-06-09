# CineDot Backend Task List

## 1. Khởi tạo Project & Database
- [ ] Thiết lập Models và Migrations cho các nhóm bảng User & Cinema (Users, Provinces, Cinemas, Rooms, Seats).
- [ ] Thiết lập Models và Migrations cho các nhóm bảng Phim (Movies, Genres, Movie_genres, Persons, Credits, Videos, Reviews).
- [ ] Thiết lập Models và Migrations cho nhóm bảng Lịch chiếu (Schedule, Schedule_Seats).
- [ ] Thiết lập Models và Migrations cho nhóm bảng Đặt vé & Thanh toán (Bookings, Booking_seats, Payments).
- [ ] Tạo Seeders (Provinces, Genres, Users, Mock Cinemas/Rooms/Seats/Movies).

## 2. API - Authentication & Users
- [ ] `POST /api/auth/register` - Đăng ký
- [ ] `POST /api/auth/login` - Đăng nhập
- [ ] `POST /api/auth/logout` - Đăng xuất
- [ ] `GET /api/users/profile` - Xem profile
- [ ] `PUT /api/users/profile` - Cập nhật profile

## 3. API - Cinemas & Locations
- [ ] `GET /api/provinces` - Lấy danh sách Tỉnh/Thành
- [ ] `GET /api/cinemas` - Danh sách rạp
- [ ] `GET /api/cinemas/:id` - Chi tiết rạp
- [ ] `GET /api/cinemas/:id/rooms` - Lấy các phòng của rạp
- [ ] `GET /api/rooms/:id/seats` - Sơ đồ ghế gốc của phòng

## 4. API - Movies & Metadata
- [ ] `GET /api/genres` - Danh sách thể loại
- [ ] `GET /api/movies` - Lấy danh sách phim (kèm filter/search)
- [ ] `GET /api/movies/:id` - Chi tiết phim
- [ ] `GET /api/movies/:id/videos` - Danh sách video/trailer
- [ ] `GET /api/movies/:id/credits` - Danh sách đạo diễn, diễn viên
- [ ] `GET /api/movies/:id/reviews` - Đánh giá của phim
- [ ] `POST /api/movies/:id/reviews` - Thêm đánh giá
- [ ] `GET /api/persons/:id` - Chi tiết người (diễn viên/đạo diễn)

## 5. API - Schedules (Lịch chiếu)
- [ ] `GET /api/schedules` - Danh sách suất chiếu (filter theo phim, rạp, ngày)
- [ ] `GET /api/schedules/:id` - Chi tiết suất chiếu
- [ ] `GET /api/schedules/:id/seats` - Lấy sơ đồ ghế thực tế (có trạng thái ghế/giá vé) của suất chiếu.

## 6. API - Booking & Payments (Luồng Đặt vé)
- [ ] `POST /api/bookings/hold-seats` - Giữ ghế (Schedule_Seats -> HELD)
- [ ] `POST /api/bookings` - Tạo đơn Booking từ các ghế đã giữ
- [ ] `GET /api/bookings/:id` - Xem chi tiết đơn hàng
- [ ] `GET /api/users/bookings` - Xem lịch sử mua vé
- [ ] `POST /api/payments` - Mock thanh toán thành công (Cập nhật Bookings, Schedule_Seats -> SOLD)
