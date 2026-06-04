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
        Schema::create('config_rate_overrides', function (Blueprint $table) {
            $table->string('cliente_uid')->primary();
            $table->foreign('cliente_uid')->references('uid')->on('clientes')->onDelete('cascade');
            $table->integer('por_minuto');
            $table->integer('por_dia');
            $table->string('actualizado_por')->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_rate_overrides');
    }
};
