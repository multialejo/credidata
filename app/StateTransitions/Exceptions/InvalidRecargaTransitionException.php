<?php

namespace App\StateTransitions\Exceptions;

use App\Enums\EstadoRecarga;
use DomainException;

class InvalidRecargaTransitionException extends DomainException
{
    public static function from(EstadoRecarga $from, EstadoRecarga $to): self
    {
        return new self(sprintf(
            "Cannot transition Recarga from '%s' to '%s'",
            $from->value,
            $to->value,
        ));
    }
}
