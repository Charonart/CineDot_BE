# Báo cáo Phân tích Kiến trúc & Code backend (CineDot_BE)

Dưới đây là tài liệu phân tích chi tiết toàn bộ các điểm yếu, dư thừa, phi lý và vi phạm nguyên lý cơ bản của lập trình Backend, Database, Caching và kiến trúc SOLID từ code hiện tại.

## 1. Vi phạm Nguyên lý Thiết kế & SOLID (Backend Fundamentals)

*   **Fat Controllers (Vi phạm Single Responsibility Principle - SRP):**
    Các controller như `MovieController`, `ShowtimeController` hiện tại đang "ôm" quá nhiều trách nhiệm: từ nhận Request, query DB (Query Builder), xử lý cấu trúc dữ liệu, thực hiện Caching, cho đến format Response (JSON).
    *Cách chuẩn:* Cần tách bạch ra tầng **Service** (chứa logic nghiệp vụ) và tầng **Repository / Query Builder** (chứa logic chọc vào DB). Controller chỉ nên làm nhiệm vụ cầu nối (nhận Request -> gọi Service -> trả Response).
*   **Format dữ liệu API trực tiếp tại Model (Anti-pattern):**
    Trong `app/Models/Movie.php`, phương thức `toArray()` bị ghi đè để ép các keys thành `camelCase` (`posterUrl`, `backdropUrl`, `releaseDate`) phục vụ cho Frontend.
    *Hậu quả:* Model trong Laravel sinh ra để đại diện cho 1 bản ghi trong Database. Việc ghi đè `toArray()` sẽ phá hỏng dữ liệu nếu các logic nội bộ khác (cronjob, event, job queue) gọi tới `$model->toArray()`.
    *Cách chuẩn:* Sử dụng **API Resources** (`Illuminate\Http\Resources\Json\JsonResource`) để map dữ liệu chuẩn hóa trước khi trả về cho client.

## 2. Sai lầm nghiêm trọng về Caching (Cache Fundamentals)

*   **Cache dữ liệu động (Real-time data):**
    Tại `ShowtimeController::index()`, logic đang thực hiện lưu toàn bộ danh sách suất chiếu, **bao gồm cả biến `available_seats` (ghế trống)** vào Cache trong thời gian 5 phút (`now()->addMinutes(5)`).
    *Hậu quả (Logic phi lý):* Tình trạng ghế trống là dữ liệu thời gian thực. Nếu giữ cache 5 phút, khi User A vừa đặt ghế thành công, User B vào sau vẫn sẽ thấy ghế đó đang "trống" do đọc từ cache cũ. Điều này dẫn tới "race condition" (nhiều người đè nhau mua cùng 1 ghế) và trải nghiệm cực kì tệ hại.
*   **Cache phân trang với Dynamic Keys:**
    Tại `MovieController::trending`, cache key được tạo ra dựa vào tham số page: `"movies.trending.page{$page}.perPage{$perPage}"`.
    *Hậu quả:* Khi người dùng truyền vào các giá trị `per_page` và `page` khác nhau, hệ thống sinh ra hàng ngàn key cache rác. Khi dữ liệu của bộ phim thay đổi (thêm review, đổi rating), server KHÔNG THỂ biết để xóa chính xác các cache này. Lâu dần sẽ dẫn đến tốn RAM Redis/File vô ích và dữ liệu không bao giờ đồng bộ (inconsistent).

## 3. Logic "Vạn năng" & Phi lý (Illogical logic & Over-generic)

*   **Logic "Tìm phim tương tự" quá ngây ngô:**
    Trong `MovieController::similar()`, để tìm phim tương tự, code sử dụng vòng lặp `whereIn` với mảng `genreIds` của phim hiện tại:
    `->whereHas('genres', fn($q) => $q->whereIn('genres.genre_id', $genreIds))`
    *Hậu quả (Vạn năng):* Giả sử một bộ phim có thể loại "Hài kịch" và "Kinh dị". Bất kỳ phim nào có dính 1 chữ "Hài kịch" (cho dù đó là phim Hài/Gia đình) cũng được gom vào danh sách "Tương tự". Thuật toán đúng phải dùng tính điểm trọng số (Scoring) để đo độ khớp % các tags, chứ không dùng `WHERE IN` bừa bãi.
*   **Xử lý mảng (Collection) cồng kềnh trong Memory:**
    Trong `ShowtimeController`, sau khi query xong `Schedule`, code lại dùng hàng loạt các hàm `groupBy`, `map` lồng nhau tới 3 cấp (Movie -> Cinema -> Showtimes) trên memory của PHP. Nếu trong 1 ngày có hàng vạn suất chiếu, việc này sẽ ngốn một lượng lớn RAM của Server và làm API phản hồi cực chậm.

## 4. Lỗ hổng Database & Validation (DB & Security Fundamentals)

*   **Hoàn toàn thiếu Request Validation:**
    Hầu hết các API đọc trực tiếp input từ URL/Body bằng `$request->get()` (VD: `$request->get('per_page', 20)`) mà không đi qua các lớp **FormRequest** để validate.
    *Rủi ro DDoS DB:* Hacker có thể gọi tham số `?per_page=1000000`. Server ngay lập tức sẽ yêu cầu Database query ra 1 triệu record một lúc và bắt PHP phân trang, dẫn đến Out Of Memory và sập DB Server.
*   **Hardcode "Magic Strings" khắp nơi:**
    Việc gọi các chuỗi cứng như `where('status', 'available')`, hay order by theo biến ảo `'reviews_avg_rating'` làm code rất khó refactor sau này. Cần sử dụng Enums (PHP 8.1+) hoặc Constants.
*   **Thiếu cốt lõi của ứng dụng Đặt vé (Booking Core):**
    Mặc dù Database đã thiết kế các bảng như `bookings`, `booking_seats`, `payments` (trong Migrations), nhưng Backend chưa hề có API xử lý việc "Giữ ghế" (Lock Seats). Trong các hệ thống vé, việc Query giữ ghế bắt buộc phải có cơ chế **Pessimistic Locking** (`lockForUpdate()`) hoặc **Optimistic Locking** ở Database để tránh việc bán 1 ghế cho 2 người cùng 1 tích tắc.

---

### Đề xuất Kiến trúc Refactor (Actionable Items)

1.  **Chuyển đổi kiến trúc:** Tạo thư mục `app/Services` để dời các logic tính toán (VD: tính phim Trending, Gom nhóm lịch chiếu) ra khỏi Controllers.
2.  **API Resources:** Chạy `php artisan make:resource` cho từng Model (MovieResource, CinemaResource) để quy chuẩn lại JSON response trả cho FE, đồng thời xóa logic `toArray()` ở Models.
3.  **Form Validation:** Tạo `FormRequest` giới hạn tối đa `per_page <= 50` và validate kiểu dữ liệu (Date, ID).
4.  **Sửa lỗi Cache Ghế:** Tách rời API lấy thông tin lịch chiếu và API lấy sơ đồ ghế. Danh sách lịch chiếu có thể Cache, nhưng Sơ đồ ghế (trạng thái Trống/Đã bán) bắt buộc phải Query trực tiếp hoặc quản lý riêng biệt bằng Redis Pub/Sub / Hash.
