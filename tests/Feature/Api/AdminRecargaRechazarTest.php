<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoRecarga;
use App\Jobs\SendRecargaRechazadaEmail;
use App\Models\Cliente;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminRecargaRechazarTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $staffUsuario;

    private Staff $staff;

    private Usuario $clienteUsuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffUsuario = Usuario::create([
            'uid' => 'staff-uid',
            'email' => 'staff@test.com',
            'nombre' => 'Staff Admin',
            'roles' => json_encode(['staff']),
        ]);

        $this->staff = Staff::create([
            'usuario_id' => $this->staffUsuario->id,
            'rol_staff' => 'admin',
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

    private function crearRecarga(array $overrides = []): Recarga
    {
        return Recarga::create(array_merge([
            'cliente_id' => $this->cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 10.00,
            'creditos_obtenidos' => 100,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => 'REF-'.uniqid(),
            'fecha' => now(),
        ], $overrides));
    }

    public function test_staff_puede_rechazar_una_recarga_pendiente(): void
    {
        Queue::fake();

        $recarga = $this->crearRecarga();
        $motivo = 'Comprobante ilegible, contactar al cliente';

        $response = $this->actingAs($this->staffUsuario, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/rechazar", [
                'motivo' => $motivo,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('exito', true);
        $response->assertJsonPath('mensaje', 'Recarga rechazada');
        $response->assertJsonPath('datos.recarga.estado', 'rechazada');
        $response->assertJsonPath('datos.recarga.motivo_rechazo', $motivo);
        $response->assertJsonPath('datos.recarga.rechazada_por', 'Staff Admin');
        $this->assertNotNull($response->json('datos.recarga.rechazada_at'));

        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'rechazada',
            'motivo_rechazo' => $motivo,
            'rechazada_por' => $this->staffUsuario->id,
        ]);

        $this->assertNotNull($recarga->fresh()->rechazada_at);

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'recarga.rechazada',
            'actor_id' => $this->staffUsuario->id,
        ]);

        $log = LogActividad::where('accion', 'recarga.rechazada')->first();
        $this->assertSame($recarga->id, $log->detalle['recarga_id']);
        $this->assertSame($motivo, $log->detalle['motivo']);
        $this->assertSame($recarga->referencia_externa, $log->detalle['referencia_externa']);

        Queue::assertPushed(SendRecargaRechazadaEmail::class, function ($job) use ($recarga) {
            return $job->recarga->id === $recarga->id;
        });
    }

    public function test_validacion_motivo_requerido_y_longitud_minima(): void
    {
        $recarga = $this->crearRecarga();

        $responseVacio = $this->actingAs($this->staffUsuario, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/rechazar", [
                'motivo' => '',
            ]);

        $responseVacio->assertStatus(422);
        $this->assertStringContainsString(
            'obligatorio',
            (string) collect($responseVacio->json('errors.motivo'))->first(),
        );

        $responseCorto = $this->actingAs($this->staffUsuario, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/rechazar", [
                'motivo' => 'corto',
            ]);

        $responseCorto->assertStatus(422);
        $this->assertStringContainsString(
            '10 caracteres',
            (string) collect($responseCorto->json('errors.motivo'))->first(),
        );

        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_usuario_sin_rol_staff_retorna_403(): void
    {
        $recarga = $this->crearRecarga();

        $response = $this->actingAs($this->clienteUsuario, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/rechazar", [
                'motivo' => 'Intento de rechazo no autorizado',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error.tipo', 'NO_STAFF');

        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_transicion_invalida_retorna_409_para_recarga_completada(): void
    {
        $recarga = $this->crearRecarga(['estado' => EstadoRecarga::Completada]);

        $response = $this->actingAs($this->staffUsuario, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/rechazar", [
                'motivo' => 'Intento de rechazo de una ya completada',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.tipo', 'TRANSICION_INVALIDA');

        $this->assertDatabaseHas('recargas', [
            'id' => $recarga->id,
            'estado' => 'completada',
        ]);
    }

    public function test_transicion_invalida_retorna_409_para_recarga_ya_rechazada(): void
    {
        $recarga = $this->crearRecarga(['estado' => EstadoRecarga::Rechazada]);

        $response = $this->actingAs($this->staffUsuario, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/rechazar", [
                'motivo' => 'Intento de rechazo de una ya rechazada',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.tipo', 'TRANSICION_INVALIDA');
    }

    public function test_rechazar_no_acredita_saldo(): void
    {
        $this->cliente->update(['saldo_creditos' => 50]);
        $saldoInicial = (int) $this->cliente->fresh()->saldo_creditos;
        $recarga = $this->crearRecarga();

        $response = $this->actingAs($this->staffUsuario, 'sanctum')
            ->postJson("/api/v1/admin/recargas/{$recarga->id}/rechazar", [
                'motivo' => 'Comprobante no corresponde al monto',
            ]);

        $response->assertStatus(200);

        $this->assertSame($saldoInicial, (int) $this->cliente->fresh()->saldo_creditos);
    }

    public function test_requiere_autenticacion(): void
    {
        $recarga = $this->crearRecarga();

        $response = $this->postJson("/api/v1/admin/recargas/{$recarga->id}/rechazar", [
            'motivo' => 'Intento sin autenticación',
        ]);

        $response->assertStatus(401);
    }
}
