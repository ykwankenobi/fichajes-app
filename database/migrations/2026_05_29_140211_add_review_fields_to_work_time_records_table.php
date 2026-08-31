<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_time_records', function (Blueprint $table) {
			$table
				->foreignId('reviewed_by')
				->nullable()
				->after('requires_review')
				->constrained('users')
				->nullOnDelete();

			$table
				->timestamp('reviewed_at')
				->nullable()
				->after('reviewed_by');

			$table
				->text('review_notes')
				->nullable()
				->after('reviewed_at');
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_time_records', function (Blueprint $table) {
			$table->dropForeign(['reviewed_by']);

			$table->dropColumn([
				'reviewed_by',
				'reviewed_at',
				'review_notes',
			]);
		});
    }
};
