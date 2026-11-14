<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_link_in_the_menu_opens_whatsapp_with_a_ready_message(): void
    {
        $this->get('/movies')->assertOk()
            ->assertSee('https://wa.me/94788402670?text=Hi%20AuraCinema%2C%20I%27d%20like%20to%20request%20a%20movie%3A%20', false)
            ->assertSee('target="_blank" rel="noopener"', false);
    }

    public function test_number_can_be_changed_with_the_setting_and_is_cleaned(): void
    {
        config(['auracinema.whatsapp_number' => '+94 77 123 4567']);

        $this->get('/movies')->assertSee('https://wa.me/94771234567?text=', false);
    }

    public function test_report_error_button_opens_whatsapp_naming_this_movie(): void
    {
        $movie = \App\Models\Movie::create(['title' => 'OIC Gadafi (2026)']);
        $text = rawurlencode('Hi AuraCinema, I have an error in "OIC Gadafi (2026)" movie: ');

        $this->get(route('movies.show', $movie))->assertOk()
            ->assertSee('Report error')
            ->assertSee('https://wa.me/94788402670?text='.$text, false)
            ->assertSee('මෙම චිත්‍රපටය පිලිබද ගැටලුවක් තිබේනම් කියන්න.');
    }
}
