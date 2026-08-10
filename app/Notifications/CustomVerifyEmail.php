<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Channels\ResendChannel;

class CustomVerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return [ResendChannel::class];
    }

    public function toResend($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        $html = "
            <h2>Xác thực địa chỉ email</h2>
            <p>Cảm ơn bạn đã đăng ký tài khoản tại CineDot. Vui lòng click vào nút bên dưới để xác thực địa chỉ email của bạn.</p>
            <p><a href='{$verificationUrl}' style='display:inline-block;padding:10px 20px;background:#E50914;color:#fff;text-decoration:none;border-radius:5px;'>Xác thực Email</a></p>
            <p>Nếu bạn không đăng ký tài khoản này, vui lòng bỏ qua email.</p>
        ";

        return [
            'from' => env('RESEND_MAIL_FROM', 'onboarding@resend.dev'),
            'to' => $notifiable->email,
            'subject' => 'CineDot - Xác thực Email',
            'html' => $html,
        ];
    }

    protected function verificationUrl($notifiable)
    {
        // Chuyển hướng tới BE endpoint để verify, BE sẽ tự redirect sang FE sau khi verify xong
        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
        
        return $url;
    }
}
