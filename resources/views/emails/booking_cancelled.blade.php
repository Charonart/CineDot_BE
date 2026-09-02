@extends('emails.layouts.master')

@section('title', 'Thông báo Hủy vé #' . $booking->booking_code . ' - CineDot')

@section('content')
@php
    $showtime = $booking->showtime;
    $movie = $showtime?->movie;
    $room = $showtime?->room;
    $cinema = $room?->cinema;
    $showtimeStart = $showtime?->showtime_start ? \Carbon\Carbon::parse($showtime->showtime_start) : null;
    $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
@endphp

<!-- HEADER STATUS -->
<div style="text-align: center; margin-bottom: 20px;">
    <div style="display: inline-block; background-color: #FEF2F2; color: #DC2626; padding: 5px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        ✕ Hủy vé thành công
    </div>
    <h1 style="color: #0F172A; font-size: 20px; font-weight: 900; margin: 10px 0 4px 0; letter-spacing: -0.3px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        XÁC NHẬN HỦY & HOÀN TIỀN VÉ
    </h1>
    <p style="color: #64748B; font-size: 13px; margin: 0; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        Xin chào <strong>{{ $booking->user->fullname ?? $booking->user->name ?? 'Quý khách' }}</strong>, yêu cầu hủy vé của bạn đã được xử lý hoàn tất.
    </p>
</div>

<!-- CANCELLED DETAILS CARD (BORDERLESS) -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #F8FAFC; border-radius: 16px; padding: 18px 20px; margin-bottom: 16px;">
    <tr>
        <td>
            <div style="color: #64748B; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 3px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                MÃ ĐƠN HÀNG HỦY
            </div>
            <div style="color: #0F172A; font-size: 18px; font-weight: 900; font-family: monospace; margin-bottom: 12px;">
                {{ $booking->booking_code }}
            </div>

            <div style="border-top: 1px solid #EEF2F6; padding-top: 12px; font-size: 13px; line-height: 1.6; color: #334155; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                <div style="margin-bottom: 4px;">
                    🎬 Phim: <strong style="color: #0F172A;">{{ $movie->title ?? 'Suất Chiếu Phim' }}</strong>
                </div>
                <div style="margin-bottom: 4px;">
                    🏢 Rạp: <strong style="color: #0F172A;">{{ $cinema->cinema_name ?? 'CineDot Cinema' }}</strong> &bull; Phòng: <strong>{{ $room->room_name ?? 'Standard' }}</strong>
                </div>
                <div>
                    ⏰ Suất chiếu: <strong style="color: #0F172A;">{{ $showtimeStart ? $showtimeStart->format('H:i - d/m/Y') : 'N/A' }}</strong>
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- REFUND DETAILS CARD -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #FEF8ED; border-radius: 14px; padding: 16px 20px; margin-bottom: 22px;">
    <tr>
        <td>
            <div style="color: #92400E; font-size: 12px; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                💰 Chi Tiết Hoàn Tiền
            </div>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="font-size: 13px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                <tr>
                    <td style="color: #78350F; padding-bottom: 5px;">Tỷ lệ hoàn trả:</td>
                    <td align="right" style="color: #0F172A; font-weight: 800;">{{ $refundPercentage ?? 100 }}%</td>
                </tr>
                <tr>
                    <td style="color: #78350F; padding-bottom: 5px;">Số tiền hoàn dự kiến:</td>
                    <td align="right" style="color: #059669; font-weight: 900; font-size: 16px; font-family: monospace;">
                        {{ number_format(($booking->final_amount * ($refundPercentage ?? 100)) / 100, 0, ',', '.') }} đ
                    </td>
                </tr>
                <tr>
                    <td style="color: #78350F;">Thời gian nhận tiền:</td>
                    <td align="right" style="color: #92400E; font-weight: 600;">1 - 3 ngày làm việc (tùy ngân hàng)</td>
                </tr>
            </table>
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
