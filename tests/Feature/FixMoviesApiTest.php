<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixMoviesApiTest extends TestCase
{
    use RefreshDatabase;

    private Movie $movie1;
    private Movie $movie2;

    protected function setUp(): void
    {
        parent::setUp();

        $actionGenre = Genre::create(['genre_name' => 'Hành động']);

        $this->movie1 = Movie::create([
            'title' => 'Lật Mặt 8',
            'slug' => 'lat-mat-8',
            'duration' => 120,
            'status' => 'now_showing',
            'overview' => 'Bộ phim hành động kịch tính.',
        ]);
        $this->movie1->genres()->attach($actionGenre->genre_id);

        $this->movie2 = Movie::create([
            'title' => 'Avengers 5',
            'slug' => 'avengers-5',
            'duration' => 150,
            'status' => 'upcoming',
        ]);
    }

    public function test_get_movies_list_executes_without_deleted_at_column_error()
    {
        $response = $this->getJson('/api/v1/movies');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(2, $response->json('data.results'));
        $this->assertEquals($this->movie1->movie_id, $response->json('data.results.0.id'));
        $this->assertEquals(120, $response->json('data.results.0.runtime'));
    }

    public function test_get_movies_filters_by_status()
    {
        $response = $this->getJson('/api/v1/movies?status=now_showing');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(1, $response->json('data.results'));
        $this->assertEquals('Lật Mặt 8', $response->json('data.results.0.title'));
    }

    public function test_get_movie_detail_returns_non_null_id_runtime_and_full_data()
    {
        $response = $this->getJson('/api/v1/movies/lat-mat-8');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.id', $this->movie1->movie_id);
        $response->assertJsonPath('data.runtime', 120);
        $response->assertJsonPath('data.status', 'now_showing');
        $response->assertJsonPath('data.genres.0.name', 'Hành động');
    }
}
