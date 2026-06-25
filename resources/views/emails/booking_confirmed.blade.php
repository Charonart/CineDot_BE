<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Xác nhận đặt vé thành công</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h2 {
            color: #d32f2f;
            text-align: center;
            border-bottom: 2px solid #eeeeee;
            padding-bottom: 10px;
        }
        .info-group {
            margin-bottom: 15px;
        }
        .info-group strong {
            display: inline-block;
            width: 150px;
            color: #555555;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #999999;
            border-top: 1px solid #eeeeee;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cảm ơn bạn đã đặt vé tại CineDot!</h2>
        
        <p>Xin chào <strong>{{ $booking->user->name ?? 'bạn' }}</strong>,</p>
        <p>Giao dịch mua vé của bạn đã được thanh toán thành công. Dưới đây là thông tin vé của bạn:</p>

        <div style="background-color: #fdfdfd; border: 1px solid #eeeeee; padding: 15px; border-radius: 5px;">
            <div class="info-group">
                <strong>Mã đơn hàng:</strong> <span style="color: #d32f2f; font-weight: bold; font-size: 18px;">{{ $booking->booking_code }}</span>
            </div>
            <div class="info-group">
                <strong>Phim:</strong> {{ $booking->schedule->movie->title ?? 'N/A' }}
            </div>
            <div class="info-group">
                <strong>Rạp chiếu:</strong> {{ $booking->schedule->room->cinema->name ?? 'N/A' }} - Phòng: {{ $booking->schedule->room->name ?? 'N/A' }}
            </div>
            <div class="info-group">
                <strong>Giờ chiếu:</strong> {{ \Carbon\Carbon::parse($booking->schedule->start_time)->format('H:i d/m/Y') }}
            </div>
            <div class="info-group">
                <strong>Tổng tiền:</strong> <span style="color: #4CAF50; font-weight: bold;">{{ number_format($booking->total_amount, 0, ',', '.') }} VNĐ</span>
            </div>
        </div>

        <p style="margin-top: 20px;">Vui lòng đưa mã đơn hàng này cho nhân viên tại quầy vé để lấy vé cứng trước khi vào rạp.</p>
        
        <div class="footer">
            <p>Đây là email tự động, vui lòng không trả lời email này.</p>
            <p>&copy; {{ date('Y') }} CineDot. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
