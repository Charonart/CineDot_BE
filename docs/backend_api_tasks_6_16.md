# [Backend Task] API Implementation & Verification

Tài liệu này liệt kê các task chi tiết dành cho team Backend để verify hợp đồng API (API Contract) và tiến hành implement đầy đủ các chức năng dựa trên thiết kế hiện tại của Frontend.

## 1. Tiêu chuẩn chung (API Standard & Conventions)

Backend cần tuân thủ cấu trúc Response chuẩn cho TẤT CẢ các API (kể cả thành công hay thất bại):

```json
{
  "success": true, // false nếu lỗi
  "req": {
    "method": "GET",
    "path": "/api/v1/...",
    "query": {} // Nếu có
  },
  "data": { ... } // Payload. Nếu không có dữ liệu trả về hoặc lỗi, vẫn phải có field data (có thể là null)
}
```

### Tiêu chuẩn Phân trang (Pagination)
Các API trả về danh sách bắt buộc phải có thông tin phân trang trong `data`:
```json
{
  "page": 1,
  "totalPages": 5,
  "totalResults": 50,
  "results": [ ... ]
}
```

> [!IMPORTANT]
> **Quy định nghiêm ngặt về Dữ Liệu (TMDB Mapper):**
> - **Tuyệt đối KHÔNG** trả về các trường dữ liệu thô từ TMDB (như `poster_path`, `backdrop_path`, `vote_average`). 
> - Backend đóng vai trò Adapter: Bắt buộc phải transform/map dữ liệu sang DTO của hệ thống CineDot trước khi trả về cho Frontend (Ví dụ: map `poster_path` thành `posterUrl`, `backdrop_path` thành `backdropUrl`, `vote_average` thành `rating`).

---

## 2. Danh sách các API Endpoint cần Implement

Dưới đây là các API mà hệ thống Frontend đang gọi/kỳ vọng. Backend cần verify và xây dựng các controller/service tương ứng:

### 🔐 Module: Authentication
- `[ ]` `GET /api/v1/auth/csrf-cookie`: Lấy CSRF token cho bảo mật.
- `[ ]` `POST /api/v1/auth/login`: Xác thực người dùng (trả về thông tin User object).
- `[ ]` `POST /api/v1/auth/register`: Đăng ký tài khoản mới.
- `[ ]` `POST /api/v1/auth/logout`: Hủy session/token.
- `[ ]` `POST /api/v1/auth/forgot-password`: Yêu cầu gửi link quên mật khẩu.
- `[ ]` `POST /api/v1/auth/reset-password`: Đặt lại mật khẩu với token.
- `[ ]` `POST /api/v1/auth/email/verification-notification`: Gửi lại email xác thực.
- `[ ]` `GET /api/v1/auth/me`: Trả về thông tin user hiện tại nếu đã login, trả `success: false` nếu là guest.

### 🎬 Module: Movies
- `[ ]` `GET /api/v1/movies`: Lấy danh sách phim. Hỗ trợ filter qua query: `category` (coming-soon, imax, nationwide, now-showing), `limit`. (Trả về Pagination).
- `[ ]` `GET /api/v1/movies/navbar`: Lấy danh sách phim (thường là phim hot) để hiển thị nhanh trên mega dropdown Navbar.
- `[ ]` `GET /api/v1/movies/search`: Tìm kiếm phim qua từ khóa (hỗ trợ debounce search, trả về Pagination).
- `[ ]` `GET /api/v1/movies/detail/:slug`: Lấy thông tin chi tiết đầy đủ của phim.
- `[ ]` `GET /api/v1/movies/:id/credits`: Lấy danh sách diễn viên (cast) và đoàn làm phim (crew).
- `[ ]` `GET /api/v1/movies/:id/similar`: Lấy danh sách phim tương tự/gợi ý.
- `[ ]` `GET /api/v1/movies/:id/videos`: Lấy danh sách trailer/video liên quan.

### 🎪 Module: Home & Content (Events, Articles)
- `[ ]` `GET /api/v1/home/hero-slides`: Lấy danh sách phim nổi bật để chạy slider lớn ở trang chủ.
- `[ ]` `GET /api/v1/cinema-corner/articles`: Danh sách bài viết tin tức/review điện ảnh (Pagination).
- `[ ]` `GET /api/v1/cinema-corner/articles/:slug`: Chi tiết bài viết.
- `[ ]` `GET /api/v1/events`: Danh sách sự kiện, khuyến mãi.
- `[ ]` `GET /api/v1/events/:slug`: Chi tiết sự kiện.

