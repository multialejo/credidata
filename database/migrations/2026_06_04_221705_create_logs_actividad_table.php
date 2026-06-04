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
        Schema::create('logs_actividad', function (Blueprint $table) {
            $table->id();
            $table->string('accion');
            $table->string('actor_id')->nullable();
            $table->boolean('actor_sistema')->default(false);
            $table->json('detalle')->nullable();
            $table->string('ip_origen')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            // Sin FK en actor_id para permitir NULL (acciones del sistema)
            // La FK se valida a nivel de aplicación
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs_actividad');
    }
};
