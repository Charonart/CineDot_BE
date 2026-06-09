<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\Request;
use App\Models\Booking;

class PaymentController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    public function process(Request $request)
    {
        $request->validate(['booking_id' => 'required|integer|exists:bookings,booking_id']);
        
        $booking = Booking::where('user_id', $request->user()->user_id)
            ->findOrFail($request->booking_id);

        // Giả lập xử lý thanh toán thành công qua Momo/ZaloPay...
        
        $this->bookingService->confirmBooking($booking->booking_id);

        return response()->json([
            'success' => true,
            'message' => 'Thanh toán đơn hàng thành công!'
        ]);
    }
}
