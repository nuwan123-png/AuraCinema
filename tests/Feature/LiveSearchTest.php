<?php

namespace Tests\Feature;

use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_matching_titles_ignoring_case(): void
    {
        Movie::create(['title' => 'Island Python', 'year' => 2025, 'genre' => 'Action', 'tags' => ',monster,']);
        Movie::create(['title' => 'Cocktail 2', 'year' => 2026]);

        $this->getJson('/search/live?q=ISLAND')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.title', 'Island Python')
            ->assertJsonPath('items.0.year', 2025)
            ->assertJsonStructure(['items' => [['title', 'year', 'meta', 'poster', 'url']], 'total']);
    }

    public function test_matches_keywords_too(): void
    {
        Movie::create(['title' => 'Sigma', 'tags' => ',telugu,action,']);

        $this->getJson('/search/live?q=telugu')->assertOk()->assertJsonPath('items.0.title', 'Sigma');
    }

    public function test_short_queries_return_nothing_and_results_are_capped(): void
    {
        foreach (range(1, 9) as $i) {
            Movie::create(['title' => "Spider Film {$i}"]);
        }

        $this->getJson('/search/live?q=s')->assertOk()->assertJsonPath('total', 0)->assertJsonCount(0, 'items');
        $this->getJson('/search/live?q=spider')->assertOk()->assertJsonPath('total', 9)->assertJsonCount(6, 'items');
    }

    public function test_wildcard_characters_are_treated_as_plain_text(): void
    {
        Movie::create(['title' => 'Plain Title']);

        $this->getJson('/search/live?q=%25%25')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_detail_page_shows_a_trailer_button_not_an_inline_player(): void
    {
        $with = Movie::create(['title' => 'With Trailer', 'trailer_url' => 'trailers/x.mp4']);
        $without = Movie::create(['title' => 'No Trailer']);

        $this->get(route('movies.show', $with))->assertOk()->assertSee('data-trailer-open', false)->assertSee('data-trailer-dialog', false);
        $this->get(route('movies.show', $without))->assertOk()->assertDontSee('data-trailer-open', false);
    }
}
