<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DinardapParametroSeeder extends Seeder
{
    public function run(): void
    {
        $params = [
            ['modulo' => 'dinardap', 'clave' => 'timeoutSegundos', 'valor' => json_encode('8')],
            ['modulo' => 'dinardap', 'clave' => 'cacheTTLMinutos', 'valor' => json_encode('1440')],
        ];

        DB::table('config_parametros')->insert($params);
    }
}
