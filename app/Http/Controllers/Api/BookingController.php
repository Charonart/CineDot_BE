<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HoldSeatsRequest;
use App\Services\BookingService;
use Illuminate\Http\Request;
use App\Models\Booking;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    public function holdSeats(HoldSeatsRequest $request)
    {
        $combos = $request->input('combos', []);
        
        $booking = $this->bookingService->holdSeats(
            $request->user()->user_id,
            $request->schedule_id,
            $request->schedule_seat_ids,
            $combos
        );

        return response()->json([
            'success' => true,
            'message' => 'Giữ ghế thành công, vui lòng thanh toán trong 10 phút.',
            'data'    => $booking
        ]);
    }

    public function show($id, Request $request)
    {
        $booking = Booking::with(['schedule.movie', 'bookingSeats.scheduleSeat.seat', 'schedule.room.cinema', 'bookingCombos.combo', 'voucher'])
            ->where('user_id', $request->user()->user_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $booking
        ]);
    }

    public function myBookings(Request $request)
    {
        $bookings = Booking::with(['schedule.movie', 'schedule.room.cinema', 'bookingCombos.combo', 'voucher'])
            ->where('user_id', $request->user()->user_id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $bookings
        ]);
    }
}
