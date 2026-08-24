<?php

namespace App\Console\Commands;

use App\Models\LogActividad;
use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AuthToken extends Command
{
    protected $signature = 'auth:token {email} {--password= : Password del usuario (si se omite, se pide por stdin)}';

    protected $description = 'Emite un Sanctum token para un usuario existente (uso interno / Hurl)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = (string) ($this->option('password') ?: $this->secret('Password'));

        if ($password === '') {
            $this->error('Password requerido (--password=... o input interactivo).');

            return 1;
        }

        $usuario = Usuario::where('email', $email)->first();
        if (! $usuario) {
            $this->error("Usuario con email {$email} no encontrado.");

            return 1;
        }

        if (empty($usuario->password)) {
            $this->error("El usuario {$email} no tiene password (cuenta OAuth-only).");

            return 1;
        }

        if (! Hash::check($password, $usuario->password)) {
            $this->error('Password incorrecto.');

            return 1;
        }

        $token = $usuario->createToken('cli')->plainTextToken;

        LogActividad::create([
            'accion' => 'AUTH_TOKEN_EMITIDO',
            'actor_id' => $usuario->id,
            'detalle' => [
                'origen' => 'cli',
                'token_name' => 'cli',
            ],
            'ip_origen' => 'sistema',
        ]);

        $this->line($token);

        return 0;
    }
}
