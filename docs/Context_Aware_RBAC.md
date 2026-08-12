# Hướng dẫn sử dụng Hệ thống Phân quyền theo Ngữ cảnh (Context-Aware RBAC)

Hệ thống phân quyền của CineDot đã được nâng cấp lên chuẩn Context-Aware RBAC (tiếp cận 5 bảng). Điều này cho phép chúng ta cấp quyền cho nhân sự không chỉ dựa trên Vai trò (Role) mà còn phụ thuộc vào Địa điểm (Cinema) hoặc Khu vực (Region) mà họ quản lý.

---

## 1. Cơ sở dữ liệu (Database Architecture)
Hệ thống sử dụng bảng trung gian `user_roles` để nối User và Role.
Cấu trúc bảng:
- `user_id`: Định danh người dùng.
- `role_id`: Định danh vai trò (VD: Admin, Manager, Staff).
- `scope_type`: Phạm vi ảnh hưởng của quyền. Cho phép 3 giá trị:
  - `system`: Quyền trên toàn hệ thống (Super Admin).
  - `region`: Quyền áp dụng cho tất cả các rạp trong 1 khu vực (VD: Tỉnh/Thành phố).
  - `cinema`: Quyền chỉ áp dụng tại 1 rạp cụ thể.
- `scope_id`: ID tương ứng của Region hoặc Cinema (để `null` nếu `scope_type` là `system`).

> **Ví dụ thực tế:**
> - User A là Super Admin: `scope_type = 'system'`, `scope_id = null`.
> - User B là Quản lý cụm rạp Miền Bắc: `scope_type = 'region'`, `scope_id = 1` (ID của Miền Bắc).
> - User C là Trưởng rạp CGV Bà Triệu: `scope_type = 'cinema'`, `scope_id = 5` (ID của CGV Bà Triệu).

---

## 2. Cách kiểm tra quyền hành động (Action Authorization)

Hệ thống đã được tích hợp chặt chẽ với tính năng **Gate/Policy** mặc định của Laravel. Ở bất cứ đâu (Controller, Middleware, Blade Template), bạn đều có thể dùng hàm `can` của Laravel.

### Kịch bản 1: Cần check quyền có kèm theo ngữ cảnh (Context)
Ví dụ: User muốn cập nhật thông tin của `$cinema` (rạp A). Ta cần kiểm tra xem họ có quyền `edit-cinema` tại rạp này hay không.

**Trong Controller:**
```php
public function update(Request $request, Cinema $cinema)
{
    // Hệ thống sẽ tự kiểm tra xem User có quyền 'edit-cinema' trên rạp này không
    // (hoặc nếu user là Super Admin / Quản lý vùng chứa rạp này, hàm cũng trả về true)
    if (!auth()->user()->can('edit-cinema', $cinema)) {
        abort(403, 'Bạn không có quyền quản lý rạp này.');
    }

    // Logic update...
}
```

**Trong Blade Template:**
```blade
@can('edit-cinema', $cinema)
    <button>Sửa thông tin Rạp</button>
@endcan
```

### Kịch bản 2: Check quyền hệ thống (Không có ngữ cảnh)
Ví dụ: Nút "Thêm Rạp Mới" (chỉ Super Admin mới làm được).

```php
if (!auth()->user()->can('create-cinema')) {
    abort(403);
}
```

---

## 3. Cách Lọc dữ liệu theo Quyền (Data Scoping)

Đây là tính năng cốt lõi cho trang Admin Dashboard. Khi Quản lý Rạp truy cập trang "Danh sách Lịch chiếu", họ **chỉ được phép thấy lịch chiếu của rạp mình**.

Để làm điều này, bạn sử dụng hàm `$user->getAuthorizedScopeIds($permission, $targetScopeType)` được cung cấp bởi Trait `HasContextRoles`.

**Ví dụ: Lọc Lịch chiếu dựa theo quyền**
```php
use App\Models\Showtime;

public function index()
{
    $user = auth()->user();
    
    // Lấy ra danh sách ID các rạp mà user này có quyền 'view-showtimes'
    // Lưu ý: Hàm này tự động tính toán từ 'system', 'region' xuống 'cinema'
    $allowedCinemaIds = $user->getAuthorizedScopeIds('view-showtimes', 'cinema');

    // Nếu trả về mảng chứa dấu sao ['*'], nghĩa là user có đặc quyền trên toàn hệ thống
    if (in_array('*', $allowedCinemaIds)) {
        $showtimes = Showtime::with(['movie', 'cinema'])->paginate(20);
    } 
    // Ngược lại, chỉ query lịch chiếu thuộc các rạp mà user được phép xem
    else {
        $showtimes = Showtime::with(['movie', 'cinema'])
            ->whereIn('cinema_id', $allowedCinemaIds)
            ->paginate(20);
    }

    return response()->json($showtimes);
}
```

### Cấu trúc hàm `getAuthorizedScopeIds`
- Tham số 1 (`$permissionName`): Tên quyền cần kiểm tra (VD: `view-report`, `manage-booking`).
- Tham số 2 (`$targetScopeType`): Mục tiêu lấy ID (Mặc định là `'cinema'`). Hiện tại hàm hỗ trợ trả về tập hợp các `cinema_id`.

---

## 4. Cách gán quyền cho User (Gợi ý Logic Insert Database)

Khi viết chức năng "Gán vai trò" trên Admin UI, dữ liệu gửi xuống API nên có dạng:
`{ user_id: 1, role_id: 2, scope_type: 'cinema', scope_id: 5 }`

Và bạn dùng DB/Eloquent để insert thẳng vào bảng `user_roles`:
```php
use Illuminate\Support\Facades\DB;

DB::table('user_roles')->insert([
    'user_id' => $request->user_id,
    'role_id' => $request->role_id,
    'scope_type' => $request->scope_type, // 'system', 'region', 'cinema'
    'scope_id' => $request->scope_id // null, hoặc ID tương ứng
]);
```
*(Lưu ý: Bảng này đã được set UNIQUE cho tổ hợp 4 cột trên để chống bị trùng lặp quyền).*
