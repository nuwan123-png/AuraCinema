<?php

namespace Tests\Feature;

use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
