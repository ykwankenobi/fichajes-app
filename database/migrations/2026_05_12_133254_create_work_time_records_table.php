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
        Schema::create('work_time_records', function (Blueprint $table) {
            $table->id();

            // Usuario relacionado
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Inicio de jornada
            $table->dateTime('started_at');

            // Fin de jornada
            $table->dateTime('ended_at')->nullable();

            // Tipo de salida
            $table->enum('end_type', [
                'end_shift',
                'justified_exit',
                'unjustified_exit',
            ])->nullable();

            // Minutos trabajados
            $table->unsignedInteger('worked_minutes')->default(0);

            // Minutos de salidas justificadas
            $table->unsignedInteger('justified_exit_minutes')->default(0);

            // Minutos de salidas sin justificar
            $table->unsignedInteger('unjustified_exit_minutes')->default(0);

            // Observaciones opcionales
            $table->text('notes')->nullable();

            $table->timestamps();

            // Índice para informes
            $table->index(['user_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_time_records');
    }
};