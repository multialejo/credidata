<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Colaborador;
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
 * La vista enviar-aporte.blade.php publica un ejemplo de curl y una respuesta de muestra.
 * Esta clase ejecuta ese ejemplo literal contra el endpoint real: si el contrato deriva,
 * el ejemplo vuelve a mentir y el test cae.
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
                'valor' => '+593 99 123 4567',
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
        $this->mockSujeto(['contacto' => ['telefonos' => ['+593 99 000 0000'], 'emails' => [], 'direcciones' => []]]);
        $key = $this->apiKeyDeColaboradorActivo();

        $response = $this->withHeader('Authorization', 'Bearer '.$key)
            ->postJson('/api/v1/colaboradores/datos', [
                'identificador' => self::IDENTIFICADOR,
                'tipo_dato' => 'telefono',
                'valor' => '+593 99 123 4567',
            ])->assertCreated();

        $this->assertSame('pendiente', $response->json('datos.aporte.estado'));
        $this->assertNull($response->json('datos.aporte.recompensa_creditos'), 'Una reescritura no acredita todavia');
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
