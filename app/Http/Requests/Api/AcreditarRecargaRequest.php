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
            'recarga_id' => ['required', 'integer', 'exists:recargas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'recarga_id.required' => 'La solicitud de recarga es obligatoria.',
            'recarga_id.integer' => 'La solicitud de recarga no es válida.',
            'recarga_id.exists' => 'La solicitud de recarga no existe.',
        ];
    }
}
