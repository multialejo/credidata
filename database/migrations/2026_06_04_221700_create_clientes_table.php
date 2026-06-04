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
        Schema::create('clientes', function (Blueprint $table) {
            $table->string('uid')->primary();
            $table->foreign('uid')->references('uid')->on('usuarios')->onDelete('cascade');
            $table->decimal('saldo_creditos', 10, 2)->default(0);
            $table->string('metodo_pago_preferido')->default('paypal');
            $table->string('api_key_prefijo')->unique()->nullable();
            $table->string('api_key_hash')->nullable();
            $table->string('api_key_alias')->nullable();
            $table->timestamp('api_key_creada')->nullable();
            $table->boolean('api_key_revocada')->default(false);
            $table->timestamp('api_key_revocada_en')->nullable();
            $table->timestamp('api_key_ultimo_uso')->nullable();
            $table->json('api_key_ips_permitidas')->nullable();
            $table->json('api_key_alcance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
