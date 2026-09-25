<?php

namespace Tests\Feature\Api;

use App\Models\Aporte;
use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\ConfigParametro;
use App\Models\Usuario;
use App\Services\ApiKeyService;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\DocumentSnapshot;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Mockery;
use Tests\TestCase;

/**
 * La pantalla enviar-aporte.blade.php publica el curl y documentacion.blade.php describe
 * la respuesta de muestra. Esta clase ejecuta el cuerpo literal contra el endpoint real:
 * si el contrato deriva, la guia vuelve a mentir y el test cae.
 *
 * El sujeto de Firestore se mockea en cada prueba porque el estado de un documento real
 * depende de lo que haya aportado antes, y eso decide entre "aprobado" y "pendiente".
 */
class EjemploGuiaAporteTest extends TestCase
{
    use RefreshDatabase;

    private const IDENTIFICADOR = '1713175071';

    public function test_el_cuerpo_del_ejemplo_de_la_guia_produce_la_forma_documentada(): void
    {
        // Campo sin valor: es el caso que la guia muestra en su ejemplo de respuesta.
        $this->mockSujeto(['contacto' => ['telefonos' => [], 'emails' => [], 'direcciones' => []]]);
        $key = $this->apiKeyDeColaboradorActivo();

        $response = $this->withHeader('Authorization', 'Bearer '.$key)
            ->postJson('/api/v1/colaboradores/datos', [
                'identificador' => self::IDENTIFICADOR,
                'tipo_dato' => 'telefono',
                'valor' => '0991234567',
            ]);

        $response->assertCreated()->assertJsonPath('exito', true)->assertJsonPath('mensaje', 'Aporte registrado');

        // El ejemplo de la guia lista estas claves en este orden.
        $this->assertSame(
            ['id', 'identificador', 'tipo_dato', 'valor', 'estado', 'comentario', 'recompensa_creditos', 'fecha', 'revisado_en'],
            array_keys($response->json('datos.aporte'))
        );

        $this->assertSame('aprobado', $response->json('datos.aporte.estado'));
        $this->assertSame(1, $response->json('datos.aporte.recompensa_creditos'), 'La guia anuncia 1 credito para registro nuevo');

        // La guia afirma que el 201 no trae metadatos, a diferencia del resto de la API.
        $this->assertArrayNotHasKey('metadatos', $response->json());
    }

    public function test_una_reescritura_queda_pendiente_sin_acreditar_creditos(): void
    {
        // Campo con valor: la guia promete estado "pendiente" y recompensa en null.
        $this->mockSujeto(['contacto' => ['telefonos' => ['0990000000'], 'emails' => [], 'direcciones' => []]]);
        $key = $this->apiKeyDeColaboradorActivo();

        $response = $this->withHeader('Authorization', 'Bearer '.$key)
            ->postJson('/api/v1/colaboradores/datos', [
                'identificador' => self::IDENTIFICADOR,
                'tipo_dato' => 'telefono',
                'valor' => '0991234567',
            ])->assertCreated();

        $this->assertSame('pendiente', $response->json('datos.aporte.estado'));
        $this->assertNull($response->json('datos.aporte.recompensa_creditos'), 'Una reescritura no acredita todavia');
    }

