<?php

namespace Tests\Unit\StateTransitions;

use App\Enums\EstadoIntencionPayphone;
use App\StateTransitions\Exceptions\InvalidIntencionPayphoneTransitionException;
use App\StateTransitions\IntencionPayphoneTransitions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IntencionPayphoneTransitionsTest extends TestCase
{
    #[Test]
    #[DataProvider('validTransitions')]
    public function can_returns_true_for_valid_transitions(EstadoIntencionPayphone $from, EstadoIntencionPayphone $to): void
    {
        $this->assertTrue(IntencionPayphoneTransitions::can($from, $to));
    }

    public static function validTransitions(): array
    {
        return [
            'pendiente a confirmada' => [EstadoIntencionPayphone::Pendiente, EstadoIntencionPayphone::Confirmada],
            'pendiente a cancelada' => [EstadoIntencionPayphone::Pendiente, EstadoIntencionPayphone::Cancelada],
            'pendiente a expirada' => [EstadoIntencionPayphone::Pendiente, EstadoIntencionPayphone::Expirada],
        ];
    }

    #[Test]
    public function can_returns_false_for_invalid_transitions(): void
    {
        $this->assertFalse(IntencionPayphoneTransitions::can(EstadoIntencionPayphone::Confirmada, EstadoIntencionPayphone::Pendiente));
        $this->assertFalse(IntencionPayphoneTransitions::can(EstadoIntencionPayphone::Pendiente, EstadoIntencionPayphone::Pendiente));
        $this->assertFalse(IntencionPayphoneTransitions::can(EstadoIntencionPayphone::Cancelada, EstadoIntencionPayphone::Confirmada));
        $this->assertFalse(IntencionPayphoneTransitions::can(EstadoIntencionPayphone::Expirada, EstadoIntencionPayphone::Confirmada));
        $this->assertFalse(IntencionPayphoneTransitions::can(EstadoIntencionPayphone::Cancelada, EstadoIntencionPayphone::Expirada));
    }

    #[Test]
    public function assert_does_not_throw_for_valid_transitions(): void
    {
        IntencionPayphoneTransitions::assert(EstadoIntencionPayphone::Pendiente, EstadoIntencionPayphone::Confirmada);
        $this->assertTrue(true);
    }

    #[Test]
    public function assert_throws_for_invalid_transition(): void
    {
        $this->expectException(InvalidIntencionPayphoneTransitionException::class);
        IntencionPayphoneTransitions::assert(EstadoIntencionPayphone::Confirmada, EstadoIntencionPayphone::Cancelada);
    }
}
