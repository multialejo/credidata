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
        $type = (int) $value[2];
        $province = (int) substr($value, 0, 2);
        if ($province < 1 || $province > 24) {
            return false;
        }

        if ($type <= 5) {
            return $this->validarCedula(substr($value, 0, 10)) && substr($value, 10, 3) !== '000';
        }

        if ($type === 9) {
            if (substr($value, 10, 3) !== '001') {
                return false;
            }

            return $this->mod11(substr($value, 0, 9), [4, 3, 2, 7, 6, 5, 4, 3, 2], (int) $value[9]);
        }

        if ($type === 6) {
            if (substr($value, 10, 3) !== '001') {
                return false;
            }

            return $this->mod11(substr($value, 0, 9), [3, 2, 7, 6, 5, 4, 3, 2, 1], (int) $value[9]);
        }

        return false;
    }

    private function mod11(string $digits, array $coefficients, int $expected): bool
    {
        $sum = 0;
        foreach (str_split($digits) as $index => $digit) {
            $sum += (int) $digit * $coefficients[$index];
        }
        $remainder = 11 - ($sum % 11);
        $check = $remainder === 11 ? 0 : $remainder;

        return $check !== 10 && $check === $expected;
    }
}
