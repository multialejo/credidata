<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Consulta;
use App\Models\Usuario;
use App\Services\CatastroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Tests\TestCase;

class ConsultaRucTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'cd_sk_abcdef12_cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

    private const RUC = '0100001437001';

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = Usuario::create([
            'uid' => 'ruc-test-client', 'email' => 'ruc@test.com', 'nombre' => 'RUC Test',
            'roles' => json_encode(['cliente']),
        ]);
        $this->cliente = Cliente::create([
            'usuario_id' => $usuario->id, 'saldo_creditos' => 10,
            'api_key_hash' => Hash::make(substr(self::API_KEY, 15)), 'api_key_prefijo' => substr(self::API_KEY, 6, 8),
            'api_key_revocada' => false, 'api_key_alcance' => ['consulta:ruc'],
        ]);
        ConfigParametro::create(['modulo' => 'consulta', 'clave' => 'costoConsultaBase', 'valor' => json_encode(1)]);
    }

    public function test_returns_ordered_establishments_and_charges_once(): void
    {
        $this->mock(CatastroService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('buscarEstablecimientos')->once()->with(self::RUC)->andReturn([
                ['numero' => '001', 'razonSocial' => 'A'], ['numero' => '002', 'razonSocial' => 'B'],
            ]);
        });

        $response = $this->getResponse();

        $response->assertOk()->assertJsonPath('datos.ruc', self::RUC)->assertJsonCount(2, 'datos.establecimientos');
        $response->assertJsonPath('metadatos.creditos_gastados', 1);
        $this->assertSame(9.0, (float) $this->cliente->refresh()->saldo_creditos);
        $this->assertDatabaseHas('consultas', ['tipo' => 'ruc', 'identificador' => self::RUC, 'exitosa' => true]);
        $this->assertDatabaseHas('logs_actividad', ['accion' => 'CONSULTA_RUC']);
    }

    public function test_not_found_is_successful_processing_and_is_chargeable(): void
    {
        $this->mock(CatastroService::class, fn (MockInterface $mock) => $mock->shouldReceive('buscarEstablecimientos')->once()->andReturn([]));

        $this->getResponse()->assertStatus(404)->assertJsonPath('exito', true)->assertJsonPath('datos.establecimientos', []);
        $this->assertSame(9.0, (float) $this->cliente->refresh()->saldo_creditos);
        $this->assertDatabaseHas('consultas', ['tipo' => 'ruc', 'creditos_gastados' => 1, 'exitosa' => true]);
    }

    public function test_firestore_error_returns_503_without_charge_or_query_record(): void
    {
        $this->mock(CatastroService::class, fn (MockInterface $mock) => $mock->shouldReceive('buscarEstablecimientos')->once()->andThrow(new \RuntimeException('Firestore down')));

        $this->getResponse()->assertStatus(503)->assertJsonPath('error.tipo', 'FUENTE_EXTERNA_NO_DISPONIBLE');
        $this->assertSame(10.0, (float) $this->cliente->refresh()->saldo_creditos);
        $this->assertSame(0, Consulta::where('cliente_id', $this->cliente->id)->count());
    }

    public function test_rejects_cedula_as_ruc_before_calling_service(): void
    {
        $this->mock(CatastroService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('buscarEstablecimientos'));

        $this->withHeaders($this->headers())->postJson('/api/v1/consulta/ruc', ['ruc' => '1713175071'])
            ->assertStatus(422)->assertJsonValidationErrors('ruc');
    }

    public function test_missing_scope_returns_forbidden(): void
    {
        $this->cliente->update(['api_key_alcance' => ['consulta:cedula']]);

        $this->withHeaders($this->headers())->postJson('/api/v1/consulta/ruc', ['ruc' => self::RUC])
            ->assertStatus(403)->assertJsonPath('error.tipo', 'PERMISO_INSUFICIENTE');
    }

    public function test_insufficient_balance_does_not_call_service(): void
    {
        $this->cliente->update(['saldo_creditos' => 0]);
        $this->mock(CatastroService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('buscarEstablecimientos'));

        $this->getResponse()->assertStatus(402)->assertJsonPath('error.tipo', 'SALDO_INSUFICIENTE');
    }

    private function getResponse()
    {
        return $this->withHeaders($this->headers())->postJson('/api/v1/consulta/ruc', ['ruc' => self::RUC]);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer '.self::API_KEY];
    }
}
