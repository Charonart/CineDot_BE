<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeatStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $showtimeId
     * @param array|int $seatIds ShowtimeSeat IDs
     * @param string $status 'available' | 'selecting' | 'holding' | 'booked' | 'blocked'
     * @param int|null $userId User ID who triggered the action
     */
    public function __construct(
        public int $showtimeId,
        public array|int $seatIds,
        public string $status,
        public ?int $userId = null
    ) {
        $this->seatIds = is_array($seatIds) ? $seatIds : [$seatIds];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("showtimes.{$this->showtimeId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'seat.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'showtime_id' => $this->showtimeId,
            'seat_ids'    => array_values(array_map('intval', $this->seatIds)),
            'status'      => strtolower($this->status),
            'user_id'     => $this->userId,
            'updated_at'  => now()->toIso8601String(),
        ];
    }
}
