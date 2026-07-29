<?php

namespace App\StateTransitions;

use App\Enums\EstadoRecarga;
use App\StateTransitions\Exceptions\InvalidRecargaTransitionException;

final class RecargaTransitions
{
    public const MATRIX = [
        'pendiente' => ['completada', 'fallida', 'rechazada'],
        'completada' => [],
        'fallida' => [],
        'rechazada' => [],
    ];

    public static function can(EstadoRecarga $from, EstadoRecarga $to): bool
    {
        return in_array($to->value, self::MATRIX[$from->value] ?? [], true);
    }

    public static function assert(EstadoRecarga $from, EstadoRecarga $to): void
    {
        if (! self::can($from, $to)) {
            throw InvalidRecargaTransitionException::from($from, $to);
        }
    }
}
