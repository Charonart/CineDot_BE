@extends('emails.layouts.master')

@section('title', 'Chúc mừng bạn đã thăng hạng hội viên - CineDot')

@section('content')
@php
    $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
    $tierName = strtoupper($newTier->tier ?? 'VIP');
@endphp

<!-- HEADER STATUS -->
<div style="text-align: center; margin-bottom: 20px;">
    <div style="font-size: 38px; margin-bottom: 8px;">👑✨</div>
    <div style="display: inline-block; background-color: #FEF3C7; color: #D97706; padding: 5px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        Thăng hạng thành công
    </div>
    <h1 style="color: #0F172A; font-size: 20px; font-weight: 900; margin: 10px 0 4px 0; letter-spacing: -0.3px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        CHÚC MỪNG BẠN LÊN HẠNG {{ $tierName }}!
    </h1>
    <p style="color: #64748B; font-size: 13px; margin: 0; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        Xin chào <strong>{{ $user->fullname ?? $user->name ?? 'Quý khách' }}</strong>, cảm ơn bạn đã luôn tin tưởng và đồng hành cùng CineDot!
    </p>
</div>

<!-- TIER PRIVILEGES CARD (BORDERLESS) -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #F8FAFC; border-radius: 16px; padding: 20px; margin-bottom: 22px;">
    <tr>
        <td>
            <!-- Tier Highlight Box -->
            <div style="text-align: center; padding-bottom: 16px; border-bottom: 1px solid #EEF2F6;">
                <div style="color: #64748B; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    HẠNG HỘI VIÊN MỚI
                </div>
                <div style="color: #7C6FE8; font-size: 26px; font-weight: 900; letter-spacing: 2px; margin: 4px 0; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    {{ $tierName }}
                </div>
                <div style="color: #475569; font-size: 13px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    Tổng điểm tích lũy: <strong style="color: #059669; font-size: 14px;">{{ number_format($user->total_points, 0, ',', '.') }} Điểm</strong>
                </div>
            </div>

            <!-- Privileges list -->
            <div style="padding-top: 16px;">
                <div style="color: #0F172A; font-size: 12px; font-weight: 800; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    🎁 ĐẶC QUYỀN DÀNH RIÊNG CHO BẠN:
                </div>

                @if($newTier && $newTier->discount_percent > 0)
                <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 10px;">
                    <tr>
                        <td width="28" style="vertical-align: top; font-size: 15px;">⭐</td>
                        <td style="vertical-align: top; font-size: 13px; color: #334155; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            <strong>Chiết khấu độc quyền:</strong> Giảm ngay <strong style="color: #7C6FE8; font-size: 14px;">{{ $newTier->discount_percent }}%</strong> trên mọi đơn đặt vé tại CineDot.
                        </td>
                    </tr>
                </table>
                @endif

                <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 10px;">
                    <tr>
                        <td width="28" style="vertical-align: top; font-size: 15px;">🍿</td>
                        <td style="vertical-align: top; font-size: 13px; color: #334155; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            <strong>Ưu tiên bắp nước:</strong> Nhận các phần quà và combo ưu đãi đặc biệt theo chương trình hội viên.
                        </td>
                    </tr>
                </table>

                <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td width="28" style="vertical-align: top; font-size: 15px;">⚡</td>
                        <td style="vertical-align: top; font-size: 13px; color: #334155; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            <strong>Đặt vé sớm (Early Bird):</strong> Đặt trước vé các suất chiếu bom tấn và sự kiện đặc biệt.
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<!-- CTA BUTTON -->
<div style="text-align: center;">
    <a href="{{ $frontendUrl }}/profile" class="btn-primary" style="display: inline-block; background-color: #7C6FE8; color: #FFFFFF !important; padding: 13px 32px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        Xem Hồ Sơ & Quyền Lợi &rarr;
    </a>
</div>
@endsection
