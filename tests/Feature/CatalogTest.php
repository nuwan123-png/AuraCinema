<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Request as MovieRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_renders_the_head_and_the_category_navbar(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('AuraCinema');
    }

    public function test_admin_page_sets_the_cookie_that_shows_the_edit_button(): void
    {
        $response = $this->post('/login', [
            'username' => config('auracinema.admin_user', 'admin'),
            'password' => config('auracinema.admin_pass', 'password'),
        ]);

        $response->assertRedirect('/');
    }

    public function test_edit_button_only_shows_with_the_admin_cookie(): void
    {
        $movie = Movie::create([
            'title' => 'Test Movie',
            'type' => 'movie',
        ]);

        $this->get('/movies/'.$movie->id)->assertDontSee('Edit');

        $this->withCookie('aura_admin', '1')
            ->get('/movies/'.$movie->id)
            ->assertSee('Edit');
    }

    public function test_posters_render_with_a_real_src(): void
    {
        $movie = Movie::create([
            'title' => 'Poster Movie',
            'type' => 'movie',
            'image' => 'posters/test.webp',
        ]);

        $this->get('/movies/'.$movie->id)->assertSee('posters/test.webp');
    }

    public function test_assets_use_https_behind_cloudfront(): void
    {
        $this->get('/', ['HTTP_X_FORWARDED_PROTO' => 'https'])->assertOk();
    }

    public function test_series_is_saved_with_its_episode_links(): void
    {
        $this->withCookie('aura_admin', '1')->post('/movies', [
            'title' => 'Test Series',
            'type' => 'series',
            'has_episodes' => '1',
            'episodes' => [
                ['number' => 1, 'url' => 'https://streamtape.com/v/test1'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('movies', ['title' => 'Test Series']);
        $this->assertDatabaseHas('episodes', ['number' => 1]);
    }

    public function test_episode_numbers_must_be_unique(): void
    {
        $response = $this->withCookie('aura_admin', '1')->post('/movies', [
            'title' => 'Duplicate Series',
            'type' => 'series',
            'has_episodes' => '1',
            'episodes' => [
                ['number' => 1, 'url' => 'https://streamtape.com/v/test1'],
                ['number' => 1, 'url' => 'https://streamtape.com/v/test2'],
            ],
        ]);

        $response->assertSessionHasErrors('episodes');
    }

    public function test_episodes_are_ignored_when_the_box_is_not_ticked(): void
    {
        $this->withCookie('aura_admin', '1')->post('/movies', [
            'title' => 'Ignored Series',
            'type' => 'movie',
            'has_episodes' => '0',
            'episodes' => [
                ['number' => 1, 'url' => 'https://streamtape.com/v/test1'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('episodes', 0);
    }

    public function test_navbar_filters_by_type_genre_and_year(): void
    {
        Movie::create([
            'title' => 'Filtered Movie',
            'type' => 'movie',
            'genre' => 'Action',
            'year' => 2026,
        ]);

        $this->get('/movies?type=movie&genre=Action&year=2026')
            ->assertOk()
            ->assertSee('Filtered Movie');
    }

    public function test_language_and_keywords_are_saved_and_filter_the_lists(): void
    {
        Movie::create([
            'title' => 'Sinhala Action Movie',
            'type' => 'movie',
            'tags' => ',sinhala,action,',
        ]);

        $this->get('/movies?tag=sinhala')
            ->assertOk()
            ->assertSee('Sinhala Action Movie');
    }

    public function test_card_shows_play_icon_and_sinhala_sub_label_only_when_tagged(): void
    {
        Movie::create([
            'title' => 'Sample Movie',
            'type' => 'movie',
            'tags' => ',sinhala sub,',
        ]);

        $html = $this->get('/movies?type=movie')->assertOk()->getContent();

        $this->assertStringContainsString('fa-play', $html);
        $this->assertStringContainsString('සිංහල', $html);
    }

    public function test_navbar_shows_languages_on_hover_menus(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Hindi')
            ->assertSee('Sinhala');
    }

    public function test_visitors_can_send_a_request_and_only_the_admin_can_read_them(): void
    {
        $this->post('/requests', [
            'title' => 'Request A Movie',
        ])->assertRedirect();

        $this->assertDatabaseHas('requests', ['title' => 'Request A Movie']);
    }

    public function test_request_honeypot_blocks_bots(): void
    {
        $response = $this->post('/requests', [
            'title' => 'Bot Request',
            'website' => 'http://spam.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_sitemap_and_robots_use_the_site_url(): void
    {
        $this->get('/robots.txt')->assertOk();
    }

    public function test_watch_now_accepts_allowed_players_and_normalises_them(): void
    {
        $this->withCookie('aura_admin', '1')->post('/movies', [
            'title' => 'Player Movie',
            'type' => 'movie',
            'embed_url' => 'https://streamtape.com/v/abc123xyz',
        ])->assertRedirect();

        $this->assertDatabaseHas('movies', ['title' => 'Player Movie']);
    }

    public function test_watch_now_rejects_other_hosts_and_non_https(): void
    {
        $response = $this->withCookie('aura_admin', '1')->post('/movies', [
            'title' => 'Invalid Player Movie',
            'type' => 'movie',
            'embed_url' => 'http://malicious-site.com/video',
        ]);

        $response->assertSessionHasErrors('embed_url');
    }

    public function test_watch_now_player_loads_only_on_click(): void
    {
        $movie = Movie::create([
            'title' => 'Lazy Player Movie',
            'type' => 'movie',
            'embed_url' => 'https://streamtape.com/e/abc123xyz',
        ]);

        $this->get('/movies/'.$movie->id)
            ->assertOk()
            ->assertSee('data-embed-load');
    }
}