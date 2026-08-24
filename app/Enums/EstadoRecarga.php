<?php

namespace App\Enums;

enum EstadoRecarga: string
{
    case Pendiente = 'pendiente';
    case Completada = 'completada';
    case Fallida = 'fallida';
    case Rechazada = 'rechazada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Completada => 'Completada',
            self::Fallida => 'Fallida',
            self::Rechazada => 'Rechazada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'yellow',
            self::Completada => 'green',
            self::Fallida => 'red',
            self::Rechazada => 'orange',
        };
    }

    public function isTerminal(): bool
    {
        return $this !== self::Pendiente;
    }

    public static function fromString(string $value): self
    {
        return self::from($value);
    }
}
