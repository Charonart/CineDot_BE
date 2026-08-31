@extends('emails.layouts.master')

@section('title', 'Thông báo Hủy vé #' . $booking->booking_code . ' - CineDot')

@section('content')
@php
    $showtime = $booking->showtime;
    $movie = $showtime?->movie;
    $room = $showtime?->room;
    $cinema = $room?->cinema;
    $showtimeStart = $showtime?->showtime_start ? \Carbon\Carbon::parse($showtime->showtime_start) : null;
@endphp

<div style="text-align: center; margin-bottom: 24px;">
    <div style="display: inline-block; background-color: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #F87171; padding: 6px 16px; border-radius: 9999px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
        ✕ Hủy vé thành công
    </div>
    <h1 style="color: #FFFFFF; font-size: 22px; font-weight: 800; margin: 16px 0 6px 0;">XÁC NHẬN HỦY & HOÀN TIỀN VÉ</h1>
    <p style="color: #94A3B8; font-size: 14px; margin: 0;">Xin chào <strong>{{ $booking->user->fullname ?? $booking->user->name ?? 'Quý khách' }}</strong>, yêu cầu hủy vé của bạn đã được xử lý hoàn tất.</p>
</div>

<table width="100%" style="background-color: #0F172A; border: 1px solid #1E293B; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
    <tr>
        <td>
            <div style="color: #64748B; font-size: 11px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">MÃ ĐƠN HÀNG</div>
            <div style="color: #FFFFFF; font-size: 20px; font-weight: 800; font-family: monospace; margin-bottom: 16px;">
                {{ $booking->booking_code }}
            </div>

            <div style="border-top: 1px solid #1E293B; padding-top: 16px;">
                <div style="color: #CBD5E1; font-size: 14px; margin-bottom: 8px;">
                    🎬 Phim: <strong>{{ $movie->title ?? 'Suất Chiếu Phim' }}</strong>
                </div>
                <div style="color: #CBD5E1; font-size: 14px; margin-bottom: 8px;">
                    🏢 Rạp: <strong>{{ $cinema->cinema_name ?? 'CineDot' }}</strong> - Phòng: <strong>{{ $room->room_name ?? 'Standard' }}</strong>
                </div>
                <div style="color: #CBD5E1; font-size: 14px; margin-bottom: 8px;">
                    ⏰ Suất chiếu: <strong>{{ $showtimeStart ? $showtimeStart->format('H:i - d/m/Y') : 'N/A' }}</strong>
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- REFUND DETAILS -->
<table width="100%" style="background-color: #0F172A; border-radius: 12px; padding: 20px; margin-bottom: 24px; border-left: 4px solid #F59E0B;">
    <tr>
        <td>
            <h3 style="color: #FBBF24; font-size: 15px; font-weight: 800; margin: 0 0 12px 0;">CHI TIẾT HOÀN TIỀN</h3>
            <table width="100%">
                <tr>
                    <td style="color: #94A3B8; font-size: 13px; padding-bottom: 6px;">Tỷ lệ hoàn tiền:</td>
                    <td style="text-align: right; color: #FFFFFF; font-weight: 700; font-size: 13px;">{{ $refundPercentage ?? 100 }}%</td>
                </tr>
                <tr>
                    <td style="color: #94A3B8; font-size: 13px; padding-bottom: 6px;">Số tiền hoàn dự kiến:</td>
                    <td style="text-align: right; color: #4ADE80; font-weight: 800; font-size: 15px;">
                        {{ number_format(($booking->final_amount * ($refundPercentage ?? 100)) / 100, 0, ',', '.') }} đ
                    </td>
                </tr>
                <tr>
                    <td style="color: #94A3B8; font-size: 13px;">Thời gian nhận tiền:</td>
                    <td style="text-align: right; color: #94A3B8; font-size: 13px;">1 - 3 ngày làm việc (tùy ngân hàng)</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div style="text-align: center; margin-top: 28px;">
    <a href="{{ env('FRONTEND_URL', 'https://cinedot.vn') }}/movies" class="btn-primary">
        Khám Phá Phim Đang Chiếu Khác
    </a>
</div>
@endsection
