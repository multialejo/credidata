<?php

namespace App\Console\Commands;

use App\Models\LogActividad;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class StaffCreate extends Command
{
    protected $signature = 'staff:create
        {email : Email del usuario staff}
        {--password= : Password (si se omite, se genera uno aleatorio de 16 chars y se imprime)}
        {--nombre=Staff Hurl : Nombre visible del usuario}
        {--rol=admin : Rol staff (admin|validator|support)}';

    protected $description = 'Crea un Usuario con fila Staff asociada para tests / setup manual (uso interno)';

    private const ROLES_VALIDOS = ['admin', 'validator', 'support'];

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $nombre = (string) $this->option('nombre');
        $rol = (string) $this->option('rol');

        if (! in_array($rol, self::ROLES_VALIDOS, true)) {
            $this->error("rol '{$rol}' inválido. Valores permitidos: ".implode(' | ', self::ROLES_VALIDOS));

            return 1;
        }

        if (Usuario::where('email', $email)->exists()) {
            $this->error("Ya existe un usuario con email {$email}.");

            return 1;
        }

        $password = (string) ($this->option('password') ?: Str::random(16));
        $passwordFueGenerado = empty($this->option('password'));

        $usuario = Usuario::create([
            'email' => $email,
            'nombre' => $nombre,
            'password' => $password,
            'roles' => ['staff'],
        ]);

        $staff = Staff::create([
            'usuario_id' => $usuario->id,
            'rol_staff' => $rol,
            'fecha_asignacion' => now(),
        ]);

        LogActividad::create([
            'accion' => 'STAFF_CREADO',
            'actor_id' => $usuario->id,
            'actor_sistema' => true,
            'detalle' => [
                'rol_staff' => $rol,
                'origen' => 'cli',
                'password_generado' => $passwordFueGenerado,
            ],
            'ip_origen' => 'sistema',
        ]);

        $this->line("[OK] staff creado: usuario_id={$usuario->id} staff_id={$staff->id} rol={$rol}");
        if ($passwordFueGenerado) {
            $this->line("[OK] password generado: {$password}");
        }

        return 0;
    }
}
