<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Channels\ResendChannel;

class CustomResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return [ResendChannel::class];
    }

    public function toResend($notifiable)
    {
        $url = env('FRONTEND_URL', 'http://localhost:3000') . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        $otp = \Illuminate\Support\Facades\Redis::get("password_reset:otp:{$notifiable->email}");

        $html = "
            <h2>Yêu cầu đặt lại mật khẩu CineDot</h2>
            <p>Mã OTP khôi phục mật khẩu của bạn là: <strong style='font-size:24px;color:#E50914;'>{$otp}</strong></p>
            <p>Mã OTP này có hiệu lực trong 15 phút.</p>
            <p>Hoặc bạn có thể click vào nút bên dưới để đặt lại mật khẩu:</p>
            <p><a href='{$url}' style='display:inline-block;padding:10px 20px;background:#E50914;color:#fff;text-decoration:none;border-radius:5px;'>Đặt lại mật khẩu</a></p>
            <p>Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
        ";


        return [
            'from' => env('RESEND_MAIL_FROM', 'onboarding@resend.dev'),
            'to' => $notifiable->email,
            'subject' => 'CineDot - Đặt lại mật khẩu',
            'html' => $html,
        ];
    }
}
