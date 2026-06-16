<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\ResendChannel;

class CustomResetPassword extends Notification
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

        $html = "
            <h2>Yêu cầu đặt lại mật khẩu</h2>
            <p>Bạn nhận được email này vì chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản CineDot của bạn.</p>
            <p><a href='{$url}' style='display:inline-block;padding:10px 20px;background:#E50914;color:#fff;text-decoration:none;border-radius:5px;'>Đặt lại mật khẩu</a></p>
            <p>Link này sẽ hết hạn sau 60 phút.</p>
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
