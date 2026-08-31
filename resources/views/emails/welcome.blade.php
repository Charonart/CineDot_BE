@extends('emails.layouts.master')

@section('title', 'Chào mừng bạn đến với CineDot Cinema!')

@section('content')
<div style="text-align: center; margin-bottom: 28px;">
    <div style="font-size: 40px; margin-bottom: 12px;">🍿✨</div>
    <h1 style="color: #FFFFFF; font-size: 22px; font-weight: 900; margin: 0 0 8px 0; letter-spacing: 0.5px;">
        CHÀO MỪNG BẠN ĐẾN VỚI CINEDOT!
    </h1>
    <p style="color: #94A3B8; font-size: 15px; margin: 0;">
        Xin chào <strong>{{ $user->fullname ?? $user->name ?? 'Bạn mới' }}</strong>, tài khoản của bạn đã được kích hoạt thành công.
    </p>
</div>

<!-- FEATURE HIGHLIGHTS -->
<table width="100%" style="background-color: #0F172A; border: 1px solid #1E293B; border-radius: 12px; padding: 24px; margin-bottom: 28px;">
    <tr>
        <td>
            <h3 style="color: #E2E8F0; font-size: 16px; font-weight: 800; margin: 0 0 16px 0;">
                TRẢI NGHIỆM ĐIỆN ẢNH ĐỈNH CAO TẠI CINEDOT
            </h3>
            
            <table width="100%" style="margin-bottom: 16px;">
                <tr>
                    <td width="36" style="vertical-align: top; font-size: 20px;">🎟️</td>
                    <td style="vertical-align: top; padding-left: 8px;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Đặt vé siêu tốc & Chọn ghế chuẩn xác</strong>
                        <p style="color: #94A3B8; font-size: 13px; margin: 4px 0 0 0; line-height: 1.4;">
                            Giữ ghế yêu thích ngay trong thời gian thực, thanh toán linh hoạt qua VNPay và nhận ngay vé điện tử E-Ticket không cần xếp hàng.
                        </p>
                    </td>
                </tr>
            </table>

            <table width="100%" style="margin-bottom: 16px;">
                <tr>
                    <td width="36" style="vertical-align: top; font-size: 20px;">⭐</td>
                    <td style="vertical-align: top; padding-left: 8px;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Tích điểm & Thăng hạng Hội viên</strong>
                        <p style="color: #94A3B8; font-size: 13px; margin: 4px 0 0 0; line-height: 1.4;">
                            Mỗi 10.000 VNĐ chi tiêu tích lũy ngay 1 Điểm thưởng để thăng hạng Vàng, Bạch Kim và nhận các đặc quyền chiết khấu độc quyền.
                        </p>
                    </td>
                </tr>
            </table>

            <table width="100%">
                <tr>
                    <td width="36" style="vertical-align: top; font-size: 20px;">🎬</td>
                    <td style="vertical-align: top; padding-left: 8px;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Phòng chiếu Hiện đại & Đa dạng</strong>
                        <p style="color: #94A3B8; font-size: 13px; margin: 4px 0 0 0; line-height: 1.4;">
                            Trải nghiệm âm thanh Dolby Atmos, màn chiếu IMAX cùng dàn ghế đôi Sweetbox và ghế da VIP êm ái.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div style="text-align: center;">
    <a href="{{ env('FRONTEND_URL', 'https://cinedot.vn') }}/movies" class="btn-primary">
        Khám Phá Phim Đang Chiếu Ngay
    </a>
</div>
@endsection
