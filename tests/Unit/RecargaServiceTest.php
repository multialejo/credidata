<?php

namespace Tests\Unit;

use App\Enums\EstadoRecarga;
use App\Models\Cliente;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Usuario;
use App\Services\RecargaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RecargaServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecargaService $service;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = Usuario::create([
            'uid' => 'test-uid-1',
            'email' => 'cliente-rs@test.com',
            'nombre' => 'Cliente RecargaService',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $usuario->id,
            'saldo_creditos' => 100,
        ]);

        $this->service = new RecargaService;
    }

    public function test_creates_recarga_row_with_estado_completada(): void
    {
        $recarga = $this->service->procesar('REF-NEW-1', $this->cliente->usuario->uid, 50, 'paypal');

        $this->assertInstanceOf(Recarga::class, $recarga);
        $this->assertSame(EstadoRecarga::Completada, $recarga->estado);
        $this->assertSame('REF-NEW-1', $recarga->referencia_externa);
        $this->assertSame(50, (int) $recarga->creditos_obtenidos);
        $this->assertSame('paypal', $recarga->metodo);

        $this->assertDatabaseHas('recargas', [
            'referencia_externa' => 'REF-NEW-1',
            'estado' => 'completada',
            'cliente_id' => $this->cliente->id,
        ]);
    }

    public function test_idempotency_returns_existing_row_on_second_call(): void
    {
        $first = $this->service->procesar('REF-IDEMP-1', $this->cliente->usuario->uid, 30, 'paypal');
        $second = $this->service->procesar('REF-IDEMP-1', $this->cliente->usuario->uid, 30, 'paypal');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Recarga::where('referencia_externa', 'REF-IDEMP-1')->count());
    }

    public function test_credits_cliente_saldo_atomically(): void
    {
        $saldoInicial = (int) $this->cliente->saldo_creditos;

        $this->service->procesar('REF-SALDO-1', $this->cliente->usuario->uid, 25, 'paypal');

        $this->assertSame($saldoInicial + 25, (int) $this->cliente->fresh()->saldo_creditos);
    }

    public function test_inserts_log_actividad_with_detalle(): void
    {
        $this->service->procesar('REF-LOG-1', $this->cliente->usuario->uid, 10, 'payphone');

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'recarga.acreditada',
            'actor_id' => $this->cliente->usuario->id,
        ]);

        $log = LogActividad::where('accion', 'recarga.acreditada')
            ->where('actor_id', $this->cliente->usuario->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('payphone', $log->detalle['metodo']);
        $this->assertSame(10, $log->detalle['creditos']);
        $this->assertSame('REF-LOG-1', $log->detalle['referencia_externa']);
        $this->assertArrayHasKey('recarga_id', $log->detalle);
    }

    public function test_updates_pendiente_row_in_place_not_insert(): void
    {
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => 'REF-PEND-1',
            'fecha' => now(),
        ]);

        $this->assertSame(1, Recarga::where('referencia_externa', 'REF-PEND-1')->count());

        $recarga = $this->service->procesar('REF-PEND-1', $this->cliente->usuario->uid, 100, 'paypal');

        $this->assertSame(1, Recarga::where('referencia_externa', 'REF-PEND-1')->count());
        $this->assertSame(EstadoRecarga::Completada, $recarga->fresh()->estado);
        $this->assertSame(10.00, (float) $recarga->fresh()->monto_usd);
    }

    public function test_rolls_back_all_writes_when_record_log_throws(): void
    {
        $service = new FailingRecargaService;
        $saldoInicial = (int) $this->cliente->saldo_creditos;

        $threw = false;
        try {
            $service->procesar('REF-FAIL-1', $this->cliente->usuario->uid, 75, 'paypal');
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Se esperaba RuntimeException del log forzado');

        $this->assertDatabaseMissing('recargas', ['referencia_externa' => 'REF-FAIL-1']);
        $this->assertDatabaseMissing('logs_actividad', [
            'accion' => 'recarga.acreditada',
            'actor_id' => $this->cliente->usuario->id,
        ]);
        $this->assertSame($saldoInicial, (int) $this->cliente->fresh()->saldo_creditos);
    }

    public function test_saldo_invariant_holds_after_multiple_recargas(): void
    {
        $saldoInicial = (int) $this->cliente->saldo_creditos;

        $this->service->procesar('REF-MULT-1', $this->cliente->usuario->uid, 10, 'paypal');
        $this->service->procesar('REF-MULT-2', $this->cliente->usuario->uid, 20, 'payphone');
        $this->service->procesar('REF-MULT-3', $this->cliente->usuario->uid, 30, 'paypal');

        $this->cliente->refresh();
        $this->assertSame($saldoInicial + 60, (int) $this->cliente->saldo_creditos);

        $logsCount = LogActividad::where('accion', 'recarga.acreditada')
            ->where('actor_id', $this->cliente->usuario->id)
            ->count();
        $this->assertSame(3, $logsCount);
    }

    public function test_returns_existing_completada_without_opening_transaction(): void
    {
        $this->service->procesar('REF-EXISTS-1', $this->cliente->usuario->uid, 5, 'paypal');
        $logCountBefore = LogActividad::where('accion', 'recarga.acreditada')->count();

        $this->service->procesar('REF-EXISTS-1', $this->cliente->usuario->uid, 5, 'paypal');
        $logCountAfter = LogActividad::where('accion', 'recarga.acreditada')->count();

        $this->assertSame($logCountBefore, $logCountAfter);
    }
}

class FailingRecargaService extends RecargaService
{
    protected function recordLog(array $data): LogActividad
    {
        throw new RuntimeException('Forced log failure for atomicity test');
    }
}
