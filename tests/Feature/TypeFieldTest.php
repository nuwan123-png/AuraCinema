<?php

namespace Tests\Feature;

use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TypeFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_has_type_dropdown_and_saving_changes_the_menu_category(): void
    {
        config(['auracinema.admin_user' => 'nuwan', 'auracinema.admin_password_hash' => Hash::make('secret-pass')]);
        $auth = ['Authorization' => 'Basic '.base64_encode('nuwan:secret-pass')];
        $movie = Movie::create(['title' => 'Vadhandhi', 'type' => 'movie']);

        $this->withHeaders($auth)->get('/movies/'.$movie->id.'/edit')->assertOk()
            ->assertSee('<select id="type" name="type"', false)->assertSee('Drama / Series');

        $this->withHeaders($auth)->put('/movies/'.$movie->id, ['title' => 'Vadhandhi', 'type' => 'series'])->assertRedirect();

        $this->assertSame('series', $movie->fresh()->type);
        $this->get('/movies?type=series')->assertSee('Vadhandhi');
        $this->get('/movies?type=movie')->assertDontSee('Vadhandhi');
    }
}
