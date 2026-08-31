<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkTimeRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KioskWorkTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiosk_screen_can_be_rendered(): void
    {
        User::factory()->create([
            'name' => 'Empleado Activo',
            'pin_hash' => Hash::make('1234'),
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Fichajes')
            ->assertSee('Empleado Activo');

        $this->assertGuest();
    }

    public function test_kiosk_rejects_invalid_pin_without_authenticating(): void
    {
        $user = User::factory()->create([
            'pin_hash' => Hash::make('1234'),
        ]);

        $response = $this->from('/')->post(route('kiosk.verify'), [
            'user_id' => $user->id,
            'pin' => '9999',
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_kiosk_accepts_valid_pin_and_shows_work_time_actions(): void
    {
        $user = User::factory()->create([
            'name' => 'Empleado PIN',
            'pin_hash' => Hash::make('1234'),
        ]);

        $response = $this->post(route('kiosk.verify'), [
            'user_id' => $user->id,
            'pin' => '1234',
        ]);

        $response->assertRedirect();

        $showResponse = $this->get($response->headers->get('Location'));

        $showResponse
            ->assertOk()
            ->assertSee('Empleado PIN')
            ->assertSee('Fichar entrada');

        $this->assertGuest();
    }

    public function test_kiosk_can_clock_in_with_temporary_token(): void
    {
        $user = User::factory()->create([
            'pin_hash' => Hash::make('1234'),
        ]);

        $verifyResponse = $this->post(route('kiosk.verify'), [
            'user_id' => $user->id,
            'pin' => '1234',
        ]);

        $tokenUrl = $verifyResponse->headers->get('Location');
        $token = basename($tokenUrl);

        $response = $this->post(route('kiosk.clock-in', $token));

        $response
            ->assertRedirect(route('kiosk.index'))
            ->assertSessionHas('success', 'Entrada registrada correctamente.');

        $this->assertDatabaseHas('work_time_records', [
            'user_id' => $user->id,
            'record_type' => WorkTimeRecord::TYPE_WORK,
            'ended_at' => null,
        ]);

        $this->assertGuest();
    }

    public function test_kiosk_can_clock_out_with_the_same_exit_flow(): void
    {
        $user = User::factory()->create([
            'pin_hash' => Hash::make('1234'),
        ]);

        $user->workTimeRecords()->create([
            'record_type' => WorkTimeRecord::TYPE_WORK,
            'started_at' => now()->subHours(2),
        ]);

        $verifyResponse = $this->post(route('kiosk.verify'), [
            'user_id' => $user->id,
            'pin' => '1234',
        ]);

        $token = basename($verifyResponse->headers->get('Location'));

        $response = $this->post(route('kiosk.clock-out', $token), [
            'end_type' => 'justified_exit',
        ]);

        $response
            ->assertRedirect(route('kiosk.index'))
            ->assertSessionHas('success', 'Salida registrada correctamente.');

        $this->assertDatabaseHas('work_time_records', [
            'user_id' => $user->id,
            'record_type' => WorkTimeRecord::TYPE_JUSTIFIED_EXIT,
            'ended_at' => null,
        ]);

        $this->assertGuest();
    }
}