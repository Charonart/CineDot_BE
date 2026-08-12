<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $showtime = $this->showtime;
        $movie = $showtime?->movie;
        $room = $showtime?->room;
        $cinema = $room?->cinema;

        $seatNames = $this->bookingSeats ? $this->bookingSeats->map(function ($bSeat) {
            $stSeat = $bSeat->showtimeSeat;
            return $stSeat ? ($stSeat->row_name . $stSeat->seat_number) : null;
        })->filter()->values()->toArray() : [];

        $comboSummary = $this->bookingCombos ? $this->bookingCombos->map(function ($bCombo) {
            $name = $bCombo->combo?->name ?? 'Combo';
            return "{$name} x{$bCombo->quantity}";
        })->filter()->values()->toArray() : [];

        return [
            'booking_id'      => $this->booking_id,
            'booking_code'    => $this->booking_code,
            'booking_status'  => $this->booking_status,
            'final_amount'    => (int) round($this->final_amount),
            'discount_amount' => (int) round($this->discount_amount),
            'qr_code_url'     => "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($this->booking_code),
            'created_at'      => $this->created_at?->toIso8601String(),
            'movie'           => $movie ? [
                'movie_id'   => $movie->movie_id,
                'title'      => $movie->title,
                'poster_url' => $movie->poster_url,
                'duration'   => $movie->duration,
            ] : null,
            'cinema'          => $cinema ? [
                'cinema_id'   => $cinema->cinema_id,
                'cinema_name' => $cinema->cinema_name,
            ] : null,
            'room'            => $room ? [
                'room_id'   => $room->room_id,
                'room_name' => $room->room_name,
                'room_type' => $room->room_type,
            ] : null,
            'showtime'        => $showtime ? [
                'showtime_id'    => $showtime->showtime_id,
                'showtime_start' => $showtime->showtime_start,
                'showtime_end'   => $showtime->showtime_end,
            ] : null,
            'total_seats'     => count($seatNames),
            'seats_summary'   => implode(', ', $seatNames),
            'total_combos'    => count($comboSummary),
            'combos_summary'  => implode(', ', $comboSummary),
        ];
    }
}
