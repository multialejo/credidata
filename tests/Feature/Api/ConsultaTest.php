<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Consulta;
use App\Models\LogActividad;
use App\Models\Usuario;
use App\Services\DinardapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConsultaTest extends TestCase
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

        $this->partialMock(DinardapService::class, function ($mock) {
            $mock->shouldReceive('obtenerCache')
                ->zeroOrMoreTimes()
                ->andReturn(null);
        });
    }

    public function test_requires_api_key(): void
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

    public function test_with_invalid_api_key(): void
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

    public function test_with_revoked_api_key(): void
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

    public function test_with_exhausted_credits(): void
    {
        $this->cliente->update(['saldo_creditos' => 0]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(402);
        $response->assertJsonPath('codigo', 402);
        $response->assertJsonPath('exito', false);
        $response->assertJsonPath('error.tipo', 'SALDO_INSUFICIENTE');
        $response->assertJsonPath('datos', null);
    }

    public function test_successfully(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Consulta exitosa',
        ]);
        $response->assertJsonPath('metadatos.cedula', $this->cedula);
        $response->assertJsonPath('metadatos.creditos_gastados', 1);
        $response->assertJsonPath('metadatos.creditos_restantes', 99);
        $response->assertJsonPath('metadatos.fuente', 'dinardap');
        $response->assertJsonPath('datos.nombres', 'Juan Carlos Pérez García');
    }

    public function test_deducts_credits(): void
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

    public function test_creates_consulta_record(): void
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

    public function test_creates_log_entry(): void
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

    public function test_validates_cedula_format(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cedula']);
    }

    public function test_uses_custom_cost(): void
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
        $response->assertJsonPath('metadatos.creditos_gastados', 3);
        $response->assertJsonPath('metadatos.creditos_restantes', 97);
    }

    public function test_updates_last_usage(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $this->cliente->refresh();
        $this->assertNotNull($this->cliente->api_key_ultimo_uso);
    }

    public function test_returns_normalized_data_structure(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'datos' => [
                'nombres',
                'fechaNacimiento',
                'lugarNacimiento',
                'estadoCivilCodigo',
                'conyuge',
                'ubicacion' => ['provincia', 'canton', 'parroquia'],
            ],
        ]);
        $response->assertJsonPath('datos.nombres', 'Juan Carlos Pérez García');
        $response->assertJsonPath('datos.fechaNacimiento', '1985-06-15');
        $response->assertJsonPath('datos.ubicacion.provincia', 'Pichincha');
    }

    public function test_creates_consulta_with_resultado_json(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $consulta = Consulta::where('cliente_id', $this->cliente->uid)->first();
        $this->assertNotNull($consulta);
        $this->assertNotNull($consulta->resultado_json);
        $this->assertEquals('Juan Carlos Pérez García', $consulta->resultado_json['nombres']);
    }

    public function test_creates_consulta_with_fuentes_utilizadas(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $consulta = Consulta::where('cliente_id', $this->cliente->uid)->first();
        $this->assertNotNull($consulta);
        $this->assertNotNull($consulta->fuentes_utilizadas);
        $this->assertEquals(['dinardap'], $consulta->fuentes_utilizadas);
    }

    public function test_response_has_unified_structure(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validApiKey,
        ])->postJson('/api/v1/consulta/cedula', [
            'cedula' => $this->cedula,
        ]);

        $response->assertJsonStructure([
            'codigo',
            'exito',
            'mensaje',
            'datos',
            'metadatos' => ['timestamp', 'consulta_id', 'cedula', 'creditos_gastados', 'creditos_restantes', 'fuente'],
        ]);
    }
}
