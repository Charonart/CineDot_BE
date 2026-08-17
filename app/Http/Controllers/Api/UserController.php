<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user()->load('province');
        
        return response()->json([
            'success' => true,
            'data'    => new UserResource($user)
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công',
            'data'    => new UserResource($user->load('province'))
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required'     => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min'          => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'new_password.confirmed'    => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        $user = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không chính xác.',
            ], 400);
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }

    public function transactions(Request $request)
    {
        $bookings = \App\Models\Booking::where('user_id', $request->user()->user_id)
            ->with(['showtime.movie', 'showtime.room.cinema'])
            ->orderByDesc('created_at')
            ->get();

        $transactions = $bookings->map(function ($b) {
            $isPaid = in_array($b->booking_status, ['paid', 'completed']);
            $points = (int) round(($b->final_amount ?? 0) / 10000);

            $statusText = match ($b->booking_status) {
                'completed', 'paid' => 'Thành công',
                'cancelled'        => 'Đã hủy / Hoàn tiền',
                'pending'          => 'Chờ thanh toán',
                default            => 'Thành công',
            };

            return [
                'id'              => 'TXN-' . ($b->booking_code ?: $b->booking_id),
                'booking_id'      => $b->booking_id,
                'booking_code'    => $b->booking_code ?: 'CINEMA-' . $b->booking_id,
                'movie_title'     => $b->showtime?->movie?->title ?: 'Vé xem phim',
                'cinema_name'     => $b->showtime?->room?->cinema?->cinema_name ?: 'CineDot Cinema',
                'amount'          => (int) ($b->final_amount ?? 0),
                'payment_method'  => $b->payment_method ?: 'VNPAY',
                'status'          => $b->booking_status,
                'status_label'    => $statusText,
                'points_earned'   => $isPaid ? $points : 0,
                'created_at'      => $b->created_at?->toIso8601String(),
                'formatted_date'  => $b->created_at?->format('d/m/Y H:i') ?: 'Gần đây',
                'type'            => $b->booking_status === 'cancelled' ? 'REFUND' : 'PAYMENT',
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }
}
