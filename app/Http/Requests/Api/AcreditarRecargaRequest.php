<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AcreditarRecargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'creditos' => ['required', 'integer', 'min:1'],
            'motivo' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'creditos.required' => 'El número de créditos es obligatorio.',
            'creditos.integer' => 'Los créditos deben ser un número entero.',
            'creditos.min' => 'Los créditos deben ser al menos 1.',
            'motivo.required' => 'El motivo de acreditación es obligatorio.',
            'motivo.string' => 'El motivo debe ser texto.',
            'motivo.max' => 'El motivo no puede exceder los 500 caracteres.',
        ];
    }
}