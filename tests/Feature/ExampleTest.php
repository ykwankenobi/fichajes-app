<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_redirects_to_the_user_login_screen(): void
    {
        $response = $this->get('/');

        $response
            ->assertRedirect('/login');
    }
}
