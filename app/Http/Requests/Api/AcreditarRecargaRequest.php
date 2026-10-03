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
            'cliente_email' => ['required', 'email', 'max:255'],
            'monto_usd' => ['required', 'numeric', 'min:0.01'],
            'referencia_bancaria' => ['required', 'string', 'max:100'],
            'comprobante' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_email.required' => 'El email del cliente es obligatorio.',
            'cliente_email.email' => 'El email del cliente no es válido.',
            'monto_usd.required' => 'El monto en USD es obligatorio.',
            'monto_usd.numeric' => 'El monto en USD debe ser numérico.',
            'monto_usd.min' => 'El monto en USD debe ser mayor que cero.',
            'referencia_bancaria.required' => 'La referencia bancaria es obligatoria.',
            'comprobante.required' => 'El comprobante es obligatorio.',
            'comprobante.file' => 'El comprobante debe ser un archivo.',
            'comprobante.mimes' => 'El comprobante debe ser JPG, PNG o PDF.',
            'comprobante.max' => 'El comprobante no puede superar 10 MB.',
        ];
    }
}
