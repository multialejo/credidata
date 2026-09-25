<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'limiteDiarioPorIdentificador' => 3,
            'limiteDiarioPorColaborador' => 10,
        ] as $clave => $valor) {
            DB::table('config_parametros')->updateOrInsert(
                ['modulo' => 'colaboracion', 'clave' => $clave],
                ['valor' => json_encode($valor), 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        DB::table('config_parametros')
            ->where('modulo', 'colaboracion')
            ->whereIn('clave', ['limiteDiarioPorIdentificador', 'limiteDiarioPorColaborador'])
            ->delete();
    }
};
