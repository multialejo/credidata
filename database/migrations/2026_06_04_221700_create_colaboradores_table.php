<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('colaboradores', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->foreign('uid')->references('uid')->on('usuarios')->onDelete('cascade');
            $table->integer('creditos_ganados')->default(0);
            $table->integer('creditos_acreditados')->default(0);
            $table->integer('total_aportes')->default(0);
            $table->integer('aportes_aprobados')->default(0);
            $table->integer('aportes_rechazados')->default(0);
            $table->decimal('tasa_aprobacion', 5, 2)->default(0);
            $table->string('nivel_confianza')->default('bronce');
            $table->string('estado_colaborador')->default('activo');
            $table->timestamp('fecha_suspension')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaboradores');
    }
};
