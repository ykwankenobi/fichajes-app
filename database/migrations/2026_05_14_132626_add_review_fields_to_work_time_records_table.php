<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_time_records', function (Blueprint $table) {
            $table->boolean('closed_automatically')
                ->default(false)
                ->after('notes');

            $table->boolean('requires_review')
                ->default(false)
                ->after('closed_automatically');
        });
    }

    public function down(): void
    {
        Schema::table('work_time_records', function (Blueprint $table) {
            $table->dropColumn([
                'closed_automatically',
                'requires_review',
            ]);
        });
    }
};