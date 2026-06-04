<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
    protected $fillable = [
        'cliente_id', 'tipo', 'identificador', 'sujeto_id', 'origen',
        'creditos_gastados', 'resultado_json', 'fuentes_utilizadas',
        'exitosa', 'ip_origen', 'fecha',
    ];

    protected $casts = [
        'creditos_gastados' => 'integer',
        'resultado_json' => 'array',
        'fuentes_utilizadas' => 'array',
        'exitosa' => 'boolean',
        'fecha' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'uid');
    }
}
