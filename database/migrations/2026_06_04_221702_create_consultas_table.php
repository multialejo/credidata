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
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_id');
            $table->foreign('cliente_id')->references('uid')->on('clientes')->onDelete('cascade');
            $table->string('tipo'); // cedula | ruc | lote
            $table->string('identificador');
            $table->string('sujeto_id')->nullable();
            $table->string('origen')->default('api'); // api | dashboard
            $table->integer('creditos_gastados')->default(0);
            $table->json('resultado_json')->nullable();
            $table->json('fuentes_utilizadas')->nullable();
            $table->boolean('exitosa')->default(false);
            $table->string('ip_origen')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
