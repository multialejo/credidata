<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aporte extends Model
{
    protected $fillable = [
        'colaborador_id', 'identificador_relacionado', 'tipo_dato', 'valor',
        'evidencia_url', 'estado', 'revisado_por', 'comentario_rechazo', 'comentario_revision',
        'revisado_en', 'recompensa_creditos', 'recompensado_en', 'aplicacion_pendiente', 'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'revisado_en' => 'datetime',
        'recompensado_en' => 'datetime',
        'aplicacion_pendiente' => 'boolean',
    ];

    public function colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id', 'id');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(Usuario::class, 'revisado_por', 'id');
    }

    public function getComentarioDecisionAttribute(): ?string
    {
        return $this->comentario_revision ?? $this->comentario_rechazo;
    }
}
