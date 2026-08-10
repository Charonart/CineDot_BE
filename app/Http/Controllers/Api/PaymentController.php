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
        
        $booking = Booking::where('user_id', $request->user()->user_id)
            ->findOrFail($request->booking_id);

        $paymentMethod = strtoupper($request->input('payment_method', 'VNPAY'));
        $amount = (float) ($booking->final_amount ?? $booking->total_amount);

        $vnp_Url = config('services.vnpay.url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        $vnp_Returnurl = config('services.vnpay.return_url', 'http://localhost:8000/api/v1/payments/vnpay/return');
        $vnp_TmnCode = config('services.vnpay.tmn_code', '50XQ0B1Y');
        $vnp_HashSecret = config('services.vnpay.hash_secret', 'KNHEY5MFOU7GSAV0YYMSETPC2DTCKO4I');

        $vnp_TxnRef = $booking->booking_code;
        $vnp_OrderInfo = 'Thanh_toan_ve_phim_' . $vnp_TxnRef;
        $vnp_Amount = intval(round($amount * 100));

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $request->ip(),
            "vnp_Locale" => 'vn',
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => 'billpayment',
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
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
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'idempotency_key' => $idempotencyKey,
            'data' => [
                'payment_url' => $vnp_Url
            ]
        ]);
    }

    public function process(Request $request)
    {
        return $this->createUrl(new PaymentRequest($request->all()));
    }
}
