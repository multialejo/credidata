<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_COLUMNS = [
        'api_key_prefijo', 'api_key_hash', 'api_key_alias', 'api_key_creada',
        'api_key_revocada', 'api_key_revocada_en', 'api_key_ultimo_uso',
        'api_key_ips_permitidas', 'api_key_alcance', 'api_key_formato',
        'api_key_rotacion_sugerida_en', 'api_key_notificacion_rotacion_enviada',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('clientes', 'api_key_prefijo')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table): void {
            $table->dropUnique('clientes_api_key_prefijo_unique');
            $table->dropColumn(self::LEGACY_COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $table->string('api_key_prefijo')->nullable()->unique();
            $table->string('api_key_hash')->nullable();
            $table->string('api_key_alias')->nullable();
            $table->timestamp('api_key_creada')->nullable();
            $table->boolean('api_key_revocada')->default(false);
            $table->timestamp('api_key_revocada_en')->nullable();
            $table->timestamp('api_key_ultimo_uso')->nullable();
            $table->json('api_key_ips_permitidas')->nullable();
            $table->json('api_key_alcance')->nullable();
            $table->string('api_key_formato')->default('v2');
            $table->timestamp('api_key_rotacion_sugerida_en')->nullable();
            $table->timestamp('api_key_notificacion_rotacion_enviada')->nullable();
        });
    }
};
