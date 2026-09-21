<?php

namespace Tests\Unit;

use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class WeeklyScheduleTest extends TestCase
{
    public function test_it_calculates_the_ranges_configured_for_each_day(): void
    {
        $user = new User([
            'weekly_schedule' => [
                'monday' => [['desde' => '07:00', 'hasta' => '15:00']],
                'tuesday' => [
                    ['desde' => '09:00', 'hasta' => '11:00'],
                    ['desde' => '17:00', 'hasta' => '20:00'],
                ],
            ],
        ]);

        $this->assertSame(480, $user->scheduledMinutesForDay(Carbon::parse('2026-09-21')));
        $this->assertSame(300, $user->scheduledMinutesForDay(Carbon::parse('2026-09-22')));
        $this->assertSame(0, $user->scheduledMinutesForDay(Carbon::parse('2026-09-23')));
        $this->assertSame(['monday', 'tuesday'], $user->scheduledWorkingDays());
    }

    public function test_it_uses_legacy_ranges_when_a_weekly_schedule_is_not_present(): void
    {
        $user = new User([
            'working_days' => ['monday'],
            'horario_franjas' => [['desde' => '08:00', 'hasta' => '14:00']],
        ]);

        $this->assertSame(360, $user->scheduledMinutesForDay(Carbon::parse('2026-09-21')));
        $this->assertSame(0, $user->scheduledMinutesForDay(Carbon::parse('2026-09-22')));
    }
}
