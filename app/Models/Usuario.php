<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use LogicException;

class Usuario extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;

    protected $fillable = [
        'uid', 'email', 'firebase_uid', 'password', 'nombre', 'estado', 'roles', 'tipo_acceso',
    ];

    protected $casts = [
        'roles' => 'array',
        'email_verified_at' => 'datetime',
        'fecha_registro' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::creating(function (Usuario $usuario): void {
            if (empty($usuario->uid)) {
                $usuario->uid = (string) Str::uuid();
            }

            $usuario->sincronizarTipoAcceso();
            $usuario->assertRolesAccesoValidos();
        });

        static::updating(function (Usuario $usuario): void {
            if ($usuario->isDirty('roles')) {
                $usuario->sincronizarTipoAcceso();
            }

            $usuario->assertRolesAccesoValidos();
        });
    }

    private function sincronizarTipoAcceso(): void
    {
        $roles = $this->rolesNormalizados();
        $esCliente = in_array('cliente', $roles, true);
        $esStaff = in_array('staff', $roles, true);

        $this->tipo_acceso = $esCliente ? 'cliente' : ($esStaff ? 'staff' : null);
    }

    private function assertRolesAccesoValidos(): void
    {
        $roles = $this->rolesNormalizados();
        $esCliente = in_array('cliente', $roles, true);
        $esStaff = in_array('staff', $roles, true);

        if ($esCliente && $esStaff) {
            throw new LogicException('Un usuario no puede tener simultáneamente los roles cliente y staff.');
        }

        if (! $this->exists) {
            return;
        }

        if ($this->cliente()->exists() && (! $esCliente || $esStaff)) {
            throw new LogicException('Los roles del usuario son incompatibles con su perfil cliente.');
        }

        if ($this->staff()->exists() && (! $esStaff || $esCliente)) {
            throw new LogicException('Los roles del usuario son incompatibles con su perfil staff.');
        }
    }

    public function rolesNormalizados(): array
    {
        $roles = $this->roles ?? [];

        return is_string($roles) ? (json_decode($roles, true) ?? []) : $roles;
    }

    public function getNameAttribute()
    {
        return $this->nombre;
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'usuario_id', 'id');
    }

    public function colaborador()
    {
        return $this->hasOne(Colaborador::class, 'usuario_id', 'id');
    }

    public function staff()
    {
        return $this->hasOne(Staff::class, 'usuario_id', 'id');
    }

    public function logs()
    {
        return $this->hasMany(LogActividad::class, 'actor_id', 'id');
    }
}
