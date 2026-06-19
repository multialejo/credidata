<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Consulta;
use App\Models\LogActividad;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    private string $validApiKey = 'cd_sk_testvalidkey1234567890abcd';
    private string $invalidApiKey = 'cd_sk_thiskeydoesnotexist123456';
    private Cliente $cliente;
    private string $cedula = '1234567890';

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_consulta_requires_api_key(): void
    {
        $response = $this->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'codigo' => 401,
            'exito' => false,
            'mensaje' => 'API Key requerida',
        ]);
        $response->assertJsonPath('error.tipo', 'API_KEY_REQUERIDA');
    }

    public function test_consulta_with_invalid_api_key(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->invalidApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'codigo' => 401,
            'exito' => false,
            'mensaje' => 'API Key inválida o revocada',
        ]);
        $response->assertJsonPath('error.tipo', 'API_KEY_INVALIDA');
    }

    public function test_consulta_with_revoked_api_key(): void
    {
        $this->cliente->update(['api_key_revocada' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'codigo' => 401,
            'exito' => false,
            'mensaje' => 'API Key inválida o revocada',
        ]);
        $response->assertJsonPath('error.tipo', 'API_KEY_INVALIDA');
    }

    public function test_consulta_with_exhausted_credits(): void
    {
        $this->cliente->update(['saldo_creditos' => 0]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(402);
        $response->assertExactJson(['error' => 'SALDO_INSUFICIENTE']);
    }

    public function test_consulta_successfully(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'consulta_id',
            'cedula',
            'creditos_gastados',
            'creditos_restantes',
            'mensaje',
        ]);
        $response->assertJson([
            'cedula' => $this->cedula,
            'creditos_gastados' => 1,
            'creditos_restantes' => 99,
            'mensaje' => 'consulta exitosa',
        ]);
    }

    public function test_consulta_deducts_credits(): void
    {
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

    public function test_consulta_creates_consulta_record(): void
    {
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
    }

    public function test_consulta_creates_log_entry(): void
    {
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

    public function test_consulta_validates_cedula_format(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cedula']);
    }

    public function test_consulta_uses_custom_cost(): void
    {
        ConfigParametro::where('clave', 'costoConsultaBase')->update([
            'valor' => json_encode(3),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'creditos_gastados' => 3,
            'creditos_restantes' => 97,
        ]);
    }

    public function test_consulta_updates_last_usage(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->cliente->refresh();
        $this->assertNotNull($this->cliente->api_key_ultimo_uso);
    }

    public function test_revoke_valid_api_key(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/api-key/revocar');

        $response->assertStatus(200);
        $response->assertJson([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'API Key revocada exitosamente',
        ]);

        $this->assertDatabaseHas('clientes', [
            'uid' => $this->cliente->uid,
            'api_key_revocada' => true,
        ]);
    }

    public function test_revoke_creates_log_entry(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/api-key/revocar');

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'API_KEY_REVOCADA',
            'actor_id' => $this->cliente->uid,
            'ip_origen' => '127.0.0.1',
        ]);
    }

    public function test_revoke_without_api_key(): void
    {
        $response = $this->postJson('/api/v1/api-key/revocar');

        $response->assertStatus(401);
        $response->assertJson([
            'codigo' => 401,
            'exito' => false,
            'mensaje' => 'API Key requerida',
        ]);
        $response->assertJsonPath('error.tipo', 'API_KEY_REQUERIDA');
    }

    public function test_revoke_with_invalid_api_key(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->invalidApiKey,
        ])->postJson('/api/v1/api-key/revocar');

        $response->assertStatus(401);
        $response->assertJson([
            'codigo' => 401,
            'exito' => false,
            'mensaje' => 'API Key inválida',
        ]);
        $response->assertJsonPath('error.tipo', 'API_KEY_INVALIDA');
    }

    public function test_revoke_already_revoked_key(): void
    {
        $this->cliente->update(['api_key_revocada' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/api-key/revocar');

        $response->assertStatus(401);
        $response->assertJson([
            'codigo' => 401,
            'exito' => false,
            'mensaje' => 'API Key inválida',
        ]);
        $response->assertJsonPath('error.tipo', 'API_KEY_INVALIDA');
    }
}
