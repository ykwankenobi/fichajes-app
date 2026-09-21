<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('weekly_schedule')->nullable()->after('horario_franjas');
        });

        DB::table('users')->orderBy('id')->each(function (object $user): void {
            $workingDays = json_decode($user->working_days ?: '[]', true) ?: [];
            $ranges = json_decode($user->horario_franjas ?: '[]', true) ?: [];
            $schedule = [];

            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                $schedule[$day] = in_array($day, $workingDays, true) ? $ranges : [];
            }

            DB::table('users')->where('id', $user->id)->update([
                'weekly_schedule' => json_encode($schedule),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('weekly_schedule');
        });
    }
};
