<?php

namespace Tests\Feature\Api;

use App\Jobs\SendRecargaEmail;
use App\Models\Cliente;
use App\Models\LogActividad;
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
    }

    private function payload(string $reference = 'BANK-001'): array
    {
        return [
            'cliente_email' => 'cliente@test.com',
            'monto_usd' => 10,
            'referencia_bancaria' => $reference,
            'motivo' => 'Depósito confirmado por WhatsApp',
            'comprobante' => UploadedFile::fake()->create('deposito.pdf', 100, 'application/pdf'),
        ];
    }

    public function test_admin_acredita_transferencia_con_evidencia_y_auditoria(): void
    {
        Storage::fake('local');
        Queue::fake();

        $response = $this->actingAs($this->admin, 'sanctum')->post('/api/v1/admin/recargas/acreditar', $this->payload());

        $response->assertOk()->assertJsonPath('datos.recarga.estado', 'completada');
        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $recarga = Recarga::firstOrFail();
        $this->assertSame('transferencia', $recarga->metodo);
        Storage::disk('local')->assertExists($recarga->comprobante_url);
        $this->assertDatabaseHas('logs_actividad', ['accion' => 'recarga.acreditada_manual', 'actor_id' => $this->admin->id]);
        Queue::assertPushed(SendRecargaEmail::class);
    }

    public function test_repeated_bank_reference_does_not_credit_twice(): void
    {
        Storage::fake('local');
        $first = $this->actingAs($this->admin, 'sanctum')->post('/api/v1/admin/recargas/acreditar', $this->payload());
        $first->assertOk();
        $second = $this->actingAs($this->admin, 'sanctum')->post('/api/v1/admin/recargas/acreditar', $this->payload());
        $second->assertOk();
        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(1, LogActividad::where('accion', 'recarga.acreditada_manual')->count());
    }

    public function test_requires_client_and_evidence(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->post('/api/v1/admin/recargas/acreditar', [
            'cliente_email' => 'missing@test.com', 'creditos' => 1, 'referencia_bancaria' => 'BANK-002',
            'motivo' => 'Motivo suficientemente descriptivo',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['comprobante']);

        $response = $this->actingAs($this->admin, 'sanctum')->post('/api/v1/admin/recargas/acreditar', [
            ...$this->payload('BANK-003'), 'cliente_email' => 'missing@test.com',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['cliente_email']);
    }

    public function test_support_can_accredit_direct_transfer(): void
    {
        Storage::fake('local');
        Queue::fake();

        $response = $this->actingAs($this->support, 'sanctum')->post('/api/v1/admin/recargas/acreditar', $this->payload());

        $response->assertOk()->assertJsonPath('datos.recarga.estado', 'completada');
        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('logs_actividad', ['accion' => 'recarga.acreditada_manual', 'actor_id' => $this->support->id]);
    }
}
