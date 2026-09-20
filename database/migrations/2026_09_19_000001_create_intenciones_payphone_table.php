<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intenciones_payphone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('ctid')->unique();
            $table->string('payment_id')->nullable();
            $table->decimal('monto_usd', 10, 2);
            $table->integer('creditos_estimados');
            $table->string('moneda', 3)->default('USD');
            $table->string('estado')->default('pendiente'); // pendiente | confirmada | cancelada | expirada
            $table->timestamp('expira_en')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();

            $table->index(['cliente_id', 'estado']);
            $table->index(['estado', 'expira_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intenciones_payphone');
    }
};
