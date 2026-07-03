<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EcuadorianIdentificador implements ValidationRule
{
    private const CEDULA_COEFFICIENTS = [2, 1, 2, 1, 2, 1, 2, 1, 2];

    private const RUC_MOD11_COEFFICIENTS = [2, 3, 4, 5, 6, 7, 2, 3, 4, 5, 6, 7];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! ctype_digit($value)) {
            $fail('El identificador no es una cédula ni un RUC ecuatoriano válido.');

            return;
        }

        $len = strlen($value);

        if ($len === 10) {
            if (! $this->validarCedula($value)) {
                $fail('El identificador no es una cédula ni un RUC ecuatoriano válido.');
            }

            return;
        }

        if ($len === 13) {
            if (! $this->validarRUC($value)) {
                $fail('El identificador no es una cédula ni un RUC ecuatoriano válido.');
            }

            return;
        }

        $fail('El identificador no es una cédula ni un RUC ecuatoriano válido.');
    }

    private function validarCedula(string $value): bool
    {
        $digits = str_split($value);
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $product = (int) $digits[$i] * self::CEDULA_COEFFICIENTS[$i];
            if ($product >= 10) {
                $product -= 9;
            }
            $sum += $product;
        }

        $expectedDv = (10 - ($sum % 10)) % 10;

        return $expectedDv === (int) $digits[9];
    }

    private function validarRUC(string $value): bool
    {
        if (! $this->validarCedula(substr($value, 0, 10))) {
            return false;
        }

        if (substr($value, 10, 2) !== '00') {
            return false;
        }

        $digits = str_split(substr($value, 0, 12));
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits[$i] * self::RUC_MOD11_COEFFICIENTS[$i];
        }

        $dv = 11 - ($sum % 11);

        if ($dv === 11) {
            $dv = 0;
        }

        if ($dv === 10) {
            return false;
        }

        return $dv === (int) $value[12];
    }
}
