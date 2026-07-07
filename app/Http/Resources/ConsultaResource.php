<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsultaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'cedula'            => $this['cedula'] ?? null,
            'nombres'           => $this['nombres'] ?? null,
            'profesion'         => $this['profesion'] ?? null,
            'fechaNacimiento'   => $this['fechaNacimiento'] ?? null,
            'lugarNacimiento'   => $this['lugarNacimiento'] ?? null,
            'estadoCivilCodigo' => $this['estadoCivilCodigo'] ?? null,
            'conyuge'           => $this['conyuge'] ?? null,
            'ubicacion'         => $this['ubicacion'] ?? [
                'provincia' => null,
                'canton'    => null,
                'parroquia' => null,
            ],
            'ruc'               => $this['ruc'] ?? null,
            'contacto'          => $this['contacto'] ?? [
                'telefonos'   => [],
                'emails'      => [],
                'direcciones' => [],
            ],
        ];
    }
}
