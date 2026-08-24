<?php

namespace Tests\Feature\Web;

use App\Enums\EstadoRecarga;
use App\Models\Cliente;
use App\Models\Recarga;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecargaPaypalCancelTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::create([
            'uid' => 'cancel-test-uid',
            'email' => 'cancel@test.com',
            'nombre' => 'Cliente Cancel',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_cancel_con_token_muestra_vista_informativa_sin_escribir_db(): void
    {
        $token = 'MOCK-ORDER-CANCEL-1';
        $countBefore = Recarga::count();

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/cancel?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $this->assertSame($countBefore, Recarga::count());
    }

    public function test_cancel_sin_token_muestra_vista_sin_escribir_db(): void
    {
        $countBefore = Recarga::count();

        $response = $this->actingAs($this->usuario)
            ->get('/dashboard/recargas/paypal/cancel');

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $this->assertSame($countBefore, Recarga::count());
    }

    public function test_cancel_con_orden_pendiente_no_cambia_estado(): void
    {
        $token = 'MOCK-ORDER-CANCEL-PEND';
        Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => 'pendiente',
            'referencia_externa' => $token,
            'fecha' => now(),
        ]);

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/cancel?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $this->assertSame(EstadoRecarga::Pendiente, Recarga::where('referencia_externa', $token)->first()->estado);
    }
}
