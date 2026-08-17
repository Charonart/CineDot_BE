<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    public function createUrl(PaymentRequest $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key', (string) \Illuminate\Support\Str::uuid());
        $userId = $request->user()->user_id;

        $bookingQuery = Booking::where('user_id', $userId);
        if ($request->has('booking_id')) {
            $bookingQuery->where('booking_id', $request->input('booking_id'));
        } elseif ($request->has('booking_code')) {
            $bookingQuery->where('booking_code', $request->input('booking_code'));
        }
        
        $booking = $bookingQuery->first();
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn đặt vé hợp lệ.'
            ], 404);
        }

        if (in_array($booking->booking_status, ['completed', 'paid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này đã được thanh toán thành công.'
            ], 400);
        }

        // Check hold TTL expiration
        $ttlSeconds = (int) env('HOLD_SEAT_EXPIRE_SECONDS', 600);
        $expiresAt = \Carbon\Carbon::parse($booking->created_at)->addSeconds($ttlSeconds);

        if (\Carbon\Carbon::now()->greaterThan($expiresAt)) {
            $booking->update(['booking_status' => 'cancelled']);
            $seatIds = \App\Models\BookingSeat::where('booking_id', $booking->booking_id)->pluck('showtime_seat_id')->toArray();
            $this->bookingService->releaseSeats($userId, $booking->showtime_id, $seatIds);

            return response()->json([
                'success' => false,
                'message' => 'Đơn đặt vé đã quá thời gian giữ ghế (' . (int) ceil($ttlSeconds / 60) . ' phút). Vui lòng chọn ghế và đặt lại.'
            ], 400);
        }

        if ($request->has('combos') || $request->has('voucher_code')) {
            $combos = $request->input('combos', []);
            $voucherCode = $request->input('voucher_code');
            $showtimeSeatIds = \App\Models\BookingSeat::where('booking_id', $booking->booking_id)->pluck('showtime_seat_id')->toArray();
            
            $snapshot = app(\App\Services\PricingEngineService::class)->calculateSummary(
                $booking->showtime_id,
                $showtimeSeatIds,
                $combos,
                $voucherCode,
                $request->user()
            );

            $booking->update([
                'price_breakdown' => $snapshot,
                'final_amount'    => $snapshot['financial_breakdown']['final_amount_to_pay'],
                'discount_amount' => $snapshot['financial_breakdown']['total_discount_amount'],
                'voucher_id'      => $snapshot['voucher_id'] ?? null,
            ]);

            \App\Models\BookingCombo::where('booking_id', $booking->booking_id)->delete();
            foreach ($snapshot['items']['combos'] as $cItem) {
                \App\Models\BookingCombo::create([
                    'booking_id'       => $booking->booking_id,
                    'combo_id'         => $cItem['combo_id'],
                    'quantity'         => $cItem['quantity'],
                    'price_at_booking' => $cItem['unit_price'],
                    'is_claimed'       => false,
                ]);
            }
        }

        $paymentMethod = strtoupper($request->input('payment_method', 'VNPAY'));
        $amount = (float) ($booking->final_amount ?? $booking->total_amount);

        $vnp_Url = config('services.vnpay.url') ?: 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
        $vnp_Returnurl = config('services.vnpay.return_url') ?: 'http://localhost:8000/api/v1/payments/vnpay/return';
        $vnp_TmnCode = config('services.vnpay.tmn_code') ?: 'X30Z4K1B';
        $vnp_HashSecret = config('services.vnpay.hash_secret') ?: 'GCCOFZVEFCGWUBXFNOVSPYEDLZHFMWWG';

        $vnp_TxnRef = $booking->booking_code;
        $vnp_OrderInfo = 'Thanh toan don hang ' . $vnp_TxnRef;
        $vnp_Amount = intval(round($amount * 100));

        $vnTime = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $vnp_CreateDate = $vnTime->format('YmdHis');

        $ipAddr = $request->ip();
        if ($ipAddr === '::1' || empty($ipAddr)) {
            $ipAddr = '127.0.0.1';
        }

        $vnp_ExpireDate = \Carbon\Carbon::parse($expiresAt)
            ->setTimezone('Asia/Ho_Chi_Minh')
            ->subSeconds(10)
            ->format('YmdHis');

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $ipAddr,
            "vnp_Locale" => 'vn',
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => 'billpayment',
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ExpireDate" => $vnp_ExpireDate,
        ];

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (!empty($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        return response()->json([
            'success' => true,
            'payment_url' => $vnp_Url,
            'order_id' => $booking->booking_code,
            'amount' => (int) round($amount),
            'expires_at' => $expiresAt->toIso8601String(),
            'idempotency_key' => $idempotencyKey,
            'data' => [
                'payment_url' => $vnp_Url
            ]
        ]);
    }

    public function process(PaymentRequest $request)
    {
        return $this->createUrl($request);
    }
}
