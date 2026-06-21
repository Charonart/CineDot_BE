# Sprint Backlog Issues - Create Using GitHub CLI

Use the following commands to create all 21 missing issues:

```bash
# T01
gh issue create --title "T01 — Xây dựng API Đăng nhập & Đăng ký (Sanctum)" --body "Definition of Done (DoD): API trả về Token hợp lệ khi login/register thành công\n\nMetadata:\n- Story ID: S01\n- Backlog ID: B01\n- Sprint: Sprint 1\n- State: Done\n- Estimate Time (Hours): 1.0" --label "Sprint 1,B01,S01,Done,estimate:1h"

# T02
gh issue create --title "T02 — Tích hợp gửi & xác thực Email" --body "Definition of Done (DoD): Gửi email chứa link xác thực, verify thành công cập nhật DB\n\nMetadata:\n- Story ID: S01\n- Backlog ID: B01\n- Sprint: Sprint 1\n- State: Done\n- Estimate Time (Hours): 1.5" --label "Sprint 1,B01,S01,Done,estimate:1.5h"

# T03
gh issue create --title "T03 — Xây dựng luồng Quên & Đặt lại mật khẩu" --body "Definition of Done (DoD): Gửi email reset, cho phép đổi mật khẩu bằng token hợp lệ\n\nMetadata:\n- Story ID: S01\n- Backlog ID: B01\n- Sprint: Sprint 1\n- State: Done\n- Estimate Time (Hours): 1.0" --label "Sprint 1,B01,S01,Done,estimate:1h"

# T04
gh issue create --title "T04 — Quản lý thông tin hồ sơ (Profile)" --body "Definition of Done (DoD): Lấy thông tin User và cập nhật thành công qua API\n\nMetadata:\n- Story ID: S01\n- Backlog ID: B01\n- Sprint: Sprint 1\n- State: Done\n- Estimate Time (Hours): 0.5" --label "Sprint 1,B01,S01,Done,estimate:0.5h"

# T05
gh issue create --title "T05 — API Danh sách phim cơ bản (Trending, Popular)" --body "Definition of Done (DoD): Lấy đúng danh sách phim theo phân loại, hỗ trợ Search\n\nMetadata:\n- Story ID: S02\n- Backlog ID: B02\n- Sprint: Sprint 1\n- State: Done\n- Estimate Time (Hours): 1.5" --label "Sprint 1,B02,S02,Done,estimate:1.5h"

# T06
gh issue create --title "T06 — API Chi tiết Phim qua định danh Slug" --body "Definition of Done (DoD): Trả về đầy đủ thông tin nội dung, điểm số của phim\n\nMetadata:\n- Story ID: S02\n- Backlog ID: B02\n- Sprint: Sprint 2\n- State: Done\n- Estimate Time (Hours): 0.5" --label "Sprint 2,B02,S02,Done,estimate:0.5h"

# T07
gh issue create --title "T07 — API Quản lý dàn diễn viên & Ekip (Credits)" --body "Definition of Done (DoD): Trả về credits của phim và thông tin người tham gia (Person)\n\nMetadata:\n- Story ID: S02\n- Backlog ID: B02\n- Sprint: Sprint 2\n- State: Done\n- Estimate Time (Hours): 0.5" --label "Sprint 2,B02,S02,Done,estimate:0.5h"

# T08
gh issue create --title "T08 — API Danh sách & Lọc phim theo Thể loại" --body "Definition of Done (DoD): Lấy danh mục thể loại và hiển thị phim theo ID thể loại\n\nMetadata:\n- Story ID: S02\n- Backlog ID: B02\n- Sprint: Sprint 2\n- State: Done\n- Estimate Time (Hours): 0.5" --label "Sprint 2,B02,S02,Done,estimate:0.5h"

# T09
gh issue create --title "T09 — API Video/Trailer & Phim tương tự (Similar)" --body "Definition of Done (DoD): Trả về danh sách link video nhúng và phim có cùng thể loại\n\nMetadata:\n- Story ID: S02\n- Backlog ID: B02\n- Sprint: Sprint 2\n- State: Done\n- Estimate Time (Hours): 0.5" --label "Sprint 2,B02,S02,Done,estimate:0.5h"

# T10
gh issue create --title "T10 — API Danh sách Rạp & Chi tiết rạp (Slug)" --body "Definition of Done (DoD): Trả về danh sách rạp chiếu (Cinemas) và chi tiết thông qua slug\n\nMetadata:\n- Story ID: S03\n- Backlog ID: B03\n- Sprint: Sprint 3\n- State: Done\n- Estimate Time (Hours): 1.0" --label "Sprint 3,B03,S03,Done,estimate:1h"

# T11
gh issue create --title "T11 — API Phân loại rạp đặc biệt & Giá vé" --body "Definition of Done (DoD): Liệt kê các loại rạp (Imax, 4DX..) và bảng giá tương ứng\n\nMetadata:\n- Story ID: S03\n- Backlog ID: B03\n- Sprint: Sprint 3\n- State: Done\n- Estimate Time (Hours): 0.5" --label "Sprint 3,B03,S03,Done,estimate:0.5h"

# T12
gh issue create --title "T12 — API Master Data Tỉnh/Thành phố" --body "Definition of Done (DoD): Trả về danh sách tỉnh/thành để bộ lọc rạp sử dụng\n\nMetadata:\n- Story ID: S03\n- Backlog ID: B03\n- Sprint: Sprint 3\n- State: Done\n- Estimate Time (Hours): 0.5" --label "Sprint 3,B03,S03,Done,estimate:0.5h"

# T13
gh issue create --title "T13 — API Danh sách suất chiếu rạp (Showtimes)" --body "Definition of Done (DoD): Lấy danh sách suất chiếu của rạp theo ngày xác định\n\nMetadata:\n- Story ID: S04\n- Backlog ID: B04\n- Sprint: Sprint 3\n- State: Done\n- Estimate Time (Hours): 1.0" --label "Sprint 3,B04,S04,Done,estimate:1h"

# T14
gh issue create --title "T14 — API Chi tiết suất chiếu & Trạng thái Ghế" --body "Definition of Done (DoD): Trả về layout ghế và trạng thái trống/đã đặt của suất chiếu\n\nMetadata:\n- Story ID: S04\n- Backlog ID: B04\n- Sprint: Sprint 4\n- State: Done\n- Estimate Time (Hours): 1.5" --label "Sprint 4,B04,S04,Done,estimate:1.5h"

# T15
gh issue create --title "T15 — API Layout cấu trúc ghế theo phòng (Rooms)" --body "Definition of Done (DoD): Trả về sơ đồ ghế gốc của từng phòng chiếu\n\nMetadata:\n- Story ID: S04\n- Backlog ID: B04\n- Sprint: Sprint 4\n- State: Done\n- Estimate Time (Hours): 1.0" --label "Sprint 4,B04,S04,Done,estimate:1h"

# T16
gh issue create --title "T16 — Xây dựng tính năng Giữ ghế (Hold Seats)" --body "Definition of Done (DoD): Redis/Cache khóa ghế đang hold tạm thời trong 5-10 phút\n\nMetadata:\n- Story ID: S05\n- Backlog ID: B05\n- Sprint: Sprint 4\n- State: Done\n- Estimate Time (Hours): 2.0" --label "Sprint 4,B05,S05,Done,estimate:2h"

# T17
gh issue create --title "T17 — Lịch sử Đặt vé & Chi tiết đơn vé" --body "Definition of Done (DoD): User xem danh sách booking và thông tin chi tiết từng vé\n\nMetadata:\n- Story ID: S05\n- Backlog ID: B05\n- Sprint: Sprint 5\n- State: Done\n- Estimate Time (Hours): 1.0" --label "Sprint 5,B05,S05,Done,estimate:1h"

# T18
gh issue create --title "T18 — API Danh mục Combo Bắp Nước" --body "Definition of Done (DoD): Trả về danh sách combo (Concessions) để mua kèm khi đặt vé\n\nMetadata:\n- Story ID: S05\n- Backlog ID: B05\n- Sprint: Sprint 5\n- State: Done\n- Estimate Time (Hours): 0.5" --label "Sprint 5,B05,S05,Done,estimate:0.5h"

# T19
gh issue create --title "T19 — Áp dụng (Apply) / Gỡ bỏ (Remove) Voucher" --body "Definition of Done (DoD): Tính lại chính xác tổng tiền sau khi apply/remove mã giảm giá\n\nMetadata:\n- Story ID: S05\n- Backlog ID: B05\n- Sprint: Sprint 5\n- State: Done\n- Estimate Time (Hours): 1.0" --label "Sprint 5,B05,S05,Done,estimate:1h"

# T20
gh issue create --title "T20 — Tích hợp cổng Thanh toán (Payment Gateway)" --body "Definition of Done (DoD): Gọi cổng thanh toán và cập nhật trạng thái đơn (IPN/Callback)\n\nMetadata:\n- Story ID: S06\n- Backlog ID: B06\n- Sprint: Sprint 5\n- State: In Progress\n- Estimate Time (Hours): 2.0" --label "Sprint 5,B06,S06,In Progress,estimate:2h"

# T21
gh issue create --title "T21 — API Tạo bình luận & Đánh giá phim (Reviews)" --body "Definition of Done (DoD): Lưu đánh giá người dùng và tự động tính trung bình rating\n\nMetadata:\n- Story ID: S07\n- Backlog ID: B07\n- Sprint: Sprint 6\n- State: Done\n- Estimate Time (Hours): 1.0" --label "Sprint 6,B07,S07,Done,estimate:1h"
```

Alternatively, you can manually create these issues one by one from the GitHub UI, or I can help you troubleshoot the API creation issue.
