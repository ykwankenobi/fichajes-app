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
        Schema::table('users', function (Blueprint $table) {

            $table->string('dni')->nullable()->after('email');

            $table->boolean('activo')
                ->default(true)
                ->after('password');

            $table->integer('horas_semanales')
                ->nullable()
                ->after('activo');

            $table->date('fecha_alta')
                ->nullable()
                ->after('horas_semanales');

            $table->date('fecha_baja')
                ->nullable()
                ->after('fecha_alta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'dni',
                'activo',
                'horas_semanales',
                'fecha_alta',
                'fecha_baja',
            ]);
        });
    }
};