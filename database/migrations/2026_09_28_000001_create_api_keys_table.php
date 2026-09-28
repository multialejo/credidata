<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->string('prefijo')->unique();
            $table->string('hash');
            $table->string('formato')->default('v2');
            $table->timestamp('creada_en')->useCurrent();
            $table->boolean('revocada')->default(false);
            $table->timestamp('revocada_en')->nullable();
            $table->timestamp('ultimo_uso_en')->nullable();
            $table->json('ips_permitidas')->nullable();
            $table->json('alcance')->nullable();
            $table->timestamp('rotacion_sugerida_en')->nullable();
            $table->timestamp('notificacion_rotacion_enviada_en')->nullable();
            $table->timestamps();

            $table->unique(['cliente_id', 'nombre']);
            $table->index(['cliente_id', 'revocada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
