<?php

namespace App\Models;

use App\Enums\EstadoRecarga;
use Illuminate\Database\Eloquent\Model;

class Recarga extends Model
{
    protected $fillable = [
        'cliente_id', 'metodo', 'monto_usd', 'creditos_obtenidos',
        'estado', 'referencia_externa', 'comprobante_url', 'fecha',
        'motivo_rechazo', 'rechazada_at', 'rechazada_por',
    ];

    protected $casts = [
        'monto_usd' => 'decimal:2',
        'creditos_obtenidos' => 'integer',
        'fecha' => 'datetime',
        'estado' => EstadoRecarga::class,
        'rechazada_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'id');
    }

    public function rechazadaPor()
    {
        return $this->belongsTo(Usuario::class, 'rechazada_por', 'id');
    }
}
