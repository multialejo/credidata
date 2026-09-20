<?php

namespace Tests\Feature\Jobs;

use App\Enums\EstadoIntencionPayphone;
use App\Jobs\ExpirarIntencionesPayphoneJob;
use App\Models\Cliente;
use App\Models\IntencionPayphone;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpirarIntencionesPayphoneJobTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $usuario = Usuario::create([
            'uid' => 'expira-intencion-uid',
            'email' => 'expira-intencion@test.com',
            'nombre' => 'Cliente Expiración',
            'roles' => json_encode(['cliente']),
        ]);

        $this->cliente = Cliente::create([
            'usuario_id' => $usuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    public function test_expira_intenciones_pendientes_vencidas(): void
    {
        $vencida = $this->crearIntencion('bs-vencida-001', now()->subMinutes(5));
        $vigente = $this->crearIntencion('bs-vigente-001', now()->addMinutes(10));

        $this->crearIntencion('bs-configurada-001', now()->subMinutes(5), EstadoIntencionPayphone::Cancelada);

        app(ExpirarIntencionesPayphoneJob::class)->handle();

        $this->assertSame(EstadoIntencionPayphone::Expirada, $vencida->fresh()->estado);
        $this->assertSame(EstadoIntencionPayphone::Pendiente, $vigente->fresh()->estado);
        $this->assertSame(EstadoIntencionPayphone::Cancelada, IntencionPayphone::where('ctid', 'bs-configurada-001')->first()->estado);
    }

    public function test_expira_intenciones_sin_expira_en(): void
    {
        $sinVencer = $this->crearIntencion('bs-sin-ttl-001', null);

        app(ExpirarIntencionesPayphoneJob::class)->handle();

        $this->assertSame(EstadoIntencionPayphone::Pendiente, $sinVencer->fresh()->estado);
    }

    private function crearIntencion(string $ctid, ?Carbon $expiraEn, EstadoIntencionPayphone $estado = EstadoIntencionPayphone::Pendiente): IntencionPayphone
    {
        return IntencionPayphone::create([
            'cliente_id' => $this->cliente->id,
            'ctid' => $ctid,
            'payment_id' => '12345',
            'monto_usd' => 10.00,
            'creditos_estimados' => 100,
            'moneda' => 'USD',
            'estado' => $estado,
            'expira_en' => $expiraEn,
            'fecha' => now(),
        ]);
    }
}
