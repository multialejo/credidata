<?php

namespace Tests\Feature\Web;

use App\Enums\EstadoIntencionPaypal;
use App\Models\Cliente;
use App\Models\IntencionPaypal;
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
            'uid' => 'paypal-cancel-uid',
            'email' => 'paypal-cancel@test.com',
            'nombre' => 'Cliente PayPal Cancel',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_cancel_con_intencion_pendiente_la_cancela_y_muestra_vista(): void
    {
        $token = 'ORDER-CANCEL-001';
        $this->crearIntencionPendiente($token);

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/cancel?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $response->assertSeeText($token);

        $this->assertSame(EstadoIntencionPaypal::Cancelada, IntencionPaypal::where('order_id', $token)->first()->estado);
        $this->assertDatabaseMissing('recargas', ['referencia_externa' => $token]);
    }

    public function test_cancel_con_token_inexistente_muestra_vista_sin_escribir_db(): void
    {
        $countBefore = IntencionPaypal::count();

        $response = $this->actingAs($this->usuario)
            ->get('/dashboard/recargas/paypal/cancel?token=NO-EXISTE');

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $this->assertSame($countBefore, IntencionPaypal::count());
    }

    public function test_cancel_sin_token_muestra_vista_sin_escribir_db(): void
    {
        $countBefore = IntencionPaypal::count();

        $response = $this->actingAs($this->usuario)
            ->get('/dashboard/recargas/paypal/cancel');

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.cancel');
        $response->assertSeeText('Has cancelado el pago');
        $this->assertSame($countBefore, IntencionPaypal::count());
    }

    public function test_cancel_con_intencion_terminal_no_cambia_estado(): void
    {
        $token = 'ORDER-CANCEL-CONFIRMADA-001';
        $this->crearIntencionPendiente($token, EstadoIntencionPaypal::Confirmada);

        $response = $this->actingAs($this->usuario)
            ->get("/dashboard/recargas/paypal/cancel?token={$token}");

        $response->assertStatus(200);
        $response->assertViewIs('recargas.paypal.cancel');
        $response->assertSeeText('Has cancelado el pago');

        $this->assertSame(EstadoIntencionPaypal::Confirmada, IntencionPaypal::where('order_id', $token)->first()->estado);
    }

    private function crearIntencionPendiente(string $orderId, EstadoIntencionPaypal $estado = EstadoIntencionPaypal::Pendiente): IntencionPaypal
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
}
