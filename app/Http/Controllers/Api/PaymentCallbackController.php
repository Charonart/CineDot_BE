<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    public function vnpayIpn(Request $request)
    {
        $inputData = array();
        $returnData = array();

        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $vnp_HashSecret = config('services.vnpay.hash_secret');
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? null;
        $vnp_Amount = $inputData['vnp_Amount'] ?? null;
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? null;

        try {
            if ($secureHash == $vnp_SecureHash) {
                $booking = Booking::where('booking_code', $vnp_TxnRef)->first();
                if ($booking != NULL) {
                    if ($booking->total_amount * 100 == $vnp_Amount) {
                        if ($booking->booking_status == 'pending') {
                            if ($vnp_ResponseCode == '00') {
                                // Thanh toán thành công
                                $this->bookingService->confirmBooking($booking->booking_id);
                            } else {
                                // Thanh toán lỗi
                                $booking->update(['booking_status' => 'cancelled']);
                            }
                            $returnData['RspCode'] = '00';
                            $returnData['Message'] = 'Confirm Success';
                        } else {
                            $returnData['RspCode'] = '02';
                            $returnData['Message'] = 'Order already confirmed';
                        }
                    } else {
                        $returnData['RspCode'] = '04';
                        $returnData['Message'] = 'invalid amount';
                    }
                } else {
                    $returnData['RspCode'] = '01';
                    $returnData['Message'] = 'Order not found';
                }
            } else {
                $returnData['RspCode'] = '97';
                $returnData['Message'] = 'Invalid signature';
            }
        } catch (\Exception $e) {
            $returnData['RspCode'] = '99';
            $returnData['Message'] = 'Unknown error';
            Log::error('VNPay IPN Error: ' . $e->getMessage());
        }

        return response()->json($returnData);
    }

    public function vnpayReturn(Request $request)
    {
        $inputData = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $vnp_HashSecret = config('services.vnpay.hash_secret');
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        if ($secureHash == $vnp_SecureHash) {
            $vnp_TxnRef = $request->vnp_TxnRef;
            $booking = Booking::where('booking_code', $vnp_TxnRef)->first();

            if ($request->vnp_ResponseCode == '00') {
                // Fallback update status in Return URL for local development (when IPN cannot be reached)
                if ($booking && $booking->booking_status == 'pending') {
                    $this->bookingService->confirmBooking($booking->booking_id);
                }
                return redirect()->away($frontendUrl . '/payment/success?order_id=' . $request->vnp_TxnRef);
            }

            if ($booking && $booking->booking_status == 'pending') {
                $booking->update(['booking_status' => 'cancelled']);
            }
            return redirect()->away($frontendUrl . '/payment/failed?order_id=' . $request->vnp_TxnRef . '&message=Payment_Failed');
        }

        return redirect()->away($frontendUrl . '/payment/failed?order_id=' . $request->vnp_TxnRef . '&message=Invalid_Signature');
    }
}
