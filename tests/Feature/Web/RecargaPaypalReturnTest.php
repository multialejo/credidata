<?php

namespace Tests\Feature\Web;

use App\Mail\RecargaConfirmada;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Usuario;
use App\Services\RecargaPaypalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ConfigParametrosRecargaWeb::seed();

        $this->usuario = Usuario::create([
            'uid' => 'return-test-uid',
            'email' => 'return@test.com',
            'nombre' => 'Cliente Return',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    // --- REQ-01 ---

    public function test_retorno_con_orden_completada_muestra_exito_sin_recapturar(): void
    {
        $token = 'MOCK-ORDER-123';
        $saldoInicial = 100;

        $this->cliente->update(['saldo_creditos' => $saldoInicial]);

        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'completada',
            'referencia_externa' => $token,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Recarga acreditada');
        $response->assertSeeText('100');
        $response->assertSeeText('Volver al Panel de Saldo');
        $response->assertSee(route('dashboard'));
        $this->assertSame($saldoInicial, (int) $this->cliente->fresh()->saldo_creditos);
    }

    public function test_retorno_con_orden_completada_y_guest_muestra_exito_sin_recapturar(): void
    {
        $token = 'MOCK-ORDER-COMPLETED-GUEST';
        $saldoInicial = 100;

        $this->cliente->update(['saldo_creditos' => $saldoInicial]);

        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'completada',
            'referencia_externa' => $token,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        // Guest (no actingAs) hitting the return URL for an already-completed order.
        $response = $this->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Recarga acreditada');
        $response->assertSeeText('100');
        $response->assertSeeText('Volver al Panel de Saldo');
        $response->assertSee(route('dashboard'));
        $this->assertSame('completada', $this->cliente->fresh()->recargas()->where('referencia_externa', $token)->first()->estado);
        $this->assertSame($saldoInicial, (int) $this->cliente->fresh()->saldo_creditos);
    }

    // --- REQ-02 ---

    public function test_retorno_con_orden_pendiente_y_owner_captura_y_muestra_exito(): void
    {
        Mail::fake();

        $token = 'MOCK-ORDER-CAPTURE-1';
        $saldoInicial = 0;

        $this->cliente->update(['saldo_creditos' => $saldoInicial]);

        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $token,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($token) {
            $mock->shouldReceive('captureOrder')
                ->with($token)
                ->once()
                ->andReturn([
                    'id' => $token,
                    'status' => 'COMPLETED',
                ]);
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Recarga acreditada');
        $response->assertSeeText('Volver al Panel de Saldo');
        $response->assertSee(route('dashboard'));

        $this->assertSame('completada', $this->cliente->fresh()->recargas()->where('referencia_externa', $token)->first()->estado);
        $this->assertSame($saldoInicial + 100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(1, LogActividad::where('accion', 'recarga.acreditada')
            ->where('actor_id', $this->usuario->id)->count());
        Mail::assertSent(RecargaConfirmada::class, fn ($m) => $m->hasTo($this->usuario->email));
    }

    // --- REQ-04 ---

    public function test_retorno_con_owner_distinto_retorna_not_found_sin_info_leak(): void
    {
        $otroUsuario = Usuario::create([
            'uid' => 'OTHER-USER-UID-RETURN',
            'email' => 'otro-return@test.com',
            'nombre' => 'Otro Cliente Return',
            'roles' => json_encode(['cliente']),
        ]);
        $otroCliente = Cliente::create([
            'usuario_id' => $otroUsuario->id,
            'saldo_creditos' => 0,
        ]);

        $token = 'MOCK-ORDER-OTHER-OWNER';
        Recarga::create([
            'cliente_id' => $otroCliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $token,
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
        $response->assertSeeText('Volver al Panel de Saldo');
        $response->assertSee(route('dashboard'));
        $this->assertSame('pendiente', $otroCliente->fresh()->recargas()->where('referencia_externa', $token)->first()->estado);
    }

    // --- REQ-03 ---

    public function test_retorno_con_orden_pendiente_y_guest_muestra_status_sin_capturar(): void
    {
        Mail::fake();

        $token = 'MOCK-ORDER-GUEST';

        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $token,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Pendiente');
        $this->assertSame('pendiente', $this->cliente->fresh()->recargas()->where('referencia_externa', $token)->first()->estado);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        Mail::assertNothingSent();
    }

    // --- REQ-06 ---

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
        $response->assertSeeText('Volver al Panel de Saldo');
        $response->assertSee(route('dashboard'));
    }

    // --- REQ-05 ---

    public function test_retorno_con_orden_fallida_muestra_fallo_sin_reintentar(): void
    {
        Mail::fake();

        $token = 'MOCK-ORDER-FALLIDA';

        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'fallida',
            'referencia_externa' => $token,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) {
            $mock->shouldReceive('captureOrder')->never();
        });

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.return');
        $response->assertSeeText('Pago no completado');
        $response->assertSeeText('Volver al Panel de Saldo');
        $response->assertSee(route('dashboard'));
        $this->assertSame('fallida', $this->cliente->fresh()->recargas()->where('referencia_externa', $token)->first()->estado);
        Mail::assertNothingSent();
    }

    // --- REQ-02 (idempotency) ---

    public function test_retorno_doble_no_acredita_doble(): void
    {
        Mail::fake();

        $token = 'MOCK-ORDER-DOUBLE-RETURN';

        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $token,
            'fecha' => now(),
        ]);

        $this->partialMock(RecargaPaypalService::class, function ($mock) use ($token) {
            $mock->shouldReceive('captureOrder')
                ->with($token)
                ->once()
                ->andReturn(['id' => $token, 'status' => 'COMPLETED']);
        });

        $primera = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");
        $segunda = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/return?token={$token}");

        $primera->assertStatus(200);
        $primera->assertSeeText('Recarga acreditada');
        $primera->assertSeeText('Volver al Panel de Saldo');
        $primera->assertSee(route('dashboard'));
        $segunda->assertStatus(200);
        $segunda->assertSeeText('Recarga acreditada');
        $segunda->assertSeeText('Volver al Panel de Saldo');
        $segunda->assertSee(route('dashboard'));

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        Mail::assertSentCount(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

class ConfigParametrosRecargaWeb
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
