<?php

namespace Tests\Feature;

use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CardBadgesTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_shows_year_language_and_new_badge(): void
    {
        Movie::create(['title' => 'Fresh Film', 'year' => 2026, 'language' => 'Sinhala']);

        $this->get('/')->assertOk()->assertSee('NEW')->assertSee('2026')->assertSee('Sinhala');
    }

    public function test_old_movie_has_no_new_badge(): void
    {
        $m = Movie::create(['title' => 'Old Film', 'year' => 1999]);
        $m->forceFill(['created_at' => now()->subDays(40)])->save();

        $this->assertFalse($m->fresh()->isNew());
        $this->get('/')->assertOk()->assertDontSee('>NEW<', false);
    }

    public function test_trailer_button_only_when_movie_has_trailer(): void
    {
        Movie::create(['title' => 'No Trailer']);
        $this->get('/')->assertDontSee('data-card-trailer', false);

        Movie::create(['title' => 'With Trailer', 'trailer_url' => 'trailers/x.mp4']);
        $this->get('/')->assertSee('data-card-trailer', false)->assertSee('data-card-trailer-dialog', false);
    }

    public function test_year_and_language_fall_back_to_title_and_keywords(): void
    {
        $m = Movie::create(['title' => 'Happy Raj (2026)', 'tags' => ',telugu,romantic,']);

        $this->assertSame(2026, $m->display_year);
        $this->assertSame('Telugu', $m->display_language);
        $this->get('/')->assertOk()->assertSee('Telugu')->assertSee('2026');
    }

    public function test_fields_win_over_fallbacks(): void
    {
        $m = Movie::create(['title' => 'X (1999)', 'year' => 2025, 'language' => 'Tamil', 'tags' => ',telugu,']);

        $this->assertSame(2025, $m->display_year);
        $this->assertSame('Tamil', $m->display_language);
    }

    public function test_completed_series_shows_completed_instead_of_new(): void
    {
        $m = Movie::create(['title' => 'Done Series', 'is_completed' => true]);
        $m->episodes()->create(['number' => 1, 'url' => 'https://streamtape.com/e/abc']);

        $this->get('/')->assertOk()->assertSee('COMPLETED')->assertDontSee('>NEW<', false);
    }

    public function test_completed_flag_saved_only_when_title_has_episodes(): void
    {
        config(['auracinema.admin_user' => 'nuwan', 'auracinema.admin_password_hash' => Hash::make('secret-pass')]);
        $auth = ['Authorization' => 'Basic '.base64_encode('nuwan:secret-pass')];
        $base = ['title' => 'Show', 'type' => 'series', 'is_completed' => '1'];

        $this->withHeaders($auth)->post('/movies', $base + ['has_episodes' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse(Movie::where('title', 'Show')->first()->is_completed);

        $this->withHeaders($auth)->post('/movies', ['title' => 'Show 2'] + $base + [
            'has_episodes' => '1',
            'episodes' => [['number' => 1, 'url' => 'https://streamtape.com/e/abc']],
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Movie::where('title', 'Show 2')->first()->is_completed);
    }
}
