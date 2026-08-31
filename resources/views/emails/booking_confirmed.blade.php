@extends('emails.layouts.master')

@section('title', 'Vé xem phim điện tử #' . $booking->booking_code . ' - CineDot')

@section('content')
@php
    $showtime = $booking->showtime;
    $movie = $showtime?->movie;
    $room = $showtime?->room;
    $cinema = $room?->cinema;
    $seats = $booking->bookingSeats;
    $combos = $booking->bookingCombos;
    $showtimeStart = $showtime?->showtime_start ? \Carbon\Carbon::parse($showtime->showtime_start) : null;
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($booking->booking_code);
@endphp

<div style="text-align: center; margin-bottom: 24px;">
    <div style="display: inline-block; background-color: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ADE80; padding: 6px 16px; border-radius: 9999px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
        ✓ Thanh toán thành công
    </div>
    <h1 style="color: #FFFFFF; font-size: 22px; font-weight: 800; margin: 16px 0 6px 0;">VÉ XEM PHIM ĐIỆN TỬ (E-TICKET)</h1>
    <p style="color: #94A3B8; font-size: 14px; margin: 0;">Xin chào <strong>{{ $booking->user->fullname ?? $booking->user->name ?? 'Quý khách' }}</strong>, đơn hàng của bạn đã sẵn sàng!</p>
</div>

<!-- E-TICKET CARD -->
<table width="100%" style="background-color: #0F172A; border: 1px dashed #334155; border-radius: 12px; margin-bottom: 24px; overflow: hidden;">
    <!-- MOVIE BANNER & TITLE -->
    <tr>
        <td style="padding: 20px; border-bottom: 1px dashed #334155;">
            <table width="100%">
                <tr>
                    @if($movie && !empty($movie->poster_url))
                    <td width="90" style="vertical-align: top; padding-right: 16px;">
                        <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" width="90" style="border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); display: block;" />
                    </td>
                    @endif
                    <td style="vertical-align: top;">
                        <h2 style="color: #FFFFFF; font-size: 18px; font-weight: 800; margin: 0 0 8px 0; line-height: 1.3;">
                            {{ $movie->title ?? 'Suất Chiếu Phim' }}
                        </h2>
                        @if($movie && !empty($movie->age_rating))
                        <span style="display: inline-block; background-color: #E50914; color: #FFFFFF; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 4px; margin-right: 6px;">
                            {{ $movie->age_rating }}
                        </span>
                        @endif
                        @if($movie && !empty($movie->duration))
                        <span style="color: #94A3B8; font-size: 12px;">{{ $movie->duration }} phút</span>
                        @endif
                        <div style="margin-top: 10px; color: #CBD5E1; font-size: 13px; line-height: 1.5;">
                            <div>🏢 <strong>{{ $cinema->cinema_name ?? 'Rạp CineDot' }}</strong></div>
                            <div>🚪 Phòng: <strong>{{ $room->room_name ?? 'Standard' }}</strong></div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- TIME & SEATS -->
    <tr>
        <td style="padding: 20px; border-bottom: 1px dashed #334155;">
            <table width="100%">
                <tr>
                    <td width="50%" style="vertical-align: top; padding-right: 10px;">
                        <div style="color: #64748B; font-size: 11px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Suất chiếu</div>
                        <div style="color: #F8FAFC; font-size: 15px; font-weight: 800;">
                            {{ $showtimeStart ? $showtimeStart->format('H:i') : '--:--' }}
                        </div>
                        <div style="color: #94A3B8; font-size: 12px;">
                            {{ $showtimeStart ? $showtimeStart->format('d/m/Y') : '' }} ({{ $showtimeStart ? $showtimeStart->isoFormat('dddd') : '' }})
                        </div>
                    </td>
                    <td width="50%" style="vertical-align: top; padding-left: 10px;">
                        <div style="color: #64748B; font-size: 11px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Ghế ngồi</div>
                        <div style="color: #E50914; font-size: 16px; font-weight: 800;">
                            @if($seats->isNotEmpty())
                                {{ $seats->map(fn($s) => ($s->showtimeSeat ? ($s->showtimeSeat->row_name . $s->showtimeSeat->seat_number) : ('#' . $s->showtime_seat_id)))->implode(', ') }}
                            @else
                                Chưa cập nhật
                            @endif
                        </div>
                        <div style="color: #94A3B8; font-size: 12px;">{{ $seats->count() }} Vé</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- COMBOS (IF ANY) -->
    @if($combos && $combos->isNotEmpty())
    <tr>
        <td style="padding: 16px 20px; border-bottom: 1px dashed #334155; background-color: #131C31;">
            <div style="color: #64748B; font-size: 11px; text-transform: uppercase; font-weight: 700; margin-bottom: 6px;">🍿 Combo Bắp Nước Kèm Theo</div>
            @foreach($combos as $c)
            <div style="color: #E2E8F0; font-size: 13px; margin-bottom: 4px;">
                • <strong>{{ $c->combo->combo_name ?? 'Combo' }}</strong> x <strong>{{ $c->quantity }}</strong>
            </div>
            @endforeach
        </td>
    </tr>
    @endif

    <!-- QR CODE & BOOKING CODE -->
    <tr>
        <td style="padding: 24px 20px; text-align: center; background-color: #0B1120;">
            <div style="background-color: #FFFFFF; display: inline-block; padding: 12px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
                <img src="{{ $qrUrl }}" alt="Mã QR Soát Vé {{ $booking->booking_code }}" width="160" height="160" style="display: block;" />
            </div>
            <div style="margin-top: 14px;">
                <div style="color: #64748B; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">MÃ ĐƠN HÀNG</div>
                <div style="color: #FFFFFF; font-size: 24px; font-weight: 900; letter-spacing: 3px; font-family: monospace; margin: 4px 0;">
                    {{ $booking->booking_code }}
                </div>
                <div style="color: #94A3B8; font-size: 12px;">Xuất trình mã QR này tại cổng vào phòng chiếu để nhân viên quét vé.</div>
            </div>
        </td>
    </tr>
</table>

<!-- FINANCIAL SUMMARY -->
<table width="100%" style="background-color: #0F172A; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
    <tr>
        <td style="color: #94A3B8; font-size: 14px;">Tổng số tiền đã thanh toán:</td>
        <td style="text-align: right; color: #4ADE80; font-size: 18px; font-weight: 800;">
            {{ number_format($booking->final_amount, 0, ',', '.') }} đ
        </td>
    </tr>
</table>

<div style="text-align: center; margin-top: 28px;">
    <a href="{{ env('FRONTEND_URL', 'https://cinedot.vn') }}/bookings/history" class="btn-primary">
        Xem Chi Tiết Vé Trên Website
    </a>
</div>
@endsection
