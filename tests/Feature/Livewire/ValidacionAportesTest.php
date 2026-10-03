<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ValidacionAportes;
use App\Models\Aporte;
use App\Models\Colaborador;
use App\Models\Usuario;
use App\Services\ColaboracionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ValidacionAportesTest extends TestCase
{
    use RefreshDatabase;

    public function test_detalle_modal_se_abre_y_se_cierra(): void
    {
        $staff = Usuario::create([
            'uid' => 'staff-aportes-uid',
            'email' => 'staff-aportes@test.com',
            'nombre' => 'Staff Aportes',
            'roles' => ['staff'],
        ]);
        $colaboradorUsuario = Usuario::create([
            'uid' => 'colaborador-aportes-uid',
            'email' => 'colaborador@test.com',
            'nombre' => 'Colaborador',
            'roles' => ['cliente', 'colaborador'],
        ]);
        $colaborador = Colaborador::create(['usuario_id' => $colaboradorUsuario->id]);
        $aporte = Aporte::create([
            'colaborador_id' => $colaborador->id,
            'identificador_relacionado' => '1713175071',
            'tipo_dato' => 'telefono',
            'valor' => '0991234567',
            'estado' => 'pendiente',
        ]);

        $service = Mockery::mock(ColaboracionService::class);
        $service->shouldReceive('valorActual')->once()->with(Mockery::on(fn (Aporte $value) => $value->is($aporte)))->andReturn('0987654321');
        $this->app->instance(ColaboracionService::class, $service);

        Livewire::actingAs($staff)
            ->test(ValidacionAportes::class)
            ->call('ver', $aporte->id)
            ->assertSet('detalleId', $aporte->id)
            ->assertSeeText('Valor actual')
            ->assertSeeText('0987654321')
            ->call('cerrarDetalle')
            ->assertSet('detalleId', null);
    }
}
