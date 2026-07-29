<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recargas', function (Blueprint $table) {
            $table->string('motivo_rechazo', 500)->nullable();
            $table->timestamp('rechazada_at')->nullable();
            $table->foreignId('rechazada_por')->nullable()->constrained('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recargas', function (Blueprint $table) {
            $table->dropForeign(['rechazada_por']);
            $table->dropColumn(['motivo_rechazo', 'rechazada_at', 'rechazada_por']);
        });
    }
};
