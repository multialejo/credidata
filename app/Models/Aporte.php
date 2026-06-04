<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aporte extends Model
{
    protected $fillable = [
        'colaborador_id', 'identificador_relacionado', 'tipo_dato', 'valor',
        'evidencia_url', 'estado', 'revisado_por', 'comentario_rechazo', 'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id', 'uid');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(Usuario::class, 'revisado_por', 'uid');
    }
}
