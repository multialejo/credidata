<?php

namespace App\StateTransitions\Exceptions;

use App\Enums\EstadoIntencionPayphone;
use DomainException;

class InvalidIntencionPayphoneTransitionException extends DomainException
{
    public static function from(EstadoIntencionPayphone $from, EstadoIntencionPayphone $to): self
    {
        return new self(sprintf(
            "Cannot transition IntencionPayphone from '%s' to '%s'",
            $from->value,
            $to->value,
        ));
    }
}
