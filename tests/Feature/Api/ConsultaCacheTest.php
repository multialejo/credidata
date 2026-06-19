<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Consulta;
use App\Models\Usuario;
use App\Services\DinardapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class ConsultaCacheTest extends TestCase
{
    use RefreshDatabase;

    private string $validApiKey = 'cd_sk_testvalidkey1234567890abcd';
    private Cliente $cliente;
    private string $cedula = '1234567890';

    private array $cachedData = [
        'tipoIdentificador' => 'cedula',
        'nombres' => 'Carlos Cacheado',
        'fechaNacimiento' => '1990-12-25',
        'lugarNacimiento' => 'Cuenca',
        'estadoCivilCodigo' => 2,
        'conyuge' => 'Ana',
        'ubicacion' => [
            'provincia' => 'Azuay',
            'canton' => 'Cuenca',
            'parroquia' => 'El Valle',
        ],
        'fuentesUtilizadas' => ['dinardap'],
        'ultimaActualizacion' => '2025-06-18T10:00:00+00:00',
        'ultimaConsulta' => '2025-06-18T10:00:00+00:00',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $apiKeyHash = Hash::make($this->validApiKey);

        $this->app['config']->set('dinardap.mock', false);

        Usuario::create([
            'uid' => 'test-cliente-uid',
            'email' => 'cliente@test.com',
            'nombre' => 'Test Cliente',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'uid' => 'test-cliente-uid',
            'saldo_creditos' => 100,
            'api_key_prefijo' => substr($this->validApiKey, 0, 12),
            'api_key_hash' => $apiKeyHash,
            'api_key_creada' => now(),
            'api_key_revocada' => false,
        ]);

        ConfigParametro::create([
            'modulo' => 'consulta',
            'clave' => 'costoConsultaBase',
            'valor' => json_encode(1),
        ]);

        ConfigParametro::create([
            'modulo' => 'dinardap',
            'clave' => 'cacheTTLMinutos',
            'valor' => json_encode('1440'),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockDinardapCacheHit(): void
    {
        $this->mock(DinardapService::class, function ($mock) {
            $mock->shouldReceive('obtenerCache')
                ->once()
                ->with($this->cedula)
                ->andReturn($this->cachedData);
            $mock->shouldReceive('consultar')->never();
            $mock->shouldReceive('guardarCache')->never();
        });
    }

    public function test_cache_hit_returns_200(): void
    {
        $this->mockDinardapCacheHit();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('metadatos.fuente', 'cache');
        $response->assertJsonPath('datos.nombres', 'Carlos Cacheado');
    }

    public function test_cache_hit_deducts_credits(): void
    {
        $this->mockDinardapCacheHit();

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->assertDatabaseHas('clientes', [
            'uid' => $this->cliente->uid,
            'saldo_creditos' => 99.0,
        ]);
    }

    public function test_cache_hit_creates_consulta_record(): void
    {
        $this->mockDinardapCacheHit();

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->assertDatabaseHas('consultas', [
            'cliente_id' => $this->cliente->uid,
            'tipo' => 'cedula',
            'identificador' => $this->cedula,
            'creditos_gastados' => 1,
            'exitosa' => true,
            'origen' => 'api',
        ]);

        $consulta = Consulta::where('cliente_id', $this->cliente->uid)->first();
        $this->assertNotNull($consulta->resultado_json);
        $this->assertEquals('Carlos Cacheado', $consulta->resultado_json['nombres']);
    }

    public function test_cache_hit_creates_log_entry(): void
    {
        $this->mockDinardapCacheHit();

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'CONSULTA_CEDULA',
            'actor_id' => $this->cliente->uid,
            'ip_origen' => '127.0.0.1',
        ]);
    }

    public function test_cache_hit_response_has_correct_structure(): void
    {
        $this->mockDinardapCacheHit();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertJsonStructure([
            'codigo',
            'exito',
            'mensaje',
            'datos' => ['nombres', 'fechaNacimiento', 'conyuge'],
            'metadatos' => ['timestamp', 'consulta_id', 'cedula', 'creditos_gastados', 'creditos_restantes', 'fuente'],
        ]);
        $response->assertJsonPath('metadatos.cedula', $this->cedula);
        $response->assertJsonPath('metadatos.creditos_gastados', 1);
        $response->assertJsonPath('metadatos.creditos_restantes', 99);
        $response->assertJsonPath('metadatos.fuente', 'cache');
    }
}
