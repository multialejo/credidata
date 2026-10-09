<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Consulta;
use App\Models\Usuario;
use App\Services\CatastroService;
use App\Services\DinardapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Simulates a race: the balance is spent by another request between the
 * controller's pre-validation and the service's locked revalidation.
 */
class ConsultaCobroConcurrenciaTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'cd_sk_c0c0c0c0_dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd';

    private const CEDULA = '1713175071';

    private const RUC = '0100001437001';

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = Usuario::create([
            'uid' => 'concurrencia-uid', 'email' => 'concurrencia@test.com', 'nombre' => 'Concurrencia',
            'roles' => json_encode(['cliente']),
        ]);
        $this->cliente = Cliente::create(['usuario_id' => $usuario->id, 'saldo_creditos' => 1]);
        $this->cliente->apiKeys()->create([
            'nombre' => 'Concurrencia',
            'hash' => Hash::make(substr(self::API_KEY, 15)),
            'prefijo' => substr(self::API_KEY, 6, 8),
            'alcance' => ['consulta:cedula', 'consulta:ruc'],
        ]);
        ConfigParametro::create(['modulo' => 'financiero', 'clave' => 'costoConsultaBase', 'valor' => json_encode(1)]);
    }

    public function test_cedula_saldo_igual_a_costo_dos_cobros_seguidos_el_segundo_es_402(): void
    {
        $this->mock(DinardapService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('obtenerCache')->andReturn(['nombres' => 'Cacheado']);
            $mock->shouldReceive('consultar')->never();
        });

        $this->cedula()->assertOk()->assertJsonPath('metadatos.creditos_restantes', 0);
        $this->cedula()->assertStatus(402)->assertJsonPath('error.tipo', 'SALDO_INSUFICIENTE');

        $this->assertSaldo(0);
        $this->assertSame(1, Consulta::where('creditos_gastados', '>', 0)->count());
    }

    public function test_cedula_cache_hit_en_carrera_responde_402_sin_cobro(): void
    {
        $this->mock(DinardapService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('obtenerCache')->once()->andReturnUsing(function () {
                $this->gastarSaldoEnOtraRequest();

                return ['nombres' => 'Cacheado'];
            });
        });

        $this->assertCarreraRespondida402($this->cedula());
    }

    public function test_cedula_dinardap_ok_en_carrera_responde_402_pero_guarda_cache(): void
    {
        $this->mock(DinardapService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('obtenerCache')->once()->andReturnNull();
            $mock->shouldReceive('consultar')->once()->andReturnUsing(function () {
                $this->gastarSaldoEnOtraRequest();

                return ['status' => 'success', 'data' => ['nombres' => 'Fresco']];
            });
            $mock->shouldReceive('guardarCache')->once()->with(self::CEDULA, ['nombres' => 'Fresco']);
        });

        $this->assertCarreraRespondida402($this->cedula());
    }

    public function test_cedula_no_encontrado_en_carrera_responde_402_sin_cobro(): void
    {
        $this->mock(DinardapService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('obtenerCache')->once()->andReturnNull();
            $mock->shouldReceive('consultar')->once()->andReturnUsing(function () {
                $this->gastarSaldoEnOtraRequest();

                return ['status' => 'not_found'];
            });
        });

        $this->assertCarreraRespondida402($this->cedula());
    }

    public function test_ruc_en_carrera_responde_402_con_envelope_y_no_500(): void
    {
        $this->mock(CatastroService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('buscarEstablecimientos')->once()->andReturnUsing(function () {
                $this->gastarSaldoEnOtraRequest();

                return [['numero' => '001', 'razonSocial' => 'A']];
            });
        });

        $this->assertCarreraRespondida402($this->ruc());
    }

    public function test_ruc_saldo_igual_a_costo_dos_cobros_seguidos_el_segundo_es_402(): void
    {
        $this->mock(CatastroService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('buscarEstablecimientos')->once()->andReturn([['numero' => '001']]);
        });

        $this->ruc()->assertOk()->assertJsonPath('metadatos.creditos_restantes', 0);
        $this->ruc()->assertStatus(402)->assertJsonPath('error.tipo', 'SALDO_INSUFICIENTE');

        $this->assertSaldo(0);
    }

    private function assertCarreraRespondida402($response): void
    {
        $response->assertStatus(402)
            ->assertJsonPath('codigo', 402)
            ->assertJsonPath('exito', false)
            ->assertJsonPath('error.tipo', 'SALDO_INSUFICIENTE')
            ->assertJsonPath('error.detalle', 'El saldo actual es 0 créditos. Se requiere al menos 1 crédito(s).')
            ->assertJsonPath('datos', null)
            ->assertJsonPath('metadatos.creditos_restantes', 0);

        $this->assertSaldo(0);
        $this->assertSame(0, Consulta::count());
        $this->assertDatabaseCount('logs_actividad', 0);
    }

    private function gastarSaldoEnOtraRequest(): void
    {
        Cliente::whereKey($this->cliente->id)->update(['saldo_creditos' => 0]);
    }

    private function assertSaldo(float $esperado): void
    {
        $saldo = (float) $this->cliente->fresh()->saldo_creditos;
        $this->assertSame($esperado, $saldo);
        $this->assertGreaterThanOrEqual(0, $saldo);
    }

    private function cedula()
    {
        return $this->withToken(self::API_KEY)->postJson('/api/v1/consulta/cedula', ['cedula' => self::CEDULA]);
    }

    private function ruc()
    {
        return $this->withToken(self::API_KEY)->postJson('/api/v1/consulta/ruc', ['ruc' => self::RUC]);
    }
}
