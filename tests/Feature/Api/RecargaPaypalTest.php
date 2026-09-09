<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Usuario;
use App\Services\RecargaPaypalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Mockery;
use Tests\TestCase;

class RecargaPaypalTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config(['paypal.mock' => true]);

        ConfigParametrosRecarga::seed();

        $this->usuario = Usuario::create([
            'uid' => 'test-paypal-uid',
            'email' => 'paypal@test.com',
            'nombre' => 'Cliente PayPal',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    // --- crearOrden ---

    public function test_crear_orden_sin_autenticacion_retorna_401(): void
    {
        $response = $this->postJson('/api/v1/recargas/paypal/orden', ['monto_usd' => 10.00]);

        $response->assertStatus(401);
    }

    public function test_crear_orden_sin_monto_retorna_422(): void
    {
        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/paypal/orden', []);

        $response->assertStatus(422);
        $response->assertJsonPath('error.tipo', 'VALIDACION');
    }

    public function test_crear_orden_con_monto_bajo_minimo_retorna_422(): void
    {
        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/paypal/orden', ['monto_usd' => 2.00]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.tipo', 'VALIDACION');
    }

    public function test_crear_orden_con_monto_valido_retorna_200_y_crea_pendiente(): void
    {
        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/paypal/orden', ['monto_usd' => 10.00]);

        $response->assertStatus(200);
        $response->assertJsonPath('exito', true);
        $response->assertJsonPath('datos.monto_usd', 10);
        $response->assertJsonPath('datos.creditos_calculados', 100);
        $response->assertJsonStructure([
            'datos' => ['order_id', 'approval_url', 'monto_usd', 'creditos_calculados'],
        ]);

        $orderId = $response->json('datos.order_id');
        $this->assertNotEmpty($orderId);

        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $orderId,
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'estado' => 'pendiente',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
        ]);
    }

    public function test_crear_orden_usa_la_tasa_de_config_parametros(): void
    {
        ConfigParametro::where('clave', 'tasaCambioUsdCreditos')->update([
            'valor' => json_encode(7),
        ]);

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/paypal/orden', ['monto_usd' => 5.00]);

        $response->assertStatus(200);
        $response->assertJsonPath('datos.creditos_calculados', 35);
    }

    public function test_crear_orden_cuando_paypal_falla_retorna_503(): void
    {
        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('createOrder')->andThrow(new ConnectionException('PayPal caído'));
        });

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/paypal/orden', ['monto_usd' => 10.00]);

        $response->assertStatus(503);
        $response->assertJsonPath('error.tipo', 'FUENTE_EXTERNA_NO_DISPONIBLE');

        $this->assertDatabaseMissing('recargas', [
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
        ]);
    }

    // --- capturar ---

    public function test_capturar_sin_autenticacion_retorna_401(): void
    {
        $response = $this->postJson('/api/v1/recargas/paypal/SOME-ORDER/capturar');

        $response->assertStatus(401);
    }

    public function test_capturar_sin_recarga_local_retorna_404_sin_llamar_a_paypal(): void
    {
        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/paypal/UNKNOWN-123/capturar');

        $response->assertStatus(404);
        $response->assertJsonPath('error.tipo', 'RECARGA_NO_ENCONTRADA');
    }

    public function test_capturar_con_paypal_connection_exception_retorna_503(): void
    {
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => 'MOCK-ORDER-X',
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->andThrow(new ConnectionException('timeout'));
        });

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson('/api/v1/recargas/paypal/MOCK-ORDER-X/capturar');

        $response->assertStatus(503);
        $response->assertJsonPath('error.tipo', 'FUENTE_EXTERNA_NO_DISPONIBLE');
    }

    public function test_capturar_con_status_declined_marca_fallida_y_retorna_200(): void
    {
        $orderId = 'MOCK-ORDER-DECLINED';
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $orderId,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($orderId) {
            $mock->shouldReceive('captureOrder')->with($orderId)->andReturn([
                'id' => $orderId,
                'status' => 'DECLINED',
            ]);
        });

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/paypal/{$orderId}/capturar");

        $response->assertStatus(200);
        $response->assertJsonPath('exito', false);
        $response->assertJsonPath('error.tipo', 'PAGO_NO_COMPLETADO');

        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $orderId,
            'estado' => 'fallida',
        ]);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'recarga.fallida',
            'actor_id' => $this->cliente->usuario->id,
        ]);
    }

    public function test_capturar_con_status_completed_acredita_y_retorna_200(): void
    {
        $orderId = 'MOCK-ORDER-COMPLETED';
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $orderId,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($orderId) {
            $mock->shouldReceive('captureOrder')->with($orderId)->andReturn([
                'id' => $orderId,
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => ['captures' => [[
                        'id' => 'CAP-1',
                        'status' => 'COMPLETED',
                        'amount' => ['currency_code' => 'USD', 'value' => '10.00'],
                    ]]],
                ]],
            ]);
        });

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/paypal/{$orderId}/capturar");

        $response->assertStatus(200);
        $response->assertJsonPath('exito', true);
        $response->assertJsonPath('datos.creditos_acreditados', 100);
        $response->assertJsonPath('datos.saldo_actual', 100);

        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $orderId,
            'estado' => 'completada',
        ]);
        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'recarga.acreditada',
            'actor_id' => $this->cliente->usuario->id,
        ]);
    }

    public function test_capturar_completed_con_monto_distinto_no_acredita(): void
    {
        $orderId = 'MOCK-ORDER-AMOUNT-MISMATCH';
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $orderId,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($orderId) {
            $mock->shouldReceive('captureOrder')->with($orderId)->andReturn([
                'id' => $orderId,
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => ['captures' => [[
                        'id' => 'CAP-AMOUNT-MISMATCH',
                        'status' => 'COMPLETED',
                        'amount' => ['currency_code' => 'USD', 'value' => '9.99'],
                    ]]],
                ]],
            ]);
        });

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/paypal/{$orderId}/capturar");

        $response->assertStatus(409);
        $response->assertJsonPath('error.tipo', 'PAGO_NO_VALIDO');
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $orderId,
            'estado' => 'fallida',
            'provider_status' => 'COMPLETED_MISMATCH',
        ]);
    }

    public function test_capturar_doble_click_es_idempotente(): void
    {
        $orderId = 'MOCK-ORDER-DOUBLE';
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $orderId,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($orderId) {
            $mock->shouldReceive('captureOrder')->with($orderId)->andReturn([
                'id' => $orderId,
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => ['captures' => [[
                        'id' => 'CAP-DOUBLE',
                        'status' => 'COMPLETED',
                        'amount' => ['currency_code' => 'USD', 'value' => '10.00'],
                    ]]],
                ]],
            ]);
        });

        $primera = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/paypal/{$orderId}/capturar");
        $segunda = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/paypal/{$orderId}/capturar");

        $primera->assertStatus(200);
        $primera->assertJsonPath('mensaje', 'Recarga acreditada');

        $segunda->assertStatus(200);
        $segunda->assertJsonPath('mensaje', 'Recarga ya procesada');
        $segunda->assertJsonPath('datos.creditos_acreditados', 100);
        $segunda->assertJsonPath('datos.saldo_actual', 100);

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(1, Recarga::where('referencia_externa', $orderId)->count());
        $this->assertSame(1, LogActividad::where('accion', 'recarga.acreditada')
            ->where('actor_id', $this->cliente->usuario->id)->count());
    }

    public function test_capturar_orden_de_otro_cliente_retorna_403(): void
    {
        $otroUsuario = Usuario::create([
            'uid' => 'OTHER-USER-UID',
            'email' => 'otro@test.com',
            'nombre' => 'Otro Cliente',
            'roles' => json_encode(['cliente']),
        ]);
        $otroCliente = Cliente::create([
            'usuario_id' => $otroUsuario->id,
            'saldo_creditos' => 0,
        ]);

        $orderId = 'MOCK-ORDER-OTHER-USER';
        Recarga::create([
            'cliente_id' => $otroCliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $orderId,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->postJson("/api/v1/recargas/paypal/{$orderId}/capturar");

        $response->assertStatus(403);
        $response->assertJsonPath('error.tipo', 'ORDEN_NO_AUTORIZADA');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

class ConfigParametrosRecarga
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
