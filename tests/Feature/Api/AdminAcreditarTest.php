<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoRecarga;
use App\Jobs\SendRecargaEmail;
use App\Models\Cliente;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminAcreditarTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $staffAdmin;

    private Staff $admin;

    private Usuario $staffValidator;

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
        $this->admin = Staff::create([
            'usuario_id' => $this->staffAdmin->id,
            'rol_staff' => 'admin',
            'fecha_asignacion' => now(),
        ]);

        $this->staffValidator = Usuario::create([
            'uid' => 'staff-validator-uid',
            'email' => 'validator@test.com',
            'nombre' => 'Staff Validator',
            'roles' => json_encode(['staff']),
        ]);
        Staff::create([
            'usuario_id' => $this->staffValidator->id,
            'rol_staff' => 'validator',
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

    // --- Acreditar: feliz ---

    public function test_admin_acredita_recarga_pendiente_incrementa_saldo_y_loggea(): void
    {
        Queue::fake();

        $recarga = $this->crearRecargaPendiente();

        $response = $this->actingAs($this->staffAdmin, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
                'creditos' => 100,
                'motivo' => 'Depósito bancario confirmado por WhatsApp',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('exito', true);
        $response->assertJsonPath('mensaje', 'Recarga acreditada');
        $response->assertJsonPath('datos.recarga.estado', 'completada');
        $response->assertJsonPath('datos.recarga.creditos_obtenidos', 100);
        $response->assertJsonPath('datos.recarga.cliente.nombre', 'Cliente Test');

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);

        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'completada',
            'creditos_obtenidos' => 100,
        ]);

        // Log de auditoría staff (REQ §9.3)
        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'recarga.acreditada_manual',
            'actor_id' => $this->staffAdmin->id,
        ]);
        $log = LogActividad::where('accion', 'recarga.acreditada_manual')->firstOrFail();
        $this->assertSame($recarga->id, $log->detalle['recarga_id']);
        $this->assertSame(100, $log->detalle['creditos']);
        $this->assertSame('Depósito bancario confirmado por WhatsApp', $log->detalle['motivo']);

        // Email de confirmación al cliente (afterCommit del RecargaService)
        Queue::assertPushed(SendRecargaEmail::class, function ($job) use ($recarga) {
            return $job->recarga->id === $recarga->id;
        });
    }

    // --- Acreditar: 409 por transición inválida ---

    public function test_acreditar_sobre_recarga_completada_retorna_409(): void
    {
        $this->cliente->update(['saldo_creditos' => 50]);
        $saldoInicial = (int) $this->cliente->fresh()->saldo_creditos;
        $recarga = $this->crearRecargaPendiente(['estado' => EstadoRecarga::Completada]);

        $response = $this->actingAs($this->staffAdmin, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
                'creditos' => 100,
                'motivo' => 'Intento de doble acreditación',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('exito', false);
        $response->assertJsonPath('error.tipo', 'TRANSICION_INVALIDA');

        $this->assertSame($saldoInicial, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseMissing('logs_actividad', [
            'accion' => 'recarga.acreditada_manual',
        ]);
    }

    public function test_acreditar_sobre_recarga_rechazada_retorna_409(): void
    {
        $recarga = $this->crearRecargaPendiente(['estado' => EstadoRecarga::Rechazada]);

        $response = $this->actingAs($this->staffAdmin, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
                'creditos' => 100,
                'motivo' => 'Intento de acreditar una recarga rechazada',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.tipo', 'TRANSICION_INVALIDA');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'rechazada',
        ]);
    }

    // --- Acreditar: idempotencia / doble clic ---

    public function test_doble_clic_acredita_una_sola_vez(): void
    {
        $recarga = $this->crearRecargaPendiente();

        $primera = $this->actingAs($this->staffAdmin, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
                'creditos' => 100,
                'motivo' => 'Primer clic del staff',
            ]);

        $primera->assertStatus(200);
        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);

        $segunda = $this->actingAs($this->staffAdmin, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
                'creditos' => 100,
                'motivo' => 'Segundo clic (doble)',
            ]);

        $segunda->assertStatus(409);
        $segunda->assertJsonPath('error.tipo', 'TRANSICION_INVALIDA');

        $this->assertSame(100, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertSame(1, LogActividad::where('accion', 'recarga.acreditada_manual')->count());
        $this->assertSame(1, LogActividad::where('accion', 'recarga.acreditada')->count());
    }

    // --- Acreditar: validación ---

    public function test_validacion_creditos_y_motivo(): void
    {
        $recarga = $this->crearRecargaPendiente();

        $sinCreditos = $this->actingAs($this->staffAdmin, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
                'motivo' => 'Motivo sin créditos',
            ]);
        $sinCreditos->assertStatus(422);
        $this->assertStringContainsString(
            'obligatorio',
            (string) collect($sinCreditos->json('errors.creditos'))->first(),
        );

        $creditosCero = $this->actingAs($this->staffAdmin, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
                'creditos' => 0,
                'motivo' => 'Motivo con créditos en cero',
            ]);
        $creditosCero->assertStatus(422);

        $sinMotivo = $this->actingAs($this->staffAdmin, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
                'creditos' => 100,
                'motivo' => '',
            ]);
        $sinMotivo->assertStatus(422);
        $this->assertStringContainsString(
            'obligatorio',
            (string) collect($sinMotivo->json('errors.motivo'))->first(),
        );

        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'pendiente',
        ]);
        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
    }

    // --- Acreditar: solo admin ---

    public function test_staff_no_admin_recibe_403(): void
    {
        $recarga = $this->crearRecargaPendiente();

        $response = $this->actingAs($this->staffValidator, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
                'creditos' => 100,
                'motivo' => 'Intento de acreditar con rol validator',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('exito', false);
        $response->assertJsonPath('error.tipo', 'ROL_NO_AUTORIZADO');

        $this->assertSame(0, (int) $this->cliente->fresh()->saldo_creditos);
        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseMissing('logs_actividad', [
            'accion' => 'recarga.acreditada_manual',
        ]);
    }

    public function test_usuario_sin_rol_staff_recibe_403(): void
    {
        $recarga = $this->crearRecargaPendiente();

        $response = $this->actingAs($this->clienteUsuario, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
                'creditos' => 100,
                'motivo' => 'Intento de acreditar como cliente',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error.tipo', 'NO_STAFF');
    }

    public function test_requiere_autenticacion(): void
    {
        $recarga = $this->crearRecargaPendiente();

        $response = $this->postJson("/api/v1/admin/recargas/{$recarga->id}/acreditar", [
            'creditos' => 100,
            'motivo' => 'Intento sin autenticación',
        ]);

        $response->assertStatus(401);
    }

    // --- Pendientes (F-01): paginado 15, cliente + comprobante ---

    public function test_pendientes_paginado_15_por_pagina_con_cliente_y_comprobante(): void
    {
        $clienteAUsuario = Usuario::create([
            'uid' => 'cliente-a-uid',
            'email' => 'clientea@test.com',
            'nombre' => 'Cliente A',
            'roles' => json_encode(['cliente']),
        ]);
        $clienteA = Cliente::create([
            'usuario_id' => $clienteAUsuario->id,
            'saldo_creditos' => 0,
        ]);

        $clienteBUsuario = Usuario::create([
            'uid' => 'cliente-b-uid',
            'email' => 'clienteb@test.com',
            'nombre' => 'Cliente B',
            'roles' => json_encode(['cliente']),
        ]);
        $clienteB = Cliente::create([
            'usuario_id' => $clienteBUsuario->id,
            'saldo_creditos' => 0,
        ]);

        $recargasA = [];
        for ($i = 0; $i < 15; $i++) {
            $recargasA[] = Recarga::create([
                'cliente_id' => $clienteA->id,
                'metodo' => 'transferencia',
                'monto_usd' => 20.00,
                'creditos_obtenidos' => 100,
                'estado' => EstadoRecarga::Pendiente,
                'referencia_externa' => 'TRF-A-'.$i,
                'comprobante_url' => "/storage/comprobantes/a-{$i}.jpg",
                'fecha' => now()->subMinutes($i + 1),
            ]);
        }

        $recargaB = Recarga::create([
            'cliente_id' => $clienteB->id,
            'metodo' => 'transferencia',
            'monto_usd' => 40.00,
            'creditos_obtenidos' => 200,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => 'TRF-B-1',
            'comprobante_url' => '/storage/comprobantes/b-1.jpg',
            'fecha' => now(),
        ]);

        // Completada: no debe aparecer en el listado de pendientes.
        Recarga::create([
            'cliente_id' => $clienteA->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 50,
            'estado' => EstadoRecarga::Completada,
            'referencia_externa' => 'PP-C-1',
            'fecha' => now(),
        ]);

        $pagina1 = $this->actingAs($this->staffAdmin, 'sanctum')
            ->getJson('/api/v1/admin/recargas/pendientes');

        $pagina1->assertStatus(200);
        $pagina1->assertJsonPath('exito', true);
        $pagina1->assertJsonPath('datos.paginacion.total', 16);
        $pagina1->assertJsonPath('datos.paginacion.per_page', 15);
        $pagina1->assertJsonPath('datos.paginacion.current_page', 1);
        $pagina1->assertJsonPath('datos.paginacion.last_page', 2);
        $pagina1->assertJsonCount(15, 'datos.recargas');

        $idsPagina1 = collect($pagina1->json('datos.recargas'))->pluck('id')->all();
        $this->assertCount(15, $idsPagina1);
        $this->assertContains($recargaB->id, $idsPagina1);
        $this->assertNotContains($recargasA[14]->id, $idsPagina1);

        $item = $pagina1->json('datos.recargas')[0];
        $this->assertSame('/storage/comprobantes/b-1.jpg', $item['comprobante_url']);
        $this->assertSame('Cliente B', $item['cliente']['nombre']);
        $this->assertSame('clienteb@test.com', $item['cliente']['email']);
        $this->assertSame('pendiente', $item['estado']);

        $pagina2 = $this->actingAs($this->staffAdmin, 'sanctum')
            ->getJson('/api/v1/admin/recargas/pendientes?page=2');

        $pagina2->assertStatus(200);
        $pagina2->assertJsonCount(1, 'datos.recargas');
        $this->assertSame($recargasA[14]->id, $pagina2->json('datos.recargas.0.id'));
        $this->assertSame('Cliente A', $pagina2->json('datos.recargas.0.cliente.nombre'));
        $this->assertSame('clientea@test.com', $pagina2->json('datos.recargas.0.cliente.email'));
        $this->assertSame('/storage/comprobantes/a-14.jpg', $pagina2->json('datos.recargas.0.comprobante_url'));
    }

    public function test_pendientes_requiere_staff(): void
    {
        $response = $this->actingAs($this->clienteUsuario, 'sanctum')
            ->getJson('/api/v1/admin/recargas/pendientes');

        $response->assertStatus(403);
        $response->assertJsonPath('error.tipo', 'NO_STAFF');
    }

    public function test_pendientes_sin_recargas_retorna_lista_vacia(): void
    {
        $response = $this->actingAs($this->staffAdmin, 'sanctum')
            ->getJson('/api/v1/admin/recargas/pendientes');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'datos.recargas');
        $response->assertJsonPath('datos.paginacion.total', 0);
    }
}