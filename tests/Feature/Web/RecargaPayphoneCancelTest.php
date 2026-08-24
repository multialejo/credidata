<?php

namespace Tests\Feature\Web;

use App\Enums\EstadoRecarga;
use App\Models\Cliente;
use App\Models\Recarga;
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

    public function test_cancel_con_ctid_muestra_vista_informativa_sin_escribir_db(): void
    {
        $ctid = 'bs-cancel-001';
        $countBefore = Recarga::count();

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/cancel?id=12345&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $response->assertSeeText($ctid);
        $this->assertSame($countBefore, Recarga::count());
    }

    public function test_cancel_sin_query_params_muestra_vista_sin_referencia(): void
    {
        $countBefore = Recarga::count();

        $response = $this->actingAs($this->usuario)
            ->get('/dashboard/recargas/payphone/cancel');

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $this->assertSame($countBefore, Recarga::count());
    }

    public function test_cancel_con_orden_pendiente_no_cambia_estado(): void
    {
        $ctid = 'bs-cancel-pend-001';
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'payphone',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $ctid,
            'fecha' => now(),
        ]);

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/payphone/cancel?id=12345&clientTransactionId={$ctid}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.payphone.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $this->assertSame(EstadoRecarga::Pendiente, Recarga::where('referencia_externa', $ctid)->first()->estado);
    }
}
