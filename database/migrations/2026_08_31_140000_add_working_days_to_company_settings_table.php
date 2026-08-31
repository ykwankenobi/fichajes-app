<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->json('working_days')->nullable()->after('holiday_municipality_ine');
        });

        DB::table('company_settings')->whereNull('working_days')->update([
            'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
        ]);
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn('working_days');
        });
    }
};
