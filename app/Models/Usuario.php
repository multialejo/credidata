<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;

    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid', 'email', 'firebase_uid', 'password', 'nombre', 'estado', 'roles',
    ];

    protected $casts = [
        'roles' => 'array',
        'email_verified_at' => 'datetime',
        'fecha_registro' => 'datetime',
        'password' => 'hashed',
    ];

    public function getNameAttribute()
    {
        return $this->nombre;
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'uid', 'uid');
    }

    public function colaborador()
    {
        return $this->hasOne(Colaborador::class, 'uid', 'uid');
    }

    public function staff()
    {
        return $this->hasOne(Staff::class, 'uid', 'uid');
    }

    public function logs()
    {
        return $this->hasMany(LogActividad::class, 'actor_id', 'uid');
    }
}
