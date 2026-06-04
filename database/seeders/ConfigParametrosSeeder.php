<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigParametrosSeeder extends Seeder
{
    public function run(): void
    {
        $params = [
            ['modulo' => 'financiero',     'clave' => 'tasaCambioUsdCreditos',            'valor' => json_encode('10')],
            ['modulo' => 'financiero',     'clave' => 'costoConsultaBase',                'valor' => json_encode('1')],
            ['modulo' => 'financiero',     'clave' => 'recargaMinimaUsd',                 'valor' => json_encode('5.00')],
            ['modulo' => 'financiero',     'clave' => 'recompensaAporteCreditos',         'valor' => json_encode('2')],
            ['modulo' => 'financiero',     'clave' => 'umbralSaldoBajoCreditos',          'valor' => json_encode('10')],
            ['modulo' => 'rateLimiting',   'clave' => 'limitePorMinutoPorDefecto',        'valor' => json_encode('30')],
            ['modulo' => 'rateLimiting',   'clave' => 'limitePorDiaPorDefecto',           'valor' => json_encode('1000')],
            ['modulo' => 'rateLimiting',   'clave' => 'maxIntentosFallidosConsecutivos',  'valor' => json_encode('10')],
            ['modulo' => 'colaboracion',   'clave' => 'umbralSuspensionTasaRechazo',      'valor' => json_encode('0.5')],
            ['modulo' => 'colaboracion',   'clave' => 'diasEvaluacionTasa',               'valor' => json_encode('30')],
            ['modulo' => 'colaboracion',   'clave' => 'recompensaNuevoRegistroCreditos',  'valor' => json_encode('1')],
            ['modulo' => 'apiKeys',        'clave' => 'diasSugerenciaRotacion',           'valor' => json_encode('90')],
            ['modulo' => 'apiKeys',        'clave' => 'diasNotificacionAnticipada',        'valor' => json_encode('7')],
            ['modulo' => 'cache',          'clave' => 'ttlDatosExternosSegundos',         'valor' => json_encode('86400')],
        ];

        DB::table('config_parametros')->insert($params);
    }
}
