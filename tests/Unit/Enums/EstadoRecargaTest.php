<?php

namespace Tests\Unit\Enums;

use App\Enums\EstadoRecarga;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;

class EstadoRecargaTest extends TestCase
{
    #[Test]
    public function cases_have_expected_string_values(): void
    {
        $this->assertSame('pendiente', EstadoRecarga::Pendiente->value);
        $this->assertSame('completada', EstadoRecarga::Completada->value);
        $this->assertSame('fallida', EstadoRecarga::Fallida->value);
        $this->assertSame('rechazada', EstadoRecarga::Rechazada->value);
    }

    #[Test]
    public function label_returns_spanish_label(): void
    {
        $this->assertSame('Pendiente', EstadoRecarga::Pendiente->label());
        $this->assertSame('Completada', EstadoRecarga::Completada->label());
        $this->assertSame('Fallida', EstadoRecarga::Fallida->label());
        $this->assertSame('Rechazada', EstadoRecarga::Rechazada->label());
    }

    #[Test]
    public function color_returns_tailwind_palette_name(): void
    {
        $this->assertSame('yellow', EstadoRecarga::Pendiente->color());
        $this->assertSame('green', EstadoRecarga::Completada->color());
        $this->assertSame('red', EstadoRecarga::Fallida->color());
        $this->assertSame('orange', EstadoRecarga::Rechazada->color());
    }

    #[Test]
    public function is_terminal_is_true_except_for_pendiente(): void
    {
        $this->assertFalse(EstadoRecarga::Pendiente->isTerminal());
        $this->assertTrue(EstadoRecarga::Completada->isTerminal());
        $this->assertTrue(EstadoRecarga::Fallida->isTerminal());
        $this->assertTrue(EstadoRecarga::Rechazada->isTerminal());
    }

    #[Test]
    public function from_string_throws_for_unknown_value(): void
    {
        $this->expectException(ValueError::class);
        EstadoRecarga::fromString('desconocida');
    }
}
