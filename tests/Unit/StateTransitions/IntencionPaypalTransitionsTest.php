<?php

namespace Tests\Unit\StateTransitions;

use App\Enums\EstadoIntencionPaypal;
use App\StateTransitions\Exceptions\InvalidIntencionPaypalTransitionException;
use App\StateTransitions\IntencionPaypalTransitions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IntencionPaypalTransitionsTest extends TestCase
{
    #[Test]
    #[DataProvider('validTransitions')]
    public function can_returns_true_for_valid_transitions(EstadoIntencionPaypal $from, EstadoIntencionPaypal $to): void
    {
        $this->assertTrue(IntencionPaypalTransitions::can($from, $to));
    }

    public static function validTransitions(): array
    {
        return [
            'pendiente a confirmada' => [EstadoIntencionPaypal::Pendiente, EstadoIntencionPaypal::Confirmada],
            'pendiente a cancelada' => [EstadoIntencionPaypal::Pendiente, EstadoIntencionPaypal::Cancelada],
            'pendiente a expirada' => [EstadoIntencionPaypal::Pendiente, EstadoIntencionPaypal::Expirada],
        ];
    }

    #[Test]
    public function can_returns_false_for_invalid_transitions(): void
    {
        $this->assertFalse(IntencionPaypalTransitions::can(EstadoIntencionPaypal::Confirmada, EstadoIntencionPaypal::Pendiente));
        $this->assertFalse(IntencionPaypalTransitions::can(EstadoIntencionPaypal::Pendiente, EstadoIntencionPaypal::Pendiente));
        $this->assertFalse(IntencionPaypalTransitions::can(EstadoIntencionPaypal::Cancelada, EstadoIntencionPaypal::Confirmada));
        $this->assertFalse(IntencionPaypalTransitions::can(EstadoIntencionPaypal::Expirada, EstadoIntencionPaypal::Confirmada));
        $this->assertFalse(IntencionPaypalTransitions::can(EstadoIntencionPaypal::Cancelada, EstadoIntencionPaypal::Expirada));
    }

    #[Test]
    public function assert_does_not_throw_for_valid_transitions(): void
    {
        IntencionPaypalTransitions::assert(EstadoIntencionPaypal::Pendiente, EstadoIntencionPaypal::Confirmada);
        $this->assertTrue(true);
    }

    #[Test]
    public function assert_throws_for_invalid_transition(): void
    {
        $this->expectException(InvalidIntencionPaypalTransitionException::class);
        IntencionPaypalTransitions::assert(EstadoIntencionPaypal::Confirmada, EstadoIntencionPaypal::Cancelada);
    }
}
