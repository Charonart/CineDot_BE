Cách giải quyết bài toán này ở các hệ thống lớn dựa trên một nguyên tắc tối thượng: Tách biệt hoàn toàn Dữ liệu Tĩnh (Static Layout) và Dữ liệu Động (Dynamic Status).
Nếu bạn gộp chung tọa độ ghế và trạng thái ghế vào một API, hệ thống sẽ sập ngay lập tức khi có hàng ngàn người F5 liên tục để săn vé. Dưới đây là kiến trúc chuẩn để fetch và xử lý data cho mô hình Tọa độ (Coordinate Model).

1. Luồng thiết kế API (Tách Tĩnh - Động)
   Thay vì gọi 1 API nặng nề, Frontend sẽ gọi 2 nguồn dữ liệu hoàn toàn độc lập và "merge" (trộn) chúng lại ở phía Client.
   API 1: Lấy Tọa độ rạp (Dữ liệu Tĩnh)
   Tần suất: Gọi 1 lần duy nhất, cache lại trên LocalStorage hoặc CDN. Rất ít khi thay đổi (chỉ đổi khi rạp đập đi xây lại).

Endpoint: GET [https://cdn.yourcinema.com/layouts/room_1.json](https://cdn.yourcinema.com/layouts/room_1.json) (hoặc GET /api/rooms/1/layout)

Payload (JSON): Chỉ chứa ID ghế, Loại ghế và Tọa độ (X, Y, Góc).

JSON
[
{ "id": "A1", "type": "STD", "cx": 10, "cy": 20, "angle": 0 },
{ "id": "A2", "type": "STD", "cx": 30, "cy": 20, "angle": 0 },
{ "id": "C1", "type": "VIP", "cx": 10, "cy": 60, "angle": 5 }
]

API 2: Lấy Trạng thái ghế của Suất chiếu (Dữ liệu Động)
Tần suất: Gọi liên tục (Polling mỗi 3-5 giây) hoặc qua WebSocket. Dữ liệu thay đổi từng mili-giây.

Endpoint: GET /api/showtimes/105/seat-status

Thiết kế Payload Tối ưu: Tuyệt đối không trả về mảng (Array) mà phải trả về một Object/Map (Key-Value). Key là seat_id, Value là status. Việc này giúp Frontend lookup dữ liệu với độ phức tạp O(1).

JSON
{
"A1": "BOOKED",
"A2": "HOLD",
"C1": "AVAILABLE"
}

(Lưu ý: "HOLD" là trạng thái có người đang chọn ghế và đang ở bước thanh toán nhưng chưa thanh toán xong, hệ thống khóa ghế này lại trong 5-10 phút). 2. Frontend Merge Dữ liệu (Dành cho React/Next.js)
Khi người dùng bấm vào xem sơ đồ ghế của suất chiếu 105, luồng xử lý trên Frontend của bạn sẽ diễn ra như sau:
Dùng Promise.all để fetch song song API Layout và API Status.
Lấy mảng Layout làm gốc để vẽ vòng lặp (Map).
Với mỗi ghế trong Layout, dùng seat.id để soi vào Object Status lấy ra màu sắc/trạng thái tương ứng.
Dưới đây là mô phỏng logic bằng React:
JavaScript
import { useEffect, useState } from 'react';

export default function SeatMap({ showtimeId, roomId }) {
const [layout, setLayout] = useState([]);
const [seatStatuses, setSeatStatuses] = useState({});

useEffect(() => {
// 1. Fetch song song Tĩnh và Động
const fetchSeatData = async () => {
const [layoutRes, statusRes] = await Promise.all([
fetch(`https://cdn.cinema.com/layouts/room_${roomId}.json`),
fetch(`/api/showtimes/${showtimeId}/seat-status`)
]);

      setLayout(await layoutRes.json());
      setSeatStatuses(await statusRes.json());
    };

    fetchSeatData();

    // 2. Tối ưu: Polling API status mỗi 5 giây để cập nhật trạng thái
    const interval = setInterval(async () => {
        const res = await fetch(`/api/showtimes/${showtimeId}/seat-status`);
        setSeatStatuses(await res.json());
    }, 5000);

    return () => clearInterval(interval);

}, [showtimeId, roomId]);

// Hàm quyết định màu sắc dựa trên Trạng thái (từ API 2) và Loại ghế (từ API 1)
const getSeatColor = (seatId, seatType) => {
// O(1) Lookup: Tìm trạng thái của ghế trong object seatStatuses
const status = seatStatuses[seatId] || 'AVAILABLE';

    if (status === 'BOOKED') return 'gray';      // Đã bán
    if (status === 'HOLD') return 'orange';      // Đang bị giữ

    // Nếu AVAILABLE, tô màu theo loại ghế
    if (seatType === 'VIP') return 'red';
    return 'blue'; // STD

};

return (
<svg width="800" height="600" viewBox="0 0 800 600">
{layout.map((seat) => (
<g
key={seat.id}
transform={`translate(${seat.cx}, ${seat.cy}) rotate(${seat.angle})`} >
<rect
width="20" height="20" rx="4"
fill={getSeatColor(seat.id, seat.type)}
onClick={() => handleSelectSeat(seat.id)}
style={{ cursor: seatStatuses[seat.id] === 'AVAILABLE' ? 'pointer' : 'not-allowed' }}
/>
<text x="10" y="15" fontSize="8" textAnchor="middle" fill="white">
{seat.id}
</text>
</g>
))}
</svg>
);
}

3. Nâng cấp hệ thống (Enterprise Scale)
   Đoạn code trên hoạt động rất tốt, nhưng nếu bạn muốn đưa hệ thống này đạt chuẩn như các sàn vé lớn, Backend của bạn cần thêm 2 lớp kiến trúc:
   Lớp 1: Caching API Status bằng Redis: API /seat-status bị gọi hàng trăm lần mỗi giây. Backend (NestJS) tuyệt đối không được query trực tiếp vào bảng showtime_seats trong PostgreSQL/MySQL. Thay vào đó, mỗi khi có một ghế đổi trạng thái, Backend phải cập nhật ngay vào một Hash map trong Redis. API /seat-status chỉ việc đọc trực tiếp từ Redis trả về.

Lớp 2: Giao tiếp thời gian thực (WebSockets / SSE): Thay vì dùng setInterval để polling (gây tốn băng thông và delay), bạn nên mở một kết nối WebSocket (Socket.io trong NestJS). Bất cứ khi nào User A bấm chọn ghế A1, Backend bắn broadcast event SEAT_HOLD_A1 qua Socket. App của User B và User C sẽ nhận được event này trong vòng 50ms và lập tức chuyển ghế A1 trên màn hình của họ sang màu cam, không cho phép bấm vào nữa. Tránh tuyệt đối tình trạng "Booking Conflict" (2 người cùng thanh toán 1 ghế).
