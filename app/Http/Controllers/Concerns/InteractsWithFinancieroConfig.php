<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ConfigParametro;

trait InteractsWithFinancieroConfig
{
    protected function getTasaCambioUsdCreditos(): int
    {
        $param = ConfigParametro::where('modulo', 'financiero')
            ->where('clave', 'tasaCambioUsdCreditos')
            ->first();

        return $param ? (int) json_decode($param->valor) : 10;
    }

    protected function getRecargaMinimaUsd(): float
    {
        $param = ConfigParametro::where('modulo', 'financiero')
            ->where('clave', 'recargaMinimaUsd')
            ->first();

        return $param ? (float) json_decode($param->valor) : 5.00;
    }
}
