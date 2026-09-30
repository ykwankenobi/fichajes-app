<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KioskSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_refresh_returns_current_token_without_caching(): void
    {
        $this->withSession(['_token' => 'current-token'])
            ->getJson(route('kiosk.session'))
            ->assertOk()
            ->assertJson(['token' => 'current-token'])
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertGuest();
    }

    public function test_expired_kiosk_post_redirects_without_recording_attendance(): void
    {
        // Laravel bypasses CSRF in tests unless this check is explicitly enabled.
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        foreach (['kiosk.verify', 'kiosk.clock-in', 'kiosk.clock-out', 'kiosk.finish-exit'] as $route) {
            $this->withSession(['_token' => 'new-token'])
                ->post(route($route, $route === 'kiosk.verify' ? [] : ['token' => 'expired']), ['_token' => 'old-token'])
                ->assertRedirect(route('kiosk.index', ['session_expired' => 1]));
        }

        $this->assertDatabaseCount('work_time_records', 0);
        $this->withoutVite()->get(route('kiosk.index', ['session_expired' => 1]))
            ->assertOk()->assertSee('La sesión ha caducado. Introduce el PIN de nuevo.');

        $this->post(route('login'), ['_token' => 'old-token'])->assertStatus(419);
    }
}
