<?php

namespace Tests\Feature\Web;

use App\Enums\EstadoIntencionPaypal;
use App\Enums\EstadoRecarga;
use App\Mail\RecargaConfirmada;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\IntencionPaypal;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Usuario;
use App\Services\RecargaPaypalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class RecargaPaypalReturnTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config(['paypal.mock' => true]);
        ConfigParametrosRecargaPaypalWeb::seed();

        $this->usuario = Usuario::create([
            'uid' => 'paypal-return-uid',
            'email' => 'paypal-return@test.com',
            'nombre' => 'Cliente PayPal Return',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_retorno_con_recarga_completada_muestra_exito_sin_recapturar(): void
    {
        $token = 'ORDER-COMPLETED-001';
        $saldoInicial = 100;

        $this->cliente->update(['saldo_creditos' => $saldoInicial]);

        $this->crearIntencion($token);
        $this->crearRecargaCompletada($token);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Recarga acreditada');
        $response->assertSeeText('100');
        $response->assertSeeText('Volver a Inicio');
        $response->assertSee(route('dashboard'));
        $this->assertSame($saldoInicial, (int) $this->cliente->fresh()->saldo_creditos);
    }

    public function test_retorno_con_recarga_completada_y_guest_muestra_exito_sin_recapturar(): void
    {
        $token = 'ORDER-COMPLETED-GUEST-001';
        $saldoInicial = 100;

        $this->cliente->update(['saldo_creditos' => $saldoInicial]);

        $this->crearIntencion($token);
        $this->crearRecargaCompletada($token);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Recarga acreditada');
        $response->assertSeeText('100');
        $response->assertSeeText('Volver a Inicio');
        $this->assertSame($saldoInicial, (int) $this->cliente->fresh()->saldo_creditos);
    }

    public function test_retorno_con_intencion_confirmada_muestra_exito_sin_recapturar(): void
    {
        $token = 'ORDER-CONFIRMADA-001';

        $this->crearIntencion($token, EstadoIntencionPaypal::Confirmada);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Recarga acreditada');
        $this->assertSame(EstadoIntencionPaypal::Confirmada, IntencionPaypal::where('order_id', $token)->first()->estado);
    }

    public function test_retorno_con_intencion_pendiente_y_owner_captura_y_acredita(): void
    {
        Mail::fake();

        $token = 'ORDER-PENDING-001';
        $saldoInicial = 0;

        $this->cliente->update(['saldo_creditos' => $saldoInicial]);

        $this->crearIntencion($token);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($token) {
            $mock->shouldReceive('captureOrder')
                ->with($token, 10.0)
                ->once()
                ->andReturn($this->capturaCompletada($token, 'CAP-RETURN-001', '10.00'));
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Recarga acreditada');
        $response->assertSeeText('100');
        $response->assertSeeText('Volver a Inicio');

        $this->assertSame(EstadoIntencionPaypal::Confirmada, IntencionPaypal::where('order_id', $token)->first()->estado);
        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $token,
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'estado' => 'completada',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'provider_payment_id' => 'CAP-RETURN-001',
        ]);
        $this->assertSame($saldoInicial + 100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(1, LogActividad::where('accion', 'recarga.acreditada')
            ->where('actor_id', $this->usuario->id)->count());
        Mail::assertSent(RecargaConfirmada::class, fn ($m) => $m->hasTo($this->usuario->email));
    }

    public function test_retorno_con_owner_distinto_retorna_not_found_sin_info_leak(): void
    {
        $otroUsuario = Usuario::create([
            'uid' => 'OTHER-PAYPAL-RETURN-UID',
            'email' => 'otro-paypal-return@test.com',
            'nombre' => 'Otro Cliente',
            'roles' => json_encode(['cliente']),
        ]);
        $otroCliente = Cliente::create([
            'usuario_id' => $otroUsuario->id,
            'saldo_creditos' => 0,
        ]);

        $token = 'ORDER-OTHER-OWNER-001';
        IntencionPaypal::create([
            'cliente_id' => $otroCliente->id,
            'order_id' => $token,
            'monto_usd' => 10.00,
            'creditos_estimados' => 100,
            'moneda' => 'USD',
            'estado' => EstadoIntencionPaypal::Pendiente,
            'expira_en' => now()->addMinutes(15),
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Orden no encontrada');
        $response->assertSeeText('Volver a Inicio');
        $this->assertSame(EstadoIntencionPaypal::Pendiente, IntencionPaypal::where('order_id', $token)->first()->estado);
    }

    public function test_retorno_con_intencion_pendiente_y_guest_muestra_status_sin_capturar(): void
    {
        Mail::fake();

        $token = 'ORDER-PENDING-GUEST-001';

        $this->crearIntencion($token);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Pendiente');
        $this->assertSame(EstadoIntencionPaypal::Pendiente, IntencionPaypal::where('order_id', $token)->first()->estado);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        Mail::assertNothingSent();
    }

    public function test_retorno_con_token_inexistente_retorna_not_found(): void
    {
        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get('/dashboard/recargas/paypal/return?token=NO-EXISTE-12345');

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Orden no encontrada');
        $response->assertSeeText('Volver a Inicio');
    }

    public function test_retorno_sin_token_retorna_not_found(): void
    {
        $this->crearIntencion('ORDER-NO-TOKEN-001');

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get('/dashboard/recargas/paypal/return');

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Orden no encontrada');
    }

    public function test_retorno_con_intencion_cancelada_muestra_fallo_sin_reintentar(): void
    {
        Mail::fake();

        $token = 'ORDER-CANCELADA-001';
        $this->crearIntencion($token, EstadoIntencionPaypal::Cancelada);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Pago no completado');
        $this->assertSame(EstadoIntencionPaypal::Cancelada, IntencionPaypal::where('order_id', $token)->first()->estado);
        Mail::assertNothingSent();
    }

    public function test_retorno_con_intencion_expirada_muestra_fallo_sin_reintentar(): void
    {
        Mail::fake();

        $token = 'ORDER-EXPIRADA-001';
        $this->crearIntencion($token, EstadoIntencionPaypal::Expirada);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Pago no completado');
        $this->assertSame(EstadoIntencionPaypal::Expirada, IntencionPaypal::where('order_id', $token)->first()->estado);
        Mail::assertNothingSent();
    }

    public function test_retorno_doble_no_acredita_doble(): void
    {
        Mail::fake();

        $token = 'ORDER-DOUBLE-001';

        $this->crearIntencion($token);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($token) {
            $mock->shouldReceive('captureOrder')
                ->with($token, 10.0)
                ->once()
                ->andReturn($this->capturaCompletada($token, 'CAP-DOUBLE-001', '10.00'));
        });

        $primera = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");
        $segunda = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $primera->assertStatus(200);
        $primera->assertSeeText('Recarga acreditada');
        $segunda->assertStatus(200);
        $segunda->assertSeeText('Recarga acreditada');

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(1, Recarga::where('referencia_externa', $token)->count());
        Mail::assertSentCount(1);
    }

    public function test_retorno_con_status_no_completed_cancela_intencion_y_loggea(): void
    {
        Mail::fake();

        $token = 'ORDER-DECLINED-001';

        $this->crearIntencion($token);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($token) {
            $mock->shouldReceive('captureOrder')
                ->with($token, 10.0)
                ->once()
                ->andReturn(['id' => $token, 'status' => 'DECLINED']);
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Pago no completado');

        $this->assertSame(EstadoIntencionPaypal::Cancelada, IntencionPaypal::where('order_id', $token)->first()->estado);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(0, Recarga::count());
        $this->assertSame(1, LogActividad::where('accion', 'recarga.fallida')
            ->where('actor_id', $this->usuario->id)->count());
        Mail::assertNothingSent();
    }

    public function test_retorno_con_connection_exception_muestra_pendiente(): void
    {
        Mail::fake();

        $token = 'ORDER-TIMEOUT-001';

        $this->crearIntencion($token);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($token) {
            $mock->shouldReceive('captureOrder')
                ->with($token, 10.0)
                ->once()
                ->andThrow(new ConnectionException('timeout'));
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Pendiente');
        $this->assertSame(EstadoIntencionPaypal::Pendiente, IntencionPaypal::where('order_id', $token)->first()->estado);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        Mail::assertNothingSent();
    }

    public function test_retorno_completed_con_monto_distinto_cancela_sin_acreditar(): void
    {
        Mail::fake();

        $token = 'ORDER-AMOUNT-MISMATCH-001';

        $this->crearIntencion($token);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($token) {
            $mock->shouldReceive('captureOrder')
                ->with($token, 10.0)
                ->once()
                ->andReturn($this->capturaCompletada($token, 'CAP-MISMATCH-001', '9.99'));
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Pago no completado');

        $this->assertSame(EstadoIntencionPaypal::Cancelada, IntencionPaypal::where('order_id', $token)->first()->estado);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(0, Recarga::count());
        $this->assertSame(1, LogActividad::where('accion', 'recarga.fallida')
            ->where('actor_id', $this->usuario->id)->count());
        Mail::assertNothingSent();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function crearIntencion(string $orderId, EstadoIntencionPaypal $estado = EstadoIntencionPaypal::Pendiente): IntencionPaypal
    {
        return IntencionPaypal::create([
            'cliente_id' => $this->cliente->id,
            'order_id' => $orderId,
            'monto_usd' => 10.00,
            'creditos_estimados' => 100,
            'moneda' => 'USD',
            'estado' => $estado,
            'expira_en' => now()->addMinutes(15),
            'fecha' => now(),
        ]);
    }

    private function crearRecargaCompletada(string $orderId): void
    {
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => EstadoRecarga::Completada,
            'referencia_externa' => $orderId,
            'fecha' => now(),
        ]);
    }

    private function capturaCompletada(string $orderId, string $captureId, string $monto): array
    {
        return [
            'id' => $orderId,
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'reference_id' => 'default',
                'payments' => ['captures' => [[
                    'id' => $captureId,
                    'status' => 'COMPLETED',
                    'amount' => ['currency_code' => 'USD', 'value' => $monto],
                    'final_capture' => true,
                ]]],
            ]],
        ];
    }
}

class ConfigParametrosRecargaPaypalWeb
{
    public static function seed(): void
    {
        ConfigParametro::firstOrCreate(
            ['modulo' => 'financiero', 'clave' => 'tasaCambioUsdCreditos'],
            ['valor' => json_encode(10)],
        );
        ConfigParametro::firstOrCreate(
            ['modulo' => 'financiero', 'clave' => 'recargaMinimaUsd'],
            ['valor' => json_encode('5.00')],
        );
    }
}
