<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoIntencionPayphone;
use App\Jobs\SendRecargaEmail;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\IntencionPayphone;
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

    public function test_crear_transaccion_con_monto_valido_retorna_200_y_crea_intencion(): void
    {
        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/transaccion', ['monto_usd' => 10.00]);

        $response->assertStatus(200);
        $response->assertJsonPath('exito', true);
        $response->assertJsonPath('datos.monto_usd', 10);
        $response->assertJsonPath('datos.creditos_calculados', 100);
        $response->assertJsonStructure([
            'datos' => [
                'intencion_id', 'client_transaction_id', 'monto_usd', 'creditos_calculados', 'pay_with_payphone', 'pay_with_card',
            ],
        ]);

        $ctid = $response->json('datos.client_transaction_id');
        $this->assertNotEmpty($ctid);
        $this->assertStringStartsWith('bs-', $ctid);

        $this->assertNotEmpty($response->json('datos.pay_with_payphone'));
        $this->assertNotEmpty($response->json('datos.pay_with_card'));

        $this->assertDatabaseHas('intenciones_payphone', [
            'ctid' => $ctid,
            'cliente_id' => $this->cliente->id,
            'estado' => 'pendiente',
            'moneda' => 'USD',
            'monto_usd' => 10.00,
            'creditos_estimados' => 100,
        ]);
        $this->assertNotNull(IntencionPayphone::where('ctid', $ctid)->value('payment_id'));
        $this->assertNotNull(IntencionPayphone::where('ctid', $ctid)->value('expira_en'));
        $this->assertDatabaseMissing('recargas', ['referencia_externa' => $ctid]);
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

        $this->assertSame(0, IntencionPayphone::where('cliente_id', $this->cliente->id)->count());
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
        Http::fake([
            '*button/V2/Confirm*' => Http::response([
                'statusCode' => 3,
                'transactionStatus' => 'Approved',
                'transactionId' => '88888',
                'authorizationCode' => 'AUTH-001',
                'amount' => 1000,
                'message' => null,
            ], 200),
        ]);
        config()->set('payphone.mock', false);

        $ctid = 'bs-approved-001';
        $this->crearIntencionPendiente($ctid);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/12345/confirmar', [
                'clientTransactionId' => $ctid,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('exito', true);
        $response->assertJsonPath('datos.creditos_acreditados', 100);
        $response->assertJsonPath('datos.saldo_actual', 100);

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('intenciones_payphone', [
            'ctid' => $ctid,
            'estado' => 'confirmada',
        ]);
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

        $evidencia = Recarga::where('referencia_externa', $ctid)->firstOrFail();
        $this->assertSame('12345', $evidencia->provider_payment_id);
        $this->assertSame('88888', $evidencia->provider_transaction_id);
        $this->assertNotNull($evidencia->provider_verified_at);
    }

    public function test_confirmar_con_payment_id_distinto_no_llama_a_payphone(): void
    {
        Http::fake();

        $ctid = 'bs-payment-id-mismatch';
        $this->crearIntencionPendiente($ctid);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/99999/confirmar', [
                'clientTransactionId' => $ctid,
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.tipo', 'PAGO_NO_VALIDO');
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        Http::assertNothingSent();
    }

    public function test_confirmar_dos_veces_con_misma_referencia_acredita_una_sola(): void
    {
        Queue::fake();
        Http::fake([
            '*button/V2/Confirm*' => Http::response([
                'statusCode' => 3,
                'transactionStatus' => 'Approved',
                'transactionId' => '88888',
                'authorizationCode' => 'AUTH-002',
                'amount' => 1000,
                'message' => null,
            ], 200),
        ]);
        config()->set('payphone.mock', false);

        $ctid = 'bs-idempotente-001';
        $this->crearIntencionPendiente($ctid);

        $primera = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/12345/confirmar', [
                'clientTransactionId' => $ctid,
            ]);

        $segunda = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/12345/confirmar', [
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

    public function test_confirmar_canceled_marca_intencion_cancelada_sin_acreditar(): void
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
        $this->crearIntencionPendiente($ctid);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/12345/confirmar', [
                'clientTransactionId' => $ctid,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('exito', false);
        $response->assertJsonPath('error.tipo', 'PAGO_NO_COMPLETADO');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('intenciones_payphone', [
            'ctid' => $ctid,
            'estado' => 'cancelada',
        ]);
        $this->assertDatabaseMissing('recargas', ['referencia_externa' => $ctid]);
        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'recarga.fallida',
            'actor_id' => $this->usuario->id,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_confirmar_con_monto_pagado_distinto_retorna_409_y_cancela(): void
    {
        Queue::fake();

        config()->set('payphone.mock', false);
        Http::fake([
            '*button/V2/Confirm*' => Http::response([
                'statusCode' => 3,
                'transactionStatus' => 'Approved',
                'transactionId' => '88888',
                'authorizationCode' => 'AUTH-003',
                'amount' => 900,
                'message' => null,
            ], 200),
        ]);

        $ctid = 'bs-monto-invalido-001';
        $this->crearIntencionPendiente($ctid);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/12345/confirmar', [
                'clientTransactionId' => $ctid,
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.tipo', 'MONTO_NO_COINCIDE');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('intenciones_payphone', [
            'ctid' => $ctid,
            'estado' => 'cancelada',
        ]);
        $this->assertDatabaseMissing('recargas', ['referencia_externa' => $ctid]);

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
        IntencionPayphone::create([
            'cliente_id' => $otroCliente->id,
            'ctid' => $ctid,
            'payment_id' => '12345',
            'monto_usd' => 10.00,
            'creditos_estimados' => 100,
            'moneda' => 'USD',
            'estado' => EstadoIntencionPayphone::Pendiente,
            'expira_en' => now()->addMinutes(15),
            'fecha' => now(),
        ]);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/12345/confirmar', [
                'clientTransactionId' => $ctid,
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error.tipo', 'TRANSACCION_NO_AUTORIZADA');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(0, (int) $otroCliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('intenciones_payphone', [
            'ctid' => $ctid,
            'estado' => 'pendiente',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_confirmar_con_timeout_del_sdk_retorna_503_pago_pendiente_sin_tocar_estado(): void
    {
        Queue::fake();

        config()->set('payphone.mock', false);
        Http::fake([
            '*button/V2/Confirm*' => function () {
                throw new ConnectionException('timeout');
            },
        ]);

        $ctid = 'bs-timeout-001';
        $this->crearIntencionPendiente($ctid);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/payphone/12345/confirmar', [
                'clientTransactionId' => $ctid,
            ]);

        $response->assertStatus(503);
        $response->assertJsonPath('error.tipo', 'PAGO_PENDIENTE');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('intenciones_payphone', [
            'ctid' => $ctid,
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseMissing('logs_actividad', [
            'accion' => 'recarga.acreditada',
            'actor_id' => $this->usuario->id,
        ]);

        Queue::assertNothingPushed();
    }

    private function crearIntencionPendiente(string $ctid, string $paymentId = '12345', float $monto = 10.00, int $creditos = 100): IntencionPayphone
    {
        return IntencionPayphone::create([
            'cliente_id' => $this->cliente->id,
            'ctid' => $ctid,
            'payment_id' => $paymentId,
            'monto_usd' => $monto,
            'creditos_estimados' => $creditos,
            'moneda' => 'USD',
            'estado' => EstadoIntencionPayphone::Pendiente,
            'expira_en' => now()->addMinutes(15),
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