    public function test_rechaza_telefono_con_formato_no_canonico(): void
    {
        $key = $this->apiKeyDeColaboradorActivo();

        $this->withHeader('Authorization', 'Bearer '.$key)
            ->postJson('/api/v1/colaboradores/datos', [
                'identificador' => self::IDENTIFICADOR,
                'tipo_dato' => 'telefono',
                'valor' => '099 123 4567',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('valor');
    }

    public function test_reenvio_de_aporte_pendiente_identico_devuelve_el_mismo_aporte(): void
    {
        $this->mockSujeto(['contacto' => ['telefonos' => ['0990000000'], 'emails' => [], 'direcciones' => []]]);
        $usuario = Usuario::create(['uid' => 'pendiente-identico', 'email' => 'pendiente-identico@test.com', 'nombre' => 'Guia', 'roles' => ['cliente']]);
        $cliente = Cliente::create(['usuario_id' => $usuario->id, 'saldo_creditos' => 0]);
        $colaborador = Colaborador::create(['usuario_id' => $usuario->id, 'estado_colaborador' => 'activo', 'terminos_version' => 1, 'terminos_aceptados_en' => now()]);
        $aporte = Aporte::create([
            'colaborador_id' => $colaborador->id,
            'identificador_relacionado' => self::IDENTIFICADOR,
            'tipo_dato' => 'telefono',
            'valor' => '0991234567',
            'estado' => 'pendiente',
            'fecha' => now(),
        ]);
        $key = app(ApiKeyService::class)->issue($cliente, ['scopes' => ['colaboradores:aportes'], 'ips' => []], $usuario->id);

        $this->withHeader('Authorization', 'Bearer '.$key)
            ->postJson('/api/v1/colaboradores/datos', [
                'identificador' => self::IDENTIFICADOR,
                'tipo_dato' => 'telefono',
                'valor' => '0991234567',
            ])
            ->assertCreated()
            ->assertJsonPath('datos.aporte.id', $aporte->id);

        $this->assertSame(1, Aporte::where('colaborador_id', $colaborador->id)->count());
    }

    public function test_no_permita_aportar_un_valor_ya_publicado(): void
    {
        $this->mockSujeto(['contacto' => ['telefonos' => ['0991234567'], 'emails' => [], 'direcciones' => []]]);
        $key = $this->apiKeyDeColaboradorActivo();

        $this->withHeader('Authorization', 'Bearer '.$key)
            ->postJson('/api/v1/colaboradores/datos', [
                'identificador' => self::IDENTIFICADOR,
                'tipo_dato' => 'telefono',
                'valor' => '0991234567',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('valor');

        $this->assertDatabaseCount('aportes', 0);
    }

    public function test_aplica_el_limite_diario_por_identificador(): void
    {
        $this->mockSujeto(['contacto' => ['telefonos' => ['0990000000'], 'emails' => [], 'direcciones' => []]]);
        $key = $this->apiKeyDeColaboradorActivo();
        $colaborador = Colaborador::firstOrFail();
        ConfigParametro::where('modulo', 'colaboracion')->where('clave', 'limiteDiarioPorIdentificador')->update(['valor' => json_encode(1)]);
        ConfigParametro::where('modulo', 'colaboracion')->where('clave', 'limiteDiarioPorColaborador')->update(['valor' => json_encode(10)]);
        Aporte::create([
            'colaborador_id' => $colaborador->id,
            'identificador_relacionado' => self::IDENTIFICADOR,
            'tipo_dato' => 'email',
            'valor' => 'anterior@example.com',
            'estado' => 'pendiente',
            'fecha' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$key)
            ->postJson('/api/v1/colaboradores/datos', [
                'identificador' => self::IDENTIFICADOR,
                'tipo_dato' => 'telefono',
                'valor' => '0991234567',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('identificador');

        $this->assertSame(1, Aporte::where('colaborador_id', $colaborador->id)->count());
    }

    public function test_aplica_el_limite_diario_general_del_colaborador(): void
    {
        $this->mockSujeto(['contacto' => ['telefonos' => ['0990000000'], 'emails' => [], 'direcciones' => []]]);
        $key = $this->apiKeyDeColaboradorActivo();
        $colaborador = Colaborador::firstOrFail();
        ConfigParametro::where('modulo', 'colaboracion')->where('clave', 'limiteDiarioPorIdentificador')->update(['valor' => json_encode(10)]);
        ConfigParametro::where('modulo', 'colaboracion')->where('clave', 'limiteDiarioPorColaborador')->update(['valor' => json_encode(1)]);
        Aporte::create([
            'colaborador_id' => $colaborador->id,
            'identificador_relacionado' => '0920000000',
            'tipo_dato' => 'email',
            'valor' => 'anterior@example.com',
            'estado' => 'pendiente',
            'fecha' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$key)
            ->postJson('/api/v1/colaboradores/datos', [
                'identificador' => self::IDENTIFICADOR,
                'tipo_dato' => 'telefono',
                'valor' => '0991234567',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('colaborador');

        $this->assertSame(1, Aporte::where('colaborador_id', $colaborador->id)->count());
    }

    private function apiKeyDeColaboradorActivo(): string
    {
        $usuario = Usuario::create(['uid' => 'guia-colaborador', 'email' => 'guia-colaborador@test.com', 'nombre' => 'Guia', 'roles' => ['cliente']]);
        $cliente = Cliente::create(['usuario_id' => $usuario->id, 'saldo_creditos' => 0]);
        Colaborador::create(['usuario_id' => $usuario->id, 'estado_colaborador' => 'activo', 'terminos_version' => 1, 'terminos_aceptados_en' => now()]);

        return app(ApiKeyService::class)->issue($cliente, ['scopes' => ['colaboradores:aportes'], 'ips' => []], $usuario->id);
    }

    private function mockSujeto(array $data): void
    {
        $snapshot = Mockery::mock(DocumentSnapshot::class);
        $snapshot->shouldReceive('exists')->andReturn(true);
        $snapshot->shouldReceive('data')->andReturn($data);

        $document = Mockery::mock(DocumentReference::class);
        $document->shouldReceive('snapshot')->andReturn($snapshot);
        $document->shouldReceive('set')->andReturn([]);

        $database = Mockery::mock(FirestoreClient::class);
        $database->shouldReceive('document')->with('sujetos/'.self::IDENTIFICADOR)->andReturn($document);

        $firestore = Mockery::mock(Firestore::class);
        $firestore->shouldReceive('database')->andReturn($database);

        Firebase::shouldReceive('firestore')->andReturn($firestore);
    }
}
