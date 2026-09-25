<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\Staff;
use App\Models\Usuario;
use App\Services\ApiKeyService;
use App\Services\ColaboracionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ColaboradorActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_activa_el_perfil_y_reintentar_es_idempotente(): void
    {
        $usuario = $this->clienteUsuario();
        $key = $this->apiKey($usuario, ['colaboradores:registro']);

        $this->withHeader('Authorization', 'Bearer '.$key)->postJson('/api/v1/colaboradores/registro', ['acepta_terminos' => true])
            ->assertCreated()->assertJsonPath('datos.colaborador.estado', 'activo');
        $this->withHeader('Authorization', 'Bearer '.$key)->postJson('/api/v1/colaboradores/registro', ['acepta_terminos' => true])
            ->assertOk();

        $this->assertSame(1, Colaborador::count());
        $this->assertDatabaseHas('logs_actividad', ['accion' => 'colaborador.activado', 'actor_id' => $usuario->id]);
        $this->assertContains('colaborador', $usuario->fresh()->rolesNormalizados());
    }

    public function test_terminos_son_obligatorios(): void
    {
        $key = $this->apiKey($this->clienteUsuario(), ['colaboradores:registro']);

        $this->withHeader('Authorization', 'Bearer '.$key)->postJson('/api/v1/colaboradores/registro', [])
            ->assertUnprocessable();
    }

    public function test_registro_exige_authorization(): void
    {
        // El cliente existe y su perfil es válido, así que el 401 viene de que no se envió
        // API Key y no de una ausencia de usuario. Un usuario staff nunca llega hasta aquí:
        // sin Cliente no hay Key que emitir, y Cliente::booted() lo impide
        // (ver ExclusiveAccessRoleTest::test_staff_no_puede_recibir_perfil_cliente).
        $usuario = $this->clienteUsuario();
        $this->assertNotNull($usuario->cliente);

        $this->postJson('/api/v1/colaboradores/registro', ['acepta_terminos' => true])
            ->assertUnauthorized()->assertJsonPath('error.tipo', 'API_KEY_REQUERIDA');
        $this->assertDatabaseCount('colaboradores', 0);
    }

    public function test_registro_exige_el_scope_de_activacion(): void
    {
        $key = $this->apiKey($this->clienteUsuario(), ['colaboradores:aportes']);

        $this->withHeader('Authorization', 'Bearer '.$key)->postJson('/api/v1/colaboradores/registro', ['acepta_terminos' => true])
            ->assertForbidden()->assertJsonPath('error.tipo', 'PERMISO_INSUFICIENTE');
        $this->assertDatabaseCount('colaboradores', 0);
    }

    public function test_registro_acepta_el_wildcard_de_colaboradores(): void
    {
        $key = $this->apiKey($this->clienteUsuario(), ['colaboradores:*']);

        $this->withHeader('Authorization', 'Bearer '.$key)->postJson('/api/v1/colaboradores/registro', ['acepta_terminos' => true])
            ->assertCreated()->assertJsonPath('datos.colaborador.estado', 'activo');
    }

    public function test_registro_rechaza_una_key_revocada(): void
    {
        $usuario = $this->clienteUsuario();
        $key = $this->apiKey($usuario, ['colaboradores:registro']);
        $usuario->cliente->update(['api_key_revocada' => true, 'api_key_revocada_en' => now()]);

        $this->withHeader('Authorization', 'Bearer '.$key)->postJson('/api/v1/colaboradores/registro', ['acepta_terminos' => true])
            ->assertUnauthorized()->assertJsonPath('error.tipo', 'API_KEY_REVOCADA');
        $this->assertDatabaseCount('colaboradores', 0);
    }

    public function test_el_servicio_rechaza_a_un_usuario_sin_perfil_cliente(): void
    {
        $usuario = $this->staffUsuario();

        try {
            app(ColaboracionService::class)->activar($usuario, '127.0.0.1');
            $this->fail('El servicio no debio activar a un usuario staff.');
        } catch (ValidationException $exception) {
            $this->assertSame('Solo usuarios con perfil cliente pueden ser colaboradores.', $exception->errors()['terminos'][0]);
        }
        $this->assertDatabaseCount('colaboradores', 0);
    }

    private function apiKey(Usuario $usuario, array $scopes): string
    {
        return app(ApiKeyService::class)->issue($usuario->cliente, ['scopes' => $scopes, 'ips' => []], $usuario->id);
    }

    private function clienteUsuario(): Usuario
    {
        $usuario = Usuario::create(['uid' => 'cliente-colaborador', 'email' => 'cliente-colaborador@test.com', 'nombre' => 'Cliente', 'roles' => ['cliente']]);
        Cliente::create(['usuario_id' => $usuario->id, 'saldo_creditos' => 0]);

        return $usuario;
    }

    private function staffUsuario(): Usuario
    {
        $usuario = Usuario::create(['uid' => 'staff-colaborador', 'email' => 'staff-colaborador@test.com', 'nombre' => 'Staff', 'roles' => ['staff']]);
        Staff::create(['usuario_id' => $usuario->id, 'rol_staff' => 'admin', 'fecha_asignacion' => now()]);

        return $usuario;
    }
}
