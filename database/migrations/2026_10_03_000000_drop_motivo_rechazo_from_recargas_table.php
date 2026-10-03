<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recargas', function (Blueprint $table): void {
            $table->dropColumn('motivo_rechazo');
        });
    }

    public function down(): void
    {
        Schema::table('recargas', function (Blueprint $table): void {
            $table->string('motivo_rechazo', 500)->nullable();
        });
    }
};
