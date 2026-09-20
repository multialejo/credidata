<?php

namespace App\Models;

use App\Enums\EstadoIntencionPaypal;
use Illuminate\Database\Eloquent\Model;

class IntencionPaypal extends Model
{
    protected $table = 'intenciones_paypal';

    protected $fillable = [
        'cliente_id', 'order_id', 'monto_usd', 'creditos_estimados',
        'moneda', 'estado', 'expira_en', 'fecha',
    ];

    protected $casts = [
        'monto_usd' => 'decimal:2',
        'creditos_estimados' => 'integer',
        'estado' => EstadoIntencionPaypal::class,
        'expira_en' => 'datetime',
        'fecha' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'id');
    }
}