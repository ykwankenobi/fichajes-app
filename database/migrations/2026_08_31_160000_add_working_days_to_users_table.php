<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('working_days')->nullable()->after('horario_franjas');
        });

        DB::table('users')->whereNull('working_days')->update([
            'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('working_days');
        });
    }
};
