<?php

namespace Tests\Feature\Api;

use App\Models\Aporte;
use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AporteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_no_colaborador_no_puede_enviar_aportes(): void
    {
        $usuario = $this->cliente('normal');
        $this->actingAs($usuario, 'sanctum')->postJson('/api/v1/colaboradores/datos', ['identificador' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567'])->assertForbidden();
    }

    public function test_colaborador_solo_consulta_su_aporte(): void
    {
        $propietario = $this->cliente('propietario', true);
        $otro = $this->cliente('otro', true);
        $aporte = Aporte::create(['colaborador_id' => $propietario->colaborador->id, 'identificador_relacionado' => '1713175071', 'tipo_dato' => 'telefono', 'valor' => '0991234567', 'estado' => 'pendiente', 'fecha' => now()]);

        $this->actingAs($otro, 'sanctum')->getJson("/api/v1/colaboradores/aportes/{$aporte->id}")->assertForbidden();
        $this->actingAs($propietario, 'sanctum')->getJson("/api/v1/colaboradores/aportes/{$aporte->id}")->assertOk();
    }

    public function test_cliente_no_accede_a_bandeja_staff(): void
    {
        $this->actingAs($this->cliente('cliente'), 'sanctum')->getJson('/api/v1/admin/aportes/pendientes')->assertForbidden();
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
