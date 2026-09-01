<?php

namespace App\Http\Requests\Api;

use App\Services\ApiKeyService;
use Illuminate\Foundation\Http\FormRequest;

class RotateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alias' => ['sometimes', 'nullable', 'string', 'max:100'],
            'ips' => ['sometimes', 'nullable', 'array'],
            'ips.*' => ['string', 'ip'],
            'scopes' => ['sometimes', 'nullable', 'array'],
            'scopes.*' => ['string', 'in:'.implode(',', ApiKeyService::SCOPES)],
        ];
    }
}
