<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $showtime = $this->showtime;
        $movie = $showtime?->movie;
        $room = $showtime?->room;
        $cinema = $room?->cinema;

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
                'poster_url' => $movie->poster_path,
                'age_rating' => $movie->adult ? 'T18' : 'P',
                'duration'   => $movie->duration,
            ] : null,
            'cinema'          => $cinema ? [
                'cinema_id'      => $cinema->cinema_id,
                'cinema_name'    => $cinema->cinema_name,
                'cinema_address' => $cinema->cinema_address,
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
            'seats'           => $this->bookingSeats ? $this->bookingSeats->map(function ($bSeat) {
                $stSeat = $bSeat->showtimeSeat;
                return [
                    'showtime_seat_id' => $bSeat->showtime_seat_id,
                    'seat_number'      => $stSeat ? ($stSeat->row_name . $stSeat->seat_number) : null,
                    'ticket_type'      => $bSeat->ticket_type,
                    'price'            => (int) round($bSeat->price),
                ];
            }) : [],
            'combos'          => $this->bookingCombos ? $this->bookingCombos->map(function ($bCombo) {
                return [
                    'combo_id'         => $bCombo->combo_id,
                    'name'             => $bCombo->combo?->name,
                    'quantity'         => $bCombo->quantity,
                    'price_at_booking' => (int) round($bCombo->price_at_booking),
                    'is_claimed'       => (bool) $bCombo->is_claimed,
                ];
            }) : [],
            'price_breakdown' => $this->price_breakdown,
        ];
    }
}
