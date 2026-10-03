<?php

namespace Tests\Feature\Livewire;

use App\Livewire\HistorialConsultas;
use App\Models\Cliente;
use App\Models\Consulta;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HistorialConsultasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::create([
            'uid' => 'cliente-historial-uid',
            'email' => 'historial@test.com',
            'nombre' => 'Cliente Historial',
            'roles' => ['cliente'],
        ]);
        $this->cliente = Cliente::create([
            'usuario_id' => $this->usuario->id,
            'saldo_creditos' => 100,
        ]);
    }

    public function test_muestra_estadisticas_de_consultas(): void
    {
        $this->crearConsulta(3, true);
        $this->crearConsulta(2, false);

        Livewire::actingAs($this->usuario)
            ->test(HistorialConsultas::class)
            ->assertViewHas('estadisticas', [
                'total' => 2,
                'creditos' => 5,
                'tasa_exito' => 50,
            ]);
    }

    public function test_las_estadisticas_respetan_los_filtros(): void
    {
        $this->crearConsulta(3, true);
        $this->crearConsulta(2, false);

        Livewire::actingAs($this->usuario)
            ->test(HistorialConsultas::class)
            ->set('filtroResultado', 'exito')
            ->assertViewHas('estadisticas', [
                'total' => 1,
                'creditos' => 3,
                'tasa_exito' => 100,
            ]);
    }

    private function crearConsulta(int $creditos, bool $exitosa): Consulta
    {
        return Consulta::create([
            'cliente_id' => $this->cliente->id,
            'tipo' => 'cedula',
            'identificador' => '1713175071',
            'origen' => 'api',
            'creditos_gastados' => $creditos,
            'exitosa' => $exitosa,
            'fecha' => now(),
        ]);
    }
}
