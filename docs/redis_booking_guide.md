# Hướng dẫn: Cách Redis hoạt động trong Hệ thống Đặt vé (Dành cho Fresher)

Chào bạn, tài liệu này sẽ giải thích cách chúng ta dùng Redis để giải quyết bài toán "Giữ ghế" (Hold Seat) cực kỳ kinh điển trong các hệ thống bán vé (như rạp phim, máy bay).

## 1. Bài toán "Vé kẹt" là gì?
Tưởng tượng 2 người (A và B) cùng vào xem 1 suất chiếu và cùng bấm chọn Ghế `F9`.
- Nếu không có cơ chế giữ ghế: A mất 5 phút nhập thẻ tín dụng, B vào sau thanh toán trong 1 phút. A nhập xong bị báo lỗi "Ghế đã có người mua". Trải nghiệm rất tệ cho A.
- Giải pháp: Khi A vừa bấm chọn `F9` để thanh toán, ta phải "khóa" (Hold) ghế này lại, cho A 10 phút. Trong 10 phút đó, B không thể chọn `F9`.
- **Tuy nhiên:** Nếu A đổi ý, tắt app không thanh toán thì sao? Chiếc ghế `F9` đó sẽ bị kẹt vĩnh viễn ở trạng thái "Đang giữ", rạp chiếu phim mất doanh thu vì B không mua được nữa.

## 2. Tại sao không dùng Database thông thường (PostgreSQL/MySQL)?
Nếu dùng Postgres, ta sẽ lưu `status = 'held'` và `created_at = '10:00 AM'`.
Làm sao để hệ thống biết lúc 10:10 AM phải tự động đổi `status = 'available'`? 
-> Database **không biết tự đếm giờ**! Ta phải viết 1 vòng lặp (Cronjob / Background Worker) cứ mỗi phút quét toàn bộ DB 1 lần để tìm các ghế hết hạn. Nếu có 1 triệu người đang đặt vé cùng lúc, việc quét DB liên tục sẽ làm chết Server.

## 3. Giải pháp tối thượng: Sử dụng Redis
**Redis** là một cơ sở dữ liệu lưu trữ thẳng trên bộ nhớ RAM (đọc/ghi cực nhanh) và có một tính năng siêu việt tên là: **TTL (Time To Live - Thời gian sống)**. Tính năng này cho phép bạn hẹn giờ tự hủy cho dữ liệu.

### Luồng hoạt động của chúng ta (Chỉ với 3 bước):

**Bước 1: Khởi tạo dữ liệu gốc trong DB**
Trong PostgreSQL, bảng `schedule_seats` (Ghế của suất chiếu) chỉ lưu 2 trạng thái: `available` (Trống) và `booked` (Đã bán). Hoàn toàn không có chữ `held` ở đây.

**Bước 2: Khi User A chọn ghế (Hold Seat)**
Hệ thống không lưu trạng thái Held vào PostgreSQL. Thay vào đó, nó đẩy một dữ liệu (Key) lên **Redis**:
- Tên Key: `hold:schedule:1:seat:15`
- Giá trị: `"Booking ID: 123"`
- **Thời gian sống (TTL): 600 giây** (10 phút).

**Bước 3: Khi User B xem sơ đồ ghế**
Hệ thống lấy dữ liệu ghế từ Postgres (`available`, `booked`), sau đó hỏi Redis xem đang có những ghế nào bị `hold` không. 
Nếu Redis trả về danh sách có ghế số 15, Backend sẽ "gộp" (merge) thông tin này lại, đè chữ `available` thành `held` và trả về cho Frontend hiển thị màu cam (Ghế đang có người giữ).

### Chuyện gì xảy ra khi hết 10 phút (Nếu A không thanh toán)?
Đúng 10 phút sau, tính năng TTL của Redis sẽ **tự động xóa sổ** cái Key đó khỏi RAM mà không cần bất kỳ dòng code PHP nào can thiệp.
Lúc này, User B load lại trang, hệ thống hỏi Redis, Redis bảo "Không có Key nào tồn tại", thế là Frontend lại hiển thị ghế 15 màu trắng (`available`), ai cũng có thể vào mua được.
   
### Nếu User A thanh toán thành công thì sao?
Backend sẽ chạy vào PostgreSQL, cập nhật ghế thành `booked`, và dùng lệnh `Redis::del()` để xóa thủ công cái Key trên Redis đi ngay lập tức (không đợi 10 phút nữa).

### Tóm tắt lợi ích:
Với kiến trúc này, chúng ta không cần bất kỳ tiến trình chạy ngầm (Cronjob) nào. Mọi thứ tự động hóa hoàn toàn, giải quyết bài toán đồng thời (Concurrency) mượt mà, chống Deadlock (khóa chéo) và Database SQL gần như không bị áp lực.
