<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ActivarColaborador;
use App\Livewire\EnviarAporte;
use App\Livewire\PanelSaldo;
use App\Models\Aporte;
use App\Models\Cliente;
use App\Models\Colaborador;
use App\Models\Usuario;
use App\Services\ColaboracionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ColaboracionCreditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_perfil_de_colaborador_muestra_resumen_operativo(): void
    {
        $usuario = $this->crearUsuario();
        Colaborador::create([
            'usuario_id' => $usuario->id,
            'creditos_acreditados' => 7,
            'aportes_aprobados' => 3,
            'total_aportes' => 4,
        ]);

        Livewire::actingAs($usuario)
            ->test(ActivarColaborador::class)
            ->assertSeeText('Tu actividad de colaboración')
            ->assertSeeText('Créditos acreditados')
            ->assertSeeText('7')
            ->assertSeeText('Estado de tus aportes')
            ->assertSeeText('Aprobados')
            ->assertSeeText('3')
            ->assertDontSeeText('Aportes recientes')
            ->assertDontSeeText('Aún no tienes aportes');
    }

    public function test_tablero_muestra_metricas_y_limite_diario_sin_exponer_aportes(): void
    {
        $usuario = $this->crearUsuario();
        $colaborador = Colaborador::create([
            'usuario_id' => $usuario->id,
            'creditos_acreditados' => 2,
            'aportes_aprobados' => 1,
            'aportes_rechazados' => 0,
        ]);
        DB::table('config_parametros')
            ->where('modulo', 'colaboracion')
            ->where('clave', 'limiteDiarioPorColaborador')
            ->update(['valor' => json_encode(4)]);

        Aporte::create([
            'colaborador_id' => $colaborador->id,
            'identificador_relacionado' => '1713175071',
            'tipo_dato' => 'telefono',
            'valor' => '0991234567',
            'estado' => 'pendiente',
            'fecha' => now(),
        ]);
        Aporte::create([
            'colaborador_id' => $colaborador->id,
            'identificador_relacionado' => '1713175071',
            'tipo_dato' => 'email',
            'valor' => 'persona@example.com',
            'estado' => 'aprobado',
            'recompensa_creditos' => 2,
            'fecha' => now()->subDay(),
        ]);

        Livewire::actingAs($usuario)
            ->test(ActivarColaborador::class)
            ->assertSeeText('Pendientes de revisión')
            ->assertSeeText('1 / 4')
            ->assertSeeText('Créditos acreditados')
            ->assertSeeText('Estado de tus aportes')
            ->assertDontSeeText('Aportes recientes')
            ->assertDontSee('1713175071')
            ->assertDontSee('0991234567')
            ->assertDontSee('persona@example.com');
    }

    public function test_vista_de_activacion_conserva_terminos_para_quien_aun_no_es_colaborador(): void
    {
        $usuario = $this->crearUsuario();

        Livewire::actingAs($usuario)
            ->test(ActivarColaborador::class)
            ->assertSeeText('Programa de colaboradores')
            ->assertSeeText('Antes de empezar')
            ->assertSeeText('Activar colaboración')
            ->assertSeeText(ColaboracionService::TERMINOS);
    }

    public function test_panel_de_saldo_muestra_recompensa_de_colaboracion(): void
    {
        $usuario = $this->crearUsuario();
        $colaborador = Colaborador::create(['usuario_id' => $usuario->id]);

        Aporte::create([
            'colaborador_id' => $colaborador->id,
            'identificador_relacionado' => '1713175071',
            'tipo_dato' => 'telefono',
            'valor' => '0999999999',
            'estado' => 'aprobado',
            'recompensa_creditos' => 2,
            'recompensado_en' => now(),
            'fecha' => now(),
        ]);

        Livewire::actingAs($usuario)
            ->test(PanelSaldo::class)
            ->assertSeeText('Recompensa por aporte de telefono')
            ->assertSeeText('Colaboración')
            ->assertSeeText('+2');
    }

    public function test_enviar_aporte_muestra_cantidad_de_creditos_acreditados(): void
    {
        $usuario = $this->crearUsuario();
        $colaborador = Colaborador::create(['usuario_id' => $usuario->id]);
        $aporte = Aporte::create([
            'colaborador_id' => $colaborador->id,
            'identificador_relacionado' => '1713175071',
            'tipo_dato' => 'telefono',
            'valor' => '0999999999',
            'estado' => 'aprobado',
            'recompensa_creditos' => 1,
            'recompensado_en' => now(),
            'fecha' => now(),
        ]);

        $service = Mockery::mock(ColaboracionService::class);
        $service->shouldReceive('registrarAporte')->once()->andReturn($aporte);
        $this->app->instance(ColaboracionService::class, $service);

        Livewire::actingAs($usuario)
            ->test(EnviarAporte::class)
            ->set('identificador', '1713175071')
            ->set('tipoDato', 'telefono')
            ->set('valor', '0999999999')
            ->call('enviar')
            ->assertSeeText('se acreditaron 1 créditos a tu cuenta');
    }

    public function test_enviar_aporte_rechaza_telefono_con_formato_no_canonico(): void
    {
        $usuario = $this->crearUsuario();
        Colaborador::create(['usuario_id' => $usuario->id]);

        Livewire::actingAs($usuario)
            ->test(EnviarAporte::class)
            ->set('identificador', '1713175071')
            ->set('tipoDato', 'telefono')
            ->set('valor', '099 123 4567')
            ->call('enviar')
            ->assertHasErrors(['valor']);
    }

    private function crearUsuario(): Usuario
    {
        $usuario = Usuario::create([
            'uid' => fake()->unique()->uuid(),
            'email' => fake()->unique()->safeEmail(),
            'nombre' => 'Cliente Colaborador',
            'roles' => ['cliente'],
        ]);
        Cliente::create(['usuario_id' => $usuario->id, 'saldo_creditos' => 0]);

        return $usuario;
    }
}
