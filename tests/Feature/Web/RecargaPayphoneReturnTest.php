<?php

namespace Tests\Feature\Web;

use App\Enums\EstadoIntencionPayphone;
use App\Enums\EstadoRecarga;
use App\Mail\RecargaConfirmada;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\IntencionPayphone;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Usuario;
use App\Services\RecargaPayphoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class RecargaPayphoneReturnTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payphone.mock' => true]);
        ConfigParametrosRecargaPayphoneWeb::seed();

        $this->usuario = Usuario::create([
            'uid' => 'payphone-return-uid',
            'email' => 'payphone-return@test.com',
            'nombre' => 'Cliente Payphone Return',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_retorno_con_recarga_completada_muestra_exito_sin_reconfirmar(): void
    {
        $ctid = 'bs-completed-001';
        $paymentId = '12345';
        $saldoInicial = 100;

        $this->cliente->update(['saldo_creditos' => $saldoInicial]);

        $this->crearIntencion($ctid, $paymentId);
        $this->crearRecargaCompletada($ctid);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('confirm')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id={$paymentId}&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Recarga acreditada');
        $response->assertSeeText('100');
        $response->assertSeeText('Volver al Panel de Saldo');
        $response->assertSee(route('dashboard'));
        $this->assertSame($saldoInicial, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(EstadoIntencionPayphone::Pendiente, IntencionPayphone::where('ctid', $ctid)->first()->estado);
    }

    public function test_retorno_con_recarga_completada_y_guest_muestra_exito_sin_reconfirmar(): void
    {
        $ctid = 'bs-completed-guest-001';
        $paymentId = '12345';
        $saldoInicial = 100;

        $this->cliente->update(['saldo_creditos' => $saldoInicial]);

        $this->crearIntencion($ctid, $paymentId);
        $this->crearRecargaCompletada($ctid);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('confirm')->never();
        });

        $response = $this->get("/dashboard/recargas/payphone/return?id={$paymentId}&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Recarga acreditada');
        $response->assertSeeText('100');
        $response->assertSeeText('Volver al Panel de Saldo');
        $this->assertSame($saldoInicial, (int) $this->cliente->fresh()->saldo_creditos);
    }

    public function test_retorno_con_intencion_pendiente_y_owner_confirma_y_acredita(): void
    {
        Mail::fake();

        $ctid = 'bs-pending-001';
        $paymentId = '99999';
        $saldoInicial = 0;

        $this->cliente->update(['saldo_creditos' => $saldoInicial]);

        $this->crearIntencion($ctid, $paymentId);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) use ($paymentId, $ctid) {
            $mock->shouldReceive('confirm')
                ->with($paymentId, $ctid, 10.0)
                ->once()
                ->andReturn([
                    'transactionStatus' => 'Approved',
                    'transactionId' => '88888',
                    'authorizationCode' => 'AUTH-001',
                    'message' => null,
                    'amountPaidUsd' => 10.0,
                ]);
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id={$paymentId}&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Recarga acreditada');
        $response->assertSeeText('Volver al Panel de Saldo');

        $intencion = IntencionPayphone::where('ctid', $ctid)->first();
        $this->assertSame(EstadoIntencionPayphone::Confirmada, $intencion->estado);
        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => $ctid,
            'cliente_id' => $this->cliente->id,
            'metodo' => 'payphone',
            'estado' => 'completada',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'provider_payment_id' => '99999',
            'provider_transaction_id' => '88888',
        ]);
        $this->assertSame(EstadoRecarga::Completada, $this->cliente->fresh()->recargas()->where('referencia_externa', $ctid)->first()->estado);
        $this->assertSame($saldoInicial + 100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(1, LogActividad::where('accion', 'recarga.acreditada')
            ->where('actor_id', $this->usuario->id)->count());
        Mail::assertSent(RecargaConfirmada::class, fn ($m) => $m->hasTo($this->usuario->email));
    }

    public function test_retorno_con_owner_distinto_retorna_not_found_sin_info_leak(): void
    {
        $otroUsuario = Usuario::create([
            'uid' => 'OTHER-PAYPHONE-UID',
            'email' => 'otro-payphone@test.com',
            'nombre' => 'Otro Cliente',
            'roles' => json_encode(['cliente']),
        ]);
        $otroCliente = Cliente::create([
            'usuario_id' => $otroUsuario->id,
            'saldo_creditos' => 0,
        ]);

        $ctid = 'bs-other-owner-001';
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

        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('confirm')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id=12345&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Orden no encontrada');
        $response->assertSeeText('Volver al Panel de Saldo');
        $this->assertSame(EstadoIntencionPayphone::Pendiente, IntencionPayphone::where('ctid', $ctid)->first()->estado);
    }

    public function test_retorno_con_intencion_pendiente_y_guest_muestra_status_sin_confirmar(): void
    {
        Mail::fake();

        $ctid = 'bs-pending-guest-001';

        $this->crearIntencion($ctid);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('confirm')->never();
        });

        $response = $this->get("/dashboard/recargas/payphone/return?id=12345&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Pendiente');
        $this->assertSame(EstadoIntencionPayphone::Pendiente, IntencionPayphone::where('ctid', $ctid)->first()->estado);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        Mail::assertNothingSent();
    }

    public function test_retorno_con_ctid_inexistente_retorna_not_found(): void
    {
        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('confirm')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get('/dashboard/recargas/payphone/return?id=12345&clientTransactionId=NO-EXISTE-12345');

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Orden no encontrada');
        $response->assertSeeText('Volver al Panel de Saldo');
    }

    public function test_retorno_sin_payment_id_retorna_not_found(): void
    {
        $ctid = 'bs-no-payment-001';
        $this->crearIntencion($ctid);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('confirm')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Orden no encontrada');
    }

    public function test_retorno_con_intencion_cancelada_muestra_fallo_sin_reintentar(): void
    {
        Mail::fake();

        $ctid = 'bs-cancelada-001';
        $intencion = $this->crearIntencion($ctid);
        $intencion->update(['estado' => EstadoIntencionPayphone::Cancelada]);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('confirm')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id=12345&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Pago no completado');
        $this->assertSame(EstadoIntencionPayphone::Cancelada, IntencionPayphone::where('ctid', $ctid)->first()->estado);
        Mail::assertNothingSent();
    }

    public function test_retorno_con_intencion_expirada_muestra_fallo_sin_reintentar(): void
    {
        Mail::fake();

        $ctid = 'bs-expirada-001';
        $intencion = $this->crearIntencion($ctid);
        $intencion->update(['estado' => EstadoIntencionPayphone::Expirada]);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) {
            $mock->shouldReceive('confirm')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id=12345&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Pago no completado');
        $this->assertSame(EstadoIntencionPayphone::Expirada, IntencionPayphone::where('ctid', $ctid)->first()->estado);
        Mail::assertNothingSent();
    }

    public function test_retorno_doble_no_acredita_doble(): void
    {
        Mail::fake();

        $ctid = 'bs-double-return-001';
        $paymentId = '77777';

        $this->crearIntencion($ctid, $paymentId);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) use ($paymentId, $ctid) {
            $mock->shouldReceive('confirm')
                ->with($paymentId, $ctid, 10.0)
                ->once()
                ->andReturn([
                    'transactionStatus' => 'Approved',
                    'transactionId' => '88888',
                    'authorizationCode' => 'AUTH-002',
                    'message' => null,
                    'amountPaidUsd' => 10.0,
                ]);
        });

        $primera = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id={$paymentId}&clientTransactionId={$ctid}");
        $segunda = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id={$paymentId}&clientTransactionId={$ctid}");

        $primera->assertStatus(200);
        $primera->assertSeeText('Recarga acreditada');
        $segunda->assertStatus(200);
        $segunda->assertSeeText('Recarga acreditada');

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(1, Recarga::where('referencia_externa', $ctid)->count());
        Mail::assertSentCount(1);
    }

    public function test_retorno_con_status_canceled_cancela_intencion_y_loggea(): void
    {
        Mail::fake();

        $ctid = 'bs-canceled-001';
        $paymentId = '55555';

        $this->crearIntencion($ctid, $paymentId);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) use ($paymentId, $ctid) {
            $mock->shouldReceive('confirm')
                ->with($paymentId, $ctid, 10.0)
                ->once()
                ->andReturn([
                    'transactionStatus' => 'Canceled',
                    'transactionId' => null,
                    'authorizationCode' => null,
                    'message' => 'Pago cancelado por el usuario',
                ]);
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id={$paymentId}&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Pago no completado');

        $this->assertSame(EstadoIntencionPayphone::Cancelada, IntencionPayphone::where('ctid', $ctid)->first()->estado);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(0, Recarga::count());
        $this->assertSame(1, LogActividad::where('accion', 'recarga.fallida')
            ->where('actor_id', $this->usuario->id)->count());
        Mail::assertNothingSent();
    }

    public function test_retorno_con_status_unknown_cancela_intencion_sin_acreditar(): void
    {
        Mail::fake();

        $ctid = 'bs-unknown-001';
        $paymentId = '44444';

        $this->crearIntencion($ctid, $paymentId);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) use ($paymentId, $ctid) {
            $mock->shouldReceive('confirm')
                ->with($paymentId, $ctid, 10.0)
                ->once()
                ->andReturn([
                    'transactionStatus' => 'Unknown',
                    'transactionId' => null,
                    'authorizationCode' => null,
                    'message' => null,
                ]);
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id={$paymentId}&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Pago no completado');

        $this->assertSame(EstadoIntencionPayphone::Cancelada, IntencionPayphone::where('ctid', $ctid)->first()->estado);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        Mail::assertNothingSent();
    }

    public function test_retorno_con_connection_exception_en_confirm_muestra_pendiente(): void
    {
        Mail::fake();

        $ctid = 'bs-timeout-001';
        $paymentId = '33333';

        $this->crearIntencion($ctid, $paymentId);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) use ($paymentId, $ctid) {
            $mock->shouldReceive('confirm')
                ->with($paymentId, $ctid, 10.0)
                ->once()
                ->andThrow(new ConnectionException('timeout'));
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id={$paymentId}&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Pendiente');
        $this->assertSame(EstadoIntencionPayphone::Pendiente, IntencionPayphone::where('ctid', $ctid)->first()->estado);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        Mail::assertNothingSent();
    }

    public function test_retorno_con_approved_pero_monto_distinto_cancela_sin_acreditar(): void
    {
        Mail::fake();

        $ctid = 'bs-monto-invalido-001';
        $paymentId = '66666';

        $this->crearIntencion($ctid, $paymentId);

        $this->partialMock(RecargaPayphoneService::class, function ($mock) use ($paymentId, $ctid) {
            $mock->shouldReceive('confirm')
                ->with($paymentId, $ctid, 10.0)
                ->once()
                ->andReturn([
                    'transactionStatus' => 'Approved',
                    'transactionId' => '88888',
                    'authorizationCode' => 'AUTH-003',
                    'message' => null,
                    'amountPaidUsd' => 9.0,
                ]);
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/return?id={$paymentId}&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.return');
        $response->assertSeeText('Pago no completado');

        $this->assertSame(EstadoIntencionPayphone::Cancelada, IntencionPayphone::where('ctid', $ctid)->first()->estado);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(0, Recarga::count());
        $this->assertSame(1, LogActividad::where('accion', 'recarga.fallida')
            ->where('actor_id', $this->usuario->id)->count());
        Mail::assertNothingSent();
    }

    private function crearIntencion(string $ctid, string $paymentId = '12345', float $monto = 10.00, int $creditos = 100): IntencionPayphone
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

    private function crearRecargaCompletada(string $ctid): void
    {
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'payphone',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => EstadoRecarga::Completada,
            'referencia_externa' => $ctid,
            'fecha' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

class ConfigParametrosRecargaPayphoneWeb
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
