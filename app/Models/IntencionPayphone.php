<?php

namespace App\Models;

use App\Enums\EstadoIntencionPayphone;
use Illuminate\Database\Eloquent\Model;

class IntencionPayphone extends Model
{
    protected $table = 'intenciones_payphone';

    protected $fillable = [
        'cliente_id', 'ctid', 'payment_id', 'monto_usd', 'creditos_estimados',
        'moneda', 'estado', 'expira_en', 'fecha',
    ];

    protected $casts = [
        'monto_usd' => 'decimal:2',
        'creditos_estimados' => 'integer',
        'estado' => EstadoIntencionPayphone::class,
        'expira_en' => 'datetime',
        'fecha' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'id');
    }
}
