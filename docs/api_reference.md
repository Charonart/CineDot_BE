# CineDot API Reference

Tài liệu này mô tả chi tiết toàn bộ các API Backend đang hoạt động, bao gồm các tham số đầu vào (Params/Body) và yêu cầu bảo mật (Authentication).

> **Lưu ý:** Prefix cho tất cả các endpoint là `/api`

---

## 1. Authentication & Users (Cần Bearer Token cho các API được đánh dấu 🔒)

### `POST /auth/register`
- **Mô tả**: Đăng ký tài khoản mới.
- **Body (JSON)**:
  - `username` (string, required, max 50, unique)
  - `email` (string, required, email, unique)
  - `password` (string, required, min 6)
  - `fullname` (string, required, max 100)
- **Response**: Trả về thông tin User và `token`.

### `POST /auth/login`
- **Mô tả**: Đăng nhập.
- **Body (JSON)**:
  - `email` (string, required)
  - `password` (string, required)
- **Response**: Trả về thông tin User và `token`. Frontend lưu `token` này để gọi các API 🔒.

### 🔒 `POST /auth/logout`
- **Mô tả**: Đăng xuất, vô hiệu hoá token hiện tại.

### 🔒 `GET /users/profile`
- **Mô tả**: Lấy thông tin cá nhân của người đang đăng nhập.

### 🔒 `PUT /users/profile`
- **Mô tả**: Cập nhật thông tin cá nhân.
- **Body (JSON - Optional)**:
  - `fullname` (string)
  - `avatar` (string)
  - `birthday` (date: YYYY-MM-DD)
  - `gender` (string: male, female, other)
  - `province_id` (integer)
  - `phone` (string)

---

## 2. Phim & Thể loại (Movies & Genres)

### `GET /genres`
- **Mô tả**: Lấy danh sách toàn bộ thể loại phim.

### `GET /genres/{id}/movies`
- **Mô tả**: Lấy danh sách phim thuộc 1 thể loại.
- **Query Params**:
  - `page` (integer, default: 1)
  - `per_page` (integer, max: 50, default: 20)

### `GET /movies`
- **Mô tả**: Lấy danh sách phim, hỗ trợ tìm kiếm và lọc.
- **Query Params**:
  - `page`, `per_page` (integer)
  - `search` (string) - Tìm theo tên phim.
  - `status` (string: now_showing, coming_soon, ended)
  - `genre_id` (integer)

### `GET /movies/trending`
- **Mô tả**: Danh sách phim Trending (sắp xếp theo điểm đánh giá giảm dần).
- **Query Params**: `page`, `per_page`.

### `GET /movies/popular`
- **Mô tả**: Danh sách phim Phổ biến (sắp xếp theo lượt đánh giá giảm dần).
- **Query Params**: `page`, `per_page`.

### `GET /movies/{id}`
- **Mô tả**: Lấy chi tiết 1 bộ phim.

### `GET /movies/{id}/credits`
- **Mô tả**: Lấy danh sách diễn viên (cast) và ekip (crew) của bộ phim.

### `GET /movies/{id}/similar`
- **Mô tả**: Danh sách phim tương tự dựa trên độ trùng lặp thể loại.
- **Query Params**: `page`, `per_page`.

### `GET /movies/{id}/showtimes`
- **Mô tả**: Lấy toàn bộ lịch chiếu của 1 bộ phim, gom nhóm theo Ngày -> Rạp.
- **Query Params**:
  - `date` (string: YYYY-MM-DD, mặc định từ hôm nay trở đi)
  - `cinema_id` (integer)

---

## 3. Rạp phim (Cinemas)

### `GET /cinemas`
- **Mô tả**: Lấy danh sách rạp phim.
- **Query Params**:
  - `province` (string) - Lọc theo tên tỉnh/thành.

### `GET /cinemas/{id}`
- **Mô tả**: Lấy chi tiết 1 rạp (bao gồm danh sách các phòng chiếu `rooms`).

---

## 4. Lịch chiếu & Sơ đồ ghế (Showtimes & Seats)

### `GET /showtimes`
- **Mô tả**: Lấy danh sách suất chiếu, gom nhóm theo Phim -> Rạp.
- **Query Params**:
  - `date` (string: YYYY-MM-DD, default: today)
  - `cinema_id` (integer)
  - `movie_id` (integer)
  - `province` (string)

### `GET /showtimes/{id}`
- **Mô tả**: Chi tiết 1 suất chiếu (thời gian, rạp, phòng, giá gốc, số ghế trống).

### `GET /showtimes/{id}/seats`
- **Mô tả**: Sơ đồ ghế thực tế của suất chiếu này (Real-time).
- **Response**: Trả về mảng các ghế kèm `status` (`available`, `booked`, `held`, `blocked`) và `price`. Dùng data này để vẽ sơ đồ ghế ở Frontend.

---

## 5. Đặt vé & Thanh toán (Booking & Payments) - 🔒 Require Auth

### 🔒 `POST /bookings/hold-seats`
- **Mô tả**: Gửi yêu cầu giữ ghế (Khoá DB để chống trùng vé).
- **Body (JSON)**:
  - `schedule_id` (integer, required)
  - `schedule_seat_ids` (array of integer, required, max: 8 items)
- **Response**: Nếu thành công, trạng thái các ghế sẽ chuyển thành `held`. Sinh ra 1 `booking` có trạng thái `pending`. User có 10 phút để thanh toán.

### 🔒 `GET /bookings/{id}`
- **Mô tả**: Lấy chi tiết một đơn đặt vé (Dùng cho màn hình checkout/thanh toán).

### 🔒 `GET /users/bookings`
- **Mô tả**: Lấy lịch sử mua vé của người dùng hiện tại (Sắp xếp mới nhất lên đầu).

### 🔒 `POST /payments`
- **Mô tả**: Mock API xử lý thanh toán.
- **Body (JSON)**:
  - `booking_id` (integer, required)
- **Response**: Thanh toán thành công, trạng thái Booking -> `success`, trạng thái Ghế -> `booked`.
