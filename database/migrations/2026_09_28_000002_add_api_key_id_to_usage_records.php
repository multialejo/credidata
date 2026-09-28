<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultas', function (Blueprint $table): void {
            $table->foreignId('api_key_id')->nullable()->after('cliente_id')
                ->constrained('api_keys')->nullOnDelete();
        });

        Schema::table('aportes', function (Blueprint $table): void {
            $table->foreignId('api_key_id')->nullable()->after('colaborador_id')
                ->constrained('api_keys')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aportes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('api_key_id');
        });

        Schema::table('consultas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('api_key_id');
        });
    }
};
