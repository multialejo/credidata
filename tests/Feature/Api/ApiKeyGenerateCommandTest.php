<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyGenerateCommandTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::create([
            'uid' => 'test-cliente-uid',
            'email' => 'cliente@test.com',
            'nombre' => 'Test Cliente',
            'roles' => json_encode(['cliente']),
        ]);

        Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 100,
        ]);
    }

    public function test_command_fails_for_missing_client(): void
    {
        $this->artisan('apikey:generate', ['uid' => 'non-existent'])
            ->expectsOutputToContain('Cliente no encontrado')
            ->assertExitCode(1);
    }

    public function test_command_generates_key_for_valid_client(): void
    {
        $this->artisan('apikey:generate', ['uid' => 'test-cliente-uid'])
            ->expectsOutputToContain('API key generada: cd_sk_')
            ->assertExitCode(0);
    }

    public function test_command_stores_hash_and_prefix(): void
    {
        $this->artisan('apikey:generate', ['uid' => 'test-cliente-uid'])
            ->assertExitCode(0);

        $cliente = Cliente::whereHas('usuario', fn ($q) => $q->where('uid', 'test-cliente-uid')
        )->first();

        $this->assertNotNull($cliente->api_key_hash);
        $this->assertNotNull($cliente->api_key_prefijo);
        $this->assertStringStartsWith('cd_sk_', $cliente->api_key_prefijo);
        $this->assertEquals(12, strlen($cliente->api_key_prefijo));

        $this->assertNotNull($cliente->api_key_creada);
        $this->assertFalse($cliente->api_key_revocada);
        $this->assertNull($cliente->api_key_revocada_en);
    }

    public function test_command_creates_log_entry(): void
    {
        $this->artisan('apikey:generate', ['uid' => 'test-cliente-uid'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'API_KEY_GENERADA',
            'actor_id' => $this->usuario->id,
            'ip_origen' => 'sistema',
        ]);
    }
}
