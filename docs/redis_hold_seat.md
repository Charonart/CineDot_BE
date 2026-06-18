# Kiến trúc Giữ Ghế (Hold Seat) với Redis

Tài liệu này giải thích cách hệ thống Đặt vé của CineDot hoạt động dưới nền tảng, đặc biệt là cách chúng ta xử lý việc **Giữ ghế (Hold Seat)** mà không cần dùng đến Cronjob hay quét Database liên tục.

## 1. Vấn đề thường gặp (Tại sao không dùng DB thuần?)
Khi một User vào chọn ghế, họ cần 5 - 10 phút để nhập thông tin thẻ thanh toán. Trong thời gian này, chiếc ghế đó không được phép cho người khác mua.
- **Cách truyền thống**: Cập nhật trạng thái ghế trong DB thành `HELD`. Viết 1 file Cronjob chạy ngầm mỗi 1 phút để dò xem ghế nào `HELD` quá 10 phút thì nhả về `AVAILABLE`.
- **Yếu điểm**: Làm nặng Database vì phải quét liên tục, và trạng thái thường bị delay (không real-time hoàn toàn).

## 2. Giải pháp của CineDot: Redis TTL
Chúng ta sử dụng **Redis** để giải quyết bài toán "đếm ngược thời gian" cực kỳ thanh lịch.
Redis hỗ trợ một tính năng gọi là **TTL (Time-To-Live)**: Bạn lưu 1 dữ liệu và hẹn giờ 10 phút, đúng 10 phút sau dữ liệu đó tự động bốc hơi.

### Quy tắc lưu trữ
- **Database (`schedule_seats`)**: Chỉ lưu 2 trạng thái là `available` (Trống) và `booked` (Đã bán). Chúng ta KHÔNG BAO GIỜ lưu trạng thái `held` vào Database.
- **Redis**: Chịu trách nhiệm lưu trạng thái `held`.

## 3. Luồng hoạt động chi tiết

### Bước 1: User bấm Giữ ghế
User chọn ghế A1, A2 và gọi API `POST /api/bookings/hold-seats`.
1. **DB Lock**: Code dùng `lockForUpdate()` trong DB để đảm bảo không có 2 luồng cùng giữ 1 ghế cùng 1 mili-giây.
2. Code kiểm tra xem ghế đã bị `booked` trong DB chưa?
3. Code kiểm tra xem ghế có key `hold:...` trong Redis chưa?
4. Nếu đều thỏa mãn (ghế đang trống hoàn toàn), code sẽ lưu key vào Redis:
   `Redis::setex("hold:schedule:1:seat:10", 600, $booking_id)`
   *(600 = 600 giây = 10 phút. Giá trị là mã đơn hàng).*

### Bước 2: User khác vào xem sơ đồ ghế
User B vào xem sơ đồ ghế (Gọi API `GET /api/showtimes/1/seats`).
1. Code lấy toàn bộ dữ liệu ghế từ Database (lúc này ghế A1, A2 vẫn đang có status = `available` trong DB).
2. Code lặp qua danh sách ghế, dùng `Redis::exists()` để xem ghế nào đang có key hold.
3. Nếu ghế A1 có key hold trên Redis, code sẽ **ghi đè** biến `$seat->status = 'held'` trước khi trả cục JSON về cho Frontend.
*(Nhờ vậy, User B sẽ thấy ghế A1 màu vàng/xám - Đang có người giữ).*

### Bước 3: User A không thanh toán (Hết 10 phút)
Hết 600 giây, Redis tự động xóa key `hold:schedule:1:seat:10` ra khỏi RAM.
-> Lập tức, khi User B F5 lại sơ đồ ghế, code ở Bước 2 kiểm tra không thấy key hold nữa, và DB vẫn là `available` -> Trả về JSON là ghế Trống! Mọi thứ diễn ra tự động 100%.

### Bước 4: User A thanh toán thành công
Nếu User A thanh toán trong vòng 10 phút (gọi API `POST /api/payments`).
1. Cập nhật DB trạng thái đơn hàng thành `success`.
2. Cập nhật DB bảng `schedule_seats` trạng thái ghế thành `booked`.
3. Xóa thủ công key trên Redis: `Redis::del("hold:schedule:1:seat:10")`.

---
**Tóm lại:** Redis đóng vai trò như một bảng nháp trung gian có khả năng đếm giờ, giúp Database được giảm tải và logic nhả ghế diễn ra mượt mà, chính xác đến từng giây.
