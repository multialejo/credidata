<?php

namespace Tests\Feature\Livewire;

use App\Livewire\LogsActividad;
use App\Models\LogActividad;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogsActividadTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $staffUsuario;

    private Staff $staff;

    private Usuario $clienteUsuario;

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
            'nombre' => 'Juan Cliente',
            'roles' => json_encode(['cliente']),
        ]);
    }

    private function crearLog(array $overrides = []): LogActividad
    {
        // Si el override pide una fecha específica, la aplicamos vía forceFill
        // para respetar el principio de que `fecha` NO está en $fillable:
        // la auditoría temporal debe venir del server.
        $fechaCustom = $overrides['fecha'] ?? null;
        unset($overrides['fecha']);

        $log = LogActividad::create(array_merge([
            'accion' => 'STAFF_CREADO',
            'actor_id' => $this->staffUsuario->id,
            'actor_sistema' => false,
            'detalle' => ['rol_staff' => 'admin'],
            'ip_origen' => '127.0.0.1',
        ], $overrides));

        if ($fechaCustom !== null) {
            $log->forceFill(['fecha' => $fechaCustom])->save();
        }

        return $log;
    }

    // --- Acceso ---

    public function test_staff_puede_ver_la_pagina(): void
    {
        $this->actingAs($this->staffUsuario)
            ->get('/admin/logs')
            ->assertOk();
    }

    public function test_cliente_no_staff_recibe_403(): void
    {
        $this->actingAs($this->clienteUsuario)
            ->get('/admin/logs')
            ->assertForbidden();
    }

    public function test_visitante_es_redirigido_a_login(): void
    {
        $this->get('/admin/logs')
            ->assertRedirect(route('login'));
    }

    // --- Filtros ---

    public function test_filtro_por_accion(): void
    {
        $this->crearLog(['accion' => 'API_KEY_GENERADA']);
        $this->crearLog(['accion' => 'CLIENTE_REGISTRADO']);
        $this->crearLog(['accion' => 'API_KEY_REVOCADA']);

        // El <select> de filtro renderiza siempre las 10 acciones conocidas como
        // <option>, por lo que `assertDontSee('CLIENTE_REGISTRADO')` sobre el HTML
        // completo sería siempre falso. Verificamos el efecto del filtro
        // contando filas en la tabla paginada.
        Livewire::actingAs($this->staffUsuario)
            ->test(LogsActividad::class)
            ->set('accion', 'API_KEY_GENERADA')
            ->assertViewHas('logs', function ($logs) {
                return $logs->count() === 1
                    && $logs->first()->accion === 'API_KEY_GENERADA';
            });
    }

    public function test_filtro_por_rango_fechas(): void
    {
        $this->crearLog(['accion' => 'antigua', 'fecha' => now()->subDays(10)]);
        $this->crearLog(['accion' => 'reciente', 'fecha' => now()]);

        Livewire::actingAs($this->staffUsuario)
            ->test(LogsActividad::class)
            ->set('fechaDesde', now()->subDays(2)->format('Y-m-d'))
            ->set('fechaHasta', now()->addDay()->format('Y-m-d'))
            ->assertViewHas('logs', function ($logs) {
                return $logs->count() === 1
                    && $logs->first()->accion === 'reciente';
            });
    }

    public function test_filtro_por_actor_email(): void
    {
        $otroUsuario = Usuario::create([
            'uid' => 'otro-uid',
            'email' => 'otro@test.com',
            'nombre' => 'Otro',
            'roles' => json_encode(['cliente']),
        ]);

        $this->crearLog(['accion' => 'staff_accion', 'actor_id' => $this->staffUsuario->id]);
        $this->crearLog(['accion' => 'cliente_accion', 'actor_id' => $otroUsuario->id]);

        Livewire::actingAs($this->staffUsuario)
            ->test(LogsActividad::class)
            ->set('actorEmail', 'staff@')
            ->assertSee('staff_accion')
            ->assertDontSee('cliente_accion');
    }

    public function test_filtros_combinados(): void
    {
        $this->crearLog([
            'accion' => 'API_KEY_GENERADA',
            'actor_id' => $this->staffUsuario->id,
            'fecha' => now(),
        ]);
        $this->crearLog([
            'accion' => 'API_KEY_REVOCADA',
            'actor_id' => $this->staffUsuario->id,
            'fecha' => now()->subDays(5),
        ]);
        $this->crearLog([
            'accion' => 'API_KEY_GENERADA',
            'actor_id' => $this->clienteUsuario->id,
            'fecha' => now(),
        ]);

        // El filtro combinado debe dejar exactamente 1 log: la API_KEY_GENERADA
        // hecha por el staff en el rango de fechas reciente. Ni la revocada
        // (fuera de rango) ni la del cliente (actor que no matchea staff@) deben
        // pasar. Verificamos el contenido exacto de la fila resultante (el
        // assertSee del email del actor) y el conteo.
        Livewire::actingAs($this->staffUsuario)
            ->test(LogsActividad::class)
            ->set('accion', 'API_KEY_GENERADA')
            ->set('actorEmail', 'staff@')
            ->set('fechaDesde', now()->subDay()->format('Y-m-d'))
            ->assertSee('staff@test.com')
            ->assertViewHas('logs', function ($logs) {
                return $logs->count() === 1
                    && $logs->first()->accion === 'API_KEY_GENERADA'
                    && $logs->first()->actor_id === $this->staffUsuario->id;
            });
    }

    // --- Paginación ---

    public function test_paginacion_25_por_pagina(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->crearLog(['accion' => "test_{$i}"]);
        }

        Livewire::actingAs($this->staffUsuario)
            ->test(LogsActividad::class)
            ->assertViewHas('logs', function ($logs) {
                return $logs->count() === 25 && $logs->total() === 30;
            });
    }

    // --- Inmutabilidad (REQ §9.3) ---

    public function test_vista_no_expone_acciones_de_edicion(): void
    {
        Livewire::actingAs($this->staffUsuario)
            ->test(LogsActividad::class)
            ->assertDontSee('Editar')
            ->assertDontSee('Eliminar')
            ->assertDontSee('Borrar')
            ->assertDontSee('Modificar')
            ->assertDontSee('Edit')
            ->assertDontSee('Delete')
            ->assertDontSee('Remove')
            ->assertDontSee('Destroy');
    }

    // --- Sistema ---

    public function test_actor_sistema_se_muestra_como_sistema(): void
    {
        $this->crearLog(['accion' => 'STAFF_CREADO', 'actor_sistema' => true, 'actor_id' => null]);

        Livewire::actingAs($this->staffUsuario)
            ->test(LogsActividad::class)
            ->assertSee('Sistema');
    }

    public function test_actor_normal_se_muestra_con_email(): void
    {
        $this->crearLog(['accion' => 'STAFF_CREADO', 'actor_sistema' => false]);

        Livewire::actingAs($this->staffUsuario)
            ->test(LogsActividad::class)
            ->assertSee('staff@test.com');
    }

    // --- Reset ---

    public function test_reset_filters_limpia_todo(): void
    {
        Livewire::actingAs($this->staffUsuario)
            ->test(LogsActividad::class)
            ->set('accion', 'API_KEY_GENERADA')
            ->set('fechaDesde', '2026-01-01')
            ->set('actorEmail', 'staff@')
            ->call('resetFilters')
            ->assertSet('accion', '')
            ->assertSet('fechaDesde', '')
            ->assertSet('fechaHasta', '')
            ->assertSet('actorEmail', '');
    }
}
