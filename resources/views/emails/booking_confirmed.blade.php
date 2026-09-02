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
    $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
@endphp

<!-- HEADER STATUS -->
<div style="text-align: center; margin-bottom: 20px;">
    <div style="display: inline-block; background-color: #ECFDF5; color: #059669; padding: 5px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        Thanh toán thành công
    </div>
    <h1 style="color: #0F172A; font-size: 20px; font-weight: 900; margin: 10px 0 4px 0; letter-spacing: -0.3px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        VÉ XEM PHIM ĐIỆN TỬ
    </h1>
    <p style="color: #64748B; font-size: 13px; margin: 0; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        Xin chào <strong>{{ $booking->user->fullname ?? $booking->user->name ?? 'Quý khách' }}</strong>, đơn đặt vé của bạn đã được xác nhận thành công.
    </p>
</div>

<!-- DIGITAL TICKET CONTAINER (SEAMLESS BORDERLESS CARD) -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #FAFAFC; border-radius: 16px; overflow: hidden; margin-bottom: 20px;">
    <!-- 1. CINEMATIC OBSIDIAN HEADER -->
    <tr>
        <td style="background-color: #0F172A; padding: 18px 20px; color: #FFFFFF;">
            <!-- Top Tag & Booking Code Row -->
            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom: 12px;">
                <tr>
                    <td align="left">
                        <span style="display: inline-block; background-color: #7C6FE8; color: #FFFFFF; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            {{ $room->room_type ?? 'IMAX Laser' }}
                        </span>
                        @if($movie && !empty($movie->age_rating))
                        <span style="display: inline-block; background-color: rgba(245, 158, 11, 0.25); color: #FDE68A; font-size: 10px; font-weight: 700; padding: 3px 7px; border-radius: 4px; margin-left: 4px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            {{ $movie->age_rating }}
                        </span>
                        @endif
                    </td>
                    <td align="right">
                        <span style="display: inline-block; background-color: rgba(255, 255, 255, 0.12); color: #FDE68A; font-size: 11px; font-weight: 800; font-family: monospace; padding: 3px 8px; border-radius: 6px;">
                            #{{ $booking->booking_code }}
                        </span>
                    </td>
                </tr>
            </table>

            <!-- Movie Poster & Venue Details -->
            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    @if($movie && !empty($movie->poster_url))
                    <td width="64" style="vertical-align: top; padding-right: 14px;">
                        <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" width="64" style="border-radius: 8px; display: block;" />
                    </td>
                    @endif
                    <td style="vertical-align: middle;">
                        <h2 style="color: #FFFFFF; font-size: 16px; font-weight: 800; margin: 0 0 6px 0; line-height: 1.35; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            {{ $movie->title ?? 'Suất Chiếu Phim CineDot' }}
                        </h2>
                        <div style="color: #CBD5E1; font-size: 12px; line-height: 1.5; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            <div>🏢 <strong>{{ $cinema->cinema_name ?? 'CineDot Cinema' }}</strong></div>
                            <div style="color: #94A3B8;">🚪 {{ $room->room_name ?? 'Phòng chiếu chính' }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- 2. SHOWTIME & SEATS ROW -->
    <tr>
        <td style="padding: 14px 16px; background-color: #FFFFFF;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td width="48%" style="vertical-align: top; background-color: #F8FAFC; border-radius: 12px; padding: 12px 14px;">
                        <div style="color: #64748B; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            Suất Chiếu
                        </div>
                        <div style="color: #0F172A; font-size: 15px; font-weight: 900; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            {{ $showtimeStart ? $showtimeStart->format('H:i') : '--:--' }}
                        </div>
                        <div style="color: #64748B; font-size: 11px; margin-top: 2px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            {{ $showtimeStart ? $showtimeStart->format('d/m/Y') : '' }}
                        </div>
                    </td>
                    <td width="4%"></td>
                    <td width="48%" style="vertical-align: top; background-color: #F3F1FD; border-radius: 12px; padding: 12px 14px;">
                        <div style="color: #7C6FE8; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            Ghế Ngồi
                        </div>
                        <div style="color: #7C6FE8; font-size: 15px; font-weight: 900; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            @if($seats->isNotEmpty())
                                {{ $seats->map(fn($s) => ($s->showtimeSeat ? ($s->showtimeSeat->row_name . $s->showtimeSeat->seat_number) : ('#' . $s->showtime_seat_id)))->implode(', ') }}
                            @else
                                Đã xác nhận
                            @endif
                        </div>
                        <div style="color: #685BC7; font-size: 11px; margin-top: 2px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                            {{ $seats->count() }} Ghế đã chọn
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- 3. COMBOS (IF ANY) -->
    @if($combos && $combos->isNotEmpty())
    <tr>
        <td style="padding: 0 16px 14px 16px; background-color: #FFFFFF;">
            <div style="background-color: #FEF8ED; border-radius: 12px; padding: 10px 14px;">
                <div style="color: #92400E; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    🍿 Bắp nước kèm theo:
                </div>
                @foreach($combos as $c)
                <div style="color: #78350F; font-size: 12px; line-height: 1.4; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    &bull; {{ $c->combo->combo_name ?? 'Combo Bắp Nước' }} &times; <strong>{{ $c->quantity }}</strong>
                </div>
                @endforeach
            </div>
        </td>
    </tr>
    @endif

    <!-- 4. QR CODE CHECK-IN STUB -->
    <tr>
        <td align="center" style="padding: 16px 16px 20px 16px; text-align: center; background-color: #FAFAFC; border-top: 1px dashed #E2E8F0;">
            <div style="display: inline-block; background-color: #FFFFFF; padding: 10px; border-radius: 12px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);">
                <img src="{{ $qrUrl }}" alt="QR Code {{ $booking->booking_code }}" width="130" height="130" style="display: block; margin: 0 auto;" />
            </div>
            <div style="margin-top: 10px;">
                <div style="color: #64748B; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    MÃ ĐẶT VÉ
                </div>
                <div style="color: #0F172A; font-size: 18px; font-weight: 900; letter-spacing: 1.5px; font-family: monospace; margin: 2px 0;">
                    {{ $booking->booking_code }}
                </div>
                <div style="color: #94A3B8; font-size: 11px; max-width: 320px; margin: 0 auto; line-height: 1.4; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
                    Vui lòng xuất trình mã QR này tại cửa soát vé hoặc quầy bắp nước trước giờ chiếu 15 phút.
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- FINANCIAL SUMMARY TABLE -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #F8FAFC; border-radius: 12px; padding: 14px 16px; margin-bottom: 22px;">
    <tr>
        <td style="color: #64748B; font-size: 12px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">Phương thức thanh toán:</td>
        <td align="right" style="color: #0F172A; font-size: 12px; font-weight: 700; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
            {{ $booking->payment_method ?? 'Cổng VNPAY' }}
        </td>
    </tr>
    @if($booking->discount_amount > 0)
    <tr>
        <td style="color: #64748B; font-size: 12px; padding-top: 5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">Ưu đãi giảm giá:</td>
        <td align="right" style="color: #EF4444; font-size: 12px; font-weight: 700; padding-top: 5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
            -{{ number_format($booking->discount_amount, 0, ',', '.') }} đ
        </td>
    </tr>
    @endif
    <tr>
        <td style="color: #0F172A; font-size: 13px; font-weight: 800; padding-top: 8px; border-top: 1px solid #EEF2F6; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">Tổng thanh toán:</td>
        <td align="right" style="color: #7C6FE8; font-size: 16px; font-weight: 900; font-family: monospace; padding-top: 8px; border-top: 1px solid #EEF2F6;">
            {{ number_format($booking->final_amount, 0, ',', '.') }} đ
        </td>
    </tr>
</table>

<!-- CTA BUTTON -->
<div style="text-align: center;">
    <a href="{{ $frontendUrl }}/profile?tab=tickets" class="btn-primary" style="display: inline-block; background-color: #7C6FE8; color: #FFFFFF !important; padding: 13px 32px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;">
        Xem Vé Của Tôi &rarr;
    </a>
</div>
@endsection
