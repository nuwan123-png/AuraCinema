<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Support\MediaStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaNamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_name_is_readable_unique_and_safe(): void
    {
        $a = MediaStore::baseName('Happy Raj', 2026);
        $b = MediaStore::baseName('Happy Raj', 2026);

        $this->assertMatchesRegularExpression('/^happy-raj-2026-[a-z0-9]{6}$/', $a);
        $this->assertNotSame($a, $b);
        $this->assertMatchesRegularExpression('/^movie-[a-z0-9]{6}$/', MediaStore::baseName('???', null));
        // the title already ends with a year (even a different one): no second year
        $this->assertMatchesRegularExpression('/^robin-hood-2025-[a-z0-9]{6}$/', MediaStore::baseName('Robin Hood (2025)', 2025));
        $this->assertMatchesRegularExpression('/^sattendru-maarudhu-vaanilai-2026-[a-z0-9]{6}$/', MediaStore::baseName('Sattendru Maarudhu Vaanilai (2026)', 2024));
        $this->assertMatchesRegularExpression('/^despicable-me-4-sinhala-dubbed-2025-[a-z0-9]{6}$/', MediaStore::baseName('Despicable Me 4 Sinhala Dubbed', 2025));
        $this->assertStringStartsWith('o-romeo-2026-', MediaStore::baseName('O\' Romeo!', 2026));
        $this->assertLessThan(90, strlen(MediaStore::baseName(str_repeat('Long ', 80), 2026)));
    }

    public function test_detects_old_random_names_only(): void
    {
        $this->assertTrue(MediaStore::hasRandomName('posters/mTPSU565KqmaO0GG6TnHd5lvPSucxi8o.webp'));
        $this->assertTrue(MediaStore::hasRandomName('trailers/C9qbNZYKb7hHBA4e7O2DcQJyET98ztJSnKLoWLJv.mp4'));
        $this->assertFalse(MediaStore::hasRandomName('posters/happy-raj-2026-k3f9a2.webp'));
        $this->assertFalse(MediaStore::hasRandomName(null));
    }

    public function test_uploading_through_the_form_uses_readable_names(): void
    {
        Storage::fake('public');
        config(['auracinema.admin_user' => 'nuwan', 'auracinema.admin_password_hash' => \Illuminate\Support\Facades\Hash::make('secret-pass')]);
        $auth = ['Authorization' => 'Basic '.base64_encode('nuwan:secret-pass')];

        $this->withHeaders($auth)->post('/movies', [
            'title' => 'Happy Raj',
            'type' => 'movie',
            'year' => 2026,
            'image' => UploadedFile::fake()->image('whatever.jpg', 600, 900),
            'trailer' => UploadedFile::fake()->create('videoplayback (1).mp4', 100, 'video/mp4'),
        ])->assertRedirect();

        $movie = Movie::firstOrFail();
        $this->assertMatchesRegularExpression('#^posters/happy-raj-2026-[a-z0-9]{6}\.webp$#', $movie->image);
        // the fake video cannot be compressed, so the readable original name stays
        $this->assertMatchesRegularExpression('#^trailers/happy-raj-2026-[a-z0-9]{6}\.mp4$#', $movie->trailer_url);
        Storage::disk('public')->assertExists($movie->image);
        Storage::disk('public')->assertExists($movie->trailer_url);
    }

    public function test_media_rename_moves_files_updates_database_and_keeps_good_names(): void
    {
        config(['auracinema.media_disk' => 'r2', 'auracinema.media_url' => 'https://pub-test.r2.dev']);
        Storage::fake('public');
        Storage::fake('r2');

        $oldPoster = 'posters/mTPSU565KqmaO0GG6TnHd5lvPSucxi8o.webp';
        $oldTrailer = 'trailers/C9qbNZYKb7hHBA4e7O2DcQJyET98ztJSnKLoWLJv.mp4';
        Storage::disk('r2')->put($oldPoster, 'img');
        Storage::disk('r2')->put($oldTrailer, 'vid');
        Storage::disk('r2')->put('posters/already-nice-2025-abc123.webp', 'img');

        $a = Movie::create(['title' => 'Happy Raj', 'year' => 2026, 'image' => $oldPoster, 'trailer_url' => $oldTrailer]);
        $b = Movie::create(['title' => 'Already Nice', 'year' => 2025, 'image' => 'posters/already-nice-2025-abc123.webp']);
        $c = Movie::create(['title' => 'Missing File', 'image' => 'posters/ZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZ.webp']);

        $this->artisan('media:rename --dry-run')->assertSuccessful();
        $this->assertSame($oldPoster, $a->fresh()->image);

        $this->artisan('media:rename')->assertSuccessful();

        $a = $a->fresh();
        $this->assertMatchesRegularExpression('#^posters/happy-raj-2026-[a-z0-9]{6}\.webp$#', $a->image);
        $this->assertMatchesRegularExpression('#^trailers/happy-raj-2026-[a-z0-9]{6}\.mp4$#', $a->trailer_url);
        Storage::disk('r2')->assertExists($a->image);
        Storage::disk('r2')->assertExists($a->trailer_url);
        Storage::disk('r2')->assertMissing($oldPoster);
        Storage::disk('r2')->assertMissing($oldTrailer);
        $this->assertSame('posters/already-nice-2025-abc123.webp', $b->fresh()->image);
        $this->assertSame('posters/ZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZ.webp', $c->fresh()->image); // not found: left alone
        $this->assertStringStartsWith('https://pub-test.r2.dev/posters/happy-raj-2026-', $a->poster_url);
    }
}
