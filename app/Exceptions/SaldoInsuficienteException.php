<?php

namespace App\Exceptions;

use RuntimeException;

class SaldoInsuficienteException extends RuntimeException
{
    public function __construct(
        public readonly float $saldoActual,
        public readonly int $costo,
    ) {
        parent::__construct('Saldo insuficiente para realizar la consulta.');
    }
}
