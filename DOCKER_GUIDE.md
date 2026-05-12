# Hướng dẫn thiết lập Docker - CineDot_BE

Tài liệu này hướng dẫn cách thiết lập môi trường phát triển cho dự án Laravel CineDot_BE bằng Docker. Cách tiếp cận này giúp bạn không cần cài đặt PHP hay PostgreSQL trực tiếp trên máy tính cá nhân.

---

## 1. Yêu cầu hệ thống

- Đã cài đặt [Docker Desktop](https://www.docker.com/products/docker-desktop/).
- Docker Desktop đang được bật.

## 2. Các thành phần trong hệ thống

Hệ thống bao gồm 2 container chính:

- **`app` (cinedot_app)**: Chạy PHP 8.2, Composer và server Laravel (Cổng 8080).
- **`db` (cinedot_pgsql)**: Chạy Database PostgreSQL 15 (Cổng 5432).

---

## 3. Các bước thiết lập lần đầu

### Bước 1: Chuẩn bị file `.env`

Đảm bảo file `.env` của bạn có cấu hình kết nối Database như sau để Docker nhận diện được:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=cinedot
DB_USERNAME=postgres
DB_PASSWORD=kochomuonok1
```

### Bước 2: Khởi động Docker

Mở terminal tại thư mục gốc của project và chạy:

```powershell
docker-compose up -d --build
```

_(Lần đầu tiên chạy lệnh này sẽ mất vài phút để Docker tải và xây dựng môi trường)._

### Bước 3: Cài đặt thư viện (Composer)

```powershell
docker-compose run --rm app composer install
```

### Bước 4: Thiết lập Database

```powershell
# Tạo Key cho Laravel
docker-compose exec app php artisan key:generate

# Tạo bảng và dữ liệu mẫu
docker-compose exec app php artisan migrate:fresh --seed
```

---

## 4. Cách sử dụng hàng ngày

### Khởi động / Dừng dự án

- **Bật**: `docker-compose up -d`
- **Tắt**: `docker-compose stop`
- **Xóa container (không mất dữ liệu db)**: `docker-compose down`

### Truy cập ứng dụng

- **Địa chỉ**: [http://localhost:8080](http://localhost:8080)
- **API Movies**: [http://localhost:8080/api/movies](http://localhost:8080/api/movies)

### Cách chạy lệnh Laravel / Composer

Vì PHP nằm trong Docker, mọi lệnh bạn thường dùng sẽ phải đi kèm với tiền tố `docker-compose exec app`.

**Ví dụ:**

- Thay vì `php artisan migrate`, dùng:
    ```powershell
    docker-compose exec app php artisan migrate
    ```
- Thay vì `composer require ...`, dùng:
    ```powershell
    docker-compose exec app composer require <tên-thư-viện>
    ```
- Thay vì `php artisan make:controller`, dùng:
    ```powershell
    docker-compose exec app php artisan make:controller MovieController
    ```

---

## 5. Xử lý lỗi thường gặp

1. **Lỗi "Address already in use":**
    - Do có phần mềm khác (như Skype, XAMPP, hoặc một project khác) đang chiếm cổng 8000 hoặc 8080. Hãy tắt phần mềm đó hoặc đổi cổng trong `docker-compose.yml`.

2. **Lỗi "Connection refused" tới Database:**
    - Kiểm tra xem `DB_HOST` trong file `.env` đã là `db` chưa.
    - Chạy lệnh `docker-compose exec app php artisan config:clear` để xóa cache.

3. **Thư mục `vendor` bị trống:**
    - Chạy lại lệnh: `docker-compose run --rm app composer install`.
