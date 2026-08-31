<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Booking;

class BookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public int $refundPercentage;

    public function __construct(Booking $booking, int $refundPercentage = 100)
    {
        $this->booking = $booking->loadMissing([
            'showtime.movie',
            'showtime.room.cinema',
            'user'
        ]);
        $this->refundPercentage = $refundPercentage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Thông báo Hủy vé #{$this->booking->booking_code} - Hoàn tiền thành công | CineDot",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking_cancelled',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
