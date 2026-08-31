<?php

use App\Models\WorkTimeRecord;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_time_record_corrections', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(WorkTimeRecord::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class, 'requested_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class, 'reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('original_started_at')->nullable();
            $table->dateTime('original_ended_at')->nullable();

            $table->dateTime('corrected_started_at')->nullable();
            $table->dateTime('corrected_ended_at')->nullable();

            $table->string('status')->default('pending');

            $table->text('reason');
            $table->text('review_notes')->nullable();

            $table->dateTime('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['work_time_record_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_time_record_corrections');
    }
};