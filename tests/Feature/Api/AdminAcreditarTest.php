<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoRecarga;
use App\Jobs\SendRecargaEmail;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Recarga;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAcreditarTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Usuario $support;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::create(['uid' => 'admin-uid', 'email' => 'admin@test.com', 'nombre' => 'Admin', 'roles' => ['staff']]);
        Staff::create(['usuario_id' => $this->admin->id, 'rol_staff' => 'admin', 'fecha_asignacion' => now()]);
        $this->support = Usuario::create(['uid' => 'support-uid', 'email' => 'support@test.com', 'nombre' => 'Support', 'roles' => ['staff']]);
        Staff::create(['usuario_id' => $this->support->id, 'rol_staff' => 'support', 'fecha_asignacion' => now()]);
        $usuario = Usuario::create(['uid' => 'cliente-uid', 'email' => 'cliente@test.com', 'nombre' => 'Cliente', 'roles' => ['cliente']]);
        $this->cliente = Cliente::create(['usuario_id' => $usuario->id, 'saldo_creditos' => 0]);
        ConfigParametro::create(['modulo' => 'financiero', 'clave' => 'tasaCambioUsdCreditos', 'valor' => json_encode(10)]);
        Storage::fake('local');
    }

    private function crearTransferenciaPendiente(string $referencia = 'BANK-001'): Recarga
    {
        $path = UploadedFile::fake()->create('deposito.pdf', 100, 'application/pdf')->store('recargas/comprobantes', 'local');

        return Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'transferencia',
            'monto_usd' => 10,
            'creditos_obtenidos' => 100,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => $referencia,
            'comprobante_url' => $path,
        ]);
    }

    public function test_admin_approves_pending_transfer_and_credits_once(): void
    {
        Queue::fake();
        $recarga = $this->crearTransferenciaPendiente();

        $response = $this->actingAs($this->admin, 'sanctum')->post('/api/v1/admin/recargas/acreditar', ['recarga_id' => $recarga->id]);

        $response->assertOk()->assertJsonPath('datos.recarga.estado', 'completada');
        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame('transferencia', $recarga->fresh()->metodo);
        Storage::disk('local')->assertExists($recarga->comprobante_url);
        $this->assertDatabaseHas('logs_actividad', ['accion' => 'recarga.acreditada_manual', 'actor_id' => $this->admin->id]);
        Queue::assertPushed(SendRecargaEmail::class);

        $this->actingAs($this->admin, 'sanctum')->post('/api/v1/admin/recargas/acreditar', ['recarga_id' => $recarga->id])
            ->assertStatus(409);
        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
    }

    public function test_support_can_approve_pending_transfer(): void
    {
        Queue::fake();
        $recarga = $this->crearTransferenciaPendiente();

        $this->actingAs($this->support, 'sanctum')
            ->post('/api/v1/admin/recargas/acreditar', ['recarga_id' => $recarga->id])
            ->assertOk();

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('logs_actividad', ['accion' => 'recarga.acreditada_manual', 'actor_id' => $this->support->id]);
    }

    public function test_api_requires_a_valid_pending_transfer_id(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->post('/api/v1/admin/recargas/acreditar', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['recarga_id']);

        $recarga = Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'estado' => EstadoRecarga::Completada,
            'referencia_externa' => 'PAYPAL-001',
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->post('/api/v1/admin/recargas/acreditar', ['recarga_id' => $recarga->id])
            ->assertStatus(409);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
    }

    public function test_api_does_not_accredit_pending_transfer_with_non_exact_credit_amount(): void
    {
        Queue::fake();
        $recarga = $this->crearTransferenciaPendiente('BANK-NON-EXACT');
        $recarga->update(['monto_usd' => 10.01]);

        $this->actingAs($this->admin, 'sanctum')
            ->post('/api/v1/admin/recargas/acreditar', ['recarga_id' => $recarga->id])
            ->assertStatus(422)
            ->assertJsonPath('error.tipo', 'MONTO_INVALIDO');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(EstadoRecarga::Pendiente, $recarga->fresh()->estado);
        Queue::assertNothingPushed();
    }
}
