<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigRateOverride extends Model
{
    protected $primaryKey = 'cliente_uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'cliente_uid', 'por_minuto', 'por_dia', 'actualizado_por', 'actualizado_en',
    ];

    protected $casts = [
        'por_minuto' => 'integer',
        'por_dia' => 'integer',
        'actualizado_en' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_uid', 'uid');
    }
}
