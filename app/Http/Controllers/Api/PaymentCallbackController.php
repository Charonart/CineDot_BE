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
        unset($inputData['vnp_SecureHashType']);
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
                    $expectedAmount = (float) ($booking->final_amount ?? $booking->total_amount);
                    if (intval(round($expectedAmount * 100)) == intval($vnp_Amount)) {
                        if (in_array($booking->booking_status, ['pending', 'holding', 'unpaid'])) {
                            if ($vnp_ResponseCode == '00') {
                                // Thanh toán thành công
                                $this->bookingService->confirmBooking($booking->booking_id, [
                                    'transaction_id' => $inputData['vnp_TransactionNo'] ?? null,
                                    'payment_method' => 'VNPAY',
                                ]);
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
        foreach ($request->query() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);
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
        
        Log::info("vnpayReturn debug", [
            'secureHash' => $secureHash,
            'vnp_SecureHash' => $vnp_SecureHash,
            'hashData' => $hashData,
            'match' => ($secureHash === $vnp_SecureHash)
        ]);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        if ($secureHash == $vnp_SecureHash) {
            $vnp_TxnRef = $request->vnp_TxnRef;
            $booking = Booking::where('booking_code', $vnp_TxnRef)->first();

            Log::info("booking status before confirmBooking", [
                'booking_id' => $booking?->booking_id,
                'status' => $booking?->booking_status,
                'vnp_ResponseCode' => $request->vnp_ResponseCode
            ]);

            if ($request->vnp_ResponseCode == '00') {
                if ($booking && in_array($booking->booking_status, ['pending', 'holding', 'unpaid'])) {
                    try {
                        Log::info("Calling confirmBooking for booking_id: " . $booking->booking_id);
                        $booking = $this->bookingService->confirmBooking($booking->booking_id, [
                            'transaction_id' => $request->input('vnp_TransactionNo'),
                            'payment_method' => 'VNPAY',
                        ]);
                        Log::info("confirmBooking finished. New status: " . $booking->booking_status);
                    } catch (\Throwable $e) {
                        Log::error('confirmBooking Error: ' . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
                    }
                }
                
                return redirect()->away($frontendUrl . '/booking/success?booking_code=' . $vnp_TxnRef);
            }

            if ($booking && in_array($booking->booking_status, ['pending', 'holding', 'unpaid'])) {
                $booking->update(['booking_status' => 'cancelled']);
            }

            return redirect()->away($frontendUrl . '/booking/success?booking_code=' . $vnp_TxnRef . '&status=failed');
        }

        return redirect()->away($frontendUrl . '/booking/success?status=invalid_signature');
    }

    public function paymentWebhook(Request $request)
    {
        $orderId = $request->input('order_id', $request->input('vnp_TxnRef'));
        $resultCode = $request->input('result_code', 0);

        if ($orderId) {
            $booking = Booking::where('booking_code', $orderId)->first();
            if ($booking && $booking->booking_status === 'pending') {
                if ((int)$resultCode === 0) {
                    $this->bookingService->confirmBooking($booking->booking_id);
                } else {
                    $booking->update(['booking_status' => 'cancelled']);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Xử lý IPN / Webhook giao dịch thành công'
        ]);
    }
}

