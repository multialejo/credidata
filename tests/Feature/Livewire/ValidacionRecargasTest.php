<?php

namespace Tests\Feature\Livewire;

use App\Enums\EstadoRecarga;
use App\Jobs\SendRecargaEmail;
use App\Jobs\SendRecargaRechazadaEmail;
use App\Livewire\ValidacionRecargas;
use App\Models\Cliente;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ValidacionRecargasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $staffAdmin;

    private Usuario $staffSupport;

    private Usuario $clienteUsuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffAdmin = Usuario::create([
            'uid' => 'staff-admin-uid',
            'email' => 'admin@test.com',
            'nombre' => 'Staff Admin',
            'roles' => json_encode(['staff']),
        ]);
        Staff::create([
            'usuario_id' => $this->staffAdmin->id,
            'rol_staff' => 'admin',
            'fecha_asignacion' => now(),
        ]);

        $this->staffSupport = Usuario::create([
            'uid' => 'staff-support-uid',
            'email' => 'support@test.com',
            'nombre' => 'Staff Support',
            'roles' => json_encode(['staff']),
        ]);
        Staff::create([
            'usuario_id' => $this->staffSupport->id,
            'rol_staff' => 'support',
            'fecha_asignacion' => now(),
        ]);

        $this->clienteUsuario = Usuario::create([
            'uid' => 'cliente-uid',
            'email' => 'cliente@test.com',
            'nombre' => 'Cliente Test',
            'roles' => json_encode(['cliente']),
        ]);
        $this->cliente = Cliente::create([
            'usuario_id' => $this->clienteUsuario->id,
            'saldo_creditos' => 0,
        ]);
    }

    private function crearRecargaPendiente(array $overrides = []): Recarga
    {
        return Recarga::create(array_merge([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'transferencia',
            'monto_usd' => 20.00,
            'creditos_obtenidos' => 100,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => 'TRF-'.uniqid(),
            'comprobante_url' => '/storage/comprobantes/deposito.jpg',
            'fecha' => now(),
        ], $overrides));
    }

    // --- Acceso ---

    public function test_staff_puede_ver_la_pagina(): void
    {
        $this->actingAs($this->staffAdmin)
            ->get('/admin/recargas')
            ->assertOk();
    }

    public function test_visitante_es_redirigido_a_login(): void
    {
        $this->get('/admin/recargas')
            ->assertRedirect(route('login'));
    }

    public function test_cliente_no_staff_recibe_403(): void
    {
        $this->actingAs($this->clienteUsuario)
            ->get('/admin/recargas')
            ->assertForbidden();
    }

    // --- Tabla de pendientes ---

    public function test_muestra_tabla_con_cliente_comprobante_y_acciones(): void
    {
        $this->crearRecargaPendiente([
            'referencia_externa' => 'TRF-PEND-1',
            'comprobante_url' => '/storage/comprobantes/pend-1.jpg',
        ]);

        $otroUsuario = Usuario::create([
            'uid' => 'cliente-otro-uid',
            'email' => 'otro@test.com',
            'nombre' => 'Otro Cliente',
            'roles' => json_encode(['cliente']),
        ]);
        $otroCliente = Cliente::create([
            'usuario_id' => $otroUsuario->id,
            'saldo_creditos' => 0,
        ]);
        Recarga::create([
            'cliente_id' => $otroCliente->id,
            'metodo' => 'transferencia',
            'monto_usd' => 50.00,
            'creditos_obtenidos' => 250,
            'estado' => EstadoRecarga::Completada,
            'referencia_externa' => 'TRF-COPL-1',
            'fecha' => now(),
        ]);

        Livewire::actingAs($this->staffAdmin)
            ->test(ValidacionRecargas::class)
            ->assertSee('Cliente Test')
            ->assertSee('cliente@test.com')
            ->assertSee('Ver comprobante')
            ->assertSee('Acreditar')
            ->assertSee('Rechazar')
            ->assertDontSee('otro@test.com');
    }

    // --- Acreditar (admin) ---

    public function test_acreditar_incrementa_saldo_loggea_y_quita_de_lista(): void
    {
        Queue::fake();

        $recarga = $this->crearRecargaPendiente();

        Livewire::actingAs($this->staffAdmin)
            ->test(ValidacionRecargas::class)
            ->call('toggleAcreditar', $recarga->id)
            ->set('creditos', 100)
            ->set('motivo', 'Depósito bancario confirmado por WhatsApp')
            ->call('acreditar', $recarga->id)
            ->assertHasNoErrors()
            ->assertDontSee('cliente@test.com');

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'completada',
            'creditos_obtenidos' => 100,
        ]);

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'recarga.acreditada_manual',
            'actor_id' => $this->staffAdmin->id,
        ]);
        $log = LogActividad::where('accion', 'recarga.acreditada_manual')->firstOrFail();
        $this->assertSame($recarga->id, $log->detalle['recarga_id']);
        $this->assertSame(100, $log->detalle['creditos']);
        $this->assertSame('Depósito bancario confirmado por WhatsApp', $log->detalle['motivo']);

        Queue::assertPushed(SendRecargaEmail::class, function ($job) use ($recarga) {
            return $job->recarga->id === $recarga->id;
        });
    }

    public function test_acreditar_requiere_creditos_y_motivo_validos(): void
    {
        $recarga = $this->crearRecargaPendiente();

        Livewire::actingAs($this->staffAdmin)
            ->test(ValidacionRecargas::class)
            ->call('toggleAcreditar', $recarga->id)
            ->set('creditos', 0)
            ->set('motivo', '')
            ->call('acreditar', $recarga->id)
            ->assertHasErrors(['creditos', 'motivo']);

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_staff_no_admin_no_ve_opcion_acreditar(): void
    {
        $recarga = $this->crearRecargaPendiente();

        Livewire::actingAs($this->staffSupport)
            ->test(ValidacionRecargas::class)
            ->assertSee('Cliente Test')
            ->assertSee('Rechazar')
            ->assertDontSee('Acreditar')
            ->call('toggleAcreditar', $recarga->id)
            ->set('creditos', 100)
            ->set('motivo', 'Intento con rol support')
            ->call('acreditar', $recarga->id)
            ->assertHasErrors(['acreditacion']);

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseMissing('logs_actividad', [
            'accion' => 'recarga.acreditada_manual',
        ]);
    }

    // --- Rechazar ---

    public function test_rechazar_marca_rechazada_loggea_y_quita_de_lista(): void
    {
        Queue::fake();

        $recarga = $this->crearRecargaPendiente();

        Livewire::actingAs($this->staffAdmin)
            ->test(ValidacionRecargas::class)
            ->call('toggleRechazar', $recarga->id)
            ->set('motivoRechazo', 'Comprobante ilegible, contactar al cliente')
            ->call('rechazar', $recarga->id)
            ->assertHasNoErrors()
            ->assertDontSee('cliente@test.com');

        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'rechazada',
            'rechazada_por' => $this->staffAdmin->id,
        ]);
        $this->assertNotNull($recarga->fresh()->rechazada_at);

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'recarga.rechazada',
            'actor_id' => $this->staffAdmin->id,
        ]);

        Queue::assertPushed(SendRecargaRechazadaEmail::class, function ($job) use ($recarga) {
            return $job->recarga->id === $recarga->id;
        });
    }
}