### 🏢 Module: Cinemas & Theaters
- `[ ]` `GET /api/v1/cinemas`: Danh sách rạp chiếu phim trong hệ thống.
- `[ ]` `GET /api/v1/cinemas/:slug`: Thông tin chi tiết của 1 rạp cụ thể.
- `[ ]` `GET /api/v1/cinemas/pricing`: Cấu trúc bảng giá vé tiêu chuẩn của rạp.
- `[ ]` `GET /api/v1/cinemas/showtimes`: Lấy danh sách suất chiếu của tất cả các phim tại 1 rạp.
- `[ ]` `GET /api/v1/special-theaters/:type`: Lấy thông tin các định dạng rạp đặc biệt (imax, 4dx, dolby-atmos, kids).

### 🎟️ Module: Booking & Showtimes (Luồng đặt vé)
- **Quick Booking Aggregators:**
  - `[ ]` `GET /api/v1/quick-booking/movies`: Danh sách phim đang chiếu khả dụng để đặt vé.
  - `[ ]` `GET /api/v1/quick-booking/cinemas`: Danh sách rạp đang chiếu phim cụ thể (Query: `movieId`).
  - `[ ]` `GET /api/v1/quick-booking/dates`: Lấy ngày có suất chiếu (Query: `movieId`, `cinemaId`).
  - `[ ]` `GET /api/v1/quick-booking/showtimes`: Lấy danh sách suất chiếu (Query: `movieId`, `cinemaId`, `date`).
- **Showtimes & Seats:**
  - `[ ]` `GET /api/v1/showtimes`: Danh sách suất chiếu chung.
  - `[ ]` `GET /api/v1/showtimes/by-movie`: Suất chiếu được group lại theo từng phim.
  - `[ ]` `GET /api/v1/showtimes/:id`: Chi tiết 1 suất chiếu (bao gồm trạng thái còn mở bán hay không).
  - `[ ]` `GET /api/v1/showtimes/:id/seats`: Lấy sơ đồ phòng chiếu và trạng thái các ghế (đã bán, đang giữ, trống).
- **Booking Actions:**
  - `[ ]` `POST /api/v1/booking/seat-hold`: Giữ ghế tạm thời (Lock seats). Yêu cầu xử lý concurrency/conflict khi nhiều user cùng đặt 1 ghế. Trả về thông tin phiên giữ vé.

### 🛍️ Module: Star Shop (Merchandise)
- `[ ]` `GET /api/v1/star-shop/products`: Lấy danh sách vật phẩm, combo bắp nước bán kèm. Hỗ trợ lọc theo `category`.

---

## 3. Các chức năng và Validate đặc biệt Backend cần Đảm bảo

Dựa trên những phân tích từ hệ thống Frontend (và các mock lỗi hiện tại), Backend cần đặc biệt lưu ý thực hiện các yêu cầu sau:

- `[ ]` **Luôn có `req` object trong Response:** Mọi response gửi về Client phải luôn đính kèm object `req` (gồm `method`, `path`, `query`) theo đúng quy chuẩn.
- `[ ]` **Luôn có `data` object trong Response:** Kể cả khi có Exception/Error hoặc các API dạng POST action (như logout, gửi email thành công) không có dữ liệu trả về, cũng phải đảm bảo tồn tại block `data` (có thể là `{ "data": null }` hoặc `{ "data": { "message": "Success" } }`). Việc trả về JSON thiếu field data hoàn toàn sẽ làm Frontend parse DTO lỗi.
- `[ ]` **Kiểm tra Schema Phân trang (Pagination):** Mọi API List/Search (lấy list phim, list videos, list showtimes) BẮT BUỘC trả về format Pagination. Chú ý tính toán và trả về chính xác `page` và `totalPages`.
- `[ ]` **Xử lý Seat Logic & Concurrency cực kỳ cẩn thận:** API `/booking/seat-hold` phải chịu tải được các vấn đề race conditions và trả về lỗi Conflict HTTP Status (409) kèm format chuẩn khi người dùng chọn ghế đã bị mua hoặc giữ bởi session khác.
- `[ ]` **Xác định Trạng thái (Status) của Showtime:** Các endpoint lấy detail của showtime phải tính toán và trả về rõ ràng trạng thái: `available` (còn mở bán), `closed` (sắp chiếu/đã chiếu nên đóng bán vé online), hoặc `cancelled` (đã hủy).
- `[ ]` **Chuẩn hóa Seat Map Matrix:** Xây dựng schema trả về sơ đồ phòng chiếu một cách nhất quán (hỗ trợ layout hàng/cột, các loại ghế: Thường, VIP, Đôi và các trạng thái: Trống, Đang giữ, Đã bán, Bảo trì). Hiện tại thiết kế mock `seats.json` đang bị lỗi/không tối ưu, Backend có trách nhiệm thiết kế payload tối ưu và thống nhất lại với Frontend.
