# CineDot Backend API (Sprint 1)

> **CineDot** là hệ thống Backend đặt vé xem phim trực tuyến hiệu năng cao được xây dựng trên nền tảng **Laravel 10**, **PostgreSQL**, và **Redis Caching / Distributed Locking**.

---

## 🚀 Các Tính Năng Cốt Lõi (Sprint 1)

1. **Authentication & Profile**:
   - Xác thực người dùng qua Laravel Sanctum API Token.
   - Quản lý hồ sơ cá nhân, đổi mật khẩu, quên mật khẩu và xác thực email.
   - Tích hợp User Tier Loyalty tự động thăng hạng theo điểm tích lũy (`total_points`).

2. **Movies & Catalog**:
   - Quản lý phim đang chiếu, sắp chiếu, xu hướng (trending), tìm kiếm nâng cao.
   - Chi tiết phim kèm trailer YouTube, credits đạo diễn/diễn viên, đánh giá 1-5 sao.

3. **Cinemas, Rooms & Dynamic Seat Matrix**:
   - Quản lý cụm rạp, phòng chiếu đặc biệt (IMAX, 4DX, GOLD).
   - Thiết kế sơ đồ ghế tĩnh dưới dạng JSON ma trận (`seat_matrix`) tọa độ canvas (cx, cy).
   - Tự động sinh danh sách ghế thời gian thực theo từng suất chiếu.

4. **Booking & Pricing Engine**:
   - **Distributed Seat Locking**: Khóa giữ ghế tạm thời thời gian thực qua **Redis** với TTL 10 phút chống xung đột đặt trùng chỗ.
   - **Stacking Discount Engine**: Tính hóa đơn đa tầng (Phụ thu loại ghế VIP/Couple + Chiết khấu Hạng thành viên + Giảm giá Voucher chiến dịch) với cơ chế chống số âm (Zero Floor).
   - **E-Ticket**: Mã QR Code vé điện tử soát vé một lần (`checked_in_at`).
   - Tự động hủy đơn quá hạn và giải phóng ghế qua Queue Job.

5. **Context-Aware RBAC (Phân quyền theo ngữ cảnh)**:
   - Phân quyền nhân sự 3 cấp độ: `system` (Super Admin), `region` (Quản lý khu vực / Tỉnh thành), `cinema` (Quản lý rạp).
   - Bộ nhớ đệm phân quyền Redis Caching siêu tốc (`user:{id}:permissions`).
   - Tự động lọc dữ liệu (Data Scoping) tại các trang quản trị đơn vé, lịch chiếu, báo cáo doanh thu.

6. **Payment & POS Staff Operations**:
   - Tích hợp cổng thanh toán trực tuyến **VNPay Sandbox** (Tạo URL thanh toán, xử lý Webhook IPN, Return URL).
   - API Quầy vé POS & Soát vé quét QR / nhập mã `booking_code`.
   - Bàn giao Combo F&B (`is_claimed`).

---

## 🛠️ Yêu Cầu Môi Trường & Công Nghệ

- **PHP**: >= 8.1 / 8.2 (kèm extension `pdo_pgsql`, `redis`, `gd`, `bcmath`)
- **Database**: PostgreSQL 14+
- **Cache & Queue**: Redis 6+
- **Composer**: 2.x

---

## ⚙️ Hướng Dẫn Cài Đặt & Khởi Chạy

### 1. Clone Source Code & Cài Đặt Dependencies
```bash
git clone <repository-url>
cd CineDot_BE
composer install
```

### 2. Cấu Hình Biến Môi Trường (.env)
```bash
cp .env.example .env
php artisan key:generate
```
*Cập nhật các thông số kết nối PostgreSQL và Redis trong file `.env`:*
```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cinedot
DB_USERNAME=postgres
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### 3. Khởi Chạy Migration & Nạp Dữ Liệu Mẫu (Seeders)
```bash
# Chạy migration và seed toàn bộ dữ liệu mẫu
php artisan migrate:fresh --seed
```

### 4. Khởi Chạy Server & Background Queue Worker
```bash
# Chạy HTTP Server
php artisan serve --port=8000

# Chạy Queue Worker xử lý hủy vé & hoàn tiền
php artisan queue:work redis
```

---

## 📚 Tài Liệu API & Postman Collection

Toàn bộ **111 API Endpoints** được định nghĩa chuẩn RESTful và lưu trữ trong file:
- **File Postman**: [`CineDot_API_Postman_Collection_V2.json`](./CineDot_API_Postman_Collection_V2.json)
- **Cấu trúc 8 nhóm API**:
  1. `1. Auth & Profile` (9 requests)
  2. `2. Master Data` (6 requests)
  3. `3. Movies & Catalog` (12 requests)
  4. `4. Cinemas, Rooms & Showtimes` (11 requests)
  5. `5. Booking & Pricing Engine` (9 requests)
  6. `6. Payment & Webhooks` (5 requests)
  7. `7. Staff Operations & POS` (4 requests)
  8. `8. Admin Management` (55 requests)

---

## 📄 Bản Quyền & Giấy Phép
Dự án được phát triển cho nền tảng **CineDot**. Mọi quyền được bảo lưu.
