<?php

namespace App\Http\Requests\Api;

use App\Rules\AporteValor;
use App\Rules\EcuadorianIdentificador;
use Illuminate\Foundation\Http\FormRequest;

class RegistrarAporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipo = $this->input('tipo_dato');

        return [
            'identificador' => ['required', 'string', new EcuadorianIdentificador],
            'tipo_dato' => ['required', 'in:telefono,email,direccion'],
            'valor' => ['required', 'string', 'max:500', new AporteValor(is_string($tipo) ? $tipo : '')],
        ];
    }
}
