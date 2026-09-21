<?php

namespace App\Http\Requests\Api;

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
        return ['identificador' => ['required', 'string', new EcuadorianIdentificador], 'tipo_dato' => ['required', 'in:telefono,email,direccion'], 'valor' => ['required', 'string', 'max:500']];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $tipo = $this->input('tipo_dato');
            $valor = (string) $this->input('valor');
            if ($tipo === 'email' && ! filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                $validator->errors()->add('valor', 'El email no es válido.');
            }
            if ($tipo === 'telefono' && ! preg_match('/^\+?[0-9 ()-]{7,30}$/', $valor)) {
                $validator->errors()->add('valor', 'El teléfono no es válido.');
            }
            if ($tipo === 'direccion' && mb_strlen(trim($valor)) < 5) {
                $validator->errors()->add('valor', 'La dirección debe tener al menos 5 caracteres.');
            }
        }];
    }
}
