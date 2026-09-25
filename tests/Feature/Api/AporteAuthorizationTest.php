<?php

namespace Tests\Feature\Api;

use App\Models\Aporte;
use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\Staff;
use App\Models\Usuario;
use App\Services\ApiKeyService;
use App\Services\ColaboracionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AporteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_no_colaborador_no_puede_enviar_aportes(): void
    {
        $key = $this->apiKey($this->cliente('normal'), ['colaboradores:aportes']);

        $this->withHeader('Authorization', 'Bearer '.$key)->postJson('/api/v1/colaboradores/datos', ['identificador' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567'])
            ->assertForbidden()->assertJsonPath('error.tipo', 'COLABORADOR_NO_ACTIVO');
    }

    public function test_colaborador_solo_consulta_su_aporte(): void
    {
        $propietario = $this->cliente('propietario', true);
        $otro = $this->cliente('otro', true);
        $aporte = Aporte::create(['colaborador_id' => $propietario->colaborador->id, 'identificador_relacionado' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567', 'estado' => 'pendiente', 'fecha' => now()]);

        $this->withHeader('Authorization', 'Bearer '.$this->apiKey($otro, ['colaboradores:aportes']))->getJson("/api/v1/colaboradores/aportes/{$aporte->id}")
            ->assertForbidden()->assertJsonPath('error.tipo', 'APORTE_NO_PROPIETARIO');
        $this->withHeader('Authorization', 'Bearer '.$this->apiKey($propietario, ['colaboradores:aportes']))->getJson("/api/v1/colaboradores/aportes/{$aporte->id}")->assertOk();
    }

    public function test_una_ip_fuera_de_la_lista_no_puede_enviar_aportes(): void
    {
        $usuario = $this->cliente('colaborador-ip', true);
        $this->mockRegistrarAporte($usuario->colaborador);
        $fuera = $this->apiKey($usuario, ['colaboradores:aportes'], ['203.0.113.5']);

        $this->withHeader('Authorization', 'Bearer '.$fuera)->postJson('/api/v1/colaboradores/datos', ['identificador' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567'])
            ->assertForbidden()->assertJsonPath('error.tipo', 'IP_NO_PERMITIDA');

        // La IP real del cliente de pruebas es 127.0.0.1: con esa en la lista el mismo request
        // atraviesa ValidateApiKey y solo lo frena el guard de colaborador. Si el 403 fuera de
        // IP_NO_PERMITIDA, la lista no sería lo que bloquea y este control lo delataría.
        $dentro = $this->apiKey($usuario, ['colaboradores:aportes'], ['127.0.0.1']);

        $this->withHeader('Authorization', 'Bearer '.$dentro)->postJson('/api/v1/colaboradores/datos', ['identificador' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567'])
            ->assertCreated();
    }

    public function test_un_colaborador_suspendido_no_puede_enviar_aportes(): void
    {
        $usuario = $this->colaboradorSuspendido('colaborador-suspendido');

        $this->withHeader('Authorization', 'Bearer '.$this->apiKey($usuario, ['colaboradores:aportes']))->postJson('/api/v1/colaboradores/datos', ['identificador' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567'])
            ->assertForbidden()->assertJsonPath('error.tipo', 'COLABORADOR_NO_ACTIVO');
    }

    public function test_un_colaborador_suspendido_no_puede_consultar_sus_aportes(): void
    {
        $usuario = $this->colaboradorSuspendido('colaborador-suspendido-consulta');
        $aporte = $this->aportePendiente($usuario->colaborador);

        $this->withHeader('Authorization', 'Bearer '.$this->apiKey($usuario, ['colaboradores:aportes']))->getJson("/api/v1/colaboradores/aportes/{$aporte->id}")
            ->assertForbidden()->assertJsonPath('error.tipo', 'COLABORADOR_NO_ACTIVO');
    }

    public function test_enviar_aportes_exige_authorization(): void
    {
        $this->cliente('colaborador-sin-key', true);

        $this->postJson('/api/v1/colaboradores/datos', ['identificador' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567'])
            ->assertUnauthorized()->assertJsonPath('error.tipo', 'API_KEY_REQUERIDA');
    }

    public function test_enviar_aportes_exige_el_scope_de_aportes(): void
    {
        $key = $this->apiKey($this->cliente('colaborador-sin-scope', true), ['consulta:cedula']);

        $this->withHeader('Authorization', 'Bearer '.$key)->postJson('/api/v1/colaboradores/datos', ['identificador' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567'])
            ->assertForbidden()->assertJsonPath('error.tipo', 'PERMISO_INSUFICIENTE');
    }

    public function test_enviar_aportes_acepta_el_wildcard_de_colaboradores(): void
    {
        $usuario = $this->cliente('colaborador-wildcard', true);
        $aporte = $this->mockRegistrarAporte($usuario->colaborador);
        $key = $this->apiKey($usuario, ['colaboradores:*']);

        $this->withHeader('Authorization', 'Bearer '.$key)->postJson('/api/v1/colaboradores/datos', ['identificador' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567'])
            ->assertCreated()->assertJsonPath('datos.aporte.id', $aporte->id);
    }

    public function test_consultar_aporte_rechaza_una_key_revocada(): void
    {
        $usuario = $this->cliente('colaborador-revocado', true);
        $aporte = Aporte::create(['colaborador_id' => $usuario->colaborador->id, 'identificador_relacionado' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567', 'estado' => 'pendiente', 'fecha' => now()]);
        $key = $this->apiKey($usuario, ['colaboradores:aportes']);
        $usuario->cliente->update(['api_key_revocada' => true, 'api_key_revocada_en' => now()]);

        $this->withHeader('Authorization', 'Bearer '.$key)->getJson("/api/v1/colaboradores/aportes/{$aporte->id}")
            ->assertUnauthorized()->assertJsonPath('error.tipo', 'API_KEY_REVOCADA');
    }

    public function test_cliente_no_accede_a_bandeja_staff(): void
    {
        $this->actingAs($this->cliente('cliente'), 'sanctum')->getJson('/api/v1/admin/aportes/pendientes')->assertForbidden();
    }

    public function test_support_puede_aprobar_y_rechazar_aportes(): void
    {
        $support = Usuario::create(['uid' => 'support-uid', 'email' => 'support@test.com', 'nombre' => 'Support', 'roles' => ['staff']]);
        Staff::create(['usuario_id' => $support->id, 'rol_staff' => 'support']);
        $colaborador = $this->cliente('colaborador-aporte', true)->colaborador;
        $aprobar = $this->aportePendiente($colaborador);
        $rechazar = $this->aportePendiente($colaborador);

        $service = $this->mock(ColaboracionService::class);
        $service->shouldReceive('decidir')->twice()->andReturnUsing(
            function (Aporte $aporte, Usuario $actor, bool $decision): Aporte {
                $this->assertSame('support@test.com', $actor->email);
                $aporte->estado = $decision ? 'aprobado' : 'rechazado';

                return $aporte;
            },
        );

        $this->actingAs($support, 'sanctum')
            ->postJson("/api/v1/admin/aportes/{$aprobar->id}/aprobar")
            ->assertOk();

        $this->postJson("/api/v1/admin/aportes/{$rechazar->id}/rechazar")
            ->assertOk();
    }

    private function mockRegistrarAporte(Colaborador $colaborador): Aporte
    {
        $aporte = $this->aportePendiente($colaborador);

        $service = $this->mock(ColaboracionService::class);
        $service->shouldReceive('registrarAporte')->once()->andReturn($aporte);

        return $aporte;
    }

    private function aportePendiente(Colaborador $colaborador): Aporte
    {
        return Aporte::create([
            'colaborador_id' => $colaborador->id,
            'identificador_relacionado' => '1713175071',
            'tipo_dato' => 'telefono',
            'valor' => '0991234567',
            'estado' => 'pendiente',
            'fecha' => now(),
        ]);
    }

    private function apiKey(Usuario $usuario, array $scopes, array $ips = []): string
    {
        return app(ApiKeyService::class)->issue($usuario->cliente, ['scopes' => $scopes, 'ips' => $ips], $usuario->id);
    }

    private function colaboradorSuspendido(string $suffix): Usuario
    {
        $usuario = $this->cliente($suffix, true);
        $usuario->colaborador->update(['estado_colaborador' => 'suspendido', 'fecha_suspension' => now()]);

        return $usuario->fresh();
    }

    private function cliente(string $suffix, bool $colaborador = false): Usuario
    {
        $usuario = Usuario::create(['uid' => "cliente-{$suffix}", 'email' => "{$suffix}@test.com", 'nombre' => 'Cliente', 'roles' => $colaborador ? ['cliente', 'colaborador'] : ['cliente']]);
        Cliente::create(['usuario_id' => $usuario->id, 'saldo_creditos' => 0]);
        if ($colaborador) {
            Colaborador::create(['usuario_id' => $usuario->id, 'terminos_version' => 'test', 'terminos_aceptados_en' => now()]);
        }

        return $usuario->fresh();
    }
}
