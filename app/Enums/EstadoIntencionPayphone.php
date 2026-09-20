<?php

namespace App\Enums;

enum EstadoIntencionPayphone: string
{
    case Pendiente = 'pendiente';
    case Confirmada = 'confirmada';
    case Cancelada = 'cancelada';
    case Expirada = 'expirada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Confirmada => 'Confirmada',
            self::Cancelada => 'Cancelada',
            self::Expirada => 'Expirada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'yellow',
            self::Confirmada => 'green',
            self::Cancelada => 'red',
            self::Expirada => 'gray',
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
