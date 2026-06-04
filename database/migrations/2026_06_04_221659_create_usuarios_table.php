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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->string('email')->unique();
            $table->string('firebase_uid')->unique()->nullable();
            $table->string('nombre');
            $table->string('estado')->default('activo'); // activo | inactivo | suspendido
            $table->timestamp('fecha_registro')->useCurrent();
            $table->json('roles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
