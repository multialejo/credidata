<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogActividad extends Model
{
    protected $table = 'logs_actividad';

    protected $fillable = [
        'accion', 'actor_id', 'actor_sistema', 'detalle', 'ip_origen',
    ];

    protected $casts = [
        'actor_sistema' => 'boolean',
        'detalle' => 'array',
        'fecha' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(Usuario::class, 'actor_id', 'uid');
    }
}
