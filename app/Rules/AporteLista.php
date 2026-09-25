<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AporteLista implements ValidationRule
{
    public function __construct(private readonly string $tipo) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('El valor debe ser texto.');

            return;
        }

        foreach (preg_split('/\r\n|\r|\n/', $value) as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            if (mb_strlen($line) > 500) {
                $fail('La línea '.($index + 1).' no puede superar los 500 caracteres.');

                return;
            }

            (new AporteValor($this->tipo))->validate(
                $attribute,
                $line,
                static fn (string $message) => $fail('Línea '.($index + 1).': '.$message),
            );
        }
    }
}
