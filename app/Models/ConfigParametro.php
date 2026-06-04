<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigParametro extends Model
{
    protected $primaryKey = ['modulo', 'clave'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'modulo', 'clave', 'valor', 'actualizado_por', 'actualizado_en',
    ];

    protected $casts = [
        'actualizado_en' => 'datetime',
    ];

    public $timestamps = true;

    public function setKeysForSelectQuery($query)
    {
        return $query->where('modulo', $this->modulo)->where('clave', $this->clave);
    }
}
