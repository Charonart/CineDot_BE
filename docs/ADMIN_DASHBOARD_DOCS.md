# 📊 TÀI LIỆU KỸ THUẬT: HỆ THỐNG ADMIN DASHBOARD & REALTIME REPORTING

> **Dự án**: CineDot — Hệ thống Quản trị & Đặt vé xem phim trực tuyến  
> **Mục tiêu tài liệu**: Giúp tất cả thành viên trong nhóm phát triển nắm rõ toàn bộ kiến trúc, API, luồng dữ liệu, cơ chế phân quyền ngữ cảnh (Context-Aware RBAC) và hệ thống cập nhật thời gian thực (Realtime WebSockets) đã xây dựng cho **Admin Dashboard**.

---

## 📑 Mục lục
1. [Tổng quan hệ thống (Overview)](#1-tổng-quan-hệ-thống-overview)
2. [Kiến trúc & Luồng hoạt động (Architecture & Flow)](#2-kiến-trúc--luồng-hoạt-động-architecture--flow)
3. [Phân quyền theo ngữ cảnh (Context-Aware RBAC & Data Scoping)](#3-phân-quyền-theo-ngữ-cảnh-context-aware-rbac--data-scoping)
4. [Đặc tả API Báo cáo & Thống kê (API Specs)](#4-đặc-tả-api-báo-cáo--thống-kê-api-specs)
5. [Hệ thống Realtime WebSockets & Broadcast Events](#5-hệ-thống-realtime-websockets--broadcast-events)
6. [Tích hợp Frontend & Hook `useDashboardRealtime`](#6-tích-hợp-frontend--hook-usedashboardrealtime)
7. [Xử lý Ngoại lệ, Độ tin cậy & Single Source of Truth](#7-xử-lý-ngoại-lệ-độ-tin-cậy--single-source-of-truth)
8. [Hướng dẫn mở rộng tính năng (Developer Next Steps)](#8-hướng-dẫn-mở-rộng-tính-năng-developer-next-steps)

---

## 1. Tổng quan hệ thống (Overview)

Module **Admin Dashboard** được thiết kế nhằm cung cấp cái nhìn toàn diện, tức thì và chính xác về hoạt động kinh doanh của CineDot:
* **Chỉ số KPI cốt lõi**: Tổng doanh thu thực tế, số lượng vé đã bán ra, tỷ lệ chuyển đổi, hoạt động soát vé.
* **Biểu đồ trực quan (Revenue & Ticket Chart)**: Theo dõi doanh thu & lượng vé theo từng ngày với thuật toán tự động bù lấp ngày trống (Zero-Filling).
* **Bộ lọc linh hoạt (Multi-dimension Filters)**: Lọc đồng thời theo khoảng ngày (`start_date`, `end_date`), Cụm rạp (`cinema_id`), Phim (`movie_id`).
* **Realtime Live-Feed**: Cập nhật doanh thu và hoạt động soát vé tức thời qua WebSocket mà không cần reload trang (F5).
* **Bảo mật đa tầng**: Áp dụng Context-Aware Data Scoping (Trưởng rạp chỉ thấy số liệu của rạp mình; Super Admin/Kế toán trưởng thấy toàn hệ thống).

---

## 2. Kiến trúc & Luồng hoạt động (Architecture & Flow)

```mermaid
graph TD
    subgraph Client["Frontend Admin Dashboard"]
        UI[Dashboard View / Charts / Counter]
        Hook[useDashboardRealtime Hook]
    end

    subgraph Backend["Laravel Backend Engine"]
        ReportCtrl[ReportController / API Controller]
        ReportSrv[ReportService (Data Aggregation & Scoping)]
        EventRev[RevenueUpdated Event]
        EventScan[TicketScanned Event]
        AuthGate[Context-Aware RBAC Gatekeeper]
    end

    subgraph Database["MySQL Database (Single Source of Truth)"]
        Bookings[(Bookings Table)]
        BookingSeats[(BookingSeats Table)]
        UserRoles[(UserRoles Context Table)]
    end

    subgraph WS["WebSocket Server (Laravel Reverb / Pusher)"]
        Channel["Private Channel: admin.dashboard"]
    end

    %% REST Flow
    UI -->|1. GET /reports/revenue (Filters, DateRange)| ReportCtrl
    ReportCtrl --> ReportSrv
    ReportSrv -->|2. Check Permissions & Scope| AuthGate
    AuthGate -.->|Scope Cinema IDs| UserRoles
    ReportSrv -->|3. Query Aggregated Paid/Completed Bookings| Bookings
    ReportSrv -->|4. Query Tickets Count| BookingSeats
    ReportSrv -->|5. Zero-Fill Date Missing Range| ReportCtrl
    ReportCtrl -->|6. JSON Response| UI

    %% Realtime Flow
    Bookings -->|Payment Success| EventRev
    Bookings -->|Staff QR Scan| EventScan
    EventRev -->|Broadcast Safely After DB Commit| Channel
    EventScan -->|Broadcast Safely After DB Commit| Channel
    Channel -->|WebSocket Push Notification| Hook
    Hook -->|Trigger Re-fetch / Toast / Sound| UI
```

---

## 3. Phân quyền theo ngữ cảnh (Context-Aware RBAC & Data Scoping)

Để đảm bảo an toàn thông tin kinh doanh, hệ thống không chỉ dùng RBAC tĩnh mà triển khai **Context-Aware Scoping** theo 3 cấp độ trong bảng `user_roles`:
* `system`: Toàn quyền hệ thống (Super Admin, Quản trị viên cấp cao) $\rightarrow$ Xem báo cáo của tất cả các rạp.
* `region`: Quản lý cụm rạp khu vực (VD: Miền Bắc, TP.HCM) $\rightarrow$ Xem báo cáo các rạp thuộc khu vực quản lý.
* `cinema`: Trưởng rạp / Quản lý rạp cụ thể $\rightarrow$ Chỉ xem báo cáo đúng rạp được gán `cinema_id`.

### Cơ chế Data Scoping trong `ReportService.php`:
```php
// Tự động giải quyết quyền và inject WHERE clause tương ứng
if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
    $allowedCinemaIds = $user->getAuthorizedScopeIds('view:report', 'cinema');
    if (!in_array('*', $allowedCinemaIds)) {
        $query->whereHas('showtime.room', function ($q) use ($allowedCinemaIds) {
            $q->whereIn('cinema_id', $allowedCinemaIds);
        });
    }
}
```

### Ủy quyền truy cập Kênh Realtime (`routes/channels.php`):
Kênh `private-admin.dashboard` được bảo vệ nghiêm ngặt:
* Cho phép nếu User mang Role `admin`, `super_admin`, `cinema_manager`, `accountant`.
* Hoặc User có quyền `view:report`, `reports.revenue`, `reports.dashboard.view`.
* Hoặc User sở hữu quyền theo Scope tại ít nhất một rạp.

---

## 4. Đặc tả API Báo cáo & Thống kê (API Specs)

### `GET /api/admin/reports/revenue`

#### Request Parameters (Query String):
| Tham số | Kiểu | Bắt buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `start_date` / `from_date` | `string (YYYY-MM-DD)` | Không | Ngày bắt đầu thống kê (Mặc định 7 ngày trước) |
| `end_date` / `to_date` | `string (YYYY-MM-DD)` | Không | Ngày kết thúc thống kê (Mặc định hôm nay) |
| `cinema_id` | `integer` | Không | Lọc doanh thu theo cụm rạp cụ thể |
| `movie_id` | `integer` | Không | Lọc doanh thu theo phim cụ thể |
| `group_by` | `string` | Không | Nhóm dữ liệu biểu đồ. Hỗ trợ giá trị `'day'` |

#### Response JSON Thành công:
```json
{
  "success": true,
  "data": {
    "total_revenue": 125000000.0,
    "total_tickets_sold": 1420,
    "cinema_name": "Tất cả cụm rạp",
    "start_date": "2026-08-17",
    "end_date": "2026-08-24",
    "summary": {
      "total_revenue": 125000000.0,
      "total_tickets_sold": 1420
    },
    "filters": {
      "cinema_id": null,
      "movie_id": null,
      "start_date": "2026-08-17",
      "end_date": "2026-08-24",
      "group_by": "day"
    },
    "chart": [
      { "date": "2026-08-17", "revenue": 15000000, "tickets_sold": 180 },
      { "date": "2026-08-18", "revenue": 12000000, "tickets_sold": 145 },
      { "date": "2026-08-19", "revenue": 0, "tickets_sold": 0 },
      { "date": "2026-08-20", "revenue": 18500000, "tickets_sold": 210 },
      { "date": "2026-08-21", "revenue": 22000000, "tickets_sold": 250 },
      { "date": "2026-08-22", "revenue": 31500000, "tickets_sold": 360 },
      { "date": "2026-08-23", "revenue": 26000000, "tickets_sold": 275 }
    ]
  }
}
```

> 💡 **Điểm sáng thuật toán**: Hàm `fillMissingDates()` trong `ReportService` sử dụng `CarbonPeriod` duyệt qua từng ngày trong dải tìm kiếm. Nếu ngày nào không phát sinh đơn hàng, hệ thống tự động gán `revenue: 0` và `tickets_sold: 0`, đảm bảo biểu đồ không bị đứt đoạn hoặc sai lệch trục thời gian.

---

## 5. Hệ thống Realtime WebSockets & Broadcast Events

Hệ thống cung cấp 2 Broadcast Events chuẩn hóa phát trên Kênh riêng tư `private-admin.dashboard`:

### 5.1. `RevenueUpdated` (`revenue.updated`)
* **Thời điểm kích hoạt**: Khi thanh toán đơn vé thành công (Payment Gateway IPN / VNPAY / MoMo Callback / Nhân viên POS thanh toán).
* **Payload phát đi**:
```json
{
  "event": "revenue.updated",
  "reason": "payment_completed",
  "booking_id": 1042,
  "cinema_id": 3,
  "movie_id": 12,
  "changed_at": "2026-08-24T00:15:30+07:00"
}
```

### 5.2. `TicketScanned` (`ticket.scanned`)
* **Thời điểm kích hoạt**: Khi nhân viên rạp quét mã QR check-in vé của khách tại cửa phòng chiếu.
* **Payload phát đi**:
```json
{
  "event": "ticket.scanned",
  "action": "ticket_checkin",
  "booking_id": 1042,
  "booking_code": "CND-883921",
  "customer_name": "Nguyễn Văn A",
  "movie_title": "Avengers: Secret Wars",
  "cinema_name": "CineDot Landmark 81",
  "room_name": "Cinema Hall 01 (IMAX)",
  "seats": "F08, F09",
  "final_amount": 260000.0,
  "checked_in_at": "2026-08-24T00:18:00+07:00",
  "formatted_time": "00:18 24/08/2026",
  "status": "CHECKED_IN",
  "is_checked_in": true
}
```

---

## 6. Tích hợp Frontend & Hook `useDashboardRealtime`

Trên giao diện Frontend React/TypeScript, hook `useDashboardRealtime` kết nối với Laravel Echo để quản lý luồng dữ liệu thời gian thực:

```typescript
// Ví dụ cách hook hoạt động ở Frontend
import { useEffect } from 'react';
import { echo } from '@/lib/echo';

export const useDashboardRealtime = ({ onRevenueUpdate, onTicketScanned }) => {
  useEffect(() => {
    const channel = echo.private('admin.dashboard');

    // Lắng nghe cập nhật doanh thu
    channel.listen('.revenue.updated', (data) => {
      // 1. Tự động trigger re-fetch dữ liệu tổng hợp
      onRevenueUpdate?.(data);
    });

    // Lắng nghe hoạt động soát vé
    channel.listen('.ticket.scanned', (data) => {
      // 1. Thêm vào Live Activity Feed
      // 2. Phát âm thanh notification nhẹ (audio ping)
      // 3. Hiển thị Toast thông báo check-in thành công
      onTicketScanned?.(data);
    });

    return () => {
      channel.stopListening('.revenue.updated');
      channel.stopListening('.ticket.scanned');
    };
  }, [onRevenueUpdate, onTicketScanned]);
};
```

---

## 7. Xử lý Ngoại lệ, Độ tin cậy & Single Source of Truth

Hệ thống được thiết kế theo các nguyên tắc an toàn dữ liệu cao cấp:

1. **Transaction-Safe Broadcasting (`DB::afterCommit`)**:
   - Sử dụng `RevenueUpdated::dispatchSafely()` và `TicketScanned::dispatchSafely()`.
   - Sự kiện chỉ được gửi lên WebSocket sau khi Database Transaction đã `COMMIT` thành công 100%. Nếu có lỗi hoặc rollback, không có tin nhắn giả mạo nào bị phát đi.
2. **Không làm nghẽn luồng xử lý chính (Graceful Degradation)**:
   - Toàn bộ quá trình broadcast được bọc trong khối `try-catch (\Throwable $e)`. Nếu WebSocket Server (Reverb/Pusher) gặp sự cố mạng, nghiệp vụ chính (thanh toán, tạo vé, check-in) vẫn hoạt động hoàn hảo và ghi warning log.
3. **Single Source of Truth**:
   - Chỉ tính doanh thu và vé cho các booking có trạng thái `completed` hoặc `paid`.
   - Tuyệt đối không cộng dồn số liệu ở Frontend bằng Javascript mà luôn tính toán trực tiếp từ cơ sở dữ liệu để đảm bảo tính nhất quán giữa tất cả các client.

---

## 8. Hướng dẫn mở rộng tính năng (Developer Next Steps)

Khi cần phát triển thêm các tính năng phân tích nâng cao, đồng nghiệp có thể kế thừa kiến trúc có sẵn như sau:

| Tính năng mở rộng | Vị trí can thiệp | Gợi ý triển khai |
| :--- | :--- | :--- |
| **Top 5 Phim Doanh Thu Cao Nhất** | `ReportService.php` | `join('showtimes')->groupBy('movie_id')->orderByDesc('revenue')` |
| **Thống kê Bán Combo / Bắp nước** | `ReportService.php` | Thống kê từ quan hệ `bookingCombos` |
| **Tỷ lệ Lấp Đầy Ghế (Fill Rate)** | `ReportService.php` | `COUNT(booking_seats) / (COUNT(showtimes) * total_room_seats) * 100` |
| **Xuất Báo Cáo Excel / PDF** | `ReportController.php` | Tái sử dụng dữ liệu từ `getRevenueReport()` và xuất file bằng Laravel Excel |

---

*Tài liệu được cập nhật tự động và lưu trữ tại `docs/ADMIN_DASHBOARD_DOCS.md`.*
