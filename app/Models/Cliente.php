<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid', 'saldo_creditos', 'metodo_pago_preferido',
        'api_key_prefijo', 'api_key_hash', 'api_key_alias',
        'api_key_creada', 'api_key_revocada', 'api_key_revocada_en',
        'api_key_ultimo_uso', 'api_key_ips_permitidas', 'api_key_alcance',
    ];

    protected $casts = [
        'api_key_ips_permitidas' => 'array',
        'api_key_alcance' => 'array',
        'api_key_revocada' => 'boolean',
        'api_key_creada' => 'datetime',
        'api_key_ultimo_uso' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'uid', 'uid');
    }

    public function consultas()
    {
        return $this->hasMany(Consulta::class, 'cliente_id', 'uid');
    }

    public function recargas()
    {
        return $this->hasMany(Recarga::class, 'cliente_id', 'uid');
    }

    public function rateOverride()
    {
        return $this->hasOne(ConfigRateOverride::class, 'cliente_uid', 'uid');
    }
}
