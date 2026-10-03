<?php

namespace Tests\Feature\Livewire;

use App\Enums\EstadoRecarga;
use App\Jobs\SendRecargaEmail;
use App\Livewire\ValidacionRecargas;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Recarga;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ValidacionRecargasTest extends TestCase
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
        ConfigParametro::create(['modulo' => 'financiero', 'clave' => 'tasaCambioUsdCreditos', 'valor' => json_encode(10)]);
        $user = Usuario::create(['uid' => 'cliente-uid', 'email' => 'cliente@test.com', 'nombre' => 'Cliente', 'roles' => ['cliente']]);
        $this->cliente = Cliente::create(['usuario_id' => $user->id, 'saldo_creditos' => 0]);
    }

    public function test_admin_approves_pending_transfer_and_customer_is_emailed(): void
    {
        Storage::fake('local');
        Queue::fake();
        $recarga = $this->crearTransferenciaPendiente('BANK-LW-001');

        Livewire::actingAs($this->admin)->test(ValidacionRecargas::class)
            ->assertSee('cliente@test.com')
            ->assertSee('Ver comprobante')
            ->call('aprobar', $recarga->id)
            ->assertHasNoErrors()
            ->assertSee('Transferencia aprobada y créditos acreditados.');

        $this->assertSame(50, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', ['id' => $recarga->id, 'estado' => 'completada']);
        Queue::assertPushed(SendRecargaEmail::class);
    }

    public function test_support_can_approve_a_client_submitted_transfer(): void
    {
        Storage::fake('local');
        Queue::fake();
        $recarga = $this->crearTransferenciaPendiente('BANK-LW-002');

        Livewire::actingAs($this->support)->test(ValidacionRecargas::class)
            ->call('aprobar', $recarga->id)
            ->assertHasNoErrors();

        $this->assertSame(50, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', ['id' => $recarga->id, 'estado' => 'completada']);
        Queue::assertPushed(SendRecargaEmail::class);
    }

    public function test_completed_transfer_cannot_be_approved_twice(): void
    {
        Storage::fake('local');
        Queue::fake();
        $recarga = $this->crearTransferenciaPendiente('BANK-LW-003');

        $component = Livewire::actingAs($this->admin)->test(ValidacionRecargas::class);
        $component->call('aprobar', $recarga->id)->assertHasNoErrors();
        $component->call('aprobar', $recarga->id);

        $this->assertSame(50, (int) $this->cliente->fresh()->saldo_creditos);
        Queue::assertPushedTimes(SendRecargaEmail::class, 1);
    }

    public function test_pending_transfer_with_non_exact_credit_amount_cannot_be_approved(): void
    {
        Storage::fake('local');
        Queue::fake();
        $recarga = $this->crearTransferenciaPendiente('BANK-NON-EXACT');
        $recarga->update(['monto_usd' => 5.01]);

        Livewire::actingAs($this->admin)->test(ValidacionRecargas::class)
            ->call('aprobar', $recarga->id)
            ->assertHasErrors('aprobacion');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(EstadoRecarga::Pendiente, $recarga->fresh()->estado);
        Queue::assertNothingPushed();
    }

    private function crearTransferenciaPendiente(string $referencia): Recarga
    {
        $path = UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf')->store('recargas/comprobantes', 'local');

        return Recarga::create([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'transferencia',
            'monto_usd' => 5,
            'creditos_obtenidos' => 50,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => $referencia,
            'comprobante_url' => $path,
        ]);
    }
}
