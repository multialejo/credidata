<?php

namespace Tests\Unit\Rules;

use App\Rules\EcuadorianIdentificador;
use Tests\TestCase;

class EcuadorianIdentificadorTest extends TestCase
{
    private EcuadorianIdentificador $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new EcuadorianIdentificador;
    }

    public function test_valid_cedula(): void
    {
        $this->assertTrue($this->validate('1713175071'));
    }

    public function test_invalid_cedula_checksum(): void
    {
        $this->assertFalse($this->validate('1713175072'));
    }

    public function test_valid_ruc(): void
    {
        $this->assertTrue($this->validate('1713175071003'));
    }

    public function test_invalid_ruc_checksum(): void
    {
        $this->assertFalse($this->validate('1713175071001'));
    }

    public function test_rejects_short_length(): void
    {
        $this->assertFalse($this->validate('123456789'));
    }

    public function test_rejects_length_11(): void
    {
        $this->assertFalse($this->validate('12345678901'));
    }

    public function test_rejects_length_12(): void
    {
        $this->assertFalse($this->validate('123456789012'));
    }

    public function test_rejects_non_numeric(): void
    {
        $this->assertFalse($this->validate('abc'));
    }

    public function test_rejects_empty_string(): void
    {
        $this->assertFalse($this->validate(''));
    }

    public function test_rejects_numeric_string_with_spaces(): void
    {
        $this->assertFalse($this->validate('1713175071 '));
    }

    private function validate(string $value): bool
    {
        $passed = true;
        $this->rule->validate('cedula', $value, function () use (&$passed) {
            $passed = false;
        });

        return $passed;
    }
}
