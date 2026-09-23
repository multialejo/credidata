<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('config_parametros')->updateOrInsert(
            ['modulo' => 'financiero', 'clave' => 'creditosBienvenida'],
            ['valor' => json_encode(0), 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function down(): void
    {
        DB::table('config_parametros')
            ->where('modulo', 'financiero')
            ->where('clave', 'creditosBienvenida')
            ->delete();
    }
};
