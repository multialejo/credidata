<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $fillable = [
        'cliente_id', 'nombre', 'prefijo', 'hash', 'formato', 'creada_en',
        'revocada', 'revocada_en', 'ultimo_uso_en', 'ips_permitidas', 'alcance',
        'rotacion_sugerida_en', 'notificacion_rotacion_enviada_en',
    ];

    protected $casts = [
        'creada_en' => 'datetime',
        'revocada' => 'boolean',
        'revocada_en' => 'datetime',
        'ultimo_uso_en' => 'datetime',
        'ips_permitidas' => 'array',
        'alcance' => 'array',
        'rotacion_sugerida_en' => 'datetime',
        'notificacion_rotacion_enviada_en' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function consultas()
    {
        return $this->hasMany(Consulta::class, 'api_key_id');
    }

    public function aportes()
    {
        return $this->hasMany(Aporte::class, 'api_key_id');
    }
}
