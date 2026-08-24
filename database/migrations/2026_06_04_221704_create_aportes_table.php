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
        Schema::create('aportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('identificador_relacionado');
            $table->string('tipo_dato'); // telefono | email | direccion
            $table->string('valor');
            $table->string('evidencia_url')->nullable();
            $table->string('estado')->default('pendiente'); // pendiente | aprobado | rechazado
            $table->unsignedBigInteger('revisado_por')->nullable();
            $table->text('comentario_rechazo')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aportes');
    }
};
