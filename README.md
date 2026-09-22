# 🎬 CineDot Backend API - Cinema Booking Ecosystem

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2" />
  <img src="https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 10" />
  <img src="https://img.shields.io/badge/PostgreSQL-15-4169E1?style=for-the-badge&logo=postgresql&logoColor=white" alt="PostgreSQL 15" />
  <img src="https://img.shields.io/badge/Redis-7.x-DC382D?style=for-the-badge&logo=redis&logoColor=white" alt="Redis 7" />
  <img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker" />
  <img src="https://img.shields.io/badge/APIs-211_Endpoints-success?style=for-the-badge" alt="211 Endpoints" />
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License" />
</p>

> [!IMPORTANT]
> 🔗 **Looking for the Frontend Client & Admin Portal?**  
> Giao diện người dùng (Next.js 15, React 19, Tailwind CSS v4, WebGL OGL, AI CSP Engine) được lưu trữ tại:  
> 👉 **[Charonart/CineDot](https://github.com/Charonart/CineDot)**

> **CineDot** là hệ sinh thái Backend đặt vé xem phim trực tuyến hiệu năng cao được xây dựng trên nền tảng **Laravel 10**, **PostgreSQL 15**, và **Redis Caching / Distributed Locking**. Hệ thống cung cấp đầy đủ các giải pháp từ bán vé đa kênh, giữ ghế thời gian thực, tính giá đa tầng, soát vé tại quầy (POS), đến phân tích doanh thu chuyên sâu cho chuỗi rạp chiếu phim.

---

## 🌟 Tính Năng Nổi Bật

### 1. 🎟️ Đặt Vé & Khóa Giữ Ghế Thời Gian Thực (Real-time Seat Locking)
- **Ma trận sơ đồ ghế (Dynamic Seat Matrix)**: Lưu trữ tọa độ Canvas 2D (`cx`, `cy`) cho các định dạng phòng chiếu (Standard, IMAX, 4DX, Gold Class, Sweetbox Couple).
- **Khóa ghế phân tán O(1) qua Redis**: Cơ chế giữ chỗ tạm thời trong 10 phút (`HOLD_SEAT_EXPIRE_SECONDS`) bằng `Redis::pipeline()`, triệt tiêu hoàn toàn tình trạng đặt trùng ghế khi có hàng nghìn lượt truy cập đồng thời.
- **WebSocket Realtime (`SeatStatusUpdated`)**: Phát sự kiện ngay lập tức khi ghế được chọn/giữ/hủy để đồng bộ tức thì trên giao diện khách hàng.

### 2. 💰 Động Cơ Tính Giá Đa Tầng (Stacking Pricing Engine)
- Tự động cộng phụ thu theo loại ghế (Standard, VIP, Couple), khung giờ (Suất sớm, Giờ vàng) và ngày chiếu (Ngày thường, Cuối tuần, Ngày lễ).
- Áp dụng lũy kế chiết khấu Hạng thành viên (Silver, Gold, Platinum, Diamond) + Mã giảm giá Voucher chiến dịch.
- Cơ chế bảo vệ tài chính **Zero Floor** (đảm bảo giá vé không bao giờ âm).

### 3. 📧 Hệ Sinh Thái Email & Vé Điện Tử E-Ticket (QR Code)
- **Vé điện tử E-Ticket cao cấp**: Tự động gửi email vé chuẩn phong cách Cinema Dark & Red (#0B0F19 / #E50914), hiển thị mã QR Code soát vé tại rạp, danh sách ghế, combo bắp nước và poster phim.
- **Email Onboarding & Loyalty**: Tự động gửi email chào mừng thành viên mới, email chúc mừng thăng hạng VIP và thông báo hoàn tiền/hủy vé.
- **Hàng đợi riêng biệt `emails`**: Chạy bất đồng bộ, có cơ chế Exponential Retry Backoff tránh nghẽn luồng mua vé.

### 4. 🛡️ Phân Quyền Theo Ngữ Cảnh (Context-Aware RBAC)
- Phân quyền nhân sự 3 cấp độ độc lập: `system` (Super Admin), `region` (Quản lý khu vực / Tỉnh thành), `cinema` (Quản lý rạp cụ thể).
- Bộ nhớ đệm phân quyền Redis siêu tốc (`user:{id}:permissions`), tự động xóa sạch khi Admin điều chỉnh ma trận quyền.
- Tự động lọc phạm vi dữ liệu (Data Scoping) trong tất cả báo cáo doanh thu và danh sách đơn hàng.

### 5. 💳 Thanh Toán & Soát Vé Quầy (POS & Staff Operations)
- Tích hợp cổng thanh toán trực tuyến **VNPay** (Tạo URL thanh toán an toàn, xử lý Webhook IPN, Return URL).
- API Quầy vé POS & Soát vé bằng mã QR / mã đơn hàng với khóa phân tán chống quét vé 2 lần (`lock:checkin:{code}`).
- Quản lý bàn giao Combo bắp nước F&B (`is_claimed`).

### 6. 📊 Dashboard & Báo Cáo Phân Tích Doanh Thu
- Cung cấp 6 API Dashboard chuyên sâu: Doanh thu theo ngày/tháng, Top phim ăn khách, Tỷ lệ lấp đầy phòng chiếu, Thống kê soát vé check-in và Dòng hoạt động giao dịch gần nhất.

---

## 🛠️ Yêu Cầu Hệ Thống

- **PHP**: >= 8.1 / 8.2 (Cần các extension: `pdo_pgsql`, `redis`, `gd`, `bcmath`, `curl`, `mbstring`)
- **Database**: PostgreSQL 14+ (Khuyên dùng PostgreSQL 15)
- **Cache / Queue**: Redis 6+ (Khuyên dùng Redis 7)
- **Composer**: >= 2.x
- *(Tùy chọn)*: [Docker Desktop](https://www.docker.com/products/docker-desktop/) nếu chạy bằng Docker.

---

## 🚀 Hướng Dẫn Cài Đặt & Khởi Chạy

Bạn có thể lựa chọn 1 trong 2 cách bên dưới để khởi chạy dự án:

### Cách 1: Khởi Chạy Bằng Docker (Khuyên Dùng - Đơn Giản & Nhanh Nhất)

Cách này tự động thiết lập toàn bộ môi trường PHP 8.2, PostgreSQL 15 và Redis 7 trong các container riêng biệt.

#### 1. Chuẩn bị file `.env`
```bash
cp .env.example .env
```
Mở file `.env` và kiểm tra các cấu hình sau (kết nối trực tiếp qua tên service Docker):
```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=cinedot
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password

REDIS_HOST=redis
REDIS_PORT=6379
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

#### 2. Khởi động các container Docker
```bash
docker-compose up -d --build
```

#### 3. Cài đặt thư viện & Tạo khóa ứng dụng
```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
```

#### 4. Khởi tạo Database & Nạp Dữ Liệu Mẫu (Seed Data)
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

#### 5. Bật Background Worker trong Docker
```bash
docker-compose exec app php artisan queue:work redis --queue=high,default,emails --sleep=3 --tries=3
```

> 🌐 **Ứng dụng chạy tại:** [http://localhost:8000](http://localhost:8000)

---

### Cách 2: Khởi Chạy Trực Tiếp Trên Máy Cá Nhân (Local)

#### 1. Cài đặt Composer Dependencies
```bash
composer install
```

#### 2. Cấu hình file `.env`
```bash
cp .env.example .env
php artisan key:generate
```
Cập nhật thông tin kết nối PostgreSQL và Redis cục bộ trong `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cinedot
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

#### 3. Khởi tạo Cơ Sở Dữ Liệu & Dữ Liệu Mẫu
```bash
php artisan migrate:fresh --seed
```

#### 4. Khởi chạy HTTP Server
```bash
php artisan serve --port=8000
```

---

## ⚡ Hướng Dẫn Chạy Worker & Scheduler (Bắt Buộc Cho Production)

Hệ thống CineDot sử dụng Redis Queue để xử lý bất đồng bộ các tác vụ: gửi email vé điện tử, giải phóng ghế hết hạn và hoàn tiền.

### 1. Khởi chạy Background Queue Worker
Mở một tab terminal riêng và chạy câu lệnh sau:

```bash
php artisan queue:work redis \
  --queue=high,default,emails \
  --sleep=3 \
  --tries=3 \
  --timeout=60 \
  --max-time=3600 \
  --max-jobs=1000 \
  --rest=0.5
```

**Ý nghĩa các cờ tối ưu:**
- `--queue=high,default,emails`: Ưu tiên xử lý sạch hàng đợi `high` (ghế/tiền) trước, sau đó tới `default` và `emails`.
- `--max-time=3600` & `--max-jobs=1000`: Tự động tái khởi động worker sau 1 tiếng hoặc sau 1,000 jobs để **chống rò rỉ RAM (Memory Leak)**.
- `--rest=0.5`: Khoảng nghỉ 0.5s giữa các job để giảm tải CPU server.

### 2. Khởi chạy Cron Scheduler (Tự Động Hủy Đơn Quá Hạn)
Hệ thống có cron job quét và giải phóng các đơn giữ ghế quá 10 phút mỗi phút một lần.

- **Trên môi trường Local**:
  ```bash
  php artisan schedule:work
  ```
- **Trên Server Linux Production (Crontab)**:
  ```bash
  * * * * * cd /path-to-cinedot-be && php artisan schedule:run >> /dev/null 2>&1
  ```

---

## 🔑 Tài Khoản Mẫu Để Test (Seed Data)

Sau khi chạy `php artisan migrate:fresh --seed`, hệ thống đã sẵn sàng các tài khoản mẫu:

| Vai trò (Role) | Email | Mật khẩu | Quyền hạn |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@cinedot.com` | `password123` | Toàn quyền quản trị hệ thống (`system` scope) |
| **Quản Lý Rạp / Staff** | `staff@cinedot.com` | `password123` | Soát vé POS, quản lý suất chiếu tại cụm rạp |
| **Khách Hàng (Customer)** | `customer1@gmail.com` | `password123` | Đặt vé, xem lịch sử mua vé, tích điểm |

---

## 📖 Cấu Trúc Thư Mục Dự Án

```
CineDot_BE/
├── app/
│   ├── Console/Commands/       # Cron Commands (Hủy vé quá hạn)
│   ├── Events/                 # Real-time WebSocket Events (SeatStatusUpdated, RevenueUpdated)
│   ├── Http/
│   │   ├── Controllers/Api/    # API Controllers (Admin, Staff, Customer)
│   │   ├── Requests/           # Form Request Validation
│   │   └── Resources/          # API Resource Transformers (JSON Formatting)
│   ├── Jobs/                   # Background Jobs (SendBookingEmailJob, ProcessRefundJob, CancelExpiredBookingJob)
│   ├── Mail/                   # Mailables (BookingConfirmedMail, WelcomeUserMail, TierUpgradedMail)
│   ├── Models/                 # Eloquent Models (Movie, Showtime, Booking, Seat, User, Role)
│   ├── Services/               # Business Logic Layer (BookingService, SeatService, MovieService, PermissionService)
│   └── Traits/                 # Context RBAC & Filter/Sort Traits
├── database/
│   ├── migrations/             # Database Schemas & Performance Indexes
│   └── seeders/                # Seed dữ liệu mẫu đầy đủ
├── resources/views/emails/     # Blade Templates Email phong cách Cinema Dark & Red
├── routes/
│   ├── api.php                 # Toàn bộ 211 RESTful API Endpoints
│   └── channels.php            # Broadcasting Channels
├── docker-compose.yml          # Cấu hình Docker App + PostgreSQL + Redis
├── CineDot_API_Postman_Collection_V2.json  # Bộ Postman Collection đầy đủ
└── README.md
```

---

## 📑 Tài Liệu API & Postman Collection

Toàn bộ **211 API Endpoints** được xuất sẵn trong file Postman Collection ở thư mục gốc:
- **File Postman**: [`CineDot_API_Postman_Collection_V2.json`](./CineDot_API_Postman_Collection_V2.json)
- **Cách sử dụng**:
  1. Mở Postman -> Chọn **Import** -> Chọn file `CineDot_API_Postman_Collection_V2.json`.
  2. Tạo Environment với biến `baseUrl = http://localhost:8000/api/v1`.
  3. Đăng nhập với tài khoản Admin/Customer để Postman tự động gán Bearer Token vào header các request tiếp theo.

---

## 🛠️ Các Lệnh Thường Dùng (Cheatsheet)

```bash
# Xóa sạch toàn bộ cache hệ thống (Config, Route, View, Cache)
php artisan optimize:clear

# Xem danh sách toàn bộ routes trong hệ thống
php artisan route:list

# Chạy migration mới
php artisan migrate

# Rollback và nạp lại dữ liệu mẫu từ đầu
php artisan migrate:fresh --seed

# Quét thủ công giải phóng ghế hết hạn
php artisan bookings:cancel-expired

# Chạy kiểm thử tự động
php artisan test
```

---

## 👨‍💻 Tác Giả & Liên Hệ (Author)

- **Lead Developer**: [Charonart (Hải Đăng)](https://github.com/Charonart)
- **Frontend Repository**: [Charonart/CineDot](https://github.com/Charonart/CineDot)
- **Backend Repository**: [Charonart/CineDot_BE](https://github.com/Charonart/CineDot_BE)

---

## 📄 Bản Quyền & Giấy Phép
Dự án được phát triển cho hệ sinh thái **CineDot Cinema Ecosystem**. Mọi quyền được bảo lưu.
