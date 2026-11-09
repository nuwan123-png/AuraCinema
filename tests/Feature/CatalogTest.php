<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\MovieRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): array
    {
        config(['auracinema.admin_user' => 'nuwan', 'auracinema.admin_password_hash' => Hash::make('secret-pass')]);

        return ['Authorization' => 'Basic '.base64_encode('nuwan:secret-pass')];
    }

    public function test_layout_renders_the_head_and_the_category_navbar(): void
    {
        // Guards against Blade swallowing part of the layout (it once dropped the whole <head> tail).
        $this->get('/')->assertOk()
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('aria-label="Primary"', false)
            ->assertSee('Drama / Series')
            ->assertSee('Suggestion')
            ->assertSee('Request');
    }

    public function test_admin_page_sets_the_cookie_that_shows_the_edit_button(): void
    {
        $this->withHeaders($this->admin())->get('/movies/create')->assertOk()->assertCookie('aura_admin');
    }

    public function test_edit_button_only_shows_with_the_admin_cookie(): void
    {
        $movie = Movie::create(['title' => 'Inception']);

        $this->get('/movies/'.$movie->id)->assertOk()->assertDontSee('/movies/'.$movie->id.'/edit');
        $this->withCookie('aura_admin', '1')->get('/movies/'.$movie->id)->assertOk()->assertSee('/movies/'.$movie->id.'/edit');
    }

    public function test_posters_render_with_a_real_src(): void
    {
        $movie = Movie::create(['title' => 'Poster Test', 'image' => 'posters/p.jpg', 'type' => 'movie']);

        $this->get('/')->assertOk()->assertSee('/storage/posters/p.jpg', false);
        $this->get(route('movies.show', $movie))->assertOk()->assertSee('/storage/posters/p.jpg', false);
    }

    public function test_assets_use_https_behind_cloudfront(): void
    {
        // CloudFront talks to the server over plain HTTP; nginx passes the visitor's scheme as X-Forwarded-Proto.
        \Illuminate\Support\Facades\Route::get('/_asset-probe', fn () => asset('build/app.css'));

        $this->withServerVariables(['HTTP_X_FORWARDED_PROTO' => 'https'])
            ->get('/_asset-probe')->assertOk()->assertSee('https://', false);
    }

    public function test_series_is_saved_with_its_episode_links(): void
    {
        $this->withHeaders($this->admin())->post('/movies', [
            'title' => 'My Show',
            'type' => 'series',
            'genre' => 'Drama',
            'year' => 2026,
            'has_episodes' => '1',
            'episodes' => [
                ['number' => 1, 'url' => 'https://t.me/ep1'],
                ['number' => 2, 'url' => 'https://t.me/ep2'],
                ['number' => '', 'url' => ''], // empty row is ignored
            ],
        ])->assertRedirect();

        $movie = Movie::firstOrFail();
        $this->assertSame('series', $movie->type);
        $this->assertSame(2, $movie->episodes()->count());

        $this->get('/movies/'.$movie->id)->assertOk()->assertSee('Watch episode 2')->assertSee('https://t.me/ep2');
        $this->get('/')->assertOk()->assertSee('2 EPS');
    }

    public function test_episode_numbers_must_be_unique(): void
    {
        $this->withHeaders($this->admin())->post('/movies', [
            'title' => 'Dup Show',
            'type' => 'series',
            'has_episodes' => '1',
            'episodes' => [
                ['number' => 1, 'url' => 'https://t.me/a'],
                ['number' => 1, 'url' => 'https://t.me/b'],
            ],
        ])->assertSessionHasErrors('episodes');

        $this->assertSame(0, Movie::count());
    }

    public function test_episodes_are_ignored_when_the_box_is_not_ticked(): void
    {
        $this->withHeaders($this->admin())->post('/movies', [
            'title' => 'Single Film',
            'has_episodes' => '0',
            'stream_url' => 'https://t.me/film',
            'episodes' => [['number' => 1, 'url' => 'https://t.me/ignored']],
        ])->assertRedirect();

        $movie = Movie::firstOrFail();
        $this->assertSame('movie', $movie->type);
        $this->assertSame('https://t.me/film', $movie->stream_url);
        $this->assertSame(0, $movie->episodes()->count());
    }

    public function test_navbar_filters_by_type_genre_and_year(): void
    {
        Movie::create(['title' => 'Drama Show', 'type' => 'series', 'genre' => 'Drama', 'year' => 2026]);
        Movie::create(['title' => 'Action Film', 'type' => 'movie', 'genre' => 'Action', 'year' => 2020]);

        $this->get('/movies?type=series')->assertOk()->assertSee('Drama Show')->assertDontSee('Action Film');
        $this->get('/movies?genre=Action')->assertOk()->assertSee('Action Film')->assertDontSee('Drama Show');
        $this->get('/movies?year=2026')->assertOk()->assertSee('Drama Show')->assertDontSee('Action Film');
        $this->get('/suggestions')->assertOk()->assertSee('Drama Show')->assertSee('Action Film');
    }

    public function test_language_and_keywords_are_saved_and_filter_the_lists(): void
    {
        $this->withHeaders($this->admin())->post('/movies', [
            'title' => 'Hindi Love', 'type' => 'movie', 'language' => 'Hindi', 'year' => 2026,
            'tags' => ' Romantic, 2026 ,romantic, New Release!! ',
        ])->assertRedirect();
        Movie::create(['title' => 'Tamil Action', 'type' => 'movie', 'language' => 'Tamil', 'year' => 2025, 'tags' => ',action,']);
        Movie::create(['title' => 'Hindi Series', 'type' => 'series', 'language' => 'Hindi', 'tags' => ',drama,']);

        $saved = Movie::where('title', 'Hindi Love')->firstOrFail();
        $this->assertSame(',romantic,2026,new release,', $saved->tags);

        $this->get('/movies?language=Hindi')->assertOk()->assertSee('Hindi Love')->assertSee('Hindi Series')->assertDontSee('Tamil Action');
        $this->get('/movies?type=movie&language=Hindi')->assertOk()->assertSee('Hindi Love')->assertDontSee('Hindi Series');
        $this->get('/movies?tag=romantic')->assertOk()->assertSee('Hindi Love')->assertDontSee('Tamil Action');
        $this->get('/movies?tag=roman')->assertOk()->assertDontSee('Hindi Love'); // exact keyword, not a prefix
        // A keyword typed as "Sinhala" (no Language chosen) still shows up under Movies > Sinhala.
        Movie::create(['title' => 'Only Keyword', 'type' => 'movie', 'tags' => ',sinhala,']);
        $this->get('/movies?type=movie&language=Sinhala')->assertOk()->assertSee('Only Keyword')->assertDontSee('Hindi Love');
        $this->get('/movies?language=Klingon')->assertOk(); // unknown values are ignored, not an error
    }

    public function test_card_shows_play_icon_and_sinhala_sub_label_only_when_tagged(): void
    {
        $html = $this->get('/movies?type=movie')->assertOk()->getContent();

        $this->assertStringContainsString('fa-play', $html);
        $this->assertStringContainsString('සිංහල', $html);
    }

    public function test_navbar_shows_languages_on_hover_menus(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('language=Hindi', $html);
        $this->assertStringContainsString('language=Malayalam', $html);
        $this->assertStringContainsString('All Movies', $html);
    }

    public function test_visitors_can_send_a_request_and_only_the_admin_can_read_them(): void
    {
        $this->post('/request', ['title' => 'Some Movie', 'note' => 'please'])->assertRedirect(route('home'));
        $this->assertSame(1, MovieRequest::count());

        $headers = $this->admin();

        $this->get('/requests')->assertStatus(401);
        $this->withHeaders($headers)->get('/requests')->assertOk()->assertSee('Some Movie');
    }

    public function test_request_honeypot_blocks_bots(): void
    {
        $this->post('/request', ['title' => 'Spam', 'website' => 'http://spam.example'])->assertSessionHasErrors('website');
        $this->assertSame(0, MovieRequest::count());
    }

    public function test_sitemap_and_robots_use_the_site_url(): void
    {
        config(['auracinema.site_url' => 'http://example.test']);
        $movie = Movie::create(['title' => 'Inception']);

        $this->get('/sitemap.xml')->assertOk()->assertSee('http://example.test/movies/'.$movie->id);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: http://example.test/sitemap.xml');
    }

    public function test_watch_now_accepts_allowed_players_and_normalises_them(): void
    {
        $headers = $this->admin();

        $this->withHeaders($headers)->post('/movies', ['title' => 'A', 'embed_url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertRedirect();
        $this->withHeaders($headers)->post('/movies', [
            'title' => 'B',
            'embed_url' => '<iframe src="https://player.vimeo.com/video/76979871" width="100%" height="500" frameborder="0" allowfullscreen></iframe>',
        ])->assertRedirect();

        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', Movie::where('title', 'A')->value('embed_url'));
        $this->assertSame('https://player.vimeo.com/video/76979871', Movie::where('title', 'B')->value('embed_url'));
    }

    public function test_watch_now_rejects_other_hosts_and_non_https(): void
    {
        $headers = $this->admin();

        foreach (['https://streamtape.com/e/abc/', 'https://evil.example/embed/1', 'http://www.youtube.com/embed/dQw4w9WgXcQ', 'javascript:alert(1)'] as $bad) {
            $this->withHeaders($headers)->post('/movies', ['title' => 'X', 'embed_url' => $bad])->assertSessionHasErrors('embed_url');
        }
        $this->assertSame(0, Movie::count());
    }

    public function test_watch_now_player_loads_only_on_click(): void
    {
        $movie = Movie::create(['title' => 'Player Film', 'embed_url' => 'https://player.vimeo.com/video/76979871']);

        $page = $this->get('/movies/'.$movie->id)->assertOk();
        $page->assertSee('data-embed-load', false)->assertSee('data-embed-src="https://player.vimeo.com/video/76979871"', false);
        $this->assertStringNotContainsString('<iframe', $page->getContent());

        $plain = Movie::create(['title' => 'No Player']);
        $this->get('/movies/'.$plain->id)->assertOk()->assertDontSee('data-embed-load', false);
    }
}
