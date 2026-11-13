<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_carries_the_google_verification_tag(): void
    {
        $this->get('/movies')->assertOk()
            ->assertSee('<meta name="google-site-verification" content="QAEtx_c2iJIWFBV0gO1khYOkx1Bdb4pc5ygvhmzPqYg">', false);
    }

    public function test_tag_is_left_out_when_no_token_is_set(): void
    {
        config(['auracinema.google_verification' => null]);

        $this->get('/movies')->assertOk()->assertDontSee('google-site-verification');
    }
}
