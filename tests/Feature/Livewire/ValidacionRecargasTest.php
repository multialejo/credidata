<?php

namespace Tests\Feature\Livewire;

use App\Enums\EstadoRecarga;
use App\Livewire\ValidacionRecargas;
use App\Models\Cliente;
use App\Models\Recarga;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $user = Usuario::create(['uid' => 'cliente-uid', 'email' => 'cliente@test.com', 'nombre' => 'Cliente', 'roles' => ['cliente']]);
        $this->cliente = Cliente::create(['usuario_id' => $user->id, 'saldo_creditos' => 0]);
    }

    public function test_admin_can_accredit_direct_transfer(): void
    {
        Storage::fake('local');
        Livewire::actingAs($this->admin)->test(ValidacionRecargas::class)
            ->set('clienteEmail', 'cliente@test.com')->set('montoUsd', 5)
            ->set('referenciaBancaria', 'BANK-LW-001')->set('motivo', 'Transferencia confirmada por WhatsApp')
            ->set('comprobante', UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf'))
            ->call('acreditar')->assertHasNoErrors();

        $this->assertSame(50, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', ['metodo' => 'transferencia', 'referencia_externa' => 'BANK-LW-001']);
    }

    public function test_non_admin_cannot_accredit_and_provider_pending_is_rejectable(): void
    {
        Recarga::create(['cliente_id' => $this->cliente->id, 'metodo' => 'paypal', 'estado' => EstadoRecarga::Pendiente, 'referencia_externa' => 'PP-LW-001', 'fecha' => now()]);
        Livewire::actingAs($this->support)->test(ValidacionRecargas::class)
            ->assertDontSee('wire:model="clienteEmail"', false)->assertSee('Rechazar');
    }
}
