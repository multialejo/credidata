<?php

namespace Tests\Feature\Models;

use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ExclusiveAccessRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_no_puede_tener_roles_cliente_y_staff(): void
    {
        $this->expectException(LogicException::class);

        Usuario::create($this->datosUsuario(['cliente', 'staff']));
    }

    public function test_cliente_no_puede_recibir_perfil_staff(): void
    {
        $usuario = Usuario::create($this->datosUsuario(['cliente']));
        Cliente::create(['usuario_id' => $usuario->id]);

        $this->expectException(LogicException::class);

        Staff::create([
            'usuario_id' => $usuario->id,
            'rol_staff' => 'admin',
        ]);
    }

    public function test_staff_no_puede_recibir_perfil_cliente(): void
    {
        $usuario = Usuario::create($this->datosUsuario(['staff']));
        Staff::create([
            'usuario_id' => $usuario->id,
            'rol_staff' => 'admin',
        ]);

        $this->expectException(LogicException::class);

        Cliente::create(['usuario_id' => $usuario->id]);
    }

    public function test_roles_no_pueden_contradecir_un_perfil_cliente_existente(): void
    {
        $usuario = Usuario::create($this->datosUsuario(['cliente']));
        Cliente::create(['usuario_id' => $usuario->id]);

        $this->expectException(LogicException::class);

        $usuario->update(['roles' => ['staff']]);
    }

    public function test_roles_no_pueden_contradecir_un_perfil_staff_existente(): void
    {
        $usuario = Usuario::create($this->datosUsuario(['staff']));
        Staff::create([
            'usuario_id' => $usuario->id,
            'rol_staff' => 'support',
        ]);

        $this->expectException(LogicException::class);

        $usuario->update(['roles' => ['cliente']]);
    }

    public function test_colaborador_puede_combinarse_con_cliente_o_staff(): void
    {
        $cliente = Usuario::create($this->datosUsuario(['cliente', 'colaborador'], 'cliente-colaborador@test.com'));
        Cliente::create(['usuario_id' => $cliente->id]);
        Colaborador::create(['usuario_id' => $cliente->id]);

        $staff = Usuario::create($this->datosUsuario(['staff', 'colaborador'], 'staff-colaborador@test.com'));
        Staff::create(['usuario_id' => $staff->id, 'rol_staff' => 'validator']);
        Colaborador::create(['usuario_id' => $staff->id]);

        $this->assertNotNull($cliente->fresh()->cliente);
        $this->assertNotNull($cliente->fresh()->colaborador);
        $this->assertNotNull($staff->fresh()->staff);
        $this->assertNotNull($staff->fresh()->colaborador);
    }

    private function datosUsuario(array $roles, string $email = 'usuario@test.com'): array
    {
        return [
            'email' => $email,
            'nombre' => 'Usuario de prueba',
            'roles' => $roles,
        ];
    }
}
