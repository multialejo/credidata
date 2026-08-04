<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoRecarga;
use App\Jobs\SendRecargaEmail;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecargaPayphoneTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        ConfigParametrosRecargaPayphone::seed();

        config()->set('payphone.api_token', 'test-token');
        config()->set('payphone.store_id', 'test-store-id');
        config()->set('payphone.mock', true);
        config()->set('payphone.timeout', 10);
        config()->set('payphone.reference', 'CrediData recarga');
        config()->set('payphone.currency', 'USD');

        $this->usuario = Usuario::create([
            'uid' => 'test-payphone-uid',
            'email' => 'payphone@test.com',
            'nombre' => 'Cliente Payphone',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_crear_transaccion_sin_autenticacion_retorna_401(): void
    {
        $response = $this->postJson('/api/v1/recargas/payphone/transaccion', ['monto_usd' => 10.00]);

        $response->assertStatus(401);
    }

    public function test_crear_transaccion_sin_monto_retorna_422(): void
    {
        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/transaccion', []);

        $response->assertStatus(422);
        $response->assertJsonPath('error.tipo', 'VALIDACION');
    }

    public function test_crear_transaccion_con_monto_bajo_minimo_retorna_422(): void
    {
        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/transaccion', ['monto_usd' => 2.00]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.tipo', 'VALIDACION');
    }

    public function test_crear_transaccion_con_monto_valido_retorna_200_y_crea_pendiente(): void
    {
        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/transaccion', ['monto_usd' => 10.00]);

        $response->assertStatus(200);
        $response->assertJsonPath('exito', true);
        $response->assertJsonPath('datos.monto_usd', 10);
        $response->assertJsonPath('datos.creditos_calculados', 100);
        $response->assertJsonStructure([
            'datos' => [
                'recarga_id', 'client_transaction_id', 'monto_usd', 'creditos_calculados', 'pay_with_payphone', 'pay_with_card',
            ],
        ]);

        $ctid = $response->json('datos.client_transaction_id');
        $this->assertNotEmpty($ctid);
        $this->assertStringStartsWith('bs-', $ctid);

        $this->assertNotEmpty($response->json('datos.pay_with_payphone'));
        $this->assertNotEmpty($response->json('datos.pay_with_card'));

        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $ctid,
            'cliente_id' => $this->cliente->id,
            'metodo' => 'payphone',
            'estado' => 'pendiente',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
        ]);
    }

    public function test_crear_transaccion_usa_la_tasa_de_config_parametros(): void
    {
        ConfigParametro::where('clave', 'tasaCambioUsdCreditos')->update([
            'valor' => json_encode(7),
        ]);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/transaccion', ['monto_usd' => 5.00]);

        $response->assertStatus(200);
        $response->assertJsonPath('datos.creditos_calculados', 35);
    }

    public function test_crear_transaccion_dos_veces_genera_client_transaction_id_distintos(): void
    {
        $primera = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/transaccion', ['monto_usd' => 10.00]);

        $segunda = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/transaccion', ['monto_usd' => 10.00]);

        $primera->assertStatus(200);
        $segunda->assertStatus(200);
        $this->assertNotSame(
            $primera->json('datos.client_transaction_id'),
            $segunda->json('datos.client_transaction_id'),
        );
    }

    public function test_crear_transaccion_con_prepare_falla_retorna_503_sin_crear_fila(): void
    {
        Queue::fake();

        config()->set('payphone.mock', false);
        Http::fake([
            '*button/Prepare*' => function () {
                throw new ConnectionException('timeout');
            },
        ]);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/transaccion', ['monto_usd' => 10.00]);

        $response->assertStatus(503);
        $response->assertJsonPath('error.tipo', 'FUENTE_EXTERNA_NO_DISPONIBLE');

        $this->assertSame(0, Recarga::where('metodo', 'payphone')
            ->where('cliente_id', $this->cliente->id)->count());
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);

        Queue::assertNothingPushed();
    }

    public function test_confirmar_sin_autenticacion_retorna_401(): void
    {
        $response = $this->postJson('/api/v1/recargas/payphone/12345/confirmar', [
            'clientTransactionId' => 'bs-1-abc',
        ]);

        $response->assertStatus(401);
    }

    public function test_confirmar_sin_client_transaction_id_retorna_422(): void
    {
        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/12345/confirmar', []);

        $response->assertStatus(422);
    }

    public function test_confirmar_con_referencia_inexistente_retorna_404(): void
    {
        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/12345/confirmar', [
                'clientTransactionId' => 'bs-inexistente',
            ]);

        $response->assertStatus(404);
        $response->assertJsonPath('error.tipo', 'RECARGA_NO_ENCONTRADA');
    }

    public function test_confirmar_approved_acredita_y_encola_email(): void
    {
        Queue::fake();

        $ctid = 'bs-approved-001';
        $recarga = $this->crearRecargaPendiente($ctid, 100);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/payphone/{$recarga->id}/confirmar", [
                'clientTransactionId' => $ctid,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('exito', true);
        $response->assertJsonPath('datos.creditos_acreditados', 100);
        $response->assertJsonPath('datos.saldo_actual', 100);

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $ctid,
            'estado' => 'completada',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
        ]);
        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'recarga.acreditada',
            'actor_id' => $this->usuario->id,
        ]);

        Queue::assertPushed(SendRecargaEmail::class, function ($job) use ($ctid) {
            return $job->recarga->referencia_externa === $ctid;
        });
    }

    public function test_confirmar_dos_veces_con_misma_referencia_acredita_una_sola(): void
    {
        Queue::fake();

        $ctid = 'bs-idempotente-001';
        $recarga = $this->crearRecargaPendiente($ctid, 100);

        $primera = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/payphone/{$recarga->id}/confirmar", [
                'clientTransactionId' => $ctid,
            ]);

        $segunda = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/payphone/{$recarga->id}/confirmar", [
                'clientTransactionId' => $ctid,
            ]);

        $primera->assertStatus(200);
        $primera->assertJsonPath('datos.creditos_acreditados', 100);

        $segunda->assertStatus(200);
        $segunda->assertJsonPath('mensaje', 'Recarga ya procesada');
        $segunda->assertJsonPath('datos.creditos_acreditados', 100);
        $segunda->assertJsonPath('datos.saldo_actual', 100);

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(1, Recarga::where('referencia_externa', $ctid)->count());
        $this->assertSame(1, LogActividad::where('accion', 'recarga.acreditada')
            ->where('actor_id', $this->usuario->id)->count());
    }

    public function test_confirmar_canceled_marca_fallida_sin_acreditar(): void
    {
        Queue::fake();

        config()->set('payphone.mock', false);
        Http::fake([
            '*button/V2/Confirm*' => Http::response([
                'statusCode' => 2,
                'transactionStatus' => 'Canceled',
                'message' => 'Pago cancelado por el usuario',
            ], 200),
        ]);

        $ctid = 'bs-canceled-001';
        $recarga = $this->crearRecargaPendiente($ctid, 100);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/payphone/{$recarga->id}/confirmar", [
                'clientTransactionId' => $ctid,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('exito', false);
        $response->assertJsonPath('error.tipo', 'PAGO_NO_COMPLETADO');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $ctid,
            'estado' => 'fallida',
        ]);
        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'recarga.fallida',
            'actor_id' => $this->usuario->id,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_confirmar_con_referencia_de_otro_cliente_retorna_403(): void
    {
        Queue::fake();

        $otroUsuario = Usuario::create([
            'uid' => 'otro-payphone-uid',
            'email' => 'otro@test.com',
            'nombre' => 'Otro Cliente',
            'roles' => json_encode(['cliente']),
        ]);
        $otroCliente = Cliente::create([
            'usuario_id' => $otroUsuario->id,
            'saldo_creditos' => 0,
        ]);

        $ctid = 'bs-otro-cliente-001';
        $recarga = Recarga::create([
            'cliente_id' => $otroCliente->id,
            'metodo' => 'payphone',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => $ctid,
            'fecha' => now(),
        ]);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/payphone/{$recarga->id}/confirmar", [
                'clientTransactionId' => $ctid,
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error.tipo', 'TRANSACCION_NO_AUTORIZADA');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(0, (int) $otroCliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $ctid,
            'estado' => 'pendiente',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_confirmar_con_timeout_del_sdk_retorna_503_sin_tocar_recargas(): void
    {
        Queue::fake();

        config()->set('payphone.mock', false);
        Http::fake([
            '*button/V2/Confirm*' => function () {
                throw new ConnectionException('timeout');
            },
        ]);

        $ctid = 'bs-timeout-001';
        $recarga = $this->crearRecargaPendiente($ctid, 100);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/payphone/{$recarga->id}/confirmar", [
                'clientTransactionId' => $ctid,
            ]);

        $response->assertStatus(503);
        $response->assertJsonPath('error.tipo', 'FUENTE_EXTERNA_NO_DISPONIBLE');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $ctid,
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseMissing('logs_actividad', [
            'accion' => 'recarga.acreditada',
            'actor_id' => $this->usuario->id,
        ]);

        Queue::assertNothingPushed();
    }

    private function crearRecargaPendiente(string $ctid, int $creditos): Recarga
    {
        return Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'payphone',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => $creditos,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => $ctid,
            'fecha' => now(),
        ]);
    }
}

class ConfigParametrosRecargaPayphone
{
    public static function seed(): void
    {
        ConfigParametro::create([
            'modulo' => 'financiero',
            'clave' => 'tasaCambioUsdCreditos',
            'valor' => json_encode(10),
        ]);
        ConfigParametro::create([
            'modulo' => 'financiero',
            'clave' => 'recargaMinimaUsd',
            'valor' => json_encode('5.00'),
        ]);
    }
}
