<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ValidacionRecargas;
use App\Models\Cliente;
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

    public function test_support_can_accredit_direct_transfer(): void
    {
        Storage::fake('local');
        Livewire::actingAs($this->support)->test(ValidacionRecargas::class)
            ->set('clienteEmail', 'cliente@test.com')->set('montoUsd', 5)
            ->set('referenciaBancaria', 'BANK-LW-002')->set('motivo', 'Transferencia confirmada por soporte')
            ->set('comprobante', UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf'))
            ->call('acreditar')->assertHasNoErrors();

        $this->assertSame(50, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', ['metodo' => 'transferencia', 'referencia_externa' => 'BANK-LW-002']);
    }
}
