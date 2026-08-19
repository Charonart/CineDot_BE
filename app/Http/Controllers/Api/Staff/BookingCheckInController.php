<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingCombo;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class BookingCheckInController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    /**
     * Tra cứu thông tin vé qua QR / Mã đơn mà chưa cập nhật checked_in_at.
     */
    public function lookupByQr(Request $request)
    {
        $code = $request->input('qr_data', $request->input('qr_code', $request->input('booking_code', $request->input('code'))));
        if (empty($code)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng cung cấp mã QR (qr_data) hoặc mã vé.'
            ], 422);
        }

        $booking = Booking::with([
            'showtime.movie',
            'showtime.room.cinema',
            'user',
            'bookingSeats.showtimeSeat',
            'bookingCombos.combo'
        ])
            ->where('booking_code', $code)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin vé với mã này trong hệ thống.'
            ], 404);
        }

        if (!in_array($booking->booking_status, ['completed', 'paid'])) {
            return response()->json([
                'success' => false,
                'message' => "Đơn đặt vé này đang ở trạng thái '{$booking->booking_status}', chưa hoàn tất thanh toán."
            ], 400);
        }

        $isCheckedIn = $booking->checked_in_at !== null;
        $formattedCheckedIn = $isCheckedIn ? Carbon::parse($booking->checked_in_at)->format('H:i d/m/Y') : null;

        $showtimeStart = $booking->showtime?->showtime_start ? Carbon::parse($booking->showtime->showtime_start) : null;
        $showtimeEnd = $booking->showtime?->showtime_end ? Carbon::parse($booking->showtime->showtime_end) : null;
        $startTime = $showtimeStart ? $showtimeStart->format('H:i') : '';
        $endTime = $showtimeEnd ? $showtimeEnd->format('H:i') : '';
        $showDate = $showtimeStart ? $showtimeStart->format('d/m/Y') : '';

        // Extract seats list
        $seatsList = $booking->bookingSeats->map(function ($bs) {
            $seatCode = '';
            if ($bs->showtimeSeat) {
                $seatCode = $bs->showtimeSeat->row_name . $bs->showtimeSeat->seat_number;
            }
            if (empty($seatCode)) {
                $seatCode = 'Ghế #' . ($bs->showtime_seat_id ?? $bs->booking_seat_id);
            }
            return [
                'seatId'   => $bs->showtime_seat_id ?? $bs->booking_seat_id,
                'seatCode' => $seatCode,
                'price'    => (float) $bs->price,
            ];
        });

        // Extract combos list
        $combosList = $booking->bookingCombos->map(function ($bc) {
            return [
                'bookingComboId' => $bc->booking_combo_id ?? $bc->id,
                'comboName'      => $bc->combo->combo_name ?? 'Combo Bắp Nước',
                'quantity'       => (int) $bc->quantity,
                'isClaimed'      => (bool) $bc->is_claimed,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => $isCheckedIn
                ? "Vé này đã được soát trước đó vào lúc {$formattedCheckedIn}."
                : 'VÉ HỢP LỆ! SẴN SÀNG XÁC NHẬN KHÁCH VÀO PHÒNG.',
            'data'    => [
                'bookingId'     => $booking->booking_id,
                'bookingCode'   => $booking->booking_code,
                'bookingStatus' => $booking->booking_status,
                'isCheckedIn'   => $isCheckedIn,
                'checkedInAt'   => $booking->checked_in_at ? $booking->checked_in_at->toDateTimeString() : null,
                'movieTitle'    => $booking->showtime->movie->title ?? '',
                'moviePoster'   => $booking->showtime->movie->poster_url ?? '',
                'ageRating'     => $booking->showtime->movie->age_rating ?? '',
                'duration'      => $booking->showtime->movie->duration ?? 0,
                'cinemaName'    => $booking->showtime->room->cinema->cinema_name ?? '',
                'roomName'      => $booking->showtime->room->room_name ?? '',
                'roomType'      => $booking->showtime->room->room_type ?? '',
                'showDate'      => $showDate,
                'startTime'     => $startTime,
                'endTime'       => $endTime,
                'customerName'  => $booking->user->fullname ?? $booking->user->name ?? 'Khách vãng lai',
                'customerPhone' => $booking->user->phone ?? '',
                'customerEmail' => $booking->user->email ?? '',
                'finalAmount'   => (float) $booking->final_amount,
                'seats'         => $seatsList,
                'combos'        => $combosList,
            ]
        ]);
    }

    /**
     * Staff scans QR code to check in a booking via POST /staff/check-in.
     */
    public function checkInByQr(Request $request)
    {
        $code = $request->input('qr_data', $request->input('qr_code', $request->input('booking_code', $request->input('code'))));
        if (empty($code)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng cung cấp mã QR (qr_data) hoặc mã vé.'
            ], 422);
        }

        return $this->checkIn($request, $code);
    }

    /**
     * Staff confirms F&B combo claim for customer via POST /staff/fnb/claim.
     */
    public function claimFnb(Request $request)
    {
        $detailId = $request->input('booking_detail_id', $request->input('booking_combo_id'));
        if (empty($detailId)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã booking_detail_id là bắt buộc.'
            ], 422);
        }

        $bookingCombo = BookingCombo::find($detailId);
        if (!$bookingCombo) {
            return response()->json([
                'success' => false,
                'message' => 'Combo bắp nước không tồn tại.'
            ], 404);
        }

        if ($bookingCombo->is_claimed) {
            return response()->json([
                'success' => false,
                'message' => 'Combo bắp nước này đã được nhận trước đó.'
            ], 422);
        }

        $bookingCombo->update(['is_claimed' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Xác nhận trả Combo Bắp Nước thành công (is_claimed = true).'
        ]);
    }

    /**
     * Direct POS counter sale via POST /staff/pos/create-order.
     */
    public function createPosOrder(Request $request)
    {
        $showtimeId = $request->input('showtime_id');
        $showtimeSeatIds = $request->input('showtime_seat_ids', []);
        $combos = $request->input('combos', []);

        $booking = $this->bookingService->holdSeats(
            $request->user()->user_id,
            $showtimeId,
            $showtimeSeatIds,
            $combos
        );

        $confirmed = $this->bookingService->confirmBooking($booking->booking_id);

        return response()->json([
            'success' => true,
            'message' => 'Bán vé & bắp nước tại quầy POS thành công!',
            'data'    => $confirmed
        ], 201);
    }

    /**
     * Staff scans QR code to check in a booking.
     */
    public function checkIn(Request $request, string $code)
    {
        $lockKey = "lock:checkin:{$code}";
        
        try {
            $acquired = Redis::set($lockKey, true, 'NX', 'EX', 5);
            if (!$acquired) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yêu cầu soát vé đang được xử lý, vui lòng thử lại.'
                ], 429);
            }
        } catch (\Exception $e) {
            // Redis optional fallback
        }

        try {
            return DB::transaction(function () use ($request, $code) {
                $booking = Booking::with([
                    'showtime.movie',
                    'showtime.room.cinema',
                    'user',
                    'bookingSeats.showtimeSeat',
                    'bookingCombos.combo'
                ])
                    ->where('booking_code', $code)
                    ->lockForUpdate()
                    ->first();

                if (!$booking) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy thông tin vé với mã soát vé này.'
                    ], 404);
                }

                if (!in_array($booking->booking_status, ['completed', 'paid'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chỉ có thể soát vé cho đơn hàng đã hoàn thành thanh toán (completed).'
                    ], 400);
                }

                if ($booking->checked_in_at !== null) {
                    $formattedTime = Carbon::parse($booking->checked_in_at)->format('H:i d/m/Y');
                    return response()->json([
                        'success' => false,
                        'message' => "Vé này đã được soát trước đó vào lúc {$formattedTime}."
                    ], 400);
                }

                $nowTime = now();
                $booking->update([
                    'checked_in_at' => $nowTime,
                ]);

                $showtimeStart = $booking->showtime?->showtime_start ? Carbon::parse($booking->showtime->showtime_start) : null;
                $showtimeEnd = $booking->showtime?->showtime_end ? Carbon::parse($booking->showtime->showtime_end) : null;
                $startTime = $showtimeStart ? $showtimeStart->format('H:i') : '';
                $endTime = $showtimeEnd ? $showtimeEnd->format('H:i') : '';
                $showDate = $showtimeStart ? $showtimeStart->format('d/m/Y') : '';

                // Extract seat names
                $seatsList = $booking->bookingSeats->map(function ($bs) {
                    $seatCode = '';
                    if ($bs->showtimeSeat) {
                        $seatCode = $bs->showtimeSeat->row_name . $bs->showtimeSeat->seat_number;
                    }
                    if (empty($seatCode)) {
                        $seatCode = 'Ghế #' . ($bs->showtime_seat_id ?? $bs->booking_seat_id);
                    }
                    return [
                        'seatId'   => $bs->showtime_seat_id ?? $bs->booking_seat_id,
                        'seatCode' => $seatCode,
                        'price'    => (float) $bs->price,
                    ];
                });

                // Extract combos list
                $combosList = $booking->bookingCombos->map(function ($bc) {
                    return [
                        'bookingComboId' => $bc->booking_combo_id ?? $bc->id,
                        'comboName'      => $bc->combo->combo_name ?? 'Combo Bắp Nước',
                        'quantity'       => (int) $bc->quantity,
                        'isClaimed'      => (bool) $bc->is_claimed,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'XÁC THỰC VÉ PHIM HỢP LỆ! MỜI KHÁCH VÀO PHÒNG CHIẾU.',
                    'data'    => [
                        'bookingId'     => $booking->booking_id,
                        'bookingCode'   => $booking->booking_code,
                        'bookingStatus' => $booking->booking_status,
                        'isCheckedIn'   => true,
                        'checkedInAt'   => $nowTime->toDateTimeString(),
                        'movieTitle'    => $booking->showtime->movie->title ?? '',
                        'moviePoster'   => $booking->showtime->movie->poster_url ?? '',
                        'ageRating'     => $booking->showtime->movie->age_rating ?? '',
                        'duration'      => $booking->showtime->movie->duration ?? 0,
                        'cinemaName'    => $booking->showtime->room->cinema->cinema_name ?? '',
                        'roomName'      => $booking->showtime->room->room_name ?? '',
                        'roomType'      => $booking->showtime->room->room_type ?? '',
                        'showDate'      => $showDate,
                        'startTime'     => $startTime,
                        'endTime'       => $endTime,
                        'customerName'  => $booking->user->fullname ?? $booking->user->name ?? 'Khách vãng lai',
                        'customerPhone' => $booking->user->phone ?? '',
                        'customerEmail' => $booking->user->email ?? '',
                        'finalAmount'   => (float) $booking->final_amount,
                        'seats'         => $seatsList,
                        'combos'        => $combosList,
                    ]
                ]);
            });
        } finally {
            try {
                Redis::del($lockKey);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Get recently checked-in tickets for live audit.
     */
    public function recentScans(Request $request)
    {
        $limit = $request->get('limit', 15);
        $recent = Booking::with([
            'user',
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.showtimeSeat',
            'bookingCombos.combo'
        ])
            ->whereNotNull('checked_in_at')
            ->orderBy('checked_in_at', 'desc')
            ->limit($limit)
            ->get();

        $formatted = $recent->map(function ($booking) {
            $showtimeStart = $booking->showtime?->showtime_start ? Carbon::parse($booking->showtime->showtime_start) : null;
            $showtimeStr = $showtimeStart ? ($showtimeStart->format('H:i') . ' • ' . $showtimeStart->format('d/m/Y')) : 'Chưa xếp suất';

            $seatsStr = $booking->bookingSeats->map(function ($bs) {
                if ($bs->showtimeSeat) {
                    return $bs->showtimeSeat->row_name . $bs->showtimeSeat->seat_number;
                }
                return '#' . ($bs->showtime_seat_id ?? $bs->booking_seat_id);
            })->implode(', ');

            return [
                'bookingId'     => $booking->booking_id,
                'bookingCode'   => $booking->booking_code,
                'checkedInAt'   => $booking->checked_in_at ? $booking->checked_in_at->toDateTimeString() : null,
                'movieTitle'    => $booking->showtime->movie->title ?? '',
                'moviePoster'   => $booking->showtime->movie->poster_url ?? '',
                'cinemaName'    => $booking->showtime->room->cinema->cinema_name ?? '',
                'roomName'      => $booking->showtime->room->room_name ?? '',
                'showtime'      => $showtimeStr,
                'customerName'  => $booking->user->fullname ?? $booking->user->name ?? 'Khách vãng lai',
                'seats'         => $seatsStr,
                'combosCount'   => $booking->bookingCombos->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $formatted,
        ]);
    }
}
