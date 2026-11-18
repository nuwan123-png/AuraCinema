<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\PageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VisitorStatsTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';
    private const PC = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36';

    private function admin(): array
    {
        config(['auracinema.admin_user' => 'nuwan', 'auracinema.admin_password_hash' => Hash::make('secret-pass')]);

        return ['Authorization' => 'Basic '.base64_encode('nuwan:secret-pass')];
    }

    public function test_a_normal_visit_is_recorded_without_the_ip_address(): void
    {
        $movie = Movie::create(['title' => 'Happy Raj (2026)']);

        $this->withHeaders(['User-Agent' => self::PHONE, 'Referer' => 'https://www.google.com/'])
            ->get(route('movies.show', $movie))->assertOk();

        $view = PageView::first();
        $this->assertSame($movie->id, $view->movie_id);
        $this->assertSame('google.com', $view->referrer_host);
        $this->assertSame('mobile', $view->device);
        $this->assertSame('/movies/'.$movie->id, $view->path);
        $this->assertStringNotContainsString('127.0.0.1', json_encode($view->getAttributes()));
    }

    public function test_bots_admin_browsers_and_failed_pages_are_not_counted(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])->get('/')->assertOk();
        $this->withCookie('aura_admin', '1')->withHeaders(['User-Agent' => self::PC])->get('/')->assertOk();
        $this->withHeaders(['User-Agent' => self::PC])->get('/movies/99999')->assertNotFound();

        $this->assertSame(0, PageView::count());
    }

    public function test_same_person_counts_once_as_a_visitor_but_every_page_as_a_view(): void
    {
        $this->withHeaders(['User-Agent' => self::PC])->get('/');
        $this->withHeaders(['User-Agent' => self::PC])->get('/');
        $this->withHeaders(['User-Agent' => self::PHONE])->get('/');

        $this->assertSame(3, PageView::count());
        $this->assertSame(2, PageView::distinct('visitor')->count('visitor'));
    }

    public function test_stats_page_is_admin_only_and_shows_the_numbers(): void
    {
        $movie = Movie::create(['title' => 'Island Python']);
        $this->withHeaders(['User-Agent' => self::PC])->get(route('movies.show', $movie));
        $this->withHeaders(['User-Agent' => self::PHONE, 'Referer' => 'https://facebook.com/x'])->get(route('movies.show', $movie));

        $auth = $this->admin();
        $this->get('/admin/stats')->assertStatus(401);

        $this->withHeaders($auth)->get('/admin/stats')->assertOk()
            ->assertSee('Visitor stats')
            ->assertSee('Island Python')
            ->assertSee('facebook.com')
            ->assertSee('2 visitors');
    }

    public function test_stats_page_works_with_no_data(): void
    {
        $this->withHeaders($this->admin())->get('/admin/stats')->assertOk()->assertSee('No movie views yet.');
    }
}
