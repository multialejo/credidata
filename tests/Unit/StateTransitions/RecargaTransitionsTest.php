<?php

namespace Tests\Unit\StateTransitions;

use App\Enums\EstadoRecarga;
use App\StateTransitions\Exceptions\InvalidRecargaTransitionException;
use App\StateTransitions\RecargaTransitions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RecargaTransitionsTest extends TestCase
{
    #[Test]
    #[DataProvider('validTransitions')]
    public function can_returns_true_for_valid_transitions(EstadoRecarga $from, EstadoRecarga $to): void
    {
        $this->assertTrue(RecargaTransitions::can($from, $to));
    }

    public static function validTransitions(): array
    {
        return [
            'pendiente a completada' => [EstadoRecarga::Pendiente, EstadoRecarga::Completada],
            'pendiente a fallida' => [EstadoRecarga::Pendiente, EstadoRecarga::Fallida],
            'pendiente a rechazada' => [EstadoRecarga::Pendiente, EstadoRecarga::Rechazada],
        ];
    }

    #[Test]
    public function can_returns_false_for_invalid_transitions(): void
    {
        $this->assertFalse(RecargaTransitions::can(EstadoRecarga::Completada, EstadoRecarga::Pendiente));
        $this->assertFalse(RecargaTransitions::can(EstadoRecarga::Pendiente, EstadoRecarga::Pendiente));
        $this->assertFalse(RecargaTransitions::can(EstadoRecarga::Fallida, EstadoRecarga::Completada));
        $this->assertFalse(RecargaTransitions::can(EstadoRecarga::Rechazada, EstadoRecarga::Completada));
    }

    #[Test]
    public function assert_does_not_throw_for_valid_transitions(): void
    {
        RecargaTransitions::assert(EstadoRecarga::Pendiente, EstadoRecarga::Completada);
        $this->assertTrue(true);
    }

    #[Test]
    public function assert_throws_for_invalid_transition(): void
    {
        $this->expectException(InvalidRecargaTransitionException::class);
        RecargaTransitions::assert(EstadoRecarga::Completada, EstadoRecarga::Pendiente);
    }
}
