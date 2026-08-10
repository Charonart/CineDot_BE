<?php

namespace Tests\Feature;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Province;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixShowtimeApiTest extends TestCase
{
    use RefreshDatabase;

    private Movie $movie;
    private Cinema $cinema;
    private Showtime $showtime;

    protected function setUp(): void
    {
        parent::setUp();

        $province = Province::create(['province_name' => 'Hà Nội']);

        $this->cinema = Cinema::create([
            'province_id' => $province->province_id,
            'name'        => 'CineDot Cầu Giấy',
            'slug'        => 'cinedot-cau-giay',
            'address'     => '123 Cầu Giấy',
        ]);

        $room = Room::create([
            'cinema_id' => $this->cinema->cinema_id,
            'room_name' => 'Phòng 01',
            'room_type' => '2D',
        ]);

        $this->movie = Movie::create([
            'title'    => 'Lật Mặt 8',
            'slug'     => 'lat-mat-8',
            'duration' => 120,
            'status'   => 'now_showing',
        ]);

        $this->showtime = Showtime::create([
            'movie_id'       => $this->movie->movie_id,
            'room_id'        => $room->room_id,
            'showtime_start' => '2026-07-27 19:00:00',
            'showtime_end'   => '2026-07-27 21:00:00',
            'base_price'     => 90000,
        ]);
    }

    public function test_get_showtimes_filters_by_movie_id_and_cinema_id_without_sql_error()
    {
        $response = $this->getJson("/api/v1/showtimes?movie_id={$this->movie->movie_id}&cinema_id={$this->cinema->cinema_id}&date=2026-07-27");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.date', '2026-07-27');
        $this->assertCount(1, $response->json('data.results'));
    }

    public function test_get_showtimes_filters_by_movie_slug()
    {
        $response = $this->getJson("/api/v1/showtimes?movie=lat-mat-8&cinema_id={$this->cinema->cinema_id}&date=2026-07-27");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(1, $response->json('data.results'));
        $this->assertEquals('Lật Mặt 8', $response->json('data.results.0.movie.title'));
    }

    public function test_get_showtimes_by_movie_slug_endpoint()
    {
        $response = $this->getJson("/api/v1/movies/lat-mat-8/showtimes?date=2026-07-27");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.movie.slug', 'lat-mat-8');
        $this->assertCount(1, $response->json('data.results'));
    }
}
