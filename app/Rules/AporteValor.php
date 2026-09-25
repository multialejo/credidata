<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AporteValor implements ValidationRule
{
    public function __construct(private readonly string $tipo) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('El valor debe ser texto.');

            return;
        }

        $valid = match ($this->tipo) {
            'telefono' => preg_match('/^0[0-9]{9}$/', $value) === 1,
            'email' => mb_strlen($value) <= 254 && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'direccion' => mb_strlen(trim($value)) >= 5,
            default => false,
        };

        if (! $valid) {
            $fail(match ($this->tipo) {
                'telefono' => 'El teléfono debe tener exactamente 10 dígitos y comenzar con 0.',
                'email' => 'El email no es válido.',
                'direccion' => 'La dirección debe tener al menos 5 caracteres.',
                default => 'El tipo de dato no es válido.',
            });
        }
    }
}
