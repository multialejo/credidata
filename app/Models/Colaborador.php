<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colaborador extends Model
{
    protected $fillable = [
        'usuario_id', 'creditos_ganados', 'creditos_acreditados',
        'total_aportes', 'aportes_aprobados', 'aportes_rechazados',
        'tasa_aprobacion', 'nivel_confianza', 'estado_colaborador', 'fecha_suspension',
    ];

    protected $casts = [
        'creditos_ganados' => 'integer',
        'creditos_acreditados' => 'integer',
        'total_aportes' => 'integer',
        'aportes_aprobados' => 'integer',
        'aportes_rechazados' => 'integer',
        'tasa_aprobacion' => 'decimal:2',
        'fecha_suspension' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }

    public function aportes()
    {
        return $this->hasMany(Aporte::class, 'colaborador_id', 'id');
    }
}
