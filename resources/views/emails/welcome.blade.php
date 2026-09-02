@extends('emails.layouts.master')

@section('title', 'Chào mừng bạn đến với CineDot Cinema!')

@section('content')
@php
    $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
@endphp

<!-- HEADER WELCOME STATUS -->
<div style="text-align: center; margin-bottom: 20px;">
    <div style="display: inline-block; background-color: #EEECFB; color: #7C6FE8; padding: 5px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        Tài Khoản Đã Kích Hoạt
    </div>
    <h1 style="color: #0F172A; font-size: 20px; font-weight: 900; margin: 10px 0 4px 0; letter-spacing: -0.3px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        CHÀO MỪNG BẠN ĐẾN VỚI CINEDOT
    </h1>
    <p style="color: #64748B; font-size: 13px; margin: 0; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        Xin chào <strong>{{ $user->fullname ?? $user->name ?? 'Bạn mới' }}</strong>, tài khoản thành viên của bạn đã sẵn sàng trải nghiệm!
    </p>
</div>

<!-- FEATURE HIGHLIGHTS CARD (BORDERLESS) -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #F8FAFC; border-radius: 16px; padding: 20px; margin-bottom: 22px;">
    <tr>
        <td>
            <div style="color: #0F172A; font-size: 12px; font-weight: 800; text-transform: uppercase; margin-bottom: 14px; letter-spacing: 0.5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                🌟 Trải Nghiệm Điện Ảnh Cùng CineDot:
            </div>

            <!-- Point 1 -->
            <div style="margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #EEF2F6;">
                <strong style="color: #0F172A; font-size: 13px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">🎟️ Đặt vé trực tuyến & Nhận mã QR tức thì</strong>
                <p style="color: #64748B; font-size: 12px; margin: 3px 0 0 0; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    Giữ chỗ yêu thích trong thời gian thực, thanh toán an toàn tiện lợi qua VNPAY và nhận ngay vé điện tử QR Code không cần xếp hàng.
                </p>
            </div>

            <!-- Point 2 -->
            <div style="margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #EEF2F6;">
                <strong style="color: #0F172A; font-size: 13px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">⭐ Tích lũy điểm thưởng & Thăng hạng hội viên</strong>
                <p style="color: #64748B; font-size: 12px; margin: 3px 0 0 0; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    Tích lũy điểm thưởng trên từng giao dịch để nâng hạng thành viên và tận hưởng các đặc quyền chiết khấu độc quyền.
                </p>
            </div>

            <!-- Point 3 -->
            <div>
                <strong style="color: #0F172A; font-size: 13px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">🎬 Hệ thống phòng chiếu IMAX Laser & Dolby Atmos</strong>
                <p style="color: #64748B; font-size: 12px; margin: 3px 0 0 0; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    Đắm chìm vào không gian điện ảnh đỉnh cao với màn chiếu IMAX sắc nét cùng hệ thống âm thanh vòm sống động.
                </p>
            </div>
        </td>
    </tr>
</table>

<!-- CTA BUTTON -->
<div style="text-align: center;">
    <a href="{{ $frontendUrl }}/movies" class="btn-primary" style="display: inline-block; background-color: #7C6FE8; color: #FFFFFF !important; padding: 13px 32px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        Khám Phá Phim Đang Chiếu &rarr;
    </a>
</div>
@endsection
