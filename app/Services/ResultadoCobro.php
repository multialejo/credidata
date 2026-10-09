<?php

namespace App\Services;

use App\Models\Consulta;

final class ResultadoCobro
{
    public function __construct(
        public readonly Consulta $consulta,
        public readonly float $saldoAnterior,
        public readonly float $saldoNuevo,
    ) {}
}
