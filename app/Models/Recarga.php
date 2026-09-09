<?php

namespace App\Models;

use App\Enums\EstadoRecarga;
use Illuminate\Database\Eloquent\Model;

class Recarga extends Model
{
    protected $fillable = [
        'cliente_id', 'metodo', 'monto_usd', 'creditos_obtenidos',
        'estado', 'referencia_externa', 'provider_payment_id', 'provider_transaction_id',
        'provider_authorization_code', 'provider_status', 'provider_amount',
        'provider_currency', 'provider_verified_at', 'comprobante_url', 'fecha',
        'motivo_rechazo', 'rechazada_at', 'rechazada_por',
    ];

    protected $casts = [
        'monto_usd' => 'decimal:2',
        'provider_amount' => 'decimal:2',
        'creditos_obtenidos' => 'integer',
        'fecha' => 'datetime',
        'provider_verified_at' => 'datetime',
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
