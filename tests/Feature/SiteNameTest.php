<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_declares_the_site_name_for_google(): void
    {
        config(['auracinema.site_url' => 'https://auracinema.duckdns.org']);

        $this->get('/')->assertOk()
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('"name":"AuraCinema"', false)
            ->assertSee('"url":"https://auracinema.duckdns.org/"', false);
    }
}
