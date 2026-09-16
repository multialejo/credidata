<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('terminos_version')->nullable()->after('usuario_id');
            $table->timestamp('terminos_aceptados_en')->nullable()->after('terminos_version');
        });

        Schema::table('aportes', function (Blueprint $table) {
            $table->text('comentario_revision')->nullable()->after('comentario_rechazo');
            $table->timestamp('revisado_en')->nullable()->after('comentario_revision');
            $table->unsignedInteger('recompensa_creditos')->default(0)->after('revisado_en');
            $table->timestamp('recompensado_en')->nullable()->after('recompensa_creditos');
            $table->boolean('aplicacion_pendiente')->default(false)->after('recompensado_en');
            $table->index(['estado', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('aportes', function (Blueprint $table) {
            $table->dropIndex(['estado', 'fecha']);
            $table->dropColumn(['comentario_revision', 'revisado_en', 'recompensa_creditos', 'recompensado_en', 'aplicacion_pendiente']);
        });

        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn(['terminos_version', 'terminos_aceptados_en']);
        });
    }
};
