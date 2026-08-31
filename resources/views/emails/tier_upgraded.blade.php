@extends('emails.layouts.master')

@section('title', 'Chúc mừng bạn đã thăng hạng hội viên - CineDot')

@section('content')
<div style="text-align: center; margin-bottom: 28px;">
    <div style="font-size: 48px; margin-bottom: 12px;">👑✨</div>
    <div style="display: inline-block; background-color: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.3); color: #FACC15; padding: 6px 18px; border-radius: 9999px; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
        Thăng hạng thành công
    </div>
    <h1 style="color: #FFFFFF; font-size: 22px; font-weight: 900; margin: 16px 0 8px 0;">
        CHÚC MỪNG BẠN LÊN HẠNG {{ strtoupper($newTier->tier ?? 'VIP') }}!
    </h1>
    <p style="color: #94A3B8; font-size: 15px; margin: 0;">
        Xin chào <strong>{{ $user->fullname ?? $user->name ?? 'Quý khách' }}</strong>, cảm ơn bạn đã tích cực gắn bó cùng CineDot.
    </p>
</div>

<!-- TIER PRIVILEGES CARD -->
<table width="100%" style="background-color: #0F172A; border: 1px solid #1E293B; border-radius: 12px; padding: 24px; margin-bottom: 28px; border-top: 3px solid #E50914;">
    <tr>
        <td>
            <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px dashed #334155;">
                <div style="color: #64748B; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">Hạng hội viên mới của bạn</div>
                <div style="color: #FACC15; font-size: 28px; font-weight: 900; letter-spacing: 2px; margin: 6px 0;">
                    {{ strtoupper($newTier->tier ?? 'VIP') }}
                </div>
                <div style="color: #CBD5E1; font-size: 14px;">
                    Tổng điểm tích lũy: <strong style="color: #4ADE80;">{{ number_format($user->total_points, 0, ',', '.') }} Điểm</strong>
                </div>
            </div>

            <div style="padding-top: 20px;">
                <h3 style="color: #FFFFFF; font-size: 15px; font-weight: 800; margin: 0 0 12px 0;">
                    🎁 ĐẶC QUYỀN DÀNH RIÊNG CHO BẠN:
                </h3>
                @if($newTier && $newTier->discount_percent > 0)
                <div style="color: #E2E8F0; font-size: 14px; margin-bottom: 10px;">
                    ⭐ <strong>Chiết khấu độc quyền:</strong> Giảm ngay <strong style="color: #E50914; font-size: 16px;">{{ $newTier->discount_percent }}%</strong> trên mọi đơn đặt vé tại CineDot.
                </div>
                @endif
                <div style="color: #E2E8F0; font-size: 14px; margin-bottom: 10px;">
                    🍿 <strong>Ưu tiên bắp nước:</strong> Nhận các phần quà và combo ưu đãi theo chương trình hội viên.
                </div>
                <div style="color: #E2E8F0; font-size: 14px;">
                    ⚡ <strong>Đặt vé sớm (Early Bird):</strong> Đặt vé các suất chiếu bom tấn và sự kiện đặc biệt trước ngày công chiếu chính thức.
                </div>
            </div>
        </td>
    </tr>
</table>

<div style="text-align: center;">
    <a href="{{ env('FRONTEND_URL', 'https://cinedot.vn') }}/users/profile" class="btn-primary">
        Xem Hồ Sơ & Quyền Lợi Của Bạn
    </a>
</div>
@endsection
