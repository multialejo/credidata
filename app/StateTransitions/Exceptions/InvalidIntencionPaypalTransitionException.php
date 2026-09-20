<?php

namespace App\StateTransitions\Exceptions;

use App\Enums\EstadoIntencionPaypal;
use DomainException;

class InvalidIntencionPaypalTransitionException extends DomainException
{
    public static function from(EstadoIntencionPaypal $from, EstadoIntencionPaypal $to): self
    {
        return new self(sprintf(
            "Cannot transition IntencionPaypal from '%s' to '%s'",
            $from->value,
            $to->value,
        ));
    }
}