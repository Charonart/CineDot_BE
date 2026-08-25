<?php

namespace App\Events;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketScanned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Booking $booking;
    public string $scannedAt;

    /**
     * Create a new event instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
        $this->scannedAt = now()->toIso8601String();
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ticket.scanned';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.dashboard'),
        ];

        if (!empty($this->booking->showtime_id)) {
            $channels[] = new Channel("showtimes.{$this->booking->showtime_id}");
        }

        if (!empty($this->booking->user_id)) {
            $channels[] = new PrivateChannel("App.Models.User.{$this->booking->user_id}");
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $checkedInTime = $this->booking->checked_in_at 
            ? Carbon::parse($this->booking->checked_in_at) 
            : Carbon::parse($this->scannedAt);

        // Chuỗi số ghế
        $seatsStr = '';
        if ($this->booking->relationLoaded('bookingSeats') && $this->booking->bookingSeats) {
            $seatsStr = $this->booking->bookingSeats->map(function ($bs) {
                if ($bs->showtimeSeat) {
                    return $bs->showtimeSeat->row_name . $bs->showtimeSeat->seat_number;
                }
                return '#' . ($bs->showtime_seat_id ?? $bs->booking_seat_id);
            })->implode(', ');
        }

        return [
            'event'         => 'ticket.scanned',
            'action'        => 'ticket_checkin',
            'reason'        => 'ticket_scanned',
            'booking_id'    => $this->booking->booking_id,
            'booking_code'  => $this->booking->booking_code,
            'user_id'       => $this->booking->user_id,
            'showtime_id'   => $this->booking->showtime_id,
            'movie_title'   => $this->booking->showtime?->movie?->title ?? '',
            'cinema_name'   => $this->booking->showtime?->room?->cinema?->cinema_name ?? '',
            'room_name'     => $this->booking->showtime?->room?->room_name ?? '',
            'customer_name' => $this->booking->user?->fullname ?? $this->booking->user?->name ?? 'Khách vãng lai',
            'seats'         => $seatsStr,
            'final_amount'  => (float) $this->booking->final_amount,
            'checked_in_at' => $checkedInTime->toIso8601String(),
            'formatted_time'=> $checkedInTime->format('H:i d/m/Y'),
            'status'        => 'CHECKED_IN',
            'is_checked_in' => true,
        ];
    }

    /**
     * Safely dispatch the event after the current database transaction commits.
     */
    public static function dispatchSafely(Booking $booking): void
    {
        $dispatchCallback = function () use ($booking) {
            try {
                event(new static($booking));
            } catch (\Throwable $e) {
                Log::warning('TicketScanned broadcasting warning: ' . $e->getMessage());
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatchCallback);
        } else {
            $dispatchCallback();
        }
    }
}
