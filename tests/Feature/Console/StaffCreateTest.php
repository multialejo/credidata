<?php

namespace Tests\Feature\Console;

use App\Models\LogActividad;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_usuario_y_fila_staff_con_password_provisto(): void
    {
        $this->artisan('staff:create', [
            'email' => 'staff-hurl@test.com',
            '--password' => 'hurl-pass',
            '--rol' => 'admin',
        ])->assertExitCode(0);

        $usuario = Usuario::where('email', 'staff-hurl@test.com')->first();
        $this->assertNotNull($usuario);
        $this->assertSame('staff-hurl@test.com', $usuario->email);
        $this->assertSame(['staff'], $usuario->roles);
        $this->assertTrue(Hash::check('hurl-pass', $usuario->password));

        $staff = Staff::where('usuario_id', $usuario->id)->first();
        $this->assertNotNull($staff);
        $this->assertSame('admin', $staff->rol_staff);
        $this->assertNotNull($staff->fecha_asignacion);
    }

    public function test_rol_default_es_admin(): void
    {
        $this->artisan('staff:create', [
            'email' => 'staff-default@test.com',
            '--password' => 'x',
        ])->assertExitCode(0);

        $usuario = Usuario::where('email', 'staff-default@test.com')->firstOrFail();
        $this->assertSame('admin', $usuario->staff->rol_staff);
    }

    public function test_genera_password_cuando_no_se_provee_y_lo_imprime(): void
    {
        $this->artisan('staff:create', [
            'email' => 'staff-nopass@test.com',
        ])
            ->expectsOutputToContain('[OK] staff creado:')
            ->expectsOutputToContain('[OK] password generado:')
            ->assertExitCode(0);

        $usuario = Usuario::where('email', 'staff-nopass@test.com')->firstOrFail();

        $log = LogActividad::where('accion', 'STAFF_CREADO')
            ->where('actor_id', $usuario->id)
            ->firstOrFail();
        $this->assertTrue($log->detalle['password_generado']);
    }

    public function test_marca_password_generado_false_cuando_se_provee(): void
    {
        $this->artisan('staff:create', [
            'email' => 'staff-withpass@test.com',
            '--password' => 'explicit',
        ])->assertExitCode(0);

        $usuario = Usuario::where('email', 'staff-withpass@test.com')->firstOrFail();

        $log = LogActividad::where('accion', 'STAFF_CREADO')
            ->where('actor_id', $usuario->id)
            ->firstOrFail();
        $this->assertFalse($log->detalle['password_generado']);
    }

    public function test_audita_creacion_en_logs_actividad(): void
    {
        $this->artisan('staff:create', [
            'email' => 'staff-audit@test.com',
            '--password' => 'x',
            '--rol' => 'validator',
        ])->assertExitCode(0);

        $usuario = Usuario::where('email', 'staff-audit@test.com')->firstOrFail();

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'STAFF_CREADO',
            'actor_id' => $usuario->id,
            'actor_sistema' => true,
            'ip_origen' => 'sistema',
        ]);

        $log = LogActividad::where('accion', 'STAFF_CREADO')->firstOrFail();
        $this->assertSame('validator', $log->detalle['rol_staff']);
        $this->assertSame('cli', $log->detalle['origen']);
    }

    public function test_falla_con_rol_invalido(): void
    {
        $this->artisan('staff:create', [
            'email' => 'staff-bad@test.com',
            '--password' => 'x',
            '--rol' => 'superuser',
        ])
            ->expectsOutputToContain("rol 'superuser' inválido")
            ->assertExitCode(1);

        $this->assertDatabaseMissing('usuarios', ['email' => 'staff-bad@test.com']);
        $this->assertDatabaseCount('staff', 0);
    }

    public function test_falla_con_email_duplicado(): void
    {
        Usuario::create([
            'email' => 'existing@test.com',
            'nombre' => 'Existing',
            'password' => 'whatever',
            'roles' => ['staff'],
        ]);

        $this->artisan('staff:create', [
            'email' => 'existing@test.com',
            '--password' => 'x',
        ])
            ->expectsOutputToContain('Ya existe un usuario con email')
            ->assertExitCode(1);

        $this->assertSame(1, Usuario::where('email', 'existing@test.com')->count());
        $this->assertDatabaseCount('staff', 0);
    }

    public function test_acepta_los_tres_roles_validos(): void
    {
        foreach (['admin', 'validator', 'support'] as $rol) {
            $this->artisan('staff:create', [
                'email' => "staff-{$rol}@test.com",
                '--password' => 'x',
                '--rol' => $rol,
            ])->assertExitCode(0);

            $usuario = Usuario::where('email', "staff-{$rol}@test.com")->firstOrFail();
            $this->assertSame($rol, $usuario->staff->rol_staff);
        }
    }
}
