<?php

namespace Tests\Feature;

use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_search_filters_by_title(): void
    {
        Movie::create(['title' => 'Inception']);
        Movie::create(['title' => 'Interstellar']);

        $this->get('/movies?q=Incep')->assertOk()->assertSee('Inception')->assertDontSee('Interstellar');
    }

    public function test_admin_routes_reject_guests(): void
    {
        config(['auracinema.admin_user' => 'nuwan', 'auracinema.admin_password_hash' => Hash::make('secret-pass')]);

        $this->get('/movies/create')->assertStatus(401);
        $this->post('/movies', ['title' => 'x'])->assertStatus(401);
        $this->delete('/movies/1')->assertStatus(401);
    }

    public function test_admin_routes_accept_correct_credentials(): void
    {
        config(['auracinema.admin_user' => 'nuwan', 'auracinema.admin_password_hash' => Hash::make('secret-pass')]);

        $this->withHeaders(['Authorization' => 'Basic '.base64_encode('nuwan:secret-pass')])
            ->get('/movies/create')->assertOk();
        $this->withHeaders(['Authorization' => 'Basic '.base64_encode('nuwan:wrong')])
            ->get('/movies/create')->assertStatus(401);
    }

    public function test_stream_url_must_be_http_or_https(): void
    {
        config(['auracinema.admin_user' => 'nuwan', 'auracinema.admin_password_hash' => Hash::make('secret-pass')]);

        $this->withHeaders(['Authorization' => 'Basic '.base64_encode('nuwan:secret-pass')])
            ->post('/movies', ['title' => 'Bad', 'stream_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('stream_url');
    }
}
