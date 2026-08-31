<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_time_records', function (Blueprint $table) {
            $table->string('record_type')
                ->default('work')
                ->after('user_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('work_time_records', function (Blueprint $table) {
            $table->dropColumn('record_type');
        });
    }
};