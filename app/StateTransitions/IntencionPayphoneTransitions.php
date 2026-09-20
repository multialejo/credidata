<?php

namespace App\StateTransitions;

use App\Enums\EstadoIntencionPayphone;
use App\StateTransitions\Exceptions\InvalidIntencionPayphoneTransitionException;

final class IntencionPayphoneTransitions
{
    public const MATRIX = [
        'pendiente' => ['confirmada', 'cancelada', 'expirada'],
        'confirmada' => [],
        'cancelada' => [],
        'expirada' => [],
    ];

    public static function can(EstadoIntencionPayphone $from, EstadoIntencionPayphone $to): bool
    {
        return in_array($to->value, self::MATRIX[$from->value] ?? [], true);
    }

    public static function assert(EstadoIntencionPayphone $from, EstadoIntencionPayphone $to): void
    {
        if (! self::can($from, $to)) {
            throw InvalidIntencionPayphoneTransitionException::from($from, $to);
        }
    }
}
