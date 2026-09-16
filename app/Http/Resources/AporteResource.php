<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AporteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'identificador' => $this->identificador_relacionado, 'tipo_dato' => $this->tipo_dato, 'valor' => $this->valor, 'estado' => $this->estado, 'comentario' => $this->comentario_decision, 'recompensa_creditos' => $this->recompensa_creditos, 'fecha' => $this->fecha?->toIso8601String(), 'revisado_en' => $this->revisado_en?->toIso8601String()];
    }
}
