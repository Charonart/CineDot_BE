<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\UserTier;

class TierUpgradedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $newTier;

    public $tries = 3;
    public $backoff = [10, 30];
    public $timeout = 30;

    public function __construct(User $user, UserTier $newTier)
    {
        $this->user = $user;
        $this->newTier = $newTier;
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $tierName = strtoupper($this->newTier->tier ?? 'VIP');
        return new Envelope(
            subject: "👑 Chúc mừng bạn đã thăng hạng hội viên {$tierName} - CineDot Cinema",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tier_upgraded',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
