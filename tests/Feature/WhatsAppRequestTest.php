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
}
