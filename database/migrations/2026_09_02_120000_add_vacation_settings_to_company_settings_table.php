<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->string('vacation_counting_method')->default('working')->after('working_days');
            $table->unsignedSmallInteger('annual_vacation_days')->default(22)->after('vacation_counting_method');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn(['vacation_counting_method', 'annual_vacation_days']);
        });
    }
};
