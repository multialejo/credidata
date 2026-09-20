<?php

namespace Tests\Feature\Web;

use App\Enums\EstadoIntencionPayphone;
use App\Models\Cliente;
use App\Models\IntencionPayphone;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecargaPayphoneCancelTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::create([
            'uid' => 'payphone-cancel-uid',
            'email' => 'payphone-cancel@test.com',
            'nombre' => 'Cliente Payphone Cancel',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_cancel_con_intencion_pendiente_la_cancela_y_muestra_vista(): void
    {
        $ctid = 'bs-cancel-001';
        $this->crearIntencionPendiente($ctid);

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/cancel?id=12345&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $response->assertSeeText($ctid);

        $this->assertSame(EstadoIntencionPayphone::Cancelada, IntencionPayphone::where('ctid', $ctid)->first()->estado);
        $this->assertDatabaseMissing('recargas', ['referencia_externa' => $ctid]);
    }

    public function test_cancel_con_ctid_inexistente_muestra_vista_sin_escribir_db(): void
    {
        $countBefore = IntencionPayphone::count();

        $response = $this->actingAs($this->usuario)
            ->get('/dashboard/recargas/payphone/cancel?id=12345&clientTransactionId=NO-EXISTE');

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $this->assertSame($countBefore, IntencionPayphone::count());
    }

    public function test_cancel_sin_query_params_muestra_vista_sin_referencia(): void
    {
        $countBefore = IntencionPayphone::count();

        $response = $this->actingAs($this->usuario)
            ->get('/dashboard/recargas/payphone/cancel');

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $this->assertSame($countBefore, IntencionPayphone::count());
    }

    public function test_cancel_con_intencion_terminal_no_cambia_estado(): void
    {
        $ctid = 'bs-cancel-conf-001';
        $intencion = $this->crearIntencionPendiente($ctid);
        $intencion->update(['estado' => EstadoIntencionPayphone::Confirmada]);

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/cancel?id=12345&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.cancel');
        $response->assertSeeText('Has cancelado el pago');

        $this->assertSame(EstadoIntencionPayphone::Confirmada, IntencionPayphone::where('ctid', $ctid)->first()->estado);
    }

    private function crearIntencionPendiente(string $ctid): IntencionPayphone
    {
        return IntencionPayphone::create([
            'cliente_id' => $this->cliente->id,
            'ctid' => $ctid,
            'payment_id' => '12345',
            'monto_usd' => 10.00,
            'creditos_estimados' => 100,
            'moneda' => 'USD',
            'estado' => EstadoIntencionPayphone::Pendiente,
            'expira_en' => now()->addMinutes(15),
            'fecha' => now(),
        ]);
    }
}
