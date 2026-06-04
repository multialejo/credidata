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
        Schema::create('recargas', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_id');
            $table->foreign('cliente_id')->references('uid')->on('clientes')->onDelete('cascade');
            $table->string('metodo'); // paypal | payphone | transferencia | bonificacion
            $table->decimal('monto_usd', 10, 2)->default(0);
            $table->integer('creditos_obtenidos')->default(0);
            $table->string('estado')->default('pendiente'); // completada | pendiente | rechazada
            $table->string('referencia_externa')->nullable()->unique();
            $table->string('comprobante_url')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recargas');
    }
};
