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

    private Usuario $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = Usuario::create([
            'email' => 'staff-logs@test.com',
            'nombre' => 'Staff Logs',
            'roles' => ['staff'],
        ]);
        Staff::create(['usuario_id' => $this->staff->id, 'rol_staff' => 'admin']);
    }

    public function test_muestra_detalle_json_y_actor_sistema(): void
    {
        LogActividad::create([
            'accion' => 'consulta.realizada',
            'actor_id' => $this->staff->id,
            'detalle' => ['identificador' => '0102030405', 'creditos' => 1],
            'fecha' => now(),
        ]);
        LogActividad::create([
            'accion' => 'tarea.ejecutada',
            'actor_sistema' => true,
            'fecha' => now()->subMinute(),
        ]);

        Livewire::actingAs($this->staff)
            ->test(LogsActividad::class)
            ->assertSeeText('consulta.realizada')
            ->assertSeeText('Mostrar JSON')
            ->assertSeeText('"identificador": "0102030405"')
            ->assertSeeText('Sistema');
    }

    public function test_filtra_por_rango_usando_fecha_del_evento(): void
    {
        $antiguo = LogActividad::create([
            'accion' => 'log.antiguo',
            'actor_sistema' => true,
        ]);
        $antiguo->fecha = now()->subDays(2);
        $antiguo->save();

        LogActividad::create([
            'accion' => 'log.actual',
            'actor_sistema' => true,
        ]);

        Livewire::actingAs($this->staff)
            ->test(LogsActividad::class)
            ->set('fecha_desde', now()->toDateString())
            ->assertSeeText('log.actual')
            ->assertDontSeeText('log.antiguo');
    }
}
