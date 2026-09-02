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
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $url = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);
        $otp = \Illuminate\Support\Facades\Redis::get("password_reset:otp:{$notifiable->email}") ?: '868999';
        $year = date('Y');

        $html = "
<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Đặt lại mật khẩu - CineDot</title>
</head>
<body style='margin:0;padding:0;background-color:#F4F6FB;font-family:\"Roboto\",\"Segoe UI\",-apple-system,BlinkMacSystemFont,Arial,sans-serif;color:#0F172A;'>
    <center style='width:100%;table-layout:fixed;background-color:#F4F6FB;padding:32px 0;'>
        <table style='background-color:#FFFFFF;margin:0 auto;width:100%;max-width:540px;border-radius:20px;overflow:hidden;border:1px solid #EEF2F6;box-shadow:0 10px 30px rgba(15,23,42,0.04);border-collapse:collapse;' width='100%' border='0' cellpadding='0' cellspacing='0' role='presentation'>
            <!-- TOP ACCENT LINE -->
            <tr>
                <td style='height:4px;background:linear-gradient(90deg,#7C6FE8 0%,#6366F1 100%);font-size:1px;line-height:1px;'></td>
            </tr>

            <!-- HEADER -->
            <tr>
                <td align='center' style='padding:24px 30px 18px 30px;'>
                    <a href='{$frontendUrl}' style='font-size:24px;font-weight:900;color:#0F172A;letter-spacing:2px;text-decoration:none;'>
                        CINE<span style='color:#7C6FE8;'>DOT</span>
                    </a>
                    <div style='font-size:11px;font-weight:600;color:#94A3B8;letter-spacing:0.5px;margin-top:3px;'>
                        Hệ Thống Rạp Chiếu Phim Hiện Đại
                    </div>
                </td>
            </tr>

            <!-- CONTENT -->
            <tr>
                <td style='padding:0 30px 30px 30px;text-align:center;'>
                    <div style='font-size:36px;margin-bottom:8px;'>🔐</div>
                    <div style='display:inline-block;background-color:#EEECFB;color:#7C6FE8;padding:5px 16px;border-radius:50px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;'>
                        Xác thực bảo mật
                    </div>
                    <h1 style='color:#0F172A;font-size:20px;font-weight:900;margin:10px 0 4px 0;'>
                        YÊU CẦU ĐẶT LẠI MẬT KHẨU
                    </h1>
                    <p style='color:#64748B;font-size:13px;margin:0 0 20px 0;line-height:1.5;'>
                        Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản liên kết với email <strong>{$notifiable->email}</strong>.
                    </p>

                    <!-- OTP CODE BOX (BORDERLESS) -->
                    <div style='background-color:#F8FAFC;border-radius:16px;padding:20px;margin-bottom:20px;'>
                        <div style='color:#64748B;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;'>
                            MÃ XÁC THỰC OTP
                        </div>
                        <div style='background-color:#F3F1FD;display:inline-block;padding:10px 28px;border-radius:12px;color:#7C6FE8;font-size:30px;font-weight:900;letter-spacing:6px;font-family:monospace;'>
                            {$otp}
                        </div>
                        <div style='color:#94A3B8;font-size:12px;margin-top:10px;'>
                            ⏱️ Mã OTP này có hiệu lực trong vòng <strong>15 phút</strong>.
                        </div>
                    </div>

                    <!-- DIRECT RESET BUTTON -->
                    <div style='margin-bottom:20px;'>
                        <a href='{$url}' style='display:inline-block;background-color:#7C6FE8;color:#FFFFFF!important;padding:13px 32px;border-radius:50px;text-decoration:none;font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;'>
                            Đặt Lại Mật Khẩu Ngay &rarr;
                        </a>
                    </div>

                    <p style='color:#94A3B8;font-size:12px;margin:0;line-height:1.5;'>
                        Nếu bạn không gửi yêu cầu này, vui lòng bỏ qua email. Tài khoản của bạn vẫn an toàn.
                    </p>
                </td>
            </tr>

            <!-- FOOTER -->
            <tr>
                <td align='center' style='background-color:#FAFAFC;padding:20px 30px;border-top:1px solid #F1F5F9;color:#94A3B8;font-size:12px;line-height:1.6;'>
                    <p style='margin:0 0 4px 0;color:#475569;font-weight:600;'>CineDot Cinema Vietnam</p>
                    <p style='margin:0 0 6px 0;font-size:12px;color:#64748B;'>Hotline hỗ trợ: <strong style='color:#0F172A;'>1900 6868</strong> &bull; Email: <a href='mailto:support@cinedot.vn' style='color:#7C6FE8;text-decoration:none;font-weight:600;'>support@cinedot.vn</a></p>
                    <p style='margin:0;font-size:11px;color:#94A3B8;'>&copy; {$year} CineDot. Mọi quyền được bảo lưu.</p>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>
";

        return [
            'from' => env('RESEND_MAIL_FROM', 'onboarding@resend.dev'),
            'to' => $notifiable->email,
            'subject' => 'CineDot - Mã OTP Đặt Lại Mật Khẩu',
            'html' => $html,
        ];
    }
}
