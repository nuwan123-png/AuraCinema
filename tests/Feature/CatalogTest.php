<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Support\EmbedUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_streamtape_url_is_normalized_correctly(): void
    {
        $normalized = EmbedUrl::normalize('https://streamtape.com/v/3xx1jlAX7gFaJm/video.mp4');
        $this->assertEquals('https://streamtape.com/e/3xx1jlAX7gFaJm', $normalized);
    }

    public function test_streamtape_embed_url_is_normalized_correctly(): void
    {
        $normalized = EmbedUrl::normalize('https://streamtape.com/e/3xx1jlAX7gFaJm');
        $this->assertEquals('https://streamtape.com/e/3xx1jlAX7gFaJm', $normalized);
    }

    public function test_youtube_url_is_normalized_correctly(): void
    {
        $normalized = EmbedUrl::normalize('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertEquals('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $normalized);
    }

    public function test_movie_can_be_stored_with_streamtape_embed(): void
    {
        config(['auracinema.admin_user' => 'nuwan', 'auracinema.admin_password_hash' => Hash::make('secret-pass')]);

        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode('nuwan:secret-pass')
        ])->post(route('movies.store'), [
            'title' => 'Test Movie',
            'embed_url' => 'https://streamtape.com/v/3xx1jlAX7gFaJm/video.mp4',
        ]);

        $movie = Movie::first();
        $this->assertNotNull($movie);
        $this->assertEquals('https://streamtape.com/e/3xx1jlAX7gFaJm', $movie->embed_url);
    }
}