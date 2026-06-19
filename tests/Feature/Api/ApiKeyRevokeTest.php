<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiKeyRevokeTest extends TestCase
{
    use RefreshDatabase;

    private string $validApiKey = 'cd_sk_testvalidkey1234567890abcd';
    private string $invalidApiKey = 'cd_sk_thiskeydoesnotexist123456';
    private Cliente $cliente;

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
