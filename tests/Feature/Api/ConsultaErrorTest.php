<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Consulta;
use App\Models\LogActividad;
use App\Models\Usuario;
use App\Services\DinardapService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConsultaErrorTest extends TestCase
{
    use RefreshDatabase;

    private string $validApiKey = 'cd_sk_testvalidkey1234567890abcd';
    private Cliente $cliente;
    private string $cedula = '1234567890';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('dinardap.mock', false);

        $apiKeyHash = Hash::make($this->validApiKey);

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

        $this->partialMock(DinardapService::class, function ($mock) {
            $mock->shouldReceive('obtenerCache')
                ->with($this->cedula)
                ->andReturn(null);
        });
    }

    public function test_timeout_returns_503(): void
    {
        Http::fake(fn() => throw new ConnectionException('Timeout'));

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(503);
        $response->assertJsonPath('exito', false);
        $response->assertJsonPath('error.tipo', 'FUENTE_EXTERNA_NO_DISPONIBLE');
        $response->assertJsonPath('metadatos.creditos_gastados', 0);
    }

    public function test_timeout_does_not_deduct_credits(): void
    {
        Http::fake(fn() => throw new ConnectionException('Timeout'));

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->assertDatabaseHas('clientes', [
            'uid' => $this->cliente->uid,
            'saldo_creditos' => 100.0,
        ]);
    }

    public function test_timeout_creates_consulta_not_exitosa(): void
    {
        Http::fake(fn() => throw new ConnectionException('Timeout'));

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->assertDatabaseHas('consultas', [
            'cliente_id' => $this->cliente->uid,
            'exitosa' => false,
            'creditos_gastados' => 0,
        ]);
    }

    public function test_timeout_creates_log(): void
    {
        Http::fake(fn() => throw new ConnectionException('Timeout'));

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'CONSULTA_CEDULA',
            'actor_id' => $this->cliente->uid,
        ]);
    }

    public function test_not_found_returns_404(): void
    {
        Http::fake(['*' => Http::response(['error' => 'no_encontrado'], 404)]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('exito', false);
        $response->assertJsonPath('error.tipo', 'SUJETO_NO_ENCONTRADO');
    }

    public function test_not_found_deducts_credits(): void
    {
        Http::fake(['*' => Http::response(['error' => 'no_encontrado'], 404)]);

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

    public function test_not_found_creates_consulta_exitosa(): void
    {
        Http::fake(['*' => Http::response(['error' => 'no_encontrado'], 404)]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->assertDatabaseHas('consultas', [
            'cliente_id' => $this->cliente->uid,
            'exitosa' => true,
            'creditos_gastados' => 1,
        ]);
    }

    public function test_not_found_creates_log(): void
    {
        Http::fake(['*' => Http::response(['error' => 'no_encontrado'], 404)]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'CONSULTA_CEDULA',
            'actor_id' => $this->cliente->uid,
        ]);
    }

    public function test_unauthorized_returns_503(): void
    {
        Http::fake(['*' => Http::response(null, 401)]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(503);
        $response->assertJsonPath('error.tipo', 'FUENTE_EXTERNA_NO_DISPONIBLE');
    }

    public function test_unauthorized_does_not_deduct_credits(): void
    {
        Http::fake(['*' => Http::response(null, 401)]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->assertDatabaseHas('clientes', [
            'uid' => $this->cliente->uid,
            'saldo_creditos' => 100.0,
        ]);
    }

    public function test_malformed_response_is_treated_as_success(): void
    {
        Http::fake(['*' => Http::response(null, 200)]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('metadatos.fuente', 'dinardap');
    }
}
