<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Resend;

class ResendChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toResend')) {
            $payload = $notification->toResend($notifiable);
            
            if (empty($payload)) {
                return;
            }
            
            $resend = Resend::client(env('RESEND_API_KEY'));
            $resend->emails->send($payload);
        }
    }
}
