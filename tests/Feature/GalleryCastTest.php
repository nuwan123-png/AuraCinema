<?php

namespace Tests\Feature;

use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryCastTest extends TestCase
{
    use RefreshDatabase;

    private array $auth;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auracinema.media_disk' => 'r2', 'auracinema.media_url' => 'https://pub-test.r2.dev',
            'auracinema.admin_user' => 'nuwan', 'auracinema.admin_password_hash' => Hash::make('secret-pass')]);
        Storage::fake('public');
        Storage::fake('r2');
        $this->auth = ['Authorization' => 'Basic '.base64_encode('nuwan:secret-pass')];
    }

    private function img(string $name = 'x.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 1600, 900);
    }

    private function base(array $extra = []): array
    {
        return array_merge(['title' => 'Sigma', 'type' => 'movie', 'year' => 2026], $extra);
    }

    private function r2Files(string $dir): array
    {
        return Storage::disk('r2')->allFiles($dir);
    }

    public function test_create_stores_resized_gallery_and_cast_and_page_shows_them(): void
    {
        $this->withHeaders($this->auth)->post('/movies', $this->base([
            'gallery_new' => [$this->img('a.jpg'), $this->img('b.png')],
            'cast' => [
                0 => ['name' => 'Anikha Surendran', 'role' => 'Nandhini', 'photo' => $this->img('p.jpg')],
                1 => ['name' => 'No Photo Person', 'role' => ''],
                2 => ['name' => '', 'role' => ''],   // empty row is dropped
            ],
        ]))->assertRedirect();

        $movie = Movie::firstOrFail();
        $this->assertCount(2, $movie->gallery);
        $this->assertCount(2, $this->r2Files('gallery'));
        $this->assertCount(2, $movie->cast_members);
        $this->assertMatchesRegularExpression('#^cast/anikha-surendran-[a-z0-9]{6}\.webp$#', $movie->cast_members[0]['photo']);
        $this->assertNull($movie->cast_members[1]['photo']);
        $this->assertCount(1, $this->r2Files('cast'));

        $page = $this->get('/movies/'.$movie->id)->assertOk();
        $page->assertSee('Gallery')->assertSee('Cast')->assertSee('Anikha Surendran')->assertSee('Nandhini')->assertSee('No Photo Person');
        $page->assertSee('https://pub-test.r2.dev/'.$movie->gallery[0], false);
    }

    public function test_movie_without_gallery_or_cast_shows_neither_section(): void
    {
        $movie = Movie::create(['title' => 'Plain', 'type' => 'movie']);

        $this->get('/movies/'.$movie->id)->assertOk()->assertDontSee('id="gallery"', false)->assertDontSee('id="cast"', false);
    }

    public function test_edit_removes_old_pictures_and_photos_from_r2_only_when_replaced(): void
    {
        $this->withHeaders($this->auth)->post('/movies', $this->base([
            'gallery_new' => [$this->img('a.jpg'), $this->img('b.jpg')],
            'cast' => [0 => ['name' => 'Alpha', 'photo' => $this->img('a.jpg')], 1 => ['name' => 'Beta', 'photo' => $this->img('b.jpg')]],
        ]));
        $movie = Movie::firstOrFail();
        [$g1, $g2] = $movie->gallery;
        [$alpha, $beta] = $movie->cast_members;

        $this->withHeaders($this->auth)->put('/movies/'.$movie->id, $this->base([
            'gallery_remove' => [$g1],
            'gallery_new' => [$this->img('c.jpg')],
            'cast' => [
                0 => ['name' => 'Alpha', 'existing_photo' => $alpha['photo']],                       // keeps its photo
                1 => ['name' => 'Beta', 'existing_photo' => $beta['photo'], 'photo' => $this->img('n.jpg')], // new photo replaces old
            ],
        ]))->assertRedirect();

        $movie = $movie->fresh();
        $this->assertCount(2, $movie->gallery);
        $this->assertNotContains($g1, $movie->gallery);
        $this->assertContains($g2, $movie->gallery);
        Storage::disk('r2')->assertMissing($g1);
        Storage::disk('r2')->assertExists($g2);
        Storage::disk('r2')->assertExists($alpha['photo']);
        Storage::disk('r2')->assertMissing($beta['photo']);
        $this->assertCount(2, $this->r2Files('gallery'));
        $this->assertCount(2, $this->r2Files('cast'));

        // dropping a cast row deletes its photo
        $this->withHeaders($this->auth)->put('/movies/'.$movie->id, $this->base([
            'cast' => [0 => ['name' => 'Alpha', 'existing_photo' => $alpha['photo']]],
            'gallery_remove' => [],
        ]))->assertRedirect();
        $this->assertCount(1, $this->r2Files('cast'));
        $this->assertCount(2, $this->r2Files('gallery'));  // untouched
    }

    public function test_forged_paths_cannot_touch_other_files(): void
    {
        Storage::disk('r2')->put('posters/someone-elses.webp', 'x');
        $this->withHeaders($this->auth)->post('/movies', $this->base(['gallery_new' => [$this->img()]]));
        $movie = Movie::firstOrFail();

        $this->withHeaders($this->auth)->put('/movies/'.$movie->id, $this->base([
            'gallery_remove' => ['posters/someone-elses.webp'],
            'cast' => [0 => ['name' => 'Eve', 'existing_photo' => 'posters/someone-elses.webp']],
        ]))->assertRedirect();

        Storage::disk('r2')->assertExists('posters/someone-elses.webp');
        $this->assertNull($movie->fresh()->cast_members[0]['photo']);
        $this->assertCount(1, $movie->fresh()->gallery);
    }

    public function test_more_than_twelve_pictures_is_rejected_and_nothing_is_left_behind(): void
    {
        $files = [];
        for ($i = 0; $i < 13; $i++) {
            $files[] = $this->img("p$i.jpg");
        }

        $this->withHeaders($this->auth)->post('/movies', $this->base(['gallery_new' => $files]))->assertSessionHasErrors('gallery_new');

        $this->assertSame(0, Movie::count());
        $this->assertSame([], $this->r2Files('gallery'));
    }

    public function test_deleting_a_movie_deletes_gallery_and_cast_photos(): void
    {
        $this->withHeaders($this->auth)->post('/movies', $this->base([
            'gallery_new' => [$this->img(), $this->img()],
            'cast' => [0 => ['name' => 'Alpha', 'photo' => $this->img()]],
        ]));
        $movie = Movie::firstOrFail();
        $this->assertNotEmpty($this->r2Files('gallery'));

        $this->withHeaders($this->auth)->delete('/movies/'.$movie->id)->assertRedirect();

        $this->assertSame([], $this->r2Files('gallery'));
        $this->assertSame([], $this->r2Files('cast'));
    }
}
