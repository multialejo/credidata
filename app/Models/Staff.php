<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class Staff extends Model
{
    protected $fillable = [
        'usuario_id', 'tipo_acceso', 'rol_staff', 'fecha_asignacion',
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Staff $staff): void {
            $usuario = Usuario::query()->findOrFail($staff->usuario_id);
            $roles = $usuario->rolesNormalizados();

            if (! in_array('staff', $roles, true) || in_array('cliente', $roles, true)) {
                throw new LogicException('El perfil staff requiere un usuario con rol staff y sin rol cliente.');
            }

            if ($usuario->cliente()->exists()) {
                throw new LogicException('No se puede asignar un perfil staff a un usuario cliente.');
            }

            $staff->tipo_acceso = 'staff';
        });
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }
}
