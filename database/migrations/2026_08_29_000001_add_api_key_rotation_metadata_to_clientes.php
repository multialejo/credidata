<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $table->string('api_key_formato')->default('legacy')->after('api_key_hash');
            $table->timestamp('api_key_rotacion_sugerida_en')->nullable()->after('api_key_creada');
            $table->timestamp('api_key_notificacion_rotacion_enviada')->nullable()->after('api_key_rotacion_sugerida_en');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $table->dropColumn(['api_key_formato', 'api_key_rotacion_sugerida_en', 'api_key_notificacion_rotacion_enviada']);
        });
    }
};
