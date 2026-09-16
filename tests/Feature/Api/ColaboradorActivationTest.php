<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColaboradorActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_activa_el_perfil_y_reintentar_es_idempotente(): void
    {
        $usuario = $this->clienteUsuario();

        $this->actingAs($usuario, 'sanctum')->postJson('/api/v1/colaboradores/registro', ['acepta_terminos' => true])
            ->assertCreated()->assertJsonPath('datos.colaborador.estado', 'activo');
        $this->actingAs($usuario, 'sanctum')->postJson('/api/v1/colaboradores/registro', ['acepta_terminos' => true])
            ->assertOk();

        $this->assertSame(1, Colaborador::count());
        $this->assertDatabaseHas('logs_actividad', ['accion' => 'colaborador.activado', 'actor_id' => $usuario->id]);
        $this->assertContains('colaborador', $usuario->fresh()->rolesNormalizados());
    }

    public function test_terminos_son_obligatorios(): void
    {
        $this->actingAs($this->clienteUsuario(), 'sanctum')->postJson('/api/v1/colaboradores/registro', [])
            ->assertUnprocessable();
    }

    public function test_staff_no_puede_activarse(): void
    {
        $usuario = Usuario::create(['uid' => 'staff-colaborador', 'email' => 'staff-colaborador@test.com', 'nombre' => 'Staff', 'roles' => ['staff']]);
        Staff::create(['usuario_id' => $usuario->id, 'rol_staff' => 'admin', 'fecha_asignacion' => now()]);

        $this->actingAs($usuario, 'sanctum')->postJson('/api/v1/colaboradores/registro', ['acepta_terminos' => true])
            ->assertUnprocessable();
        $this->assertDatabaseCount('colaboradores', 0);
    }

    private function clienteUsuario(): Usuario
    {
        $usuario = Usuario::create(['uid' => 'cliente-colaborador', 'email' => 'cliente-colaborador@test.com', 'nombre' => 'Cliente', 'roles' => ['cliente']]);
        Cliente::create(['usuario_id' => $usuario->id, 'saldo_creditos' => 0]);

        return $usuario;
    }
}
