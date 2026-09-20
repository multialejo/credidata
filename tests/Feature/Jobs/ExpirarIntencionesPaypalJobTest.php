<?php

namespace Tests\Feature\Jobs;

use App\Enums\EstadoIntencionPaypal;
use App\Jobs\ExpirarIntencionesPaypalJob;
use App\Models\Cliente;
use App\Models\IntencionPaypal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpirarIntencionesPaypalJobTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = Usuario::create([
            'uid' => 'expira-intencion-paypal-uid',
            'email' => 'expira-intencion-paypal@test.com',
            'nombre' => 'Cliente Expiración PayPal',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_expira_intenciones_pendientes_vencidas(): void
    {
        $vencida = $this->crearIntencion('ORDER-VENCIDA-001', now()->subMinutes(5));
        $vigente = $this->crearIntencion('ORDER-VIGENTE-001', now()->addMinutes(10));

        $this->crearIntencion('ORDER-CANCELADA-001', now()->subMinutes(5), EstadoIntencionPaypal::Cancelada);

        app(ExpirarIntencionesPaypalJob::class)->handle();

        $this->assertSame(EstadoIntencionPaypal::Expirada, $vencida->fresh()->estado);
        $this->assertSame(EstadoIntencionPaypal::Pendiente, $vigente->fresh()->estado);
        $this->assertSame(EstadoIntencionPaypal::Cancelada, IntencionPaypal::where('order_id', 'ORDER-CANCELADA-001')->first()->estado);
    }

    public function test_expira_intenciones_sin_expira_en(): void
    {
        $sinVencer = $this->crearIntencion('ORDER-SIN-TTL-001', null);

        app(ExpirarIntencionesPaypalJob::class)->handle();

        $this->assertSame(EstadoIntencionPaypal::Pendiente, $sinVencer->fresh()->estado);
    }

    private function crearIntencion(string $orderId, ?Carbon $expiraEn, EstadoIntencionPaypal $estado = EstadoIntencionPaypal::Pendiente): IntencionPaypal
    {
        return IntencionPaypal::create([
            'cliente_id' => $this->cliente->id,
            'order_id' => $orderId,
            'monto_usd' => 10.00,
            'creditos_estimados' => 100,
            'moneda' => 'USD',
            'estado' => $estado,
            'expira_en' => $expiraEn,
            'fecha' => now(),
        ]);
    }
}
