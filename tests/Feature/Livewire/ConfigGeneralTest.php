<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ConfigGeneral;
use App\Models\ConfigParametro;
use App\Models\LogActividad;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConfigGeneralTest extends TestCase
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

    private function crearParametro(array $overrides = []): ConfigParametro
    {
        return ConfigParametro::create(array_merge([
            'modulo' => 'financiero',
            'clave' => 'costoConsultaBase',
            'valor' => json_encode('1'),
        ], $overrides));
    }

    // --- Acceso ---

    public function test_staff_puede_ver_la_pagina(): void
    {
        $this->actingAs($this->staffUsuario)
            ->get('/admin/config')
            ->assertOk();
    }

    public function test_cliente_no_staff_recibe_403(): void
    {
        $this->actingAs($this->clienteUsuario)
            ->get('/admin/config')
            ->assertForbidden();
    }

    public function test_visitante_es_redirigido_a_login(): void
    {
        $this->get('/admin/config')
            ->assertRedirect(route('login'));
    }

    // --- Listado agrupado por módulo ---

    public function test_lista_parametros_agrupados_por_modulo(): void
    {
        $this->crearParametro(['clave' => 'costoConsultaBase']);
        $this->crearParametro(['clave' => 'tasaCambioUsdCreditos', 'valor' => json_encode('10')]);
        $this->crearParametro(['modulo' => 'cache', 'clave' => 'ttlDatosExternosSegundos', 'valor' => json_encode('86400')]);

        Livewire::actingAs($this->staffUsuario)
            ->test(ConfigGeneral::class)
            ->assertSee('financiero')
            ->assertSee('cache')
            ->assertSee('costoConsultaBase')
            ->assertSee('tasaCambioUsdCreditos')
            ->assertSee('ttlDatosExternosSegundos');
    }

    // --- Edición válida ---

    public function test_edicion_valida_persiste_y_actualiza_campos(): void
    {
        $this->crearParametro();

        Livewire::actingAs($this->staffUsuario)
            ->test(ConfigGeneral::class)
            ->call('iniciarEdicion', 'financiero', 'costoConsultaBase')
            ->assertSet('editando', 'financiero.costoConsultaBase')
            ->set('valorEditando', '5')
            ->call('guardar', 'financiero', 'costoConsultaBase')
            ->assertSet('editando', null);

        $param = ConfigParametro::where('modulo', 'financiero')
            ->where('clave', 'costoConsultaBase')
            ->first();

        $this->assertSame(5, json_decode($param->valor));
        $this->assertSame($this->staff->id, $param->actualizado_por);
        $this->assertNotNull($param->actualizado_en);
    }

    // --- Edición inválida ---

    public function test_edicion_valor_json_invalido_no_persiste(): void
    {
        $this->crearParametro();

        Livewire::actingAs($this->staffUsuario)
            ->test(ConfigGeneral::class)
            ->call('iniciarEdicion', 'financiero', 'costoConsultaBase')
            ->set('valorEditando', '{invalido')
            ->call('guardar', 'financiero', 'costoConsultaBase')
            ->assertHasErrors(['valorEditando']);

        $param = ConfigParametro::where('modulo', 'financiero')
            ->where('clave', 'costoConsultaBase')
            ->first();

        $this->assertSame('1', json_decode($param->valor));
        $this->assertNull($param->actualizado_por);
    }

    public function test_edicion_valor_no_escalar_no_persiste(): void
    {
        $this->crearParametro();

        Livewire::actingAs($this->staffUsuario)
            ->test(ConfigGeneral::class)
            ->call('iniciarEdicion', 'financiero', 'costoConsultaBase')
            ->set('valorEditando', '[1, 2, 3]')
            ->call('guardar', 'financiero', 'costoConsultaBase')
            ->assertHasErrors(['valorEditando']);

        $param = ConfigParametro::where('modulo', 'financiero')
            ->where('clave', 'costoConsultaBase')
            ->first();

        $this->assertSame('1', json_decode($param->valor));
    }

    // --- Log de auditoría ---

    public function test_guardar_crea_log_auditoria_con_antes_despues(): void
    {
        $this->crearParametro();

        Livewire::actingAs($this->staffUsuario)
            ->test(ConfigGeneral::class)
            ->call('iniciarEdicion', 'financiero', 'costoConsultaBase')
            ->set('valorEditando', '5')
            ->call('guardar', 'financiero', 'costoConsultaBase');

        $this->assertDatabaseHas('logs_actividad', [
            'accion' => 'config.actualizada',
            'actor_id' => $this->staffUsuario->id,
            'actor_sistema' => false,
        ]);

        $log = LogActividad::where('accion', 'config.actualizada')->firstOrFail();
        $this->assertSame('financiero', $log->detalle['modulo']);
        $this->assertSame('costoConsultaBase', $log->detalle['clave']);
        $this->assertSame('1', $log->detalle['valor_anterior']);
        $this->assertSame(5, $log->detalle['valor_nuevo']);
    }

    // --- Cancelar ---

    public function test_cancelar_edicion_limpia_estado(): void
    {
        $this->crearParametro();

        Livewire::actingAs($this->staffUsuario)
            ->test(ConfigGeneral::class)
            ->call('iniciarEdicion', 'financiero', 'costoConsultaBase')
            ->assertSet('editando', 'financiero.costoConsultaBase')
            ->call('cancelarEdicion')
            ->assertSet('editando', null)
            ->assertSet('valorEditando', '');
    }
}