<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevenueUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?int $bookingId;
    public string $reason;
    public ?int $cinemaId;
    public ?int $movieId;
    public string $changedAt;

    /**
     * Create a new event instance.
     */
    public function __construct(
        ?int $bookingId = null,
        string $reason = 'payment_completed',
        ?int $cinemaId = null,
        ?int $movieId = null
    ) {
        $this->bookingId = $bookingId;
        $this->reason = $reason;
        $this->cinemaId = $cinemaId;
        $this->movieId = $movieId;
        $this->changedAt = now()->toIso8601String();
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'revenue.updated';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.dashboard'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event'      => 'revenue.updated',
            'changed_at' => $this->changedAt,
            'reason'     => $this->reason,
            'booking_id' => $this->bookingId,
            'cinema_id'  => $this->cinemaId,
            'movie_id'   => $this->movieId,
        ];
    }

    /**
     * Safely dispatch the event after the current database transaction commits.
     * If no active transaction, dispatch immediately.
     * Errors during broadcasting are caught and logged so business logic is never broken.
     */
    public static function dispatchSafely(
        ?int $bookingId = null,
        string $reason = 'payment_completed',
        ?int $cinemaId = null,
        ?int $movieId = null
    ): void {
        $dispatchCallback = function () use ($bookingId, $reason, $cinemaId, $movieId) {
            try {
                event(new static($bookingId, $reason, $cinemaId, $movieId));
            } catch (\Throwable $e) {
                Log::warning('RevenueUpdated broadcasting warning: ' . $e->getMessage());
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatchCallback);
        } else {
            $dispatchCallback();
        }
    }
}
