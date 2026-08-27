<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class Cliente extends Model
{
    protected $fillable = [
        'usuario_id', 'tipo_acceso', 'saldo_creditos', 'metodo_pago_preferido',
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

    protected static function booted(): void
    {
        static::saving(function (Cliente $cliente): void {
            $usuario = Usuario::query()->findOrFail($cliente->usuario_id);
            $roles = $usuario->rolesNormalizados();

            if (! in_array('cliente', $roles, true) || in_array('staff', $roles, true)) {
                throw new LogicException('El perfil cliente requiere un usuario con rol cliente y sin rol staff.');
            }

            if ($usuario->staff()->exists()) {
                throw new LogicException('No se puede asignar un perfil cliente a un usuario staff.');
            }

            $cliente->tipo_acceso = 'cliente';
        });
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }

    public function consultas()
    {
        return $this->hasMany(Consulta::class, 'cliente_id', 'id');
    }

    public function recargas()
    {
        return $this->hasMany(Recarga::class, 'cliente_id', 'id');
    }

    public function rateOverride()
    {
        return $this->hasOne(ConfigRateOverride::class, 'cliente_id', 'id');
    }
}
