<?php

namespace App\StateTransitions;

use App\Enums\EstadoIntencionPaypal;
use App\StateTransitions\Exceptions\InvalidIntencionPaypalTransitionException;

final class IntencionPaypalTransitions
{
    public const MATRIX = [
        'pendiente' => ['confirmada', 'cancelada', 'expirada'],
        'confirmada' => [],
        'cancelada' => [],
        'expirada' => [],
    ];

    public static function can(EstadoIntencionPaypal $from, EstadoIntencionPaypal $to): bool
    {
        return in_array($to->value, self::MATRIX[$from->value] ?? [], true);
    }

    public static function assert(EstadoIntencionPaypal $from, EstadoIntencionPaypal $to): void
    {
        if (! self::can($from, $to)) {
            throw InvalidIntencionPaypalTransitionException::from($from, $to);
        }
    }
}