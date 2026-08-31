<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_shows_the_kiosk_work_time_screen(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Fichajes')
            ->assertSee('Selecciona tu nombre');
    }
}