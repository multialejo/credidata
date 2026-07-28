<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;

    protected $fillable = [
        'uid', 'email', 'firebase_uid', 'password', 'nombre', 'estado', 'roles',
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
        });
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
